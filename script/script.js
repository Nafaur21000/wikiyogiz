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

    /* ---- MENUS DÉFILANTS (sous-menus du site) ---- */
    document.querySelectorAll('.toggle-submenu').forEach(function (toggle) {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            this.parentElement.classList.toggle('open');
        });
    });

    /* ---- MENU DU BOUTON PROFIL ----
       Le rond n'est plus un simple lien : il ouvre un petit menu
       contenant "Panneau admin" et "Se déconnecter". */
    const profilBouton = document.getElementById('profilBouton');
    const profilMenu   = document.getElementById('profilMenu');

    if (profilBouton && profilMenu) {
        profilBouton.addEventListener('click', function (e) {
            e.stopPropagation();
            profilMenu.classList.toggle('ouvert');
        });

        // Un clic ailleurs sur la page referme le menu
        document.addEventListener('click', function () {
            profilMenu.classList.remove('ouvert');
        });

        // La touche Échap aussi
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { profilMenu.classList.remove('ouvert'); }
        });
    }

    /* ---- CONFIRMATION AVANT SUPPRESSION (back-office) ---- */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (this.classList.contains('inactif')) { e.preventDefault(); return; }
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    /* ---- FLÈCHES D'ORDRE DÉSACTIVÉES ----
       Une flèche en bout de liste ne doit rien déclencher. */
    document.querySelectorAll('.btn-fleche.inactif').forEach(function (el) {
        el.addEventListener('click', function (e) { e.preventDefault(); });
    });
});