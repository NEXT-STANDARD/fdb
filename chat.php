<?php
session_start();
require 'config.php';
header("Content-Type: application/json");

$user_id = $_SESSION["loggedin"] ? $_SESSION["user_id"] : null;
$session_id = $_SESSION["loggedin"] ? null : session_id();

// `conversation_id` がない場合、新規作成
if (!isset($_SESSION["conversation_id"])) {
    $conversation_id = bin2hex(random_bytes(16)); // 32文字のUUID
    $_SESSION["conversation_id"] = $conversation_id;

    // 会話をDBに登録
    $stmt = $pdo->prepare("INSERT INTO chat_conversations (conversation_id, user_id, session_id, title) VALUES (?, ?, ?, ?)");
    $stmt->execute([$conversation_id, $user_id, $session_id, '新しい会話']);
} else {
    $conversation_id = $_SESSION["conversation_id"];
}

// ユーザーの入力を取得
$input = json_decode(file_get_contents("php://input"), true);
if (!isset($input["message"]) || empty(trim($input["message"]))) {
    echo json_encode(["error" => "メッセージが空です。"]);
    exit;
}

$userMessage = htmlspecialchars(trim($input["message"]), ENT_QUOTES, 'UTF-8');

// **ユーザーのメッセージを保存**
try {
    $stmt = $pdo->prepare("INSERT INTO chat_messages (conversation_id, message, sender) VALUES (?, ?, ?)");
    $stmt->execute([$conversation_id, $userMessage, 'user']);

    // **会話の最終更新時刻を更新**
    $updateStmt = $pdo->prepare("UPDATE chat_conversations SET updated_at = NOW() WHERE conversation_id = ?");
    $updateStmt->execute([$conversation_id]);
} catch (PDOException $e) {
    echo json_encode(["error" => "データベースエラー: " . $e->getMessage()]);
    exit;
}


// ボットの応答を生成
function generateBotResponse($message) {
    $responses = [
        "こんにちは！",
        "元気ですか？",
        "それは面白いですね！",
        "もっと詳しく教えてください！",
        "なるほど、そうなんですね！"
    ];
    return $responses[array_rand($responses)];
}

$botReply = generateBotResponse($userMessage);

// ボットのメッセージを保存
try {
    $stmt = $pdo->prepare("INSERT INTO chat_messages (conversation_id, message, sender) VALUES (?, ?, ?)");
    $stmt->execute([$conversation_id, $botReply, 'bot']);
} catch (PDOException $e) {
    echo json_encode(["error" => "データベースエラー: " . $e->getMessage()]);
    exit;
}

// JSONで返す
echo json_encode(["reply" => $botReply]);
?>
