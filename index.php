<?php
session_start();
require 'config.php';

// `conversation_id` を URL から取得（リロード時も維持）
$conversation_id = $_GET['conversation_id'] ?? null;
$user_id = $_SESSION["loggedin"] ? $_SESSION["user_id"] : null;

// **ゲスト時のみ `session_id` を使用（ログイン後も一時的に保持）**
if (!$_SESSION["loggedin"]) {
    $_SESSION["guest_session_id"] = $_SESSION["guest_session_id"] ?? session_id();
}
$session_id = $_SESSION["loggedin"] ? null : $_SESSION["guest_session_id"];

// **新しい会話を開始する場合（URLに `conversation_id` がない）**
if (!$conversation_id) {
    $conversation_id = bin2hex(random_bytes(16)); // 新しい会話IDを生成
    $_SESSION["conversation_id"] = $conversation_id;

    // **新しい会話をDBに保存**
    $stmt = $pdo->prepare("INSERT INTO chat_conversations (conversation_id, user_id, session_id, title) VALUES (?, ?, ?, ?)");
    $stmt->execute([$conversation_id, $user_id, $session_id, '新しい会話']);

    // **URL に `conversation_id` を追加し、リダイレクト**
    header("Location: index.php?conversation_id=" . $conversation_id);
    exit;
}

// **🔹 会話リストを取得**
$stmt = $pdo->prepare("
    SELECT conversation_id, title 
    FROM chat_conversations 
    WHERE (user_id = ? OR (session_id = ? AND user_id IS NULL)) 
    AND is_deleted = 0 
    ORDER BY updated_at DESC
");
$stmt->execute([$user_id, $_SESSION["guest_session_id"] ?? null]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ダッシュボード | AI紹介文ジェネレーター</title>
    <link rel="stylesheet" href="assets/css/styles.css?<?php echo time(); ?>">
</head>
<body>
    <div class="chat-container">
        <!-- サイドバー -->
        <div class="sidebar">
            <button class="toggle-btn" onclick="toggleSidebar()">☰</button>

            <!-- ログイン情報 -->
            <div class="user-info">
                <?php if (!empty($_SESSION["loggedin"])): ?>
                    <p>ようこそ、<strong><?= htmlspecialchars($_SESSION["username"], ENT_QUOTES, 'UTF-8') ?></strong> さん</p>
                    <a href="logout.php" class="logout-btn">ログアウト</a>
                <?php else: ?>
                    <p><a href="login.php" class="login-btn">ログイン</a></p>
                    <p><a href="register.php" class="register-btn">新規会員登録</a></p>
                <?php endif; ?>
            </div>

            <!-- **ゲストの場合は会話履歴を非表示にし、「新規会員登録」ボタンを表示** -->
            <?php if (empty($_SESSION["loggedin"])): ?>
                <div class="guest-message">
                    <p>ゲストの会話履歴は保存されません。</p>
                </div>
            <?php else: ?>
                <h3>会話一覧</h3>
                <button onclick="startNewConversation()">＋ 新しい会話</button>

                <!-- フィルターボタン -->
                <div class="conversation-filters">
                    <button class="filter-btn" data-filter="today">今日</button>
                    <button class="filter-btn" data-filter="yesterday">昨日</button>
                    <button class="filter-btn" data-filter="last7days">過去7日間</button>
                </div>

                <!-- 会話リスト -->
                <ul id="conversationList">
                    <?php foreach ($conversations as $conversation): ?>
                        <li class="conversation-item">
                            <!-- タイトル表示部分 -->
                            <span class="conversation-title-text" onclick="openConversation('<?= $conversation['conversation_id'] ?>')">
                                <?= htmlspecialchars($conversation['title'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <input type="text" class="conversation-title-input" 
                                value="<?= htmlspecialchars($conversation['title'], ENT_QUOTES, 'UTF-8') ?>" 
                                onblur="updateTitle('<?= $conversation['conversation_id'] ?>', this)" 
                                onkeypress="handleTitleInput(event, '<?= $conversation['conversation_id'] ?>', this)">
                            
                            <!-- 3点リーダーメニュー -->
                            <div class="menu-container">
                                <button class="menu-btn">⋮</button>
                                <div class="menu-content">
                                    <button onclick="editTitle(this)">名前の変更</button>
                                    <button onclick="showDeleteModal('<?= $conversation['conversation_id'] ?>', '<?= htmlspecialchars($conversation['title'], ENT_QUOTES, 'UTF-8') ?>')">削除</button>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

            <?php endif; ?>
        </div>

        <div class="main-content">
            <div class="chat-header">
                <div class="logo">
                    <a href="./"><img src="assets/images/fdb-logo.png" alt="ChatBot"></a>
                </div>
            </div>

            <div class="chat-history" id="chatHistory"></div>

            <div class="input-area initial">
                <input type="text" id="userInput" placeholder="ここにメッセージを入力（Enterで送信）">
            </div>
        </div>
    </div>
    <!-- 背景ロゴ -->
    <img src="assets/images/fdb-bg-logo.png" alt="Background Logo" class="background-logo">
    <script>
        function startNewConversation() {
            window.location.href = "index.php"; // **新しい会話を作成**
        }
    </script>
    <script src="assets/js/script.js"></script>
    <!-- 削除確認モーダル -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <p id="deleteMessage"></p>
            <button id="confirmDeleteBtn">削除する</button>
            <button id="cancelDeleteBtn">キャンセル</button>
        </div>
    </div>
</body>
</html>
