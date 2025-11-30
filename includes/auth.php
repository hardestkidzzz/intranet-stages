<?php
if (session_status()===PHP_SESSION_NONE) session_start();

function requireLogin() {
  if (empty($_SESSION['user_id'])) { header('Location: /intranet-stages/auth/login.php'); exit; }
}
function requireRole(array $roles) {
  requireLogin();
  if (empty($_SESSION['role']) || !in_array($_SESSION['role'],$roles,true)) {
    http_response_code(403); die('Accès interdit');
  }
}
