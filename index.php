<?php
/**
 * SONGO v2 — Multijoueur distant (Ajax + PHP + JSON)
 * index.php — Lobby : créer ou rejoindre une partie
 * TP Dr. MESSI
 */
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Songo — Multijoueur en ligne</title>
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
      --or:          #E8B84B;
      --texte:       #F5E6C8;
      --texte-sombre:#2C1A0E;
      --rouge:       #C0392B;
      --vert:        #27AE60;
    }

    body {
      background: var(--bois-fonce);
      font-family: 'Inter', sans-serif;
      color: var(--texte);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 30px 16px;
      background-image:
        radial-gradient(ellipse at 20% 0%,   rgba(139,94,60,0.25) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 100%, rgba(92,51,23,0.4)   0%, transparent 60%);
    }

    header { text-align: center; margin-bottom: 40px; }
    header h1 {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2.6rem, 8vw, 4.2rem);
      font-weight: 900;
      letter-spacing: 0.1em;
      color: var(--or);
      text-shadow: 0 2px 24px rgba(232,184,75,0.4);
    }
    header p {
      font-size: 0.8rem;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      opacity: 0.5;
      margin-top: 6px;
    }

    .lobby {
      background: linear-gradient(160deg, #5C3317 0%, #2C1A0E 100%);
      border: 2px solid rgba(232,184,75,0.25);
      border-radius: 20px;
      padding: 36px 40px;
      max-width: 440px;
      width: 100%;
      box-shadow: 0 16px 50px rgba(0,0,0,0.55);
    }

    .tabs {
      display: flex;
      gap: 0;
      margin-bottom: 28px;
      border-radius: 10px;
      overflow: hidden;
      border: 1.5px solid rgba(232,184,75,0.2);
    }
    .tab {
      flex: 1;
      padding: 10px;
      text-align: center;
      font-size: 0.82rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      cursor: pointer;
      background: transparent;
      color: rgba(245,230,200,0.5);
      border: none;
      transition: background 0.2s, color 0.2s;
    }
    .tab.active {
      background: var(--or);
      color: var(--texte-sombre);
    }

    .panneau { display: none; }
    .panneau.visible { display: block; }

    label {
      display: block;
      font-size: 0.72rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      opacity: 0.6;
      margin-bottom: 7px;
      margin-top: 18px;
    }
    input[type="text"] {
      width: 100%;
      background: rgba(0,0,0,0.3);
      border: 1.5px solid rgba(232,184,75,0.2);
      border-radius: 8px;
      padding: 11px 14px;
      color: var(--texte);
      font-family: 'Inter', sans-serif;
      font-size: 0.9rem;
      outline: none;
      transition: border-color 0.2s;
    }
    input[type="text"]:focus { border-color: var(--or); }
    input[type="text"]::placeholder { opacity: 0.35; }

    .btn-principal {
      margin-top: 22px;
      width: 100%;
      padding: 13px;
      background: var(--or);
      color: var(--texte-sombre);
      border: none;
      border-radius: 10px;
      font-family: 'Inter', sans-serif;
      font-size: 0.88rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      cursor: pointer;
      transition: transform 0.12s, box-shadow 0.12s, opacity 0.2s;
    }
    .btn-principal:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(232,184,75,0.35); }
    .btn-principal:active { transform: translateY(0); }
    .btn-principal:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

    .code-partie {
      display: none;
      margin-top: 22px;
      background: rgba(0,0,0,0.35);
      border: 1.5px solid var(--or);
      border-radius: 12px;
      padding: 18px;
      text-align: center;
    }
    .code-partie .label-code {
      font-size: 0.7rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      opacity: 0.55;
      margin-bottom: 8px;
    }
    .code-partie .valeur-code {
      font-family: 'Playfair Display', serif;
      font-size: 2.2rem;
      font-weight: 700;
      color: var(--or);
      letter-spacing: 0.2em;
    }
    .code-partie .attente {
      font-size: 0.78rem;
      margin-top: 10px;
      opacity: 0.6;
    }
    .spinner {
      display: inline-block;
      width: 14px; height: 14px;
      border: 2px solid rgba(232,184,75,0.3);
      border-top-color: var(--or);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      vertical-align: middle;
      margin-right: 6px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    #msg-erreur {
      margin-top: 12px;
      font-size: 0.8rem;
      color: #E74C3C;
      text-align: center;
      min-height: 18px;
    }

    .info-tech {
      margin-top: 28px;
      font-size: 0.72rem;
      opacity: 0.3;
      text-align: center;
      line-height: 1.6;
    }

    /* Parties disponibles */
    .parties-liste {
      margin-top: 14px;
      max-height: 180px;
      overflow-y: auto;
    }
    .partie-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 9px 12px;
      border-radius: 8px;
      background: rgba(0,0,0,0.2);
      margin-bottom: 6px;
      font-size: 0.82rem;
      cursor: pointer;
      border: 1px solid transparent;
      transition: border-color 0.2s;
    }
    .partie-item:hover { border-color: rgba(232,184,75,0.4); }
    .partie-item .code { font-weight: 700; color: var(--or); letter-spacing: 0.1em; }
    .partie-item .attente-badge {
      font-size: 0.68rem;
      padding: 2px 8px;
      border-radius: 10px;
      background: rgba(39,174,96,0.2);
      color: #27AE60;
      border: 1px solid rgba(39,174,96,0.3);
    }
    footer {
      color: rgba(245,230,200,0.5);
    }
  
    /* ── Bouton Règles ── */
    .btn-regles {
      display: block;
      margin: 16px auto 0;
      background: transparent;
      border: 1.5px solid rgba(232,184,75,0.35);
      color: rgba(245,230,200,0.65);
      font-family: 'Inter', sans-serif;
      font-size: 0.78rem;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 8px 22px;
      border-radius: 8px;
      cursor: pointer;
      transition: border-color 0.2s, color 0.2s;
    }
    .btn-regles:hover { border-color: var(--or); color: var(--or); }

    /* ── Modale Règles ── */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(20,8,2,0.85);
      z-index:100; align-items:center; justify-content:center; padding:20px; }
    .modal-overlay.visible { display:flex; }
    .modal { background:linear-gradient(160deg,#5C3317,#2C1A0E); border:2px solid var(--or);
      border-radius:18px; padding:30px 32px; max-width:480px; width:100%;
      box-shadow:0 20px 60px rgba(0,0,0,0.7); max-height:85vh; overflow-y:auto; }
    .modal h2 { font-family:'Playfair Display',serif; font-size:1.6rem; color:var(--or);
      margin-bottom:14px; text-align:center; }
    .modal p, .modal li { font-size:0.84rem; line-height:1.75;
      color:rgba(245,230,200,0.85); margin-bottom:8px; }
    .modal ul { padding-left:20px; margin-bottom:12px; }
    .modal .section-titre { font-weight:700; color:var(--or); margin-top:14px;
      margin-bottom:4px; font-size:0.86rem; letter-spacing:0.05em; }
    .modal-btn { margin-top:18px; background:var(--or); color:#2C1A0E; border:none;
      padding:10px 28px; border-radius:8px; font-weight:700; font-size:0.82rem;
      cursor:pointer; display:block; margin-left:auto; margin-right:auto; }
  
  @media (max-width: 480px) {
    #pwa-banner {
      bottom: 0 !important;
      right: 0 !important;
      left: 0 !important;
      max-width: 100% !important;
      width: 100% !important;
      border-radius: 0 !important;
      border-left: none !important;
      border-right: none !important;
      border-bottom: none !important;
    }
  }
  </style>
