<?php

/**
 * @author William Sergio Minossi
 * @copyright 2020
 */
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly



global $wpdb;
$antibots_table = $wpdb->prefix . "antibots_visitorslog";
$query = "SELECT * from " . $antibots_table . "  limit 1";
$result = $wpdb->get_results($query);

if ($wpdb->num_rows < 1) {
  echo '<br>';
  echo '<br><h3>';
  echo __("Empty Table. Please, try again later.", "antibots");
  sleep(5);
  echo '<br></h3>';
  return;
}
?>

<style>
  div.dataTables_wrapper div.dataTables_processing {
    top: 0;
  }
</style>

<div id="antibots-logo">
  <img alt="logo" src="<?php echo ANTIBOTSIMAGES; ?>/logo.png" width="250px" />
</div>
<div id="antibots_help_title">
  <?php _e('Visits Log', 'antibots'); ?>
</div>
<div class="table-responsive" style="margin-top: 0px; margin-right:20px; width: 99%; max-width:99%;">
  <table style="margin-right:20px; cellpadding=" 0" cellspacing="0" border="1px" class="dataTable" id="dataTableBots">


    <form action="" method="post">
      <input type="hidden" name="antibots_view_visits" id="antibots_view_visits" value="<?php echo esc_attr(wp_create_nonce('antibots_view_visits')); ?>">
    </form>


    <thead>
      <tr>
        <th></th>
        <th>date</th>
        <th>access</th>
        <th>ip</th>
        <th>human</th>
        <th>response</th>
        <th>method</th>
        <th>user agent</th>
        <th>url</th>
        <th>referer</th>
      </tr>
    </thead>
    <tfoot>
      <tr>
        <th></th>
        <th>date</th>
        <th>access</th>
        <th>ip</th>
        <th>human</th>
        <th>response</th>
        <th>method</th>
        <th>user agent</th>
        <th>url</th>
        <th>referer</th>
      </tr>
    </tfoot>
    <tbody>
    </tbody>
  </table>
  <div id="dialog-confirm" title="Confirm">
    <div id="modal-body">
    </div>
  </div>