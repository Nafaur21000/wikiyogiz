document.addEventListener("DOMContentLoaded", function() {
    const menuToggle = document.getElementById('menuToggle');
    const sideMenu = document.getElementById('sideMenu');
    const mainContent = document.getElementById('mainContent');
    
    // Gère TOUS les sous-menus de la page
    const toggleSubmenus = document.querySelectorAll('.toggle-submenu');

    if (menuToggle && sideMenu) {
        menuToggle.addEventListener('click', function() {
            sideMenu.classList.toggle('closed');
            menuToggle.classList.toggle('shifted');
            if (mainContent) mainContent.classList.toggle('expanded');
        });
    }

    // Boucle pour faire fonctionner toutes les flèches déroulantes
    toggleSubmenus.forEach(function(toggle) {
        toggle.addEventListener('click', function(event) {
            event.preventDefault(); 
            // Ouvre ou ferme le menu-group dans lequel on a cliqué
            this.parentElement.classList.toggle('open');
        });
    });
});