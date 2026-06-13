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
  
    /* ── Animation graines en vol ── */
    .graine-volante {
      position: fixed;
      width: 10px; height: 10px;
      border-radius: 50%;
      pointer-events: none;
      z-index: 200;
      transition: none;
    }
    .graine-volante.nord { background: radial-gradient(circle at 35% 35%, #EFE0C0, #C4A97A); box-shadow: 0 1px 3px rgba(0,0,0,0.4); }
    .graine-volante.sud  { background: radial-gradient(circle at 35% 35%, #6B3A1F, #2C1A0E); border: 1px solid rgba(139,94,60,0.6); }

    /* ── Timer ── */
    #timer-barre {
      display: none;
      max-width: 700px;
      width: 100%;
      margin: 0 auto 10px;
      height: 5px;
      border-radius: 3px;
      background: rgba(255,255,255,0.08);
      overflow: hidden;
    }
    #timer-barre.visible { display: block; }
    #timer-progress {
      height: 100%;
      width: 100%;
      border-radius: 3px;
      background: var(--or);
      transition: width 1s linear, background 0.5s;
    }
    #timer-compte {
      text-align: center;
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.08em;
      margin-bottom: 8px;
      min-height: 18px;
      color: rgba(245,230,200,0.5);
      transition: color 0.3s;
    }
    #timer-compte.urgent { color: #E74C3C; }
  
    /* ── Barre emojis ── */
    #emoji-barre {
      display: none;
      max-width: 700px;
      width: 100%;
      margin: 12px auto 0;
      background: rgba(0,0,0,0.25);
      border: 1px solid rgba(232,184,75,0.15);
      border-radius: 14px;
      padding: 10px 14px;
      gap: 8px;
      flex-direction: column;
    }
    #emoji-barre.visible { display: flex; }

    .emoji-boutons {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      justify-content: center;
    }
    .emoji-btn {
      font-size: 1.4rem;
      background: rgba(92,51,23,0.5);
      border: 1.5px solid rgba(232,184,75,0.15);
      border-radius: 10px;
      padding: 5px 9px;
      cursor: pointer;
      transition: transform 0.12s, border-color 0.15s, background 0.15s;
      line-height: 1;
    }
    .emoji-btn:hover { transform: scale(1.25); border-color: var(--or); background: rgba(232,184,75,0.12); }
    .emoji-btn:active { transform: scale(0.95); }

    /* Emojis flottants animés */
    .emoji-flottant {
      position: fixed;
      font-size: 2rem;
      pointer-events: none;
      z-index: 300;
      animation: monterEmoji 2.2s ease-out forwards;
      user-select: none;
    }
    @keyframes monterEmoji {
      0%   { opacity: 1;   transform: translateY(0)   scale(1); }
      60%  { opacity: 1;   transform: translateY(-80px) scale(1.3); }
      100% { opacity: 0;   transform: translateY(-140px) scale(0.8); }
    }

    /* Bulle réaction adverse */
    .bulle-emoji {
      position: fixed;
      background: rgba(44,26,14,0.92);
      border: 1.5px solid var(--or);
      border-radius: 18px 18px 18px 4px;
      padding: 6px 14px;
      font-size: 1.5rem;
      z-index: 300;
      animation: apparaitreBulle 2.8s ease forwards;
      pointer-events: none;
    }
    @keyframes apparaitreBulle {
      0%   { opacity: 0; transform: scale(0.5); }
      15%  { opacity: 1; transform: scale(1.1); }
      30%  { transform: scale(1); }
      70%  { opacity: 1; }
      100% { opacity: 0; transform: scale(0.8) translateY(-20px); }
    }
  </style>
</head>
<body>

  <script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(regs => {
      regs.forEach(reg => reg.unregister());
    });
    if ('caches' in window) {
      caches.keys().then(keys => keys.forEach(k => caches.delete(k)));
    }
  }
  </script>

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

<div id="timer-compte"></div>
<div id="timer-barre"><div id="timer-progress"></div></div>
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


