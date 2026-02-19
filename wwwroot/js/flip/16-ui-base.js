/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class UIBase  {
        constructor(t, e, i)  {
            this.touchPoint = null, this.swipeTimeout = 250, this.onResize = () =>  {
                this.update() }
            , this.onMouseDown = t =>  {
                if (this.checkTarget(t.target))  {
                    const e = this.getMousePos(t.clientX, t.clientY);
                    this.app.startUserTouch(e), t.preventDefault() }
                 }
            , this.onTouchStart = t =>  {
                if (this.checkTarget(t.target) && t.changedTouches.length > 0)  {
                    const e = t.changedTouches[0], i = this.getMousePos(e.clientX, e.clientY);
                    this.touchPoint =  {
                        point: i, time: Date.now() }
                    , setTimeout(() =>  {
                        null !== this.touchPoint && this.app.startUserTouch(i) }
                    , this.swipeTimeout), this.app.getSettings().mobileScrollSupport || t.preventDefault() }
                 }
            , this.onMouseUp = t =>  {
                const e = this.getMousePos(t.clientX, t.clientY);
                this.app.userStop(e) }
            , this.onMouseMove = t =>  {
                const e = this.getMousePos(t.clientX, t.clientY);
                this.app.userMove(e, !1) }
            , this.onTouchMove = t =>  {
                if (t.changedTouches.length > 0)  {
                    const e = t.changedTouches[0], i = this.getMousePos(e.clientX, e.clientY);
                    this.app.getSettings().mobileScrollSupport ? (null !== this.touchPoint && (Math.abs(this.touchPoint.point.x - i.x) > 10 || "read" !== this.app.getState()) && t.cancelable && this.app.userMove(i, !0), "read" !== this.app.getState() && t.preventDefault()) : this.app.userMove(i, !0) }
                 }
            , this.onTouchEnd = t =>  {
                if (t.changedTouches.length > 0)  {
                    const e = t.changedTouches[0], i = this.getMousePos(e.clientX, e.clientY);
                    let s = !1;
                    if (null !== this.touchPoint)  {
                        const t = i.x - this.touchPoint.point.x, e = Math.abs(i.y - this.touchPoint.point.y);
                        Math.abs(t) > this.swipeDistance && e < 2 * this.swipeDistance && Date.now() - this.touchPoint.time < this.swipeTimeout && (t > 0 ? this.app.flipPrev(this.touchPoint.point.y < this.app.getRender().getRect().height / 2 ? "top" : "bottom") : this.app.flipNext(this.touchPoint.point.y < this.app.getRender().getRect().height / 2 ? "top" : "bottom"), s = !0), this.touchPoint = null }
                     this.app.userStop(i, s) }
                 }
            , this.parentElement = t, t.classList.add("stf__parent"), t.insertAdjacentHTML("afterbegin", '<div class="stf__wrapper"></div>'), this.wrapper = t.querySelector(".stf__wrapper"), this.app = e;
            const s = this.app.getSettings().usePortrait ? 1 : 2;
            t.style.minWidth = i.minWidth * s + "px", t.style.minHeight = i.minHeight + "px", "fixed" === i.size && (t.style.minWidth = i.width * s + "px", t.style.minHeight = i.height + "px"), i.autoSize && (t.style.width = "100%", t.style.maxWidth = 2 * i.maxWidth + "px"), t.style.display = "block", window.addEventListener("resize", this.onResize, !1), this.swipeDistance = i.swipeDistance }
         destroy()  {
            this.app.getSettings().useMouseEvents && this.removeHandlers(), this.distElement.remove(), this.wrapper.remove() }
         getDistElement()  {
            return this.distElement }
         getWrapper()  {
            return this.wrapper }
         setOrientationStyle(t)  {
            this.wrapper.classList.remove("--portrait", "--landscape"), "portrait" === t ? (this.app.getSettings().autoSize && (this.wrapper.style.paddingBottom = this.app.getSettings().height / this.app.getSettings().width * 100 + "%"), this.wrapper.classList.add("--portrait")) : (this.app.getSettings().autoSize && (this.wrapper.style.paddingBottom = this.app.getSettings().height / (2 * this.app.getSettings().width) * 100 + "%"), this.wrapper.classList.add("--landscape")), this.update() }
         removeHandlers()  {
            window.removeEventListener("resize", this.onResize), this.distElement.removeEventListener("mousedown", this.onMouseDown), this.distElement.removeEventListener("touchstart", this.onTouchStart), window.removeEventListener("mousemove", this.onMouseMove), window.removeEventListener("touchmove", this.onTouchMove), window.removeEventListener("mouseup", this.onMouseUp), window.removeEventListener("touchend", this.onTouchEnd) }
         setHandlers()  {
            window.addEventListener("resize", this.onResize, !1), this.app.getSettings().useMouseEvents && (this.distElement.addEventListener("mousedown", this.onMouseDown), this.distElement.addEventListener("touchstart", this.onTouchStart), window.addEventListener("mousemove", this.onMouseMove), window.addEventListener("touchmove", this.onTouchMove,  {
                passive: !this.app.getSettings().mobileScrollSupport }
            ), window.addEventListener("mouseup", this.onMouseUp), window.addEventListener("touchend", this.onTouchEnd)) }
         getMousePos(t, e)  {
            const i = this.distElement.getBoundingClientRect();
            return  {
                x: t - i.left, y: e - i.top }
             }
         checkTarget(t)  {
            return !this.app.getSettings().clickEventForward || !["a", "button"].includes(t.tagName.toLowerCase()) }
         }

    St.UIBase = UIBase;
})(typeof self !== "undefined" ? self : this);
