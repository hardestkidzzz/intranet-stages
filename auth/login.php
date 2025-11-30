<?php
// Démarre une session dédiée à l'intranet (+ BASE_URL)
require_once __DIR__ . '/../includes/init.php';

// Connexion DB de l'INTRANET (vérifie que ce db.php pointe bien sur ta base intranet)
require_once __DIR__ . '/../db.php';

$errors = [];
$email = trim($_POST['email'] ?? '');
$pwd   = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($email === '' || $pwd === '') {
    $errors[] = "Email et mot de passe requis.";
  } else {
    // Vérifier l'utilisateur par email
    $st = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $st->execute([$email]);
    $u = $st->fetch(PDO::FETCH_ASSOC);

    // Si user trouvé, vérifier le hash
    if (!$u || !password_verify($pwd, $u['mot_de_passe'])) {
      $errors[] = "Identifiants invalides.";
    } else {
      // OK: créer la session
      $_SESSION['user_id'] = (int)$u['id'];
      $_SESSION['role']    = $u['role'];
      $_SESSION['nom']     = $u['nom'];
      $_SESSION['prenom']  = $u['prenom'];

      // Redirection sûre (utilise BASE_URL définie dans init.php)
      header('Location: ' . BASE_URL . 'index.php');
      exit;
    }
  }
}

$page_title = 'Connexion';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <h2 class="mb-3">Connexion</h2>
    <?php if ($errors): ?>
      <div class="alert alert-danger"><?php foreach($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Mot de passe</label>
        <input class="form-control" type="password" name="password" required>
      </div>
      <button class="btn btn-primary w-100">Se connecter</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
