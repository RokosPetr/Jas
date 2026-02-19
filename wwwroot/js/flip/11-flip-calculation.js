/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class FlipCalculation  {
        constructor(t, e, i, s)  {
            this.direction = t, this.corner = e, this.topIntersectPoint = null, this.sideIntersectPoint = null, this.bottomIntersectPoint = null, this.pageWidth = parseInt(i, 10), this.pageHeight = parseInt(s, 10) }
         calc(t)  {
            try  {
                return this.position = this.calcAngleAndPosition(t), this.calculateIntersectPoint(this.position), !0 }
             catch (t)  {
                return !1 }
             }
         getFlippingClipArea()  {
            const t = [];
            let e = !1;
            return t.push(this.rect.topLeft), t.push(this.topIntersectPoint), null === this.sideIntersectPoint ? e = !0 : (t.push(this.sideIntersectPoint), null === this.bottomIntersectPoint && (e = !1)), t.push(this.bottomIntersectPoint), (e || "bottom" === this.corner) && t.push(this.rect.bottomLeft), t }
         getBottomClipArea()  {
            const t = [];
            return t.push(this.topIntersectPoint), "top" === this.corner ? t.push( {
                x: this.pageWidth, y: 0 }
            ) : (null !== this.topIntersectPoint && t.push( {
                x: this.pageWidth, y: 0 }
            ), t.push( {
                x: this.pageWidth, y: this.pageHeight }
            )), null !== this.sideIntersectPoint ? Geometry.GetDistanceBetweenTwoPoint(this.sideIntersectPoint, this.topIntersectPoint) >= 10 && t.push(this.sideIntersectPoint) : "top" === this.corner && t.push( {
                x: this.pageWidth, y: this.pageHeight }
            ), t.push(this.bottomIntersectPoint), t.push(this.topIntersectPoint), t }
         getAngle()  {
            return 0 === this.direction ? -this.angle : this.angle }
         getRect()  {
            return this.rect }
         getPosition()  {
            return this.position }
         getActiveCorner()  {
            return 0 === this.direction ? this.rect.topLeft : this.rect.topRight }
         getDirection()  {
            return this.direction }
         getFlippingProgress()  {
            return Math.abs((this.position.x - this.pageWidth) / (2 * this.pageWidth) * 100) }
         getCorner()  {
            return this.corner }
         getBottomPagePosition()  {
            return 1 === this.direction ?  {
                x: this.pageWidth, y: 0 }
             :  {
                x: 0, y: 0 }
             }
         getShadowStartPoint()  {
            return "top" === this.corner ? this.topIntersectPoint : null !== this.sideIntersectPoint ? this.sideIntersectPoint : this.topIntersectPoint }
         getShadowAngle()  {
            const t = Geometry.GetAngleBetweenTwoLine(this.getSegmentToShadowLine(), [ {
                x: 0, y: 0 }
            ,  {
                x: this.pageWidth, y: 0 }
            ]);
            return 0 === this.direction ? t : Math.PI - t }
         calcAngleAndPosition(t)  {
            let e = t;
            if (this.updateAngleAndGeometry(e), e = "top" === this.corner ? this.checkPositionAtCenterLine(e,  {
                x: 0, y: 0 }
            ,  {
                x: 0, y: this.pageHeight }
            ) : this.checkPositionAtCenterLine(e,  {
                x: 0, y: this.pageHeight }
            ,  {
                x: 0, y: 0 }
            ), Math.abs(e.x - this.pageWidth) < 1 && Math.abs(e.y) < 1) throw new Error("Point is too small");
            return e }
         updateAngleAndGeometry(t)  {
            this.angle = this.calculateAngle(t), this.rect = this.getPageRect(t) }
         calculateAngle(t)  {
            const e = this.pageWidth - t.x + 1, i = "bottom" === this.corner ? this.pageHeight - t.y : t.y;
            let s = 2 * Math.acos(e / Math.sqrt(i * i + e * e));
            i < 0 && (s = -s);
            const n = Math.PI - s;
            if (!isFinite(s) || n >= 0 && n < .003) throw new Error("The G point is too small");
            return "bottom" === this.corner && (s = -s), s }
         getPageRect(t)  {
            return "top" === this.corner ? this.getRectFromBasePoint([ {
                x: 0, y: 0 }
            ,  {
                x: this.pageWidth, y: 0 }
            ,  {
                x: 0, y: this.pageHeight }
            ,  {
                x: this.pageWidth, y: this.pageHeight }
            ], t) : this.getRectFromBasePoint([ {
                x: 0, y: -this.pageHeight }
            ,  {
                x: this.pageWidth, y: -this.pageHeight }
            ,  {
                x: 0, y: 0 }
            ,  {
                x: this.pageWidth, y: 0 }
            ], t) }
         getRectFromBasePoint(t, e)  {
            return  {
                topLeft: this.getRotatedPoint(t[0], e), topRight: this.getRotatedPoint(t[1], e), bottomLeft: this.getRotatedPoint(t[2], e), bottomRight: this.getRotatedPoint(t[3], e) }
             }
         getRotatedPoint(t, e)  {
            return  {
                x: t.x * Math.cos(this.angle) + t.y * Math.sin(this.angle) + e.x, y: t.y * Math.cos(this.angle) - t.x * Math.sin(this.angle) + e.y }
             }
         calculateIntersectPoint(t)  {
            const e =  {
                left: -1, top: -1, width: this.pageWidth + 2, height: this.pageHeight + 2 }
            ;
            "top" === this.corner ? (this.topIntersectPoint = Geometry.GetIntersectBetweenTwoSegment(e, [t, this.rect.topRight], [ {
                x: 0, y: 0 }
            ,  {
                x: this.pageWidth, y: 0 }
            ]), this.sideIntersectPoint = Geometry.GetIntersectBetweenTwoSegment(e, [t, this.rect.bottomLeft], [ {
                x: this.pageWidth, y: 0 }
            ,  {
                x: this.pageWidth, y: this.pageHeight }
            ]), this.bottomIntersectPoint = Geometry.GetIntersectBetweenTwoSegment(e, [this.rect.bottomLeft, this.rect.bottomRight], [ {
                x: 0, y: this.pageHeight }
            ,  {
                x: this.pageWidth, y: this.pageHeight }
            ])) : (this.topIntersectPoint = Geometry.GetIntersectBetweenTwoSegment(e, [this.rect.topLeft, this.rect.topRight], [ {
                x: 0, y: 0 }
            ,  {
                x: this.pageWidth, y: 0 }
            ]), this.sideIntersectPoint = Geometry.GetIntersectBetweenTwoSegment(e, [t, this.rect.topLeft], [ {
                x: this.pageWidth, y: 0 }
            ,  {
                x: this.pageWidth, y: this.pageHeight }
            ]), this.bottomIntersectPoint = Geometry.GetIntersectBetweenTwoSegment(e, [this.rect.bottomLeft, this.rect.bottomRight], [ {
                x: 0, y: this.pageHeight }
            ,  {
                x: this.pageWidth, y: this.pageHeight }
            ])) }
         checkPositionAtCenterLine(t, e, i)  {
            let s = t;
            const n = Geometry.LimitPointToCircle(e, this.pageWidth, s);
            s !== n && (s = n, this.updateAngleAndGeometry(s));
            const r = Math.sqrt(Math.pow(this.pageWidth, 2) + Math.pow(this.pageHeight, 2));
            let o = this.rect.bottomRight, a = this.rect.topLeft;
            if ("bottom" === this.corner && (o = this.rect.topRight, a = this.rect.bottomLeft), o.x <= 0)  {
                const t = Geometry.LimitPointToCircle(i, r, a);
                t !== s && (s = t, this.updateAngleAndGeometry(s)) }
             return s }
         getSegmentToShadowLine()  {
            const t = this.getShadowStartPoint();
            return [t, t !== this.sideIntersectPoint && null !== this.sideIntersectPoint ? this.sideIntersectPoint : this.bottomIntersectPoint] }
         }

    St.FlipCalculation = FlipCalculation;
})(typeof self !== "undefined" ? self : this);
