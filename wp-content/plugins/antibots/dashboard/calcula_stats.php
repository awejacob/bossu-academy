<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

 
global $wpdb;
$table_name = $wpdb->prefix . "antibots_visitorslog";

$results = $wpdb->get_results("
    SELECT DATE_FORMAT(date, '%m%d') as mydate
    FROM `$table_name`
    WHERE access <> 'OK'
      AND DATEDIFF(NOW(), date) <= 14
    ORDER BY date DESC
", ARRAY_A);

if (!$results) {
    $array30d = [];
    $array30  = [];
    return;
}

$data = [];
foreach ($results as $result) {
    $data[] = strval($result['mydate']);
}

$results8 = array_count_values($data);
unset($results, $data);

if (count($results8) < 1) {
    $array30d = [];
    $array30  = [];
    return;
}

$timestamp = time();
$array30d = [];
$array30  = [];

$x = 0;
$d = 15;
for ($i = $d; $i > 0; $i--) {
    $tm = $timestamp - (86400 * $x);
    $the_day = date("d", $tm);
    $this_month = date("m", $tm);

    $day_key = $this_month . $the_day;
    $array30d[$x] = $day_key;
    $array30[$x]  = $results8[$day_key] ?? 0;

    $x++;
}

$array30  = array_reverse($array30);
$array30d = array_reverse($array30d);
