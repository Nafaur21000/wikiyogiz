document.addEventListener("DOMContentLoaded", function () {

    /* ---- OUVRIR / FERMER LE MENU GLOBAL ---- */
    const menuToggle  = document.getElementById('menuToggle');
    const sideMenu    = document.getElementById('sideMenu');
    const mainContent = document.getElementById('mainContent');

    if (menuToggle && sideMenu) {
        menuToggle.addEventListener('click', function () {
            sideMenu.classList.toggle('closed');
            menuToggle.classList.toggle('shifted');
            if (mainContent) mainContent.classList.toggle('expanded');
        });
    }

    /* ---- MENUS DÉFILANTS ---- */
    document.querySelectorAll('.toggle-submenu').forEach(function (toggle) {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            this.parentElement.classList.toggle('open');
        });
    });

    /* ---- CONFIRMATION AVANT SUPPRESSION ---- */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
});