<?php
session_start();
require "data.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$stmt = $mysqli->prepare(
    "SELECT * FROM orders
     WHERE user_id=?
     ORDER BY ordered_at DESC"
);

$stmt->bind_param(
    "i",
    $_SESSION["user_id"]
);

$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>購入履歴</title>
</head>

<body>

<h1>購入履歴</h1>

<?php while ($order = $result->fetch_assoc()): ?>

<h2>
注文番号：<?= $order["order_id"] ?>
</h2>

<p>
購入日時：<?= $order["ordered_at"] ?>
</p>

<p>
合計：<?= number_format($order["total_price"]) ?>円
</p>

<?php

$stmt2 = $mysqli->prepare(
    "SELECT * FROM order_details
     WHERE order_id=?"
);

$stmt2->bind_param(
    "i",
    $order["order_id"]
);

$stmt2->execute();

$details = $stmt2->get_result();

?>

<ul>

<?php while ($detail = $details->fetch_assoc()): ?>

<li>
<?= htmlspecialchars($detail["product_name"]) ?>

：
<?= $detail["quantity"] ?>個

×
<?= number_format($detail["price"]) ?>円
</li>

<?php endwhile; ?>

</ul>

<hr>

<?php endwhile; ?>

<p>
<a href="store.php">商品一覧</a>
</p>

</body>
</html>