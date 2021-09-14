<?php

if (!defined("WHMCS")) {
    die("無法讀取文件");
}
use WHMCS\Database\Capsule;
function onepaid_cvs_MetaData()
{
    return array(
        'DisplayName' => 'onepaid_cvs',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function onepaid_cvs_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'OnePaid (萬付通)超商代碼-背景取號',
        ),
        // a text field type allows for single line text input
        'MerID' => array(
            'FriendlyName' => 'MerchantID (商店代號)',
            'Type' => 'text',
            'Size' => '100',
            'Default' => '',
            'Description' => 'MerchantID',
        ),
        // a password field type allows for masked text input
        'SecurityCode' => array(
            'FriendlyName' => 'SecurityCode',
            'Type' => 'text',
            'Size' => '100',
            'Default' => '',
            'Description' => 'SecurityCode',
        ),
    );
}

function onepaid_cvs_link($params){
    // 網關參數
    $MerID = $params['MerID'];
    $SecurityCode = $params['SecurityCode'];

    // 帳單參數
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    //由於支付接口不支援小數金額，因此取整數金額。
    $amount = round($params['amount']);
    $amount = $amount.'.00';

    // 客戶參數
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // 系統參數
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    $url = 'https://payment.onepaid.com/payment/PayLaterOrder';
    $time = date("Y-m-d H:i:s");
    $oid = date("YmdHis");
    //交易參數
    $post = array(
        'MerID' => trim($MerID),
        'MerTradeNo' => 'WHMCS'.$oid,
        'MerTradeDate' => $time,
        'PaymentType' => '37',
        'ProductName' => "Invoice".$invoiceId,
        'CurrencyType' => '1',
        'TotalAmt' => $amount,
        'Remark' => $invoiceId,
    );
    //產生檢核碼
    ksort($post);
    $postfields = http_build_query($post);
    $postfields = "SecurityCode=".$SecurityCode."&".$postfields;
    $postfields = urlencode($postfields);
    $postfields = str_replace('%2d', '-', $postfields);
    $postfields = str_replace('%5f', '_', $postfields);
    $postfields = str_replace('%2e', '.', $postfields);
    $postfields = str_replace('%21', '!', $postfields);
    $postfields = str_replace('%2a', '*', $postfields);
    $postfields = str_replace('%28', '(', $postfields);
    $postfields = str_replace('%29', ')', $postfields);
    $postfields = str_replace('&', '%3d', $postfields);
    $postfields = strtolower($postfields);
    $postfields = str_replace('%253a%252f%252f', '%3a%2f%2f', $postfields);
    $postfields = str_replace('%2b', '+', $postfields);
    $postfields = str_replace('%253a', '%3a', $postfields);
    $postfields = str_replace('%252f', '%2f', $postfields);
    $SignCode = md5($postfields);

    $data = array(
        'MerID' => trim($MerID),
        'MerTradeNo' => 'WHMCS'.$oid,
        'MerTradeDate' => $time,
        'PaymentType' => '37',
        'ProductName' => "Invoice".$invoiceId,
        'CurrencyType' => '1',
        'TotalAmt' => $amount,
        'Remark' => $invoiceId,
        'SignCode' => $SignCode,
    );
    function curl_post($url,$data)
    {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST,true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    $result = curl_exec($ch);
    curl_close ($ch);
    return $result;
    }

    $data = json_decode(curl_post($url,$data), true);
    $eee = $data['CustomeArgs'];
    $info = str_replace("]","",str_replace("[","",$eee));
    $info = json_decode($info, true);
    global $_LANG;
    $PaymentNo = $info['CVSCode'];
    $ExpireDateStr = $info['PayDeadline'];
    $code = '<div class="text-left alert alert-info"><p><b>繳費代碼：</b><code>'.$PaymentNo.'</code></p>'.
    '<p><b>繳費期限：</b><code>'.$ExpireDateStr.'</code></p></div>';
    return $code;
    echo '<meta http-equiv="refresh" content="0;url='.$params['returnurl'].'" />';
}