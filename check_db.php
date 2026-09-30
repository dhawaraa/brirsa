<?php
require_once 'www/includes/config.php';
$pdo = getDB();
$stmt = $pdo->query("DESCRIBE requests");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode("\n", $columns);
