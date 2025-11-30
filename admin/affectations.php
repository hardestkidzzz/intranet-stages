<?php
require_once __DIR__ . '/../includes/auth.php'; requireRole(['admin']);
require_once __DIR__ . '/../db.php';
$page_title = 'Administration — Affectations tuteur enseignant';

$errors=[]; $infos=[];

/* Affectation POST */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['stage_id']) && ctype_digit($_POST['stage_id'])) {
  $stage_id = (int)$_POST['stage_id'];
  $teacher  = $_POST['teacher_id'];
  if ($teacher !== '' && !ctype_digit($teacher)) { $errors[]="Identifiant enseignant invalide."; }
  if (!$errors) {
    // Vérifier que teacher est bien un enseignant si non vide
    if ($teacher!=='') {
      $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id=? AND role='enseignant'");
      $check->execute([(int)$teacher]);
      if (!(int)$check->fetchColumn()) $errors[]="Utilisateur sélectionné n'est pas un enseignant.";
    }
    if (!$errors) {
      $upd = $pdo->prepare("UPDATE stages SET tuteur_enseignant_user_id = :tid WHERE id = :sid");
      $upd->execute([':tid' => ($teacher===''? null : (int)$teacher), ':sid'=>$stage_id]);
      $infos[]="Affectation mise à jour.";
    }
  }
}

/* Stages + enseignants */
$st = $pdo->query("
  SELECT s.*, uetu.prenom AS etu_prenom, uetu.nom AS etu_nom, ent.nom AS entreprise_nom,
         uprof.id AS prof_id, uprof.prenom AS prof_prenom, uprof.nom AS prof_nom
  FROM stages s
  JOIN users uetu ON uetu.id = s.etudiant_user_id
  JOIN entreprises ent ON ent.id = s.entreprise_id
  LEFT JOIN users uprof ON uprof.id = s.tuteur_enseignant_user_id
  ORDER BY s.date_debut DESC, s.id DESC
");
$stages = $st->fetchAll(PDO::FETCH_ASSOC);

$ens = $pdo->query("SELECT id, prenom, nom FROM users WHERE role='enseignant' ORDER BY nom, prenom")
           ->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="mb-0">Affectations — Tuteur enseignant</h2>
  <div class="d-flex gap-2">
    <a href="stats.php" class="btn btn-outline-primary"><i class="bi bi-graph-up"></i> Stats</a>
    <a href="../index.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Accueil</a>
  </div>
</div>

<?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div><?php endif; ?>
<?php if ($infos): ?><div class="alert alert-success"><?php foreach($infos as $i) echo "<div>".htmlspecialchars($i)."</div>"; ?></div><?php endif; ?>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Étudiant</th>
          <th>Entreprise</th>
          <th>Période</th>
          <th>Tuteur enseignant</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($stages)): ?>
        <tr><td colspan="5" class="text-center p-4">Aucun stage.</td></tr>
      <?php else: foreach ($stages as $s): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($s['etu_prenom'].' '.$s['etu_nom']) ?></td>
          <td><?= htmlspecialchars($s['entreprise_nom']) ?></td>
          <td><?= htmlspecialchars($s['date_debut']) ?> → <?= htmlspecialchars($s['date_fin']) ?></td>
          <td>
            <form method="post" class="d-flex gap-2">
              <input type="hidden" name="stage_id" value="<?= (int)$s['id'] ?>">
              <select name="teacher_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">— Non assigné —</option>
                <?php foreach ($ens as $t): ?>
                  <option value="<?= (int)$t['id'] ?>" <?= ((int)$s['prof_id']===(int)$t['id'])?'selected':''; ?>>
                    <?= htmlspecialchars($t['nom'].' '.$t['prenom']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
          <td class="text-end">
            <?php if ($s['prof_id']): ?>
              <form method="post" class="d-inline" onsubmit="return confirm('Retirer le tuteur ?');">
                <input type="hidden" name="stage_id" value="<?= (int)$s['id'] ?>">
                <input type="hidden" name="teacher_id" value="">
                <button class="btn btn-sm btn-outline-danger">Retirer</button>
              </form>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
