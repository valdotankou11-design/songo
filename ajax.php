<?php
/**
 * SONGO v2 — ajax.php
 * Point d'entrée unique pour toutes les requêtes Ajax.
 * Stockage de l'état dans des fichiers JSON (dossier /parties/).
 *
 * Actions disponibles :
 *   creer_partie    → crée une nouvelle partie, retourne le code
 *   rejoindre_partie → j2 rejoint via code
 *   lister_parties  → retourne les parties en attente
 *   etat_partie     → retourne l'état courant (polling)
 *   jouer_coup      → joue un coup (idx de la case)
 *
 * TP Dr. MESSI — Version multijoueur distant (Ajax + PHP + JSON)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

/* ══ Constantes ══════════════════════════════════════════════ */
define('DOSSIER_PARTIES', __DIR__ . '/parties/');
define('NB_CASES',     7);
define('GRAINES_INIT', 5);
define('SEUIL_FIN',    10);   // < 10 graines sur plateau → fin
define('SEUIL_WIN',    40);   // ≥ 40 graines récoltées   → victoire
define('TTL_PARTIE',   7200); // 2h : purge les vieilles parties

/* ══ Routage ══════════════════════════════════════════════════ */
if (!is_dir(DOSSIER_PARTIES)) mkdir(DOSSIER_PARTIES, 0777, true);

$action = $_POST['action'] ?? '';

switch ($action) {
  case 'creer_partie':    repondre(creerPartie());    break;
  case 'rejoindre_partie':repondre(rejoindrePartie()); break;
  case 'lister_parties':  repondre(listerParties());  break;
  case 'etat_partie':     repondre(etatPartie());     break;
  case 'jouer_coup':      repondre(jouerCoup());      break;
  case 'envoyer_emoji':   repondre(envoyerEmoji());   break;
  default:                repondre(['succes' => false, 'message' => 'Action inconnue.']);
}

/* ══════════════════════════════════════════════════════════════
   ACTION : créer_partie
   ══════════════════════════════════════════════════════════════ */
function creerPartie() {
  $nom = trim($_POST['nom'] ?? '');
  if (!$nom) return ['succes' => false, 'message' => 'Pseudo requis.'];

  $code      = genererCode();
  $partie_id = uniqid('p_', true);

  $etat = etatInitial($partie_id, $nom, $code);
  sauvegarderPartie($partie_id, $etat);

  // Index : partie_id → code pour lister rapidement
  indexerPartie($partie_id, $code, $nom);

  return ['succes' => true, 'partie_id' => $partie_id, 'code' => $code];
}

/* ══════════════════════════════════════════════════════════════
   ACTION : rejoindre_partie
   ══════════════════════════════════════════════════════════════ */
function rejoindrePartie() {
  $nom  = trim($_POST['nom']  ?? '');
  $code = strtoupper(trim($_POST['code'] ?? ''));
  if (!$nom || !$code) return ['succes' => false, 'message' => 'Pseudo et code requis.'];

  $partie_id = trouverParCode($code);
  if (!$partie_id) return ['succes' => false, 'message' => 'Code introuvable ou partie déjà commencée.'];

  $etat = chargerPartie($partie_id);
  if (!$etat) return ['succes' => false, 'message' => 'Partie introuvable.'];
  if ($etat['statut'] !== 'attente') return ['succes' => false, 'message' => 'Cette partie a déjà commencé.'];

  $etat['joueur_nord'] = $nom;
  $etat['statut']      = 'en_cours';
  $etat['tour']        = 'sud';  // Sud (créateur) commence toujours
  $etat['ts_debut']    = time();

  sauvegarderPartie($partie_id, $etat);
  desindexerPartie($partie_id); // retirer de la liste d'attente

  return ['succes' => true, 'partie_id' => $partie_id];
}

/* ══════════════════════════════════════════════════════════════
   ACTION : lister_parties
   ══════════════════════════════════════════════════════════════ */
