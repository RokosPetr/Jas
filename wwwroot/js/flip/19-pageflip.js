/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class EventEmitter {
    constructor() {
        this.events = new Map();
    }

    on(eventName, handler) {
        if (this.events.has(eventName)) {
            this.events.get(eventName).push(handler);
        } else {
            this.events.set(eventName, [handler]);
        }
        return this;
    }

    off(eventName) {
        this.events.delete(eventName);
    }

    trigger(eventName, object, data = null) {
        if (!this.events.has(eventName)) return;
        for (const handler of this.events.get(eventName)) {
            handler({ data, object });
        }
    }
}

class PageFlip extends EventEmitter {
        constructor(t, e)  {
            super(), this.isUserTouch = !1, this.isUserMove = !1, this.setting = null, this.pages = null, this.setting = (new SettingsParser).getSettings(e), this.block = t }
         destroy()  {
            this.ui.destroy(), this.block.remove() }
         update()  {
            this.render.update(), this.pages.show() }
         loadFromImages(t)  {
            this.ui = new CanvasUI(this.block, this, this.setting);
            const e = this.ui.getCanvas();
            this.render = new CanvasRender(this, this.setting, e), this.flipController = new FlipController(this.render, this), this.pages = new ImagePageCollection(this, this.render, t), this.pages.load(), this.render.start(), this.pages.show(this.setting.startPage), setTimeout(() =>  {
                this.ui.update(), this.trigger("init", this,  {
                    page: this.setting.startPage, mode: this.render.getOrientation() }
                ) }
            , 1) }
         loadFromHTML(t)  {
            this.ui = new HtmlUI(this.block, this, this.setting, t), this.render = new HtmlRender(this, this.setting, this.ui.getDistElement()), this.flipController = new FlipController(this.render, this), this.pages = new HtmlPageCollection(this, this.render, this.ui.getDistElement(), t), this.pages.load(), this.render.start(), this.pages.show(this.setting.startPage), setTimeout(() =>  {
                this.ui.update(), this.trigger("init", this,  {
                    page: this.setting.startPage, mode: this.render.getOrientation() }
                ) }
            , 1) }
         updateFromImages(t)  {
            const e = this.pages.getCurrentPageIndex();
            this.pages.destroy(), this.pages = new ImagePageCollection(this, this.render, t), this.pages.load(), this.pages.show(e), this.trigger("update", this,  {
                page: e, mode: this.render.getOrientation() }
            ) }
         updateFromHtml(t)  {
            const e = this.pages.getCurrentPageIndex();
            this.pages.destroy(), this.pages = new HtmlPageCollection(this, this.render, this.ui.getDistElement(), t), this.pages.load(), this.ui.updateItems(t), this.render.reload(), this.pages.show(e), this.trigger("update", this,  {
                page: e, mode: this.render.getOrientation() }
            ) }
         clear()  {
            this.pages.destroy(), this.ui.clear() }
         turnToPrevPage()  {
            this.pages.showPrev() }
         turnToNextPage()  {
            this.pages.showNext() }
         turnToPage(t)  {
            this.pages.show(t) }
         flipNext(t = "top")  {
            this.flipController.flipNext(t) }
         flipPrev(t = "top")  {
            this.flipController.flipPrev(t) }
         flip(t, e = "top")  {
            this.flipController.flipToPage(t, e) }
         updateState(t)  {
            this.trigger("changeState", this, t) }
         updatePageIndex(t)  {
            this.trigger("flip", this, t) }
         updateOrientation(t)  {
            this.ui.setOrientationStyle(t), this.update(), this.trigger("changeOrientation", this, t) }
         getPageCount()  {
            return this.pages.getPageCount() }
         getCurrentPageIndex()  {
            return this.pages.getCurrentPageIndex() }
         getPage(t)  {
            return this.pages.getPage(t) }
         getRender()  {
            return this.render }
         getFlipController()  {
            return this.flipController }
         getOrientation()  {
            return this.render.getOrientation() }
         getBoundsRect()  {
            return this.render.getRect() }
         getSettings()  {
            return this.setting }
         getUI()  {
            return this.ui }
         getState()  {
            return this.flipController.getState() }
         getPageCollection()  {
            return this.pages }
         startUserTouch(t)  {
            this.mousePosition = t, this.isUserTouch = !0, this.isUserMove = !1 }
         userMove(t, e)  {
            this.isUserTouch || e || !this.setting.showPageCorners ? this.isUserTouch && Geometry.GetDistanceBetweenTwoPoint(this.mousePosition, t) > 5 && (this.isUserMove = !0, this.flipController.fold(t)) : this.flipController.showCorner(t) }
         userStop(t, e = !1)  {
            this.isUserTouch && (this.isUserTouch = !1, e || (this.isUserMove ? this.flipController.stopMove() : this.flipController.flip(t))) }
         }

    St.EventEmitter = EventEmitter;
    St.PageFlip = PageFlip;
})(typeof self !== "undefined" ? self : this);
