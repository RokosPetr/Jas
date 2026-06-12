using DocumentFormat.OpenXml.Spreadsheet;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasIdentityDb;
using Jas.Data.JasMtzDb;
using Jas.Globals.Srv.Enums;
using Jas.Models.Srv;
using Jas.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using NoteTypeEnum = Jas.Globals.Srv.Enums.MaintenanceRequestNoteType;
using RepairCategoryEnum = Jas.Globals.Srv.Enums.RepairCategory;
using StatusEnum = Jas.Globals.Srv.Enums.Status;

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

        public List<SrvMaintenanceRequestNote> HistoryNotes { get; set; } = new();

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
                ReturnDescription = null
            };

            // po mapování:
            if (Request.Status == (int)StatusEnum.Returned)
            {
                // textarea pro nové vyjádření bude prázdná
                Request.RepairDescription = null;
            }

            // je přihlášený uživatel zadavatel?
            IsOwner = _userService.JasUser?.Id == Request.IdUser;

            await ReloadPageDataAsync();

            return Page();
        }

        public async Task<IActionResult> OnPostSaveAsync()
        {
            ModelState.Remove("Request.IdUser");
            ModelState.Remove("Request.IdSolver");

            IsOwner = _userService.JasUser?.Id == Request.IdUser;

            var entity = await _context.SrvMaintenanceRequests
                .FirstOrDefaultAsync(r => r.Id == Request.Id);

            if (entity == null)
            {
                return NotFound();
            }

            // validace podle skutečného stavu entity
            if (IsAdmin && (entity.Status == (int)StatusEnum.InProgress
                            || entity.Status == (int)StatusEnum.Returned))
            {
                if (string.IsNullOrWhiteSpace(Request.RepairDescription))
                    ModelState.AddModelError("Request.RepairDescription", "Zadejte popis opravy.");

                if (!Request.PlannedRepairDate.HasValue)
                    ModelState.AddModelError("Request.PlannedRepairDate", "Zadejte plánovaný termín opravy.");
            }

            if (!ModelState.IsValid)
            {
                await ReloadPageDataAsync();
                return Page();
            }

            // zadavatel může měnit základní pole jen ve stavu NOVÁ
            bool canEditBasic = IsOwner && entity.Status == (int)StatusEnum.New;

            if (canEditBasic)
            {
                entity.IssueDescription = Request.IssueDescription;

                if (entity.RepairCategory != Request.RepairCategory)
                    entity.RepairCategory = Request.RepairCategory;
            }

            if (IsAdmin)
            {
                // vždy přenést popis opravy
                entity.RepairDescription = Request.RepairDescription;

                // ve stavu VRÁCENO vždy přenést nové vyjádření
                if (entity.Status == (int)StatusEnum.Returned)
                {
                    entity.ReturnDescription = Request.ReturnDescription;
                }

                entity.PlannedRepairDate = Request.PlannedRepairDate;
                entity.EstimatedCost     = Request.EstimatedCost;
                entity.ActualCost        = Request.ActualCost;

                if (Request.RepairCategoryAdmin == 0 ||
                    Request.RepairCategoryAdmin == entity.RepairCategory)
                {
                    entity.RepairCategoryAdmin = null;
                    var days = entity.RepairCategory switch
                    {
                        (int)RepairCategoryEnum.Light => 60,
                        (int)RepairCategoryEnum.Serious => 30,
                        (int)RepairCategoryEnum.Urgent => 5,
                        _ => 0
                    };
                    if (days > 0)
                    {
                        entity.DueDate = entity.CreatedDate.AddDays(days);
                    }
                }
                else
                {
                    entity.RepairCategoryAdmin = Request.RepairCategoryAdmin;
                    var days = entity.RepairCategoryAdmin switch
                    {
                        (int)RepairCategoryEnum.Light => 60,
                        (int)RepairCategoryEnum.Serious => 30,
                        (int)RepairCategoryEnum.Urgent => 5,
                        _ => 0
                    };
                    if (days > 0)
                    {
                        entity.DueDate = entity.CreatedDate.AddDays(days);
                    }
                }
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
                await ReloadPageDataAsync();
                return Page();
            }

            var entity = await _context.SrvMaintenanceRequests
                .FirstOrDefaultAsync(r => r.Id == Request.Id);

            if (entity == null)
            {
                return NotFound();
            }

            if (entity.Status is (int)Status.InProgress or (int)Status.Returned)
            {
                // vždy přenést – validace už vyžaduje neprázdný popis
                entity.RepairDescription = Request.RepairDescription;
                entity.PlannedRepairDate = Request.PlannedRepairDate;
                entity.EstimatedCost     = Request.EstimatedCost;
                entity.ActualCost        = Request.ActualCost;

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
                await ReloadPageDataAsync();
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
                await ReloadPageDataAsync();
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

            var currentUserId = _userService.JasUser?.Id;
            var isOwner       = currentUserId == entity.IdUser;

            // Do „K odsouhlašení“ smí jen ADMIN nebo ZADAVATEL, a jen ze stavu VRÁCENO
            if (entity.Status != (int)StatusEnum.Returned || (!IsAdmin && !isOwner))
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
                await ReloadPageDataAsync();
                return Page();
            }

            // nové vyjádření: nastavíme jako aktuální ReturnDescription
            // interceptor MaintenanceRequestHistoryInterceptor z toho vytvoří poznámku typu Return
            entity.ReturnDescription = Request.ReturnDescription;

            entity.Status      = (int)StatusEnum.ToConfirm;
            entity.RemovedDate = DateTime.Now;

            await _context.SaveChangesAsync();

            var filter = GetFilterForStatus(entity.Status);
            return RedirectToPage("/Index", new { filter });
        }

        // Handler pro tlačítko „Převést na spuštěný"
        public async Task<IActionResult> OnPostToProgressAsync()
        {
            ModelState.Remove("Request.IdUser");
            ModelState.Remove("Request.IdSolver");

            var entity = await _context.SrvMaintenanceRequests
                .FirstOrDefaultAsync(r => r.Id == Request.Id);

            if (entity == null)
            {
                return NotFound();
            }

            // Do „V procesu“ může jen admin a jen z NEW
            if (!IsAdmin || entity.Status != (int)StatusEnum.New)
            {
                return Forbid();
            }

            if (IsAdmin && entity.Status == (int)StatusEnum.New)
            {
                if (string.IsNullOrWhiteSpace(Request.RepairDescription))
                    ModelState.AddModelError("Request.RepairDescription", "Zadejte popis opravy.");

                if (!Request.PlannedRepairDate.HasValue)
                    ModelState.AddModelError("Request.PlannedRepairDate", "Zadejte plánovaný termín opravy.");

                if (!Request.EstimatedCost.HasValue)
                    ModelState.AddModelError("Request.EstimatedCost", "Zadejte plánované náklady opravy.");
            }

            if (!ModelState.IsValid)
            {
                await ReloadPageDataAsync();
                return Page();
            }

            entity.Status = (int)StatusEnum.InProgress;
            entity.RepairDescription = Request.RepairDescription;
            entity.PlannedRepairDate = Request.PlannedRepairDate;
            entity.EstimatedCost = Request.EstimatedCost;

            if (Request.RepairCategoryAdmin == 0 ||
                Request.RepairCategoryAdmin == entity.RepairCategory)
            {
                entity.RepairCategoryAdmin = null;
            }
            else
            {
                entity.RepairCategoryAdmin = Request.RepairCategoryAdmin;
            }

            await _context.SaveChangesAsync();

            var filter = GetFilterForStatus(entity.Status);
            return RedirectToPage("/Index", new { filter });
        }

        private async Task ReloadPageDataAsync()
        {
            await LoadDepartmentAsync();
            await LoadUserNameAsync();

            if (Request != null && Request.Id > 0)
            {
                HistoryNotes = await _context.SrvMaintenanceRequestNotes
                    .AsNoTracking()
                    .Where(n => n.IdRequest == Request.Id)
                    .OrderBy(n => n.CreatedAt)
                    .ToListAsync();
            }
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