function listerParties() {
  purgerVieuxFichiers();
  $index = chargerIndex();
  $parties = [];
  foreach ($index as $id => $info) {
    if ($info['statut'] === 'attente') {
      $parties[] = [
        'partie_id'  => $id,
        'code'       => $info['code'],
        'joueur_sud' => $info['joueur_sud'],
        'ts'         => $info['ts'],
      ];
    }
  }
  // Trier par date (les plus récentes en haut)
  usort($parties, fn($a,$b) => $b['ts'] - $a['ts']);
  return ['succes' => true, 'parties' => $parties];
}

/* ══════════════════════════════════════════════════════════════
   ACTION : etat_partie  (polling)
   ══════════════════════════════════════════════════════════════ */
function etatPartie() {
  $partie_id = $_POST['partie_id'] ?? '';
  if (!$partie_id) return ['succes' => false, 'message' => 'partie_id manquant.'];

  $etat = chargerPartie($partie_id);
  if (!$etat) return ['succes' => false, 'message' => 'Partie introuvable.'];

  return ['succes' => true, 'etat' => $etat];
}

/* ══════════════════════════════════════════════════════════════
   ACTION : jouer_coup
   ══════════════════════════════════════════════════════════════ */
function jouerCoup() {
  $partie_id = $_POST['partie_id'] ?? '';
  $role      = $_POST['role']      ?? ''; // 'sud' ou 'nord'
  $idx       = (int)($_POST['idx'] ?? -1);

  if (!$partie_id || !$role || $idx < 0 || $idx >= NB_CASES)
    return ['succes' => false, 'message' => 'Paramètres invalides.'];

  $etat = chargerPartie($partie_id);
  if (!$etat)                          return ['succes' => false, 'message' => 'Partie introuvable.'];
  if ($etat['statut'] !== 'en_cours')  return ['succes' => false, 'message' => 'Partie non active.'];
  if ($etat['tour'] !== $role)         return ['succes' => false, 'message' => 'Ce n\'est pas votre tour.'];
  if ($etat['plateau'][$role][$idx] === 0) return ['succes' => false, 'message' => 'Case vide.'];

  // ── Exécuter la distribution ──────────────────────────────
  $graines = $etat['plateau'][$role][$idx];
  $etat['plateau'][$role][$idx] = 0;

  $adversaire = ($role === 'sud') ? 'nord' : 'sud';
  $sequence   = construireSequence($role, $idx, $graines, $etat['plateau']);

  // Appliquer la distribution sur le plateau
  foreach ($sequence as $pos) {
    $etat['plateau'][$pos['joueur']][$pos['idx']]++;
  }

  // ── Prises ────────────────────────────────────────────────
  $prises = effectuerPrises($role, $adversaire, $sequence, $etat);

  // Mettre à jour les scores
  if ($role === 'sud') $etat['score_sud']  += $prises;
  else                 $etat['score_nord'] += $prises;

  // ── Enregistrer le coup dans l'historique ─────────────────
  $etat['historique'][] = [
    'joueur'   => $role,
    'case'     => $idx + 1,
    'idx'      => $idx,
    'graines'  => $graines,
    'prises'   => $prises,
    'ts'       => time(),
    'sequence' => $sequence,
  ];

  // ── Vérifier fin de partie ─────────────────────────────────
  $fin = verifierFin($etat);
  if ($fin) {
    $etat['statut']   = 'termine';
    $etat['resultat'] = $fin;
  } else {
    // Passer le tour
    $etat['tour'] = $adversaire;

    // Vérifier solidarité
    $solida = verifierSolidarite($etat);
    if ($solida === 'impossible') {
      $etat['statut']   = 'termine';
      $etat['resultat'] = determinerVainqueur($etat, 'solidarite');
    } elseif ($solida === 'requise') {
      $etat['solidarite_requise'] = true;
    } else {
      $etat['solidarite_requise'] = false;
    }
  }

  sauvegarderPartie($partie_id, $etat);
  return ['succes' => true, 'etat' => $etat];
}

