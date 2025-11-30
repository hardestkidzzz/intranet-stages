<?php
require_once '../includes/auth.php'; requireRole(['etudiant']);
require_once '../db.php';

$user_id = (int)$_SESSION['user_id'];
$page_title = 'Mes candidatures';

$st = $pdo->prepare("
  SELECT c.*, o.titre, o.lieu, e.nom AS entreprise_nom
  FROM candidatures c
  JOIN offres_stage o ON o.id = c.offre_id
  JOIN entreprises e ON e.id = o.entreprise_id
  WHERE c.etudiant_user_id = ?
  ORDER BY c.date_candidature DESC
");
$st->execute([$user_id]);
$cands = $st->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="mb-0">Mes candidatures</h2>
  <a href="offres.php" class="btn btn-outline-secondary"><i class="bi bi-search"></i> Parcourir les offres</a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Offre</th>
          <th>Entreprise</th>
          <th>Lieu</th>
          <th>Statut</th>
          <th>Envoyée le</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($cands)): ?>
        <tr><td colspan="5" class="text-center p-4">Aucune candidature pour le moment.</td></tr>
      <?php else: foreach($cands as $c): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($c['titre']) ?></td>
          <td><?= htmlspecialchars($c['entreprise_nom']) ?></td>
          <td><?= htmlspecialchars($c['lieu'] ?: '—') ?></td>
          <td>
            <span class="badge <?= 
              $c['statut']==='acceptée'  ? 'text-bg-success' :
              ($c['statut']==='refusée' ? 'text-bg-danger'  :
              ($c['statut']==='en_cours'? 'text-bg-warning' : 'text-bg-secondary')) ?>">
              <?= ucfirst($c['statut']) ?>
            </span>
          </td>
          <td><?= htmlspecialchars($c['date_candidature']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
