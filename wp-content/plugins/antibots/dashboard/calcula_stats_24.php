<?php
/**
 * @ Author: Bill Minozzi
 * @ Copyright: 2020 www.BillMinozzi.com
 * @ Modified time: 2020-03-13 19:43:01
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
global $wpdb;
$table_name = $wpdb->prefix . "antibots_visitorslog";

/*
$query = 'SELECT date, DATE_FORMAT(date, "%H") as myhour
FROM   ' . $table_name . '
WHERE human = \'Bot\' and access = \'Denied\' and date  > (NOW() - INTERVAL 12 HOUR) ORDER BY date DESC';
*/


$query = 'SELECT date, DATE_FORMAT(date, "%H") as myhour
FROM   ' . $table_name . '
WHERE access <> \'OK\' and date  > (NOW() - INTERVAL 12 HOUR) ORDER BY date DESC';

$results9 = $wpdb->get_results($query, ARRAY_A);




if (!$results9){
    $array24l = array();
    $array24 = array();
    return;
}


foreach ($results9 as $result5) {
    $data5[] = strval($result5['myhour']);
}
$results8 = (array_count_values($data5));
unset($results9);
foreach ($results8 as $key => $value) {
    $i = $key;
    break;
}

if(count($results8) < 2)
{
    $array24l = array();
    $array24 = array();
    return;
}

$ctd = 0;
$i = intval($i);
foreach ($results8 as $key => $value) {
    $key = intval($key);
    if ($ctd > 0)
        $i--;
    while ($i <> $key) {
        if ($i == 0)
            $i = 24;
        if ($i < 1) {
            if ($key == 0)
                $key = 24;
        }
        if ($i < 9)
            $w = '0' . (string) $i;
        else
            $w = (string) $i;
        $array24l[] = $w;
        $array24[] = 0;
        if ($i > $key)
            $i--;
        $ctd++;
        if ($ctd > 11)
            break;
    }
    if ($i == 0)
        $i = 24;
    if ($key == 0)
        $key = 24;
    if ($key < 9)
        $w = '0' . (string) $key;
    else
        $w = (string) $key;
    $array24l[] = $w;
    $array24[] = $value;
    $ctd++;
    if ($ctd > 11)
        break;
}
$array24l = array_reverse($array24l);
$array24 = array_reverse($array24);