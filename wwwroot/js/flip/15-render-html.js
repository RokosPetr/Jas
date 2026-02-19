/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class HtmlRender extends RenderBase  {
        constructor(t, e, i)  {
            super(t, e), this.outerShadow = null, this.innerShadow = null, this.hardShadow = null, this.hardInnerShadow = null, this.element = i, this.createShadows() }
         createShadows()  {
            this.element.insertAdjacentHTML("beforeend", '<div class="stf__outerShadow"></div>\n             <div class="stf__innerShadow"></div>\n             <div class="stf__hardShadow"></div>\n             <div class="stf__hardInnerShadow"></div>'), this.outerShadow = this.element.querySelector(".stf__outerShadow"), this.innerShadow = this.element.querySelector(".stf__innerShadow"), this.hardShadow = this.element.querySelector(".stf__hardShadow"), this.hardInnerShadow = this.element.querySelector(".stf__hardInnerShadow") }
         clearShadow()  {
            super.clearShadow(), this.outerShadow.style.cssText = "display: none", this.innerShadow.style.cssText = "display: none", this.hardShadow.style.cssText = "display: none", this.hardInnerShadow.style.cssText = "display: none" }
         reload()  {
            this.element.querySelector(".stf__outerShadow") || this.createShadows() }
         drawHardInnerShadow()  {
            const t = this.getRect(), e = this.shadow.progress > 100 ? 200 - this.shadow.progress : this.shadow.progress;
            let i = (100 - e) * (2.5 * t.pageWidth) / 100 + 20;
            i > t.pageWidth && (i = t.pageWidth);
            let s = `\n            display: block;\n            z-index: ${(this.getSettings().startZIndex + 5).toString(10)};\n            width: ${i}px;\n            height: ${t.height}px;\n            background: linear-gradient(to right,\n                rgba(0, 0, 0, ${this.shadow.opacity * e / 100}) 5%,\n                rgba(0, 0, 0, 0) 100%);\n            left: ${t.left + t.width / 2}px;\n            transform-origin: 0 0;\n        `;
            s += 0 === this.getDirection() && this.shadow.progress > 100 || 1 === this.getDirection() && this.shadow.progress <= 100 ? "transform: translate3d(0, 0, 0);" : "transform: translate3d(0, 0, 0) rotateY(180deg);", this.hardInnerShadow.style.cssText = s }
         drawHardOuterShadow()  {
            const t = this.getRect();
            let e = (100 - (this.shadow.progress > 100 ? 200 - this.shadow.progress : this.shadow.progress)) * (2.5 * t.pageWidth) / 100 + 20;
            e > t.pageWidth && (e = t.pageWidth);
            let i = `\n            display: block;\n            z-index: ${(this.getSettings().startZIndex + 4).toString(10)};\n            width: ${e}px;\n            height: ${t.height}px;\n            background: linear-gradient(to left, rgba(0, 0, 0, ${this.shadow.opacity}) 5%, rgba(0, 0, 0, 0) 100%);\n            left: ${t.left + t.width / 2}px;\n            transform-origin: 0 0;\n        `;
            i += 0 === this.getDirection() && this.shadow.progress > 100 || 1 === this.getDirection() && this.shadow.progress <= 100 ? "transform: translate3d(0, 0, 0) rotateY(180deg);" : "transform: translate3d(0, 0, 0);", this.hardShadow.style.cssText = i }
         drawInnerShadow()  {
            const t = this.getRect(), e = 3 * this.shadow.width / 4, i = 0 === this.getDirection() ? e : 0, s = 0 === this.getDirection() ? "to left" : "to right", n = this.convertToGlobal(this.shadow.pos), r = this.shadow.angle + 3 * Math.PI / 2, o = [this.pageRect.topLeft, this.pageRect.topRight, this.pageRect.bottomRight, this.pageRect.bottomLeft];
            let a = "polygon( ";
            for (const t of o)  {
                let e = 1 === this.getDirection() ?  {
                    x: -t.x + this.shadow.pos.x, y: t.y - this.shadow.pos.y }
                 :  {
                    x: t.x - this.shadow.pos.x, y: t.y - this.shadow.pos.y }
                ;
                e = Geometry.GetRotatedPoint(e,  {
                    x: i, y: 100 }
                , r), a += e.x + "px " + e.y + "px, " }
             a = a.slice(0, -2), a += ")";
            const g = `\n            display: block;\n            z-index: ${(this.getSettings().startZIndex + 10).toString(10)};\n            width: ${e}px;\n            height: ${2 * t.height}px;\n            background: linear-gradient(${s},\n                rgba(0, 0, 0, ${this.shadow.opacity}) 5%,\n                rgba(0, 0, 0, 0.05) 15%,\n                rgba(0, 0, 0, ${this.shadow.opacity}) 35%,\n                rgba(0, 0, 0, 0) 100%);\n            transform-origin: ${i}px 100px;\n            transform: translate3d(${n.x - i}px, ${n.y - 100}px, 0) rotate(${r}rad);\n            clip-path: ${a};\n            -webkit-clip-path: ${a};\n        `;
            this.innerShadow.style.cssText = g }
         drawOuterShadow()  {
            const t = this.getRect(), e = this.convertToGlobal( {
                x: this.shadow.pos.x, y: this.shadow.pos.y }
            ), i = this.shadow.angle + 3 * Math.PI / 2, s = 1 === this.getDirection() ? this.shadow.width : 0, n = 0 === this.getDirection() ? "to right" : "to left", r = [ {
                x: 0, y: 0 }
            ,  {
                x: t.pageWidth, y: 0 }
            ,  {
                x: t.pageWidth, y: t.height }
            ,  {
                x: 0, y: t.height }
            ];
            let o = "polygon( ";
            for (const t of r) if (null !== t)  {
                let e = 1 === this.getDirection() ?  {
                    x: -t.x + this.shadow.pos.x, y: t.y - this.shadow.pos.y }
                 :  {
                    x: t.x - this.shadow.pos.x, y: t.y - this.shadow.pos.y }
                ;
                e = Geometry.GetRotatedPoint(e,  {
                    x: s, y: 100 }
                , i), o += e.x + "px " + e.y + "px, " }
             o = o.slice(0, -2), o += ")";
            const a = `\n            display: block;\n            z-index: ${(this.getSettings().startZIndex + 10).toString(10)};\n            width: ${this.shadow.width}px;\n            height: ${2 * t.height}px;\n            background: linear-gradient(${n}, rgba(0, 0, 0, ${this.shadow.opacity}), rgba(0, 0, 0, 0));\n            transform-origin: ${s}px 100px;\n            transform: translate3d(${e.x - s}px, ${e.y - 100}px, 0) rotate(${i}rad);\n            clip-path: ${o};\n            -webkit-clip-path: ${o};\n        `;
            this.outerShadow.style.cssText = a }
         drawLeftPage()  {
            "portrait" !== this.orientation && null !== this.leftPage && (1 === this.direction && null !== this.flippingPage && "hard" === this.flippingPage.getDrawingDensity() ? (this.leftPage.getElement().style.zIndex = (this.getSettings().startZIndex + 5).toString(10), this.leftPage.setHardDrawingAngle(180 + this.flippingPage.getHardAngle()), this.leftPage.draw(this.flippingPage.getDrawingDensity())) : this.leftPage.simpleDraw(0)) }
         drawRightPage()  {
            null !== this.rightPage && (0 === this.direction && null !== this.flippingPage && "hard" === this.flippingPage.getDrawingDensity() ? (this.rightPage.getElement().style.zIndex = (this.getSettings().startZIndex + 5).toString(10), this.rightPage.setHardDrawingAngle(180 + this.flippingPage.getHardAngle()), this.rightPage.draw(this.flippingPage.getDrawingDensity())) : this.rightPage.simpleDraw(1)) }
         drawBottomPage()  {
            if (null === this.bottomPage) return;
            const t = null != this.flippingPage ? this.flippingPage.getDrawingDensity() : null;
            "portrait" === this.orientation && 1 === this.direction || (this.bottomPage.getElement().style.zIndex = (this.getSettings().startZIndex + 3).toString(10), this.bottomPage.draw(t)) }
         drawFrame()  {
            this.clear(), this.drawLeftPage(), this.drawRightPage(), this.drawBottomPage(), null != this.flippingPage && (this.flippingPage.getElement().style.zIndex = (this.getSettings().startZIndex + 5).toString(10), this.flippingPage.draw()) }
         clear()  {
            for (const t of this.app.getPageCollection().getPages()) t !== this.leftPage && t !== this.rightPage && t !== this.flippingPage && t !== this.bottomPage && (t.getElement().style.cssText = "display: none"), t.getTemporaryCopy() !== this.flippingPage && t.hideTemporaryCopy() }
         update()  {
            super.update(), null !== this.rightPage && this.rightPage.setOrientation(1), null !== this.leftPage && this.leftPage.setOrientation(0) }
         }

    St.HtmlRender = HtmlRender;
})(typeof self !== "undefined" ? self : this);