<!-- Barre emojis -->
<div id="emoji-barre">
  <div class="emoji-boutons">
    <button class="emoji-btn" onclick="envoyerEmoji('😂')">😂</button>
    <button class="emoji-btn" onclick="envoyerEmoji('😎')">😎</button>
    <button class="emoji-btn" onclick="envoyerEmoji('🔥')">🔥</button>
    <button class="emoji-btn" onclick="envoyerEmoji('😤')">😤</button>
    <button class="emoji-btn" onclick="envoyerEmoji('🤔')">🤔</button>
    <button class="emoji-btn" onclick="envoyerEmoji('😱')">😱</button>
    <button class="emoji-btn" onclick="envoyerEmoji('👏')">👏</button>
    <button class="emoji-btn" onclick="envoyerEmoji('😢')">😢</button>
    <button class="emoji-btn" onclick="envoyerEmoji('💪')">💪</button>
    <button class="emoji-btn" onclick="envoyerEmoji('🎉')">🎉</button>
    <button class="emoji-btn" onclick="envoyerEmoji('😴')">😴</button>
    <button class="emoji-btn" onclick="envoyerEmoji('🤝')">🤝</button>
  </div>
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
let audioCtx = null;
function getAudioCtx() {
  if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  return audioCtx;
}
function jouerSonGraine() {
  try {
    const ctx = getAudioCtx();
    const bufSize = ctx.sampleRate * 0.06;
    const buf = ctx.createBuffer(1, bufSize, ctx.sampleRate);
    const data = buf.getChannelData(0);
    for (let i = 0; i < bufSize; i++) data[i] = (Math.random() * 2 - 1);
    const src = ctx.createBufferSource();
    src.buffer = buf;
    const bpf = ctx.createBiquadFilter();
    bpf.type = 'bandpass';
    bpf.frequency.value = 800 + Math.random() * 200;
    bpf.Q.value = 1.5;
    const gain = ctx.createGain();
    const now = ctx.currentTime;
    gain.gain.setValueAtTime(0.55, now);
    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.055);
    src.connect(bpf);
    bpf.connect(gain);
    gain.connect(ctx.destination);
    src.start(now);
    src.stop(now + 0.06);
  } catch(e) {}
}

let dernierEtat     = null;
let dernierTour     = null; // pour éviter de relancer le timer à chaque poll
let enAnimation     = false;
let pollingActif    = false;
let intervalPolling = null;
let dernierHistoLen = 0;   // pour détecter un nouveau coup adverse

// ── Timer 60s ─────────────────────────────────────────────────────────────
const DUREE_TOUR   = 60;  // secondes
let timerInterval  = null;
let timerRestant   = DUREE_TOUR;

function demarrerTimer() {
  arreterTimer();
  timerRestant = DUREE_TOUR;
  mettreAJourTimer();
  document.getElementById('timer-barre').classList.add('visible');

  timerInterval = setInterval(() => {
    timerRestant--;
    mettreAJourTimer();
    if (timerRestant <= 0) {
      arreterTimer();
      jouerCoupAuto();
    }
  }, 1000);
}

function arreterTimer() {
  clearInterval(timerInterval);
  timerInterval = null;
  document.getElementById('timer-barre').classList.remove('visible');
  document.getElementById('timer-compte').textContent = '';
  document.getElementById('timer-compte').classList.remove('urgent');
}

function mettreAJourTimer() {
  const pct = (timerRestant / DUREE_TOUR) * 100;
  const bar = document.getElementById('timer-progress');
  const lbl = document.getElementById('timer-compte');
  bar.style.width = pct + '%';
  bar.style.background = timerRestant <= 10 ? '#E74C3C' : timerRestant <= 20 ? '#E8B84B' : '#27AE60';
  lbl.textContent = `⏱ ${timerRestant}s`;
  lbl.classList.toggle('urgent', timerRestant <= 10);
}

