<?php
// セッションスタート
session_start();
// データベース接続--------------------------------------------------
$mysqli = new mysqli("localhost", "ei2435", "ei2435@alumni.hamako-ths.ed.jp", "ei2435");
if (mysqli_connect_errno()) {
    die("MySQL connection error: " . mysqli_connect_error());
}

// URLから商品IDを受け取る
$product_id = (int)($_GET["product_id"] ?? 0);

// 商品IDが正しく指定されているか確認する
if($product_id <= 0) {
    die("商品が指定されていません。");
}

// SQLの実行--------------------------------------------------------
$sql = "SELECT * FROM products WHERE product_id=" . $product_id;
if (!($result = $mysqli->query($sql))) {
    die("SQL error: " . $mysqli->error);
}

// 実行結果を取り出す
$product = $result->fetch_array(MYSQLI_ASSOC);

// 商品が見つからなかった場合
if(!$product) {
    die("商品が見つかりません。");
}
?>

<!--商品情報の表示-->

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>商品詳細</title>
</head>

<body>
<h2>商品詳細</h2>

<!--商品名-->
<div class="product-name">
    <h3><?= htmlspecialchars($product["name"]) ?></h3>
</div>

<!--商品画像-->
<div class="product-image">
    <!--あとから商品画像を入れる-->
</div>

<!--商品説明-->
<div class="product-description">
    <h3>説明</h3>
    <p><?= htmlspecialchars($product["description"]) ?></p>
</div>

<!--商品価格-->
<div class="product-price">
    <h3>価格</h3>
    <p><?= number_format($product["price"]) ?>円</p>
</div>

<!--商品在庫-->
<div class="product-stock">
    <h3>在庫</h3>
    <p><?= $product["stock"] ?>個</p>
</div>

<!--商品一覧に戻る-->
<div class="product-buttons">
    <form action="store.php" method="get">
        <button type="submit">商品一覧に戻る</button>
    </form>
</div>
</body>
</html>

<?php
// データベースの終了処理---------------------------------------------
// 結果セット$resultを解放する。
$result->close();
// データベース$データベース$mysqliとの接続を閉じます。
$mysqli->close();
?>
