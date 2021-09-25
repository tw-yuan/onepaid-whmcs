<?php
$Trade = file_get_contents("php://input");
$REQUEST = json_decode($Trade, true);
//加載所需的庫
require_once __DIR__ . '/../../../init.php';
//根據回傳通知判斷付款方式以確認模組名稱
if ($REQUEST['PaymentType'] == '37') {
    $payway = 'onepaid_cvs';
} else {
    $payway = 'onepaid_bank';
};

//獲取回傳的參數
$StatusCode = $REQUEST["StatusCode"];
$orderMerID = $REQUEST["MerID"];

$command = 'AddInvoicePayment';
$postData = array(
    'invoiceid' => $REQUEST['Remark'],
    'transid' => $REQUEST['TradeNo'],
    'gateway' => $payway,
    'date' => $REQUEST['PaymentDate'],
);

$results = localAPI($command, $postData);
$results = json_encode($results);
$results = json_decode($results, true);
$results = strtoupper($results['result']);
echo "SUCCESS";
