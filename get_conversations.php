<?php
session_start();
require 'config.php';
header("Content-Type: application/json");

// **セッション情報を取得**
$user_id = $_SESSION["loggedin"] ? $_SESSION["user_id"] : null;
$session_id = session_id();

// **デフォルトでは「今日」の会話を取得**
$filter = $_GET['filter'] ?? 'today';

try {
    // **メッセージが1件以上ある会話のみ取得**
    $query = "
    SELECT c.conversation_id, c.title, MAX(m.created_at) AS last_message_time
    FROM chat_conversations c
    JOIN chat_messages m ON c.conversation_id = m.conversation_id
    WHERE (c.user_id = :user_id OR c.session_id = :session_id) 
    AND c.is_deleted = 0 
    ";

    // **期間別フィルター（会話の更新日時で制御）**
    if ($filter === 'today') {
        $query .= " AND DATE(m.created_at) = CURDATE()";
    } elseif ($filter === 'yesterday') {
        $query .= " AND DATE(m.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    } elseif ($filter === 'last7days') {
        $query .= " AND DATE(m.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($filter === 'last30days') {
        $query .= " AND DATE(m.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    $query .= " GROUP BY c.conversation_id, c.title ORDER BY last_message_time DESC"; // **メッセージが新しい順**

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':user_id' => $user_id,
        ':session_id' => $session_id
    ]);

    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$conversations) {
        echo json_encode(["message" => "会話が見つかりません"]);
        exit;
    }

    echo json_encode($conversations);
} catch (PDOException $e) {
    echo json_encode(["error" => "データベースエラー: " . $e->getMessage()]);
}
?>
