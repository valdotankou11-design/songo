<?php
/**
 * SONGO v2 — jeu.php
 * Interface de jeu pour les deux joueurs (chacun sur sa machine).
 * TP Dr. MESSI
 */
$partie_id = htmlspecialchars($_GET['partie_id'] ?? '');
$role      = htmlspecialchars($_GET['role']      ?? '');  // 'sud' ou 'nord'
$nom       = htmlspecialchars($_GET['nom']       ?? 'Joueur');

if (!$partie_id || !in_array($role, ['sud', 'nord'])) {
  header('Location: index.php');
  exit;
}
$adversaire = ($role === 'sud') ? 'nord' : 'sud';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Songo — <?= $nom ?></title>
  <!-- ══ PWA ══════════════════════════════════════════ -->
  <meta name="application-name" content="Songo"/>
  <meta name="mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
  <meta name="apple-mobile-web-app-title" content="Songo"/>
  <meta name="theme-color" content="#E8B84B"/>
  <meta name="msapplication-TileColor" content="#2C1A0E"/>
  <meta name="msapplication-TileImage" content="icons/icon-144.png"/>
  <link rel="manifest" href="/manifest.json"/>
  <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192.png"/>
  <link rel="apple-touch-icon" sizes="152x152" href="/icons/icon-152.png"/>
  <link rel="icon" type="image/png" sizes="32x32"  href="/icons/icon-96.png"/>
  <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png"/>
  <!-- ══ Fin PWA ═══════════════════════════════════════ -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bois-fonce:  #2C1A0E;
      --bois-moyen:  #5C3317;
      --bois-clair:  #8B5E3C;
      --case-hover:  #E8B84B;
      --or:          #E8B84B;
      --texte:       #F5E6C8;
      --texte-sombre:#2C1A0E;
      --rouge:       #C0392B;
      --vert:        #27AE60;
      --bleu:        #2980B9;
    }

    body {
      background: var(--bois-fonce);
      font-family: 'Inter', sans-serif;
      color: var(--texte);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 16px 10px 30px;
      background-image:
        radial-gradient(ellipse at 20% 0%,   rgba(139,94,60,0.25) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 100%, rgba(92,51,23,0.40)  0%, transparent 60%);
    }

    header { text-align: center; margin-bottom: 16px; }
    header h1 {
      font-family: 'Playfair Display', serif;
      font-size: clamp(1.8rem, 5vw, 2.8rem);
      font-weight: 900;
      letter-spacing: 0.08em;
      color: var(--or);
    }
    .badge-role {
      display: inline-block;
      margin-top: 5px;
      font-size: 0.72rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      padding: 3px 12px;
      border-radius: 20px;
      border: 1px solid rgba(232,184,75,0.35);
      color: rgba(245,230,200,0.65);
    }

    /* ── STATUT ── */
    #statut-barre {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-size: 0.82rem;
      font-weight: 600;
      letter-spacing: 0.04em;
      margin-bottom: 14px;
      min-height: 26px;
      padding: 5px 16px;
      border-radius: 20px;
      background: rgba(0,0,0,0.25);
      border: 1px solid rgba(255,255,255,0.07);
      max-width: 420px;
      width: 100%;
      text-align: center;
    }
    .dot-online {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: var(--vert);
      box-shadow: 0 0 6px rgba(39,174,96,0.7);
      flex-shrink: 0;
      animation: pulse-dot 1.8s ease-in-out infinite;
    }
    @keyframes pulse-dot {
      0%,100% { opacity: 1; } 50% { opacity: 0.4; }
    }

    /* ── SCORES ── */
    .scores {
      display: flex;
      justify-content: center;
      gap: 24px;
      margin-bottom: 14px;
      flex-wrap: wrap;
    }
    .score-card {
      background: rgba(92,51,23,0.5);
      border: 1.5px solid rgba(232,184,75,0.2);
      border-radius: 12px;
      padding: 8px 22px;
      text-align: center;
      min-width: 120px;
      transition: border-color 0.3s, background 0.3s;
    }
    .score-card.actif {
      border-color: var(--or);
      background: rgba(232,184,75,0.1);
      box-shadow: 0 0 16px rgba(232,184,75,0.15);
    }
    .score-card .nom { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.55; margin-bottom: 2px; }
    .score-card .pts { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 700; color: var(--or); }
    .score-card .moi-badge {
      font-size: 0.6rem;
      padding: 1px 7px;
      border-radius: 10px;
      background: rgba(232,184,75,0.15);
      border: 1px solid rgba(232,184,75,0.3);
      color: var(--or);
      display: inline-block;
      margin-top: 2px;
    }

    /* ── TABLIER ── */
    .tablier-wrapper {
      background: linear-gradient(160deg, #6B3A1F 0%, #3E200C 60%, #2C1A0E 100%);
      border: 3px solid var(--bois-clair);
      border-radius: 22px;
      padding: 18px 14px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.6), inset 0 1px 0 rgba(255,255,255,0.06);
      max-width: 700px;
      width: 100%;
    }

    .rangee-label {
      font-size: 0.65rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      opacity: 0.4;
      text-align: center;
      margin-bottom: 5px;
    }
    .rangee-label.moi { opacity: 0.8; color: var(--or); }

    .rangee {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 7px;
      margin-bottom: 8px;
    }
    .nums-rangee {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 7px;
      margin-bottom: 3px;
    }
    .num-case { text-align: center; font-size: 0.58rem; opacity: 0.3; }

    .separateur {
      height: 2px;
      background: linear-gradient(90deg, transparent, var(--bois-clair), transparent);
      margin: 6px 0 12px;
      opacity: 0.45;
    }

    /* ── CASE ── */
    .case {
      aspect-ratio: 1;
      background: radial-gradient(circle at 35% 35%, #D4A96A, #A06830);
      border-radius: 50%;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: center;
      gap: 2px;
      padding: 5px;
      cursor: pointer;
      border: 2.5px solid transparent;
      transition: transform 0.15s, border-color 0.2s, box-shadow 0.2s;
      position: relative;
      min-width: 0;
      box-shadow: inset 0 2px 6px rgba(0,0,0,0.35), 0 2px 4px rgba(0,0,0,0.3);
    }
    .case:hover:not(.disabled):not(.inactive) {
      transform: scale(1.07);
      border-color: var(--case-hover);
      box-shadow: 0 0 14px rgba(232,184,75,0.4), inset 0 2px 6px rgba(0,0,0,0.35);
    }
    .case.disabled   { opacity: 0.4; cursor: not-allowed; }
    .case.inactive   { cursor: default; }
    .case.selectionnee {
      border-color: var(--or);
      box-shadow: 0 0 20px rgba(245,200,66,0.6), inset 0 2px 6px rgba(0,0,0,0.35);
      transform: scale(1.08);
    }
    .case.derniere { border-color: var(--vert); box-shadow: 0 0 14px rgba(39,174,96,0.5); }
    .case.prise    { border-color: var(--rouge); box-shadow: 0 0 14px rgba(192,57,43,0.5); }

    .graine {
      width: 9px; height: 9px;
      border-radius: 50%;
      flex-shrink: 0;
    }
    .rangee-nord .graine { background: radial-gradient(circle at 35% 35%, #EFE0C0, #C4A97A); box-shadow: 0 1px 3px rgba(0,0,0,0.4); }
    .rangee-sud  .graine { background: radial-gradient(circle at 35% 35%, #6B3A1F, #2C1A0E); box-shadow: 0 1px 2px rgba(0,0,0,0.5); border: 1px solid rgba(139,94,60,0.6); }
    .case-count { position: absolute; bottom: 2px; right: 4px; font-size: 0.55rem; font-weight: 700; opacity: 0.75; pointer-events: none; }

    /* ── ACTIONS ── */
    .actions {
      display: flex;
      justify-content: center;
      gap: 12px;
      margin-top: 18px;
      flex-wrap: wrap;
    }
    button {
      font-family: 'Inter', sans-serif;
      font-size: 0.8rem;
      font-weight: 600;
      letter-spacing: 0.07em;
      text-transform: uppercase;
      padding: 9px 22px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      transition: transform 0.12s, box-shadow 0.12s;
    }
    button:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,0.3); }
    #btn-quitter { background: rgba(92,51,23,0.7); color: var(--texte); border: 1.5px solid rgba(232,184,75,0.25); }

    /* ── HISTORIQUE ── */
    #historique {
      max-width: 700px;
      width: 100%;
      margin-top: 16px;
    }
    #historique h3 { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.14em; opacity: 0.4; margin-bottom: 7px; text-align: center; }
    #historique ul { list-style: none; display: flex; flex-wrap: wrap; gap: 5px; justify-content: center; }
    #historique li { background: rgba(92,51,23,0.4); border: 1px solid rgba(232,184,75,0.12); border-radius: 5px; font-size: 0.7rem; padding: 3px 9px; color: rgba(245,230,200,0.6); }

    /* ── MODAL FIN ── */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(20,8,2,0.85); z-index: 100; align-items: center; justify-content: center; padding: 20px; }
    .modal-overlay.visible { display: flex; }
    .modal { background: linear-gradient(160deg, #5C3317, #2C1A0E); border: 2px solid var(--or); border-radius: 18px; padding: 30px 36px; max-width: 420px; width: 100%; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.7); }
    .modal h2 { font-family: 'Playfair Display', serif; font-size: 1.7rem; color: var(--or); margin-bottom: 12px; }
    .modal p  { font-size: 0.85rem; line-height: 1.7; opacity: 0.85; margin-bottom: 8px; }
    .modal .emoji { font-size: 2.8rem; display: block; margin-bottom: 10px; }
    .modal-btn { margin-top: 16px; background: var(--or); color: var(--texte-sombre); padding: 10px 28px; border-radius: 8px; font-weight: 700; }

    /* ── ATTENTE ADVERSAIRE ── */
    #ecran-attente {
      display: none;
      flex-direction: column;
      align-items: center;
      gap: 14px;
      margin-top: 30px;
    }
    #ecran-attente.visible { display: flex; }
    .spinner-grand {
      width: 48px; height: 48px;
      border: 4px solid rgba(232,184,75,0.2);
      border-top-color: var(--or);
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 480px) {
      .case { padding: 3px; }
      .graine { width: 6px; height: 6px; }
      .rangee { gap: 4px; }
      .tablier-wrapper { padding: 12px 7px; }
    }
  </style>
</head>
<body>

<header>
  <h1>SONGO</h1>
  <div class="badge-role">Vous jouez : <?= strtoupper($role) ?> — <?= $nom ?></div>
</header>

<div id="statut-barre">
  <span class="dot-online"></span>
  <span id="texte-statut">Connexion…</span>
</div>

<!-- Scores -->
<div class="scores">
  <div class="score-card <?= $role === 'nord' ? 'actif' : '' ?>" id="card-nord">
    <div class="nom">Joueur Nord</div>
    <div class="pts" id="score-nord">0</div>
    <?php if ($role === 'nord'): ?><div class="moi-badge">Vous</div><?php endif; ?>
  </div>
  <div class="score-card <?= $role === 'sud'  ? 'actif' : '' ?>" id="card-sud">
    <div class="nom">Joueur Sud</div>
    <div class="pts" id="score-sud">0</div>
    <?php if ($role === 'sud'): ?><div class="moi-badge">Vous</div><?php endif; ?>
  </div>
</div>

<!-- Écran attente joueur 2 -->
<div id="ecran-attente">
  <div class="spinner-grand"></div>
  <p style="opacity:0.6; font-size:0.85rem;">En attente du deuxième joueur…</p>
</div>

<!-- Tablier (caché si en attente) -->
<div class="tablier-wrapper" id="tablier" style="display:none;">
  <div class="nums-rangee"  id="nums-nord"></div>
  <div class="rangee-label <?= $role === 'nord' ? 'moi' : '' ?>" id="label-nord">— JOUEUR NORD <?= $role === 'nord' ? '(Vous)' : '' ?> —</div>
  <div class="rangee rangee-nord" id="rangee-nord"></div>
  <div class="separateur"></div>
  <div class="rangee rangee-sud"  id="rangee-sud"></div>
  <div class="rangee-label <?= $role === 'sud'  ? 'moi' : '' ?>" id="label-sud"  style="margin-top:5px;">— JOUEUR SUD <?= $role === 'sud' ? '(Vous)' : '' ?> —</div>
  <div class="nums-rangee"  id="nums-sud"></div>
</div>

<div class="actions" id="actions" style="display:none;">
  <button id="btn-quitter" onclick="window.location.href='index.php'">✕ Quitter</button>
</div>

<div id="historique" style="display:none;">
  <h3>Historique des coups</h3>
  <ul id="liste-historique"></ul>
</div>

<!-- Modal Fin -->
<div class="modal-overlay" id="modal-fin">
  <div class="modal">
    <span class="emoji" id="fin-emoji">🏆</span>
    <h2 id="fin-titre"></h2>
    <p  id="fin-message"></p>
    <button class="modal-btn" onclick="window.location.href='index.php'">Nouvelle partie</button>
  </div>
</div>

<script>
/* ══════════════════════════════════════════════
   SONGO v2 — jeu.php (client)
   Ajax + Polling toutes les 2 secondes
   ══════════════════════════════════════════════ */

const PARTIE_ID  = <?= json_encode($partie_id) ?>;
const MON_ROLE   = <?= json_encode($role) ?>;
const ADVERSAIRE = <?= json_encode($adversaire) ?>;
const NB_CASES   = 7;

let dernierEtat    = null;
let enAnimation    = false;
let pollingActif   = false;
let intervalPolling = null;

/* ══════════════════════════════════════════════
   POLLING AJAX
   ══════════════════════════════════════════════ */
function demarrerPolling() {
  if (pollingActif) return;
  pollingActif = true;
  intervalPolling = setInterval(pollEtat, 2000);
  pollEtat(); // immédiat
}

function arreterPolling() {
  pollingActif = false;
  clearInterval(intervalPolling);
}

function pollEtat() {
  ajax('etat_partie', { partie_id: PARTIE_ID }, function(data) {
    if (!data.succes) { setStatut('⚠️ Erreur de connexion', 'rouge'); return; }
    traiterEtat(data.etat);
  });
}

/* ══════════════════════════════════════════════
   TRAITEMENT DE L'ÉTAT REÇU
   ══════════════════════════════════════════════ */
function traiterEtat(etat) {
  dernierEtat = etat;

  if (etat.statut === 'attente') {
    document.getElementById('ecran-attente').classList.add('visible');
    document.getElementById('tablier').style.display   = 'none';
    document.getElementById('actions').style.display   = 'none';
    document.getElementById('historique').style.display= 'none';
    setStatut('En attente du deuxième joueur…');
    return;
  }

  // Partie démarrée ou terminée
  document.getElementById('ecran-attente').classList.remove('visible');
  document.getElementById('tablier').style.display    = 'block';
  document.getElementById('actions').style.display    = 'flex';
  document.getElementById('historique').style.display = 'block';

  rendrePlateau(etat);
  mettreAJourScores(etat);
  mettreAJourHistorique(etat);

  if (etat.statut === 'termine') {
    arreterPolling();
    afficherFin(etat.resultat, etat);
    return;
  }

  // Message de tour
  if (etat.tour === MON_ROLE) {
    setStatut(etat.solidarite_requise ? '⚠️ Solidarité : vous devez alimenter l\'adversaire !' : '🎯 C\'est votre tour — choisissez une case', 'or');
  } else {
    const nomAdv = etat.tour === 'nord' ? (etat.joueur_nord || 'Joueur Nord') : etat.joueur_sud;
    setStatut(`⏳ Tour de ${nomAdv}…`);
  }

  // Activer/désactiver les cartes de score
  document.getElementById('card-nord').classList.toggle('actif', etat.tour === 'nord');
  document.getElementById('card-sud').classList.toggle('actif',  etat.tour === 'sud');
}

/* ══════════════════════════════════════════════
   RENDU DU PLATEAU
   ══════════════════════════════════════════════ */
function rendrePlateau(etat) {
  ['nord', 'sud'].forEach(joueur => {
    const rangee = document.getElementById(`rangee-${joueur}`);
    const nums   = document.getElementById(`nums-${joueur}`);
    rangee.innerHTML = '';
    nums.innerHTML   = '';

    for (let col = 0; col < NB_CASES; col++) {
      // Pour le Sud : col 0 (gauche) = index 6, col 6 (droite) = index 0
      // Pour le Nord : col 0 (gauche) = index 0, col 6 (droite) = index 6
      const i = joueur === 'sud' ? (NB_CASES - 1 - col) : col;

      // Numérotation 1→7 de gauche à droite pour les deux joueurs
      const numDiv = document.createElement('div');
      numDiv.className = 'num-case';
      numDiv.textContent = col + 1;
      nums.appendChild(numDiv);

      // Case
      const div = document.createElement('div');
      div.className = 'case';
      div.dataset.joueur = joueur;
      div.dataset.idx    = i;

      const nb = etat.plateau[joueur][i];
      const affich = Math.min(nb, 9);
      for (let g = 0; g < affich; g++) {
        const gr = document.createElement('div');
        gr.className = 'graine';
        div.appendChild(gr);
      }
      if (nb > 9) {
        const cnt = document.createElement('div');
        cnt.className = 'case-count';
        cnt.textContent = nb;
        div.appendChild(cnt);
      }

      // Interactivité : seulement nos cases, notre tour, partie active
      const peutJouer = !enAnimation
        && etat.statut === 'en_cours'
        && joueur === MON_ROLE
        && etat.tour === MON_ROLE
        && nb > 0;

      if (peutJouer) {
        div.addEventListener('click', () => jouerCoup(i));
      } else {
        div.classList.add(joueur !== MON_ROLE ? 'inactive' : 'disabled');
      }

      rangee.appendChild(div);
    }
  });
}

/* ══════════════════════════════════════════════
   JOUER UN COUP
   ══════════════════════════════════════════════ */
async function jouerCoup(idx) {
  if (enAnimation) return;
  enAnimation = true;
  arreterPolling();

  // Highlight immédiat de la case jouée
  highlightCase(MON_ROLE, idx, 'selectionnee');

  ajax('jouer_coup', { partie_id: PARTIE_ID, role: MON_ROLE, idx }, async function(data) {
    clearHighlight(MON_ROLE, idx, 'selectionnee');

    if (!data.succes) {
      enAnimation = false;
      setStatut('⚠️ ' + (data.message || 'Erreur.'), 'rouge');
      demarrerPolling();
      return;
    }

    // Récupérer le nombre de graines distribuées depuis l'historique
    const dernierCoup = data.etat.historique?.[data.etat.historique.length - 1];
    const nbGraines   = dernierCoup ? dernierCoup.graines : 0;

    // Animation case par case avant d'afficher le nouvel état
    await animerDistribution(MON_ROLE, idx, nbGraines, dernierEtat?.plateau);

    enAnimation = false;
    traiterEtat(data.etat);
    if (data.etat.statut !== 'termine') demarrerPolling();
  });
}

/* ══════════════════════════════════════════════
   ANIMATION DE DISTRIBUTION DES GRAINES
   ══════════════════════════════════════════════ */
function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

// Son "toc" synthétique — une bille qui tombe dans du bois
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
function jouerSonGraine() {
  try {
    // Bruit bref filtré → son "toc" boisé
    const bufferSize = audioCtx.sampleRate * 0.06; // 60ms
    const buffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
    const data   = buffer.getChannelData(0);
    for (let k = 0; k < bufferSize; k++) {
      data[k] = (Math.random() * 2 - 1) * Math.pow(1 - k / bufferSize, 8);
    }
    const source = audioCtx.createBufferSource();
    source.buffer = buffer;

    // Filtre passe-bas → enlève les aigus, donne un son sourd/boisé
    const filter = audioCtx.createBiquadFilter();
    filter.type      = 'lowpass';
    filter.frequency.value = 400;

    // Gain pour ne pas saturer
    const gain = audioCtx.createGain();
    gain.gain.value = 0.6;

    source.connect(filter);
    filter.connect(gain);
    gain.connect(audioCtx.destination);
    source.start();
  } catch(e) { /* silencieux si AudioContext bloqué */ }
}

// Retourne la case suivante dans le sens anti-horaire du Songo
function caseSuivante(joueur, idx) {
  if (joueur === 'sud') {
    if (idx > 0) return { joueur: 'sud', idx: idx - 1 };
    else         return { joueur: 'nord', idx: 0 };
  } else {
    if (idx < NB_CASES - 1) return { joueur: 'nord', idx: idx + 1 };
    else                    return { joueur: 'sud',  idx: NB_CASES - 1 };
  }
}

// Ajoute visuellement une graine dans une case DOM
function ajouterGraineDom(caseEl, joueur) {
  const gr = document.createElement('div');
  gr.className = `graine graine-new`;
  gr.style.transform = 'scale(0)';
  gr.style.transition = 'transform 0.12s ease-out';
  caseEl.appendChild(gr);
  // Forcer reflow puis animer
  requestAnimationFrame(() => { gr.style.transform = 'scale(1)'; });
  // Mettre à jour le compteur affiché si > 9
  const graines = caseEl.querySelectorAll('.graine').length;
  let cnt = caseEl.querySelector('.case-count');
  if (graines > 9) {
    if (!cnt) {
      cnt = document.createElement('div');
      cnt.className = 'case-count';
      caseEl.appendChild(cnt);
    }
    cnt.textContent = graines;
  }
}

async function animerDistribution(joueurDepart, idxDepart, nbGraines, plateauInitial) {
  let j = joueurDepart;
  let i = idxDepart;

  // Vider visuellement la case de départ
  const rangeeDepart = document.getElementById(`rangee-${joueurDepart}`);
  const caseDepart   = rangeeDepart
    ? [...rangeeDepart.querySelectorAll('.case')].find(c => parseInt(c.dataset.idx) === idxDepart)
    : null;
  if (caseDepart) {
    caseDepart.querySelectorAll('.graine').forEach(g => g.remove());
    const cnt = caseDepart.querySelector('.case-count');
    if (cnt) cnt.remove();
    caseDepart.classList.add('selectionnee');
    await sleep(120);
    caseDepart.classList.remove('selectionnee');
  }

  // Distribuer une graine à la fois
  for (let g = 0; g < nbGraines; g++) {
    ({ joueur: j, idx: i } = caseSuivante(j, i));

    const rangeeEl = document.getElementById(`rangee-${j}`);
    const caseEl   = rangeeEl
      ? [...rangeeEl.querySelectorAll('.case')].find(c => parseInt(c.dataset.idx) === i)
      : null;

    if (caseEl) {
      // Résoudre le contexte audio (nécessaire après interaction utilisateur sur certains navigateurs)
      if (audioCtx.state === 'suspended') audioCtx.resume();

      ajouterGraineDom(caseEl, j);
      jouerSonGraine();

      // Highlight bref de la case qui reçoit la graine
      caseEl.classList.add('selectionnee');
      await sleep(180);
      caseEl.classList.remove('selectionnee');
    } else {
      await sleep(180);
    }
  }
}

/* ══════════════════════════════════════════════
   AFFICHAGE FIN DE PARTIE
   ══════════════════════════════════════════════ */
function afficherFin(resultat, etat) {
  let emoji, titre, message;
  const n = resultat.score_nord, s = resultat.score_sud;

  if (resultat.vainqueur === MON_ROLE) {
    emoji  = '🏆'; titre = 'Vous avez gagné !';
    message = `Votre score : ${MON_ROLE === 'sud' ? s : n} — Adversaire : ${MON_ROLE === 'sud' ? n : s}`;
  } else if (resultat.vainqueur === 'egalite') {
    emoji  = '🤝'; titre = 'Match nul !';
    message = `Nord : ${n} — Sud : ${s} graines`;
  } else {
    emoji  = '😔'; titre = 'Défaite…';
    message = `Votre score : ${MON_ROLE === 'sud' ? s : n} — Adversaire : ${MON_ROLE === 'sud' ? n : s}`;
  }

  const raisons = { victoire: '', solidarite: ' (Solidarité impossible)', moins10: ' (Moins de 10 graines)' };
  message += raisons[resultat.raison] || '';

  document.getElementById('fin-emoji').textContent   = emoji;
  document.getElementById('fin-titre').textContent   = titre;
  document.getElementById('fin-message').textContent = message;
  document.getElementById('modal-fin').classList.add('visible');
}

/* ══════════════════════════════════════════════
   HISTORIQUE
   ══════════════════════════════════════════════ */
function mettreAJourHistorique(etat) {
  const ul = document.getElementById('liste-historique');
  ul.innerHTML = '';
  const hist = [...(etat.historique || [])].reverse().slice(0, 20);
  hist.forEach(h => {
    const li  = document.createElement('li');
    const qui = h.joueur === 'sud' ? 'Sud' : 'Nord';
    li.textContent = `${qui} C${h.case} (${h.graines}g)${h.prises > 0 ? ` +${h.prises}` : ''}`;
    ul.appendChild(li);
  });
}

/* ══════════════════════════════════════════════
   UTILITAIRES UI
   ══════════════════════════════════════════════ */
function mettreAJourScores(etat) {
  document.getElementById('score-nord').textContent = etat.score_nord;
  document.getElementById('score-sud').textContent  = etat.score_sud;
}

function setStatut(msg, couleur) {
  const el = document.getElementById('texte-statut');
  el.textContent = msg;
  el.style.color = couleur === 'or' ? 'var(--or)' : couleur === 'rouge' ? 'var(--rouge)' : 'inherit';
}

function highlightCase(joueur, idx, classe) {
  const c = document.getElementById(`rangee-${joueur}`)?.querySelectorAll('.case')[idx];
  if (c) c.classList.add(classe);
}

function clearHighlight(joueur, idx, classe) {
  const c = document.getElementById(`rangee-${joueur}`)?.querySelectorAll('.case')[idx];
  if (c) c.classList.remove(classe);
}

function ajax(action, params, callback) {
  const body = new URLSearchParams({ action, ...params });
  fetch('ajax.php', { method: 'POST', body })
    .then(r => r.json())
    .then(callback)
    .catch(e => { setStatut('Erreur réseau', 'rouge'); });
}

/* ── Démarrage ── */
demarrerPolling();
</script>

  <!-- ══ Bannière installation PWA ══ -->
  <div id="pwa-banner" style="display:none; position:fixed; bottom:0; left:0; right:0;
    background:linear-gradient(135deg,#5C3317,#2C1A0E); border-top:2px solid #E8B84B;
    padding:12px 20px; z-index:999; align-items:center; justify-content:space-between;
    gap:12px; font-family:'Inter',sans-serif;">
    <div style="display:flex;align-items:center;gap:10px;">
      <img src="icons/icon-72.png" width="36" height="36" style="border-radius:8px;"/>
      <div>
        <div style="color:#E8B84B;font-weight:700;font-size:0.85rem;">Installer Songo</div>
        <div style="color:rgba(245,230,200,0.65);font-size:0.72rem;">Jouer hors ligne depuis votre écran</div>
      </div>
    </div>
    <button onclick="installerApp()" style="background:#E8B84B;color:#2C1A0E;border:none;
      border-radius:8px;padding:8px 18px;font-weight:700;font-size:0.8rem;cursor:pointer;">
      Installer
    </button>
    <button onclick="document.getElementById('pwa-banner').style.display='none'"
      style="background:transparent;border:none;color:rgba(245,230,200,0.5);font-size:1.2rem;cursor:pointer;padding:4px 8px;">✕</button>
  </div>

  <script>
  /* ── Enregistrement du Service Worker ── */
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js')
        .then(reg => {
          console.log('[PWA] Service Worker enregistré :', reg.scope);
          // Proposer la mise à jour si nouvelle version disponible
          reg.addEventListener('updatefound', () => {
            const worker = reg.installing;
            worker.addEventListener('statechange', () => {
              if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                console.log('[PWA] Nouvelle version disponible — rechargement...');
                worker.postMessage({ type: 'SKIP_WAITING' });
                window.location.reload();
              }
            });
          });
        })
        .catch(err => console.warn('[PWA] Échec enregistrement SW :', err));
    });
  }

  /* ── Bannière installation (A2HS) ── */
  let deferredPrompt = null;
  window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    deferredPrompt = e;
    const banner = document.getElementById('pwa-banner');
    if (banner) banner.style.display = 'flex';
  });
  window.addEventListener('appinstalled', () => {
    const banner = document.getElementById('pwa-banner');
    if (banner) banner.style.display = 'none';
    deferredPrompt = null;
  });
  function installerApp() {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then(choice => {
      deferredPrompt = null;
      document.getElementById('pwa-banner').style.display = 'none';
    });
  }
  </script>
</body>
</html>
