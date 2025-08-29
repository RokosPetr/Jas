using Jas.Data.JasIdentityApp;
using Jas.Data.JasIdentityDb;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

public class CreateEshopUsersModel : PageModel
{
    private readonly UserManager<JasUser> _userManager;
    private readonly IUserStore<JasUser> _userStore;
    private readonly IUserEmailStore<JasUser> _emailStore;
    private readonly ILogger<CreateEshopUsersModel> _logger;
    private readonly JasIdentityDbContext _identityDbContext;

    public CreateEshopUsersModel(
        UserManager<JasUser> userManager,
        IUserStore<JasUser> userStore,
        ILogger<CreateEshopUsersModel> logger,
        JasIdentityDbContext identityDbContext)
    {
        _userManager = userManager;
        _userStore = userStore;
        _emailStore = GetEmailStore();
        _logger = logger;
        _identityDbContext = identityDbContext;
    }

    public string StatusMessage { get; set; }

    public async Task<IActionResult> OnGetAsync()
    {
        var users = await _identityDbContext.ViEshopUsers.Where(w => w.Login != null && w.Ico != null).ToListAsync();
        int created = 0, skipped = 0;

        foreach (var vUser in users)
        {
            if (string.IsNullOrWhiteSpace(vUser.Login))
                continue;

            var existing = await _userManager.FindByNameAsync(vUser.Login);
            if (existing != null)
            {
                skipped++;
                continue;
            }

            JasUser user = CreateUser(vUser);
            await _userStore.SetUserNameAsync(user, vUser.Login, CancellationToken.None);
            await _emailStore.SetEmailAsync(user, vUser.EmailOdberatele ?? vUser.EmailObchodnika ?? "", CancellationToken.None);

            var password = string.IsNullOrWhiteSpace(vUser.Heslo) ? "DefaultPass123!" : vUser.Heslo;
            var result = await _userManager.CreateAsync(user, password);

            if (result.Succeeded)
                created++;
            else
                _logger.LogWarning($"Chyba při vytváření uživatele {vUser.Login}: {string.Join(", ", result.Errors.Select(e => e.Description))}");
        }

        StatusMessage = $"Vytvořeno: {created}, Přeskočeno: {skipped}";
        return Page();
    }

    private JasUser CreateUser(ViEshopUser v)
    {
        return new JasUser
        {
            UserName = v.Login,
            Email = v.EmailOdberatele,
            PhoneNumber = v.Telefon,
            InternalLogin = v.Login,
            Ico = v.Ico,
            Name = string.Format("{0} {1}", v.Prijmeni, v.Jmeno)
        };
    }

    private IUserEmailStore<JasUser> GetEmailStore()
    {
        if (!_userManager.SupportsUserEmail)
            throw new NotSupportedException("User store must support emails.");

        return (IUserEmailStore<JasUser>)_userStore;
    }
}
