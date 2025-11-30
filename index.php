<?php
// intranet-stages/index.php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/db.php';

// Sécurité minimale : si pas connecté -> login
if (empty($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . 'auth/login.php'); exit;
}

$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'] ?? '';

// =====================
// Filtres (GET)
// =====================
$q            = trim($_GET['q'] ?? '');                  // recherche globale
$statut       = $_GET['statut'] ?? '';                   // en_cours / terminé / rupture / préparation
$entrepriseId = ctype_digit($_GET['entreprise'] ?? '') ? (int)$_GET['entreprise'] : 0;
$enseignantId = ctype_digit($_GET['enseignant'] ?? '') ? (int)$_GET['enseignant'] : 0;
$dateMin      = $_GET['date_min'] ?? '';
$dateMax      = $_GET['date_max'] ?? '';

// Paging
$limit  = 12;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// =====================
// Contrainte par rôle
// =====================
// - enseignant : ne voit que ses stages
// - entreprise : ne voit que les stages de son entreprise
// - étudiant : ne voit que son stage
$where = [];
$params = [];

if ($role === 'enseignant') {
  $where[] = "s.tuteur_enseignant_user_id = ?";
  $params[] = $user_id;
} elseif ($role === 'entreprise') {
  // récupérer l'entreprise du tuteur entreprise
  $te = $pdo->prepare("SELECT entreprise_id FROM tuteurs_entreprise WHERE user_id=?");
  $te->execute([$user_id]);
  $eid = (int)($te->fetchColumn() ?: 0);
  if ($eid) {
    $where[] = "s.entreprise_id = ?";
    $params[] = $eid;
  } else {
    // rien à afficher si profil incomplet
    $where[] = "1=0";
  }
} elseif ($role === 'etudiant') {
  $where[] = "s.etudiant_user_id = ?";
  $params[] = $user_id;
}

// =====================
// Filtres avancés
// =====================
if ($q !== '') {
  $where[] = "(CONCAT(ue.prenom,' ',ue.nom) LIKE ? OR e.nom LIKE ? OR s.sujet LIKE ?)";
  $params[] = "%$q%";
  $params[] = "%$q%";
  $params[] = "%$q%";
}
if (in_array($statut, ['préparation','en_cours','terminé','rupture'])) {
  $where[] = "s.statut = ?";
  $params[] = $statut;
}
if ($entrepriseId > 0) {
  $where[] = "s.entreprise_id = ?";
  $params[] = $entrepriseId;
}
if ($enseignantId > 0) {
  $where[] = "s.tuteur_enseignant_user_id = ?";
  $params[] = $enseignantId;
}
if ($dateMin !== '') {
  $where[] = "s.date_debut >= ?";
  $params[] = $dateMin;
}
if ($dateMax !== '') {
  $where[] = "s.date_fin <= ?";
  $params[] = $dateMax;
}

$whereSql = $where ? "WHERE ".implode(" AND ", $where) : "";

// =====================
// KPIs
// =====================
$kpiAll = [
  'total'     => 0,
  'en_cours'  => 0,
  'termines'  => 0,
  'ruptures'  => 0
];

$kpiSql = "
  SELECT 
    COUNT(*) AS total,
    SUM(s.statut='en_cours')  AS en_cours,
    SUM(s.statut='terminé')   AS termines,
    SUM(s.statut='rupture')   AS ruptures
  FROM stages s
  JOIN users ue ON ue.id = s.etudiant_user_id
  JOIN entreprises e ON e.id = s.entreprise_id
  LEFT JOIN users ut ON ut.id = s.tuteur_enseignant_user_id
  $whereSql
";
$stKpi = $pdo->prepare($kpiSql);
$stKpi->execute($params);
$rowKpi = $stKpi->fetch(PDO::FETCH_ASSOC);
if ($rowKpi) {
  $kpiAll['total']    = (int)$rowKpi['total'];
  $kpiAll['en_cours'] = (int)$rowKpi['en_cours'];
  $kpiAll['termines'] = (int)$rowKpi['termines'];
  $kpiAll['ruptures'] = (int)$rowKpi['ruptures'];
}

