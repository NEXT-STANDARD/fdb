<?php
// **セッション開始**
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'delicious_fdb';
$username = 'delicious_fdb'; // DBユーザー名
$password = 'L9LTwSsA'; // DBパスワード（XAMPP/MAMPのデフォルトは空）

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("データベース接続に失敗しました: " . $e->getMessage());
}

// Grok APIキー
define('GROK_API_KEY', 'xai-jfVb4MmSDzyeCCcueg6XY44k6bvZUsni3zKibjhWySrE93ocUP2bAxIB1ZjJdh8nlv5qq1gHgUc1w4AF');
?>
