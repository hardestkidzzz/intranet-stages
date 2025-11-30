<?php
/**
 * Génération de la Convention de Stage en HTML
 * Document officiel pour signature par toutes les parties
 * 
 * @package IntranetStages
 * @author Projet BTS SIO
 */

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['admin', 'enseignant', 'etudiant']);
require_once __DIR__ . '/db.php';

// Vérification de l'ID du stage
if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
    die('Stage invalide.');
}
$stageId = (int)$_GET['id'];

// Récupération des données complètes du stage
$sql = "
    SELECT 
        s.*,
        ue.nom AS etu_nom, ue.prenom AS etu_prenom, ue.email AS etu_email, ue.telephone AS etu_tel,
        e.nom AS entreprise_nom, e.siret, e.adresse AS entreprise_adresse, 
        e.site_web, e.telephone AS entreprise_tel, e.email AS entreprise_email,
        ut.nom AS tuteur_ens_nom, ut.prenom AS tuteur_ens_prenom, ut.email AS tuteur_ens_email,
        te.nom AS tuteur_ent_nom, te.prenom AS tuteur_ent_prenom, te.email AS tuteur_ent_email
    FROM stages s
    JOIN users ue ON ue.id = s.etudiant_user_id
    JOIN entreprises e ON e.id = s.entreprise_id
    LEFT JOIN users ut ON ut.id = s.tuteur_enseignant_user_id
    LEFT JOIN users te ON te.id = s.tuteur_entreprise_user_id
    WHERE s.id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$stageId]);
$stage = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stage) {
    die('Stage introuvable.');
}

// Contrôle d'accès
$role = $_SESSION['role'] ?? '';
$user_id = (int)($_SESSION['user_id'] ?? 0);
$allowed = false;

if (in_array($role, ['admin', 'enseignant'])) {
    $allowed = true;
} elseif ($role === 'etudiant' && (int)$stage['etudiant_user_id'] === $user_id) {
    $allowed = true;
}

if (!$allowed) {
    die('Accès refusé.');
}

// Calcul de la durée
$dateDebut = new DateTime($stage['date_debut']);
$dateFin = new DateTime($stage['date_fin']);
$dureeJours = $dateDebut->diff($dateFin)->days;
$dureeSemaines = ceil($dureeJours / 7);

// Informations établissement (à personnaliser)
$etablissement = [
    'nom' => 'Lycée / Centre de Formation',
    'adresse' => '123 Rue de l\'Éducation, 75000 Paris',
    'telephone' => '01 23 45 67 89',
    'email' => 'contact@etablissement.fr',
    'directeur' => 'M. / Mme Directeur(trice)'
];

