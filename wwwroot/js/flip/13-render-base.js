/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class RenderBase  {
        constructor(t, e)  {
            this.leftPage = null, this.rightPage = null, this.flippingPage = null, this.bottomPage = null, this.direction = null, this.orientation = null, this.shadow = null, this.animation = null, this.pageRect = null, this.boundsRect = null, this.timer = 0, this.safari = !1, this.setting = e, this.app = t;
            const i = new RegExp("Version\\/[\\d\\.]+.*Safari/");
            this.safari = null !== i.exec(window.navigator.userAgent) }
         render(t)  {
            if (null !== this.animation)  {
                const e = Math.round((t - this.animation.startedAt) / this.animation.durationFrame);
                e < this.animation.frames.length ? this.animation.frames[e]() : (this.animation.onAnimateEnd(), this.animation = null) }
             this.timer = t, this.drawFrame() }
         start()  {
            this.update();
            const t = e =>  {
                this.render(e), requestAnimationFrame(t) }
            ;
            requestAnimationFrame(t) }
         startAnimation(t, e, i)  {
            this.finishAnimation(), this.animation =  {
                frames: t, duration: e, durationFrame: e / t.length, onAnimateEnd: i, startedAt: this.timer }
             }
         finishAnimation()  {
            null !== this.animation && (this.animation.frames[this.animation.frames.length - 1](), null !== this.animation.onAnimateEnd && this.animation.onAnimateEnd()), this.animation = null }
         update()  {
            this.boundsRect = null;
            const t = this.calculateBoundsRect();
            this.orientation !== t && (this.orientation = t, this.app.updateOrientation(t)) }
         calculateBoundsRect()  {
            let t = "landscape";
            const e = this.getBlockWidth(), i = e / 2, s = this.getBlockHeight() / 2, n = this.setting.width / this.setting.height;
            let h = this.setting.width, r = this.setting.height, o = i - h;
            return "stretch" === this.setting.size ? (e < 2 * this.setting.minWidth && this.app.getSettings().usePortrait && (t = "portrait"), h = "portrait" === t ? this.getBlockWidth() : this.getBlockWidth() / 2, h > this.setting.maxWidth && (h = this.setting.maxWidth), r = h / n, r > this.getBlockHeight() && (r = this.getBlockHeight(), h = r * n), o = "portrait" === t ? i - h / 2 - h : i - h) : e < 2 * h && this.app.getSettings().usePortrait && (t = "portrait", o = i - h / 2 - h), this.boundsRect =  {
                left: o, top: s - r / 2, width: 2 * h, height: r, pageWidth: h }
            , t }
         setShadowData(t, e, i, s)  {
            if (!this.app.getSettings().drawShadow) return;
            const n = 100 * this.getSettings().maxShadowOpacity;
            this.shadow =  {
                pos: t, angle: e, width: 3 * this.getRect().pageWidth / 4 * i / 100, opacity: (100 - i) * n / 100 / 100, direction: s, progress: 2 * i }
             }
         clearShadow()  {
            this.shadow = null }
         getBlockWidth()  {
            return this.app.getUI().getDistElement().offsetWidth }
         getBlockHeight()  {
            return this.app.getUI().getDistElement().offsetHeight }
         getDirection()  {
            return this.direction }
         getRect()  {
            return null === this.boundsRect && this.calculateBoundsRect(), this.boundsRect }
         getSettings()  {
            return this.app.getSettings() }
         getOrientation()  {
            return this.orientation }
         setPageRect(t)  {
            this.pageRect = t }
         setDirection(t)  {
            this.direction = t }
         setRightPage(t)  {
            null !== t && t.setOrientation(1), this.rightPage = t }
         setLeftPage(t)  {
            null !== t && t.setOrientation(0), this.leftPage = t }
         setBottomPage(t)  {
            null !== t && t.setOrientation(1 === this.direction ? 0 : 1), this.bottomPage = t }
         setFlippingPage(t)  {
            null !== t && t.setOrientation(0 === this.direction && "portrait" !== this.orientation ? 0 : 1), this.flippingPage = t }
         convertToBook(t)  {
            const e = this.getRect();
            return  {
                x: t.x - e.left, y: t.y - e.top }
             }
         isSafari()  {
            return this.safari }
         convertToPage(t, e)  {
            e || (e = this.direction);
            const i = this.getRect();
            return  {
                x: 0 === e ? t.x - i.left - i.width / 2 : i.width / 2 - t.x + i.left, y: t.y - i.top }
             }
         convertToGlobal(t, e)  {
            if (e || (e = this.direction), null == t) return null;
            const i = this.getRect();
            return  {
                x: 0 === e ? t.x + i.left + i.width / 2 : i.width / 2 - t.x + i.left, y: t.y + i.top }
             }
         convertRectToGlobal(t, e)  {
            return e || (e = this.direction),  {
                topLeft: this.convertToGlobal(t.topLeft, e), topRight: this.convertToGlobal(t.topRight, e), bottomLeft: this.convertToGlobal(t.bottomLeft, e), bottomRight: this.convertToGlobal(t.bottomRight, e) }
             }
         }

    St.RenderBase = RenderBase;
})(typeof self !== "undefined" ? self : this);
