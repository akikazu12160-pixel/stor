<?php
session_start();
require "data.php";

// ---------- カート追加処理（POST） ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }

    $product_id = (int)($_POST["product_id"] ?? 0);
    $quantity   = (int)($_POST["quantity"] ?? 0);
    $user_id    = (int)$_SESSION["user_id"];

    if ($product_id <= 0 || $quantity <= 0) {
        $_SESSION["flash_message"] = "不正な注文内容です。";
        header("Location: store.php");
        exit;
    }

    $stmt = $mysqli->prepare("SELECT name, stock FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        $_SESSION["flash_message"] = "商品が見つかりません。";
        header("Location: store.php");
        exit;
    }

    if ($quantity > $product["stock"]) {
        $_SESSION["flash_message"] = "在庫が不足しています。";
        header("Location: store.php");
        exit;
    }

    $stmt = $mysqli->prepare("SELECT cart_id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $new_qty = $existing["quantity"] + $quantity;
        $stmt = $mysqli->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ?");
        $stmt->bind_param("ii", $new_qty, $existing["cart_id"]);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $mysqli->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        $stmt->execute();
        $stmt->close();
    }

    $_SESSION["flash_message"] = htmlspecialchars($product["name"]) . "をカートに追加しました。";
    header("Location: store.php");
    exit;
}

// ---------- 一覧表示処理（GET） ----------
$result = $mysqli->query(
    "SELECT * FROM products ORDER BY product_id"
);

$message = $_SESSION["flash_message"] ?? null;
unset($_SESSION["flash_message"]);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>STORE</title>
</head>

<body>

<h1>STORE</h1>

<?php if (isset($_SESSION["user_id"])): ?>

<p>
<?= htmlspecialchars($_SESSION["username"]) ?> さん

<a href="cart.php">カート</a>
<a href="history.php">購入履歴</a>
<a href="logout.php">ログアウト</a>
</p>

<?php else: ?>

<p>
<a href="login.php">ログイン</a>
<a href="register.php">新規登録</a>
</p>

<?php endif; ?>

<?php if ($message): ?>
<p style="color: green;"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<table border="1">

<tr>
    <th>商品名</th>
    <th>説明</th>
    <th>価格</th>
    <th>在庫</th>
    <th>注文</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>

<tr>

<td>
<a href="select.php?product_id=<?= $row["product_id"] ?>">
<?= htmlspecialchars($row["name"]) ?>
</a>
</td>

<td>
<?= htmlspecialchars($row["description"]) ?>
</td>

<td>
<?= number_format($row["price"]) ?>円
</td>

<td>

<?php if ($row["stock"] > 0): ?>

<?= $row["stock"] ?>個

<?php else: ?>

売り切れ

<?php endif; ?>

</td>

<td>
<?php if ($row["stock"] > 0): ?>

    <?php if (isset($_SESSION["user_id"])): ?>
    <form action="store.php" method="post" style="display:inline;">
        <input type="hidden" name="product_id" value="<?= $row["product_id"] ?>">
        <select name="quantity">
            <?php for ($i = 1; $i <= min(10, $row["stock"]); $i++): ?>
            <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit">カートに入れる</button>
    </form>
    <?php else: ?>
    <a href="login.php">ログインして注文</a>
    <?php endif; ?>

<?php else: ?>
—
<?php endif; ?>
</td>

</tr>

<?php endwhile; ?>

</table>

</body>
</html>