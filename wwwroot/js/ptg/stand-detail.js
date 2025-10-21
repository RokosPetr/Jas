(function () {
    // ==== Konstanty / nastavení =============================================
    var STORAGE_KEY = 'activeTabId';

    // ==== Helpery pro práci s taby ==========================================
    function getLastTabId() {
        // přednostně hash v URL (když je), jinak localStorage
        if (location.hash && location.hash !== '#') return location.hash;
        try { return localStorage.getItem(STORAGE_KEY) || ''; } catch { return ''; }
    }

    function saveLastTabId(id) {
        if (!id || !id.startsWith('#')) return;
        try { localStorage.setItem(STORAGE_KEY, id); } catch { }
        // udrž hash bez vytváření nové historie
        history.replaceState(null, '', id);
    }

    // Zapne záložku jen přidáním tříd (bez odebírání odjinud)
    function activateTab(id) {
        if (!id) return;

        // umožni volat jak s "#paneId", tak s "paneId"
        if (id.charAt(0) !== '#') id = '#' + id;

        // nav-link (záložka)
        var link = document.querySelector(
            '[data-bs-toggle="tab"][data-bs-target="' + id + '"], ' +
            '[data-bs-toggle="tab"][href="' + id + '"]'
        );
        if (link) {
            link.classList.add('active');
        }

        // tab-pane (obsah)
        var pane = document.querySelector(id);
        if (pane) {
            pane.classList.add('show', 'active');
        }
    }

    // function activateTab(id) {
    //   if (!id) return;
    //   var trigger = document.querySelector(
    //     '[data-bs-toggle="tab"][data-bs-target="' + id + '"], ' +
    //     '[data-bs-toggle="tab"][href="' + id + '"]'
    //   );
    //   if (!trigger) return;

    //   if (window.bootstrap?.Tab) {
    //     new bootstrap.Tab(trigger).show();           // Bootstrap 5
    //   } else if (typeof $ !== 'undefined' && $.fn.tab) {
    //     $(trigger).tab('show');                      // Bootstrap 4 fallback
    //   }
    // }

    // ==== Inicializace lupy / zoomu na plát =================================
    function initPlateZoom() {
        const zoomEl = document.querySelector('.plate-zoom');
        if (!zoomEl) return;

        const imgUrl = zoomEl.getAttribute('data-img');
        const modalEl = document.getElementById('plateModal');
        const modalImg = document.getElementById('plateModalImg');

        // výchozí zobrazení
        zoomEl.style.backgroundImage = "url('" + imgUrl + "')";
        zoomEl.style.backgroundSize = 'contain';
        zoomEl.style.backgroundPosition = 'center';

        // 1) zjisti přirozené rozměry obrázku
        const img = new Image();
        let natW = 0, natH = 0;
        img.onload = function () {
            natW = this.naturalWidth;
            natH = this.naturalHeight;
        };
        img.src = imgUrl;

        // 2) lupa = 100% (tj. skutečné pixely obrázku)
        zoomEl.addEventListener('mousemove', function (e) {
            if (!natW || !natH) return; // ještě se nenačetly rozměry
            const rect = zoomEl.getBoundingClientRect();
            const xPct = ((e.clientX - rect.left) / rect.width) * 100;
            const yPct = ((e.clientY - rect.top) / rect.height) * 100;

            // nastavit na originální velikost (1:1) v pixelech
            zoomEl.style.backgroundSize = natW + 'px auto';
            zoomEl.style.backgroundPosition = xPct + '% ' + yPct + '%';
        });

        zoomEl.addEventListener('mouseleave', function () {
            zoomEl.style.backgroundSize = 'contain';
            zoomEl.style.backgroundPosition = 'center';
        });

        // klik => modal s originálem
        zoomEl.addEventListener('click', function () {
            if (!modalEl || !modalImg) return;
            modalImg.src = imgUrl || '';
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });
    }

    // ==== DOM READY ==========================================================
    document.addEventListener('DOMContentLoaded', function () {
        initPlateZoom();
        activateTab(getLastTabId());
    });

    // ==== Reakce na přepnutí tabu (uložení posledního) =======================
    document.addEventListener('shown.bs.tab', function (e) {
        var id = e.target.getAttribute('data-bs-target') || e.target.getAttribute('href');
        saveLastTabId(id);
    });

    // ==== Změna hash (zpět/vpřed) => aktivuj tab =============================
    window.addEventListener('hashchange', function () {
        activateTab(getLastTabId());
    });

    // ==== AJAX success (po přepnutí plata načteného přes partial) ===========
    // jQuery Unobtrusive Ajax: data-ajax-success="reactivateLastTab" je taky fajn;
    // tady chytáme globálně všechny ajaxSuccess.
    $(document).on('ajaxSuccess', function () {
        // znovu inicializuj zoom a reaktivuj poslední tab
        initPlateZoom();
        activateTab(getLastTabId());

        // zvýraznění thumbnailů + aktualizace URL s aktuálním plátem
        const meta = document.getElementById('plate-meta');
        if (!meta) return;

        const newPlate = parseInt(meta.dataset.plate || '1', 10);
        const newUrl = location.pathname + '?plate=' + newPlate;

        window.history.replaceState({}, '', newUrl);

        $('.thumb img')
            .removeClass('border-primary border-3')
            .addClass('border border-secondary');

        $('.thumb[data-plate="' + newPlate + '"] img')
            .removeClass('border-secondary')
            .addClass('border-primary border-3');
    });

    // ==== Lightbox: otevření konkrétního obrázku ve skupině ==================
    $(document).on('click', '.lightbox-open', function (e) {
        e.preventDefault();
        const imgUrl = this.getAttribute('data-img');
        const group = this.getAttribute('data-group');
        const selector = 'a[data-lightbox="' + group + '"][href="' + CSS.escape(imgUrl) + '"]';
        const a = document.querySelector(selector);
        if (a) a.click(); // otevře Lightbox a skupina bude bez duplicit
    });
})();
