<?php /**
 * @ Author: Bill Minozzi
 * @ Copyright: 2020 www.BillMinozzi.com
 * @ Modified time: 2020-03-17 15:02:12
 */
if (!defined('ABSPATH')) 
    exit;
global $wpdb;
global $table_prefix;
$table = $table_prefix . "antibots_visitorslog";
// Table's primary key
$primaryKey = 'date';
$columns = array(
    array(
        'db'        => 'date',
        'dt'        => 1,
        'formatter' => function ($d, $row) {
            return date('d-M-Y H:i:s', strtotime($d));
        }
    ),
    array('db' => 'access', 'dt' => 2),
    array('db' => 'ip', 'dt' => 3),
    array('db' => 'human',  'dt' => 4),
    array('db' => 'response',   'dt' => 5),
    array('db' => 'method',     'dt' => 6),
    array('db' => 'ua',     'dt' => 7),
    array('db' => 'url',     'dt' => 8),
    array('db' => 'referer',     'dt' => 9)
);
require('_ssp.class.php');
echo json_encode(
    ANTIBOT_SSP::simple($_GET, $table, $primaryKey, $columns)
);