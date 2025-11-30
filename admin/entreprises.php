<?php
require_once __DIR__ . '/../includes/auth.php'; requireRole(['admin']);
require_once __DIR__ . '/../db.php';

$page_title = 'Administration — Entreprises';

// Actions (create/update/delete)
$errors=[]; $infos=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $act = $_POST['action'] ?? '';
  if ($act==='save') {
    $id = ctype_digit($_POST['id']??'') ? (int)$_POST['id'] : 0;
    $nom = trim($_POST['nom']??'');
    $siret = trim($_POST['siret']??'');
    $site = trim($_POST['site_web']??'');
    $adr  = trim($_POST['adresse']??'');
    if ($nom==='') $errors[]="Le nom est requis.";
    if (!$errors) {
      if ($id>0) {
        $pdo->prepare("UPDATE entreprises SET nom=?, siret=?, site_web=?, adresse=? WHERE id=?")
            ->execute([$nom?:null,$siret?:null,$site?:null,$adr?:null,$id]);
        $infos[]="Entreprise mise à jour.";
      } else {
        $pdo->prepare("INSERT INTO entreprises (nom,siret,site_web,adresse) VALUES (?,?,?,?)")
            ->execute([$nom?:null,$siret?:null,$site?:null,$adr?:null]);
        $infos[]="Entreprise créée.";
      }
    }
  }
  if ($act==='delete' && ctype_digit($_POST['id']??'')) {
    $id=(int)$_POST['id'];
    $pdo->prepare("DELETE FROM entreprises WHERE id=?")->execute([$id]);
    $infos[]="Entreprise supprimée.";
  }
}

// Liste
$q = trim($_GET['q']??'');
$where=''; $params=[];
if ($q!=='') { $where="WHERE (e.nom LIKE ? OR e.siret LIKE ? OR e.site_web LIKE ?)"; $params=["%$q%","%$q%","%$q%"]; }

$st=$pdo->prepare("SELECT e.*, 
  (SELECT COUNT(*) FROM offres_stage o WHERE o.entreprise_id=e.id) AS nb_offres
  FROM entreprises e $where
  ORDER BY e.date_creation DESC");
$st->execute($params);
$ents=$st->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="mb-0">Entreprises</h2>
  <a href="../index.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Accueil</a>
</div>

<?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div><?php endif; ?>
<?php if ($infos): ?><div class="alert alert-success"><?php foreach($infos as $i) echo "<div>".htmlspecialchars($i)."</div>"; ?></div><?php endif; ?>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-sm-6">
        <label class="form-label">Recherche</label>
        <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="nom, siret, site web">
      </div>
      <div class="col-sm-2 d-flex align-items-end">
        <button class="btn btn-secondary w-100">Filtrer</button>
      </div>
    </form>
  </div>
</div>

<div class="row g-3">
  <!-- Liste -->
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th><th>Nom</th><th>SIRET</th><th>Site</th><th>Offres</th><th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($ents)): ?>
            <tr><td colspan="6" class="text-center p-4">Aucune entreprise.</td></tr>
          <?php else: foreach($ents as $e): ?>
            <tr>
              <td><?= (int)$e['id'] ?></td>
              <td class="fw-semibold"><?= htmlspecialchars($e['nom']) ?></td>
              <td><?= htmlspecialchars($e['siret'] ?: '—') ?></td>
              <td><?php if ($e['site_web']): ?><a href="<?= htmlspecialchars($e['site_web']) ?>" target="_blank">Visiter</a><?php else: ?>—<?php endif; ?></td>
              <td><?= (int)$e['nb_offres'] ?></td>
              <td class="text-end">
                <button class="btn btn-sm btn-warning" 
                        onclick='fillForm(<?= (int)$e["id"] ?>, <?= json_encode($e["nom"]) ?>, <?= json_encode($e["siret"]) ?>, <?= json_encode($e["site_web"]) ?>, <?= json_encode($e["adresse"]) ?>)'>
                        Modifier
                </button>
                <form method="post" class="d-inline" onsubmit="return confirm('Supprimer cette entreprise ?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Formulaire -->
  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="h6 mb-3" id="formTitle">Créer une entreprise</h3>
        <form method="post" id="entForm">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" id="f_id" value="0">
          <div class="mb-2">
            <label class="form-label">Nom *</label>
            <input class="form-control" name="nom" id="f_nom" required>
          </div>
          <div class="mb-2">
            <label class="form-label">SIRET</label>
            <input class="form-control" name="siret" id="f_siret">
          </div>
          <div class="mb-2">
            <label class="form-label">Site web</label>
            <input class="form-control" name="site_web" id="f_site">
          </div>
          <div class="mb-3">
            <label class="form-label">Adresse</label>
            <textarea class="form-control" name="adresse" id="f_adr" rows="3"></textarea>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-primary flex-fill">Enregistrer</button>
            <button class="btn btn-outline-secondary" type="button" onclick="resetForm()">Nouveau</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function fillForm(id, nom, siret, site, adr){
  document.getElementById('formTitle').textContent = 'Modifier une entreprise';
  document.getElementById('f_id').value = id;
  document.getElementById('f_nom').value = nom || '';
  document.getElementById('f_siret').value = siret || '';
  document.getElementById('f_site').value = site || '';
  document.getElementById('f_adr').value = adr || '';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
function resetForm(){
  document.getElementById('formTitle').textContent = 'Créer une entreprise';
  document.getElementById('f_id').value = 0;
  document.getElementById('f_nom').value = '';
  document.getElementById('f_siret').value = '';
  document.getElementById('f_site').value = '';
  document.getElementById('f_adr').value = '';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
