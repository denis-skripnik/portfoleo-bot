<?php
require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/helpers.php';

use GrapheneNodeClient\Commands\Single;
use GrapheneNodeClient\Commands\CommandQueryData;
use GrapheneNodeClient\Commands\Single\GetAccountsCommand;
use GrapheneNodeClient\Commands\Single\GetDynamicGlobalPropertiesCommand;

function getDynamicProps($chain) {
    $connector_class = CONNECTORS_MAP[$chain];
    $commandQuery3 = new CommandQueryData();
    $data3 = [];
   $commandQuery3->setParams($data3);
    $connector = new $connector_class();
        $command3 = new GetDynamicGlobalPropertiesCommand($connector);
    $res = $command3->execute($commandQuery3); 
    $result = $res['result'];
    $tvfs = (float)$result['total_vesting_fund_steem'];
  $tvsh = (float)$result['total_vesting_shares'];
    $steem_per_vests = 1000000 * $tvfs / $tvsh;
return $steem_per_vests;
}

function getAccountBalanceInGrapheneBlockchains($login, $chain) {
    $connector_class = CONNECTORS_MAP[$chain];

    $commandQuery = new CommandQueryData();
    
    $command_data = [
    '0' => [$login], //authors
    ];
    
    $commandQuery->setParams($command_data);
    
    $connector = new $connector_class();
    $command = new GetAccountsCommand($connector);
    
    $res = $command->execute($commandQuery); 
$result = $res['result'][0];
if (isset($result) && isset($result['balance'])) {
    $vesting_shares = (float)$result['vesting_shares'];
    if ($chain !== 'viz') {
$steem_per_vests = getDynamicProps($chain);
$vesting_shares = (float)$result['vesting_shares'] / 1000000 * $steem_per_vests;
    }
    return (float)$result['balance'] + $vesting_shares;
}
}

function getPage($url, $unic_id) {
    $cache_file = 'cache/'.$unic_id.'.cache';
    if(file_exists($cache_file)) {
      if(time() - filemtime($cache_file) > 60) {
         // too old , re-fetch
         $cache = file_get_contents($url);
         file_put_contents($cache_file, $cache);
      } else {
        $cache = file_get_contents('cache/'.$unic_id.'.cache');
      }
    } else {
      // no cache, create one
      $cache = file_get_contents($url);
      file_put_contents($cache_file, $cache);
    }
$res = json_decode($cache, true);
return $res;
}

function getBalances($name) {
    $json_wallets = file_get_contents('wallets.json');
    $wallets = json_decode($json_wallets, true);
    $wallet = $wallets[$name];
    if ($name === 'tron') {
        $acc = getPage('https://apilist.tronscan.org/api/account?address='.$wallet, 1);
    $balances = $acc['tokenBalances'];
    $balance = "";
    foreach ($balances as $token) {
    if ($token['tokenName'] === 'trx') $balance = $token['amount'] + ((int)$acc['totalFrozen'] / 1000000);
} // end foreach
    return ['balance' => $balance, 'link' => 'https://tronscan.io/#/address/'.$wallet];
} // end if
else if ($name === 'bitcoin') {
    $acc = getPage('https://blockchain.info/rawaddr/'.$wallet, 2);
$balance = $acc['final_balance'];
return ['balance' => $balance, 'link' => 'https://www.blockchain.com/btc/address/'.$wallet];
} // end if
else if ($name === 'ethereum') {
    $acc = getPage('https://api.etherscan.io/api?module=account&action=balance&address='.$wallet, 3);
    $balance = $acc['result'];
return ['balance' => $balance, 'link' => 'https://etherscan.io/address/'.$wallet];
} // end if
else if ($name === 'cosmos') {
    $acc = getPage('https://api.bscscan.com/api?module=account&action=tokenbalance&contractaddress=0x0eb3a705fc54725037cc9e008bdede697f62f335&address='.$wallet, 4);
    $balance = $acc['result'];
return ['balance' => $balance, 'link' => 'https://bscscan.com/address/'.$wallet];
} // end if
else if ($name === 'polkadot') {
    $acc = getPage('https://api.bscscan.com/api?module=account&action=tokenbalance&contractaddress=0x7083609fce4d1d8dc0c979aab8c869ea2c873402&address='.$wallet, 5);
$balance = (float)$acc['result'] / 10 ** 18;
return ['balance' => $balance, 'link' => 'https://bscscan.com/address/'.$wallet];
} // end if
else if ($name === 'bip') {
    $balances = getPage('https://explorer-api.minter.network/api/v2/addresses/'.$wallet.'?with_sum=true', 6);
        $balance = (float)$balances['data']['total_balance_sum'] + (float)$balances['data']['stake_balance_sum'];
return ['balance' => $balance, 'link' => 'https://explorer.minter.network/address/'.$wallet];
} // end if
else if ($name === 'viz' || $name === 'golos' || $name === 'steem') {
$balance = getAccountBalanceInGrapheneBlockchains($wallet, $name);
return ['balance' => $balance, 'link' => 'https://dpos.space/'.$name.'/profiles/'.$wallet];
}
}

?>