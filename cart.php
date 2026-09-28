<?php
session_start();
require "data.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

if (isset($_POST["update"])) {

    $cart_id = (int)$_POST["cart_id"];
    $quantity = (int)$_POST["quantity"];

    $stmt = $mysqli->prepare(
        "UPDATE cart_items c
         JOIN products p ON c.product_id=p.product_id
         SET c.quantity=?
         WHERE c.cart_id=?
         AND c.user_id=?
         AND ? <= p.stock"
    );

    $stmt->bind_param(
        "iiii",
        $quantity,
        $cart_id,
        $user_id,
        $quantity
    );

    $stmt->execute();
}

if (isset($_POST["delete"])) {

    $cart_id = (int)$_POST["cart_id"];

    $stmt = $mysqli->prepare(
        "DELETE FROM cart_items
         WHERE cart_id=? AND user_id=?"
    );

    $stmt->bind_param(
        "ii",
        $cart_id,
        $user_id
    );

    $stmt->execute();
}

$stmt = $mysqli->prepare(
    "SELECT c.cart_id,c.quantity,
            p.product_id,p.name,p.price,p.stock
     FROM cart_items c
     JOIN products p
     ON c.product_id=p.product_id
     WHERE c.user_id=?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$total = 0;
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>カート</title>
</head>

<body>

<h1>カート</h1>

<?php while ($row = $result->fetch_assoc()): ?>

<?php
$subtotal =
    $row["price"] * $row["quantity"];

$total += $subtotal;
?>

<div>

<h2>
<?= htmlspecialchars($row["name"]) ?>
</h2>

<p>
<?= number_format($row["price"]) ?>円 ×
<?= $row["quantity"] ?>個
=
<?= number_format($subtotal) ?>円
</p>

<form method="post">

<input type="hidden"
       name="cart_id"
       value="<?= $row["cart_id"] ?>">

<input type="number"
       name="quantity"
       min="1"
       max="<?= $row["stock"] ?>"
       value="<?= $row["quantity"] ?>">

<input type="submit"
       name="update"
       value="数量変更">

<input type="submit"
       name="delete"
       value="削除">

</form>

</div>

<hr>

<?php endwhile; ?>

<h2>
合計：<?= number_format($total) ?>円
</h2>

<?php if ($total > 0): ?>

<a href="checkout.php">購入確認</a>

<?php else: ?>

<p>カートは空です。</p>

<?php endif; ?>

<p>
<a href="store.php">商品一覧</a>
</p>

</body>
</html>