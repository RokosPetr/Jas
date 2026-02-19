/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class HtmlPage extends PageBase  {
        constructor(t, e, i)  {
            super(t, i), this.copiedElement = null, this.temporaryCopy = null, this.isLoad = !1, this.element = e, this.element.classList.add("stf__item"), this.element.classList.add("--" + i) }
         newTemporaryCopy()  {
            return "hard" === this.nowDrawingDensity ? this : (null === this.temporaryCopy && (this.copiedElement = this.element.cloneNode(!0), this.element.parentElement.appendChild(this.copiedElement), this.temporaryCopy = new HtmlPage(this.render, this.copiedElement, this.nowDrawingDensity)), this.getTemporaryCopy()) }
         getTemporaryCopy()  {
            return this.temporaryCopy }
         hideTemporaryCopy()  {
            null !== this.temporaryCopy && (this.copiedElement.remove(), this.copiedElement = null, this.temporaryCopy = null) }
         draw(t)  {
            const e = t || this.nowDrawingDensity, i = this.render.convertToGlobal(this.state.position), s = this.render.getRect().pageWidth, n = this.render.getRect().height;
            this.element.classList.remove("--simple");
            const h = `\n            display: block;\n            z-index: ${this.element.style.zIndex};\n            left: 0;\n            top: 0;\n            width: ${s}px;\n            height: ${n}px;\n        `;
            "hard" === e ? this.drawHard(h) : this.drawSoft(i, h) }
         drawHard(t = "")  {
            const e = this.render.getRect().left + this.render.getRect().width / 2, i = this.state.hardDrawingAngle, s = t + "\n                backface-visibility: hidden;\n                -webkit-backface-visibility: hidden;\n                clip-path: none;\n                -webkit-clip-path: none;\n            " + (0 === this.orientation ? `transform-origin: ${this.render.getRect().pageWidth}px 0; \n                   transform: translate3d(0, 0, 0) rotateY(${i}deg);` : `transform-origin: 0 0; \n                   transform: translate3d(${e}px, 0, 0) rotateY(${i}deg);`);
            this.element.style.cssText = s }
         drawSoft(t, e = "")  {
            let i = "polygon( ";
            for (const t of this.state.area) if (null !== t)  {
                let e = 1 === this.render.getDirection() ?  {
                    x: -t.x + this.state.position.x, y: t.y - this.state.position.y }
                 :  {
                    x: t.x - this.state.position.x, y: t.y - this.state.position.y }
                ;
                e = Geometry.GetRotatedPoint(e,  {
                    x: 0, y: 0 }
                , this.state.angle), i += e.x + "px " + e.y + "px, " }
             i = i.slice(0, -2), i += ")";
            const s = e + `transform-origin: 0 0; clip-path: ${i}; -webkit-clip-path: ${i};` + (this.render.isSafari() && 0 === this.state.angle ? `transform: translate(${t.x}px, ${t.y}px);` : `transform: translate3d(${t.x}px, ${t.y}px, 0) rotate(${this.state.angle}rad);`);
            this.element.style.cssText = s }
         simpleDraw(t)  {
            const e = this.render.getRect(), i = e.pageWidth, s = e.height, n = 1 === t ? e.left + e.pageWidth : e.left, h = e.top;
            this.element.classList.add("--simple"), this.element.style.cssText = `\n            position: absolute; \n            display: block; \n            height: ${s}px; \n            left: ${n}px; \n            top: ${h}px; \n            width: ${i}px; \n            z-index: ${this.render.getSettings().startZIndex + 1};` }
         getElement()  {
            return this.element }
         load()  {
            this.isLoad = !0 }
         setOrientation(t)  {
            super.setOrientation(t), this.element.classList.remove("--left", "--right"), this.element.classList.add(1 === t ? "--right" : "--left") }
         setDrawingDensity(t)  {
            this.element.classList.remove("--soft", "--hard"), this.element.classList.add("--" + t), super.setDrawingDensity(t) }
         }

    St.HtmlPage = HtmlPage;
})(typeof self !== "undefined" ? self : this);
