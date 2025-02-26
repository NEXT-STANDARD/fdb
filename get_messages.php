<?php
require 'config.php';
header("Content-Type: application/json");

$conversation_id = $_GET["conversation_id"] ?? null;

if (!$conversation_id) {
    echo json_encode(["error" => "会話IDがありません。"]);
    exit;
}

// **選択された会話のメッセージ履歴を取得**
$stmt = $pdo->prepare("SELECT message, sender FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC");
$stmt->execute([$conversation_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);
?>
