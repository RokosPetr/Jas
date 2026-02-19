/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class CanvasRender extends RenderBase  {
        constructor(t, e, i)  {
            super(t, e), this.canvas = i, this.ctx = i.getContext("2d") }
         getContext()  {
            return this.ctx }
         reload()  {
            }
         drawFrame()  {
            this.clear(), "portrait" !== this.orientation && null != this.leftPage && this.leftPage.simpleDraw(0), null != this.rightPage && this.rightPage.simpleDraw(1), null != this.bottomPage && this.bottomPage.draw(),  null != this.flippingPage && this.flippingPage.draw();
            const t = this.getRect();
            "portrait" === this.orientation && (this.ctx.beginPath(), this.ctx.rect(t.left + t.pageWidth, t.top, t.width, t.height), this.ctx.clip()) }
         drawBookShadow()  {
            const t = this.getRect();
            this.ctx.save(), this.ctx.beginPath();
            const e = t.width / 20;
            this.ctx.rect(t.left, t.top, t.width, t.height);
            const i =  {
                x: t.left + t.width / 2 - e / 2, y: 0 }
            ;
            this.ctx.translate(i.x, i.y);
            const s = this.ctx.createLinearGradient(0, 0, e, 0);
            s.addColorStop(0, "rgba(0, 0, 0, 0)"), s.addColorStop(.4, "rgba(0, 0, 0, 0.2)"), s.addColorStop(.49, "rgba(0, 0, 0, 0.1)"), s.addColorStop(.5, "rgba(0, 0, 0, 0.5)"), s.addColorStop(.51, "rgba(0, 0, 0, 0.4)"), s.addColorStop(1, "rgba(0, 0, 0, 0)"), this.ctx.clip(), this.ctx.fillStyle = s, this.ctx.fillRect(0, 0, e, 2 * t.height), this.ctx.restore() }
         drawOuterShadow()  {
            const t = this.getRect();
            this.ctx.save(), this.ctx.beginPath(), this.ctx.rect(t.left, t.top, t.width, t.height);
            const e = this.convertToGlobal( {
                x: this.shadow.pos.x, y: this.shadow.pos.y }
            );
            this.ctx.translate(e.x, e.y), this.ctx.rotate(Math.PI + this.shadow.angle + Math.PI / 2);
            const i = this.ctx.createLinearGradient(0, 0, this.shadow.width, 0);
            0 === this.shadow.direction ? (this.ctx.translate(0, -100), i.addColorStop(0, "rgba(0, 0, 0, " + this.shadow.opacity + ")"), i.addColorStop(1, "rgba(0, 0, 0, 0)")) : (this.ctx.translate(-this.shadow.width, -100), i.addColorStop(0, "rgba(0, 0, 0, 0)"), i.addColorStop(1, "rgba(0, 0, 0, " + this.shadow.opacity + ")")), this.ctx.clip(), this.ctx.fillStyle = i, this.ctx.fillRect(0, 0, this.shadow.width, 2 * t.height), this.ctx.restore() }
         drawInnerShadow()  {
            const t = this.getRect();
            this.ctx.save(), this.ctx.beginPath();
            const e = this.convertToGlobal( {
                x: this.shadow.pos.x, y: this.shadow.pos.y }
            ), i = this.convertRectToGlobal(this.pageRect);
            this.ctx.moveTo(i.topLeft.x, i.topLeft.y), this.ctx.lineTo(i.topRight.x, i.topRight.y), this.ctx.lineTo(i.bottomRight.x, i.bottomRight.y), this.ctx.lineTo(i.bottomLeft.x, i.bottomLeft.y), this.ctx.translate(e.x, e.y), this.ctx.rotate(Math.PI + this.shadow.angle + Math.PI / 2);
            const s = 3 * this.shadow.width / 4, n = this.ctx.createLinearGradient(0, 0, s, 0);
            0 === this.shadow.direction ? (this.ctx.translate(-s, -100), n.addColorStop(1, "rgba(0, 0, 0, " + this.shadow.opacity + ")"), n.addColorStop(.9, "rgba(0, 0, 0, 0.05)"), n.addColorStop(.7, "rgba(0, 0, 0, " + this.shadow.opacity + ")"), n.addColorStop(0, "rgba(0, 0, 0, 0)")) : (this.ctx.translate(0, -100), n.addColorStop(0, "rgba(0, 0, 0, " + this.shadow.opacity + ")"), n.addColorStop(.1, "rgba(0, 0, 0, 0.05)"), n.addColorStop(.3, "rgba(0, 0, 0, " + this.shadow.opacity + ")"), n.addColorStop(1, "rgba(0, 0, 0, 0)")), this.ctx.clip(), this.ctx.fillStyle = n, this.ctx.fillRect(0, 0, s, 2 * t.height), this.ctx.restore() }
         clear()  {
            this.ctx.fillStyle = "white", this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height) }
         }

    St.CanvasRender = CanvasRender;
})(typeof self !== "undefined" ? self : this);
