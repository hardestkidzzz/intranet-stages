<?php
/**
 * Autoloader pour les classes du projet
 * Charge automatiquement les classes quand elles sont utilisées
 * 
 * @package IntranetStages
 * @author Projet BTS SIO
 */

spl_autoload_register(function ($className) {
    $classFile = __DIR__ . '/' . $className . '.php';
    
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Ou chargement manuel des classes principales
// require_once __DIR__ . '/Eleve.php';
// require_once __DIR__ . '/EleveDAO.php';
// require_once __DIR__ . '/Entreprise.php';
// require_once __DIR__ . '/EntrepriseDAO.php';
// require_once __DIR__ . '/Stage.php';
// require_once __DIR__ . '/StageDAO.php';
// require_once __DIR__ . '/Tuteur.php';
// require_once __DIR__ . '/TuteurDAO.php';

