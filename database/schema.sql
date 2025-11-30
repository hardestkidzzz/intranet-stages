-- ============================================================================
-- INTRANET GESTION DES STAGES
-- Schéma de Base de Données (MCD/MPD)
-- ============================================================================
-- Auteur: Projet BTS SIO
-- Date: 2024
-- SGBD: MySQL / MariaDB (XAMPP)
-- ============================================================================

-- Suppression des tables existantes (ordre inverse des dépendances)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS evaluations;
DROP TABLE IF EXISTS suivis;
DROP TABLE IF EXISTS candidatures;
DROP TABLE IF EXISTS offres_stage;
DROP TABLE IF EXISTS stages;
DROP TABLE IF EXISTS tuteurs_entreprise;
DROP TABLE IF EXISTS entreprises;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- TABLE: users (Utilisateurs du système)
-- ============================================================================
-- Stocke tous les utilisateurs : étudiants, enseignants, entreprises, admins
-- Le champ 'role' permet de différencier les types d'utilisateurs
-- ============================================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('etudiant', 'enseignant', 'entreprise', 'admin') NOT NULL DEFAULT 'etudiant',
    email VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(20) DEFAULT NULL,
    formation VARCHAR(100) DEFAULT NULL COMMENT 'Formation de l''étudiant (ex: BTS SIO)',
    actif TINYINT(1) NOT NULL DEFAULT 1,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_role (role),
    INDEX idx_email (email),
    INDEX idx_nom_prenom (nom, prenom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Table des utilisateurs (étudiants, enseignants, tuteurs entreprise, admins)';

-- ============================================================================
-- TABLE: entreprises (Entreprises partenaires)
-- ============================================================================
-- Stocke les informations des entreprises accueillant des stagiaires
-- Relation 1-N avec offres_stage et stages
-- ============================================================================
CREATE TABLE entreprises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    siret VARCHAR(14) DEFAULT NULL COMMENT 'Numéro SIRET (14 chiffres)',
    adresse TEXT DEFAULT NULL,
    code_postal VARCHAR(10) DEFAULT NULL,
    ville VARCHAR(100) DEFAULT NULL,
    telephone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    site_web VARCHAR(255) DEFAULT NULL,
    secteur_activite VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_nom (nom),
    INDEX idx_ville (ville),
    INDEX idx_siret (siret)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Table des entreprises partenaires pour les stages';

-- ============================================================================
-- TABLE: tuteurs_entreprise (Liaison tuteur-entreprise)
-- ============================================================================
-- Table de liaison entre les utilisateurs 'entreprise' et leur entreprise
-- Permet d'associer un tuteur à une entreprise spécifique
-- ============================================================================
CREATE TABLE tuteurs_entreprise (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    entreprise_id INT NOT NULL,
    fonction VARCHAR(100) DEFAULT NULL COMMENT 'Fonction/poste dans l''entreprise',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE CASCADE,
    
    UNIQUE KEY uk_user_entreprise (user_id, entreprise_id),
    INDEX idx_entreprise (entreprise_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Association entre tuteurs et entreprises';

-- ============================================================================
-- TABLE: stages (Stages effectués)
-- ============================================================================
-- Table centrale du système : stocke toutes les informations d'un stage
-- Relations avec : users (étudiant, tuteur enseignant), entreprises
-- ============================================================================
CREATE TABLE stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_user_id INT NOT NULL COMMENT 'FK vers users (étudiant)',
    entreprise_id INT NOT NULL COMMENT 'FK vers entreprises',
    tuteur_enseignant_user_id INT DEFAULT NULL COMMENT 'FK vers users (enseignant)',
    tuteur_entreprise_user_id INT DEFAULT NULL COMMENT 'FK vers users (tuteur entreprise)',
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    sujet TEXT DEFAULT NULL COMMENT 'Sujet/mission du stage',
    description TEXT DEFAULT NULL COMMENT 'Description détaillée',
    statut ENUM('préparation', 'en_cours', 'terminé', 'rupture') NOT NULL DEFAULT 'préparation',
    gratification DECIMAL(10,2) DEFAULT NULL COMMENT 'Montant mensuel en euros',
    horaires_hebdo DECIMAL(4,2) DEFAULT 35.00 COMMENT 'Heures par semaine',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (etudiant_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE RESTRICT,
    FOREIGN KEY (tuteur_enseignant_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (tuteur_entreprise_user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_etudiant (etudiant_user_id),
    INDEX idx_entreprise (entreprise_id),
    INDEX idx_tuteur_ens (tuteur_enseignant_user_id),
    INDEX idx_statut (statut),
    INDEX idx_dates (date_debut, date_fin),
    
    CONSTRAINT chk_dates CHECK (date_debut <= date_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Table des stages avec toutes les informations associées';

-- ============================================================================
-- TABLE: offres_stage (Offres de stage publiées)
-- ============================================================================
-- Offres publiées par les entreprises auxquelles les étudiants peuvent postuler
-- Relation 1-N avec candidatures
-- ============================================================================
CREATE TABLE offres_stage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entreprise_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    competences TEXT DEFAULT NULL COMMENT 'Compétences requises',
    localisation VARCHAR(255) DEFAULT NULL,
    duree_semaines INT DEFAULT NULL,
    gratification DECIMAL(10,2) DEFAULT NULL,
    date_debut_souhaitee DATE DEFAULT NULL,
    statut ENUM('brouillon', 'publiée', 'clôturée', 'pourvue') NOT NULL DEFAULT 'brouillon',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE CASCADE,
    
    INDEX idx_entreprise (entreprise_id),
    INDEX idx_statut (statut),
    INDEX idx_date_creation (date_creation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Offres de stage publiées par les entreprises';

-- ============================================================================
-- TABLE: candidatures (Candidatures des étudiants)
-- ============================================================================
-- Candidatures des étudiants aux offres de stage
-- Stocke les pièces jointes (CV, lettre de motivation)
-- ============================================================================
CREATE TABLE candidatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offre_id INT NOT NULL,
    etudiant_user_id INT NOT NULL,
    message TEXT DEFAULT NULL COMMENT 'Message d''accompagnement',
    cv_path VARCHAR(255) DEFAULT NULL COMMENT 'Chemin vers le CV',
    lm_path VARCHAR(255) DEFAULT NULL COMMENT 'Chemin vers la lettre de motivation',
    statut ENUM('en_attente', 'vue', 'acceptée', 'refusée', 'retirée') NOT NULL DEFAULT 'en_attente',
    date_candidature DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_reponse DATETIME DEFAULT NULL,
    commentaire_reponse TEXT DEFAULT NULL,
    
    FOREIGN KEY (offre_id) REFERENCES offres_stage(id) ON DELETE CASCADE,
    FOREIGN KEY (etudiant_user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY uk_offre_etudiant (offre_id, etudiant_user_id),
    INDEX idx_offre (offre_id),
    INDEX idx_etudiant (etudiant_user_id),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Candidatures des étudiants aux offres de stage';

-- ============================================================================
-- TABLE: suivis (Suivi des stages)
-- ============================================================================
-- Journal de suivi des stages : points, visites, documents, notes
-- Permet de tracer toutes les interactions pendant le stage
-- ============================================================================
CREATE TABLE suivis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stage_id INT NOT NULL,
    auteur_user_id INT NOT NULL COMMENT 'Qui a créé cette entrée',
    type ENUM('point', 'visite', 'rapport', 'note', 'doc') NOT NULL DEFAULT 'point',
    contenu TEXT DEFAULT NULL,
    fichier_path VARCHAR(255) DEFAULT NULL COMMENT 'Pièce jointe éventuelle',
    date_suivi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (stage_id) REFERENCES stages(id) ON DELETE CASCADE,
    FOREIGN KEY (auteur_user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_stage (stage_id),
    INDEX idx_auteur (auteur_user_id),
    INDEX idx_type (type),
    INDEX idx_date (date_suivi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Journal de suivi des stages (points, visites, documents)';

-- ============================================================================
-- TABLE: evaluations (Évaluations finales)
-- ============================================================================
-- Évaluation finale du stagiaire par le tuteur enseignant
-- Notes et commentaires pour le rapport de stage
-- ============================================================================
CREATE TABLE evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stage_id INT NOT NULL UNIQUE,
    note_technique INT DEFAULT NULL COMMENT 'Note technique /20',
    note_softskills INT DEFAULT NULL COMMENT 'Note savoir-être /20',
    note_dossier INT DEFAULT NULL COMMENT 'Note dossier/rapport /20',
    commentaire TEXT DEFAULT NULL,
    date_eval DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (stage_id) REFERENCES stages(id) ON DELETE CASCADE,
    
    INDEX idx_stage (stage_id),
    
    CONSTRAINT chk_note_tech CHECK (note_technique IS NULL OR (note_technique >= 0 AND note_technique <= 20)),
    CONSTRAINT chk_note_soft CHECK (note_softskills IS NULL OR (note_softskills >= 0 AND note_softskills <= 20)),
    CONSTRAINT chk_note_doss CHECK (note_dossier IS NULL OR (note_dossier >= 0 AND note_dossier <= 20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Évaluations finales des stages';

-- ============================================================================
-- DONNÉES DE TEST (optionnel)
-- ============================================================================

-- Utilisateur admin par défaut (mot de passe: admin123)
INSERT INTO users (role, email, mot_de_passe, nom, prenom) VALUES
('admin', 'admin@intranet.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur', 'Système');

-- Quelques utilisateurs de test
INSERT INTO users (role, email, mot_de_passe, nom, prenom, formation) VALUES
('etudiant', 'etudiant@test.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dupont', 'Marie', 'BTS SIO SLAM'),
('etudiant', 'etudiant2@test.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Martin', 'Pierre', 'BTS SIO SISR'),
('enseignant', 'prof@test.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bernard', 'Jean', NULL),
('entreprise', 'tuteur@test.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Leroy', 'Sophie', NULL);

-- Entreprises de test
INSERT INTO entreprises (nom, siret, adresse, ville, code_postal, telephone, email, site_web, secteur_activite) VALUES
('Tech Solutions SAS', '12345678901234', '10 Rue de l''Innovation', 'Paris', '75001', '01 23 45 67 89', 'contact@techsolutions.fr', 'https://techsolutions.fr', 'Informatique'),
('Digital Agency', '98765432109876', '25 Avenue du Digital', 'Lyon', '69001', '04 56 78 90 12', 'hello@digital-agency.fr', 'https://digital-agency.fr', 'Marketing Digital'),
('StartUp Factory', '45678901234567', '5 Place de la Startup', 'Bordeaux', '33000', '05 67 89 01 23', 'info@startup-factory.com', NULL, 'Incubateur');

-- Liaison tuteur-entreprise
INSERT INTO tuteurs_entreprise (user_id, entreprise_id, fonction) VALUES
(5, 1, 'Responsable technique');

-- ============================================================================
-- FIN DU SCRIPT
-- ============================================================================

