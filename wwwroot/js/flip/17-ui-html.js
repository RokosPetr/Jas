/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class HtmlUI extends UIBase  {
        constructor(t, e, i, s)  {
            super(t, e, i), this.wrapper.insertAdjacentHTML("afterbegin", '<div class="stf__block"></div>'), this.distElement = t.querySelector(".stf__block"), this.items = s;
            for (const t of s) this.distElement.appendChild(t);
            this.setHandlers() }
         clear()  {
            for (const t of this.items) this.parentElement.appendChild(t) }
         updateItems(t)  {
            this.removeHandlers(), this.distElement.innerHTML = "";
            for (const e of t) this.distElement.appendChild(e);
            this.items = t, this.setHandlers() }
         update()  {
            this.app.getRender().update() }
         }

    St.HtmlUI = HtmlUI;
})(typeof self !== "undefined" ? self : this);
