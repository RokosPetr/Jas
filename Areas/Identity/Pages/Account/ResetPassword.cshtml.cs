// Licensed to the .NET Foundation under one or more agreements.
// The .NET Foundation licenses this file to you under the MIT license.
#nullable disable

using System;
using System.ComponentModel.DataAnnotations;
using System.Text;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.AspNetCore.WebUtilities;
using Jas.Data.JasIdentityApp;

namespace Jas.Areas.Identity.Pages.Account
{
    public class ResetPasswordModel : PageModel
    {
        private readonly UserManager<JasUser> _userManager;

        public ResetPasswordModel(UserManager<JasUser> userManager)
        {
            _userManager = userManager;
        }

        [BindProperty]
        public InputModel Input { get; set; }

        public class InputModel
        {
            [Required(ErrorMessage = "Toto pole je povinné")]
            [StringLength(100, ErrorMessage = "{0} musí být nejméně {2} a maximálně {1} dlouhé.", MinimumLength = 6)]
            [DataType(DataType.Password)]
            [Display(Name = "Heslo")]
            public string Password { get; set; }

            [Required(ErrorMessage = "Toto pole je povinné")]
            [DataType(DataType.Password)]
            [Display(Name = "Heslo znovu")]
            [Compare("Password", ErrorMessage = "Hesla se neshodují.")]
            public string ConfirmPassword { get; set; }

            [Required]
            public string Code { get; set; }
            public string UserName { get; set; }


        }

        public async Task<IActionResult> OnGetAsync(string code = null, string userName = null)
        {
            if (code == null && userName == null)
            {
                return BadRequest("Pro resetování hesla je nutné zadat kód a uživatelské jméno.");
            }
            else
            {
                var user = await _userManager.FindByNameAsync(userName);
                if (user == null)
                {
                    // Don't reveal that the user does not exist
                    return RedirectToPage("./ResetPasswordConfirmation");
                }
                Input = new InputModel
                {
                    Code = Encoding.UTF8.GetString(WebEncoders.Base64UrlDecode(code)),
                    UserName = user.UserName
                };
                return Page();
            }
        }

        public async Task<IActionResult> OnPostAsync()
        {
            if (!ModelState.IsValid)
            {
                return Page();
            }

            var user = await _userManager.FindByNameAsync(Input.UserName);
            if (user == null)
            {
                // Don't reveal that the user does not exist
                return RedirectToPage("./ResetPasswordConfirmation");
            }

            var result = await _userManager.ResetPasswordAsync(user, Input.Code, Input.Password);
            if (result.Succeeded)
            {
                //return RedirectToPage("./ResetPasswordConfirmation");
                TempData["messageInfo"] = "Vaše heslo bylo úspěšně změněno";
                return RedirectToPage("./Login");

            }

            foreach (var error in result.Errors)
            {
                ModelState.AddModelError(string.Empty, error.Description);
            }
            return Page();
        }
    }
}