// =====================
// Compte total (paging)
// =====================
$countSql = "
  SELECT COUNT(*) 
  FROM stages s
  JOIN users ue ON ue.id = s.etudiant_user_id
  JOIN entreprises e ON e.id = s.entreprise_id
  LEFT JOIN users ut ON ut.id = s.tuteur_enseignant_user_id
  $whereSql
";
$stCount = $pdo->prepare($countSql);
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

// =====================
// Données tableau
// =====================
$dataSql = "
  SELECT
    s.*,
    ue.prenom AS etu_prenom, ue.nom AS etu_nom, ue.email AS etu_email,
    e.nom AS entreprise_nom,
    ut.prenom AS ens_prenom, ut.nom AS ens_nom
  FROM stages s
  JOIN users ue ON ue.id = s.etudiant_user_id
  JOIN entreprises e ON e.id = s.entreprise_id
  LEFT JOIN users ut ON ut.id = s.tuteur_enseignant_user_id
  $whereSql
  ORDER BY s.date_debut DESC, s.id DESC
  LIMIT $limit OFFSET $offset
";
$st = $pdo->prepare($dataSql);
$st->execute($params);
$stages = $st->fetchAll(PDO::FETCH_ASSOC);

// =====================
// Listes déroulantes (entreprises / enseignants)
// =====================
$ents = $pdo->query("SELECT id, nom FROM entreprises ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$profs = $pdo->query("SELECT id, nom, prenom FROM users WHERE role='enseignant' ORDER BY nom, prenom")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Tableau de bord — Stages';
require_once __DIR__ . '/includes/header.php';
?>

<!-- KPIs -->
<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card shadow-sm border-primary-subtle">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="text-muted small">Total stages</div>
          <div class="fs-4 fw-semibold"><?= $kpiAll['total'] ?></div>
        </div>
        <i class="bi bi-briefcase fs-2 text-primary"></i>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-warning-subtle">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="text-muted small">En cours</div>
          <div class="fs-4 fw-semibold"><?= $kpiAll['en_cours'] ?></div>
        </div>
        <i class="bi bi-hourglass-split fs-2 text-warning"></i>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-success-subtle">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="text-muted small">Terminés</div>
          <div class="fs-4 fw-semibold"><?= $kpiAll['termines'] ?></div>
        </div>
        <i class="bi bi-check2-circle fs-2 text-success"></i>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-danger-subtle">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="text-muted small">Ruptures</div>
          <div class="fs-4 fw-semibold"><?= $kpiAll['ruptures'] ?></div>
        </div>
        <i class="bi bi-x-octagon fs-2 text-danger"></i>
      </div>
    </div>
  </div>
</div>

<!-- Barre d'actions -->
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="mb-0">Stages</h2>
  <div class="d-flex gap-2">
    <?php if (in_array($role, ['admin','enseignant'])): ?>
      <a class="btn btn-primary" href="stages/add_stage.php"><i class="bi bi-plus-circle"></i> Nouveau stage</a>
      <!-- Bouton Export CSV -->
      <a class="btn btn-success" href="export_csv.php?<?= http_build_query($_GET) ?>">
        <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
      </a>
    <?php endif; ?>
    <?php if ($role === 'admin'): ?>
      <a class="btn btn-outline-secondary" href="admin/affectations.php"><i class="bi bi-person-gear"></i> Affectations</a>
      <a class="btn btn-outline-primary" href="admin/stats.php"><i class="bi bi-graph-up"></i> Stats</a>
    <?php endif; ?>
  </div>
</div>

<!-- Filtres -->
<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form class="row g-2" method="get" id="filterForm">
      <div class="col-md-3">
        <label class="form-label">Recherche</label>
        <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="étudiant, entreprise, sujet…">
      </div>
      <div class="col-md-2">
        <label class="form-label">Statut</label>
        <select name="statut" class="form-select" onchange="this.form.submit()">
          <option value="">Tous</option>
          <?php foreach (['préparation','en_cours','terminé','rupture'] as $s): ?>
            <option value="<?=$s?>" <?= $statut===$s?'selected':''; ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Entreprise</label>
        <select name="entreprise" class="form-select" onchange="this.form.submit()">
          <option value="0">Toutes</option>
          <?php foreach ($ents as $e): ?>
            <option value="<?= (int)$e['id'] ?>" <?= $entrepriseId===(int)$e['id']?'selected':''; ?>>
              <?= htmlspecialchars($e['nom']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Tuteur enseignant</label>
        <select name="enseignant" class="form-select" onchange="this.form.submit()">
          <option value="0">Tous</option>
          <?php foreach ($profs as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= $enseignantId===(int)$p['id']?'selected':''; ?>>
              <?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Début ≥</label>
        <input type="date" name="date_min" class="form-control" value="<?= htmlspecialchars($dateMin) ?>" onchange="this.form.submit()">
      </div>
      <div class="col-md-2">
        <label class="form-label">Fin ≤</label>
        <input type="date" name="date_max" class="form-control" value="<?= htmlspecialchars($dateMax) ?>" onchange="this.form.submit()">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-secondary w-100">Filtrer</button>
      </div>
    </form>
  </div>
</div>

<!-- Tableau -->
<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Étudiant</th>
          <th>Entreprise</th>
          <th>Période</th>
          <th>Statut</th>
          <th>Tuteur enseignant</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($stages)): ?>
        <tr><td colspan="6" class="text-center p-4">Aucun stage trouvé.</td></tr>
      <?php else: foreach ($stages as $s): ?>
        <tr>
          <td class="fw-semibold">
            <?= htmlspecialchars($s['etu_prenom'].' '.$s['etu_nom']) ?><br>
            <span class="small text-muted"><?= htmlspecialchars($s['etu_email']) ?></span>
          </td>
          <td><?= htmlspecialchars($s['entreprise_nom']) ?></td>
          <td><?= htmlspecialchars($s['date_debut']) ?> → <?= htmlspecialchars($s['date_fin']) ?></td>
          <td>
            <span class="badge
              <?= $s['statut']==='en_cours' ? 'text-bg-warning' :
                 ($s['statut']==='terminé' ? 'text-bg-success' :
                 ($s['statut']==='rupture' ? 'text-bg-danger' : 'text-bg-secondary')) ?>">
              <?= htmlspecialchars(ucfirst($s['statut'])) ?>
            </span>
          </td>
          <td><?= htmlspecialchars(trim(($s['ens_prenom']??'').' '.($s['ens_nom']??'')) ?: '—') ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="enseignant/suivi.php?stage_id=<?= (int)$s['id'] ?>">
              Ouvrir
            </a>
            <!-- Bouton Convention de stage -->
            <a class="btn btn-sm btn-outline-success" href="convention.php?id=<?= (int)$s['id'] ?>" target="_blank" title="Convention de stage">
              <i class="bi bi-file-text"></i>
            </a>
            <?php if (in_array($role, ['admin','enseignant'])): ?>
              <a class="btn btn-sm btn-outline-warning" href="stages/edit_stage.php?id=<?= (int)$s['id'] ?>">
                Modifier
              </a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="mt-3">
  <ul class="pagination justify-content-center">
    <?php
      function linkWithPage($p){
        $params = $_GET; $params['page'] = $p;
        return '?' . http_build_query($params);
      }
    ?>
    <li class="page-item <?= $page<=1?'disabled':'' ?>">
      <a class="page-link" href="<?= $page<=1?'#':linkWithPage($page-1) ?>">Précédent</a>
    </li>
    <?php for($i=1;$i<=$pages;$i++): ?>
      <li class="page-item <?= $i===$page?'active':'' ?>">
        <a class="page-link" href="<?= linkWithPage($i) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
    <li class="page-item <?= $page>=$pages?'disabled':'' ?>">
      <a class="page-link" href="<?= $page>=$pages?'#':linkWithPage($page+1) ?>">Suivant</a>
    </li>
  </ul>
  <p class="text-center text-muted mb-0">Total : <?= $total ?> stages</p>
</nav>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
