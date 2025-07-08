<?php
$dbFile = 'database.db';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare('DELETE FROM Content WHERE content_id = ?');
    $stmt->execute([$id]);
}
header('Location: index.php');
exit; 