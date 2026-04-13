using System;
using System.Linq;
using System.Threading;
using System.Threading.Tasks;
using Jas.Data.JasMtzDb;
using Jas.Globals.Srv.Enums;
using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.ChangeTracking;
using Microsoft.EntityFrameworkCore.Diagnostics;

namespace Jas.Data.JasMtzDb.Interceptors
{
    public class MaintenanceRequestHistoryInterceptor : SaveChangesInterceptor
    {
        public override ValueTask<InterceptionResult<int>> SavingChangesAsync(
            DbContextEventData eventData,
            InterceptionResult<int> result,
            CancellationToken cancellationToken = default)
        {
            var context = eventData.Context as JasMtzDbContext;
            if (context == null)
                return base.SavingChangesAsync(eventData, result, cancellationToken);

            var now = DateTime.UtcNow;

            // jen modifikované požadavky – nové přeskočíme
            var entries = context.ChangeTracker
                .Entries<SrvMaintenanceRequest>()
                .Where(e => e.State == EntityState.Modified)
                .ToList();

            foreach (var entry in entries)
            {
                var request = entry.Entity;

                // původní hodnoty (pro update)
                var original = entry.GetDatabaseValues();

                // ISSUE DESCRIPTION (1)
                if (DescriptionChanged(entry, original, nameof(SrvMaintenanceRequest.IssueDescription)))
                {
                    AddNote(context, request.Id, MaintenanceRequestNoteType.Issue,
                        request.IssueDescription, now, request.IdUser);
                }

                // REPAIR DESCRIPTION (2)
                if (DescriptionChanged(entry, original, nameof(SrvMaintenanceRequest.RepairDescription)))
                {
                    AddNote(context, request.Id, MaintenanceRequestNoteType.Repair,
                        request.RepairDescription, now, request.IdUser);
                }

                // RETURN DESCRIPTION (3)
                if (DescriptionChanged(entry, original, nameof(SrvMaintenanceRequest.ReturnDescription)))
                {
                    AddNote(context, request.Id, MaintenanceRequestNoteType.Return,
                        request.ReturnDescription, now, request.IdUser);
                }
            }

            return base.SavingChangesAsync(eventData, result, cancellationToken);
        }

        private static bool DescriptionChanged(
            EntityEntry<SrvMaintenanceRequest> entry,
            PropertyValues? originalValues,
            string propertyName)
        {
            var prop = entry.Property(propertyName);

            // už neřešíme Added, takže stačí kontrola IsModified
            if (!prop.IsModified)
                return false;

            var current = (string?)prop.CurrentValue;
            var original = (string?)originalValues?[propertyName];

            // změna NULL↔hodnota nebo změna textu
            if (string.IsNullOrWhiteSpace(original) && string.IsNullOrWhiteSpace(current))
                return false;

            return original != current;
        }

        private static void AddNote(
            JasMtzDbContext context,
            int requestId,
            MaintenanceRequestNoteType type,
            string? text,
            DateTime now,
            string? userId)
        {
            if (string.IsNullOrWhiteSpace(text))
                return;

            var note = new SrvMaintenanceRequestNote
            {
                IdRequest = requestId,
                NoteType  = (byte)type,
                NoteText  = text.Trim(),
                CreatedAt = now,
                IdUser    = userId
            };

            context.SrvMaintenanceRequestNotes.Add(note);
        }
    }
}