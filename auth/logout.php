<?php
// Utiliser le même nom de session que le reste de l'application
session_name('INTRANET_STAGES_SESSID');
session_start();
session_unset();
session_destroy();

// Supprimer le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header('Location: /intranet-stages/auth/login.php');
exit;
