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
    <div style="height: 300px; position: relative;">
      <canvas id="chart30"></canvas>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="h6">Offres par statut</h3>
        <div style="height: 250px; position: relative;">
          <canvas id="pieOffres"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="h6">Candidatures par statut</h3>
        <div style="height: 250px; position: relative;">
          <canvas id="pieCands"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="h6">Stages par statut</h3>
        <div style="height: 250px; position: relative;">
          <canvas id="pieStages"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const labels = <?= json_encode(array_keys($offresSerie)) ?>;
  const dataOffres = <?= json_encode(array_values($offresSerie)) ?>;
  const dataCands  = <?= json_encode(array_values($candsSerie)) ?>;
  const dataStages = <?= json_encode(array_values($stagesSerie)) ?>;

  // Graphique ligne - Activité 30 jours
  new Chart(document.getElementById('chart30'), {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label:'Offres', data: dataOffres, tension: 0.25, borderColor: '#198754', backgroundColor: 'rgba(25, 135, 84, 0.1)' },
        { label:'Candidatures', data: dataCands, tension: 0.25, borderColor: '#0d6efd', backgroundColor: 'rgba(13, 110, 253, 0.1)' },
        { label:'Stages', data: dataStages, tension: 0.25, borderColor: '#fd7e14', backgroundColor: 'rgba(253, 126, 20, 0.1)' }
      ]
    },
    options: { 
      responsive: true, 
      maintainAspectRatio: false,
      animation: { duration: 500 },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });

  // Fonction pour créer les graphiques camembert
  function pie(el, map) {
    const labels = Object.keys(map);
    const values = Object.values(map).map(v => parseInt(v) || 0);
    
    // Ne pas créer si pas de données
    if (values.length === 0 || values.every(v => v === 0)) {
      document.getElementById(el).parentElement.innerHTML = '<p class="text-muted text-center py-4">Aucune donnée</p>';
      return;
    }
    
    return new Chart(document.getElementById(el), {
      type: 'doughnut',
      data: { 
        labels, 
        datasets: [{ 
          data: values,
          backgroundColor: ['#198754', '#0d6efd', '#ffc107', '#dc3545', '#6c757d', '#0dcaf0']
        }]
      },
      options: { 
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 500 },
        plugins: { 
          legend: { position: 'bottom' } 
        } 
      }
    });
  }

  pie('pieOffres', <?= json_encode($pie_offres) ?>);
  pie('pieCands',  <?= json_encode($pie_cands) ?>);
  pie('pieStages', <?= json_encode($pie_stage) ?>);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
