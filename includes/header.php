<?php
// Session dédiée à l'intranet (évite les collisions avec )
if (session_status() === PHP_SESSION_NONE) {
  session_name('INTRANET_STAGES_SESSID');
  session_start();
}
$role = $_SESSION['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Intranet Stages' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- IMPORTANT: base doit être DANS le <head> -->
  <base href="/intranet-stages/">

  <!-- Bootstrap 5.3+ -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">

  <script>
    // Appliquer le thème dès le chargement (évite le flash clair/sombre)
    (function () {
      try {
        const saved = localStorage.getItem('theme');
        if (saved === 'dark' || saved === 'light') {
          document.documentElement.setAttribute('data-bs-theme', saved);
        }
      } catch(e) {}
    })();
  </script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <i class="bi bi-building"></i> Intranet Stages
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <!-- Menu de gauche -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if (!empty($_SESSION['user_id'])): ?>
          <?php if ($role === 'etudiant'): ?>
            <li class="nav-item"><a class="nav-link" href="etudiant/offres.php"><i class="bi bi-search"></i> Offres</a></li>
            <li class="nav-item"><a class="nav-link" href="etudiant/candidatures.php"><i class="bi bi-envelope-paper"></i> Mes candidatures</a></li>
          <?php elseif ($role === 'entreprise'): ?>
            <li class="nav-item"><a class="nav-link" href="entreprise/mes_offres.php"><i class="bi bi-megaphone"></i> Mes offres</a></li>
            <li class="nav-item"><a class="nav-link" href="admin/candidatures.php"><i class="bi bi-envelope-paper"></i> Candidatures</a></li>
          <?php elseif ($role === 'enseignant'): ?>
            <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-briefcase"></i> Stages</a></li>
            <li class="nav-item"><a class="nav-link" href="admin/affectations.php"><i class="bi bi-person-gear"></i> Affectations</a></li>
          <?php elseif ($role === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-briefcase"></i> Stages</a></li>
            <li class="nav-item"><a class="nav-link" href="admin/affectations.php"><i class="bi bi-person-gear"></i> Affectations</a></li>
            <li class="nav-item"><a class="nav-link" href="admin/entreprises.php"><i class="bi bi-building-gear"></i> Entreprises</a></li>
            <li class="nav-item"><a class="nav-link" href="admin/users.php"><i class="bi bi-people"></i> Utilisateurs</a></li>
            <li class="nav-item"><a class="nav-link" href="admin/stats.php"><i class="bi bi-graph-up"></i> Stats</a></li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>

      <!-- Zone de droite -->
      <div class="d-flex align-items-center gap-2">
        <?php if (!empty($_SESSION['user_id'])): ?>
          <span class="text-white-50 me-2 d-none d-sm-inline">
            <?= htmlspecialchars($_SESSION['nom'] ?? '') ?>
            <?php if($role): ?>
              <span class="badge bg-secondary ms-1"><?= htmlspecialchars(ucfirst($role)) ?></span>
            <?php endif; ?>
          </span>
          <button id="themeToggle" class="btn btn-outline-light btn-sm" type="button" title="Thème">
            <i class="bi bi-moon"></i>
          </button>
          <a class="btn btn-danger" href="auth/logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        <?php else: ?>
          <a class="btn btn-outline-light me-2" href="auth/login.php">Connexion</a>
          <a class="btn btn-primary" href="auth/register.php">Inscription</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div class="container mb-5">

<!-- Flash messages optionnels -->
<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Toast container -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