// Mode impression ?
$print = isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convention de Stage - <?= htmlspecialchars($stage['etu_prenom'] . ' ' . $stage['etu_nom']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Open+Sans:wght@400;600&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Open Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #1a1a2e;
            background: #f8f9fa;
            padding: 20px;
        }
        
        .convention {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 25mm 20mm;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .header h1 {
            font-family: 'Libre Baskerville', Georgia, serif;
            font-size: 24pt;
            color: #2c3e50;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .header .subtitle {
            font-size: 12pt;
            color: #7f8c8d;
        }
        
        .header .annee {
            font-size: 14pt;
            font-weight: 600;
            color: #3498db;
            margin-top: 10px;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-family: 'Libre Baskerville', Georgia, serif;
            font-size: 13pt;
            font-weight: 700;
            color: #2c3e50;
            background: linear-gradient(90deg, #ecf0f1 0%, transparent 100%);
            padding: 8px 15px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .info-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
        }
        
        .info-box h4 {
            font-size: 10pt;
            text-transform: uppercase;
            color: #7f8c8d;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        
        .info-box p {
            margin-bottom: 5px;
        }
        
        .info-box .name {
            font-size: 12pt;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .info-box .detail {
            font-size: 10pt;
            color: #5d6d7e;
        }
        
        .stage-details {
            background: #e8f4f8;
            border: 2px solid #3498db;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .stage-details h3 {
            color: #2980b9;
            margin-bottom: 15px;
            font-size: 14pt;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
            align-items: baseline;
        }
        
        .detail-label {
            font-weight: 600;
            color: #2c3e50;
            min-width: 150px;
        }
        
        .detail-value {
            color: #34495e;
        }
        
        .articles {
            margin: 25px 0;
        }
        
        .article {
            margin-bottom: 20px;
        }
        
        .article h4 {
            font-size: 11pt;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .article p {
            text-align: justify;
            color: #5d6d7e;
            font-size: 10pt;
        }
        
        .signatures {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        
        .signatures-title {
            text-align: center;
            font-weight: 600;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        
        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .signature-box {
            border: 1px solid #bdc3c7;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            min-height: 150px;
        }
        
        .signature-box h5 {
            font-size: 10pt;
            color: #7f8c8d;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .signature-box .name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 60px;
        }
        
        .signature-box .date-line {
            font-size: 9pt;
            color: #95a5a6;
            border-top: 1px solid #bdc3c7;
            padding-top: 10px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9pt;
            color: #95a5a6;
            border-top: 1px solid #ecf0f1;
            padding-top: 15px;
        }
        
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .btn-print {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-print:hover {
            background: #2980b9;
        }
        
        .btn-back {
            background: #95a5a6;
        }
        
        .btn-back:hover {
            background: #7f8c8d;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .convention {
                box-shadow: none;
                padding: 15mm;
            }
            
            .no-print {
                display: none;
            }
            
            .signatures {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimer / PDF</button>
        <a href="<?= BASE_URL ?>index.php" class="btn-print btn-back">← Retour</a>
    </div>

    <div class="convention">
        <div class="header">
            <h1>Convention de Stage</h1>
            <div class="subtitle">Formation professionnelle en milieu d'entreprise</div>
            <div class="annee">Année scolaire <?= date('Y', strtotime($stage['date_debut'])) ?> - <?= date('Y', strtotime($stage['date_debut'])) + 1 ?></div>
        </div>

        <div class="section">
            <div class="section-title">Article 1 — Parties concernées</div>
            <div class="info-grid">
                <div class="info-box">
                    <h4>🏫 Établissement d'enseignement</h4>
                    <p class="name"><?= htmlspecialchars($etablissement['nom']) ?></p>
                    <p class="detail"><?= htmlspecialchars($etablissement['adresse']) ?></p>
                    <p class="detail">📞 <?= htmlspecialchars($etablissement['telephone']) ?></p>
                    <p class="detail">✉️ <?= htmlspecialchars($etablissement['email']) ?></p>
                    <p class="detail" style="margin-top: 8px;"><strong>Représenté par :</strong> <?= htmlspecialchars($etablissement['directeur']) ?></p>
                </div>
                
                <div class="info-box">
                    <h4>🏢 Entreprise d'accueil</h4>
                    <p class="name"><?= htmlspecialchars($stage['entreprise_nom']) ?></p>
                    <?php if (!empty($stage['siret'])): ?>
                        <p class="detail">SIRET : <?= htmlspecialchars($stage['siret']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($stage['entreprise_adresse'])): ?>
                        <p class="detail"><?= htmlspecialchars($stage['entreprise_adresse']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($stage['entreprise_tel'])): ?>
                        <p class="detail">📞 <?= htmlspecialchars($stage['entreprise_tel']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($stage['site_web'])): ?>
                        <p class="detail">🌐 <?= htmlspecialchars($stage['site_web']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Article 2 — Le/La Stagiaire</div>
            <div class="info-box" style="max-width: 100%;">
                <h4>👤 Informations du stagiaire</h4>
                <p class="name"><?= htmlspecialchars($stage['etu_prenom'] . ' ' . $stage['etu_nom']) ?></p>
                <p class="detail">✉️ <?= htmlspecialchars($stage['etu_email']) ?></p>
                <?php if (!empty($stage['etu_tel'])): ?>
                    <p class="detail">📞 <?= htmlspecialchars($stage['etu_tel']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="stage-details">
            <h3>📋 Détails du stage</h3>
            <div class="detail-row">
                <span class="detail-label">Sujet / Mission :</span>
                <span class="detail-value"><?= htmlspecialchars($stage['sujet'] ?? 'À définir') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date de début :</span>
                <span class="detail-value"><?= date('d/m/Y', strtotime($stage['date_debut'])) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date de fin :</span>
                <span class="detail-value"><?= date('d/m/Y', strtotime($stage['date_fin'])) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Durée :</span>
                <span class="detail-value"><?= $dureeJours ?> jours (<?= $dureeSemaines ?> semaines)</span>
            </div>
            <?php if (!empty($stage['tuteur_ens_nom'])): ?>
            <div class="detail-row">
                <span class="detail-label">Tuteur enseignant :</span>
                <span class="detail-value"><?= htmlspecialchars($stage['tuteur_ens_prenom'] . ' ' . $stage['tuteur_ens_nom']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($stage['tuteur_ent_nom'])): ?>
            <div class="detail-row">
                <span class="detail-label">Tuteur entreprise :</span>
                <span class="detail-value"><?= htmlspecialchars($stage['tuteur_ent_prenom'] . ' ' . $stage['tuteur_ent_nom']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="articles">
            <div class="section-title">Clauses et conditions</div>
            
            <div class="article">
                <h4>Article 3 — Objet de la convention</h4>
                <p>La présente convention règle les rapports entre l'établissement d'enseignement, l'entreprise d'accueil et le/la stagiaire pour la réalisation d'un stage s'inscrivant dans le cadre de la formation.</p>
            </div>
            
            <div class="article">
                <h4>Article 4 — Durée et horaires</h4>
                <p>Le stage se déroule du <?= date('d/m/Y', strtotime($stage['date_debut'])) ?> au <?= date('d/m/Y', strtotime($stage['date_fin'])) ?>, soit <?= $dureeSemaines ?> semaines. Les horaires de travail sont définis par l'entreprise d'accueil conformément à la législation en vigueur.</p>
            </div>
            
            <div class="article">
                <h4>Article 5 — Encadrement</h4>
                <p>Le/la stagiaire est suivi(e) par un tuteur désigné au sein de l'entreprise et par un enseignant référent de l'établissement. Ces tuteurs assurent le suivi pédagogique et l'évaluation du stagiaire.</p>
            </div>
            
            <div class="article">
                <h4>Article 6 — Gratification</h4>
                <p>Conformément à la réglementation, une gratification est due au/à la stagiaire pour tout stage d'une durée supérieure à 2 mois. Le montant et les modalités de versement sont définis par l'entreprise d'accueil.</p>
            </div>
            
            <div class="article">
                <h4>Article 7 — Responsabilités et assurances</h4>
                <p>L'établissement d'enseignement et l'entreprise s'engagent à souscrire les assurances nécessaires. Le/la stagiaire reste soumis(e) à la discipline de l'établissement et aux règles de l'entreprise d'accueil.</p>
            </div>
            
            <div class="article">
                <h4>Article 8 — Confidentialité</h4>
                <p>Le/la stagiaire s'engage à respecter la confidentialité des informations auxquelles il/elle pourrait avoir accès pendant la durée du stage.</p>
            </div>
        </div>

        <div class="signatures">
            <div class="signatures-title">Fait en trois exemplaires originaux</div>
            <div class="signatures-grid">
                <div class="signature-box">
                    <h5>L'établissement</h5>
                    <div class="name"><?= htmlspecialchars($etablissement['directeur']) ?></div>
                    <div class="date-line">Date et signature</div>
                </div>
                
                <div class="signature-box">
                    <h5>L'entreprise</h5>
                    <div class="name"><?= htmlspecialchars($stage['entreprise_nom']) ?></div>
                    <div class="date-line">Date et signature</div>
                </div>
                
                <div class="signature-box">
                    <h5>Le/La Stagiaire</h5>
                    <div class="name"><?= htmlspecialchars($stage['etu_prenom'] . ' ' . $stage['etu_nom']) ?></div>
                    <div class="date-line">Date et signature</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Convention générée le <?= date('d/m/Y à H:i') ?> — Stage #<?= $stageId ?></p>
            <p>Ce document doit être signé par toutes les parties avant le début du stage.</p>
        </div>
    </div>
</body>
</html>

