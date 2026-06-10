# SONGO v2 — Installation XAMPP
## TP Dr. MESSI — Multijoueur distant (Ajax + PHP + JSON)

---

## Structure des fichiers

```
songo_v2/
├── index.php      ← Lobby (créer / rejoindre une partie)
├── jeu.php        ← Interface de jeu (chaque joueur sur sa machine)
├── ajax.php       ← Backend : toutes les actions Ajax
├── parties/       ← Créé automatiquement (stockage JSON des parties)
└── README.md
```

---

## Installation

1. **Copier** le dossier `songo_v2/` dans `C:\xampp\htdocs\` (Windows)
   ou `/opt/lampp/htdocs/` (Linux)

2. **Démarrer** Apache via XAMPP Control Panel

3. **Accéder** à : `http://localhost/songo_v2/`

---

## Comment jouer en multijoueur

### Joueur 1 (Créateur)
1. Ouvrir `http://localhost/songo_v2/` (ou IP réseau locale)
2. Cliquer **"Créer une partie"**, entrer son pseudo
3. Un code à 4 lettres s'affiche → le communiquer au Joueur 2

### Joueur 2
1. Ouvrir `http://[IP_du_joueur1]/songo_v2/` dans son navigateur
2. Cliquer **"Rejoindre"**, entrer son pseudo + le code reçu

### Sur le même réseau local (Wi-Fi / câble)
- Joueur 1 trouve son IP : `ipconfig` (Windows) ou `ip addr` (Linux)
- Joueur 2 utilise cette IP dans son navigateur

---

## Fonctionnement technique

| Composant | Rôle |
|-----------|------|
| `index.php` | Lobby HTML/CSS/JS, formulaires de création/jonction |
| `jeu.php`   | Plateau de jeu, logique client, polling Ajax |
| `ajax.php`  | API REST légère : traite les actions POST, lit/écrit JSON |
| `parties/*.json` | Un fichier par partie (état complet du jeu) |
| `parties/_index.json` | Index des parties en attente |

### Polling Ajax
- Chaque client interroge `ajax.php?action=etat_partie` **toutes les 2 secondes**
- Quand c'est son tour, le joueur clique une case → requête `jouer_coup`
- Le serveur met à jour le fichier JSON → l'adversaire reçoit le nouvel état au prochain poll

---

## Droits du dossier `parties/`
Le dossier est créé automatiquement par PHP.
Si erreur de permission : `chmod 777 parties/` (Linux)
