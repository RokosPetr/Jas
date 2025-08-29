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
using Microsoft.AspNetCore.Rewrite;
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
builder.Services.AddAutoMapper(typeof(MappingProfile).Assembly);

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

//app.MapGet("/", context =>
//{
//    context.Response.Redirect("/mtz");
//    return Task.CompletedTask;
//});

//app.MapWhen(
//    context => context.Request.Host.Host.StartsWith("mtz.") && context.Request.Path == "/",
//    mtzApp =>
//    {
//        mtzApp.Run(context =>
//        {
//            context.Response.Redirect("/mtz");
//            return Task.CompletedTask;
//        });
//    });

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

//app.MapMethods("/images/{domain}/{**path}", new[] { "HEAD" }, async (HttpContext context, string domain, string path) =>
//{
//    var rootDir = Directory.GetCurrentDirectory();
//    var localPath = Path.Combine(rootDir, "wwwroot", "images", domain, path.Replace('/', Path.DirectorySeparatorChar));

//    // Lokálně už existuje?
//    if (System.IO.File.Exists(localPath))
//    {
//        context.Response.StatusCode = StatusCodes.Status200OK;
//        return;
//    }

//    // Zkus stáhnout (stejná logika jako v GET, ale bez fallbacku)
//    var candidates = new[]
//    {
//        $"https://www.{domain}/{path}",
//        $"https://{domain}/{path}"
//    };

//    foreach (var url in candidates)
//    {
//        try
//        {
//            using var http = new HttpClient { Timeout = TimeSpan.FromSeconds(5) };
//            var bytes = await http.GetByteArrayAsync(url, context.RequestAborted);

//            Directory.CreateDirectory(Path.GetDirectoryName(localPath)!);
//            await System.IO.File.WriteAllBytesAsync(localPath, bytes, context.RequestAborted);

//            context.Response.StatusCode = StatusCodes.Status200OK;
//            return;
//        }
//        catch
//        {
//            // ignore a zkus další
//        }
//    }

//    // Externě taky není → vrať 404 (žádný fallback u HEAD!)
//    context.Response.StatusCode = StatusCodes.Status404NotFound;
//});

//app.MapMethods("/images/{domain}/{**path}", new[] { "GET", "HEAD" }, async (HttpContext context, string domain, string path) =>
//{
//    static string GetContentType(string p)
//    {
//        var ext = Path.GetExtension(p).ToLowerInvariant();
//        return ext switch
//        {
//            ".jpg" or ".jpeg" => "image/jpeg",
//            ".png" => "image/png",
//            ".webp" => "image/webp",
//            ".gif" => "image/gif",
//            _ => "application/octet-stream"
//        };
//    }

//    async Task<bool> TryRefreshAsync(string fullLocalPath, string domain, string path, CancellationToken ct, int timeoutSeconds = 5)
//    {
//        var urlsToTry = new[]
//        {
//            $"https://www.{domain}/{path}",
//            $"https://{domain}/{path}"
//        };

//        using var http = new HttpClient { Timeout = TimeSpan.FromSeconds(timeoutSeconds) };

//        foreach (var url in urlsToTry)
//        {
//            try
//            {
//                var bytes = await http.GetByteArrayAsync(url, ct);
//                Directory.CreateDirectory(Path.GetDirectoryName(fullLocalPath)!);
//                await File.WriteAllBytesAsync(fullLocalPath, bytes, ct);
//                return true;
//            }
//            catch
//            {
//                // ignore and try next
//            }
//        }
//        return false;
//    }

//    var rootDir = Directory.GetCurrentDirectory();
//    var relLocalPath = Path.Combine("wwwroot", "images", domain, path.Replace('/', Path.DirectorySeparatorChar));
//    var fullLocalPath = Path.Combine(rootDir, relLocalPath);

//    var method = context.Request.Method.ToUpperInvariant();
//    var ttl = TimeSpan.FromHours(1);

