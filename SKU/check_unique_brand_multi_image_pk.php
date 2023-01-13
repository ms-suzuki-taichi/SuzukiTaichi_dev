<?php
$is_dev = true;

$time_start = microtime(true);

$output_error_csv_path = '{エラー時にcsvを保存するディレクトリ}';
$no_unique_srv = [];

if ($is_dev) {
    // 開発shop環境
    $servers[] = 'dev';
} else {
    // 本番全Shop環境
    $conn_id = mysql_connect('127.0.0.1', 'id', '{idのパスワード}');
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

// 各サーバのDBにアクセス
foreach ($servers as $server) {
    $conn_shop = mysql_connect($server.'-db.makeshop.local', 'copy', '{copyのパスワード}');
    mysql_select_db('makeshop', $conn_shop);

    $sql = "select count(*) as cnt from brand_multi_image where adminuser != '' group by adminuser, brand_uid, brand_multi_image_id having cnt >= 2";
    $res = mysql_query($sql, $conn_shop);

    $res = mysql_fetch_array($res);
    if ($res['cnt'] == 0) {
        // 重複が無い場合は後続の処理をスキップして次のサーバに
        echo 'Server: ' . $server . ' => Success' . "\n";
        continue;
    }

    echo 'Server: ' . $server . ' => DuplicateCount: ' . $res['cnt'] . "\n";

    // uniqueでないサーバは後でCSVに書き出す
    // 全件updateに失敗した場合に40万件以上の結果が返ってくるとメモリーエラーかタイムアウトエラーで処理が止まるので、サーバの番号だけ残す
    array_push($no_unique_srv, $server);

    mysql_free_result($res);
    mysql_close($conn_shop);
}

$time = microtime(true) - $time_start;
echo "{$time} 秒\n";

// エラーが無かった場合はファイル書き出しをしない
if (empty($no_unique_srv)) {
    echo "AllSuccess\n";
    exit();
}

echo "Error\n";

// ファイル書き出し
$now = new DateTime();
$wright_path = $output_error_csv_path . $now->format('Y-m-d_H-i-s') . '.csv';
foreach ($no_unique_srv as $row) {
    file_put_contents($wright_path, $row . "\n", FILE_APPEND);
}