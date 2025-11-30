<?php
require_once 'intranet-stages/includes/auth.php'; requireRole(['etudiant']);
require_once 'intranet-stages/db.php';

$user_id = (int)$_SESSION['user_id'];
$page_title = 'Mon stage';

// Charger le stage confirmé de l'étudiant (le plus récent si plusieurs)
$st = $pdo->prepare("
  SELECT s.*, e.nom AS entreprise_nom, u.prenom AS t_prenom, u.nom AS t_nom
  FROM stages s
  JOIN entreprises e ON e.id = s.entreprise_id
  LEFT JOIN users u ON u.id = s.tuteur_entreprise_user_id
  WHERE s.etudiant_user_id = ?
  ORDER BY s.date_creation DESC
  LIMIT 1
");
$st->execute([$user_id]);
$stage = $st->fetch(PDO::FETCH_ASSOC);

$errors=[]; $infos=[];

// Dépôt rapport (créé comme un suivi type 'rapport' par l'étudiant)
if ($stage && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='upload_rapport') {
  if (!empty($_FILES['rapport']['name'])) {
    $f=$_FILES['rapport'];
    if ($f['error']===UPLOAD_ERR_OK) {
      $ext=strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      if (!in_array($ext,['pdf','doc','docx'])) $errors[]="Formats autorisés : PDF, DOC, DOCX.";
      if ($f['size'] > 10*1024*1024) $errors[]="Taille max 10 Mo.";
      if (!$errors) {
        @mkdir(__DIR__.'/../uploads',0777,true);
        $name = 'rapport_stage_'.$user_id.'_'.time().'.'.$ext;
        $dest = __DIR__.'/../uploads/'.$name;
        if (move_uploaded_file($f['tmp_name'],$dest)) {
          $path = 'uploads/'.$name;
          $ins = $pdo->prepare("INSERT INTO suivis (stage_id, auteur_user_id, type, contenu, fichier_path) VALUES (?,?,?,?,?)");
          $ins->execute([(int)$stage['id'],$user_id,'rapport','Rapport déposé par l’étudiant',$path]);
          $infos[]="Rapport déposé ✅";
        } else { $errors[]="Échec d’enregistrement du fichier."; }
      }
    } else { $errors[]="Erreur upload (code ".$f['error'].")."; }
  } else { $errors[]="Choisis un fichier à déposer."; }
}

// Recharger suivis
$su = null;
if ($stage) {
  $su = $pdo->prepare("
    SELECT s.*, u.prenom, u.nom
    FROM suivis s
    JOIN users u ON u.id = s.auteur_user_id
    WHERE s.stage_id = ?
    ORDER BY s.date_suivi DESC
  ");
  $su->execute([(int)$stage['id']]);
  $suivis = $su->fetchAll(PDO::FETCH_ASSOC);
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="mb-0">Mon stage</h2>
  <a href="../index.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Accueil</a>
</div>

<?php if (!$stage): ?>
  <div class="alert alert-info">Aucun stage confirmé pour le moment. Vérifie l’avancement de tes candidatures.</div>
<?php else: ?>
  <?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div><?php endif; ?>
  <?php if ($infos): ?><div class="alert alert-success"><?php foreach($infos as $i) echo "<div>".htmlspecialchars($i)."</div>"; ?></div><?php endif; ?>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <div class="text-muted small">Entreprise</div>
          <div class="fw-semibold"><?= htmlspecialchars($stage['entreprise_nom']) ?></div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Début</div>
          <div><?= htmlspecialchars($stage['date_debut']) ?></div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Fin</div>
          <div><?= htmlspecialchars($stage['date_fin']) ?></div>
        </div>
        <div class="col-md-6">
          <div class="text-muted small">Sujet</div>
          <div><?= htmlspecialchars($stage['sujet'] ?: '—') ?></div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Tuteur entreprise</div>
          <div><?= htmlspecialchars(($stage['t_prenom']??'').' '.($stage['t_nom']??'')) ?: '—' ?></div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Statut</div>
          <span class="badge <?= 
            $stage['statut']==='en_cours'?'text-bg-warning':
            ($stage['statut']==='terminé'?'text-bg-success':
            ($stage['statut']==='rupture'?'text-bg-danger':'text-bg-secondary')) ?>">
            <?= htmlspecialchars(ucfirst($stage['statut'])) ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Dépôt de rapport -->
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <h3 class="h6 mb-3">Déposer un rapport</h3>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_rapport">
        <div class="row g-2 align-items-end">
          <div class="col-md-8">
            <label class="form-label">Fichier (PDF/DOC/DOCX)</label>
            <input type="file" name="rapport" class="form-control" accept=".pdf,.doc,.docx" required>
          </div>
          <div class="col-md-4">
            <button class="btn btn-primary w-100">Déposer</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Timeline des suivis -->
  <div class="card shadow-sm">
    <div class="card-body">
      <h3 class="h6 mb-3">Suivi & documents</h3>
      <?php if (empty($suivis)): ?>
        <div class="text-muted">Aucun suivi pour l’instant.</div>
      <?php else: ?>
        <div class="list-group">
          <?php foreach ($suivis as $s): ?>
            <div class="list-group-item d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold">
                  <?= htmlspecialchars(ucfirst($s['type'])) ?> — 
                  <?= htmlspecialchars($s['prenom'].' '.$s['nom']) ?>
                </div>
                <div class="small text-muted"><?= htmlspecialchars($s['date_suivi']) ?></div>
                <?php if (!empty($s['contenu'])): ?>
                  <div class="mt-1"><?= nl2br(htmlspecialchars($s['contenu'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($s['fichier_path'])): ?>
                  <div class="mt-1">
                    <a class="btn btn-sm btn-outline-primary" target="_blank" href="../<?= htmlspecialchars($s['fichier_path']) ?>">
                      <i class="bi bi-paperclip"></i> Télécharger
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
