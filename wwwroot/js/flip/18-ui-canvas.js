/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class CanvasUI extends UIBase  {
        constructor(t, e, i)  {
            super(t, e, i), this.wrapper.innerHTML = '<canvas class="stf__canvas"></canvas>', this.canvas = t.querySelectorAll("canvas")[0], this.distElement = this.canvas, this.resizeCanvas(), this.setHandlers() }
         resizeCanvas()  {
            const t = getComputedStyle(this.canvas), e = parseInt(t.getPropertyValue("width"), 10), i = parseInt(t.getPropertyValue("height"), 10);
            this.canvas.width = e, this.canvas.height = i }
         getCanvas()  {
            return this.canvas }
         update()  {
            this.resizeCanvas(), this.app.getRender().update() }
         }

    St.CanvasUI = CanvasUI;
})(typeof self !== "undefined" ? self : this);
