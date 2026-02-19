/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class ImagePageCollection extends PageCollection  {
        constructor(t, e, i)  {
            super(t, e), this.imagesHref = i }
         load()  {
            for (const t of this.imagesHref)  {
                const e = new ImagePage(this.render, t, "soft");
                e.load(), this.pages.push(e) }
             this.createSpread() }
         }

    St.ImagePageCollection = ImagePageCollection;
})(typeof self !== "undefined" ? self : this);
