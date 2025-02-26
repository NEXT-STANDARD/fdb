<?php
session_start();
require 'config.php';
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
$conversation_id = $input["conversation_id"] ?? null;

if (!$conversation_id) {
    echo json_encode(["error" => "会話IDが指定されていません。"]);
    exit;
}

try {
    // **会話を論理削除**
    $stmt = $pdo->prepare("UPDATE chat_conversations SET is_deleted = 1 WHERE conversation_id = ?");
    $stmt->execute([$conversation_id]);

    // **削除後の最初の会話を取得**
    $user_id = $_SESSION["loggedin"] ? $_SESSION["user_id"] : null;
    $session_id = session_id();

    $stmt = $pdo->prepare("
        SELECT conversation_id FROM chat_conversations 
        WHERE (user_id = ? OR session_id = ?) AND is_deleted = 0 
        ORDER BY updated_at DESC LIMIT 1
    ");
    $stmt->execute([$user_id, $session_id]);
    $nextConversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($nextConversation) {
        echo json_encode(["message" => "会話が削除されました", "next_conversation_id" => $nextConversation['conversation_id']]);
    } else {
        echo json_encode(["message" => "会話が削除されました", "next_conversation_id" => null]);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "データベースエラー: " . $e->getMessage()]);
}
?>
