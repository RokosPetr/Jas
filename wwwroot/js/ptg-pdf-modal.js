(function () {
    const modalElement = document.getElementById("pdfOptionsModal");
    if (!modalElement) {
        return; // stránka modal nepoužívá
    }

    const pdfItemIdInput = document.getElementById("pdfItemId");
    const pdfPrintQr = document.getElementById("pdfPrintQr");
    const pdfPrintPictures = document.getElementById("pdfPrintPictures");
    const pdfPrintPicturesRow = document.getElementById("pdfPrintPicturesRow");
    const confirmButton = document.getElementById("pdfOptionsConfirm");

    const pdfModal = new bootstrap.Modal(modalElement);

    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".open-pdf-dialog");
        if (!btn) return;

        const id = btn.getAttribute("data-id");
        const hasPictures = btn.getAttribute("data-has-pictures") === "true";

        pdfItemIdInput.value = id;
        pdfPrintQr.checked = false;
        pdfPrintPictures.checked = false;

        pdfPrintPicturesRow.style.display = hasPictures ? "block" : "none";

        pdfModal.show();
    });

    confirmButton.addEventListener("click", function () {
        const id = pdfItemIdInput.value;
        if (!id) {
            pdfModal.hide();
            return;
        }

        const params = new URLSearchParams();
        params.append("handler", "Pdf");

        if (pdfPrintQr.checked) {
            params.append("PrintQr", "true");
        }
        if (pdfPrintPicturesRow.style.display !== "none" && pdfPrintPictures.checked) {
            params.append("PrintPictures", "true");
        }

        const url = `/ptg/print/${id}?` + params.toString();

        pdfModal.hide();
        window.location.href = url;
    });
})();