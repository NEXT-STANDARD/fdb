<?php
session_start();
require 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$token = $_GET["token"] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_password = $_POST["password"] ?? '';
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // **トークンの有効性を確認**
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // **パスワード更新**
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user["id"]])) {
            header("Location: login.php?reset_success=1");
            exit;
        } else {
            die("エラー: パスワードの更新に失敗しました。");
        }
    } else {
        $error = "トークンが無効または期限切れです。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新しいパスワード</title>
    <link rel="stylesheet" href="assets/css/login.css?<?php echo time(); ?>"> <!-- 専用CSS -->
</head>
<body>
    <div class="login-container">
        <h2>新しいパスワードを設定</h2>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post">
            <input type="password" name="password" placeholder="新しいパスワード" required><br>
            <button type="submit">更新</button>
        </form>
        <p><a href="login.php">ログインはこちら</a></p>
    </div>
    <!-- 背景ロゴ -->
    <img src="assets/images/fdb-bg-logo.png" alt="Background Logo" class="background-logo">
</body>
</html>
