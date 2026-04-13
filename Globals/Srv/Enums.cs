using System.ComponentModel.DataAnnotations;

namespace Jas.Globals.Srv.Enums
{
    public enum Status : int
    {
        New = 1,          // new (nová)
        InProgress = 2,   // in_progress (v procesu)
        Resolved = 3,     // resolved (vyřízeno)
        Cancelled = 4,    // cancelled (zrušeno)

        // Mezi-stav mezi "V procesu" a "Vyřízeno" – k potvrzení zadavatelem
        ToConfirm = 5,    // to_confirm (k potvrzení)

        // Požadavek vrácen zpět k doplnění / přepracování
        Returned = 6      // returned (vráceno)
    }

    public enum RepairCategory : int
    {
        [Display(Name = "Lehká (60 dní)")]
        Light = 60,    // 60 dní

        [Display(Name = "Vážná (30 dní)")]
        Serious = 30,  // 30 dní

        [Display(Name = "Urgentní (5 dní)")]
        Urgent = 5     // 5 dní
    }

    public enum MaintenanceRequestNoteType : byte
    {
        Issue  = 1, // zadání / popis závady
        Repair = 2, // vyjádření admina / způsob řešení
        Return = 3  // komunikace k vrácení / důvody vrácení
    }
}