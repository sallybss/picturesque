<?php
require_once __DIR__.'/../includes/init.php';
$me = Auth::requireUserOrRedirect('../home_guest.php');
if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }

$profiles = new ProfileRepository();
$meRow = $profiles->getHeader($me);
$isAdmin = strtolower(trim($meRow['role'] ?? '')) === 'admin';
if (!$isAdmin) { http_response_code(403); exit('Forbidden'); }

$week = DateTime::createFromFormat('Y-m-d', $_POST['week'] ?? '') ?: new DateTime('monday next week');
$ids = array_map('intval', $_POST['picture_id'] ?? []);
$ids = array_values(array_unique($ids)); // de-dup
if (count($ids) > 10) $ids = array_slice($ids, 0, 10);

$repo = new FeaturedRepository();
$repo->replaceWeekSelection($ids, (int)$me, $week);

set_flash('ok', 'Hot picks saved for week starting '.$week->format('Y-m-d'));
header('Location: ../admin_hot.php?week='.$week->format('Y-m-d'));
