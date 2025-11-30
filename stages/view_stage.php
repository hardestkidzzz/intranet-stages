<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../db.php';

if (empty($_GET['id']) || !ctype_digit($_GET['id'])) die('Stage invalide.');
$id = (int)$_GET['id'];

// récupérer le stage + infos
$st = $pdo->prepare("
  SELECT s.*,
         ue.prenom AS etu_prenom, ue.nom AS etu_nom, ue.email AS etu_email,
         ent.nom AS entreprise_nom,
         ut.prenom AS ens_prenom, ut.nom AS ens_nom
  FROM stages s
  JOIN users ue ON ue.id = s.etudiant_user_id
  JOIN entreprises ent ON ent.id = s.entreprise_id
  LEFT JOIN users ut ON ut.id = s.tuteur_enseignant_user_id
  WHERE s.id = ?
");
$st->execute([$id]);
$stage = $st->fetch(PDO::FETCH_ASSOC);
if (!$stage) die('Stage introuvable.');

$role = $_SESSION['role'] ?? '';
$user_id = (int)($_SESSION['user_id'] ?? 0);

// contrôle d'accès de base (affiner si besoin)
$allowed = false;
if (in_array($role,['admin','enseignant'])) $allowed = true;
if ($role==='etudiant' && (int)$stage['etudiant_user_id']===$user_id) $allowed = true;
if (!$allowed) die('Accès refusé.');

$page_title = 'Stage #'.$id;

// Upload d’un document lié (on réutilise la table `suivis` si tu l’as, sinon on peut créer `stage_docs`)
$errors=[]; $infos=[];
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='upload_doc') {
  if (!empty($_FILES['piece']['name'])) {
    $f=$_FILES['piece'];
    if ($f['error']===UPLOAD_ERR_OK) {
      $ext=strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      if (!in_array($ext,['pdf','doc','docx','png','jpg','jpeg'])) $errors[]="Formats autorisés : pdf, doc, docx, png, jpg.";
      if ($f['size'] > 10*1024*1024) $errors[]="Taille max 10 Mo.";
      if (!$errors) {
        @mkdir(__DIR__.'/../uploads',0777,true);
        $name='stage_'.$id.'_'.time().'.'.$ext;
        $dest=__DIR__.'/../uploads/'.$name;
        if (move_uploaded_file($f['tmp_name'],$dest)) {
          $path='uploads/'.$name;
          // on stocke dans `suivis` (type 'doc')
          $ins=$pdo->prepare("INSERT INTO suivis (stage_id, auteur_user_id, type, contenu, fichier_path) VALUES (?,?,?,?,?)");
          $ins->execute([$id,$user_id,'doc',$_POST['desc']?trim($_POST['desc']):null,$path]);
          $infos[]="Document ajouté.";
        } else { $errors[]="Échec d’enregistrement du fichier."; }
      }
    } else { $errors[]="Erreur upload (code ".$f['error'].")."; }
  } else { $errors[]="Choisis un fichier."; }
}

// Charger suivis/documents si table existe
$suivis=[];
try{
  $su=$pdo->prepare("SELECT s.*, u.prenom, u.nom FROM suivis s JOIN users u ON u.id=s.auteur_user_id WHERE s.stage_id=? ORDER BY s.date_suivi DESC");
  $su->execute([$id]);
  $suivis=$su->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){
  // table `suivis` absente -> ignorer
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="mb-0">Stage #<?= (int)$id ?></h2>
  <div class="d-flex gap-2">
    <!-- Bouton Convention de stage -->
    <a class="btn btn-outline-success" href="<?= BASE_URL ?>convention.php?id=<?= (int)$id ?>" target="_blank">
      <i class="bi bi-file-text"></i> Convention
    </a>
    <?php if (in_array($role,['admin','enseignant'])): ?>
      <a class="btn btn-outline-warning" href="stages/edit_stage.php?id=<?= (int)$id ?>"><i class="bi bi-pencil"></i> Modifier</a>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>index.php"><i class="bi bi-arrow-left"></i> Retour</a>
  </div>
</div>

<?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div><?php endif; ?>
<?php if ($infos): ?><div class="alert alert-success"><?php foreach($infos as $i) echo "<div>".htmlspecialchars($i)."</div>"; ?></div><?php endif; ?>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-4">
        <div class="text-muted small">Étudiant</div>
        <div class="fw-semibold"><?= htmlspecialchars($stage['etu_prenom'].' '.$stage['etu_nom']) ?></div>
        <div class="small text-muted"><?= htmlspecialchars($stage['etu_email']) ?></div>
      </div>
      <div class="col-md-4">
        <div class="text-muted small">Entreprise</div>
        <div class="fw-semibold"><?= htmlspecialchars($stage['entreprise_nom']) ?></div>
      </div>
      <div class="col-md-4">
        <div class="text-muted small">Tuteur enseignant</div>
        <div><?= htmlspecialchars(trim(($stage['ens_prenom']??'').' '.($stage['ens_nom']??'')) ?: '—') ?></div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Début</div>
        <div><?= htmlspecialchars($stage['date_debut']) ?></div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Fin</div>
        <div><?= htmlspecialchars($stage['date_fin']) ?></div>
      </div>
      <div class="col-md-3">
        <div class="text-muted small">Statut</div>
        <span class="badge
          <?= $stage['statut']==='en_cours' ? 'text-bg-warning' :
             ($stage['statut']==='terminé' ? 'text-bg-success' :
             ($stage['statut']==='rupture' ? 'text-bg-danger' : 'text-bg-secondary')) ?>">
          <?= htmlspecialchars(ucfirst($stage['statut'])) ?>
        </span>
      </div>
      <div class="col-md-12">
        <div class="text-muted small">Sujet</div>
        <div><?= nl2br(htmlspecialchars($stage['sujet'] ?? '—')) ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Documents / Suivi -->
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="h6">Suivi & documents</h3>
        <?php if (empty($suivis)): ?>
          <div class="text-muted">Aucun document / suivi pour l’instant.</div>
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
                    <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= BASE_URL . htmlspecialchars($s['fichier_path']) ?>">
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

  <div class="col-lg-5">
    <?php if (in_array($role,['admin','enseignant'])): ?>
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="h6 mb-3">Ajouter un document</h3>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_doc">
          <div class="mb-2">
            <label class="form-label">Description (optionnel)</label>
            <input type="text" name="desc" class="form-control" placeholder="Convention, visite, rapport…">
          </div>
          <div class="mb-3">
            <label class="form-label">Fichier (pdf, doc, docx, png, jpg) — 10Mo max</label>
            <input type="file" name="piece" class="form-control" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg" required>
          </div>
          <button class="btn btn-primary w-100">Téléverser</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
