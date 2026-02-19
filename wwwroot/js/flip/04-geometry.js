/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class Geometry  {
        static GetDistanceBetweenTwoPoint(t, e)  {
            return null === t || null === e ? 1 / 0 : Math.sqrt(Math.pow(e.x - t.x, 2) + Math.pow(e.y - t.y, 2)) }
         static GetSegmentLength(t)  {
            return Geometry.GetDistanceBetweenTwoPoint(t[0], t[1]) }
         static GetAngleBetweenTwoLine(t, e)  {
            const i = t[0].y - t[1].y, s = e[0].y - e[1].y, n = t[1].x - t[0].x, h = e[1].x - e[0].x;
            return Math.acos((i * s + n * h) / (Math.sqrt(i * i + n * n) * Math.sqrt(s * s + h * h))) }
         static PointInRect(t, e)  {
            return null === e ? null : e.x >= t.left && e.x <= t.width + t.left && e.y >= t.top && e.y <= t.top + t.height ? e : null }
         static GetRotatedPoint(t, e, i)  {
            return  {
                x: t.x * Math.cos(i) + t.y * Math.sin(i) + e.x, y: t.y * Math.cos(i) - t.x * Math.sin(i) + e.y }
             }
         static LimitPointToCircle(t, e, i)  {
            if (Geometry.GetDistanceBetweenTwoPoint(t, i) <= e) return i;
            const s = t.x, n = t.y, r = i.x, o = i.y;
            let a = Math.sqrt(Math.pow(e, 2) * Math.pow(s - r, 2) / (Math.pow(s - r, 2) + Math.pow(n - o, 2))) + s;
            i.x < 0 && (a *= -1);
            let g = (a - s) * (n - o) / (s - r) + n;
            return s - r + n === 0 && (g = e),  {
                x: a, y: g }
             }
         static GetIntersectBetweenTwoSegment(t, e, i)  {
            return Geometry.PointInRect(t, Geometry.GetIntersectBeetwenTwoLine(e, i)) }
         static GetIntersectBeetwenTwoLine(t, e)  {
            const i = t[0].y - t[1].y, s = e[0].y - e[1].y, n = t[1].x - t[0].x, h = e[1].x - e[0].x, r = t[0].x * t[1].y - t[1].x * t[0].y, o = e[0].x * e[1].y - e[1].x * e[0].y, a = i * o - s * r, g = n * o - h * r, l = -(r * h - o * n) / (i * h - s * n), d = -(i * o - s * r) / (i * h - s * n);
            if (isFinite(l) && isFinite(d)) return  {
                x: l, y: d }
            ;
            if (Math.abs(a - g) < .1) throw new Error("Segment included");
            return null }
         static GetCordsFromTwoPoint(t, e)  {
            const i = Math.abs(t.x - e.x), s = Math.abs(t.y - e.y), n = Math.max(i, s), h = [t];
            function r(t, e, i, s, n)  {
                return e > t ? t + n * (i / s) : e < t ? t - n * (i / s) : t }
             for (let o = 1;
            o <= n;
            o += 1)Geometry.push( {
                x: r(t.x, e.x, i, n, o), y: r(t.y, e.y, s, n, o) }
            );
            return h }
         }

    St.Geometry = Geometry;
})(typeof self !== "undefined" ? self : this);
