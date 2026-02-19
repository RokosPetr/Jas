/* PageFlip Refactor (multi-file, plain JS) */
(function (root) {
    const St = root.St = root.St || {};

class PageBase  {
        constructor(t, e)  {
            this.state =  {
                angle: 0, area: [], position:  {
                    x: 0, y: 0 }
                , hardAngle: 0, hardDrawingAngle: 0 }
            , this.createdDensity = e, this.nowDrawingDensity = this.createdDensity, this.render = t }
         setDensity(t)  {
            this.createdDensity = t, this.nowDrawingDensity = t }
         setDrawingDensity(t)  {
            this.nowDrawingDensity = t }
         setPosition(t)  {
            this.state.position = t }
         setAngle(t)  {
            this.state.angle = t }
         setArea(t)  {
            this.state.area = t }
         setHardDrawingAngle(t)  {
            this.state.hardDrawingAngle = t }
         setHardAngle(t)  {
            this.state.hardAngle = t, this.state.hardDrawingAngle = t }
         setOrientation(t)  {
            this.orientation = t }
         getDrawingDensity()  {
            return this.nowDrawingDensity }
         getDensity()  {
            return this.createdDensity }
         getHardAngle()  {
            return this.state.hardAngle }
         }

    St.PageBase = PageBase;
})(typeof self !== "undefined" ? self : this);
