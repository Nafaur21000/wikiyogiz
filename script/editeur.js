/* ============================================================
   ÉDITEUR DE PAGE — LOGIQUE
   Utilisé uniquement par admin/editeur_page.php.

   Deux zones d'écriture, une par langue. Une seule barre
   d'outils : elle agit toujours sur la zone visible.

   Les textes affichés viennent de window.WY_TEXTES, rempli
   côté PHP à partir du dictionnaire : l'éditeur suit donc la
   langue choisie par l'utilisateur.
   ============================================================ */

(function () {

    var T = window.WY_TEXTES || {};
    function tr(cle) { return T[cle] || cle; }

    var zoneFr     = document.getElementById('zoneEdition');
    var zoneEn     = document.getElementById('zoneEditionEn');
    var zoneHtml   = document.getElementById('zoneHtml');
    var cacheFr    = document.getElementById('contenuCache');
    var cacheEn    = document.getElementById('contenuCacheEn');
    var message    = document.getElementById('messageUpload');
    var compteur   = document.getElementById('compteurMots');

    // La page n'est pas l'éditeur : on ne fait rien.
    if (!zoneFr) { return; }

    var langueActive = 'fr';     // onglet affiché
    var modeHtml     = false;    // true quand on montre le code source

    /** La zone d'écriture actuellement visible. */
    function zone() {
        return langueActive === 'en' ? zoneEn : zoneFr;
    }

    /* --------------------------------------------------------
       ONGLETS DE LANGUE
       Ils montrent ou cachent les blocs marqués data-bloc.
       Les deux versions restent dans le formulaire et partent
       ensemble à l'enregistrement.
       -------------------------------------------------------- */
    document.querySelectorAll('.onglet').forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            // Si on était en vue code, on revient d'abord au visuel
            if (modeHtml) { document.getElementById('btnHtml').click(); }

            langueActive = this.getAttribute('data-onglet');

            document.querySelectorAll('.onglet').forEach(function (b) {
                b.classList.toggle('actif', b === bouton);
            });

            document.querySelectorAll('.bloc-langue').forEach(function (bloc) {
                bloc.style.display = (bloc.getAttribute('data-bloc') === langueActive) ? '' : 'none';
            });

            majCompteur();
        });
    });

    /* --------------------------------------------------------
       OUTILS
       -------------------------------------------------------- */

    function info(texte, erreur) {
        message.textContent = texte;
        message.style.color = erreur ? '#b25579' : '#1e6b2c';
        if (texte) {
            setTimeout(function () { message.textContent = ''; }, 6000);
        }
    }

    function majCompteur() {
        var texte = zone().innerText.trim();
        var n = texte === '' ? 0 : texte.split(/\s+/).length;
        compteur.textContent = n + ' ' + (n > 1 ? tr('mots') : tr('mot'));
    }

    /**
     * Commande de mise en forme sur la sélection de la zone visible.
     * execCommand est ancien mais reste le moyen le plus simple et
     * le plus largement supporté de piloter un contenteditable.
     */
    function commande(nom, valeur) {
        zone().focus();
        document.execCommand(nom, false, valeur || null);
        majCompteur();
    }

    function insererHtml(html) {
        zone().focus();
        document.execCommand('insertHTML', false, html);
        majCompteur();
    }

    /* --------------------------------------------------------
       BARRE D'OUTILS
       -------------------------------------------------------- */

    document.querySelectorAll('[data-cmd]').forEach(function (b) {
        b.addEventListener('click', function () {
            commande(this.getAttribute('data-cmd'));
        });
    });

    document.querySelectorAll('[data-bloc-html]').forEach(function (b) {
        b.addEventListener('click', function () {
            commande('formatBlock', '<' + this.getAttribute('data-bloc-html') + '>');
        });
    });

    document.getElementById('btnLien').addEventListener('click', function () {
        var url = prompt(tr('lien_prompt'));
        if (!url) { return; }
        if (!/^https?:\/\//i.test(url)) { url = 'https://' + url; }
        commande('createLink', url);
    });

    /* --------------------------------------------------------
       SECTIONS
       Un bloc <section class="wiki-section"> avec un titre et
       du texte, mis en forme par le CSS du site.
       -------------------------------------------------------- */

    document.getElementById('btnSectionAjout').addEventListener('click', function () {
        insererHtml(
            '<section class="wiki-section">' +
                '<h2>' + tr('titre_section') + '</h2>' +
                '<p>' + tr('contenu_section') + '</p>' +
            '</section><p><br></p>'
        );
        info(tr('section_ajoutee'));
    });

    document.getElementById('btnSectionSuppr').addEventListener('click', function () {
        var sel = window.getSelection();
        if (!sel.rangeCount) { info(tr('section_curseur'), true); return; }

        // On remonte l'arbre HTML depuis le curseur jusqu'à trouver
        // une balise <section>, sans jamais sortir de la zone.
        var noeud = sel.getRangeAt(0).startContainer;
        var z = zone();
        while (noeud && noeud !== z) {
            if (noeud.nodeType === 1 && noeud.tagName === 'SECTION') {
                if (confirm(tr('section_confirm'))) {
                    noeud.remove();
                    majCompteur();
                    info(tr('section_supprimee'));
                }
                return;
            }
            noeud = noeud.parentNode;
        }
        info(tr('section_dehors'), true);
    });

    /* --------------------------------------------------------
       ENVOI D'IMAGES ET DE FICHIERS DEPUIS L'ORDINATEUR
       -------------------------------------------------------- */

    function envoyer(fichier, apres) {
        if (!fichier) { return; }

        var donnees = new FormData();
        donnees.append('fichier', fichier);

        info(tr('envoi'));

        fetch('upload.php', { method: 'POST', body: donnees })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.uploaded) {
                    info(data.error ? data.error.message : tr('echec_envoi'), true);
                    return;
                }
                apres(data);
                info(tr('fichier_ajoute'));
            })
            .catch(function () { info(tr('pas_de_reponse'), true); });
    }

    var inputImage = document.getElementById('inputImage');
    document.getElementById('btnImage').addEventListener('click', function () { inputImage.click(); });
    inputImage.addEventListener('change', function () {
        envoyer(this.files[0], function (data) {
            insererHtml('<img src="' + data.url + '" alt=""><p><br></p>');
        });
        this.value = '';
    });

    var inputFichier = document.getElementById('inputFichier');
    document.getElementById('btnFichier').addEventListener('click', function () { inputFichier.click(); });
    inputFichier.addEventListener('change', function () {
        envoyer(this.files[0], function (data) {
            insererHtml('<p><a href="' + data.url + '" target="_blank">' + data.fileName + '</a></p>');
        });
        this.value = '';
    });

    /* --------------------------------------------------------
       REDIMENSIONNEMENT DES IMAGES À LA SOURIS
       Clic sur une image : huit poignées apparaissent, quatre
       coins et quatre côtés. On attrape, on tire, l'image suit.
       Les poignées vivent dans <body> : jamais dans le contenu
       enregistré.
       -------------------------------------------------------- */

    var imageActive = null;

    var POIGNEES = [
        { px: 0,   py: 0,   dirX: -1, dirY:  0, curseur: 'nwse-resize' },
        { px: 0.5, py: 0,   dirX:  0, dirY: -1, curseur: 'ns-resize'   },
        { px: 1,   py: 0,   dirX:  1, dirY:  0, curseur: 'nesw-resize' },
        { px: 1,   py: 0.5, dirX:  1, dirY:  0, curseur: 'ew-resize'   },
        { px: 1,   py: 1,   dirX:  1, dirY:  0, curseur: 'nwse-resize' },
        { px: 0.5, py: 1,   dirX:  0, dirY:  1, curseur: 'ns-resize'   },
        { px: 0,   py: 1,   dirX: -1, dirY:  0, curseur: 'nesw-resize' },
        { px: 0,   py: 0.5, dirX: -1, dirY:  0, curseur: 'ew-resize'   }
    ];

    var carres = POIGNEES.map(function (p) {
        var el = document.createElement('div');
        el.className = 'poignee-image';
        el.style.cursor = p.curseur;
        el.style.display = 'none';
        el.__conf = p;
        document.body.appendChild(el);
        return el;
    });

    function placerPoignees() {
        if (!imageActive) { return; }
        var r = imageActive.getBoundingClientRect();
        carres.forEach(function (el) {
            var c = el.__conf;
            el.style.display = 'block';
            el.style.left = (r.left + r.width  * c.px) + 'px';
            el.style.top  = (r.top  + r.height * c.py) + 'px';
        });
    }

    function cacherPoignees() {
        carres.forEach(function (el) { el.style.display = 'none'; });
    }

    function selectionner(img) {
        if (imageActive) { imageActive.classList.remove('image-selectionnee'); }
        imageActive = img;
        img.classList.add('image-selectionnee');
        placerPoignees();
    }

    function deselectionner() {
        if (imageActive) { imageActive.classList.remove('image-selectionnee'); }
        imageActive = null;
        cacherPoignees();
    }

    // Les deux zones réagissent de la même façon
    [zoneFr, zoneEn].forEach(function (z) {
        if (!z) { return; }
        z.addEventListener('click', function (e) {
            if (e.target && e.target.tagName === 'IMG') {
                selectionner(e.target);
            } else {
                deselectionner();
            }
        });
        z.addEventListener('input', majCompteur);

        // Glisser-déposer d'un fichier directement dans la zone
        z.addEventListener('dragover', function (e) { e.preventDefault(); z.classList.add('survol'); });
        z.addEventListener('dragleave', function () { z.classList.remove('survol'); });
        z.addEventListener('drop', function (e) {
            e.preventDefault();
            z.classList.remove('survol');
            var f = e.dataTransfer.files[0];
            if (!f) { return; }
            envoyer(f, function (data) {
                var estImage = /\.(jpe?g|png|gif|webp|svg|avif)$/i.test(data.url);
                z.insertAdjacentHTML('beforeend', estImage
                    ? '<img src="' + data.url + '" alt="">'
                    : '<p><a href="' + data.url + '" target="_blank">' + data.fileName + '</a></p>');
                majCompteur();
            });
        });

        // Coller depuis Word amène des styles parasites : on ne
        // garde que le texte.
        z.addEventListener('paste', function (e) {
            e.preventDefault();
            var texte = (e.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, texte);
        });

        // Une image fraîchement envoyée n'a pas encore ses dimensions
        // au moment de l'insertion : on replace les poignées ensuite.
        z.addEventListener('load', function (e) {
            if (e.target.tagName === 'IMG' && e.target === imageActive) { placerPoignees(); }
        }, true);
    });

    document.addEventListener('mousedown', function (e) {
        if (!imageActive) { return; }
        if (e.target.classList && e.target.classList.contains('poignee-image')) { return; }
        if (e.target === imageActive) { return; }
        if (!zone().contains(e.target)) { deselectionner(); }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { deselectionner(); }
    });

    window.addEventListener('scroll', placerPoignees, true);
    window.addEventListener('resize', placerPoignees);

    /* ---- LE GLISSEMENT ---- */

    var poigneeTiree = null;
    var departX = 0, departY = 0, largeurDepart = 0, ratio = 1;

    function commencer(el, x, y) {
        if (!imageActive) { return; }
        var r = imageActive.getBoundingClientRect();
        poigneeTiree  = el;
        departX       = x;
        departY       = y;
        largeurDepart = r.width;
        // Rapport largeur/hauteur : traduit un mouvement vertical
        // en variation de largeur, pour garder les proportions.
        ratio = r.height > 0 ? (r.width / r.height) : 1;
        document.body.classList.add('redimensionnement');
    }

    function deplacer(x, y) {
        if (!poigneeTiree || !imageActive) { return; }
        var c = poigneeTiree.__conf;

        var delta = c.dirX !== 0
            ? (x - departX) * c.dirX
            : (y - departY) * c.dirY * ratio;

        var nouvelle = largeurDepart + delta;
        var maxi = zone().clientWidth - 60;
        if (nouvelle < 40)   { nouvelle = 40; }
        if (nouvelle > maxi) { nouvelle = maxi; }

        imageActive.style.width    = Math.round(nouvelle) + 'px';
        imageActive.style.height   = 'auto';
        imageActive.style.maxWidth = '100%';

        placerPoignees();
    }

    function terminer() {
        if (!poigneeTiree) { return; }
        poigneeTiree = null;
        document.body.classList.remove('redimensionnement');
        majCompteur();
    }

    carres.forEach(function (el) {
        el.addEventListener('mousedown', function (e) {
            e.preventDefault();
            e.stopPropagation();
            commencer(el, e.clientX, e.clientY);
        });
        el.addEventListener('touchstart', function (e) {
            e.preventDefault();
            commencer(el, e.touches[0].clientX, e.touches[0].clientY);
        }, { passive: false });
    });

    document.addEventListener('mousemove', function (e) { deplacer(e.clientX, e.clientY); });
    document.addEventListener('mouseup', terminer);

    document.addEventListener('touchmove', function (e) {
        if (!poigneeTiree) { return; }
        e.preventDefault();
        deplacer(e.touches[0].clientX, e.touches[0].clientY);
    }, { passive: false });
    document.addEventListener('touchend', terminer);

    /* --------------------------------------------------------
       VUE CODE HTML
       Elle porte sur la langue affichée au moment du clic.
       -------------------------------------------------------- */
    document.getElementById('btnHtml').addEventListener('click', function () {
        var z = zone();
        if (!modeHtml) {
            zoneHtml.value = z.innerHTML;
            z.style.display = 'none';
            zoneHtml.style.display = 'block';
            this.classList.add('actif');
        } else {
            z.innerHTML = zoneHtml.value;
            zoneHtml.style.display = 'none';
            z.style.display = '';
            this.classList.remove('actif');
            majCompteur();
        }
        modeHtml = !modeHtml;
    });

    /* --------------------------------------------------------
       ENREGISTREMENT
       Les DEUX langues sont recopiées, quelle que soit celle
       affichée : on ne perd jamais la version cachée.
       -------------------------------------------------------- */
    window.preparerEnvoi = function () {
        // Si on était en vue code, on la referme pour récupérer
        // le HTML réel de la zone.
        if (modeHtml) { document.getElementById('btnHtml').click(); }

        deselectionner();   // le contour de sélection ne part pas en base

        cacheFr.value = zoneFr.innerHTML;
        cacheEn.value = zoneEn ? zoneEn.innerHTML : '';
        return true;
    };

    /* --------------------------------------------------------
       DÉMARRAGE
       -------------------------------------------------------- */
    majCompteur();

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            document.getElementById('formPage').requestSubmit();
        }
    });

})();