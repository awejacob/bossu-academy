<?php
/**
 * @author William Sergio Minossi
 * @copyright 2020
 *  */
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
global $wpdb;
$table_name = $wpdb->prefix . "antibots_visitorslog";
$antibots_current__url = esc_url($_SERVER['REQUEST_URI']);
$query = "SELECT ip
FROM   $table_name
WHERE human = 'Bot' and access = 'Denied'  and `date` >= CURDATE() - interval 15 day";
$results9 = $wpdb->get_results($query, ARRAY_A);
if ($wpdb->num_rows < 1) {
  echo 'No bots blocked by IP. Please, try again later';
  return;
}
foreach ($results9 as $result) {
  $data[] = strval($result['ip']);
}
unset($results9);
$results8 = (array_count_values($data));
unset($data);
arsort($results8);
echo '<table class="greyGridTable">';
echo '<thead>';
echo "<tr><th>Bot <br />IP</th>  <th>Num <br />Blocked</th></tr>";
echo '</thead>';
$count = 0;
foreach ($results8 as $key => $value) {
  echo "</tr>";
  echo "<tr>";
  echo "<td>";
  echo $key;
  echo "</td>";
  echo "<td>";
  echo $value;
  echo "</td>";
  echo "</tr>";
  $count++;
  if ($count == 10)
    break;
}
echo "</table>";