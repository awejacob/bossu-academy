<?php
/**
 * @author William Sergio Minossi
 * @copyright 2020
 */
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
global $wpdb;
$table_name = $wpdb->prefix . "antibots_visitorslog";
$antibots_current__url = esc_url($_SERVER['REQUEST_URI']);
$query19 = "SELECT ip, date
FROM   $table_name
WHERE human = 'Bot' and access = 'Denied'  and date > (NOW() - INTERVAL 24 HOUR)";
// WHERE human = \'Bot\' and access = \'Denied\' and date  > (NOW() - INTERVAL 12 HOUR) ORDER BY date DESC';

$results19 = $wpdb->get_results($query19, ARRAY_A);
if ($wpdb->num_rows < 1) {
  echo __('No bots blocked by IP. Please, try again later','antibots');
  return;
}
foreach ($results19 as $result19) {
  $data19[] = strval($result19['ip']);
}
$results18 = (array_count_values($data19));
arsort($results18);
echo '<table class="greyGridTable">';
echo '<thead>';
echo "<tr><th>Bot <br />IP</th>  <th><?php _e('Number', 'antibots');?> <br /><?php _e('Blocked', 'antibots');?></th></tr>";
echo '</thead>';
$count = 0;
foreach ($results18 as $key => $value) {
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