<?php
//セッションスタート
session_start();
//セッションの値の確認
//print "[SESSION]code:" . $_SESSION["code"] . "<br>";
//print "[SESSION]name:" . $_SESSION["name"] . "<br>";
//print "[SESSION]mem:" . $_SESSION["mem"] . "<br>";
//print "[SESSION]price:" . $_SESSION["price"] . "<br>";
//print "[SESSION]stock:" . $_SESSION["stock"] . "<br>";
//URLアドレスから引き渡された値の確認
//print "[GET]orders:" . $_GET["orders"] . "<br>";
//print "[GET]user_id:" . $_GET["user_id"] . "<br>";
//print "[GET]password:" . $_GET["password"] . "<br>";
//データベースの接続----------------------------------
$mysqli = new mysqli("localhost", "ei2435", "ei2435@alumni.hamako-ths.ed.jp", "ei2435");
if (mysqli_connect_errno()) {
    die("MySQL connection error: " . mysqli_connect_error());
}
//SQLの実行-----------------------------------------
//トランザクション処理を有効にする。
//$mysqli->autocommit(FALSE);
//ユーザーアカウントの取得
$sql = "select customer_id,password from customers where customer_name='" .
    htmlspecialchars($_GET["user_id"]) . "'";
//print $sql . "<br>";
if (!($result = $mysqli->query($sql))) {
    die("SQL error: " . $mysqli->error);
}
//SQL実行結果を取り出す。
$row = $result->fetch_array(MYSQLI_ASSOC);
// 数値添字配列:MYSQLI_NUM
// 連想配列:MYSQLI_ASSOC
// 連想配列および数値添字配列:MYSQLI_BOTH
//ユーザーアカウントの認証
if ($row["password"] == htmlspecialchars($_GET["password"])) {
    //認証OK
    print "THANK YOU.<br>";
    //売り上げ台帳salesへの書き込み
    $sql = "INSERT INTO sales (date,code,pcs,customer_id) VALUES ('" .
        date("Y-m-d", time()) . "'," . $_SESSION["code"] . "," .
        $_GET["orders"] . "," . $row["customer_id"] . ")";
    //print $sql . "<br>";
    if (!($result = $mysqli->query($sql))) {
        die("SQL error: " . $mysqli->error);
    }
    //商品（在庫）台帳ipodsへの書き込み
    $num = $_SESSION["stock"] - $_GET["orders"];
    $sql = "UPDATE ipods SET stock=" . $num . " WHERE code=" . $_SESSION["code"];
    //print $sql . "<br>";
    if (!($result = $mysqli->query($sql))) {
        die("SQL error: " . $mysqli->error);
    }
    //購入者へ確認メールの発送
    //mb_send_mail(htmlspecialchars($_GET["user_id"]), …);
    //販売者へ確認メールの発送
    //mb_send_mail("owner@xxx.com", …);
} else {
    //認証NG
    print "ERROR.<br>One more order!!";
}
//データベースの終了処理-----------------------------
//トランザクション処理を戻す。
//$mysqli->commit();
//結果セット$resultを解放する。
$result->close();
//データベース$mysqliとの接続を閉じます。
$mysqli->close();
?>