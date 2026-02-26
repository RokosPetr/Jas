using System;
using System.Threading.Tasks;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasIdentityDb;
using Jas.Data.JasMtzDb;
using Jas.Models.Srv;
using Jas.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using StatusEnum = Jas.Globals.Srv.Enums.Status;
using RepairCategoryEnum = Jas.Globals.Srv.Enums.RepairCategory;

namespace Jas.Areas.Srv.Pages
{
    [Area("Srv")]
    [Authorize(Roles = "SRV - admin,SRV - user")]
    public class EditModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IUserService _userService;
        private readonly JasIdentityDbContext _identityContext;

        [BindProperty]
        public SrvMaintenanceRequestModel Request { get; set; } = null!;

        [BindProperty]
        public bool InProgress { get; set; }

        public bool IsAdmin => User.IsInRole("SRV - admin");

        // indikace, že aktuálně přihlášený uživatel je zadavatel požadavku
        public bool IsOwner { get; private set; }

        public EditModel(
            JasMtzDbContext context,
            IUserService userService,
            JasIdentityDbContext identityContext)
        {
            _context = context;
            _userService = userService;
            _identityContext = identityContext;
        }

        public async Task<IActionResult> OnGetAsync(int id)
        {
            var entity = await _context.SrvMaintenanceRequests
                .FirstOrDefaultAsync(r => r.Id == id);

            if (entity == null)
            {
                return NotFound();
            }

            Request = new SrvMaintenanceRequestModel
            {
                Id = entity.Id,
                IdUser = entity.IdUser,
                IdSolver = entity.IdSolver,
                IdDepartment = entity.IdDepartment,
                IdStore = entity.IdStore,
                CreatedDate = entity.CreatedDate,
                RemovedDate = entity.RemovedDate,
                DueDate = entity.DueDate,
                PlannedRepairDate = entity.PlannedRepairDate,
                IssueDescription = entity.IssueDescription,
                RepairDescription = entity.RepairDescription,
                Status = entity.Status,
                RepairCategory = entity.RepairCategory,
                EstimatedCost = entity.EstimatedCost,
                ActualCost = entity.ActualCost
            };

            InProgress = Request.Status == (int)StatusEnum.InProgress;

            // je přihlášený uživatel zadavatel?
            IsOwner = _userService.JasUser?.Id == Request.IdUser;

            await LoadDepartmentAsync();
            await LoadUserNameAsync();   // jméno ZADAVATELE podle IdUser
            return Page();
        }

        // původní OnPostAsync přejmenuj na OnPostSaveAsync
        public async Task<IActionResult> OnPostSaveAsync()
        {
            ModelState.Remove("Request.IdUser");
            ModelState.Remove("Request.IdSolver");

            // znovu dopočítat IsOwner i při POSTu (Request.IdUser je v hidden poli)
            IsOwner = _userService.JasUser?.Id == Request.IdUser;

            // VE STAVU "V PROCESU" PŘI ULOŽENÍ VYŽADOVAT POPIS + PLÁN
            if (IsAdmin && Request.Status == (int)StatusEnum.InProgress)
            {
                if (string.IsNullOrWhiteSpace(Request.RepairDescription))
                {
                    ModelState.AddModelError("Request.RepairDescription", "Zadejte popis opravy.");
                }

                if (!Request.PlannedRepairDate.HasValue)
                {
                    ModelState.AddModelError("Request.PlannedRepairDate", "Zadejte plánovaný termín opravy.");
                }

            }

            if (!ModelState.IsValid)
            {
                await LoadDepartmentAsync();
                await LoadUserNameAsync();
                return Page();
            }

            var entity = await _context.SrvMaintenanceRequests
                .FirstOrDefaultAsync(r => r.Id == Request.Id);

            if (entity == null)
            {
                return NotFound();
            }

            // zadavatel může měnit základní pole jen ve stavu NOVÁ
            bool canEditBasic = IsOwner && entity.Status == (int)StatusEnum.New;

                if (canEditBasic)
                {
                    entity.IssueDescription = Request.IssueDescription;

                    bool categoryChanged = entity.RepairCategory != Request.RepairCategory;
                    entity.RepairCategory = Request.RepairCategory;

                    if (categoryChanged)
                    {
                        var now = DateTime.Now;
                        entity.CreatedDate = now;

                        // počet dní odvozený od kategorie opravy
                        var days = entity.RepairCategory switch
                        {
                            (int)RepairCategoryEnum.Light   => 60,
                            (int)RepairCategoryEnum.Serious => 30,
                            (int)RepairCategoryEnum.Urgent  => 5,
                            _                               => 0
                        };

                        if (days > 0)
                        {
                            entity.DueDate = now.AddDays(days);
                        }
                    }
            }

            // Admin – ostatní úpravy (obecné uložení)
            if (IsAdmin)
            {
                entity.RepairDescription = Request.RepairDescription;
                entity.PlannedRepairDate = Request.PlannedRepairDate;
                entity.EstimatedCost = Request.EstimatedCost;
                entity.ActualCost = Request.ActualCost;
            }

            // Přepnutí do stavu V procesu:
            if (InProgress ||
                entity.PlannedRepairDate.HasValue ||
                entity.EstimatedCost.HasValue ||
                !string.IsNullOrWhiteSpace(entity.RepairDescription))
            {
                entity.Status = (int)StatusEnum.InProgress;
            }

            await _context.SaveChangesAsync();

            var filter = GetFilterForStatus(entity.Status);
            return RedirectToPage("/Index", new { filter });
        }