/* ══════════════════════════════════════════════════════════════
   LOGIQUE DE JEU (PHP)
   ══════════════════════════════════════════════════════════════ */

/**
 * Construit la séquence de cases à arroser.
 * Retourne un tableau de ['joueur' => ..., 'idx' => ...].
 */
function construireSequence($joueur, $depart, $nbGraines, $plateau) {
  $adversaire = ($joueur === 'sud') ? 'nord' : 'sud';
  $seq        = [];

  // Parcours anti-horaire vu du Sud :
  // Sud  : index croissant  (0→6), puis Nord index décroissant (6→0)
  // Nord : index décroissant (6→0), puis Sud index croissant  (0→6)
  $caseJ       = $depart;
  $dansAdverse = false;
  $distribues  = 0;
  $tourComplet = false;
  $sudJoue     = ($joueur === 'sud');

  while ($distribues < $nbGraines) {
    if (!$dansAdverse) {
      // Dans son propre camp
      $caseJ = $sudJoue ? $caseJ + 1 : $caseJ - 1;
      if ($sudJoue && $caseJ >= NB_CASES) {
        $dansAdverse = true;
        $caseJ = NB_CASES - 1; // entre chez le Nord par son index 6
      } elseif (!$sudJoue && $caseJ < 0) {
        $dansAdverse = true;
        $caseJ = 0; // entre chez le Sud par son index 0
      }
    } else {
      // Dans le camp adverse
      $caseJ = $sudJoue ? $caseJ - 1 : $caseJ + 1;
      if ($sudJoue && $caseJ < 0) {
        $dansAdverse = false;
        $tourComplet = true;
        $caseJ = 0;
      } elseif (!$sudJoue && $caseJ >= NB_CASES) {
        $dansAdverse = false;
        $tourComplet = true;
        $caseJ = NB_CASES - 1;
      }
    }

    $caseActuelle = $dansAdverse ? $adversaire : $joueur;
    $idxActuel    = $caseJ;

    // Skip la case de départ lors d'un tour complet
    if ($tourComplet && $caseActuelle === $joueur && $idxActuel === $depart) {
      continue;
    }

    $seq[] = ['joueur' => $caseActuelle, 'idx' => $idxActuel];
    $distribues++;
  }

  return $seq;
}

/**
 * Effectue les prises après distribution.
 * Modifie $etat['plateau'] et retourne le nombre de graines prises.
 */
function effectuerPrises($joueur, $adversaire, $sequence, &$etat) {
  if (empty($sequence)) return 0;

  $derniere = end($sequence);
  if ($derniere['joueur'] !== $adversaire) return 0;

  // Parcours en chaîne depuis la dernière case vers idx décroissant
  $totalPris    = 0;
  $casesPrisees = [];
  $idxChaine    = $derniere['idx'];
  $sudJoue      = ($joueur === 'sud');

  while ($idxChaine >= 0 && $idxChaine < NB_CASES) {
    $nb = $etat['plateau'][$adversaire][$idxChaine];

    // Condition de prise : 2 ≤ nb ≤ 4
    if ($nb >= 2 && $nb <= 4) {
      // Case d'indice 0 (case 1 adverse) : prise seulement en chaîne, pas si c'est la seule
      if ($idxChaine === $derniere['idx'] && ($idxChaine === NB_CASES - 1 || $idxChaine === 0)) {
        if (count($sequence) >= NB_CASES * 2) { // tour complet
          $casesPrisees[] = ['idx' => $idxChaine, 'nb' => 1];
          $totalPris += 1;
        }
        break;
      }
      $casesPrisees[] = ['idx' => $idxChaine, 'nb' => $nb];
      $totalPris      += $nb;
      $idxChaine = $sudJoue ? $idxChaine + 1 : $idxChaine - 1;
    } else {
      break;
    }
  }

  if ($totalPris === 0) return 0;

  // Vérifier qu'on ne vide pas complètement le camp adverse
  $copiePlateau = $etat['plateau'][$adversaire];
  $viderait = true;
  for ($i = 0; $i < NB_CASES; $i++) {
    $prise   = array_sum(array_map(fn($c) => $c['idx'] === $i ? $c['nb'] : 0, $casesPrisees));
    $restant = $copiePlateau[$i] - $prise;
    if ($restant > 0) { $viderait = false; break; }
  }
  if ($viderait) return 0; // Interdit : annuler toutes les prises

  // Appliquer les prises
  foreach ($casesPrisees as $c) {
    $etat['plateau'][$adversaire][$c['idx']] -= $c['nb'];
  }

  return $totalPris;
}

