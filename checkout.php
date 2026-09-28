<?php
session_start();
require "data.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$stmt = $mysqli->prepare(
    "SELECT c.product_id,c.quantity,
            p.name,p.price,p.stock
     FROM cart_items c
     JOIN products p
     ON c.product_id=p.product_id
     WHERE c.user_id=?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$items = [];
$total = 0;

while ($row = $result->fetch_assoc()) {

    if ($row["quantity"] > $row["stock"]) {
        die("「" . htmlspecialchars($row["name"]) .
            "」の在庫が不足しています。");
    }

    $row["subtotal"] =
        $row["price"] * $row["quantity"];

    $total += $row["subtotal"];

    $items[] = $row;
}

if (!$items) {
    die("カートが空です。");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    try {

        $mysqli->begin_transaction();

        $stmt = $mysqli->prepare(
            "INSERT INTO orders(user_id,total_price)
             VALUES(?,?)"
        );

        $stmt->bind_param(
            "ii",
            $user_id,
            $total
        );

        $stmt->execute();

        $order_id = $mysqli->insert_id;

        foreach ($items as $item) {

            $stmt = $mysqli->prepare(
                "INSERT INTO order_details
                (order_id,product_id,product_name,
                 price,quantity,subtotal)
                VALUES(?,?,?,?,?,?)"
            );

            $stmt->bind_param(
                "iisiii",
                $order_id,
                $item["product_id"],
                $item["name"],
                $item["price"],
                $item["quantity"],
                $item["subtotal"]
            );

            $stmt->execute();

            $stmt = $mysqli->prepare(
                "UPDATE products
                 SET stock=stock-?
                 WHERE product_id=?
                 AND stock>=?"
            );

            $stmt->bind_param(
                "iii",
                $item["quantity"],
                $item["product_id"],
                $item["quantity"]
            );

            $stmt->execute();

            if ($stmt->affected_rows != 1) {
                throw new Exception();
            }
        }

        $stmt = $mysqli->prepare(
            "DELETE FROM cart_items
             WHERE user_id=?"
        );

        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $mysqli->commit();

        header("Location: history.php");
        exit;

    } catch (Exception $e) {

        $mysqli->rollback();

        die("購入処理に失敗しました。");
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>購入確認</title>
</head>

<body>

<h1>購入確認</h1>

<?php foreach ($items as $item): ?>

<p>
<?= htmlspecialchars($item["name"]) ?>

<?= $item["quantity"] ?>個

<?= number_format($item["subtotal"]) ?>円
</p>

<?php endforeach; ?>

<hr>

<h2>
合計：<?= number_format($total) ?>円
</h2>

<form method="post">

<input type="submit"
       value="購入を確定">

</form>

<p>
<a href="cart.php">カートに戻る</a>
</p>

</body>
</html>