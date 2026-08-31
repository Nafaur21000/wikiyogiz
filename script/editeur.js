/* ============================================================
   ÉDITEUR DE PAGE — LOGIQUE
   Utilisé uniquement par admin/editeur_page.php.

   Principe : la zone d'écriture est un <div contenteditable>.
   Le navigateur gère la saisie et la mise en forme ; on se
   contente de lui envoyer des commandes, puis de recopier le
   HTML obtenu dans un champ caché au moment d'enregistrer.
   ============================================================ */

(function () {

    var zone       = document.getElementById('zoneEdition');
    var zoneHtml   = document.getElementById('zoneHtml');
    var champCache = document.getElementById('contenuCache');
    var message    = document.getElementById('messageUpload');
    var compteur   = document.getElementById('compteurMots');

    // La page n'est pas l'éditeur : on ne fait rien.
    if (!zone) { return; }

    var modeHtml = false;   // true quand on affiche le code source

    /* --------------------------------------------------------
       OUTILS
       -------------------------------------------------------- */

    function info(texte, erreur) {
        message.textContent = texte;
        message.style.color = erreur ? '#a02a1c' : '#1e6b2c';
        if (texte) {
            setTimeout(function () { message.textContent = ''; }, 6000);
        }
    }

    /** Compte les mots pour le petit indicateur en bas. */
    function majCompteur() {
        var texte = zone.innerText.trim();
        var n = texte === '' ? 0 : texte.split(/\s+/).length;
        compteur.textContent = n + (n > 1 ? ' mots' : ' mot');
    }

    /**
     * Exécute une commande de mise en forme sur la sélection.
     * execCommand est ancien mais reste le seul moyen simple et
     * universellement supporté de piloter un contenteditable.
     */
    function commande(nom, valeur) {
        zone.focus();
        document.execCommand(nom, false, valeur || null);
        majCompteur();
    }

    /** Insère du HTML à l'endroit exact du curseur. */
    function insererHtml(html) {
        zone.focus();
        document.execCommand('insertHTML', false, html);
        majCompteur();
    }

    /* --------------------------------------------------------
       BARRE D'OUTILS
       -------------------------------------------------------- */

    // Boutons simples : gras, italique, listes, nettoyage...
    document.querySelectorAll('[data-cmd]').forEach(function (b) {
        b.addEventListener('click', function () {
            commande(this.getAttribute('data-cmd'));
        });
    });

    // Boutons de bloc : transforme le paragraphe courant en titre
    document.querySelectorAll('[data-bloc]').forEach(function (b) {
        b.addEventListener('click', function () {
            commande('formatBlock', '<' + this.getAttribute('data-bloc') + '>');
        });
    });

    /* --------------------------------------------------------
       LIEN VERS UNE PAGE WEB
       (l'insertion d'images et de fichiers par URL, elle, a été
       retirée : ces deux-là passent obligatoirement par l'envoi
       depuis l'ordinateur)
       -------------------------------------------------------- */
    document.getElementById('btnLien').addEventListener('click', function () {
        var url = prompt("Adresse du lien (https://...)");
        if (!url) { return; }
        if (!/^https?:\/\//i.test(url)) { url = 'https://' + url; }
        commande('createLink', url);
    });

    /* --------------------------------------------------------
       SECTIONS
       Une section est un bloc <section class="wiki-section">
       contenant un titre et du texte. Elle sert à découper une
       page en parties nettes, réutilisées par le CSS du site.
       -------------------------------------------------------- */

    document.getElementById('btnSectionAjout').addEventListener('click', function () {
        insererHtml(
            '<section class="wiki-section">' +
                '<h2>Titre de la section</h2>' +
                '<p>Écris ici le contenu de cette section.</p>' +
            '</section><p><br></p>'
        );
        info('Section ajoutée.');
    });

    document.getElementById('btnSectionSuppr').addEventListener('click', function () {
        var sel = window.getSelection();
        if (!sel.rangeCount) { info("Place d'abord le curseur dans une section.", true); return; }

        // On remonte l'arbre HTML depuis le curseur jusqu'à trouver
        // une balise <section>, sans jamais sortir de la zone.
        var noeud = sel.getRangeAt(0).startContainer;
        while (noeud && noeud !== zone) {
            if (noeud.nodeType === 1 && noeud.tagName === 'SECTION') {
                if (confirm('Supprimer cette section et tout son contenu ?')) {
                    noeud.remove();
                    majCompteur();
                    info('Section supprimée.');
                }
                return;
            }
            noeud = noeud.parentNode;
        }
        info("Le curseur n'est pas dans une section.", true);
    });

    /* --------------------------------------------------------
       ENVOI D'IMAGES ET DE FICHIERS DEPUIS L'ORDINATEUR
       Le bouton visible déclenche un <input type="file"> caché.
       Le fichier part vers admin/upload.php, qui répond en JSON.
       -------------------------------------------------------- */

    function envoyer(fichier, apres) {
        if (!fichier) { return; }

        var donnees = new FormData();
        donnees.append('fichier', fichier);

        info('Envoi en cours...');

        fetch('upload.php', { method: 'POST', body: donnees })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.uploaded) {
                    info(data.error ? data.error.message : "Échec de l'envoi.", true);
                    return;
                }
                apres(data);
                info('Fichier ajouté.');
            })
            .catch(function () {
                info("Le serveur n'a pas répondu correctement.", true);
            });
    }

    var inputImage = document.getElementById('inputImage');
    document.getElementById('btnImage').addEventListener('click', function () {
        inputImage.click();
    });
    inputImage.addEventListener('change', function () {
        envoyer(this.files[0], function (data) {
            insererHtml('<img src="' + data.url + '" alt=""><p><br></p>');
        });
        this.value = '';   // permet de renvoyer deux fois le même fichier
    });

    var inputFichier = document.getElementById('inputFichier');
    document.getElementById('btnFichier').addEventListener('click', function () {
        inputFichier.click();
    });
    inputFichier.addEventListener('change', function () {
        envoyer(this.files[0], function (data) {
            insererHtml('<p><a href="' + data.url + '" target="_blank">' + data.fileName + '</a></p>');
        });
        this.value = '';
    });

    /* --------------------------------------------------------
       GLISSER-DÉPOSER
       Déposer une image directement dans la zone l'envoie aussi.
       -------------------------------------------------------- */
    zone.addEventListener('dragover', function (e) {
        e.preventDefault();
        zone.classList.add('survol');
    });
    zone.addEventListener('dragleave', function () {
        zone.classList.remove('survol');
    });
    zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('survol');
        var f = e.dataTransfer.files[0];
        if (!f) { return; }
        envoyer(f, function (data) {
            var estImage = /\.(jpe?g|png|gif|webp|svg|avif)$/i.test(data.url);
            zone.insertAdjacentHTML('beforeend', estImage
                ? '<img src="' + data.url + '" alt="">'
                : '<p><a href="' + data.url + '" target="_blank">' + data.fileName + '</a></p>');
            majCompteur();
        });
    });

    /* --------------------------------------------------------
       COLLAGE PROPRE
       Coller depuis Word ou une page web amène des styles
       parasites. On ne garde que le texte.
       -------------------------------------------------------- */
    zone.addEventListener('paste', function (e) {
        e.preventDefault();
        var texte = (e.clipboardData || window.clipboardData).getData('text/plain');
        document.execCommand('insertText', false, texte);
    });

    /* --------------------------------------------------------
       VUE CODE HTML
       -------------------------------------------------------- */
    document.getElementById('btnHtml').addEventListener('click', function () {
        if (!modeHtml) {
            zoneHtml.value = zone.innerHTML;
            zone.style.display = 'none';
            zoneHtml.style.display = 'block';
            this.classList.add('actif');
        } else {
            zone.innerHTML = zoneHtml.value;
            zoneHtml.style.display = 'none';
            zone.style.display = 'block';
            this.classList.remove('actif');
            majCompteur();
        }
        modeHtml = !modeHtml;
    });

    /* --------------------------------------------------------
       ENREGISTREMENT
       Appelée par le onsubmit du formulaire : recopie le contenu
       dans le champ caché, qui est le seul réellement envoyé.
       -------------------------------------------------------- */
    window.preparerEnvoi = function () {
        champCache.value = modeHtml ? zoneHtml.value : zone.innerHTML;
        return true;
    };

    /* --------------------------------------------------------
       DÉMARRAGE
       -------------------------------------------------------- */
    zone.addEventListener('input', majCompteur);
    majCompteur();

    // Ctrl+S enregistre, comme dans un traitement de texte
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            document.getElementById('formPage').requestSubmit();
        }
    });

})();