//    // helper: nastavíme klientský cache header
//    void SetClientCacheHeaders(string contentPath)
//    {
//        context.Response.ContentType = GetContentType(contentPath);
//        context.Response.Headers["Cache-Control"] = "public, max-age=3600"; // 1 hodina
//        // volitelně Last-Modified + 304:
//        var last = File.GetLastWriteTimeUtc(contentPath);
//        context.Response.Headers["Last-Modified"] = last.ToString("R");
//        if (context.Request.Headers.TryGetValue("If-Modified-Since", out var ims))
//        {
//            if (DateTimeOffset.TryParse(ims, out var sinceUtc) && last <= sinceUtc.UtcDateTime.AddSeconds(1))
//            {
//                context.Response.StatusCode = StatusCodes.Status304NotModified;
//            }
//        }
//    }

//    bool LocalExists() => System.IO.File.Exists(fullLocalPath);
//    bool IsStale()
//    {
//        var age = DateTime.UtcNow - File.GetLastWriteTimeUtc(fullLocalPath);
//        return age > ttl;
//    }

//    // ----- HEAD -----
//    if (method == "HEAD")
//    {
//        if (LocalExists())
//        {
//            // když je starý, zkusíme refresh na pozadí (neblokujeme HEAD)
//            if (IsStale())
//            {
//                _ = Task.Run(() => TryRefreshAsync(fullLocalPath, domain, path, CancellationToken.None));
//            }

//            context.Response.StatusCode = StatusCodes.Status200OK;
//            context.Response.ContentType = GetContentType(fullLocalPath);
//            return;
//        }

//        // chybí lokálně -> pokus o okamžité stažení (tak jak jsi chtěl)
//        var ok = await TryRefreshAsync(fullLocalPath, domain, path, context.RequestAborted);
//        if (ok)
//        {
//            context.Response.StatusCode = StatusCodes.Status200OK;
//            context.Response.ContentType = GetContentType(fullLocalPath);
//            return;
//        }

//        // NIC – vrátíme 404 (bez fallbacku pro HEAD!)
//        context.Response.StatusCode = StatusCodes.Status404NotFound;
//        return;
//    }

//    // ----- GET -----
//    if (!LocalExists())
//    {
//        // stáhni a ulož (původní chování)
//        var ok = await TryRefreshAsync(fullLocalPath, domain, path, context.RequestAborted);
//        if (!ok)
//        {
//            // fallback
//            var fallbackPath = Path.Combine(rootDir, "wwwroot", "images", "no-picture.png");
//            if (System.IO.File.Exists(fallbackPath))
//            {
//                SetClientCacheHeaders(fallbackPath);
//                await context.Response.SendFileAsync(fallbackPath, context.RequestAborted);
//                return;
//            }
//            context.Response.StatusCode = 404;
//            await context.Response.WriteAsync("Fallback image not found.", context.RequestAborted);
//            return;
//        }
//    }
//    else
//    {
//        // lokálně existuje – když je „stará“, zkus synchronní refresh (rychlý), jinak servíruj staré
//        if (IsStale())
//        {
//            var ok = await TryRefreshAsync(fullLocalPath, domain, path, context.RequestAborted, timeoutSeconds: 3);
//            // pokud nevyjde, prostě pošli starý soubor
//        }
//    }

//    // poslat (nový nebo starý) lokální soubor + klientský cache header
//    SetClientCacheHeaders(fullLocalPath);
//    if (context.Response.StatusCode != StatusCodes.Status304NotModified)
//    {
//        await context.Response.SendFileAsync(fullLocalPath, context.RequestAborted);
//    }
//});

app.MapRazorPages();

app.Run();

static string GetContentType(string path)
{
    var ext = Path.GetExtension(path).ToLowerInvariant();
    return ext switch
    {
        ".jpg" or ".jpeg" => "image/jpeg",
        ".png" => "image/png",
        ".gif" => "image/gif",
        _ => "application/octet-stream"
    };
}