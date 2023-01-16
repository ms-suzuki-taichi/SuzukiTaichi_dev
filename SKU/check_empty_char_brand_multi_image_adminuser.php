<?php
$time_start = microtime(true);

$is_dev = php_uname('n') != 'main-etc01';

$user_id_pass = '*****'; // id
$user_copy_pass = '*****'; // copy

if ($is_dev) {
    // dev-shop
    echo "dev-shop\n";
    $servers[] = 'dev';
} else {
    // pro-shop
    echo "pro-shop\n";
    $conn_id = mysql_connect('127.0.0.1', 'id', $user_id_pass);
    mysql_select_db("id", $conn_id);
    $result = mysql_query("SELECT premium FROM curtype", $conn_id);
    $curtype_cnt = substr(mysql_result($result, 0), 4);
    mysql_free_result($result);
    mysql_close($conn_id);

    $servers[] = 'free';

    for ($i=1; $i<=$curtype_cnt; $i++){
        $servers[] = 'shop'. $i;
    }
}

echo "server,adminuser,brand_uid,seq,path,created,modified\n";
foreach ($servers as $server) {
    $conn_shop = mysql_connect($server.'-db.makeshop.local', 'copy', $user_copy_pass);
    mysql_select_db('makeshop', $conn_shop);

    $sql = "select adminuser, brand_uid, seq, path, created, modified from brand_multi_image where adminuser=''";
    $res = mysql_query($sql, $conn_shop);

    while ($row = mysql_fetch_object($res)) {
        echo sprintf("%s,%s,%s,%s,%s,%s,%s\n", $server, $row->adminuser, $row->brand_uid, $row->seq, $row->path, $row->created, $row->modified); 
    }

    mysql_free_result($res);
    mysql_close($conn_shop);
}

$time = microtime(true) - $time_start;
echo "\n{$time} иц\n";