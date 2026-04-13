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
using NoteTypeEnum = Jas.Globals.Srv.Enums.MaintenanceRequestNoteType;

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
                PlannedRepairDate = entity.PlannedRepairDate ?? entity.DueDate,
                IssueDescription = entity.IssueDescription,
                RepairDescription = entity.RepairDescription,
                Status = entity.Status,
                RepairCategory = entity.RepairCategory,
                EstimatedCost = entity.EstimatedCost,
                ActualCost = entity.ActualCost,
                RepairCategoryAdmin = entity.RepairCategoryAdmin,
                ReturnDescription = entity.ReturnDescription
            };

            // po mapování:
            if (Request.Status == (int)StatusEnum.Returned)
            {
                // textarea pro nové vyjádření bude prázdná
                Request.ReturnDescription = null;
            }

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

            if (IsAdmin && Request.Status == (int)StatusEnum.Returned)
            {
                if (string.IsNullOrWhiteSpace(Request.ReturnDescription))
                {
                    ModelState.AddModelError("Request.ReturnDescription", "Zadejte nové vyjádření k vrácenému požadavku.");
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

                // uživatel může měnit RepairCategory jen ve stavu NEW
                if (entity.RepairCategory != Request.RepairCategory)
                {
                    entity.RepairCategory = Request.RepairCategory;
                }
                // POZNÁMKA: žádné ruční přidávání poznámek typu Issue – o to se postará interceptor
            }

            // Admin – ostatní úpravy (obecné uložení)
            if (IsAdmin)
            {
                // Repair – jen nastavit popis, interceptor vytvoří poznámku
                if (!string.IsNullOrWhiteSpace(Request.RepairDescription))
                {
                    entity.RepairDescription = Request.RepairDescription;
                }

                // Return – ve stavu VRÁCENO jen nastavit popis, interceptor vytvoří poznámku
                if (entity.Status == (int)StatusEnum.Returned &&
                    !string.IsNullOrWhiteSpace(Request.ReturnDescription))
                {
                    entity.ReturnDescription = Request.ReturnDescription;
                }

                entity.PlannedRepairDate = Request.PlannedRepairDate;
                entity.EstimatedCost     = Request.EstimatedCost;
                entity.ActualCost        = Request.ActualCost;

                // ---- LOGIKA RepairCategoryAdmin + DueDate ----
                if (Request.RepairCategoryAdmin == 0 ||
                    Request.RepairCategoryAdmin == entity.RepairCategory)
                {
                    entity.RepairCategoryAdmin = null;
                }
                else
                {
                    entity.RepairCategoryAdmin = Request.RepairCategoryAdmin;
                }
            }

            // Přepnutí do stavu V procesu
            if (IsAdmin && InProgress)
            {
                entity.Status = (int)StatusEnum.InProgress;
            }

            await _context.SaveChangesAsync();

            var filter = GetFilterForStatus(entity.Status);
            return RedirectToPage("/Index", new { filter });
        }

        // Handler pro tlačítko „K potvrzení“ (dříve „Vyřídit“)
        public async Task<IActionResult> OnPostResolveAsync()
        {
            ModelState.Remove("Request.IdUser");
            ModelState.Remove("Request.IdSolver");

            // Ve stavu „V procesu“ při Vyřídit vyžadovat:
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
                if (!string.IsNullOrWhiteSpace(Request.RepairDescription))
                {
                    // jen nastavíme, interceptor zapíše historii typu Repair
                    entity.RepairDescription = Request.RepairDescription;
                }

                entity.PlannedRepairDate = Request.PlannedRepairDate;
                entity.EstimatedCost     = Request.EstimatedCost;
                entity.ActualCost        = Request.ActualCost;

                // při přepnutí do stavu "K potvrzení" rovnou nastavit datum vyřízení
                entity.Status      = (int)StatusEnum.ToConfirm;
                entity.RemovedDate = DateTime.Now;

                await _context.SaveChangesAsync();
            }

            var filter = GetFilterForStatus(entity.Status);
            return RedirectToPage("/Index", new { filter });
        }

        // Handler pro tlačítko „Potvrdit“ (ze stavu K potvrzení do Vyřízeno)
        public async Task<IActionResult> OnPostConfirmAsync()
        {
            ModelState.Remove("Request.IdUser");
            ModelState.Remove("Request.IdSolver");

            var entity = await _context.SrvMaintenanceRequests
                .FirstOrDefaultAsync(r => r.Id == Request.Id);

            if (entity == null)
            {
                return NotFound();
            }

            // Potvrdit může jen zadavatel a jen ve stavu K potvrzení
            var currentUserId = _userService.JasUser?.Id;
            if (entity.Status != (int)StatusEnum.ToConfirm || currentUserId != entity.IdUser)
            {
                return Forbid();
            }

            // ve stavu K potvrzení se jen mění stav na Vyřízeno,
            // datum vyřízení (RemovedDate) již bylo nastaveno při přechodu do K potvrzení
            entity.Status = (int)StatusEnum.Resolved;
            await _context.SaveChangesAsync();

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
                if (!IsOwner && IsAdmin && string.IsNullOrWhiteSpace(Request.RepairDescription))
                {
                    ModelState.AddModelError("Request.RepairDescription", "Zadejte důvod zrušení.");
                }
            }
            else
            {
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

            if (entity.Status == (int)StatusEnum.New && IsOwner)
            {
                entity.RepairDescription = "Zrušeno zadavatelem";
            }
            else
            {
                entity.RepairDescription = Request.RepairDescription;
            }

            entity.Status      = (int)StatusEnum.Cancelled;
            entity.RemovedDate = DateTime.Now;
            await _context.SaveChangesAsync();

            var filter = GetFilterForStatus(entity.Status);
            return RedirectToPage("/Index", new { filter });
        }

        // Handler pro tlačítko „Vrátit“ (ze stavu K potvrzení do Vráceno)
        public async Task<IActionResult> OnPostReturnAsync()
        {
            ModelState.Remove("Request.IdUser");
            ModelState.Remove("Request.IdSolver");

            // dopočítat IsOwner i při POSTu
            IsOwner = _userService.JasUser?.Id == Request.IdUser;

            var entity = await _context.SrvMaintenanceRequests
                .FirstOrDefaultAsync(r => r.Id == Request.Id);

            if (entity == null)
            {
                return NotFound();
            }

            var currentUserId = _userService.JasUser?.Id;
            if (entity.Status != (int)StatusEnum.ToConfirm || currentUserId != entity.IdUser)
            {
                return Forbid();
            }

            if (string.IsNullOrWhiteSpace(Request.ReturnDescription))
            {
                ModelState.AddModelError("Request.ReturnDescription", "Zadejte důvod vrácení.");
            }

            if (!ModelState.IsValid)
            {
                await LoadDepartmentAsync();
                await LoadUserNameAsync();
                return Page();
            }

            // Můžete:
            // 1) nechat interceptor, aby vytvořil poznámku typu Return
            //    → jen nastavíme ReturnDescription
            entity.ReturnDescription = Request.ReturnDescription;
            entity.Status            = (int)StatusEnum.Returned;

            await _context.SaveChangesAsync();

            var filter = GetFilterForStatus(entity.Status);
            return RedirectToPage("/Index", new { filter });
        }

        // Handler pro tlačítko „K odsouhlašení“ ze stavu VRÁCENO
        public async Task<IActionResult> OnPostResolveFromReturnedAsync()
        {
            ModelState.Remove("Request.IdUser");
            ModelState.Remove("Request.IdSolver");

            var entity = await _context.SrvMaintenanceRequests
                .FirstOrDefaultAsync(r => r.Id == Request.Id);

            if (entity == null)
            {
                return NotFound();
            }

            if (entity.Status != (int)StatusEnum.Returned || !IsAdmin)
            {
                return Forbid();
            }

            // ve stavu VRÁCENO vyžadovat nové vyjádření
            if (string.IsNullOrWhiteSpace(Request.ReturnDescription))
            {
                ModelState.AddModelError("Request.ReturnDescription", "Zadejte nové vyjádření k vrácenému požadavku.");
            }

            if (!ModelState.IsValid)
            {
                await LoadDepartmentAsync();
                await LoadUserNameAsync();
                return Page();
            }

            // přičíst nové vyjádření do ReturnDescription
            if (!string.IsNullOrWhiteSpace(Request.ReturnDescription))
            {
                var now  = DateTime.Now.ToString("dd.MM.yyyy HH:mm");
                var line = $"{now} - {Request.ReturnDescription.Trim()}";
                if (string.IsNullOrWhiteSpace(entity.ReturnDescription))
                {
                    entity.ReturnDescription = line;
                }
                else
                {
                    entity.ReturnDescription = entity.ReturnDescription.Trim()
                                               + Environment.NewLine
                                               + line;
                }
            }

            entity.Status = (int)StatusEnum.ToConfirm;
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
                (int)StatusEnum.ToConfirm  => "toconfirm-requests",
                (int)StatusEnum.Resolved   => "resolved-requests",
                (int)StatusEnum.Returned   => "returned-requests",
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