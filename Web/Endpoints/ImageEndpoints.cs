using System.Threading;
using Jas.Application.Abstractions;
using Microsoft.AspNetCore.Builder;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.StaticFiles;

namespace Jas.Web.Endpoints;

public static class ImageEndpoints
{
    private static readonly FileExtensionContentTypeProvider _types = new();

    public static IEndpointRouteBuilder MapImageEndpoints(this IEndpointRouteBuilder app)
    {
        var group = app.MapGroup("/images");

        group.MapGet("/{**path}", async (string path, HttpContext http, IImageStore store, CancellationToken ct) =>
        {
            if (string.IsNullOrWhiteSpace(path)) return Results.BadRequest();

            // Předej do store path + query (pro správnou variantu na remote)
            var requested = string.IsNullOrEmpty(http.Request.QueryString.Value)
                ? path
                : $"{path}{http.Request.QueryString.Value}";

            var ok = await store.TryEnsureLocalAsync(requested, ct);
            if (!ok) return Results.NotFound();

            // Pro lokální čtení ignorujeme query; MapPath si poradí i když je v 'path' prefix /images/
            var stream = await store.OpenReadAsync(path, ct);
            if (stream is null) return Results.NotFound();

            if (!_types.TryGetContentType(path, out var contentType))
                contentType = "application/octet-stream";

            return Results.File(stream, contentType);
        });

        return app;
    }
}
