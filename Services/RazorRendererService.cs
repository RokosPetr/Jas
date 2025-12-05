using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.Abstractions;
using Microsoft.AspNetCore.Mvc.ModelBinding;
using Microsoft.AspNetCore.Mvc.Razor;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.AspNetCore.Mvc.Rendering;
using Microsoft.AspNetCore.Mvc.ViewEngines;
using Microsoft.AspNetCore.Mvc.ViewFeatures;

namespace Jas.Services
{
    public interface IRazorRenderer
    {
        Task<string> RenderViewToStringAsync(string viewName, object model);
    }

    public class RazorRenderer : IRazorRenderer
    {
        private readonly IRazorViewEngine _viewEngine;
        private readonly ITempDataProvider _tempDataProvider;
        private readonly IServiceProvider _serviceProvider;

        public RazorRenderer(
            IRazorViewEngine viewEngine,
            ITempDataProvider tempDataProvider,
            IServiceProvider serviceProvider)
        {
            _viewEngine = viewEngine;
            _tempDataProvider = tempDataProvider;
            _serviceProvider = serviceProvider;
        }

        public async Task<string> RenderViewToStringAsync(string viewName, object model)
        {
            var httpContext = new DefaultHttpContext
            {
                RequestServices = _serviceProvider
            };

            // >>> TADY NOVĚ – nastavíme area podle modelu, pokud je PageModel s [Area]
            var routeData = new RouteData();

            if (model is PageModel pageModel)
            {
                var areaAttr = pageModel
                    .GetType()
                    .GetCustomAttributes(typeof(AreaAttribute), inherit: true)
                    .OfType<AreaAttribute>()
                    .FirstOrDefault();

                if (areaAttr != null)
                {
                    routeData.Values["area"] = areaAttr.RouteValue; // např. "Ptg"
                }
            }
            // <<< KONEC NOVÉ ČÁSTI

            var actionContext = new ActionContext(
                httpContext,
                routeData,
                new ActionDescriptor()
            );

            using (var sw = new StringWriter())
            {
                ViewEngineResult viewResult;

                // absolutní cesta / *.cshtml → GetView (funguje i pro Razor Pages)
                if (viewName.StartsWith("/") ||
                    viewName.StartsWith("~/") ||
                    viewName.EndsWith(".cshtml", StringComparison.OrdinalIgnoreCase))
                {
                    viewResult = _viewEngine.GetView(
                        executingFilePath: null,
                        viewPath: viewName,
                        isMainPage: true);
                }
                else
                {
                    // jinak klasické MVC hledání podle názvu view
                    viewResult = _viewEngine.FindView(
                        actionContext,
                        viewName,
                        isMainPage: true);
                }

                if (!viewResult.Success || viewResult.View == null)
                {
                    var searched = viewResult.SearchedLocations ?? Array.Empty<string>();
                    var message =
                        $"{viewName} does not match any available view. " +
                        $"Searched locations:{Environment.NewLine}{string.Join(Environment.NewLine, searched)}";

                    throw new ArgumentNullException(nameof(viewName), message);
                }

                var viewDictionary = new ViewDataDictionary(
                    new EmptyModelMetadataProvider(),
                    new ModelStateDictionary())
                {
                    Model = model
                };

                var viewContext = new ViewContext(
                    actionContext,
                    viewResult.View,
                    viewDictionary,
                    new TempDataDictionary(httpContext, _tempDataProvider),
                    sw,
                    new HtmlHelperOptions()
                );

                await viewResult.View.RenderAsync(viewContext);
                return sw.ToString();
            }
        }
    }
}
