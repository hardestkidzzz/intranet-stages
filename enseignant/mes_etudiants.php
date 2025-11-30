<?php
require_once '../includes/auth.php'; requireRole(['enseignant']);
require_once '../db.php';

$teacher_id = (int)$_SESSION['user_id'];
$page_title = 'Mes étudiants';

$st = $pdo->prepare("
  SELECT s.*, u.id AS etu_id, u.prenom, u.nom, e.nom AS entreprise_nom
  FROM stages s
  JOIN users u ON u.id = s.etudiant_user_id
  JOIN entreprises e ON e.id = s.entreprise_id
  WHERE s.tuteur_enseignant_user_id = ?
  ORDER BY s.date_debut DESC
");
$st->execute([$teacher_id]);
$stages = $st->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="mb-0">Mes étudiants</h2>
  <a href="../index.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Accueil</a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Étudiant</th>
          <th>Entreprise</th>
          <th>Période</th>
          <th>Statut</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($stages)): ?>
        <tr><td colspan="5" class="text-center p-4">Aucun étudiant assigné.</td></tr>
      <?php else: foreach($stages as $s): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($s['prenom'].' '.$s['nom']) ?></td>
          <td><?= htmlspecialchars($s['entreprise_nom']) ?></td>
          <td><?= htmlspecialchars($s['date_debut']) ?> → <?= htmlspecialchars($s['date_fin']) ?></td>
          <td>
            <span class="badge <?= 
              $s['statut']==='en_cours'?'text-bg-warning':
              ($s['statut']==='terminé'?'text-bg-success':
              ($s['statut']==='rupture'?'text-bg-danger':'text-bg-secondary')) ?>">
              <?= htmlspecialchars(ucfirst($s['statut'])) ?>
            </span>
          </td>
          <td class="text-end">
            <a href="suivi.php?stage_id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-primary">
              Ouvrir le suivi
            </a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
