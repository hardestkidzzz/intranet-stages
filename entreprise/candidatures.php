<?php
// entreprise/candidatures.php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php'; requireRole(['entreprise']);
require_once __DIR__ . '/../db.php';

$user_id = (int)$_SESSION['user_id'];

// Récupérer l’entreprise liée à ce compte
$st = $pdo->prepare("SELECT entreprise_id FROM tuteurs_entreprise WHERE user_id=?");
$st->execute([$user_id]);
$entreprise_id = (int)($st->fetchColumn() ?: 0);
if (!$entreprise_id) {
  $_SESSION['flash_error'] = "Profil entreprise incomplet. Contacte l'admin pour lier ton compte à une entreprise.";
  header('Location: '.BASE_URL.'index.php'); exit;
}

// Changement de statut (POST)
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $id = ctype_digit($_POST['id'] ?? '') ? (int)$_POST['id'] : 0;
  $to = $_POST['to'] ?? '';
  if ($id && in_array($to,['en_attente','acceptée','refusée'])) {
    // Vérifier que la candidature appartient bien à une offre de CETTE entreprise
    $ck = $pdo->prepare("
      SELECT COUNT(*) 
      FROM candidatures c 
      JOIN offres_stage o ON o.id=c.offre_id 
      WHERE c.id=? AND o.entreprise_id=?");
    $ck->execute([$id,$entreprise_id]);
    if ((int)$ck->fetchColumn() === 1) {
      $pdo->prepare("UPDATE candidatures SET statut=? WHERE id=?")->execute([$to,$id]);
      $_SESSION['flash_success'] = "Candidature #$id → ".ucfirst($to).".";
    } else {
      $_SESSION['flash_error'] = "Accès refusé sur cette candidature.";
    }
  }
  header('Location: '.BASE_URL.'entreprise/candidatures.php'); exit;
}

// Filtres
$q = trim($_GET['q'] ?? '');
$statut = $_GET['statut'] ?? '';

$w = ["o.entreprise_id=?"]; 
$params = [$entreprise_id];

if ($q!=='') {
  $w[]="(o.titre LIKE ? OR ue.nom LIKE ? OR ue.prenom LIKE ? OR ue.email LIKE ?)";
  array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
}
if (in_array($statut,['en_attente','acceptée','refusée'])) {
  $w[]="c.statut=?";
  $params[] = $statut;
}
$where = "WHERE ".implode(" AND ", $w);

// Récup candidatures
$sql = "
  SELECT c.*, o.titre, ue.prenom AS etu_prenom, ue.nom AS etu_nom, ue.email AS etu_email
  FROM candidatures c
  JOIN offres_stage o ON o.id=c.offre_id
  JOIN users ue ON ue.id=c.etudiant_user_id
  $where
  ORDER BY c.date_candidature DESC, c.id DESC
";
$st = $pdo->prepare($sql);
$st->execute($params);
$cands = $st->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Candidatures — Entreprise";
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="mb-0">Candidatures reçues</h2>
  <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>entreprise/mes_offres.php"><i class="bi bi-megaphone"></i> Mes offres</a>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form class="row g-2">
      <div class="col-md-6">
        <label class="form-label">Recherche</label>
        <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="offre, nom, email…">
      </div>
      <div class="col-md-3">
        <label class="form-label">Statut</label>
        <select name="statut" class="form-select" onchange="this.form.submit()">
          <option value="">Tous</option>
          <?php foreach (['en_attente','acceptée','refusée'] as $s): ?>
            <option value="<?=$s?>" <?= $statut===$s?'selected':''; ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-secondary w-100">Filtrer</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Offre</th>
          <th>Étudiant</th>
          <th>Message</th>
          <th>CV</th>
          <th>LM</th>
          <th>Statut</th>
          <th>Date</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($cands)): ?>
        <tr><td colspan="8" class="text-center p-4">Aucune candidature reçue.</td></tr>
      <?php else: foreach ($cands as $c): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($c['titre']) ?></td>
          <td>
            <?= htmlspecialchars($c['etu_prenom'].' '.$c['etu_nom']) ?><br>
            <span class="small text-muted"><?= htmlspecialchars($c['etu_email']) ?></span>
          </td>
          <td><?= nl2br(htmlspecialchars($c['message'] ?? '—')) ?></td>
          <td>
            <?php if ($c['cv_path']): ?>
              <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>download.php?type=cv&id=<?= (int)$c['id'] ?>">CV</a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php if ($c['lm_path']): ?>
              <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>download.php?type=lm&id=<?= (int)$c['id'] ?>">LM</a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <span class="badge <?= $c['statut']==='acceptée'?'text-bg-success':($c['statut']==='refusée'?'text-bg-danger':'text-bg-warning') ?>">
              <?= htmlspecialchars(ucfirst($c['statut'])) ?>
            </span>
          </td>
          <td class="text-muted small"><?= htmlspecialchars($c['date_candidature']) ?></td>
          <td class="text-end">
            <form method="post" class="d-inline">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <input type="hidden" name="to" value="acceptée">
              <button class="btn btn-sm btn-success">Accepter</button>
            </form>
            <form method="post" class="d-inline">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <input type="hidden" name="to" value="refusée">
              <button class="btn btn-sm btn-outline-danger">Refuser</button>
            </form>
            <form method="post" class="d-inline">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <input type="hidden" name="to" value="en_attente">
              <button class="btn btn-sm btn-outline-secondary">En attente</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
