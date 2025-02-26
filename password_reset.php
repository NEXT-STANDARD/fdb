<?php
session_start();
require 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);

    // **ユーザーが存在するか確認**
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // **リセット用トークンを生成**
        $token = bin2hex(random_bytes(32));

        // **リセット用トークンをデータベースに保存**
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
        if (!$stmt->execute([$token, $user["id"]])) {
            die("データベースエラー: トークンの保存に失敗しました。");
        }

        // **リセット用URLの作成**
        $reset_link = "https://kirishima-phoenix.com/code/password_reset_confirm.php?token=$token";

        // **仮のメール送信**
        if (mail($email, "パスワードリセット", "以下のリンクからパスワードをリセットしてください:\n$reset_link")) {
            $message = "リセットリンクを送信しました。";
        } else {
            $error = "メール送信に失敗しました。";
        }
    } else {
        $error = "このメールアドレスは登録されていません。";
    }
}
?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>パスワードリセット</title>
    <link rel="stylesheet" href="assets/css/login.css?<?php echo time(); ?>"> <!-- 専用CSS -->
</head>
<body>
    <div class="login-container">
        <h2>パスワードリセット</h2>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <?php if (!empty($message)) echo "<p class='success'>$message</p>"; ?>
        <form method="post">
            <input type="email" name="email" placeholder="メールアドレス" required><br>
            <button type="submit">リセットリンクを送信</button>
        </form>
        <p><a href="login.php">ログインはこちら</a></p>
    </div>
    <!-- 背景ロゴ -->
    <img src="assets/images/fdb-bg-logo.png" alt="Background Logo" class="background-logo">
</body>
</html>
