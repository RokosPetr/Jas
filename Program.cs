using AutoMapper;
using AutoMapper.Data;
using Jas.Application.Abstractions;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasIdentityDb;
using Jas.Data.JasMtzDb;
using Jas.Infrastructure.Images;
using Jas.Services;
using Jas.Services.Mapping;
using Jas.Services.Mapping.Resolvers;
using Jas.Services.Mtz;
using Jas.Services.Ptg;
using Jas.Web.Endpoints;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Identity.UI.Services;
using Microsoft.EntityFrameworkCore;

var builder = WebApplication.CreateBuilder(args);

// Add services to the container.
builder.Services.AddDbContext<JasIdentityAppContext>(options =>
    options.UseSqlServer(builder.Configuration.GetConnectionString("IdentityConnection")));
builder.Services.AddDbContext<JasIdentityDbContext>(options =>
    options.UseSqlServer(builder.Configuration.GetConnectionString("IdentityConnection")));
builder.Services.AddDbContext<JasMtzDbContext>(options =>
    options.UseSqlServer(builder.Configuration.GetConnectionString("MtzConnection")));

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
//builder.Services.AddAutoMapper(typeof(MappingProfile).Assembly);

builder.Services.AddAutoMapper(cfg =>
{
    cfg.AddDataReaderMapping(); // umožní mapování z IDataReader/IDataRecord
    cfg.SourceMemberNamingConvention = new LowerUnderscoreNamingConvention(); // price_jas -> PriceJas (kdyby storca vracela snake_case)
    cfg.DestinationMemberNamingConvention = new PascalCaseNamingConvention();
}, typeof(MappingProfile).Assembly); 

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

builder.Services.Configure<ImageStoreOptions>(builder.Configuration.GetSection("ImageStore"));
builder.Services.AddHttpClient(nameof(LocalImageStore));
builder.Services.AddSingleton<IImageStore, LocalImageStore>(); ;

var app = builder.Build();

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