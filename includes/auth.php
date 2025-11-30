<?php
// Utiliser le même nom de session que le reste de l'application
if (session_status() === PHP_SESSION_NONE) {
  session_name('INTRANET_STAGES_SESSID');
  session_start();
}

// Définir BASE_URL si pas déjà défini
if (!defined('BASE_URL')) {
  define('BASE_URL', '/intranet-stages/');
}

function requireLogin() {
  if (empty($_SESSION['user_id'])) { 
    header('Location: ' . BASE_URL . 'auth/login.php'); 
    exit; 
  }
}

function requireRole(array $roles) {
  requireLogin();
  if (empty($_SESSION['role']) || !in_array($_SESSION['role'], $roles, true)) {
    http_response_code(403); 
    die('Accès interdit');
  }
}
