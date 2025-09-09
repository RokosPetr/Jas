// Licensed to the .NET Foundation under one or more agreements.
// The .NET Foundation licenses this file to you under the MIT license.
#nullable disable

using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Jas.Helpers;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Identity.UI.Services;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.AspNetCore.WebUtilities;
using System;
using System.ComponentModel.DataAnnotations;
using System.Text;
using System.Text.Encodings.Web;
using System.Threading.Tasks;

namespace Jas.Areas.Identity.Pages.Account
{
    public class ForgotPasswordModel : PageModel
    {
        private readonly UserManager<JasUser> _userManager;
        private readonly IEmailSender _emailSender;
        private readonly JasIdentityAppContext _dbContext;

        public ForgotPasswordModel(UserManager<JasUser> userManager, IEmailSender emailSender, JasIdentityAppContext dbContext)
        {
            _userManager = userManager;
            _emailSender = emailSender;
            _dbContext = dbContext;
        }

        /// <summary>
        ///     This API supports the ASP.NET Core Identity default UI infrastructure and is not intended to be used
        ///     directly from your code. This API may change or be removed in future releases.
        /// </summary>
        [BindProperty]
        public InputModel Input { get; set; }

        /// <summary>
        ///     This API supports the ASP.NET Core Identity default UI infrastructure and is not intended to be used
        ///     directly from your code. This API may change or be removed in future releases.
        /// </summary>
        public class InputModel
        {
            /// <summary>
            ///     This API supports the ASP.NET Core Identity default UI infrastructure and is not intended to be used
            ///     directly from your code. This API may change or be removed in future releases.
            /// </summary>
            [Required]
            public string Email { get; set; }
        }

        public EmailModel ForgotPasswordEmail { get; set; }

        public class EmailModel
        {
            public string Name { get; set; }
            public string CallbackUrl { get; set; }
        }

        public async Task<IActionResult> OnPostAsync()
        {
            if (ModelState.IsValid)
            {
                JasUser user = new JasUser();
                if (EmailHelper.IsValidEmail(Input.Email))
                {
                    try
                    {
                        user = await _userManager.FindByEmailAsync(Input.Email);
                        if (user == null)
                        {
                            ModelState.AddModelError(string.Empty, "Zadaný email nenalezen");
                            return Page();
                        }
                    }
                    catch (Exception e)
                    {
                        if (e.Message == "Sequence contains more than one element")
                        {
                            ModelState.AddModelError(string.Empty, "Zadaný email má více uživatelů");
                            return Page();
                        }
                    }
                }
                else
                {
                    var users = _userManager.Users.Where(i => i.InternalLogin == Input.Email);
                    if (!users.Any())
                    {
                        users = _userManager.Users.Where(i => i.UserName == Input.Email);
                        if (!users.Any())
                        {
                            ModelState.AddModelError(string.Empty, String.Format("Uživatel {0} nenalezen", Input.Email));
                            return Page();
                        }
                    }
                    if (users.Count() == 1)
                    {
                        user = users.First();
                    }
                    else
                    {
                        ModelState.AddModelError(string.Empty, String.Format("Uživateli {0} nelze zaslat zapomenuté heslo", Input.Email));
                        return Page();
                    }
                }

                if (user == null || !(await _userManager.IsEmailConfirmedAsync(user)))
                {
                    // Don't reveal that the user does not exist or is not confirmed
                    return RedirectToPage("./ForgotPasswordConfirmation");
                }

                // For more information on how to enable account confirmation and password reset please 
                // visit https://go.microsoft.com/fwlink/?LinkID=532713

                string code = await _userManager.GeneratePasswordResetTokenAsync(user);
                code = WebEncoders.Base64UrlEncode(Encoding.UTF8.GetBytes(code));
                string callbackUrl = Url.Page(
                    "/Account/ResetPassword",
                    pageHandler: null,
                    values: new { area = "Identity", code, user.UserName },
                    protocol: Request.Scheme);

                this.ForgotPasswordEmail = new EmailModel();
                this.ForgotPasswordEmail.Name = user.Name;
                this.ForgotPasswordEmail.CallbackUrl = callbackUrl;
                string sEmail = this.RenderViewAsync("ForgotPasswordEmail").Result;

                await _emailSender.SendEmailAsync(
                    user.Email,
                    "Koupelny JaS - Obnovení hesla",
                    sEmail);

                TempData["messageInfo"] = "Na Váš email byl odeslaný odkaz na změnu hesla";
                return RedirectToPage("./Login");
            }

            return Page();

        }
    }
}
