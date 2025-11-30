<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php'; requireRole(['admin','enseignant']);
require_once __DIR__ . '/../db.php';

$page_title = 'Nouveau stage';

// listes
$etudiants = $pdo->query("SELECT id, prenom, nom, email FROM users WHERE role='etudiant' ORDER BY nom, prenom")->fetchAll(PDO::FETCH_ASSOC);
$profs     = $pdo->query("SELECT id, prenom, nom FROM users WHERE role='enseignant' ORDER BY nom, prenom")->fetchAll(PDO::FETCH_ASSOC);
$ents      = $pdo->query("SELECT id, nom FROM entreprises ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

$errors=[]; $infos=[];

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $etu  = (int)($_POST['etudiant_user_id'] ?? 0);
  $ent  = (int)($_POST['entreprise_id'] ?? 0);
  $prof = (int)($_POST['tuteur_enseignant_user_id'] ?? 0);
  $debut= $_POST['date_debut'] ?? '';
  $fin  = $_POST['date_fin'] ?? '';
  $sujet= trim($_POST['sujet'] ?? '');
  $statut = $_POST['statut'] ?? 'préparation';
  if (!in_array($statut,['préparation','en_cours','terminé','rupture'])) $statut='préparation';

  if (!$etu)  $errors[]="Sélectionne un étudiant.";
  if (!$ent)  $errors[]="Sélectionne une entreprise.";
  if ($debut==='') $errors[]="Date de début requise.";
  if ($fin==='')   $errors[]="Date de fin requise.";
  if (!$errors && $debut > $fin) $errors[]="La date de début doit être ≤ date de fin.";

  if (!$errors) {
    $ins = $pdo->prepare("INSERT INTO stages
      (etudiant_user_id, entreprise_id, tuteur_enseignant_user_id, date_debut, date_fin, sujet, statut, date_creation)
      VALUES (?,?,?,?,?,?,?, NOW())");
    $ins->execute([$etu,$ent,$prof?:null,$debut,$fin,$sujet?:null,$statut]);
    $newId = (int)$pdo->lastInsertId();
    header('Location: ' . BASE_URL . 'stages/view_stage.php?id=' . $newId);
    exit;
  }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="mb-0">Créer un stage</h2>
  <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>index.php"><i class="bi bi-arrow-left"></i> Retour</a>
</div>

<?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div><?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Étudiant *</label>
          <select name="etudiant_user_id" class="form-select" required>
            <option value="">—</option>
            <?php foreach ($etudiants as $u): ?>
              <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['nom'].' '.$u['prenom'].' — '.$u['email']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Entreprise *</label>
          <select name="entreprise_id" class="form-select" required>
            <option value="">—</option>
            <?php foreach ($ents as $e): ?>
              <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Tuteur enseignant (optionnel)</label>
          <select name="tuteur_enseignant_user_id" class="form-select">
            <option value="">—</option>
            <?php foreach ($profs as $p): ?>
              <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Début *</label>
          <input type="date" name="date_debut" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Fin *</label>
          <input type="date" name="date_fin" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Statut</label>
          <select name="statut" class="form-select">
            <?php foreach (['préparation','en_cours','terminé','rupture'] as $s): ?>
              <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label">Sujet (optionnel)</label>
          <textarea name="sujet" rows="3" class="form-control" placeholder="Sujet / mission du stage"></textarea>
        </div>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Enregistrer</button>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>index.php">Annuler</a>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
