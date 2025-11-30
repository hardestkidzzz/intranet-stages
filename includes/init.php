<?php
// Un cookie de session dédié à l'intranet (évite les collisions avec )
if (session_status() === PHP_SESSION_NONE) {
  session_name('INTRANET_STAGES_SESSID');
  session_start();
}

// Chemin de base du projet (adapter si le dossier change)
define('BASE_URL', '/intranet-stages/');