/**
 * Vérifie la solidarité.
 * Retourne 'ok', 'requise' ou 'impossible'.
 */
function verifierSolidarite($etat) {
  $joueur     = $etat['tour'];
  $adversaire = ($joueur === 'sud') ? 'nord' : 'sud';

  $campAdverseVide = (array_sum($etat['plateau'][$adversaire]) === 0);
  if (!$campAdverseVide) return 'ok';

  // Peut-il envoyer au moins 7 graines chez l'adversaire ?
  for ($i = 0; $i < NB_CASES; $i++) {
    $g = $etat['plateau'][$joueur][$i];
    if ($g === 0) continue;
    $seq   = construireSequence($joueur, $i, $g, $etat['plateau']);
    $chezAdv = count(array_filter($seq, fn($s) => $s['joueur'] === $adversaire));
    if ($chezAdv >= 7) return 'requise';
  }

  // Peut-il envoyer quelque chose ?
  for ($i = 0; $i < NB_CASES; $i++) {
    $g = $etat['plateau'][$joueur][$i];
    if ($g === 0) continue;
    $seq = construireSequence($joueur, $i, $g, $etat['plateau']);
    if (in_array($adversaire, array_column($seq, 'joueur'))) return 'requise';
  }

  return 'impossible';
}

/**
 * Vérifie les conditions de fin de partie.
 * Retourne null si pas terminé, ou le tableau resultat.
 */
function verifierFin(&$etat) {
  $totalPlateau = array_sum($etat['plateau']['nord']) + array_sum($etat['plateau']['sud']);

  if ($etat['score_sud'] >= SEUIL_WIN || $etat['score_nord'] >= SEUIL_WIN)
    return determinerVainqueur($etat, 'victoire');

  if ($totalPlateau < SEUIL_FIN) {
    // Récupérer les graines restantes
    $etat['score_nord'] += array_sum($etat['plateau']['nord']);
    $etat['score_sud']  += array_sum($etat['plateau']['sud']);
    $etat['plateau']['nord'] = array_fill(0, NB_CASES, 0);
    $etat['plateau']['sud']  = array_fill(0, NB_CASES, 0);
    return determinerVainqueur($etat, 'moins10');
  }

  return null;
}

function determinerVainqueur($etat, $raison) {
  $n = $etat['score_nord'];
  $s = $etat['score_sud'];
  if ($n > $s)     $vainqueur = 'nord';
  elseif ($s > $n) $vainqueur = 'sud';
  else             $vainqueur = 'egalite';

  return [
    'vainqueur'   => $vainqueur,
    'score_nord'  => $n,
    'score_sud'   => $s,
    'raison'      => $raison,
  ];
}


/* ══════════════════════════════════════════════════════════════
   ACTION : envoyer_emoji
   ══════════════════════════════════════════════════════════════ */
