<?php
require_once __DIR__ . '/../includes/auth.php'; requireRole(['admin']);
require_once __DIR__ . '/../db.php';
$page_title = 'Administration — Stats';

/* --- KPIs --- */
$kpi = [
  'offres_publiees' => (int)$pdo->query("SELECT COUNT(*) FROM offres_stage WHERE statut='publiée'")->fetchColumn(),
  'candidatures'    => (int)$pdo->query("SELECT COUNT(*) FROM candidatures")->fetchColumn(),
  'accept_rate'     => 0,
  'stages_encours'  => (int)$pdo->query("SELECT COUNT(*) FROM stages WHERE statut='en_cours'")->fetchColumn(),
];
$acc = (int)$pdo->query("SELECT COUNT(*) FROM candidatures WHERE statut='acceptée'")->fetchColumn();
$kpi['accept_rate'] = $kpi['candidatures'] ? round(100 * $acc / $kpi['candidatures'], 1) : 0;

/* --- Séries 30 jours --- */
function series30($pdo, $table, $dateCol, $where='') {
  $sql="SELECT DATE($dateCol) d, COUNT(*) n FROM $table ".
       ($where ? "WHERE $where AND " : "WHERE ") .
       "$dateCol >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) GROUP BY DATE($dateCol)";
  $st=$pdo->query($sql);
  $map=[]; foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r){ $map[$r['d']]=(int)$r['n']; }
  // normalise 30 jours
  $out=[]; for($i=29;$i>=0;$i--){
    $d=(new DateTime())->modify("-$i day")->format('Y-m-d'); $out[$d]=$map[$d]??0;
  }
  return $out;
}
$offresSerie = series30($pdo, 'offres_stage', 'date_creation');
$candsSerie  = series30($pdo, 'candidatures', 'date_candidature');
$stagesSerie = series30($pdo, 'stages',       'date_creation');

/* --- Répartitions --- */
$pie_offres = $pdo->query("SELECT statut, COUNT(*) n FROM offres_stage GROUP BY statut")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
$pie_cands  = $pdo->query("SELECT statut, COUNT(*) n FROM candidatures GROUP BY statut")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
$pie_stage  = $pdo->query("SELECT statut, COUNT(*) n FROM stages GROUP BY statut")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h2 class="mb-0">Tableau de bord</h2>
  <a href="../index.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Accueil</a>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="card shadow-sm border-success-subtle">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div><div class="text-muted small">Offres publiées</div><div class="fs-4 fw-semibold"><?= $kpi['offres_publiees'] ?></div></div>
        <i class="bi bi-megaphone fs-2 text-success"></i>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-primary-subtle">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div><div class="text-muted small">Candidatures</div><div class="fs-4 fw-semibold"><?= $kpi['candidatures'] ?></div></div>
        <i class="bi bi-envelope-paper fs-2 text-primary"></i>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-warning-subtle">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div><div class="text-muted small">Taux d’acceptation</div><div class="fs-4 fw-semibold"><?= $kpi['accept_rate'] ?>%</div></div>
        <i class="bi bi-graph-up-arrow fs-2 text-warning"></i>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm border-info-subtle">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div><div class="text-muted small">Stages en cours</div><div class="fs-4 fw-semibold"><?= $kpi['stages_encours'] ?></div></div>
        <i class="bi bi-briefcase fs-2 text-info"></i>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <h3 class="h6">Activité — 30 derniers jours</h3>
    <canvas id="chart30" height="80"></canvas>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="h6">Offres par statut</h3>
        <canvas id="pieOffres" height="200"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="h6">Candidatures par statut</h3>
        <canvas id="pieCands" height="200"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="h6">Stages par statut</h3>
        <canvas id="pieStages" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode(array_keys($offresSerie)) ?>;
const dataOffres = <?= json_encode(array_values($offresSerie)) ?>;
const dataCands  = <?= json_encode(array_values($candsSerie)) ?>;
const dataStages = <?= json_encode(array_values($stagesSerie)) ?>;

new Chart(document.getElementById('chart30'), {
  type: 'line',
  data: {
    labels,
    datasets: [
      { label:'Offres', data: dataOffres, tension:.25 },
      { label:'Candidatures', data: dataCands, tension:.25 },
      { label:'Stages', data: dataStages, tension:.25 }
    ]
  },
  options: { responsive:true, maintainAspectRatio:false }
});

function pie(el, map){
  const labels = Object.keys(map), values = Object.values(map);
  return new Chart(document.getElementById(el), {
    type:'doughnut',
    data:{ labels, datasets:[{ data: values }]},
    options:{ plugins:{ legend:{ position:'bottom' } } }
  });
}
pie('pieOffres', <?= json_encode($pie_offres) ?>);
pie('pieCands',  <?= json_encode($pie_cands) ?>);
pie('pieStages', <?= json_encode($pie_stage) ?>);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
