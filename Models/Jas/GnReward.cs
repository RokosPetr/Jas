using System;
using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace Jas.Models.Jas
{
    [Table("GnRewards")]
    public class GnReward
    {
        [Display(Name = "UID")]
        public string Uid { get; set; } = default!;

        [Display(Name = "Číslo pobočky")]
        public int Store { get; set; }

        [Display(Name = "Interní ID")]
        public string InternalId { get; set; } = default!;
         
        [Display(Name = "Číslo GN")]
        public int GnNumber { get; set; }

        [Display(Name = "Datum")]
        public DateTime RecordDate { get; set; }

        [Display(Name = "Rok")]
        public int Year { get; set; }

        [Display(Name = "Měsíc")]
        public string MonthName { get; set; } = default!;

        [Display(Name = "Osoba")]
        public string PersonName { get; set; } = default!;

        [Display(Name = "Role")]
        public string RoleName { get; set; } = default!;

        [Display(Name = "Pořadí zakázky")]
        public int OrderSequence { get; set; }

        [Display(Name = "Číslo zakázky")]
        public string OrderNumber { get; set; } = default!;

        [Display(Name = "Základ zakázka")]
        public decimal OrderBaseAmount { get; set; }

        [Display(Name = "Základ bonus")]
        public decimal BonusBaseAmount { get; set; }

        [Display(Name = "Sazba")]
        public decimal Rate { get; set; }

        [Display(Name = "Odměna")]
        public decimal RewardAmount { get; set; }

        [Display(Name = "Prodejna")]
        public string StoreName { get; set; } = default!;
    }
}