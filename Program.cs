using AutoMapper;
using AutoMapper.Data;
using DinkToPdf;
using DinkToPdf.Contracts;
using Jas.Application.Abstractions;
using Jas.Application.Abstractions.Ptg;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasIdentityDb;
using Jas.Data.JasMtzDb;
using Jas.Data.JasMtzDb.Interceptors;
using Jas.Data.JasPdfDb;
using Jas.Data.JasDb;
using Jas.Globals;
using Jas.Infrastructure.Images;
using Jas.Infrastructure.Ptg;
using Jas.Services;
using Jas.Services.Mapping;
using Jas.Services.Mapping.Resolvers;
using Jas.Services.Mtz;
using Jas.Services.Ptg;
using Jas.Services.Srv;
using Jas.Web.Endpoints;
using Microsoft.AspNetCore.Authentication;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Identity.UI.Services;
using Microsoft.AspNetCore.WebUtilities;
using Microsoft.EntityFrameworkCore;
using System.Text.RegularExpressions;

var builder = WebApplication.CreateBuilder(args);

// Add services to the container.
builder.Services.AddDbContext<JasIdentityAppContext>(options =>
    options.UseSqlServer(builder.Configuration.GetConnectionString("IdentityConnection")));
builder.Services.AddDbContext<JasIdentityDbContext>(options =>
    options.UseSqlServer(builder.Configuration.GetConnectionString("IdentityConnection")));
builder.Services.AddDbContext<JasMtzDbContext>(options =>
    options.UseSqlServer(builder.Configuration.GetConnectionString("MtzConnection")));
builder.Services.AddDbContext<JasPdfDbContext>(options =>
    options.UseSqlServer(builder.Configuration.GetConnectionString("PdfConnection")));
builder.Services.AddDbContext<JasDbContext>(options =>
    options.UseSqlServer(builder.Configuration.GetConnectionString("JasDbConnection")));

builder.Services.AddDatabaseDeveloperPageExceptionFilter();

builder.Services.AddIdentity<JasUser, IdentityRole>(options =>
{
    options.SignIn.RequireConfirmedAccount = true;
    options.Password.RequireDigit = false;
    options.Password.RequireLowercase = false;
    options.Password.RequireNonAlphanumeric = false;
    options.Password.RequireUppercase = false;
    options.Password.RequiredLength = 5;
})
.AddEntityFrameworkStores<JasIdentityAppContext>()
.AddDefaultTokenProviders();

builder.Services.AddTransient<IEmailSender, EmailSender>(i =>
    new EmailSender(
        builder.Configuration["EmailSenderJas:Host"]!,
        builder.Configuration.GetValue<int>("EmailSenderJas:Port"),
        builder.Configuration.GetValue<bool>("EmailSenderJas:EnableSSL"),
        builder.Configuration["EmailSenderJas:UserName"]!,
        builder.Configuration["EmailSenderJas:Password"]!
    )
);

builder.Services.AddScoped<OrderUserResolver>();
builder.Services.AddScoped<OrderStoreResolver>();
builder.Services.AddScoped<OrderDepartmentResolver>();

//builder.Services.AddScoped<IOrderService, OrderService>();
builder.Services.AddScoped<IUserService, UserService>();
builder.Services.AddScoped<IStoreService, StoreService>();
builder.Services.AddScoped<IDepartmentService, DepartmentService>();
builder.Services.AddScoped<INavbarServiceMtz, NavbarServiceMtz>();
builder.Services.AddScoped<INavbarServicePtg, NavbarServicePtg>();
builder.Services.AddScoped<INavbarServiceSrv, NavbarServiceSrv>();
//builder.Services.AddAutoMapper(typeof(MappingProfile).Assembly);

builder.Services.AddAutoMapper(cfg =>
{
    cfg.AddDataReaderMapping(); // umožní mapování z IDataReader/IDataRecord
    cfg.SourceMemberNamingConvention = new LowerUnderscoreNamingConvention(); // price_jas -> PriceJas (kdyby storca vracela snake_case)
    cfg.DestinationMemberNamingConvention = new PascalCaseNamingConvention();
}, typeof(MappingProfile).Assembly);

builder.Services.AddScoped<IStandDetailReader, StandDetailReader>();
builder.Services.AddScoped<IStandSearchReader, StandSearchReader>();

builder.Services.AddScoped<IRazorRenderer, RazorRenderer>();
builder.Services.AddScoped<IPdfService, PdfService>();

builder.Services.ConfigureApplicationCookie(options =>
{
    options.LoginPath = "/Identity/Account/Login";
    options.AccessDeniedPath = "/Identity/Account/AccessDenied";
});

builder.Services.AddSession(options =>
{
    options.IdleTimeout = TimeSpan.FromMinutes(30);
}); 

builder.Services.AddRazorPages();
builder.Services.AddMemoryCache();

builder.Services.AddRouting(o =>
{
    o.AppendTrailingSlash = true;
});

builder.Services.Configure<ImageStoreOptions>(builder.Configuration.GetSection("ImageStore"));
builder.Services.AddHttpClient(nameof(LocalImageStore));
builder.Services.AddSingleton<IImageStore, LocalImageStore>();

var context = new CustomAssemblyLoadContext();
context.LoadUnmanagedLibrary(Path.Combine(Directory.GetCurrentDirectory(), "bin", "libwkhtmltox.dll"));
builder.Services.AddSingleton<IConverter>(provider => new SynchronizedConverter(new PdfTools()));