</head>
<body>

  <script>
  /* ── Tuer tous les anciens Service Workers au chargement ── */
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(regs => {
      regs.forEach(reg => {
        reg.unregister();
        console.log('[SW] Désinstallé:', reg.scope);
      });
    });
    // Vider tous les caches
    if ('caches' in window) {
      caches.keys().then(keys => {
        keys.forEach(k => { caches.delete(k); console.log('[Cache] Supprimé:', k); });
      });
    }
  }
  </script>

<header>
  <h1>SONGO</h1>
  <p>Songo · Multijoueur · Ajax</p>
</header>

<div class="lobby">
  <div class="tabs">
    <button class="tab active" id="tab-creer"   onclick="afficherOnglet('creer')">Créer une partie</button>
    <button class="tab"        id="tab-rejoindre" onclick="afficherOnglet('rejoindre')">Rejoindre</button>
  </div>

  <!-- ── CRÉER ── -->
  <div class="panneau visible" id="panneau-creer">
    <label for="nom-createur">Votre pseudo</label>
    <input type="text" id="nom-createur" placeholder="Ex: Joueur 1" maxlength="20"/>

    <button class="btn-principal" id="btn-creer" onclick="creerPartie()">
      Créer la partie
    </button>

    <div class="code-partie" id="bloc-code">
      <div class="label-code">Code à partager</div>
      <div class="valeur-code" id="affichage-code">—</div>
      <div class="attente">
        <span class="spinner"></span>
        En attente du deuxième joueur…
      </div>
    </div>
  </div>

  <!-- ── REJOINDRE ── -->
  <div class="panneau" id="panneau-rejoindre">
    <label for="nom-joueur2">Votre pseudo</label>
    <input type="text" id="nom-joueur2" placeholder="Ex: Joueur 2" maxlength="20"/>

    <label for="code-input">Code de la partie</label>
    <input type="text" id="code-input" placeholder="Ex: A3F7" maxlength="6" style="text-transform:uppercase; letter-spacing:0.2em; font-size:1.2rem; text-align:center;"/>

    <button class="btn-principal" id="btn-rejoindre-btn" onclick="rejoindrePartie()">
      Rejoindre
    </button>

    <div style="margin-top:20px;">
      <div style="font-size:0.7rem; opacity:0.45; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:8px;">
        Ou choisir parmi les parties en attente
      </div>
      <div class="parties-liste" id="parties-liste"></div>
    </div>
  </div>

  <div id="msg-erreur"></div>
