<?php
session_start();
require 'config.php';
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($input["conversation_id"]) || !isset($input["title"])) {
        echo json_encode(["error" => "無効なリクエスト"]);
        exit;
    }

    $conversation_id = $input["conversation_id"];
    $new_title = trim($input["title"]);

    if (empty($new_title)) {
        echo json_encode(["error" => "タイトルは空にできません"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE chat_conversations SET title = ? WHERE conversation_id = ?");
        $stmt->execute([$new_title, $conversation_id]);

        echo json_encode(["success" => true, "title" => $new_title]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "データベースエラー: " . $e->getMessage()]);
    }
}
?>
