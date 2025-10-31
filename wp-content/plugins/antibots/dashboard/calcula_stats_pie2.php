<?php

/**
 * @author William Sergio Minossi
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
global $wpdb;
$table_name = $wpdb->prefix . "antibots_visitorslog";
$query = "SELECT COUNT(*) FROM " . $table_name . "
WHERE `human` = 'Bot'";
$quantos_bots = $wpdb->get_var($query);
$query = "SELECT COUNT(*) FROM " . $table_name . "
WHERE `human` = 'Human'";
$quantos_humanos = $wpdb->get_var($query);

if ($wpdb->last_error) {
    error_log("Query Error on table: " . $table_name . " error: " . $wpdb->last_error);
    return;
} else {
    /*
    var_export("Query de bots executada: " . $query);
    echo '<br>';
    var_export("Quantidade de bots: " . $quantos_bots);
    echo '<br>';
    var_export("Quantidade de humanos: " . $quantos_humanos);
    */
}


if ($quantos_bots < 1 or $quantos_humanos < 0) {

    $antibots_results10 = array();
    echo 'Sorry, no info available. Please, try again later.';
    return;
}
$total = $quantos_bots +  $quantos_humanos;
$antibots_results10[0]['Bots'] = $quantos_bots / $total;
$antibots_results10[0]['Humans'] = $quantos_humanos / $total;