</div>


<!-- Bouton Règles -->
<button class="btn-regles" onclick="ouvrirRegles()">? Règles du jeu</button>

<!-- Modale Règles -->
<div class="modal-overlay" id="modal-regles">
  <div class="modal">
    <h2>Règles du Songo</h2>
    <p class="section-titre">🎯 Objectif</p>
    <p>Récolter au moins <strong>40 graines</strong> sur les 70 du plateau.</p>
    <p class="section-titre">🏁 Mise en place</p>
    <p>2 rangées de 7 cases, 5 graines par case. Joueur Sud commence toujours.</p>
    <p class="section-titre">▶ Mécanique</p>
    <p>Choisissez une case de votre rangée. Prenez toutes ses graines et semez-les une à une : droite→gauche dans votre rangée, puis gauche→droite chez l'adversaire (en boucle).</p>
    <p class="section-titre">✂ Récoltes</p>
    <ul>
      <li>Uniquement dans le camp adverse (sauf case 1 adverse).</li>
      <li>Prise si la dernière graine tombe dans une case adverse à 1, 2 ou 3 graines → vous prenez 2, 3 ou 4.</li>
      <li>Prise en chaîne sur les cases précédentes remplissant la condition.</li>
    </ul>
    <p class="section-titre">🤝 Solidarité</p>
    <p>Si le camp adverse est vide, vous devez envoyer ≥ 7 graines chez lui.</p>
    <p class="section-titre">⛔ Interdits</p>
    <ul>
      <li>Ne pas semer 1 ou 2 graines chez l'adversaire via sa case 7.</li>
      <li>Ne pas vider complètement le camp adverse.</li>
    </ul>
    <p class="section-titre">🏁 Fin de partie</p>
    <ul>
      <li>Un joueur atteint ≥ 40 graines récoltées.</li>
      <li>Moins de 10 graines sur le plateau.</li>
      <li>Solidarité impossible.</li>
    </ul>
    <button class="modal-btn" onclick="fermerRegles()">Fermer</button>
  </div>
</div>

<div class="info-tech">
  Version 2 — PHP · Ajax · JSON · XAMPP<br/>
  Polling toutes les 2 secondes
</div>

<script>
/* ══════════════════════════════════════════════
   LOBBY — index.php
   ══════════════════════════════════════════════ */

let codePartieEnCours = null;
let pollingLobby = null;

function afficherOnglet(onglet) {
  document.getElementById('panneau-creer').classList.toggle('visible',      onglet === 'creer');
  document.getElementById('panneau-rejoindre').classList.toggle('visible',  onglet === 'rejoindre');
  document.getElementById('tab-creer').classList.toggle('active',           onglet === 'creer');
  document.getElementById('tab-rejoindre').classList.toggle('active',       onglet === 'rejoindre');
  if (onglet === 'rejoindre') chargerPartiesDisponibles();
}

