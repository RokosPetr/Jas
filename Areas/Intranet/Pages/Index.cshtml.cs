using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;

namespace Jas.Areas.Intranet.Pages
{
    [Area("Intranet")]
    [Authorize] // Bude přístupné pouze pro přihlášené, můžete později zpřísnit parametrem Roles
    public class IndexModel : PageModel
    {
        public void OnGet()
        {
            // Initial data load
        }
    }
}
