using System.Globalization;
using System.Text;
using System.Text.RegularExpressions;

namespace Jas.Helpers
{
    public static class TextUtils
    {
        public static string Slugify(string input)
        {
            if (string.IsNullOrWhiteSpace(input))
                return string.Empty;

            // sjednotíme NBSP na běžnou mezeru
            input = input.Replace('\u00A0', ' ');

            // odstraníme diakritiku
            var normalized = input.Normalize(NormalizationForm.FormD);
            var noDiacritics = new string(normalized
                .Where(c => CharUnicodeInfo.GetUnicodeCategory(c) != UnicodeCategory.NonSpacingMark)
                .ToArray())
                .Normalize(NormalizationForm.FormC);

            // na malá písmena
            var lower = noDiacritics.ToLowerInvariant();

            // vše mimo [a-z0-9] -> pomlčka
            lower = Regex.Replace(lower, @"[^a-z0-9]", "-");

            // sloučit vícenásobné pomlčky a ořezat okraje
            var slug = Regex.Replace(lower, "-{2,}", "-").Trim('-');

            return slug;
        }
    }
}
