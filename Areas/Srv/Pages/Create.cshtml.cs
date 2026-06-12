using System;
using System.Threading.Tasks;
using AutoMapper;
using Jas.Data.JasIdentityApp;
using Jas.Data.JasMtzDb;
using Jas.Models.Srv;
using Jas.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.EntityFrameworkCore;
using StatusEnum = Jas.Globals.Srv.Enums.Status;
using RepairCategoryEnum = Jas.Globals.Srv.Enums.RepairCategory;
using NoteTypeEnum = Jas.Globals.Srv.Enums.MaintenanceRequestNoteType;
using Jas.Globals.Srv.Enums;

namespace Jas.Areas.Srv.Pages
{
    [Area("Srv")]
    [Authorize(Roles = "SRV - admin,SRV - user")]
    public class CreateModel : PageModel
    {
        private readonly JasMtzDbContext _context;
        private readonly IMapper _mapper;
        private readonly UserManager<JasUser> _userManager;
        private readonly IUserService _userService;

        [BindProperty]
        public SrvMaintenanceRequestModel Request { get; set; } = null!;

        public bool IsEdit => Request?.Id != 0;

        public CreateModel(
            JasMtzDbContext context,
            IMapper mapper,
            UserManager<JasUser> userManager,
            IUserService userService)
        {
            _context = context;
            _mapper = mapper;
            _userManager = userManager;
            _userService = userService;
        }

        public async Task<IActionResult> OnGetAsync(int? id)
        {
            if (id.HasValue)
            {
                var entity = await _context.SrvMaintenanceRequests
                    .FirstOrDefaultAsync(r => r.Id == id.Value);

                if (entity == null)
                {
                    return NotFound();
                }

                Request = _mapper.Map<SrvMaintenanceRequestModel>(entity);
            }
            else
            {
                Request = new SrvMaintenanceRequestModel
                {
                    Status = (int)StatusEnum.New,
                    RepairCategory = (int)RepairCategoryEnum.Light
                };

                // nový požadavek – vyplnit IdUser, oddělení a obchod z aktuálního uživatele
                if (_userService.JasUser is not null)
                {
                    Request.IdUser = _userService.JasUser.Id;
                    Request.IdStore = _userService.JasUser.StoreId;
                    Request.IdDepartment = _userService.JasUser.DepartmentId;
                }
            }

            await LoadDepartmentAsync();
            await LoadUserNameAsync();
            return Page();
        }

        public async Task<IActionResult> OnPostAsync()
        {
            // id_user a id_solver se nevalidují – doplňují se automaticky
            ModelState.Remove("Request.IdUser");
            ModelState.Remove("Request.IdSolver");

            if (!ModelState.IsValid)
            {
                await LoadDepartmentAsync();
                await LoadUserNameAsync();
                return Page();
            }

            if (Request.Id == 0)
            {
                // NOVÝ požadavek
                var entity = _mapper.Map<SrvMaintenanceRequest>(Request);

                // explicitně nastavíme kategorii opravy
                entity.RepairCategory = Request.RepairCategory;

                var user = _userService.JasUser;
                if (user is not null)
                {
                    entity.IdUser       = user.Id;
                    entity.IdSolver     = "1244a08d-4b45-4aac-abf8-5e59aa8f430e";
                    entity.IdDepartment = user.DepartmentId;
                    entity.IdStore      = user.StoreId;
                }

                var now = DateTime.Now;
                entity.CreatedDate = now;

                // počet dní odvozený od ADMIN kategorie opravy
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

                entity.Status = (int)StatusEnum.New;

                _context.SrvMaintenanceRequests.Add(entity);
                await _context.SaveChangesAsync(); // tady už má entity.Id hodnotu

                // ÚVODNÍ poznámky pro nově založený požadavek – interceptor na Added neběží
                var nowUtc = DateTime.UtcNow;

                if (!string.IsNullOrWhiteSpace(entity.IssueDescription))
                {
                    _context.SrvMaintenanceRequestNotes.Add(new SrvMaintenanceRequestNote
                    {
                        IdRequest = entity.Id,
                        NoteType  = (byte)NoteTypeEnum.Issue,
                        NoteText  = entity.IssueDescription.Trim(),
                        CreatedAt = nowUtc,
                        IdUser    = entity.IdUser
                    });
                }

                if (!string.IsNullOrWhiteSpace(entity.RepairDescription))
                {
                    _context.SrvMaintenanceRequestNotes.Add(new SrvMaintenanceRequestNote
                    {
                        IdRequest = entity.Id,
                        NoteType  = (byte)NoteTypeEnum.Repair,
                        NoteText  = entity.RepairDescription.Trim(),
                        CreatedAt = nowUtc,
                        IdUser    = entity.IdUser
                    });
                }

                if (!string.IsNullOrWhiteSpace(entity.ReturnDescription))
                {
                    _context.SrvMaintenanceRequestNotes.Add(new SrvMaintenanceRequestNote
                    {
                        IdRequest = entity.Id,
                        NoteType  = (byte)NoteTypeEnum.Return,
                        NoteText  = entity.ReturnDescription.Trim(),
                        CreatedAt = nowUtc,
                        IdUser    = entity.IdUser
                    });
                }

                if (_context.ChangeTracker.HasChanges())
                {
                    await _context.SaveChangesAsync(); // uloží případné nové poznámky
                }

                return RedirectToPage("/Index");
            }
            else
            {
                // EDITACE existujícího požadavku
                var entity = await _context.SrvMaintenanceRequests
                    .FirstOrDefaultAsync(r => r.Id == Request.Id);

                if (entity == null)
                {
                    return NotFound();
                }

                // zde už NEpřidáváme poznámky – jen změníme entity,
                // a interceptor MaintenanceRequestHistoryInterceptor zaloguje změny
                _mapper.Map(Request, entity);
                _context.SrvMaintenanceRequests.Update(entity);

                await _context.SaveChangesAsync();
                return RedirectToPage("/Index");
            }
        }

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

        private async Task LoadUserNameAsync()
        {
            // pro existující požadavek – ber IdUser z DB
            if (!string.IsNullOrEmpty(Request.IdUser))
            {
                var user = await _userManager.FindByIdAsync(Request.IdUser);
                Request.UserName = user?.Name ?? user?.UserName ?? string.Empty;
                return;
            }

            // pro nový požadavek – aktuálně přihlášený uživatel
            if (_userService.JasUser is not null)
            {
                Request.UserName = _userService.JasUser.Name!;
                Request.IdUser = _userService.JasUser.Id;
            }
        }
    }
}