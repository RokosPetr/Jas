using System.ComponentModel.DataAnnotations;
using System.Threading;
using System.Threading.Tasks;
using Jas.Data.JasIdentityApp;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;

namespace Jas.Areas.Ptg.Pages
{
    [Area("Ptg")]
    [Authorize(Roles = "PTG - admin")]
    public class EditCustomerModel : PageModel
    {
        private readonly UserManager<JasUser> _userManager;

        public EditCustomerModel(UserManager<JasUser> userManager)
        {
            _userManager = userManager;
        }

        [BindProperty(SupportsGet = true)]
        public string Id { get; set; } = string.Empty;

        public string UserName { get; private set; } = string.Empty;

        [BindProperty]
        [EmailAddress]
        [Display(Name = "E-mail")]
        public string Email { get; set; } = string.Empty;

        [BindProperty]
        [DataType(DataType.Password)]
        [Display(Name = "Nové heslo")]
        [StringLength(100, MinimumLength = 5)]
        public string? NewPassword { get; set; }

        [BindProperty]
        [DataType(DataType.Password)]
        [Display(Name = "Nové heslo znovu")]
        [Compare("NewPassword", ErrorMessage = "Hesla se neshodují.")]
        public string? ConfirmPassword { get; set; }

        [TempData]
        public string? StatusMessage { get; set; }

        [BindProperty(SupportsGet = true)]
        public string? ReturnSearch { get; set; }

        public async Task<IActionResult> OnGetAsync(CancellationToken ct)
        {
            if (string.IsNullOrEmpty(Id))
            {
                return RedirectToPage("./ManagePtgUsers");
            }

            var user = await _userManager.FindByIdAsync(Id);
            if (user == null)
            {
                return NotFound();
            }

            UserName = user.UserName ?? string.Empty;
            Email = user.Email ?? string.Empty;

            return Page();
        }

        public async Task<IActionResult> OnPostAsync(CancellationToken ct)
        {
            if (!ModelState.IsValid)
            {
                return Page();
            }

            var user = await _userManager.FindByIdAsync(Id);
            if (user == null)
            {
                return NotFound();
            }

            // základní údaje pro přístup do aplikace
            user.Email = Email;
            // uživatele nepřejmenováváme, ale zajistíme potvrzení e-mailu
            user.EmailConfirmed = true;
            user.LockoutEnd = null;
            user.LockoutEnabled = false;
            user.AccessFailedCount = 0;

            var updateResult = await _userManager.UpdateAsync(user);
            if (!updateResult.Succeeded)
            {
                foreach (var error in updateResult.Errors)
                {
                    ModelState.AddModelError(string.Empty, error.Description);
                }
                return Page();
            }

            // volitelná změna hesla
            if (!string.IsNullOrWhiteSpace(NewPassword))
            {
                var resetToken = await _userManager.GeneratePasswordResetTokenAsync(user);
                var resetResult = await _userManager.ResetPasswordAsync(user, resetToken, NewPassword);

                if (!resetResult.Succeeded)
                {
                    foreach (var error in resetResult.Errors)
                    {
                        ModelState.AddModelError(string.Empty, error.Description);
                    }
                    return Page();
                }
            }

            StatusMessage = "Zákazník byl upraven.";
            return RedirectToPage("./EditCustomer", new { id = Id, ReturnSearch });
        }
    }
}
