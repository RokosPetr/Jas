using Jas.Data.JasIdentityApp;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using Newtonsoft.Json;
using System.Text.Json.Serialization;

namespace Jas.Areas.Identity.Pages.Account
{
    [AllowAnonymous]
    public class PhpLoginModel : PageModel
    {
        private readonly UserManager<JasUser> _userManager;
        private readonly SignInManager<JasUser> _signInManager;
        private readonly ILogger<LoginModel> _logger;
        private readonly IConfiguration _configuration;
        private readonly IHttpContextAccessor _httpContextAccessor;

        public PhpLoginModel(SignInManager<JasUser> signInManager, 
            ILogger<LoginModel> logger,
            UserManager<JasUser> userManager,
            IConfiguration configuration,
            IHttpContextAccessor httpContextAccessor)
        {
            _userManager = userManager;
            _signInManager = signInManager;
            _logger = logger;
            _configuration = configuration;
            _httpContextAccessor = httpContextAccessor;
        }

        public string ReturnUrl { get; set; }
        [BindProperty(SupportsGet = true)]
        public string? PhpSessId { get; set; }
        public string? JsonString { get; set; }

        public class PhpUsers
        {
            [JsonPropertyName("phpuser")]
            public List<PhpUser> phpuser { get; set; }
        }
        public class PhpUser
        {
            [JsonPropertyName("name")]
            public String Name { get; set; }
            [JsonPropertyName("username")]
            public String UserName { get; set; }
            [JsonPropertyName("internalLogin")]
            public String InternalLogin { get; set; }
            [JsonPropertyName("email")]
            public String Email { get; set; }
            [JsonPropertyName("store")]
            public int? Store { get; set; }
            [JsonPropertyName("lastLogin")]
            public DateTime LastLogin { get; set; }
        }

        public async Task<IActionResult> OnGetAsync()
        {
            try
            {
                if (PhpSessId is not null)
                {
                    JsonString = await new HttpClient().GetStringAsync(_configuration["PhpLogin:Url"] + PhpSessId);

                    if (JsonString != null)
                    {
                        PhpUsers? phpUsers = JsonConvert.DeserializeObject<PhpUsers>(JsonString);
                        PhpUser? phpUser = phpUsers.phpuser.FirstOrDefault();
                        if (phpUser != null)
                        {
                            var user = await _userManager.Users.FirstOrDefaultAsync(i => i.UserName.Equals(phpUser.UserName));
                            if (user != null)
                            {
                                await _signInManager.SignInAsync(user, isPersistent: false);
                                _httpContextAccessor.HttpContext.Session.SetInt32("ExternalLoggedIn", 1);
                                return LocalRedirect("/");
                            }
                        }
                    }
                }

            }
            catch (Exception e)
            {
                _httpContextAccessor.HttpContext.Session.Remove("ExternalLoggedIn");
                return LocalRedirect("/");
            }

            _httpContextAccessor.HttpContext.Session.Remove("ExternalLoggedIn");
            return LocalRedirect("/");
        }

    }
}
