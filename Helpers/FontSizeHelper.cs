using System.Drawing;
using System.Drawing.Text;

namespace Jas.Helpers
{
    public static class FontSizeHelper
    {
        public static int GetFontSizeForOneLine(string text, string fontFilePath, float initialFontSize, int maxWidth)
        {
            using var fontCollection = new PrivateFontCollection();
            fontCollection.AddFontFile(fontFilePath);
            var family = fontCollection.Families[0];

            using (Bitmap bitmap = new Bitmap(1, 1))
            using (Graphics graphics = Graphics.FromImage(bitmap))
            {
                graphics.PageUnit = GraphicsUnit.Pixel;

                float fontSize = initialFontSize;
                SizeF size;

                do
                {
                    using (Font font = new Font(family, fontSize, FontStyle.Bold, GraphicsUnit.Pixel))
                    {
                        size = graphics.MeasureString(text, font, int.MaxValue, StringFormat.GenericTypographic);
                    }

                    if (size.Width > maxWidth)
                    {
                        fontSize -= 0.5f;
                    }
                    else
                    {
                        break;
                    }

                } while (fontSize > 0);

                return Convert.ToInt16(fontSize);
            }
        }
    }
}
