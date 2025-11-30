<?php
require_once '../includes/auth.php'; requireRole(['entreprise']);
require_once '../db.php';

$user_id = (int)$_SESSION['user_id'];
$page_title = 'Offre de stage';

// Entreprise du tuteur
$te = $pdo->prepare("SELECT entreprise_id FROM tuteurs_entreprise WHERE user_id=?");
$te->execute([$user_id]);
$entreprise_id = (int)($te->fetchColumn() ?: 0);
if (!$entreprise_id) die("Profil tuteur entreprise incomplet.");

// Mode édition ?
$id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;

$offre = [
  'titre'=>'','description'=>'','lieu'=>'','duree_semaines'=>'','date_debut'=>'','competences'=>'','statut'=>'publiée'
];
if ($id) {
  $st = $pdo->prepare("SELECT * FROM offres_stage WHERE id=? AND entreprise_id=?");
  $st->execute([$id,$entreprise_id]);
  $offre = $st->fetch(PDO::FETCH_ASSOC);
  if (!$offre) die("Offre introuvable.");
}

$errors=[];$infos=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $titre = trim($_POST['titre']??'');
  $description = trim($_POST['description']??'');
  $lieu = trim($_POST['lieu']??'');
  $duree = (int)($_POST['duree_semaines']??0);
  $date_debut = $_POST['date_debut']??null;
  $competences = trim($_POST['competences']??'');
  $statut = $_POST['statut']??'publiée';
  if ($titre==='') $errors[]="Le titre est requis.";
  if ($description==='') $errors[]="La description est requise.";
  if (!in_array($statut,['brouillon','publiée','clôturée'])) $statut='publiée';

  if (!$errors) {
    if ($id) {
      $st = $pdo->prepare("UPDATE offres_stage 
        SET titre=?,description=?,lieu=?,duree_semaines=?,date_debut=?,competences=?,statut=?
        WHERE id=? AND entreprise_id=?");
      $st->execute([$titre,$description,$lieu?$lieu:null,$duree?:null,$date_debut?:null,$competences?:null,$statut,$id,$entreprise_id]);
      $infos[]="Offre mise à jour.";
    } else {
      $st = $pdo->prepare("INSERT INTO offres_stage 
        (entreprise_id,auteur_user_id,titre,description,lieu,duree_semaines,date_debut,competences,statut)
        VALUES (?,?,?,?,?,?,?,?,?)");
      $st->execute([$entreprise_id,$user_id,$titre,$description,$lieu?:null,$duree?:null,$date_debut?:null,$competences?:null,$statut]);
      $id = (int)$pdo->lastInsertId();
      $infos[]="Offre créée.";
    }
  }
}

require_once '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="mb-0"><?= $id?'Éditer':'Créer' ?> une offre</h2>
  <a href="mes_offres.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
</div>

<?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div><?php endif; ?>
<?php if ($infos): ?><div class="alert alert-success"><?php foreach($infos as $i) echo "<div>".htmlspecialchars($i)."</div>"; ?></div><?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Titre *</label>
        <input type="text" name="titre" class="form-control" required value="<?= htmlspecialchars($_POST['titre']??$offre['titre']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Description *</label>
        <textarea name="description" rows="7" class="form-control" required><?= htmlspecialchars($_POST['description']??$offre['description']) ?></textarea>
      </div>
      <div class="row g-2">
        <div class="col-sm-4">
          <label class="form-label">Lieu</label>
          <input type="text" name="lieu" class="form-control" value="<?= htmlspecialchars($_POST['lieu']??$offre['lieu']) ?>">
        </div>
        <div class="col-sm-4">
          <label class="form-label">Durée (sem.)</label>
          <input type="number" name="duree_semaines" class="form-control" min="1" value="<?= htmlspecialchars($_POST['duree_semaines']??$offre['duree_semaines']) ?>">
        </div>
        <div class="col-sm-4">
          <label class="form-label">Date de début</label>
          <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($_POST['date_debut']??$offre['date_debut']) ?>">
        </div>
      </div>
      <div class="mb-3 mt-2">
        <label class="form-label">Compétences (séparées par des virgules)</label>
        <input type="text" name="competences" class="form-control" value="<?= htmlspecialchars($_POST['competences']??$offre['competences']) ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Statut</label>
        <select name="statut" class="form-select">
          <?php $s=$_POST['statut']??$offre['statut']; ?>
          <option value="brouillon" <?= $s==='brouillon'?'selected':''; ?>>Brouillon</option>
          <option value="publiée"   <?= $s==='publiée'?'selected':''; ?>>Publiée</option>
          <option value="clôturée"  <?= $s==='clôturée'?'selected':''; ?>>Clôturée</option>
        </select>
      </div>
      <button class="btn btn-primary"><?= $id?'Enregistrer':'Créer l’offre' ?></button>
    </form>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