builder.Services.AddScoped<MaintenanceRequestHistoryInterceptor>();

builder.Services.AddDbContext<JasMtzDbContext>((sp, options) =>
{
    options.UseSqlServer(builder.Configuration.GetConnectionString("MtzConnection"));
    options.AddInterceptors(sp.GetRequiredService<MaintenanceRequestHistoryInterceptor>());
});

var app = builder.Build();
var ptgPdfRouteRegex = new Regex(
    @"^/files/(?<fileName>(?<slug>.+)-(?<id>\d+)-(?<changeDateSegment>\d{8})-(?<qrPart>qr|noqr)-(?<picturesPart>pics|nopics))\.pdf/?$",
    RegexOptions.Compiled | RegexOptions.IgnoreCase);

// Configure the HTTP request pipeline.
if (app.Environment.IsDevelopment())
{
    app.UseMigrationsEndPoint();
}
else
{
    app.UseExceptionHandler("/Error");
    // The default HSTS value is 30 days. You may want to change this for production scenarios, see https://aka.ms/aspnetcore-hsts.
    app.UseHsts();
}

app.UseHttpsRedirection();

app.Use(async (context, next) =>
{
    var path = context.Request.Path.Value;
    if (!string.IsNullOrEmpty(path))
    {
        var match = ptgPdfRouteRegex.Match(path);
        if (match.Success)
        {
            // Ověřit přihlášení před přepsáním cesty
            var authResult = await context.AuthenticateAsync();
            if (!authResult.Succeeded)
            {
                var originalUrl = $"{context.Request.Scheme}://{context.Request.Host}{context.Request.Path}{context.Request.QueryString}";
                var phpLoginReturnUrl = $"{context.Request.Scheme}://{context.Request.Host}/Identity/Account/PhpLogin?returnUrl={Uri.EscapeDataString(originalUrl)}";
                var phpLoginUrl = $"https://www.mamekoupelny.eu/system/login/?backUrl={Uri.EscapeDataString(phpLoginReturnUrl)}";
                context.Response.Redirect(phpLoginUrl);
                return;
            }

            var fileName = match.Groups["fileName"].Value + ".pdf";
            var fullPath = Path.Combine(app.Environment.WebRootPath, "pdf", "ptg", fileName);

            if (System.IO.File.Exists(fullPath))
            {
                context.Request.Path = $"/pdf/ptg/{fileName}";
                context.Request.QueryString = QueryString.Empty;
            }
            else
            {
                var queryValues = QueryHelpers.ParseQuery(context.Request.QueryString.Value ?? string.Empty)
                    .ToDictionary(
                        item => item.Key,
                        item => item.Value.ToString(),
                        StringComparer.OrdinalIgnoreCase);

                queryValues["handler"] = "Pdf";
                queryValues["changeDateSegment"] = match.Groups["changeDateSegment"].Value;
                queryValues["ChangeDate"] = DateTime.ParseExact(match.Groups["changeDateSegment"].Value, "yyyyMMdd", System.Globalization.CultureInfo.InvariantCulture).ToString("yyyy-MM-dd", System.Globalization.CultureInfo.InvariantCulture);
                queryValues["PrintQr"] = (match.Groups["qrPart"].Value.Equals("qr", StringComparison.OrdinalIgnoreCase)).ToString().ToLowerInvariant();
                queryValues["PrintPictures"] = (match.Groups["picturesPart"].Value.Equals("pics", StringComparison.OrdinalIgnoreCase)).ToString().ToLowerInvariant();
                queryValues["Inline"] = bool.TrueString.ToLowerInvariant();
                queryValues["ForceSaveToDisk"] = bool.TrueString.ToLowerInvariant();
                queryValues["RequestedFileName"] = fileName;

                context.Request.Path = $"/ptg/print/{match.Groups["id"].Value}/{match.Groups["changeDateSegment"].Value}";
                context.Request.QueryString = QueryString.Create(queryValues);
            }
        }
    }

    await next();
});

app.UseStaticFiles();
app.MapImageEndpoints();

app.UseRouting();

app.UseAuthentication();
app.UseAuthorization();
app.UseSession();

app.MapWhen(
    context =>
    {
        var host = context.Request.Host.Host;
        var isLocalhost = host.EndsWith("localhost", StringComparison.OrdinalIgnoreCase);

        // Rozdělit hostname na části
        var parts = host.Split('.');
        var hasSubdomain = (isLocalhost && parts.Length == 2) || parts.Length > 2;

        return hasSubdomain && context.Request.Path == "/";
    },
    subdomainApp =>
    {
        subdomainApp.Run(context =>
        {
            var host = context.Request.Host.Host;
            var subdomain = host.Split('.')[0];

            // Přesměrovat na /{subdomain} (např. /mtz)
            context.Response.Redirect($"/{subdomain}");
            return Task.CompletedTask;
        });
    });

app.MapRazorPages();

app.Run();

//static string GetContentType(string path)
//{
//    var ext = Path.GetExtension(path).ToLowerInvariant();
//    return ext switch
//    {
//        ".jpg" or ".jpeg" => "image/jpeg",
//        ".png" => "image/png",
//        ".gif" => "image/gif",
//        _ => "application/octet-stream"
//    };
//}