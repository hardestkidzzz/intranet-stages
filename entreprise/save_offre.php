<?php
// entreprise/save_offre.php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php'; requireRole(['entreprise']);
require_once __DIR__ . '/../db.php';

$user_id = (int)$_SESSION['user_id'];
$st = $pdo->prepare("SELECT entreprise_id FROM tuteurs_entreprise WHERE user_id=?");
$st->execute([$user_id]);
$entreprise_id = (int)($st->fetchColumn() ?: 0);
if (!$entreprise_id) { $_SESSION['flash_error']="Aucune entreprise liée."; header('Location: '.BASE_URL.'entreprise/mes_offres.php'); exit; }

$act = $_POST['action'] ?? '';

try {
  if ($act==='create' || $act==='update') {
    $titre = trim($_POST['titre'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $comp  = trim($_POST['competences'] ?? '');
    $loc   = trim($_POST['localisation'] ?? '');
    $dur   = ctype_digit($_POST['duree_semaines'] ?? '') ? (int)$_POST['duree_semaines'] : null;
    $statut= $_POST['statut'] ?? 'brouillon';
    if (!in_array($statut,['brouillon','publiée','fermée'])) $statut='brouillon';
    if ($titre==='') throw new RuntimeException("Le titre est requis.");

    if ($act==='create') {
      $ins=$pdo->prepare("INSERT INTO offres_stage (entreprise_id, titre, description, competences, localisation, duree_semaines, statut, date_creation)
                          VALUES (?,?,?,?,?,?,?, NOW())");
      $ins->execute([$entreprise_id,$titre,$desc?:null,$comp?:null,$loc?:null,$dur,$statut]);
      $_SESSION['flash_success']="Offre créée.";
    } else {
      if (empty($_POST['id']) || !ctype_digit($_POST['id'])) throw new RuntimeException("Offre invalide.");
      $id=(int)$_POST['id'];
      // vérifier propriété
      $check=$pdo->prepare("SELECT COUNT(*) FROM offres_stage WHERE id=? AND entreprise_id=?");
      $check->execute([$id,$entreprise_id]);
      if (!(int)$check->fetchColumn()) throw new RuntimeException("Accès refusé.");
      $up=$pdo->prepare("UPDATE offres_stage SET titre=?, description=?, competences=?, localisation=?, duree_semaines=?, statut=? WHERE id=?");
      $up->execute([$titre,$desc?:null,$comp?:null,$loc?:null,$dur,$statut,$id]);
      $_SESSION['flash_success']="Offre mise à jour.";
    }
  } elseif ($act==='close') {
    if (empty($_POST['id']) || !ctype_digit($_POST['id'])) throw new RuntimeException("Offre invalide.");
    $id=(int)$_POST['id'];
    $check=$pdo->prepare("SELECT COUNT(*) FROM offres_stage WHERE id=? AND entreprise_id=?");
    $check->execute([$id,$entreprise_id]);
    if (!(int)$check->fetchColumn()) throw new RuntimeException("Accès refusé.");
    $pdo->prepare("UPDATE offres_stage SET statut='fermée' WHERE id=?")->execute([$id]);
    $_SESSION['flash_success']="Offre fermée.";
  } else {
    throw new RuntimeException("Action inconnue.");
  }
} catch(Throwable $e) {
  $_SESSION['flash_error']="Échec : ".$e->getMessage();
}

header('Location: '.BASE_URL.'entreprise/mes_offres.php');
exit;
