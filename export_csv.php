<?php
/**
 * Export CSV des stages
 * Permet d'exporter les données des stages au format CSV pour Excel
 * 
 * @package IntranetStages
 * @author Projet BTS SIO
 */

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['admin', 'enseignant']);
require_once __DIR__ . '/db.php';

// =====================
// Récupération des filtres (mêmes que index.php)
// =====================
$q            = trim($_GET['q'] ?? '');
$statut       = $_GET['statut'] ?? '';
$entrepriseId = ctype_digit($_GET['entreprise'] ?? '') ? (int)$_GET['entreprise'] : 0;
$enseignantId = ctype_digit($_GET['enseignant'] ?? '') ? (int)$_GET['enseignant'] : 0;
$dateMin      = $_GET['date_min'] ?? '';
$dateMax      = $_GET['date_max'] ?? '';

$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'] ?? '';

// =====================
// Construction de la requête
// =====================
$where = [];
$params = [];

// Contrainte par rôle
if ($role === 'enseignant') {
    $where[] = "s.tuteur_enseignant_user_id = ?";
    $params[] = $user_id;
}

// Filtres
if ($q !== '') {
    $where[] = "(CONCAT(ue.prenom,' ',ue.nom) LIKE ? OR e.nom LIKE ? OR s.sujet LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if (in_array($statut, ['préparation','en_cours','terminé','rupture'])) {
    $where[] = "s.statut = ?";
    $params[] = $statut;
}
if ($entrepriseId > 0) {
    $where[] = "s.entreprise_id = ?";
    $params[] = $entrepriseId;
}
if ($enseignantId > 0) {
    $where[] = "s.tuteur_enseignant_user_id = ?";
    $params[] = $enseignantId;
}
if ($dateMin !== '') {
    $where[] = "s.date_debut >= ?";
    $params[] = $dateMin;
}
if ($dateMax !== '') {
    $where[] = "s.date_fin <= ?";
    $params[] = $dateMax;
}

$whereSql = $where ? "WHERE ".implode(" AND ", $where) : "";

// =====================
// Requête des données
// =====================
$sql = "
    SELECT
        s.id,
        ue.nom AS etudiant_nom,
        ue.prenom AS etudiant_prenom,
        ue.email AS etudiant_email,
        e.nom AS entreprise_nom,
        e.siret AS entreprise_siret,
        e.adresse AS entreprise_adresse,
        e.site_web AS entreprise_site,
        s.date_debut,
        s.date_fin,
        DATEDIFF(s.date_fin, s.date_debut) AS duree_jours,
        CEIL(DATEDIFF(s.date_fin, s.date_debut) / 7) AS duree_semaines,
        s.sujet,
        s.statut,
        ut.nom AS tuteur_nom,
        ut.prenom AS tuteur_prenom,
        ut.email AS tuteur_email,
        s.date_creation
    FROM stages s
    JOIN users ue ON ue.id = s.etudiant_user_id
    JOIN entreprises e ON e.id = s.entreprise_id
    LEFT JOIN users ut ON ut.id = s.tuteur_enseignant_user_id
    $whereSql
    ORDER BY s.date_debut DESC, s.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =====================
// Génération du CSV
// =====================
$filename = 'export_stages_' . date('Y-m-d_His') . '.csv';

// Headers HTTP pour le téléchargement
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Ouvrir le flux de sortie
$output = fopen('php://output', 'w');

// BOM UTF-8 pour Excel (reconnaissance automatique de l'encodage)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// En-têtes des colonnes
$headers = [
    'ID',
    'Nom étudiant',
    'Prénom étudiant',
    'Email étudiant',
    'Entreprise',
    'SIRET',
    'Adresse entreprise',
    'Site web',
    'Date début',
    'Date fin',
    'Durée (jours)',
    'Durée (semaines)',
    'Sujet',
    'Statut',
    'Tuteur enseignant (nom)',
    'Tuteur enseignant (prénom)',
    'Email tuteur',
    'Date création'
];

// Écrire les en-têtes avec séparateur point-virgule pour Excel FR
fputcsv($output, $headers, ';');

// Écrire les données
foreach ($stages as $stage) {
    $row = [
        $stage['id'],
        $stage['etudiant_nom'],
        $stage['etudiant_prenom'],
        $stage['etudiant_email'],
        $stage['entreprise_nom'],
        $stage['entreprise_siret'] ?? '',
        $stage['entreprise_adresse'] ?? '',
        $stage['entreprise_site'] ?? '',
        $stage['date_debut'],
        $stage['date_fin'],
        $stage['duree_jours'],
        $stage['duree_semaines'],
        $stage['sujet'] ?? '',
        ucfirst($stage['statut']),
        $stage['tuteur_nom'] ?? '',
        $stage['tuteur_prenom'] ?? '',
        $stage['tuteur_email'] ?? '',
        $stage['date_creation']
    ];
    
    fputcsv($output, $row, ';');
}

fclose($output);
exit;

