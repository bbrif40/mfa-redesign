<?php require 'lib.php';
header('Content-Type: application/json'); header('Cache-Control: no-store');
$r = request($_GET['t'] ?? '');
echo json_encode(['status' => $r && owns($r) ? status($r) : 'invalid']);
