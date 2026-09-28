<?php
//データベースの接続--------------------------------
$mysqli = new mysqli("localhost", "ei2435", "ei2435@alumni.hamako-ths.ed.jp", "ei2435");
if (mysqli_connect_errno()) {
    die("MySQL connection error: " . mysqli_connect_error());
}