<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php'; requireRole(['etudiant']);
require_once __DIR__ . '/../db.php';

$user_id = (int)$_SESSION['user_id'];

// flash messages (optionnel)
$success = $_SESSION['flash_success'] ?? null; unset($_SESSION['flash_success']);
$error   = $_SESSION['flash_error'] ?? null;   unset($_SESSION['flash_error']);

$sql="
  SELECT c.*, o.titre, e.nom AS entreprise_nom
  FROM candidatures c
  JOIN offres_stage o ON o.id=c.offre_id
  JOIN entreprises e ON e.id=o.entreprise_id
  WHERE c.etudiant_user_id=?
  ORDER BY c.date_candidature DESC, c.id DESC
";
$st=$pdo->prepare($sql);
$st->execute([$user_id]);
$cands=$st->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Mes candidatures';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="mb-0">Mes candidatures</h2>
  <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>etudiant/offres.php"><i class="bi bi-search"></i> Voir les offres</a>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Offre</th>
          <th>Entreprise</th>
          <th>Message</th>
          <th>CV</th>
          <th>LM</th>
          <th>Statut</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($cands)): ?>
        <tr><td colspan="7" class="text-center p-4">Aucune candidature.</td></tr>
      <?php else: foreach($cands as $c): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($c['titre']) ?></td>
          <td><?= htmlspecialchars($c['entreprise_nom']) ?></td>
          <td><?= nl2br(htmlspecialchars($c['message'] ?? '—')) ?></td>
          <td><?php if ($c['cv_path']): ?><a target="_blank" class="btn btn-sm btn-outline-primary" href="<?= BASE_URL.htmlspecialchars($c['cv_path']) ?>">CV</a><?php else: ?>—<?php endif; ?></td>
          <td><?php if ($c['lm_path']): ?><a target="_blank" class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL.htmlspecialchars($c['lm_path']) ?>">LM</a><?php else: ?>—<?php endif; ?></td>
          <td>
            <span class="badge
              <?= $c['statut']==='acceptée' ? 'text-bg-success' :
                 ($c['statut']==='refusée' ? 'text-bg-danger' : 'text-bg-warning') ?>">
              <?= htmlspecialchars(ucfirst($c['statut'])) ?>
            </span>
          </td>
          <td class="text-muted small"><?= htmlspecialchars($c['date_candidature']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
