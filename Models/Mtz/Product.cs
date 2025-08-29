using Jas.Data.JasMtzDb;
using Microsoft.CodeAnalysis.CSharp.Syntax;
using System.ComponentModel.DataAnnotations;


namespace Jas.Models.Mtz
{
    public class Product : MtzProduct
    {
        [Required(ErrorMessage = "Vyberte velikost")]
        [Display(Name = "Velikost")]
        [RegularExpression(@"^((?!vyberte).)*$", ErrorMessage = "Vyberte velikost")]
        public string SelectedSize { get; set; }
        [Range(1, int.MaxValue, ErrorMessage = "Zadejte množství")]
        public int Amount { get; set; }
        [Display(Name = "Jméno zaměstnance – POVINNĚ VYPLNIT")]
        [StringLength(1000, MinimumLength = 3, ErrorMessage = "Jméno zaměstnance musí mít alespoň 3 znaky")]
        [Required(ErrorMessage = "Vyplňte jméno zaměstnance")]
        public string NamesOfEmployees { get; set; } = string.Empty;
    }
}
