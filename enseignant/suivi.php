<?php
require_once '../includes/auth.php'; requireRole(['enseignant']);
require_once '../db.php';

$teacher_id = (int)$_SESSION['user_id'];
if (empty($_GET['stage_id']) || !ctype_digit($_GET['stage_id'])) die('Stage invalide.');
$stage_id = (int)$_GET['stage_id'];

// Vérifier que le stage est bien suivi par l'enseignant
$st = $pdo->prepare("
  SELECT s.*, u.prenom AS etu_prenom, u.nom AS etu_nom, e.nom AS entreprise_nom
  FROM stages s
  JOIN users u ON u.id = s.etudiant_user_id
  JOIN entreprises e ON e.id = s.entreprise_id
  WHERE s.id = ? AND s.tuteur_enseignant_user_id = ?
");
$st->execute([$stage_id, $teacher_id]);
$stage = $st->fetch(PDO::FETCH_ASSOC);
if (!$stage) die('Accès refusé.');

$page_title = 'Suivi — '.$stage['etu_prenom'].' '.$stage['etu_nom'];
$errors=[]; $infos=[];

// Ajout d'un suivi / upload fichier
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='add_suivi') {
  $type = $_POST['type'] ?? 'point';
  $contenu = trim($_POST['contenu'] ?? '');
  if (!in_array($type,['point','visite','rapport','note'])) $type='point';
  $filePath = null;

  if (!empty($_FILES['piece']['name'])) {
    $f=$_FILES['piece'];
    if ($f['error']===UPLOAD_ERR_OK) {
      $ext=strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      if (!in_array($ext,['pdf','doc','docx','png','jpg','jpeg'])) $errors[]="Formats autorisés : pdf, doc, docx, png, jpg.";
      if ($f['size'] > 10*1024*1024) $errors[]="Taille max 10 Mo.";
      if (!$errors) {
        @mkdir(__DIR__.'/../uploads',0777,true);
        $name='suivi_'.$stage_id.'_'.time().'.'.$ext;
        $dest=__DIR__.'/../uploads/'.$name;
        if (move_uploaded_file($f['tmp_name'],$dest)) $filePath = 'uploads/'.$name;
        else $errors[]="Échec d’enregistrement du fichier.";
      }
    } elseif ($f['error']!==UPLOAD_ERR_NO_FILE) {
      $errors[]="Erreur upload (code ".$f['error'].").";
    }
  }

  if (!$errors) {
    $ins=$pdo->prepare("INSERT INTO suivis (stage_id, auteur_user_id, type, contenu, fichier_path) VALUES (?,?,?,?,?)");
    $ins->execute([$stage_id,$teacher_id,$type,$contenu?:null,$filePath]);
    $infos[]="Entrée de suivi ajoutée.";
  }
}

// Charger suivis
$su = $pdo->prepare("
  SELECT s.*, u.prenom, u.nom
  FROM suivis s
  JOIN users u ON u.id = s.auteur_user_id
  WHERE s.stage_id = ?
  ORDER BY s.date_suivi DESC
");
$su->execute([$stage_id]);
$suivis = $su->fetchAll(PDO::FETCH_ASSOC);

// Évaluation finale (affichage + édition rapide)
$ev = $pdo->prepare("SELECT * FROM evaluations WHERE stage_id=?");
$ev->execute([$stage_id]);
$eval = $ev->fetch(PDO::FETCH_ASSOC);

// Sauvegarde évaluation
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='save_eval') {
  $nt = (int)($_POST['note_technique'] ?? 0);
  $ns = (int)($_POST['note_softskills'] ?? 0);
  $nd = (int)($_POST['note_dossier'] ?? 0);
  $com = trim($_POST['commentaire'] ?? '');

  if ($eval) {
    $up = $pdo->prepare("UPDATE evaluations SET note_technique=?, note_softskills=?, note_dossier=?, commentaire=?, date_eval=NOW() WHERE id=?");
    $up->execute([$nt?:null,$ns?:null,$nd?:null,$com?:null,(int)$eval['id']]);
    $infos[]="Évaluation mise à jour.";
  } else {
    $in = $pdo->prepare("INSERT INTO evaluations (stage_id, note_technique, note_softskills, note_dossier, commentaire) VALUES (?,?,?,?,?)");
    $in->execute([$stage_id,$nt?:null,$ns?:null,$nd?:null,$com?:null]);
    $infos[]="Évaluation enregistrée.";
  }
  // recharger
  $ev->execute([$stage_id]); $eval=$ev->fetch(PDO::FETCH_ASSOC);
}

