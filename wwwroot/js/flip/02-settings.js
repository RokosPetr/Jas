/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class SettingsParser  {
        constructor()  {
            this._default =  {
                startPage: 0, size: "fixed", width: 0, height: 0, minWidth: 0, maxWidth: 0, minHeight: 0, maxHeight: 0, drawShadow: !0, flippingTime: 1e3, usePortrait: !0, startZIndex: 0, autoSize: !0, maxShadowOpacity: 1, showCover: !1, mobileScrollSupport: !0, swipeDistance: 30, clickEventForward: !0, useMouseEvents: !0, showPageCorners: !0, disableFlipByClick: !1 }
             }
         getSettings(t)  {
            const e = this._default;
            if (Object.assign(e, t), "stretch" !== e.size && "fixed" !== e.size) throw new Error('Invalid size type. Available only "fixed" and "stretch" value');
            if (e.width <= 0 || e.height <= 0) throw new Error("Invalid width or height");
            if (e.flippingTime <= 0) throw new Error("Invalid flipping time");
            return "stretch" === e.size ? (e.minWidth <= 0 && (e.minWidth = 100), e.maxWidth < e.minWidth && (e.maxWidth = 2e3), e.minHeight <= 0 && (e.minHeight = 100), e.maxHeight < e.minHeight && (e.maxHeight = 2e3)) : (e.minWidth = e.width, e.maxWidth = e.width, e.minHeight = e.height, e.maxHeight = e.height), e }
         }

    St.SettingsParser = SettingsParser;
})(typeof self !== "undefined" ? self : this);
