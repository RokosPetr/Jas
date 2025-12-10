using AutoMapper;
using DinkToPdf;
using DinkToPdf.Contracts;
using Jas.Data.JasMtzDb;

namespace Jas.Services
{
    public interface IPdfService
    {
        byte[] ConvertHtmlToPdf(string htmlContent, DinkToPdf.Orientation orientation = Orientation.Landscape);
    }

    public class PdfService : IPdfService
    {
        private JasMtzDbContext _context;
        private readonly IWebHostEnvironment _hostingEnvironment;
        private readonly IConverter _converter;

        public PdfService(JasMtzDbContext jasPdfDbContext, IWebHostEnvironment hostingEnvironment, IMapper mapper, IConverter converter)
        {
            _context = jasPdfDbContext;
            _hostingEnvironment = hostingEnvironment;
            _converter = converter;
        }
        public byte[] ConvertHtmlToPdf(string htmlContent, DinkToPdf.Orientation orientation = Orientation.Landscape)
        {
            var doc = new HtmlToPdfDocument()
            {
                GlobalSettings = new GlobalSettings
                {
                    PaperSize = PaperKind.A4,
                    Orientation = orientation,
                    Margins = new MarginSettings { Top = 5, Bottom = 0, Left = 0, Right = 0 }
                },
                Objects = {
                new ObjectSettings
                {
                    HtmlContent = htmlContent,
                    WebSettings = { 
                        DefaultEncoding = "utf-8", 
                        EnableIntelligentShrinking = false,
                        PrintMediaType = true,
                        LoadImages = true
                    }
                }
            }
            };

            return _converter.Convert(doc);
        }
    }

}
