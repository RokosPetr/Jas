// Jednoduchý fullscreen/zoom viewer pro PageFlip
// Očekává existující elementy: #overlay, #viewer, #vimg, #btnClose, #btnReset

(function (global) {
    function setupFlipViewer(config) {
        const bookWrap = config.bookWrap;
        const pageFlip = config.pageFlip;
        const getAllPages = config.getAllPages;

        const overlay = document.getElementById("overlay");
        const viewer = document.getElementById("viewer");
        const img = document.getElementById("vimg");
        const btnClose = document.getElementById("btnClose");
        const btnReset = document.getElementById("btnReset");   

        let scale = 1;
        let tx = 0;
        let ty = 0;
        const MIN_SCALE = 0.2;
        const MAX_SCALE = 8;

        function clamp(v, a, b) {
            return Math.max(a, Math.min(b, v));
        }

        function applyTransform() {
            img.style.transform = "translate(" + tx + "px, " + ty + "px) scale(" + scale + ")";
        }

        function fitAndCenterDesktop() {
            const vw = window.innerWidth;
            const vh = window.innerHeight;
            const iw = img.naturalWidth;
            const ih = img.naturalHeight;

            if (!iw || !ih) {
                return;
            }

            scale = clamp(vh / ih, MIN_SCALE, MAX_SCALE);
            tx = (vw - iw * scale) / 2;
            ty = (vh - ih * scale) / 2;
            applyTransform();
        }

        function zoomTo(newScale, mx, my) {
            newScale = clamp(newScale, MIN_SCALE, MAX_SCALE);
            const old = scale;
            if (newScale === old) {
                return;
            }

            tx = mx - (mx - tx) * (newScale / old);
            ty = my - (my - ty) * (newScale / old);
            scale = newScale;
            applyTransform();
        }

        // určení indexu stránky podle pozice double-clicku (kvůli dvojstraně)
        let lastDblX = null;

        function pageIndexFromClick() {
            const base = pageFlip.getCurrentPageIndex();
            const count = pageFlip.getPageCount();
            if (lastDblX == null) {
                return base;
            }

            const r = bookWrap.getBoundingClientRect();
            const clickedRight = (lastDblX - r.left) > (r.width / 2);
            return clickedRight ? Math.min(base + 1, count - 1) : base;
        }

        function openFullscreen() {
            const allPages = getAllPages();
            if (!allPages || !allPages.length) {
                return;
            }

            const idx = pageIndexFromClick();
            const url = allPages[idx];

            overlay.classList.add("open");
            overlay.setAttribute("aria-hidden", "false");
            document.body.style.overflow = "hidden";

            img.src = url;

            function ready() {
                fitAndCenterDesktop();
            }

            if (img.decode) {
                img.decode().then(ready)["catch"](ready);
            } else {
                img.onload = ready;
            }
        }

        function closeFullscreen() {
            overlay.classList.remove("open");
            overlay.setAttribute("aria-hidden", "true");
            document.body.style.overflow = "";
        }

        if (btnClose) {
            btnClose.addEventListener("click", closeFullscreen);
        }

        if (btnReset) {
            btnReset.addEventListener("click", function () {
                if (img.naturalWidth) {
                    fitAndCenterDesktop();
                }
            });
        }

        // wheel zoom podle myši
        viewer.addEventListener("wheel", function (e) {
            if (!overlay.classList.contains("open")) {
                return;
            }
            e.preventDefault();

            const zoomIntensity = 0.12;
            const dir = e.deltaY > 0 ? -1 : 1;
            zoomTo(scale * (1 + zoomIntensity * dir), e.clientX, e.clientY);
        }, { passive: false });

        // drag myší
        let dragging = false;
        let lastX = 0;
        let lastY = 0;

        viewer.addEventListener("mousedown", function (e) {
            if (!overlay.classList.contains("open")) {
                return;
            }
            dragging = true;
            lastX = e.clientX;
            lastY = e.clientY;
            img.style.cursor = "grabbing";
        });

        window.addEventListener("mousemove", function (e) {
            if (!dragging) {
                return;
            }
            tx += (e.clientX - lastX);
            ty += (e.clientY - lastY);
            lastX = e.clientX;
            lastY = e.clientY;
            applyTransform();
        });

        window.addEventListener("mouseup", function () {
            if (!dragging) {
                return;
            }
            dragging = false;
            img.style.cursor = "grab";
        });

        // dblclick: otevřít / zavřít fullscreen
        bookWrap.addEventListener("dblclick", function (e) {
            e.preventDefault();
            lastDblX = e.clientX;
            openFullscreen();
        }, true);

        overlay.addEventListener("dblclick", function (e) {
            e.preventDefault();
            closeFullscreen();
        }, true);

        window.addEventListener("keydown", function (e) {
            if (e.key === "Escape") {
                closeFullscreen();
            }
        });

        window.addEventListener("resize", function () {
            if (overlay.classList.contains("open") && img.naturalWidth) {
                fitAndCenterDesktop();
            }
        });
    }

    global.setupFlipViewer = setupFlipViewer;
})(window);