function jouerCoupAuto() {
  if (!dernierEtat || enAnimation) return;
  // Choisir une case non vide au hasard
  const casesDispos = [];
  for (let i = 0; i < NB_CASES; i++) {
    if (dernierEtat.plateau[MON_ROLE][i] > 0) casesDispos.push(i);
  }
  if (!casesDispos.length) return;
  const idx = casesDispos[Math.floor(Math.random() * casesDispos.length)];
  setStatut('⏱ Temps écoulé — coup automatique !', 'rouge');
  setTimeout(() => jouerCoup(idx), 600);
}

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
  if (enAnimation) return;
  ajax('etat_partie', { partie_id: PARTIE_ID }, async function(data) {
    if (!data.succes) { setStatut('⚠️ Erreur de connexion', 'rouge'); return; }
    const etat    = data.etat;
    const histLen = (etat.historique || []).length;

    // Détecter un nouveau coup adverse et l'animer
    if (histLen > dernierHistoLen && dernierEtat !== null && etat.statut === 'en_cours') {
      const dernierCoup = etat.historique[etat.historique.length - 1];
      if (dernierCoup && dernierCoup.joueur !== MON_ROLE && dernierCoup.sequence?.length > 0) {
        enAnimation = true;
        arreterPolling();
        await animerGrainesAsync(dernierCoup.joueur, dernierCoup.idx, dernierCoup.sequence);
        enAnimation = false;
      }
    }

    dernierHistoLen = histLen;
    traiterEtat(etat);
    if (etat.emojis) traiterNouveauxEmojis(etat.emojis);
    if (etat.statut !== 'termine') demarrerPolling();
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
  document.getElementById('emoji-barre').classList.add('visible');

  rendrePlateau(etat);
  mettreAJourScores(etat);
  mettreAJourHistorique(etat);

  if (etat.statut === 'termine') {
    arreterPolling();
    arreterTimer();
    dernierTour = null;
    afficherFin(etat.resultat, etat);
    return;
  }

  // Message de tour
  if (etat.tour === MON_ROLE) {
    setStatut(etat.solidarite_requise ? '⚠️ Solidarité : vous devez alimenter l\'adversaire !' : '🎯 C\'est votre tour — choisissez une case', 'or');
    if (!enAnimation && dernierTour !== MON_ROLE) demarrerTimer();
  } else {
    if (dernierTour === MON_ROLE) arreterTimer();
    const nomAdv = etat.tour === 'nord' ? (etat.joueur_nord || 'Joueur Nord') : etat.joueur_sud;
    setStatut(`⏳ Tour de ${nomAdv}…`);
  }

  // Mémoriser le tour courant
  dernierTour = etat.tour;

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
function jouerCoup(idx) {
  if (enAnimation) return;
  enAnimation = true;
  arreterTimer();
  arreterPolling();

  // Récupérer le nombre de graines pour l'animation
  const nbGraines = dernierEtat ? dernierEtat.plateau[MON_ROLE][idx] : 0;

  // Highlight case source
  highlightCase(MON_ROLE, idx, 'selectionnee');

  // Construire la séquence de distribution locale (pour l'animation)
  const sequence = nbGraines > 0 ? construireSequenceClient(MON_ROLE, idx, nbGraines) : [];

  // Lancer l'animation puis envoyer le coup au serveur
  animerGraines(MON_ROLE, idx, sequence, nbGraines, function() {
    ajax('jouer_coup', { partie_id: PARTIE_ID, role: MON_ROLE, idx }, function(data) {
      clearHighlight(MON_ROLE, idx, 'selectionnee');
      enAnimation = false;

      if (!data.succes) {
        setStatut('⚠️ ' + (data.message || 'Erreur.'), 'rouge');
        demarrerPolling();
        return;
      }

      traiterEtat(data.etat);
      if (data.etat.statut !== 'termine') demarrerPolling();
    });
  });
}

/* ── Animation graines volantes ──────────────────────────────────────────── */
function animerGraines(joueur, idxDepart, sequence, nbGraines, callback) {
  if (!sequence.length || nbGraines === 0) { callback(); return; }

  const delai = Math.max(60, 280 - nbGraines * 6);
  let i = 0;

  function step() {
    // Retirer le highlight de la case précédente
    if (i > 0) clearHighlight(sequence[i-1].joueur, sequence[i-1].idx, 'selectionnee');
    if (i === 0) clearHighlight(joueur, idxDepart, 'selectionnee');

    if (i < sequence.length) {
      const pos = sequence[i];
      // Animer une graine volante entre la case source et la case cible
      const src  = i === 0 ? getCaseElement(joueur, idxDepart) : getCaseElement(sequence[i-1].joueur, sequence[i-1].idx);
      const dest = getCaseElement(pos.joueur, pos.idx);
      if (src && dest) lancerGraineVolante(src, dest, joueur);

      // Highlight la case cible
      jouerSonGraine();
      highlightCase(pos.joueur, pos.idx, 'selectionnee');
      i++;
      setTimeout(step, delai);
    } else {
      // Dernière case : highlight vert
      const last = sequence[sequence.length - 1];
      clearHighlight(last.joueur, last.idx, 'selectionnee');
      highlightCase(last.joueur, last.idx, 'derniere');
      setTimeout(() => {
        clearHighlight(last.joueur, last.idx, 'derniere');
        callback();
      }, 350);
    }
  }

  setTimeout(step, 80);
}

function lancerGraineVolante(srcEl, destEl, joueur) {
  const sr = srcEl.getBoundingClientRect();
  const dr = destEl.getBoundingClientRect();
  const g  = document.createElement('div');
  g.className = 'graine-volante ' + joueur;
  g.style.left = (sr.left + sr.width/2 - 5) + 'px';
  g.style.top  = (sr.top  + sr.height/2 - 5) + 'px';
  document.body.appendChild(g);

  // Animer avec requestAnimationFrame
  const dx = (dr.left + dr.width/2  - 5) - (sr.left + sr.width/2  - 5);
  const dy = (dr.top  + dr.height/2 - 5) - (sr.top  + sr.height/2 - 5);
  const duree = 180;
  const debut = performance.now();

  function frame(t) {
    const pct = Math.min((t - debut) / duree, 1);
    const ease = pct < 0.5 ? 2*pct*pct : -1+(4-2*pct)*pct; // easeInOut
    g.style.transform = `translate(${dx*ease}px, ${dy*ease}px) scale(${1 - ease*0.3})`;
    if (pct < 1) requestAnimationFrame(frame);
    else g.remove();
  }
  requestAnimationFrame(frame);
}

function getCaseElement(joueur, idx) {
  const rangee = document.getElementById('rangee-' + joueur);
  if (!rangee) return null;
  return [...rangee.querySelectorAll('.case')].find(c => parseInt(c.dataset.idx) === idx) || null;
}

/* ── Reconstruction de la séquence côté client (miroir de PHP) ────────────── */
function construireSequenceClient(joueur, depart, nbGraines) {
  const adversaire = joueur === 'sud' ? 'nord' : 'sud';
  const seq = [];
  let caseJ = depart, dansAdverse = false, distribues = 0, tourComplet = false;

  while (distribues < nbGraines) {
    if (!dansAdverse) {
      // Camp du joueur : index croissant
      caseJ++;
      if (caseJ >= NB_CASES) { dansAdverse = true; caseJ = NB_CASES - 1; }
    } else {
      // Camp adverse : index décroissant
      caseJ--;
      if (caseJ < 0) { dansAdverse = false; tourComplet = true; caseJ = 0; }
    }
    const caseActuelle = dansAdverse ? adversaire : joueur;
    if (tourComplet && caseActuelle === joueur && caseJ === depart) continue;
    seq.push({ joueur: caseActuelle, idx: caseJ });
    distribues++;
  }
  return seq;
}

/* ── Version async de l'animation (pour le coup adverse via polling) ── */
function animerGrainesAsync(joueur, idxDepart, sequence) {
  return new Promise(resolve => {
    animerGraines(joueur, idxDepart, sequence, sequence.length, resolve);
  });
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
  fetch('./ajax.php', { method: 'POST', body })
    .then(r => r.json())
    .then(callback)
    .catch(e => { setStatut('Erreur réseau', 'rouge'); });
}


/* ══════════════════════════════════════════════
   EMOJIS EN TEMPS RÉEL
   ══════════════════════════════════════════════ */

let derniersEmojisVus = [];

function envoyerEmoji(emoji) {
  if (envoyerEmoji._cooldown) return;
  envoyerEmoji._cooldown = true;
  setTimeout(() => { envoyerEmoji._cooldown = false; }, 2000);
  afficherEmojiFlottant(emoji, true);
  ajax('envoyer_emoji', { partie_id: PARTIE_ID, role: MON_ROLE, emoji }, function(data) {
    if (!data.succes) console.warn('Emoji refusé:', data.message);
  });
}

function afficherEmojiFlottant(emoji, estMoi) {
  const el = document.createElement('div');
  if (estMoi) {
    el.className = 'emoji-flottant';
    el.textContent = emoji;
    el.style.left   = (20 + Math.random() * (window.innerWidth - 80)) + 'px';
    el.style.bottom = '120px';
    el.style.top    = 'auto';
  } else {
    el.className = 'bulle-emoji';
    el.textContent = emoji;
    el.style.top   = '80px';
    el.style.left  = (MON_ROLE === 'sud' ? '20px' : 'auto');
    el.style.right = (MON_ROLE === 'nord' ? '20px' : 'auto');
  }
  document.body.appendChild(el);
  setTimeout(() => el.remove(), estMoi ? 2200 : 2800);
}

function traiterNouveauxEmojis(emojis) {
  if (!emojis || !emojis.length) return;
  const vus = new Set(derniersEmojisVus.map(e => e.ts + e.role));
  const nouveaux = emojis.filter(e => !vus.has(e.ts + e.role) && e.role !== MON_ROLE);
  nouveaux.forEach(e => afficherEmojiFlottant(e.emoji, false));
  derniersEmojisVus = emojis;
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
