<?php
session_start();
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = "すべての項目を入力してください";
    } else {
        try {
            // 既存のユーザーを確認
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "このメールアドレスは既に登録されています";
            } else {
                // パスワードをハッシュ化
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password]);

                $_SESSION["loggedin"] = true;
                $_SESSION["username"] = $username;

                header("Location: index.php");
                exit;
            }
        } catch (PDOException $e) {
            $error = "エラー: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ユーザー登録</title>
    <link rel="stylesheet" href="assets/css/login.css?<?php echo time(); ?>"> <!-- ログイン専用CSS -->
</head>
<body>
    <div class="login-container">
        <h2>ユーザー登録</h2>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="post">
            <input type="text" name="username" placeholder="ユーザー名" required><br>
            <input type="email" name="email" placeholder="メールアドレス" required><br>
            <input type="password" name="password" placeholder="パスワード" required><br>
            <button type="submit">登録</button>
        </form>
        <p><a href="login.php">ログインはこちら</a></p>
    </div>
    <!-- 背景ロゴ -->
    <img src="assets/images/fdb-bg-logo.png" alt="Background Logo" class="background-logo">
</body>
</html>
