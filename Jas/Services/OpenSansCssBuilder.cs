using System;

namespace Jas.Services
{
    public static class OpenSansCssBuilder
    {
        public static string Build(string origin)
        {
            if (string.IsNullOrWhiteSpace(origin))
                throw new ArgumentException("Origin must be a non-empty absolute URL.", nameof(origin));

            return $$"""
@font-face{font-family:"Open Sans";src:url("{{origin}}/fonts/OpenSans-Light.ttf") format("truetype");font-weight:300;font-style:normal;font-display:block;}
@font-face{font-family:"Open Sans";src:url("{{origin}}/fonts/OpenSans-Regular.ttf") format("truetype");font-weight:400;font-style:normal;font-display:block;}
@font-face{font-family:"Open Sans";src:url("{{origin}}/fonts/OpenSans-SemiBold.ttf") format("truetype");font-weight:600;font-style:normal;font-display:block;}
@font-face{font-family:"Open Sans";src:url("{{origin}}/fonts/OpenSans-Bold.ttf") format("truetype");font-weight:700;font-style:normal;font-display:block;}
@font-face{font-family:"Open Sans";src:url("{{origin}}/fonts/OpenSans-ExtraBold.ttf") format("truetype");font-weight:800;font-style:normal;font-display:block;}
""";
        }
    }
}