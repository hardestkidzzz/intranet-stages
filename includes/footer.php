</div> <!-- .container -->

<!-- JS Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Thème clair/sombre + icône bouton
document.addEventListener('DOMContentLoaded', function() {
  const root = document.documentElement;
  const key = 'theme';
  const btn = document.getElementById('themeToggle');
  
  if (!btn) return;
  
  // Déterminer le thème initial
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const saved = localStorage.getItem(key);
  const initial = saved || (prefersDark ? 'dark' : 'light');
  
  // Appliquer le thème
  root.setAttribute('data-bs-theme', initial);
  
  // Mettre à jour l'icône
  function setIcon(mode) {
    if (mode === 'dark') {
      btn.innerHTML = '<i class="bi bi-sun-fill"></i>';
      btn.title = 'Passer en mode clair';
    } else {
      btn.innerHTML = '<i class="bi bi-moon-fill"></i>';
      btn.title = 'Passer en mode sombre';
    }
  }
  
  setIcon(initial);
  
  // Gérer le clic
  btn.addEventListener('click', function() {
    const cur = root.getAttribute('data-bs-theme') || 'light';
    const next = (cur === 'light') ? 'dark' : 'light';
    root.setAttribute('data-bs-theme', next);
    localStorage.setItem(key, next);
    setIcon(next);
  });
});
</script>

<?php
// ---- FLASH -> TOASTS ----
// Tu peux remplir ces clés depuis tes pages :
// $_SESSION['flash_success'] = "Candidature envoyée !";
// $_SESSION['flash_error']   = "Erreur d'upload.";
// $_SESSION['flash_info']    = "Info pour l'utilisateur.";
// $_SESSION['flash_warning'] = "Attention...";

// Supporte aussi des tableaux de messages (plusieurs toasts par type).
$flash = [
  'success' => $_SESSION['flash_success'] ?? null,
  'error'   => $_SESSION['flash_error']   ?? null,
  'info'    => $_SESSION['flash_info']    ?? null,
  'warning' => $_SESSION['flash_warning'] ?? null,
];
// On vide après lecture
unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['flash_info'], $_SESSION['flash_warning']);
?>

<script>
(function() {
  // Conteneur déjà présent dans header.php : <div id="toastContainer" class="toast-container ..."></div>
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const data = <?=
    json_encode($flash, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
  ?>;

  const mapType = {
    success: 'text-bg-success',
    error:   'text-bg-danger',
    info:    'text-bg-info',
    warning: 'text-bg-warning'
  };

  function toArray(v){ return Array.isArray(v) ? v : (v ? [v] : []); }

  function makeToast(type, message, delay=4500) {
    const cls = mapType[type] || 'text-bg-secondary';
    const el = document.createElement('div');
    el.className = `toast align-items-center border-0 ${cls}`;
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    el.setAttribute('aria-atomic', 'true');

    el.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    `;
    container.appendChild(el);
    const t = new bootstrap.Toast(el, { delay, autohide: true });
    t.show();
  }

  Object.entries(data).forEach(([type, val]) => {
    toArray(val).forEach(msg => { if (msg) makeToast(type, msg); });
  });
})();
</script>

</body>
</html>
