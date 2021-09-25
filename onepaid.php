<?php

if (!defined("WHMCS")) {
    die("無法讀取文件");
}

function onepaid_MetaData()
{
    return array(
        'DisplayName' => 'onepaid',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function onepaid_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'OnePaid (萬付通)',
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
            'Type' => 'password',
            'Size' => '100',
            'Default' => '',
            'Description' => 'SecurityCode',
        ),
        'd' => array(
            'FriendlyName' => '說明',
            'Type' => 'readonly',
            'Size' => '1000',
            'Value' => '',
            'Description' => '付款通知網址請設定：<網址>/modules/gateways/callback/onepaid.php  舉例：https://shop.easy-store.tw/modules/gateways/callback/onepaid.php',
            'Default' => '',
        ),
    );
}

function onepaid_link($params)
{
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

    $url = 'https://payment.onepaid.com/payment/paypage';

    //交易參數
    $post = array(
        'MerID' => trim($MerID),
        'MerTradeNo' => 'WHMCS'.date("YmdHis"),
        'MerTradeDate' => date("Y-m-d H:i:s"),
        'PaywayType' => '0',
        'ProductName' => "Invoice".$invoiceId,
        'CurrencyType' => '1',
        'PaywayType' => '0',
        'TotalAmt' => $amount,
        'ReturnUrl' => $systemUrl."/modules/gateways/callback/onepaid-get.php",
        'Remark' => $invoiceId,
        'ShowResult' => '1',
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
    $md5 = md5($postfields);

    //生成需要提交的表單
    $htmlOutput = '<form method="post" action="' . $url . '">';
    foreach ($post as $key => $value) {
        $htmlOutput .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
    }
    $htmlOutput .= '<input type="hidden" name="SignCode" value="' . $md5 . '" />';
    $htmlOutput .= '<input class="btn btn-success" type="submit" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';

    return $htmlOutput;
}

