<?php
// entreprise/mes_offres.php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php'; requireRole(['entreprise']);
require_once __DIR__ . '/../db.php';

$user_id = (int)$_SESSION['user_id'];
// Récup entreprise liée au compte
$st = $pdo->prepare("SELECT entreprise_id FROM tuteurs_entreprise WHERE user_id=?");
$st->execute([$user_id]);
$entreprise_id = (int)($st->fetchColumn() ?: 0);

$page_title = 'Mes offres de stage';
require_once __DIR__ . '/../includes/header.php';

if (!$entreprise_id): ?>
  <div class="alert alert-warning">
    Profil incomplet : aucun <strong>entreprise_id</strong> lié à ce compte.  
    Demande à l’admin d’associer ton utilisateur à une entreprise (table <code>tuteurs_entreprise</code>).
  </div>
<?php else:
  // Filtres simples
  $q = trim($_GET['q'] ?? '');
  $statut = $_GET['statut'] ?? '';

  $w = ["o.entreprise_id=?"]; $params = [$entreprise_id];
  if ($q!=='') { $w[]="(o.titre LIKE ? OR o.description LIKE ? OR o.competences LIKE ? OR o.localisation LIKE ?)"; array_push($params,"%$q%","%$q%","%$q%","%$q%"); }
  if (in_array($statut,['brouillon','publiée','fermée'])) { $w[]="o.statut=?"; $params[]=$statut; }
  $where = "WHERE ".implode(" AND ", $w);

  $sql = "SELECT o.* FROM offres_stage o $where ORDER BY o.date_creation DESC, o.id DESC";
  $st = $pdo->prepare($sql); $st->execute($params);
  $offres = $st->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="mb-0">Mes offres</h2>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#offreModal">
    <i class="bi bi-plus-circle"></i> Nouvelle offre
  </button>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form class="row g-2">
      <div class="col-md-6">
        <label class="form-label">Recherche</label>
        <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="titre, compétence, localisation…">
      </div>
      <div class="col-md-3">
        <label class="form-label">Statut</label>
        <select name="statut" class="form-select" onchange="this.form.submit()">
          <option value="">Tous</option>
          <?php foreach(['brouillon','publiée','fermée'] as $s): ?>
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

<div class="row g-3">
  <?php if (empty($offres)): ?>
    <div class="col-12"><div class="alert alert-info mb-0">Aucune offre.</div></div>
  <?php else: foreach($offres as $o): ?>
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-body d-flex flex-column">
          <div class="d-flex justify-content-between align-items-start">
            <h3 class="h5 mb-0"><?= htmlspecialchars($o['titre']) ?></h3>
            <span class="badge <?= $o['statut']==='publiée'?'text-bg-success':($o['statut']==='brouillon'?'text-bg-secondary':'text-bg-dark') ?>">
              <?= htmlspecialchars(ucfirst($o['statut'])) ?>
            </span>
          </div>
          <div class="small text-muted mb-2"><?= (int)$o['duree_semaines'] ?: '—' ?> sem. — <?= htmlspecialchars($o['localisation'] ?: '—') ?></div>
          <div class="mb-2"><?= nl2br(htmlspecialchars(mb_strimwidth($o['description'] ?? '', 0, 260, '…'))) ?></div>
          <?php if (!empty($o['competences'])): ?>
            <div class="small text-muted">Compétences : <?= htmlspecialchars($o['competences']) ?></div>
          <?php endif; ?>
          <div class="mt-auto d-flex gap-2">
            <button class="btn btn-outline-primary flex-fill"
                    data-bs-toggle="modal"
                    data-bs-target="#offreModal"
                    data-id="<?= (int)$o['id'] ?>"
                    data-titre="<?= htmlspecialchars($o['titre'], ENT_QUOTES) ?>"
                    data-desc="<?= htmlspecialchars($o['description'] ?? '', ENT_QUOTES) ?>"
                    data-comp="<?= htmlspecialchars($o['competences'] ?? '', ENT_QUOTES) ?>"
                    data-loc="<?= htmlspecialchars($o['localisation'] ?? '', ENT_QUOTES) ?>"
                    data-dur="<?= (int)$o['duree_semaines'] ?>"
                    data-statut="<?= htmlspecialchars($o['statut']) ?>">
              Modifier
            </button>
            <form method="post" action="entreprise/save_offre.php" onsubmit="return confirm('Fermer cette offre ?');">
              <input type="hidden" name="action" value="close">
              <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
              <button class="btn btn-outline-dark">Fermer</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<!-- Modal Création/Edition -->
<div class="modal fade" id="offreModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="entreprise/save_offre.php">
        <div class="modal-header">
          <h5 class="modal-title" id="offreTitle">Nouvelle offre</h5>
          <button class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="o_id" value="">
          <input type="hidden" name="action" id="o_action" value="create">
          <div class="row g-2">
            <div class="col-md-8">
              <label class="form-label">Titre *</label>
              <input class="form-control" name="titre" id="o_titre" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Durée (sem.)</label>
              <input class="form-control" type="number" name="duree_semaines" id="o_duree" min="1">
            </div>
            <div class="col-md-6">
              <label class="form-label">Localisation</label>
              <input class="form-control" name="localisation" id="o_loc">
            </div>
            <div class="col-md-6">
              <label class="form-label">Statut</label>
              <select class="form-select" name="statut" id="o_statut">
                <?php foreach(['brouillon','publiée','fermée'] as $s): ?>
                  <option value="<?=$s?>"><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" id="o_desc" rows="4"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Compétences</label>
              <textarea class="form-control" name="competences" id="o_comp" rows="2" placeholder="PHP, SQL, Bootstrap…"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Pré-remplir le modal en mode édition
const md = document.getElementById('offreModal');
md.addEventListener('show.bs.modal', e => {
  const btn = e.relatedTarget;
  const id = btn?.getAttribute('data-id') || '';
  document.getElementById('offreTitle').textContent = id ? 'Modifier une offre' : 'Nouvelle offre';
  document.getElementById('o_action').value = id ? 'update' : 'create';
  document.getElementById('o_id').value = id;
  document.getElementById('o_titre').value = btn?.getAttribute('data-titre') || '';
  document.getElementById('o_desc').value  = btn?.getAttribute('data-desc') || '';
  document.getElementById('o_comp').value  = btn?.getAttribute('data-comp') || '';
  document.getElementById('o_loc').value   = btn?.getAttribute('data-loc') || '';
  document.getElementById('o_duree').value = btn?.getAttribute('data-dur') || '';
  document.getElementById('o_statut').value= btn?.getAttribute('data-statut') || 'brouillon';
});
</script>

<?php endif; // entreprise ok ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
