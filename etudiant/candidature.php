<?php
require_once '../includes/auth.php'; requireRole(['etudiant']);
require_once '../db.php';
if (empty($_GET['id_offre']) || !ctype_digit($_GET['id_offre'])) die('Offre invalide.');
$id_offre = (int)$_GET['id_offre'];
$user_id  = (int)$_SESSION['user_id'];

$off = $pdo->prepare("SELECT o.*, e.nom AS entreprise_nom FROM offres_stage o JOIN entreprises e ON e.id=o.entreprise_id WHERE o.id=? AND o.statut='publiée'");
$off->execute([$id_offre]);
$offre = $off->fetch(PDO::FETCH_ASSOC);
if (!$offre) die('Offre introuvable ou non publiée.');

$page_title = 'Candidature - '.$offre['titre'];

$errors=[]; $infos=[];
$lettre = trim($_POST['lettre'] ?? '');
$cvPath = null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
  // Unicité (une seule candidature par offre/étudiant)
  $chk = $pdo->prepare("SELECT id FROM candidatures WHERE offre_id=? AND etudiant_user_id=?");
  $chk->execute([$id_offre, $user_id]);
  if ($chk->fetch()) $errors[] = "Tu as déjà candidaté à cette offre.";

  if ($lettre==='') $errors[] = "La lettre de motivation est requise.";

  // Upload CV (optionnel)
  if (!empty($_FILES['cv']['name'])) {
    $f = $_FILES['cv'];
    if ($f['error']===UPLOAD_ERR_OK) {
      $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      $ok = in_array($ext,['pdf','doc','docx']);
      if (!$ok) $errors[]="CV : formats autorisés pdf, doc, docx.";
      if ($f['size'] > 5*1024*1024) $errors[]="CV : taille max 5 Mo.";
      if (!$errors) {
        @mkdir(__DIR__.'/../uploads',0777,true);
        $name = 'cv_'.$user_id.'_'.time().'.'.$ext;
        $dest = __DIR__.'/../uploads/'.$name;
        if (move_uploaded_file($f['tmp_name'],$dest)) $cvPath = 'uploads/'.$name;
        else $errors[]="Impossible d’enregistrer le CV.";
      }
    } elseif ($f['error']!==UPLOAD_ERR_NO_FILE) {
      $errors[]="Erreur upload (code ".$f['error'].").";
    }
  }

  if (!$errors) {
    $st = $pdo->prepare("INSERT INTO candidatures (offre_id, etudiant_user_id, lettre_motivation, cv_path) VALUES (?,?,?,?)");
    $st->execute([$id_offre,$user_id,$lettre,$cvPath]);
    $infos[]="Candidature envoyée ✅";
  }
}

require_once '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="mb-0">Candidature</h2>
  <a href="offres.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour aux offres</a>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between flex-wrap">
      <div>
        <div class="text-muted small">Offre</div>
        <div class="fw-semibold"><?= htmlspecialchars($offre['titre']) ?></div>
        <div class="text-muted"><?= htmlspecialchars($offre['entreprise_nom']) ?> — <?= htmlspecialchars($offre['lieu'] ?: '—') ?></div>
      </div>
      <div>
        <div class="text-muted small">Durée</div>
        <div><?= (int)$offre['duree_semaines'] ?: '—' ?> semaines</div>
      </div>
    </div>
  </div>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div>
<?php endif; ?>
<?php if ($infos): ?>
  <div class="alert alert-success"><?php foreach($infos as $i) echo "<div>".htmlspecialchars($i)."</div>"; ?></div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Lettre de motivation <span class="text-danger">*</span></label>
        <textarea name="lettre" rows="8" class="form-control" placeholder="Explique pourquoi tu es motivé(e), ce que tu peux apporter, tes compétences et ta dispo…"
          required><?= htmlspecialchars($lettre) ?></textarea>
        <div class="form-text">Indique la période souhaitée et fais le lien avec les compétences demandées.</div>
      </div>
      <div class="mb-3">
        <label class="form-label">CV (optionnel)</label>
        <input type="file" name="cv" class="form-control" accept=".pdf,.doc,.docx">
        <div class="form-text">PDF/Doc/Docx — 5 Mo max.</div>
      </div>
      <button class="btn btn-primary">Envoyer la candidature</button>
    </form>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
