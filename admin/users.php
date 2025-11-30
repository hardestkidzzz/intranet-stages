<?php
require_once __DIR__ . '/../includes/auth.php'; requireRole(['admin']);
require_once __DIR__ . '/../db.php';

$page_title = 'Administration — Utilisateurs';

// Filtres
$role = $_GET['role'] ?? '';
$q    = trim($_GET['q'] ?? '');

// Actions POST (create / update role / reset pwd / delete)
$errors=[]; $infos=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $act = $_POST['action'] ?? '';
  if ($act==='create') {
    $nom = trim($_POST['nom']??''); $prenom = trim($_POST['prenom']??'');
    $email = trim($_POST['email']??''); $r = $_POST['role_sel']??'etudiant';
    $pwd = $_POST['pwd']??'';
    if ($nom===''||$prenom===''||$email===''||$pwd==='') $errors[]="Tous les champs sont requis pour créer un utilisateur.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[]="Email invalide.";
    elseif (!in_array($r,['etudiant','entreprise','enseignant','admin'])) $errors[]="Rôle invalide.";
    else {
      try {
        $st=$pdo->prepare("INSERT INTO users (role,email,mot_de_passe,nom,prenom) VALUES (?,?,?,?,?)");
        $st->execute([$r,$email,password_hash($pwd,PASSWORD_DEFAULT),$nom,$prenom]);
        $infos[]="Utilisateur créé.";
      } catch(Throwable $e){ $errors[]="Création impossible : ".$e->getMessage(); }
    }
  }
  if ($act==='set_role' && ctype_digit($_POST['id']??'') ) {
    $id=(int)$_POST['id']; $r=$_POST['role_new']??'';
    if (!in_array($r,['etudiant','entreprise','enseignant','admin'])) $errors[]="Rôle invalide.";
    else {
      $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$r,$id]);
      $infos[]="Rôle mis à jour.";
    }
  }
  if ($act==='reset_pwd' && ctype_digit($_POST['id']??'') ) {
    $id=(int)$_POST['id']; $pwd=$_POST['pwd_new']??'';
    if ($pwd==='') $errors[]="Nouveau mot de passe requis.";
    else {
      $pdo->prepare("UPDATE users SET mot_de_passe=? WHERE id=?")->execute([password_hash($pwd,PASSWORD_DEFAULT),$id]);
      $infos[]="Mot de passe réinitialisé.";
    }
  }
  if ($act==='delete' && ctype_digit($_POST['id']??'')) {
    $id=(int)$_POST['id'];
    if ($id===$_SESSION['user_id']) { $errors[]="Impossible de supprimer votre propre compte."; }
    else {
      $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
      $infos[]="Utilisateur supprimé.";
    }
  }
}

// Build WHERE
$where=[]; $params=[];
if (in_array($role,['etudiant','entreprise','enseignant','admin'])) { $where[]="u.role=?"; $params[]=$role; }
if ($q!=='') { $where[]="(u.email LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)"; array_push($params,"%$q%","%$q%","%$q%"); }
$whereSql = $where ? "WHERE ".implode(" AND ",$where) : "";

// Fetch
$st=$pdo->prepare("SELECT u.* FROM users u $whereSql ORDER BY u.date_creation DESC");
$st->execute($params);
$users=$st->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="mb-0">Utilisateurs</h2>
  <a href="../index.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Accueil</a>
</div>

<?php if ($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div><?php endif; ?>
<?php if ($infos): ?><div class="alert alert-success"><?php foreach($infos as $i) echo "<div>".htmlspecialchars($i)."</div>"; ?></div><?php endif; ?>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-sm-3">
        <label class="form-label">Rôle</label>
        <select name="role" class="form-select" onchange="this.form.submit()">
          <option value="">Tous</option>
          <?php foreach (['etudiant','entreprise','enseignant','admin'] as $r): ?>
            <option value="<?=$r?>" <?= $role===$r?'selected':''; ?>><?= ucfirst($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-4">
        <label class="form-label">Recherche</label>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="nom, prénom, email">
      </div>
      <div class="col-sm-2 d-flex align-items-end">
        <button class="btn btn-secondary w-100">Filtrer</button>
      </div>
    </form>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Depuis</th><th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($users)): ?>
            <tr><td colspan="6" class="text-center p-4">Aucun utilisateur.</td></tr>
          <?php else: foreach($users as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td class="fw-semibold"><?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td>
                <form method="post" class="d-inline">
                  <input type="hidden" name="action" value="set_role">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <select name="role_new" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach(['etudiant','entreprise','enseignant','admin'] as $r): ?>
                      <option value="<?=$r?>" <?= $u['role']===$r?'selected':''; ?>><?= ucfirst($r) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td class="text-muted small"><?= htmlspecialchars($u['date_creation']) ?></td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#pwdModal<?=$u['id']?>">Reset mdp</button>
                <form method="post" class="d-inline" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                </form>
                <!-- Modal reset pwd -->
                <div class="modal fade" id="pwdModal<?=$u['id']?>" tabindex="-1">
                  <div class="modal-dialog"><div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Réinitialiser le mot de passe</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <form method="post">
                      <div class="modal-body">
                        <input type="hidden" name="action" value="reset_pwd">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="text" name="pwd_new" class="form-control" required>
                      </div>
                      <div class="modal-footer">
                        <button class="btn btn-primary">Enregistrer</button>
                      </div>
                    </form>
                  </div></div>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Création rapide -->
  <div class="col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h3 class="h6 mb-3">Créer un utilisateur</h3>
        <form method="post">
          <input type="hidden" name="action" value="create">
          <div class="row g-2">
            <div class="col-md-6"><label class="form-label">Prénom</label><input class="form-control" name="prenom" required></div>
            <div class="col-md-6"><label class="form-label">Nom</label><input class="form-control" name="nom" required></div>
          </div>
          <div class="mt-2"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
          <div class="mt-2"><label class="form-label">Mot de passe</label><input class="form-control" type="text" name="pwd" required></div>
          <div class="mt-2">
            <label class="form-label">Rôle</label>
            <select name="role_sel" class="form-select">
              <option value="etudiant">Étudiant</option>
              <option value="entreprise">Entreprise</option>
              <option value="enseignant">Enseignant</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <button class="btn btn-primary mt-3 w-100">Créer</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
