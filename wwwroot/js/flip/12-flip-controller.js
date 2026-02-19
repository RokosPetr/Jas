/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class FlipController  {
        constructor(t, e)  {
            this.flippingPage = null, this.bottomPage = null, this.calc = null, this.state = "read", this.render = t, this.app = e }
         fold(t)  {
            this.setState("user_fold"), null === this.calc && this.start(t), this.do(this.render.convertToPage(t)) }
         flip(t)  {
            if (this.app.getSettings().disableFlipByClick && !this.isPointOnCorners(t)) return;
            if (null !== this.calc && this.render.finishAnimation(), !this.start(t)) return;
            const e = this.getBoundsRect();
            this.setState("flipping");
            const i = e.height / 10, s = "bottom" === this.calc.getCorner() ? e.height - i : i, n = "bottom" === this.calc.getCorner() ? e.height : 0;
            this.calc.calc( {
                x: e.pageWidth - i, y: s }
            ), this.animateFlippingTo( {
                x: e.pageWidth - i, y: s }
            ,  {
                x: -e.pageWidth, y: n }
            , !0) }
         start(t)  {
            this.reset();
            const e = this.render.convertToBook(t), i = this.getBoundsRect(), s = this.getDirectionByPoint(e), n = e.y >= i.height / 2 ? "bottom" : "top";
            if (!this.checkDirection(s)) return !1;
            try  {
                if (this.flippingPage = this.app.getPageCollection().getFlippingPage(s), this.bottomPage = this.app.getPageCollection().getBottomPage(s), "landscape" === this.render.getOrientation()) if (1 === s)  {
                    const t = this.app.getPageCollection().nextBy(this.flippingPage);
                    null !== t && this.flippingPage.getDensity() !== t.getDensity() && (this.flippingPage.setDrawingDensity("hard"), t.setDrawingDensity("hard")) }
                 else  {
                    const t = this.app.getPageCollection().prevBy(this.flippingPage);
                    null !== t && this.flippingPage.getDensity() !== t.getDensity() && (this.flippingPage.setDrawingDensity("hard"), t.setDrawingDensity("hard")) }
                 return this.render.setDirection(s), this.calc = new FlipCalculation(s, n, i.pageWidth.toString(10), i.height.toString(10)), !0 }
             catch (t)  {
                return !1 }
             }
         do(t)  {
            if (null !== this.calc && this.calc.calc(t))  {
                const t = this.calc.getFlippingProgress();
                this.bottomPage.setArea(this.calc.getBottomClipArea()), this.bottomPage.setPosition(this.calc.getBottomPagePosition()), this.bottomPage.setAngle(0), this.bottomPage.setHardAngle(0), this.flippingPage.setArea(this.calc.getFlippingClipArea()), this.flippingPage.setPosition(this.calc.getActiveCorner()), this.flippingPage.setAngle(this.calc.getAngle()), 0 === this.calc.getDirection() ? this.flippingPage.setHardAngle(90 * (200 - 2 * t) / 100) : this.flippingPage.setHardAngle(-90 * (200 - 2 * t) / 100), this.render.setPageRect(this.calc.getRect()), this.render.setBottomPage(this.bottomPage), this.render.setFlippingPage(this.flippingPage), this.render.setShadowData(this.calc.getShadowStartPoint(), this.calc.getShadowAngle(), t, this.calc.getDirection()) }
             }
         flipToPage(t, e)  {
            const i = this.app.getPageCollection().getCurrentSpreadIndex(), s = this.app.getPageCollection().getSpreadIndexByPage(t);
            try  {
                s > i && (this.app.getPageCollection().setCurrentSpreadIndex(s - 1), this.flipNext(e)), s < i && (this.app.getPageCollection().setCurrentSpreadIndex(s + 1), this.flipPrev(e)) }
             catch (t)  {
                }
             }
         flipNext(t)  {
            this.flip( {
                x: this.render.getRect().left + 2 * this.render.getRect().pageWidth - 10, y: "top" === t ? 1 : this.render.getRect().height - 2 }
            ) }
         flipPrev(t)  {
            this.flip( {
                x: 10, y: "top" === t ? 1 : this.render.getRect().height - 2 }
            ) }
         stopMove()  {
            if (null === this.calc) return;
            const t = this.calc.getPosition(), e = this.getBoundsRect(), i = "bottom" === this.calc.getCorner() ? e.height : 0;
            t.x <= 0 ? this.animateFlippingTo(t,  {
                x: -e.pageWidth, y: i }
            , !0) : this.animateFlippingTo(t,  {
                x: e.pageWidth, y: i }
            , !1) }
         showCorner(t)  {
            if (!this.checkState("read", "fold_corner")) return;
            const e = this.getBoundsRect(), i = e.pageWidth;
            if (this.isPointOnCorners(t)) if (null === this.calc)  {
                if (!this.start(t)) return;
                this.setState("fold_corner"), this.calc.calc( {
                    x: i - 1, y: 1 }
                );
                const s = 50, n = "bottom" === this.calc.getCorner() ? e.height - 1 : 1, h = "bottom" === this.calc.getCorner() ? e.height - s : s;
                this.animateFlippingTo( {
                    x: i - 1, y: n }
                ,  {
                    x: i - s, y: h }
                , !1, !1) }
             else this.do(this.render.convertToPage(t));
            else this.setState("read"), this.render.finishAnimation(), this.stopMove() }
         animateFlippingTo(t, e, i, s = !0)  {
            const n = Geometry.GetCordsFromTwoPoint(t, e), r = [];
            for (const t of n) r.push(() => this.do(t));
            const o = this.getAnimationDuration(n.length);
            this.render.startAnimation(r, o, () =>  {
                this.calc && (i && (1 === this.calc.getDirection() ? this.app.turnToPrevPage() : this.app.turnToNextPage()), s && (this.render.setBottomPage(null), this.render.setFlippingPage(null), this.render.clearShadow(), this.setState("read"), this.reset())) }
            ) }
         getCalculation()  {
            return this.calc }
         getState()  {
            return this.state }
         setState(t)  {
            this.state !== t && (this.app.updateState(t), this.state = t) }
         getDirectionByPoint(t)  {
            const e = this.getBoundsRect();
            if ("portrait" === this.render.getOrientation())  {
                if (t.x - e.pageWidth <= e.width / 5) return 1 }
             else if (t.x < e.width / 2) return 1;
            return 0 }
         getAnimationDuration(t)  {
            const e = this.app.getSettings().flippingTime;
            return t >= 1e3 ? e : t / 1e3 * e }
         checkDirection(t)  {
            return 0 === t ? this.app.getCurrentPageIndex() < this.app.getPageCount() - 1 : this.app.getCurrentPageIndex() >= 1 }
         reset()  {
            this.calc = null, this.flippingPage = null, this.bottomPage = null }
         getBoundsRect()  {
            return this.render.getRect() }
         checkState(...t)  {
            for (const e of t) if (this.state === e) return !0;
            return !1 }
         isPointOnCorners(t)  {
            const e = this.getBoundsRect(), i = e.pageWidth, s = Math.sqrt(Math.pow(i, 2) + Math.pow(e.height, 2)) / 5, n = this.render.convertToBook(t);
            return n.x > 0 && n.y > 0 && n.x < e.width && n.y < e.height && (n.x < s || n.x > e.width - s) && (n.y < s || n.y > e.height - s) }
         }

    St.FlipController = FlipController;
})(typeof self !== "undefined" ? self : this);
