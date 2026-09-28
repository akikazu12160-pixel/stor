<?php
//セッションスタート
session_start();

//データベースの接続----------------------------------
$mysqli = new mysqli("localhost", "ei2435", "ei2435@alumni.hamako-ths.ed.jp", "ei2435");
if (mysqli_connect_errno()) {
    die("MySQL connection error: " . mysqli_connect_error());
}
$mysqli->query("SET NAMES sjis");

//---------------------------------------------------
// セッションからの商品情報取得（select.php側で以下のキー名に
// 揃えてもらう必要があります：product_id, name, price, stock）
//---------------------------------------------------
if (!isset($_SESSION["product_id"])) {
    die("商品情報がありません。store.phpからやり直してください。");
}

//---------------------------------------------------
// 個数のバリデーション
//---------------------------------------------------
$orders_raw = isset($_GET["orders"]) ? $_GET["orders"] : "1";
$error_msg = "";

if (!ctype_digit(strval($orders_raw)) || (int)$orders_raw <= 0) {
    $error_msg = "個数には1以上の数字を入力してください。";
    $orders = 1;
} else {
    $orders = (int)$orders_raw;
    if ($orders > $_SESSION["stock"]) {
        $error_msg = "在庫数（" . $_SESSION["stock"] . "個）を超える注文はできません。";
        $orders = $_SESSION["stock"];
    }
}

// 支払方法（DBには未保存。画面表示のみ）
$payment = isset($_GET["payment"]) ? $_GET["payment"] : "代金引換";

// カート内の合計金額
$total = $_SESSION["price"] * $orders;

//---------------------------------------------------
// action=confirmed のときだけ決済処理へ進む
//---------------------------------------------------
$action = isset($_GET["action"]) ? $_GET["action"] : "";

if ($action !== "confirmed" || $error_msg !== "") {

    print "<h2>ご注文内容の確認</h2>";

    if ($error_msg !== "") {
        print "<p style='color:red;'>" . $error_msg . "</p>";
    }

    print "<form method=GET action=order.php>";
    print "<table border=1>";
    print "<tr><td>商品名</td><td>" . htmlspecialchars($_SESSION["name"]) . "</td></tr>";
    print "<tr><td>単価</td><td>" . $_SESSION["price"] . "円</td></tr>";

    //個数の変更
    print "<tr><td>個数</td><td>";
    print "<input type=text size=3 name=orders value='" . htmlspecialchars($orders_raw) . "'>";
    print "&nbsp;<input type=submit name=action value='recalc' formnovalidate>再計算</input>";
    print "</td></tr>";

    //支払方法の変更
    print "<tr><td>支払方法</td><td>";
    print "<input type=radio name=payment value='代金引換'" .
        ($payment == "代金引換" ? " checked" : "") . ">代金引換　";
    print "<input type=radio name=payment value='クレジットカード'" .
        ($payment == "クレジットカード" ? " checked" : "") . ">クレジットカード";
    print "</td></tr>";

    //合計金額
    print "<tr><td>合計金額</td><td><b>" . $total . "円</b></td></tr>";
    print "</table>";

    //ユーザー認証情報はhiddenで保持
    print "<input type=hidden name=user_id value='" .
        htmlspecialchars($_GET["user_id"]) . "'>";
    print "<input type=hidden name=password value='" .
        htmlspecialchars($_GET["password"]) . "'>";

    if ($error_msg === "") {
        print "<input type=submit name=action value='confirmed'>この内容で注文確定</input>";
    }
    print "</form>";

    print "<p><a href=store.php>BACK（ホームに戻る）</a></p>";

    $mysqli->close();
    exit;
}

//---------------------------------------------------
// action=confirmed かつエラーなし：決済処理を実行
//---------------------------------------------------
$message = "";

//ユーザーアカウントの取得（usersテーブル）
$sql = "select user_id,password from users where username='" .
    htmlspecialchars($_GET["user_id"]) . "'";
if (!($result = $mysqli->query($sql))) {
    die("SQL error: " . $mysqli->error);
}
$row = $result->fetch_array(MYSQLI_ASSOC);
$result->close();

//ユーザーアカウントの認証
if ($row && $row["password"] == htmlspecialchars($_GET["password"])) {
    //認証OK
    $message = "THANK YOU.";

    //注文ヘッダー：ordersテーブルへの書き込み
    $sql = "INSERT INTO orders (user_id,total_price,ordered_at) VALUES (" .
        $row["user_id"] . "," . $total . ",NOW())";
    if (!($mysqli->query($sql))) {
        die("SQL error: " . $mysqli->error);
    }
    $order_id = $mysqli->insert_id; //直前に発行されたorder_idを取得

    //注文明細：order_detailsテーブルへの書き込み
    $sql = "INSERT INTO order_details (order_id,product_id,product_name,price,quantity,subtotal) VALUES (" .
        $order_id . "," . $_SESSION["product_id"] . ",'" .
        $mysqli->real_escape_string($_SESSION["name"]) . "'," .
        $_SESSION["price"] . "," . $orders . "," . $total . ")";
    if (!($mysqli->query($sql))) {
        die("SQL error: " . $mysqli->error);
    }

    //商品台帳productsへの書き込み
    $num = $_SESSION["stock"] - $orders;
    $sql = "UPDATE products SET stock=" . $num . " WHERE product_id=" . $_SESSION["product_id"];
    if (!($mysqli->query($sql))) {
        die("SQL error: " . $mysqli->error);
    }

    //注文完了後、カート情報をクリア
    unset($_SESSION["product_id"], $_SESSION["name"], $_SESSION["price"], $_SESSION["stock"]);
} else {
    //認証NG
    $message = "ERROR.<br>One more order!!";
}

$mysqli->close();
?>
<table>
    <tr><td><?php echo $message; ?></td></tr>
    <?php if ($message == "THANK YOU."): ?>
    <tr><td>お支払方法：<?php echo htmlspecialchars($payment); ?>（※現状DB未保存）</td></tr>
    <tr><td>ご購入金額：<?php echo $total; ?>円</td></tr>
    <?php endif; ?>
    <tr><td><a href="store.php">BACK（ホームに戻る）</a></td></tr>
</table>
