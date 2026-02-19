/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class PageCollection  {
        constructor(t, e)  {
            this.pages = [], this.currentPageIndex = 0, this.currentSpreadIndex = 0, this.landscapeSpread = [], this.portraitSpread = [], this.render = e, this.app = t, this.currentPageIndex = 0, this.isShowCover = this.app.getSettings().showCover }
         destroy()  {
            this.pages = [] }
         createSpread()  {
            this.landscapeSpread = [], this.portraitSpread = [];
            for (let t = 0;
            t < this.pages.length;
            t++)this.portraitSpread.push([t]);
            let t = 0;
            this.isShowCover && (this.pages[0].setDensity("hard"), this.landscapeSpread.push([t]), t++);
            for (let e = t;
            e < this.pages.length;
            e += 2)e < this.pages.length - 1 ? this.landscapeSpread.push([e, e + 1]) : (this.landscapeSpread.push([e]), this.pages[e].setDensity("hard")) }
         getSpread()  {
            return "landscape" === this.render.getOrientation() ? this.landscapeSpread : this.portraitSpread }
         getSpreadIndexByPage(t)  {
            const e = this.getSpread();
            for (let i = 0;
            i < e.length;
            i++)if (t === e[i][0] || t === e[i][1]) return i;
            return null }
         getPageCount()  {
            return this.pages.length }
         getPages()  {
            return this.pages }
         getPage(t)  {
            if (t >= 0 && t < this.pages.length) return this.pages[t];
            throw new Error("Invalid page number") }
         nextBy(t)  {
            const e = this.pages.indexOf(t);
            return e < this.pages.length - 1 ? this.pages[e + 1] : null }
         prevBy(t)  {
            const e = this.pages.indexOf(t);
            return e > 0 ? this.pages[e - 1] : null }
         getFlippingPage(t)  {
            const e = this.currentSpreadIndex;
            if ("portrait" === this.render.getOrientation()) return 0 === t ? this.pages[e].newTemporaryCopy() : this.pages[e - 1];
             {
                const i = 0 === t ? this.getSpread()[e + 1] : this.getSpread()[e - 1];
                return 1 === i.length || 0 === t ? this.pages[i[0]] : this.pages[i[1]] }
             }
         getBottomPage(t)  {
            const e = this.currentSpreadIndex;
            if ("portrait" === this.render.getOrientation()) return 0 === t ? this.pages[e + 1] : this.pages[e - 1];
             {
                const i = 0 === t ? this.getSpread()[e + 1] : this.getSpread()[e - 1];
                return 1 === i.length ? this.pages[i[0]] : 0 === t ? this.pages[i[1]] : this.pages[i[0]] }
             }
         showNext()  {
            this.currentSpreadIndex < this.getSpread().length && (this.currentSpreadIndex++, this.showSpread()) }
         showPrev()  {
            this.currentSpreadIndex > 0 && (this.currentSpreadIndex--, this.showSpread()) }
         getCurrentPageIndex()  {
            return this.currentPageIndex }
         show(t = null)  {
            if (null === t && (t = this.currentPageIndex), t < 0 || t >= this.pages.length) return;
            const e = this.getSpreadIndexByPage(t);
            null !== e && (this.currentSpreadIndex = e, this.showSpread()) }
         getCurrentSpreadIndex()  {
            return this.currentSpreadIndex }
         setCurrentSpreadIndex(t)  {
            if (!(t >= 0 && t < this.getSpread().length)) throw new Error("Invalid page");
            this.currentSpreadIndex = t }
         showSpread()  {
            const t = this.getSpread()[this.currentSpreadIndex];
            2 === t.length ? (this.render.setLeftPage(this.pages[t[0]]), this.render.setRightPage(this.pages[t[1]])) : "landscape" === this.render.getOrientation() && t[0] === this.pages.length - 1 ? (this.render.setLeftPage(this.pages[t[0]]), this.render.setRightPage(null)) : (this.render.setLeftPage(null), this.render.setRightPage(this.pages[t[0]])), this.currentPageIndex = t[0], this.app.updatePageIndex(this.currentPageIndex) }
         }

    St.PageCollection = PageCollection;
})(typeof self !== "undefined" ? self : this);
