using System;
using System.ComponentModel.DataAnnotations;
using Jas.Data.JasMtzDb;

namespace Jas.Models.Srv
{
    // View‑model pro SRV, navazuje na entitu SrvMaintenanceRequest a
    // doplňuje české popisky + validace pro formuláře.
    public class SrvMaintenanceRequestModel : SrvMaintenanceRequest
    {
        [Display(Name = "Č.p.")]
        public new int Id { get; set; }

        [Display(Name = "Popis závady")]
        [Required(ErrorMessage = "Vyplňte popis závady")]
        [StringLength(2000, ErrorMessage = "Popis závady je příliš dlouhý")]
        public new string IssueDescription { get; set; } = string.Empty;

        [Display(Name = "Popis opravy")]
        public new string? RepairDescription { get; set; }

        [Display(Name = "Kategorie opravy")]
        [Required(ErrorMessage = "Vyberte kategorii opravy")]
        public new int RepairCategory { get; set; }

        [Display(Name = "Stav")]
        [Required(ErrorMessage = "Vyberte stav požadavku")]
        public new int Status { get; set; }

        [Display(Name = "Předpokládané nák.")]
        [Range(0, double.MaxValue, ErrorMessage = "Zadejte nezápornou částku")]
        [RegularExpression(@"^\d+$", ErrorMessage = "Zadejte částku v celých korunách")]    
        public new decimal? EstimatedCost { get; set; }

        [Display(Name = "Skutečné nák.")]
        [Range(0, double.MaxValue, ErrorMessage = "Zadejte nezápornou částku")]
        [RegularExpression(@"^\d+$", ErrorMessage = "Zadejte částku v celých korunách")]
        public new decimal? ActualCost { get; set; }

        [Display(Name = "Vytvořeno")]
        public new DateTime CreatedDate { get; set; }

        [Display(Name = "Odstraněno")]
        public new DateTime? RemovedDate { get; set; }

        [Display(Name = "Kategorie závady")]
        public int? IssueCategory { get; set; }

        [Display(Name = "Opravit do")]
        public new DateTime? DueDate { get; set; }

        [Display(Name = "Plán opravy")]
        public new DateTime? PlannedRepairDate { get; set; }

        [Display(Name = "Středisko")]
        public string Department { get; set; } = string.Empty;

        [Display(Name = "Uživatel")]
        public string UserName { get; set; } = string.Empty;
    }
}