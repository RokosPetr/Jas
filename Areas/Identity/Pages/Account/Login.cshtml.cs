using Jas.Data.JasIdentityApp;
using Jas.Helpers;
using Jas.Services.Mtz;
using Microsoft.AspNetCore.Authentication;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using System.ComponentModel.DataAnnotations;

namespace Jas.Areas.Identity.Pages.Account
{
    [AllowAnonymous]
    public class LoginModel : PageModel
    {
        private readonly UserManager<JasUser> _userManager;
        private readonly SignInManager<JasUser> _signInManager;
        private readonly ILogger<LoginModel> _logger;

        public LoginModel(SignInManager<JasUser> signInManager, 
            ILogger<LoginModel> logger,
            UserManager<JasUser> userManager)
        {
            _userManager = userManager;
            _signInManager = signInManager;
            _logger = logger;
        }

        [BindProperty]
        public InputModel Input { get; set; }

        public IList<AuthenticationScheme> ExternalLogins { get; set; }

        public string ReturnUrl { get; set; }

        [TempData]
        public string ErrorMessage { get; set; }
        public object GlobalCachingProvider { get; private set; }

        public class InputModel
        {
            [Required(ErrorMessage = "Toto pole je povinné")]
            [Display(Name = "JaS login")]
            public string Email { get; set; }

            [Required(ErrorMessage = "Toto pole je povinné")]
            [DataType(DataType.Password)]
            [Display(Name = "Heslo")]
            public string Password { get; set; }

            [Display(Name = "Zapamatovat si")]
            public bool RememberMe { get; set; }
        }

        public async Task OnGetAsync(string returnUrl = null)
        {
            if (!string.IsNullOrEmpty(ErrorMessage))
            {
                ModelState.AddModelError(string.Empty, ErrorMessage);
            }

            returnUrl = returnUrl ?? Url.Content("~/");

            if (returnUrl.Contains("/Account/Login", StringComparison.OrdinalIgnoreCase))
            {
                returnUrl = Url.Content("~/");
            }
            
            // Clear the existing external cookie to ensure a clean login process
            await HttpContext.SignOutAsync(IdentityConstants.ExternalScheme);

            ExternalLogins = (await _signInManager.GetExternalAuthenticationSchemesAsync()).ToList();

            ReturnUrl = returnUrl;
        }

        public async Task<IActionResult> OnPostAsync(string returnUrl = null)
        {
            returnUrl = returnUrl ?? Url.Content("~/");

            if (ModelState.IsValid)
            {
                var userName = Input.Email;
                if (EmailHelper.IsValidEmail(Input.Email))
                {
                    try
                    {
                        var user = await _userManager.FindByEmailAsync(Input.Email);
                        if (user != null)
                        {
                            userName = user.UserName;
                        }
                    }
                    catch (Exception e)
                    {
                        if(e.Message == "Sequence contains more than one element")
                        {
                            ModelState.AddModelError(string.Empty, "Pro přihlášení použijte uživatelské jméno nebo JaS login");
                            return Page();
                        }
                    }
                }

                var internalLogin = _userManager.Users.FirstOrDefault(i => i.InternalLogin == Input.Email);
                if (internalLogin != null)
                {
                    userName = internalLogin.UserName;
                }

                var result = await _signInManager.PasswordSignInAsync(userName, Input.Password, Input.RememberMe, lockoutOnFailure: false);
                if (result.Succeeded)
                {
                    _logger.LogInformation("Uživatel přihlášen.");
                    return LocalRedirect(returnUrl);
                }
                if (result.RequiresTwoFactor)
                {
                    return RedirectToPage("./LoginWith2fa", new { ReturnUrl = returnUrl, RememberMe = Input.RememberMe });
                }
                if (result.IsLockedOut)
                {
                    _logger.LogWarning("Uživatelský účet je uzamčen.");
                    return RedirectToPage("./Lockout");
                }
                else
                {
                    ModelState.AddModelError(string.Empty, "Naplatné přihlášení.");
                    return Page();
                }
            }

            return Page();
        }
    }
}
