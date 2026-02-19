/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class HtmlPageCollection extends PageCollection  {
        constructor(t, e, i, s)  {
            super(t, e), this.element = i, this.pagesElement = s }
         load()  {
            for (const t of this.pagesElement)  {
                const e = new HtmlPage(this.render, t, "hard" === t.dataset.density ? "hard" : "soft");
                e.load(), this.pages.push(e) }
             this.createSpread() }
         }

    St.HtmlPageCollection = HtmlPageCollection;
})(typeof self !== "undefined" ? self : this);
