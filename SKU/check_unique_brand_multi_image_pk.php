<?php
$time_start = microtime(true);

$is_dev = php_uname('n') != 'main-etc01';

$user_id_pass = '*****'; // id
$user_copy_pass = '*****'; // copy

$output_error_csv_path = '/home/suzuki.taichi/';
$no_unique_srv = [];

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

foreach ($servers as $server) {
    $conn_shop = mysql_connect($server.'-db.makeshop.local', 'copy', $user_copy_pass);
    mysql_select_db('makeshop', $conn_shop);

    $sql = "select count(*) as cnt from brand_multi_image where adminuser != '' group by adminuser, brand_uid, brand_multi_image_id having cnt >= 2";
    $res = mysql_query($sql, $conn_shop);

    $res = mysql_fetch_array($res);
    if ($res['cnt'] == 0) {
        echo 'Server: ' . $server . ' => Success' . "\n";
    } else {
        echo 'Server: ' . $server . ' => Error' . "\n";
        array_push($no_unique_srv, $server);
    }

    mysql_free_result($res);
    mysql_close($conn_shop);
}

$time = microtime(true) - $time_start;
echo "{$time} 秒\n";

// エラーが無かった場合はファイル書き出しをしない
if (empty($no_unique_srv)) {
    echo "重複はありませんでした\n";
    exit();
}

echo "重複が見つかりました\n";

// ファイル書き出し
$now = new DateTime();
$write_path = $output_error_csv_path . $now->format('Y-m-d_H-i-s') . '.csv';
foreach ($no_unique_srv as $row) {
    file_put_contents($write_path, $row . "\n", FILE_APPEND);
}