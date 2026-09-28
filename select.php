<?php
session_start();
require "data.php";

$product_id = (int)($_GET["product_id"] ?? $_POST["product_id"] ?? 0);

if ($product_id <= 0) {
    die("商品が指定されていません。");
}

// 商品情報の取得
$stmt = $mysqli->prepare("SELECT * FROM products WHERE prodcut_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    die("商品が見つかりません。");
}

$error = null;

// ---------- カート追加処理（POST） ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit;
    }

    $quantity = (int)($_POST["quantity"] ?? 0);
    $user_id  = (int)$_SESSION["user_id"];

    if ($quantity <= 0 || $quantity > $product["stock"]) {
        $error = "数量が不正か、在庫数を超えています。";
    } else {
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
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($product["name"]) ?> - STORE</title>
</head>
<body>

<p><a href="store.php">&laquo; 一覧に戻る</a></p>

<h1><?= htmlspecialchars($product["name"]) ?></h1>

<?php if (!empty($product["image"])): ?>
<p><img src="<?= htmlspecialchars($product["image"]) ?>" alt="<?= htmlspecialchars($product["name"]) ?>" width="200"></p>
<?php endif; ?>

<table border="1">
<tr><th>説明</th><td><?= nl2br(htmlspecialchars($product["description"])) ?></td></tr>
<tr><th>価格</th><td><?= number_format($product["price"]) ?>円</td></tr>
<tr><th>在庫</th><td><?= $product["stock"] > 0 ? $product["stock"] . "個" : "売り切れ" ?></td></tr>
</table>

<?php if ($error): ?>
<p style="color: red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($product["stock"] > 0): ?>

    <?php if (isset($_SESSION["user_id"])): ?>
    <form method="post" action="select.php?product_id=<?= $product["prodcut_id"] ?>">
        <input type="hidden" name="product_id" value="<?= $product["prodcut_id"] ?>">
        数量:
        <select name="quantity">
            <?php for ($i = 1; $i <= min(10, $product["stock"]); $i++): ?>
            <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit">カートに入れる</button>
    </form>
    <?php else: ?>
    <p><a href="login.php">ログインして注文する</a></p>
    <?php endif; ?>

<?php else: ?>
<p>この商品は現在売り切れです。</p>
<?php endif; ?>

</body>
</html>