<?php
session_start();
require "data.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if ($username == "" || $password == "") {
        $error = "すべて入力してください。";
    } else {

        $stmt = $mysqli->prepare(
            "SELECT user_id FROM users WHERE username=?"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {

            $error = "そのユーザー名はすでに使用されています。";

        } else {

            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $mysqli->prepare(
                "INSERT INTO users(username,password,role)
                 VALUES(?,?, 'user')"
            );

            $stmt->bind_param(
                "ss",
                $username,
                $hash
            );

            $stmt->execute();

            header("Location: login.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>新規登録</title>
</head>

<body>

<h1>新規ユーザー登録</h1>

<?php if (isset($error)): ?>
<p><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">

ユーザー名<br>
<input type="text" name="username" required>

<br><br>

パスワード<br>
<input type="password" name="password" required>

<br><br>

<input type="submit" value="登録">

</form>

<p>
<a href="login.php">ログイン</a>
</p>

</body>
</html>