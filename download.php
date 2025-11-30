<?php
// download.php (à la racine intranet-stages)
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php'; requireLogin();
require_once __DIR__ . '/db.php';

$type = $_GET['type'] ?? '';
$id   = ctype_digit($_GET['id'] ?? '') ? (int)$_GET['id'] : 0;
if (!in_array($type,['cv','lm']) || !$id) { http_response_code(400); exit('Requête invalide'); }

$st=$pdo->prepare("
  SELECT c.*, o.entreprise_id
  FROM candidatures c
  JOIN offres_stage o ON o.id=c.offre_id
  WHERE c.id=?
");
$st->execute([$id]);
$c = $st->fetch(PDO::FETCH_ASSOC);
if (!$c) { http_response_code(404); exit('Fichier introuvable'); }

$role = $_SESSION['role'] ?? '';
$user_id = (int)$_SESSION['user_id'];

// droits
$allowed = false;
if ($role==='admin') $allowed = true;
if ($role==='etudiant' && (int)$c['etudiant_user_id']===$user_id) $allowed = true;
if ($role==='entreprise') {
  $te=$pdo->prepare("SELECT entreprise_id FROM tuteurs_entreprise WHERE user_id=?");
  $te->execute([$user_id]);
  $eid = (int)($te->fetchColumn() ?: 0);
  if ($eid && $eid===(int)$c['entreprise_id']) $allowed = true;
}
if (!$allowed) { http_response_code(403); exit('Accès refusé'); }

$col = $type==='cv' ? 'cv_path' : 'lm_path';
$rel = $c[$col] ?? null;
if (!$rel) { http_response_code(404); exit('Fichier non disponible'); }

$path = __DIR__ . '/'. $rel;
if (!is_file($path)) { http_response_code(404); exit('Fichier manquant'); }

$mime = ($type==='cv' || $type==='lm') ? 'application/pdf' : 'application/octet-stream';
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($path));
header('Content-Disposition: inline; filename="'.basename($path).'"');
readfile($path);
