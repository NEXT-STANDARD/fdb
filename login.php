<?php
session_start();
require 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]) ?? '';
    $password = $_POST["password"] ?? '';

    // **ユーザーの認証**
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user["password"])) {
        $error = "メールアドレスまたはパスワードが間違っています";
    } else {
        // **ログイン成功**
        $_SESSION["loggedin"] = true;
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];

        // **セッションIDを変更**
        $old_session_id = session_id();
        session_regenerate_id(true);
        $new_session_id = session_id();

        // **ログイン成功後にゲストの会話を user_id に紐づける**
        if (!empty($_SESSION["guest_session_id"])) {
            $updateStmt = $pdo->prepare("UPDATE chat_conversations SET user_id = ?, session_id = NULL WHERE session_id = ?");
            $updateStmt->execute([$user["id"], $_SESSION["guest_session_id"]]);
            unset($_SESSION["guest_session_id"]); // **不要になったゲストの session_id を削除**
        }

        // **リダイレクト**
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン</title>
    <link rel="stylesheet" href="assets/css/login.css?<?php echo time(); ?>"> <!-- 専用CSS -->
</head>
<body>
    <div class="login-container">
        <h2>ログイン</h2>

        <!-- **パスワードリセット成功時の通知** -->
        <?php if (isset($_GET["reset_success"])): ?>
            <p class='success'>パスワードがリセットされました。<br>ログインしてください。</p>
        <?php endif; ?>

        <!-- **エラーメッセージ** -->
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

        <form method="post">
            <input type="email" name="email" placeholder="メールアドレス" required><br>
            <input type="password" name="password" placeholder="パスワード" required><br>
            <button type="submit">ログイン</button>
        </form>

        <p><a href="index.php">ゲストとして続ける</a></p>
        <p><a href="register.php">新規会員登録</a></p>
        <p><a href="password_reset.php">パスワードを忘れた場合</a></p>
    </div>

    <!-- 背景ロゴ -->
    <img src="assets/images/fdb-bg-logo.png" alt="Background Logo" class="background-logo">
</body>
</html>
