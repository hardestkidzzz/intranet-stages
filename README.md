# 📚 Intranet Gestion des Stages

**Application web de gestion des stages** développée en PHP/MySQL pour un établissement d'enseignement.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap&logoColor=white)

---

## Table des matières

- [Présentation](#-présentation)
- [Fonctionnalités](#-fonctionnalités)
- [Architecture](#-architecture)
- [Installation](#-installation)
- [Structure du projet](#-structure-du-projet)
- [Conception (MCD/MPD)](#-conception-mcdmpd)
- [Technologies](#-technologies)
- [Captures d'écran](#-captures-décran)

---

## Présentation

Cette application permet de gérer l'ensemble du processus de stage dans un établissement d'enseignement :
- Publication et gestion des offres de stage par les entreprises
- Candidature des étudiants aux offres
- Suivi des stages par les tuteurs enseignants
- Génération de conventions de stage
- Export des données au format CSV

### Rôles utilisateurs

| Rôle | Permissions |
|------|-------------|
| **Admin** | Gestion complète (utilisateurs, entreprises, affectations, statistiques) |
| **Enseignant** | Suivi des étudiants assignés, évaluations, création de stages |
| **Entreprise** | Publication d'offres, gestion des candidatures |
| **Étudiant** | Consultation des offres, candidature, suivi de son stage |

---

## Fonctionnalités

### Authentification
- Connexion sécurisée avec gestion des sessions
- Hachage des mots de passe
- Contrôle d'accès par rôle

### Tableau de bord
- KPIs en temps réel (stages en cours, terminés, ruptures)
- Filtres multi-critères (statut, entreprise, enseignant, dates)
- Pagination des résultats

### Gestion des entreprises
- CRUD complet des entreprises partenaires
- Informations : SIRET, adresse, site web, secteur d'activité
- Historique des stages par entreprise

### Gestion des étudiants
- Profil étudiant avec formation
- Historique des candidatures
- Accès à son stage en cours

### Offres de stage
- Publication par les entreprises
- Recherche multi-critères (localisation, durée, compétences)
- Système de candidature avec CV et lettre de motivation

### Suivi des stages
- Timeline des points de suivi
- Upload de documents (rapports, conventions)
- Évaluation finale (notes techniques, soft skills, dossier)

### Convention de stage
- **Génération automatique en HTML**
- Document officiel prêt à imprimer
- Toutes les informations légales incluses

### Export CSV
- Export des stages pour Excel
- Filtres conservés à l'export
- Compatible Excel

---

## Architecture

### Pattern utilisé : **DAO (Data Access Object)**

L'application utilise le pattern DAO pour séparer la logique métier de l'accès aux données :

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Controllers   │────▶│      DAO        │────▶│    Database     │
│   (PHP pages)   │     │   (PDO/MySQL)   │     │    (MySQL)      │
└─────────────────┘     └─────────────────┘     └─────────────────┘
         │                       │
         │                       │
         ▼                       ▼
┌─────────────────┐     ┌─────────────────┐
│     Views       │     │     Models      │
│   (HTML/PHP)    │     │  (Classes PHP)  │
└─────────────────┘     └─────────────────┘
```

### Classes principales

| Classe | Description |
|--------|-------------|
| `Eleve` | Modèle représentant un étudiant |
| `Entreprise` | Modèle représentant une entreprise |
| `Stage` | Modèle représentant un stage |
| `Tuteur` | Modèle représentant un tuteur (enseignant ou entreprise) |
| `EleveDAO` | Accès aux données des étudiants |
| `EntrepriseDAO` | Accès aux données des entreprises |
| `StageDAO` | Accès aux données des stages |
| `TuteurDAO` | Accès aux données des tuteurs |

---

## Installation

### Prérequis

- **XAMPP** ou **WAMP** (Apache + MySQL + PHP 8.0+)
- **phpMyAdmin** pour la gestion de la base de données
- Navigateur web assez moderne

### Étapes d'installation

1. **Cloner/Copier le projet** dans le dossier `htdocs` de XAMPP :
   ```
   C:\xampp\htdocs\intranet-stages\
   ```

2. **Créer la base de données** :
   - Ouvrir phpMyAdmin (http://localhost/phpmyadmin)
   - Créer une base `intranet_stages`
   - Importer le fichier `database/schema.sql`

3. **Configurer la connexion** dans `db.php` :
   ```php
   $host = '127.0.0.1';
   $port = '3306';  // ou autre selon votre config pour ma part c'était 3310
   $dbname = 'intranet_stages';
   $username = 'root';
   $password = ''; // pas de mdp 
   ```

4. **Démarrer XAMPP** (Apache + MySQL)

5. **Accéder à l'application** :
   ```
   http://localhost/intranet-stages/
   ```

### Comptes de test

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| admin@intranet.local | admin123 | Admin |
| etudiant@test.fr | admin123 | Étudiant |
| prof@test.fr | admin123 | Enseignant |
| tuteur@test.fr | admin123 | Entreprise |

---

## Structure du projet

```
intranet-stages/
├── 📂 admin/                    # Pages administration
│   ├── affectations.php         # Affectation tuteurs-stages
│   ├── candidatures.php         # Gestion candidatures
│   ├── entreprises.php          # CRUD entreprises
│   ├── stats.php                # Statistiques & graphiques
│   └── users.php                # Gestion utilisateurs
│
├── 📂 auth/                     # Authentification
│   ├── login.php                # Connexion
│   └── logout.php               # Déconnexion
│
├── 📂 classes/                  # Classes PHP (POO)
│   ├── Eleve.php                # Modèle Étudiant
│   ├── EleveDAO.php             # DAO Étudiant
│   ├── Entreprise.php           # Modèle Entreprise
│   ├── EntrepriseDAO.php        # DAO Entreprise
│   ├── Stage.php                # Modèle Stage
│   ├── StageDAO.php             # DAO Stage
│   ├── Tuteur.php               # Modèle Tuteur
│   └── TuteurDAO.php            # DAO Tuteur
│
├── 📂 database/                 # Scripts SQL
│   └── schema.sql               # Schéma BDD (MCD/MPD)
│
├── 📂 enseignant/               # Espace enseignant
│   ├── mes_etudiants.php        # Liste des étudiants suivis
│   └── suivi.php                # Suivi d'un stage
│
├── 📂 entreprise/               # Espace entreprise
│   ├── candidatures.php         # Candidatures reçues
│   ├── edit_offre.php           # Édition offre
│   ├── mes_offres.php           # Mes offres publiées
│   └── save_offre.php           # Sauvegarde offre
│
├── 📂 etudiant/                 # Espace étudiant
│   ├── candidature.php          # Formulaire candidature
│   ├── candidatures.php         # Toutes les candidatures
│   ├── mes_candidatures.php     # Mes candidatures
│   ├── mon_stage.php            # Mon stage actuel
│   ├── offres.php               # Liste des offres
│   └── postuler.php             # Action de candidature
│
├── 📂 includes/                 # Fichiers inclus
│   ├── auth.php                 # Fonctions authentification
│   ├── footer.php               # Pied de page
│   ├── header.php               # En-tête & navigation
│   └── init.php                 # Initialisation (session, constantes)
│
├── 📂 stages/                   # Gestion des stages
│   ├── add_stage.php            # Création stage
│   ├── edit_stage.php           # Modification stage
│   └── view_stage.php           # Détail stage
│
├── 📂 uploads/                  # Fichiers uploadés (CV, rapports)
│
├── convention.php               # Génération convention HTML
├── db.php                       # Connexion PDO
├── export_csv.php               # Export CSV
├── index.php                    # Page d'accueil / Dashboard
└── README.md                    # Documentation
```

---

## Conception (MCD/MPD)

### Modèle Conceptuel de Données (MCD)

```
┌──────────────┐         ┌──────────────┐         ┌──────────────┐
│    USERS     │         │    STAGES    │         │ ENTREPRISES  │
├──────────────┤         ├──────────────┤         ├──────────────┤
│ id (PK)      │◄───────│ etudiant_id  │────────▶│ id (PK)      │
│ role         │         │ entreprise_id│         │ nom          │
│ email        │◄───────│ tuteur_ens_id│         │ siret        │
│ mot_de_passe │         │ date_debut   │         │ adresse      │
│ nom          │         │ date_fin     │         │ ville        │
│ prenom       │         │ sujet        │         │ site_web     │
└──────────────┘         │ statut       │         └──────────────┘
       │                 └──────────────┘                │
       │                        │                        │
       │                        │                        │
       ▼                        ▼                        ▼
┌──────────────┐         ┌──────────────┐         ┌──────────────┐
│   SUIVIS     │         │ EVALUATIONS  │         │OFFRES_STAGE  │
├──────────────┤         ├──────────────┤         ├──────────────┤
│ stage_id(FK) │         │ stage_id(FK) │         │entreprise_id │
│ auteur_id    │         │note_technique│         │ titre        │
│ type         │         │note_softskill│         │ description  │
│ contenu      │         │ note_dossier │         │ statut       │
│ fichier_path │         │ commentaire  │         └──────────────┘
└──────────────┘         └──────────────┘                │
                                                         │
                                                         ▼
                                                  ┌──────────────┐
                                                  │CANDIDATURES  │
                                                  ├──────────────┤
                                                  │ offre_id(FK) │
                                                  │ etudiant_id  │
                                                  │ cv_path      │
                                                  │ statut       │
                                                  └──────────────┘
```

### Relations principales

| Relation | Type | Description |
|----------|------|-------------|
| Users → Stages | 1-N | Un étudiant peut avoir plusieurs stages |
| Entreprises → Stages | 1-N | Une entreprise accueille plusieurs stagiaires |
| Users → Stages | 1-N | Un enseignant tutore plusieurs stages |
| Stages → Suivis | 1-N | Un stage a plusieurs entrées de suivi |
| Stages → Evaluations | 1-1 | Un stage a une évaluation finale |
| Entreprises → Offres | 1-N | Une entreprise publie plusieurs offres |
| Offres → Candidatures | 1-N | Une offre reçoit plusieurs candidatures |
| Users → Candidatures | 1-N | Un étudiant peut candidater à plusieurs offres |

---

## Technologies

### Backend
- **PHP 8.0+** - Langage serveur
- **PDO** - Accès base de données (requêtes préparées)
- **MySQL/MariaDB** - Base de données relationnelle

### Frontend
- **Bootstrap 5.3** - Framework CSS
- **Bootstrap Icons** - Icônes
- **Chart.js** - Graphiques statistiques

### Outils
- **XAMPP** - Stack de développement local
- **phpMyAdmin** - Administration MySQL
- **Git** - Gestion de versions

---

## Captures d'écran

### Dashboard principal
- Vue d'ensemble avec KPIs
- Tableau des stages avec filtres

### Convention de stage
- Document HTML généré automatiquement
- Prêt pour impression/PDF

### Export CSV
- Données exportables pour Excel
- Conserve les filtres appliqués

---

## Auteur

Projet réalisé dans le cadre du **BTS SIO** (Services Informatiques aux Organisations).

---

## 📄 Licence

Ce projet est à usage éducatif. Libre de droits pour apprentissage et adaptation.