        // Handler pro tlačítko „Vyřídit“
        public async Task<IActionResult> OnPostResolveAsync()
        {
            ModelState.Remove("Request.IdUser");
            ModelState.Remove("Request.IdSolver");

            // Ve stavu „V procesu“ při Vyřídit vyžadovat:
            //  - popis opravy
            //  - plánovaný termín opravy
            //  - plánované náklady
            //  - skutečné náklady
            if (string.IsNullOrWhiteSpace(Request.RepairDescription))
            {
                ModelState.AddModelError("Request.RepairDescription", "Zadejte popis opravy.");
            }

            if (!Request.PlannedRepairDate.HasValue)
            {
                ModelState.AddModelError("Request.PlannedRepairDate", "Zadejte plánovaný termín opravy.");
            }

            if (!Request.EstimatedCost.HasValue)
            {
                ModelState.AddModelError("Request.EstimatedCost", "Zadejte plánované náklady opravy.");
            }

            if (!Request.ActualCost.HasValue)
            {
                ModelState.AddModelError("Request.ActualCost", "Zadejte skutečné náklady.");
            }

            if (!ModelState.IsValid)
            {
                await LoadDepartmentAsync();
                await LoadUserNameAsync();
                return Page();
            }

            var entity = await _context.SrvMaintenanceRequests
                .FirstOrDefaultAsync(r => r.Id == Request.Id);

            if (entity == null)
            {
                return NotFound();
            }

            if (entity.Status == (int)StatusEnum.InProgress)
            {
                // aktualizovat adminovská pole včetně nákladů
                entity.RepairDescription = Request.RepairDescription;
                entity.PlannedRepairDate = Request.PlannedRepairDate;
                entity.EstimatedCost = Request.EstimatedCost;
                entity.ActualCost = Request.ActualCost;

                entity.Status = (int)StatusEnum.Resolved;
                entity.RemovedDate = DateTime.Now;
                await _context.SaveChangesAsync();
            }

            var filter = GetFilterForStatus(entity.Status);
            return RedirectToPage("/Index", new { filter });
        }

        // Handler pro tlačítko „Zrušit“
        public async Task<IActionResult> OnPostCancelAsync()
        {
            ModelState.Remove("Request.IdUser");
            ModelState.Remove("Request.IdSolver");
            // znovu spočítat, zda je přihlášený uživatel zadavatelem
            IsOwner = _userService.JasUser?.Id == Request.IdUser;

            var entity = await _context.SrvMaintenanceRequests
                .FirstOrDefaultAsync(r => r.Id == Request.Id);

            if (entity == null)
            {
                return NotFound();
            }

            // Validace důvodu zrušení podle role a stavu
            if (entity.Status == (int)StatusEnum.New)
            {
                // Nový požadavek:
                //  - pokud ruší zadavatel, důvod nevyžadujeme (doplníme automaticky)
                //  - pokud ruší admin, důvod je povinný
                if (!IsOwner && IsAdmin && string.IsNullOrWhiteSpace(Request.RepairDescription))
                {
                    ModelState.AddModelError("Request.RepairDescription", "Zadejte důvod zrušení.");
                }
            }
            else
            {
                // Ostatní stavy – důvod zrušení vždy povinný
                if (string.IsNullOrWhiteSpace(Request.RepairDescription))
                {
                    ModelState.AddModelError("Request.RepairDescription", "Zadejte důvod zrušení.");
                }
            }

            if (!ModelState.IsValid)
            {
                await LoadDepartmentAsync();
                await LoadUserNameAsync();
                return Page();
            }

            // Vlastní zrušení podle pravidel
            if (entity.Status == (int)StatusEnum.New && IsOwner)
            {
                // 1) Zadavatel ruší NOVÝ požadavek – pevný text
                entity.RepairDescription = "Zrušeno zadavatelem";
            }
            else
            {
                // 2) Admin (nebo zrušení v jiném stavu) – použít zadaný důvod
                entity.RepairDescription = Request.RepairDescription;
            }

            entity.Status = (int)StatusEnum.Cancelled;
            entity.RemovedDate = DateTime.Now;
            await _context.SaveChangesAsync();

            var filter = GetFilterForStatus(entity.Status);
            return RedirectToPage("/Index", new { filter });
        }

        private static string GetFilterForStatus(int status) =>
            status switch
            {
                (int)StatusEnum.New        => "new-requests",
                (int)StatusEnum.InProgress => "inprogress-requests",
                (int)StatusEnum.Resolved   => "resolved-requests",
                (int)StatusEnum.Cancelled  => "cancelled-requests",
                _                          => "new-requests"
            };

        private async Task LoadDepartmentAsync()
        {
            if (Request is null || Request.IdDepartment == 0)
            {
                return;
            }

            var department = await _context.JasDepartments
                .AsNoTracking()
                .FirstOrDefaultAsync(d => d.Id == Request.IdDepartment);

            Request.Department = department?.Name ?? string.Empty;
        }

        /// <summary>
        /// Načte jméno uživatele, který požadavek zadal (IdUser),
        /// z identity databáze a uloží ho do Request.UserName.
        /// </summary>
        private async Task LoadUserNameAsync()
        {
            if (Request is null || string.IsNullOrEmpty(Request.IdUser))
            {
                return;
            }

            var user = await _identityContext.AspNetUsers
                .AsNoTracking()
                .FirstOrDefaultAsync(u => u.Id == Request.IdUser);

            Request.UserName = user?.Name ?? user?.UserName ?? string.Empty;
        }
    }
}