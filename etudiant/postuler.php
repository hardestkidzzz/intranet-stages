<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php'; requireRole(['etudiant']);
require_once __DIR__ . '/../db.php';

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.BASE_URL.'etudiant/offres.php'); exit; }
if (empty($_POST['offre_id']) || !ctype_digit($_POST['offre_id'])) { header('Location: '.BASE_URL.'etudiant/offres.php'); exit; }
$offre_id = (int)$_POST['offre_id'];

$message = trim($_POST['message'] ?? '');

// vérifie l’offre existe et est publiée
$st=$pdo->prepare("SELECT o.*, e.nom entreprise_nom FROM offres_stage o JOIN entreprises e ON e.id=o.entreprise_id WHERE o.id=? AND o.statut='publiée'");
$st->execute([$offre_id]);
$offre=$st->fetch(PDO::FETCH_ASSOC);
if (!$offre) { $_SESSION['flash_error']="Offre introuvable ou non publiée."; header('Location: '.BASE_URL.'etudiant/offres.php'); exit; }

// empêche la double candidature
$st=$pdo->prepare("SELECT COUNT(*) FROM candidatures WHERE offre_id=? AND etudiant_user_id=?");
$st->execute([$offre_id,$user_id]);
if ((int)$st->fetchColumn() > 0) {
  $_SESSION['flash_error']="Tu as déjà candidaté à cette offre.";
  header('Location: '.BASE_URL.'etudiant/offres.php'); exit;
}

// validations fichiers
function save_pdf($key, $prefix){
  if (empty($_FILES[$key]['name'])) return null;
  $f=$_FILES[$key];
  if ($f['error']!==UPLOAD_ERR_OK) throw new RuntimeException("Erreur upload ($key): code ".$f['error']);
  $ext=strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  if (!in_array($ext,['pdf'])) throw new RuntimeException("Le fichier $key doit être un PDF.");
  if ($f['size'] > 5*1024*1024) throw new RuntimeException("Le fichier $key dépasse 5 Mo.");
  @mkdir(__DIR__.'/../uploads',0777,true);
  $name=$prefix.'_'.time().'_'.bin2hex(random_bytes(4)).'.pdf';
  $dest=__DIR__.'/../uploads/'.$name;
  if (!move_uploaded_file($f['tmp_name'],$dest)) throw new RuntimeException("Impossible d'enregistrer $key.");
  return 'uploads/'.$name;
}

try {
  $cv_path = save_pdf('cv', 'cv_'.$user_id);
  if (!$cv_path) { throw new RuntimeException("CV obligatoire."); }
  $lm_path = null;
  if (!empty($_FILES['lm']['name'])) { $lm_path = save_pdf('lm', 'lm_'.$user_id); }

  $ins=$pdo->prepare("INSERT INTO candidatures (offre_id, etudiant_user_id, cv_path, lm_path, message) VALUES (?,?,?,?,?)");
  $ins->execute([$offre_id,$user_id,$cv_path,$lm_path,$message?:null]);

  $_SESSION['flash_success']="Candidature envoyée à « ".$offre['entreprise_nom']." » 👍";
  header('Location: '.BASE_URL.'etudiant/candidatures.php'); exit;

} catch (Throwable $e) {
  $_SESSION['flash_error']="Échec : ".$e->getMessage();
  header('Location: '.BASE_URL.'etudiant/offres.php'); exit;
}
