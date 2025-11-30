<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php'; requireRole(['etudiant','admin','enseignant']); // admin/prof peuvent voir
require_once __DIR__ . '/../db.php';

$user_id = (int)($_SESSION['user_id'] ?? 0);

// Filtres
$q     = trim($_GET['q'] ?? '');
$ville = trim($_GET['ville'] ?? '');
$minw  = ctype_digit($_GET['minw'] ?? '') ? (int)$_GET['minw'] : 0;

$where = ["o.statut='publiée'"];
$params = [];
if ($q !== '') {
  $where[] = "(o.titre LIKE ? OR o.description LIKE ? OR o.competences LIKE ?)";
  array_push($params, "%$q%","%$q%","%$q%");
}
if ($ville !== '') {
  $where[] = "o.localisation LIKE ?";
  $params[] = "%$ville%";
}
if ($minw > 0) {
  $where[] = "o.duree_semaines >= ?";
  $params[] = $minw;
}
$w = "WHERE ".implode(" AND ", $where);

// Récup offres + entreprise + info “déjà candidat ?”
$sql = "
  SELECT o.*, e.nom AS entreprise_nom,
         (SELECT COUNT(*) FROM candidatures c WHERE c.offre_id=o.id AND c.etudiant_user_id=?) AS deja_cand
  FROM offres_stage o
  JOIN entreprises e ON e.id = o.entreprise_id
  $w
  ORDER BY o.date_creation DESC, o.id DESC
";
$st = $pdo->prepare($sql);
$st->execute(array_merge([$user_id], $params));
$offres = $st->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Offres de stage';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="mb-0">Offres de stage</h2>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>index.php"><i class="bi bi-house"></i> Accueil</a>
    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>etudiant/candidatures.php"><i class="bi bi-envelope-paper"></i> Mes candidatures</a>
  </div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-md-4">
        <label class="form-label">Recherche</label>
        <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="titre, compétence, description…">
      </div>
      <div class="col-md-3">
        <label class="form-label">Localisation</label>
        <input class="form-control" name="ville" value="<?= htmlspecialchars($ville) ?>" placeholder="Paris, Lyon…">
      </div>
      <div class="col-md-3">
        <label class="form-label">Durée min (semaines)</label>
        <input class="form-control" type="number" name="minw" value="<?= $minw ?: '' ?>">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-secondary w-100">Filtrer</button>
      </div>
    </form>
  </div>
</div>

<div class="row g-3">
  <?php if (empty($offres)): ?>
    <div class="col-12"><div class="alert alert-info mb-0">Aucune offre trouvée.</div></div>
  <?php else: foreach ($offres as $o): ?>
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <h3 class="h5 mb-0"><?= htmlspecialchars($o['titre']) ?></h3>
            <span class="badge text-bg-secondary"><?= (int)$o['duree_semaines'] ?: '—' ?> sem.</span>
          </div>
          <div class="text-muted small mb-2"><?= htmlspecialchars($o['entreprise_nom']) ?> — <?= htmlspecialchars($o['localisation'] ?: '—') ?></div>
          <div class="mb-2"><?= nl2br(htmlspecialchars(mb_strimwidth($o['description'] ?? '', 0, 240, '…'))) ?></div>
          <?php if (!empty($o['competences'])): ?>
            <div class="small text-muted">Compétences : <?= htmlspecialchars($o['competences']) ?></div>
          <?php endif; ?>
          <div class="mt-auto d-flex gap-2">
            <?php if ((int)$o['deja_cand']>0): ?>
              <span class="btn btn-outline-success disabled flex-fill"><i class="bi bi-check2-circle"></i> Déjà candidat</span>
            <?php else: ?>
              <button class="btn btn-primary flex-fill" data-bs-toggle="modal" data-bs-target="#apply<?= (int)$o['id'] ?>">
                <i class="bi bi-send"></i> Postuler
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Postuler -->
    <div class="modal fade" id="apply<?= (int)$o['id'] ?>" tabindex="-1">
      <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
          <form action="<?= BASE_URL ?>etudiant/postuler.php" method="post" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title">Postuler — <?= htmlspecialchars($o['titre']) ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="offre_id" value="<?= (int)$o['id'] ?>">
              <div class="mb-2">
                <label class="form-label">Message au recruteur (optionnel)</label>
                <textarea class="form-control" name="message" rows="3" placeholder="Quelques lignes pour vous présenter…"></textarea>
              </div>
              <div class="mb-2">
                <label class="form-label">CV (PDF) *</label>
                <input class="form-control" type="file" name="cv" accept=".pdf" required>
              </div>
              <div class="mb-2">
                <label class="form-label">Lettre de motivation (PDF, optionnel)</label>
                <input class="form-control" type="file" name="lm" accept=".pdf">
              </div>
              <div class="small text-muted">Taille max par fichier : 5 Mo.</div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-primary">Envoyer</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
