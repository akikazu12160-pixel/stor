<?php
session_start();
require "data.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST["username"];
    $password = $_POST["password"];

    $stmt = $mysqli->prepare(
        "SELECT user_id,username,password,role
         FROM users
         WHERE username=?"
    );

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    if (
        $user &&
        password_verify($password, $user["password"])
    ) {

        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] == "admin") {
            header("Location: admin/index.php");
        } else {
            header("Location: store.php");
        }

        exit;
    }

    $error = "ユーザー名またはパスワードが違います。";
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>ログイン</title>
</head>

<body>

<h1>STORE ログイン</h1>

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

<input type="submit" value="ログイン">

</form>

<p>
<a href="register.php">新規ユーザー登録</a>
</p>

<p>
<a href="store.php">商品一覧</a>
</p>

</body>
</html>