/* ── Créer une partie ── */
function creerPartie() {
  const nom = document.getElementById('nom-createur').value.trim();
  if (!nom) { afficherErreur('Veuillez entrer votre pseudo.'); return; }

  document.getElementById('btn-creer').disabled = true;

  ajax('creer_partie', { nom }, function(data) {
    if (data.succes) {
      codePartieEnCours = data.code;
      document.getElementById('affichage-code').textContent = data.code;
      document.getElementById('bloc-code').style.display = 'block';
      afficherErreur('');
      // Polling : attendre que j2 rejoigne
      pollingLobby = setInterval(() => verifierDebutPartie(data.code, data.partie_id), 2000);
    } else {
      afficherErreur(data.message || 'Erreur lors de la création.');
      document.getElementById('btn-creer').disabled = false;
    }
  });
}

function verifierDebutPartie(code, partieId) {
  ajax('etat_partie', { partie_id: partieId }, function(data) {
    if (data.succes && data.etat && data.etat.statut === 'en_cours') {
      clearInterval(pollingLobby);
      // Rediriger vers le jeu (rôle = sud = joueur 1 = créateur)
      window.location.href = `jeu.php?partie_id=${partieId}&role=sud&nom=${encodeURIComponent(document.getElementById('nom-createur').value.trim())}`;
    }
  });
}

/* ── Rejoindre une partie ── */
function rejoindrePartie(codeForce) {
  const nom  = document.getElementById('nom-joueur2').value.trim();
  const code = (codeForce || document.getElementById('code-input').value.trim()).toUpperCase();
  if (!nom)  { afficherErreur('Veuillez entrer votre pseudo.'); return; }
  if (!code) { afficherErreur('Veuillez entrer le code de la partie.'); return; }

  document.getElementById('btn-rejoindre-btn').disabled = true;

  ajax('rejoindre_partie', { nom, code }, function(data) {
    if (data.succes) {
      afficherErreur('');
      window.location.href = `jeu.php?partie_id=${data.partie_id}&role=nord&nom=${encodeURIComponent(nom)}`;
    } else {
      afficherErreur(data.message || 'Impossible de rejoindre.');
      document.getElementById('btn-rejoindre-btn').disabled = false;
    }
  });
}

/* ── Parties disponibles ── */
function chargerPartiesDisponibles() {
  ajax('lister_parties', {}, function(data) {
    const liste = document.getElementById('parties-liste');
    liste.innerHTML = '';
    if (data.parties && data.parties.length > 0) {
      data.parties.forEach(p => {
        const div = document.createElement('div');
        div.className = 'partie-item';
        div.innerHTML = `
          <span><span class="code">${p.code}</span> — ${escHtml(p.joueur_sud)}</span>
          <span class="attente-badge">En attente</span>
        `;
        div.onclick = () => {
          document.getElementById('code-input').value = p.code;
          rejoindrePartie(p.code);
        };
        liste.appendChild(div);
      });
    } else {
      liste.innerHTML = '<div style="opacity:0.4; font-size:0.78rem; text-align:center; padding:12px;">Aucune partie en attente</div>';
    }
  });
}

/* ── Utilitaires ── */
function ajax(action, params, callback) {
  const body = new URLSearchParams({ action, ...params });
  fetch('./ajax.php', { method: 'POST', body })
    .then(r => r.json())
    .then(callback)
    .catch(e => { afficherErreur('Erreur réseau : ' + e.message); });
}

function afficherErreur(msg) {
  document.getElementById('msg-erreur').textContent = msg;
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function ouvrirRegles() {
  document.getElementById('modal-regles').classList.add('visible');
}
function fermerRegles() {
  document.getElementById('modal-regles').classList.remove('visible');
}
// Fermer en cliquant en dehors
document.getElementById('modal-regles').addEventListener('click', function(e) {
  if (e.target === this) fermerRegles();
});
</script>

  <!-- ══ Bannière installation PWA ══ -->
  <div id="pwa-banner" style="display:none; position:fixed; z-index:999; font-family:'Inter',sans-serif;
    background:linear-gradient(135deg,#5C3317,#2C1A0E); border:1.5px solid #E8B84B;
    padding:12px 16px; align-items:center; gap:12px;
    /* Desktop : coin bas droite, largeur auto */
    bottom:20px; right:20px; left:auto;
    border-radius:14px;
    box-shadow:0 8px 32px rgba(0,0,0,0.5);
    max-width:320px; width:calc(100% - 40px);">
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

  <footer>
    <br>
    <br>
    <br>
    © 2026 SOH TANKOU Joël Valdo - Créateur du jeu Songo
  </footer>

</body>
</html>
