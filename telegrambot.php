<?php
$admin_id = 123456789;
$api_key = '';

require_once('functions.php');

function botControl($text) {
    $lng = [];
    $russian = file_get_contents('ru.json');
$lng['ru'] = json_decode($russian, true); 
if ($text && $text === '/start') {
    $msg = $lng['ru']['home_text'];
    $prices = getPage('https://api.coingecko.com/api/v3/simple/price?ids=BITCOIN%2CETHEREUM%2CCOSMOS%2CPOLKADOT%2CBIP%2CNEAR%2CSTEEM%2CTRON%2CGOLOS&vs_currencies=USD', 8);
    $viz_price = getPage('https://eiy5lsown5.execute-api.us-east-2.amazonaws.com/', 9);
    $prices['viz'] = ['usd' => $viz_price['average_bid_price']];
    foreach ($prices as $name => $price) {
        $data = getBalances($name);
        if (isset($data['balance']) && $data['balance'] === 0) continue;
        $coin = $name;
$balance = '';
        if (isset($data['link'])) {
    $coin = '<a href="'.$data['link'].'">'.$name.'</a>';
}
if (isset($data['balance'])) $balance = ' ('.$lng['ru']['balance'].' '.round($data['balance'], 3).')';
        $msg .= '
'.$coin.' - '.round($price['usd'], 6).$balance;
    }
    $arInfo["inline_keyboard"] = [[['url' => 'https://t.me/blind_dev_contact_bot', 'text' => $lng['ru']['contact']]]];
// $arInfo["keyboard"][0][0]["text"] = "Кнопка";    
return ['msg' => $msg, 'buttons' => $arInfo];
} else {
    $msg = 'Сообщение отправлено.';
    $arInfo["inline_keyboard"] = [[['callback_data' => "/start", 'text' => "Главная"]]];
return ['msg' => $msg, 'buttons' => $arInfo];
}
}

$body = file_get_contents('php://input'); 
$arr = json_decode($body, true); 
 
include_once ('telegramgclass.php');   

$tg = new tg($api_key);
$chat_id = $arr['message']['chat']['id'];
if (!isset($arr['callback_query'])) {
    $text = (isset($arr['message']['text']) ? $arr['message']['text'] : '');
    $data = botControl($text);
$tg->send($chat_id, $data['msg'], 0, $data['buttons']);
} else if (isset($arr['callback_query']) && $arr['callback_query']['data']) {
    $data = botControl($arr['callback_query']['data']);
    $tg->edit($arr['callback_query']['message']['chat']['id'], $arr['callback_query']['message']['message_id'], $data['msg'], $data['buttons']);
    }
?>