function envoyerEmoji() {
  $partie_id = $_POST['partie_id'] ?? '';
  $role      = $_POST['role']      ?? '';
  $emoji     = $_POST['emoji']     ?? '';

  // Liste blanche d'emojis autorisés
  $autorises = ['😂','😎','🔥','😤','🤔','😱','👏','😢','💪','🎉','😴','🤝'];
  if (!in_array($emoji, $autorises)) return ['succes' => false, 'message' => 'Emoji non autorisé.'];

  $etat = chargerPartie($partie_id);
  if (!$etat || $etat['statut'] === 'attente') return ['succes' => false, 'message' => 'Partie non active.'];

  // Garder seulement les 10 derniers emojis
  $etat['emojis'][] = ['role' => $role, 'emoji' => $emoji, 'ts' => time()];
  if (count($etat['emojis']) > 10) $etat['emojis'] = array_slice($etat['emojis'], -10);

  sauvegarderPartie($partie_id, $etat);
  return ['succes' => true, 'emojis' => $etat['emojis']];
}

/* ══════════════════════════════════════════════════════════════
   GESTION DES FICHIERS JSON
   ══════════════════════════════════════════════════════════════ */

function etatInitial($partie_id, $nomSud, $code) {
  return [
    'partie_id'   => $partie_id,
    'code'        => $code,
    'statut'      => 'attente',  // attente | en_cours | termine
    'joueur_sud'  => $nomSud,
    'joueur_nord' => null,
    'tour'        => 'sud',
    'plateau'     => [
      'nord' => array_fill(0, NB_CASES, GRAINES_INIT),
      'sud'  => array_fill(0, NB_CASES, GRAINES_INIT),
    ],
    'score_nord'          => 0,
    'score_sud'           => 0,
    'solidarite_requise'  => false,
    'resultat'            => null,
    'historique'          => [],
    'emojis'              => [],   // [{role, emoji, ts}] — derniers emojis envoyés
    'ts_creation'         => time(),
    'ts_debut'            => null,
    'ts_maj'              => time(),
  ];
}

function cheminPartie($partie_id) {
  // Sécuriser l'ID pour éviter les traversées de chemin
  $safe = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $partie_id);
  return DOSSIER_PARTIES . $safe . '.json';
}

function chargerPartie($partie_id) {
  $f = cheminPartie($partie_id);
  if (!file_exists($f)) return null;
  return json_decode(file_get_contents($f), true);
}

function sauvegarderPartie($partie_id, $etat) {
  $etat['ts_maj'] = time();
  file_put_contents(cheminPartie($partie_id), json_encode($etat, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/* ── Index pour la liste des parties en attente ── */
function cheminIndex() { return DOSSIER_PARTIES . '_index.json'; }

function chargerIndex() {
  $f = cheminIndex();
  if (!file_exists($f)) return [];
  return json_decode(file_get_contents($f), true) ?: [];
}

function sauvegarderIndex($index) {
  file_put_contents(cheminIndex(), json_encode($index, JSON_PRETTY_PRINT), LOCK_EX);
}

function indexerPartie($partie_id, $code, $joueurSud) {
  $index = chargerIndex();
  $index[$partie_id] = ['code' => $code, 'joueur_sud' => $joueurSud, 'statut' => 'attente', 'ts' => time()];
  sauvegarderIndex($index);
}

function desindexerPartie($partie_id) {
  $index = chargerIndex();
  unset($index[$partie_id]);
  sauvegarderIndex($index);
}

function trouverParCode($code) {
  $index = chargerIndex();
  foreach ($index as $id => $info) {
    if ($info['code'] === $code && $info['statut'] === 'attente') return $id;
  }
  return null;
}

function purgerVieuxFichiers() {
  $index = chargerIndex();
  $maintenant = time();
  foreach ($index as $id => $info) {
    if ($maintenant - $info['ts'] > TTL_PARTIE) unset($index[$id]);
  }
  sauvegarderIndex($index);
}

function genererCode() {
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  $code  = '';
  for ($i = 0; $i < 4; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
  return $code;
}

/* ══════════════════════════════════════════════════════════════
   UTILITAIRES
   ══════════════════════════════════════════════════════════════ */
function repondre($data) {
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
