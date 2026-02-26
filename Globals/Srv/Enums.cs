using System.ComponentModel.DataAnnotations;

namespace Jas.Globals.Srv.Enums
{
    public enum Status : int
    {
        New = 1,          // new (nová)
        InProgress = 2,   // in_progress (v procesu)
        Resolved = 3,     // resolved (vyřízeno)
        Cancelled = 4     // cancelled (zrušeno)
    }

    public enum RepairCategory : int
    {
        [Display(Name = "Lehká (60 dní)")]
        Light = 60,    // 60 dní

        [Display(Name = "Vážná (30 dní)")]
        Serious = 30,  // 30 dní

        [Display(Name = "Urgentní (5 dní)")]
        Urgent = 5    // 5 dní
    }
}