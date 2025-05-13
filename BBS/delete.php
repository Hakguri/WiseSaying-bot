<?php
require_once 'config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // 소프트 삭제 처리
    $stmt = $pdo->prepare("UPDATE posts SET is_deleted = 1 WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: index.php');
exit; 