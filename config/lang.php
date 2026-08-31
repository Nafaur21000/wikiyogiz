<?php
// ============================================================
//  LANGUES — FRANÇAIS / ANGLAIS
//
//  Comment ça marche :
//    1. Un lien ?lang=en ou ?lang=fr change la langue.
//    2. Le choix est rangé dans la session ET dans un cookie
//       d'un an. Le cookie permet de retrouver sa langue même
//       avant de se connecter, sur la page de connexion.
//    3. La fonction t('cle') renvoie le texte dans la langue
//       en cours. Tout le site passe par elle.
//
//  Ce fichier doit être inclus AVANT tout affichage.
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/traductions.php';

define('LANGUES_DISPO', ['fr', 'en']);
define('LANGUE_DEFAUT', 'fr');

/* ------------------------------------------------------------
   DÉTERMINATION DE LA LANGUE
   ------------------------------------------------------------ */

// 1. Changement demandé par un lien ?lang=xx
if (isset($_GET['lang']) && in_array($_GET['lang'], LANGUES_DISPO, true)) {
    $_SESSION['langue'] = $_GET['lang'];
    // Cookie d'un an, pour retrouver son choix à la prochaine visite
    setcookie('wy_langue', $_GET['lang'], time() + 31536000, '/');
}
// 2. Sinon, la langue déjà en session
elseif (!isset($_SESSION['langue'])) {
    // 3. Sinon le cookie, 4. sinon le français
    if (isset($_COOKIE['wy_langue']) && in_array($_COOKIE['wy_langue'], LANGUES_DISPO, true)) {
        $_SESSION['langue'] = $_COOKIE['wy_langue'];
    } else {
        $_SESSION['langue'] = LANGUE_DEFAUT;
    }
}

/** Code de la langue en cours : 'fr' ou 'en'. */
function langue() {
    return $_SESSION['langue'] ?? LANGUE_DEFAUT;
}

/** L'autre langue, celle vers laquelle on peut basculer. */
function autre_langue() {
    return langue() === 'fr' ? 'en' : 'fr';
}

/** Nom lisible d'une langue, écrit dans sa propre langue. */
function nom_langue($code) {
    return $code === 'en' ? 'English' : 'Français';
}

/* ------------------------------------------------------------
   TRADUCTION
   ------------------------------------------------------------ */

/**
 * Renvoie le texte correspondant à une clé.
 *
 * $remplacements permet d'insérer des valeurs :
 *     t('tdb.pages')
 *
 * Si une clé manque en anglais, on retombe sur le français
 * plutôt que d'afficher une page trouée.
 */
function t($cle, $remplacements = []) {
    global $TRADUCTIONS;

    $lg = langue();
    $texte = $TRADUCTIONS[$lg][$cle]
          ?? $TRADUCTIONS[LANGUE_DEFAUT][$cle]
          ?? $cle;   // dernier recours : la clé elle-même, repérable en test

    if ($remplacements) {
        $texte = strtr($texte, $remplacements);
    }
    return $texte;
}

/** Raccourci : traduit ET échappe le HTML. À utiliser dans les vues. */
function te($cle, $remplacements = []) {
    return htmlspecialchars(t($cle, $remplacements), ENT_QUOTES, 'UTF-8');
}

/**
 * Fabrique l'adresse de la page en cours dans une autre langue.
 * On repart de l'URL actuelle et on y remplace (ou ajoute) ?lang=.
 * Changer de langue ne fait donc pas perdre la page consultée.
 */
function url_langue($code) {
    $chemin = strtok($_SERVER['REQUEST_URI'], '?');

    $params = $_GET;
    $params['lang'] = $code;

    return $chemin . '?' . http_build_query($params);
}

/* ------------------------------------------------------------
   CONTENU BILINGUE DES PAGES
   ------------------------------------------------------------ */

/**
 * Renvoie un champ de page dans la langue en cours.
 *
 *   champ_page($page, 'titre')    -> titre ou titre_en
 *   champ_page($page, 'contenu')  -> contenu ou contenu_en
 *
 * Repli volontaire : si la version anglaise est vide, on affiche
 * le français. Mieux vaut un texte dans la mauvaise langue qu'une
 * page blanche, et ça permet de traduire progressivement.
 */
function champ_page($page, $champ) {
    if (langue() === 'en') {
        $valeur = $page[$champ . '_en'] ?? '';
        if (trim((string)$valeur) !== '') {
            return $valeur;
        }
    }
    return $page[$champ] ?? '';
}