/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class ImagePage extends PageBase  {
        constructor(t, e, i)  {
            super(t, i), this.image = null, this.isLoad = !1, this.loadingAngle = 0, this.image = new Image, this.image.src = e }
         draw(t)  {
            const e = this.render.getContext(), i = this.render.convertToGlobal(this.state.position), s = this.render.getRect().pageWidth, n = this.render.getRect().height;
            e.save(), e.translate(i.x, i.y), e.beginPath();
            for (let t of this.state.area) null !== t && (t = this.render.convertToGlobal(t), e.lineTo(t.x - i.x, t.y - i.y));
            e.rotate(this.state.angle), e.clip(), this.isLoad ? e.drawImage(this.image, 0, 0, s, n) : this.drawLoader(e,  {
                x: 0, y: 0 }
            , s, n), e.restore() }
         simpleDraw(t)  {
            const e = this.render.getRect(), i = this.render.getContext(), s = e.pageWidth, n = e.height, h = 1 === t ? e.left + e.pageWidth : e.left, r = e.top;
            this.isLoad ? i.drawImage(this.image, h, r, s, n) : this.drawLoader(i,  {
                x: h, y: r }
            , s, n) }
         drawLoader(t, e, i, s)  {
            t.beginPath(), t.strokeStyle = "rgb(200, 200, 200)", t.fillStyle = "rgb(255, 255, 255)", t.lineWidth = 1, t.rect(e.x + 1, e.y + 1, i - 1, s - 1), t.stroke(), t.fill();
            const n =  {
                x: e.x + i / 2, y: e.y + s / 2 }
            ;
            t.beginPath(), t.lineWidth = 10, t.arc(n.x, n.y, 20, this.loadingAngle, 3 * Math.PI / 2 + this.loadingAngle), t.stroke(), t.closePath(), this.loadingAngle += .07, this.loadingAngle >= 2 * Math.PI && (this.loadingAngle = 0) }
         load()  {
            this.isLoad || (this.image.onload = () =>  {
                this.isLoad = !0 }
            ) }
         newTemporaryCopy()  {
            return this }
         getTemporaryCopy()  {
            return this }
         hideTemporaryCopy()  {
            }
         }

    St.ImagePage = ImagePage;
})(typeof self !== "undefined" ? self : this);