require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="mb-0">Suivi — <?= htmlspecialchars($stage['etu_prenom'].' '.$stage['etu_nom']) ?></h2>
  <div class="d-flex gap-2">
    <!-- Bouton Convention de stage -->
    <a class="btn btn-outline-success" href="../convention.php?id=<?= (int)$stage_id ?>" target="_blank">
      <i class="bi bi-file-text"></i> Convention
    </a>
    <a href="mes_etudiants.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
  </div>
</div>

<?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div><?php endif; ?>
<?php if ($infos): ?><div class="alert alert-success"><?php foreach($infos as $i) echo "<div>".htmlspecialchars($i)."</div>"; ?></div><?php endif; ?>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-4"><div class="text-muted small">Entreprise</div><div class="fw-semibold"><?= htmlspecialchars($stage['entreprise_nom']) ?></div></div>
      <div class="col-md-4"><div class="text-muted small">Période</div><div><?= htmlspecialchars($stage['date_debut']) ?> → <?= htmlspecialchars($stage['date_fin']) ?></div></div>
      <div class="col-md-4">
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

<div class="row g-3">
  <!-- Col gauche : timeline -->
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="h6 mb-3">Suivi</h3>
        <?php if (empty($suivis)): ?>
          <div class="text-muted">Aucun suivi pour l’instant.</div>
        <?php else: ?>
          <div class="list-group">
            <?php foreach ($suivis as $s): ?>
              <div class="list-group-item">
                <div class="d-flex justify-content-between">
                  <div class="fw-semibold"><?= htmlspecialchars(ucfirst($s['type'])) ?></div>
                  <div class="small text-muted"><?= htmlspecialchars($s['date_suivi']) ?></div>
                </div>
                <div class="small text-muted"><?= htmlspecialchars($s['prenom'].' '.$s['nom']) ?></div>
                <?php if (!empty($s['contenu'])): ?><div class="mt-1"><?= nl2br(htmlspecialchars($s['contenu'])) ?></div><?php endif; ?>
                <?php if (!empty($s['fichier_path'])): ?>
                  <div class="mt-1">
                    <a class="btn btn-sm btn-outline-primary" target="_blank" href="../<?= htmlspecialchars($s['fichier_path']) ?>">
                      <i class="bi bi-paperclip"></i> Télécharger
                    </a>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Col droite : ajouter suivi + évaluation -->
  <div class="col-lg-5">
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h3 class="h6 mb-3">Ajouter un suivi</h3>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add_suivi">
          <div class="mb-2">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
              <option value="point">Point</option>
              <option value="visite">Visite</option>
              <option value="rapport">Rapport</option>
              <option value="note">Note</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Contenu (optionnel)</label>
            <textarea name="contenu" rows="4" class="form-control" placeholder="Compte-rendu, décisions, points forts/axes d’amélioration…"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Pièce jointe (optionnel)</label>
            <input type="file" name="piece" class="form-control" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
          </div>
          <button class="btn btn-primary w-100">Enregistrer</button>
        </form>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="h6 mb-3">Évaluation</h3>
        <form method="post">
          <input type="hidden" name="action" value="save_eval">
          <div class="row g-2">
            <div class="col-4">
              <label class="form-label">Technique /20</label>
              <input type="number" min="0" max="20" name="note_technique" class="form-control" value="<?= htmlspecialchars($eval['note_technique'] ?? '') ?>">
            </div>
            <div class="col-4">
              <label class="form-label">Soft skills /20</label>
              <input type="number" min="0" max="20" name="note_softskills" class="form-control" value="<?= htmlspecialchars($eval['note_softskills'] ?? '') ?>">
            </div>
            <div class="col-4">
              <label class="form-label">Dossier /20</label>
              <input type="number" min="0" max="20" name="note_dossier" class="form-control" value="<?= htmlspecialchars($eval['note_dossier'] ?? '') ?>">
            </div>
          </div>
          <div class="mt-2">
            <label class="form-label">Commentaire</label>
            <textarea name="commentaire" rows="4" class="form-control"><?= htmlspecialchars($eval['commentaire'] ?? '') ?></textarea>
          </div>
          <button class="btn btn-success w-100 mt-2"><?= $eval ? 'Mettre à jour' : 'Enregistrer' ?></button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
