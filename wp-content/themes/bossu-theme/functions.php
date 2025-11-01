
<?php
// Enqueue in functions.php:
function bossu_enqueue_scripts() {
wp_enqueue_script('fingerprintjs', 'https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@3/dist/fp.min.js', [], null, true);
wp_add_inline_script('fingerprintjs', '
FingerprintJS.load().then(fp => {
fp.get().then(result => {
const visitorId = result.visitorId;
jQuery.post(ajaxurl, { action: "bossu_log_fingerprint", fingerprint: visitorId });
});
});
');
wp_enqueue_script('bossu-anti-fraud', get_template_directory_uri() . '/js/anti-fraud.js', ['jquery'], null, true);
wp_localize_script('bossu-anti-fraud', 'bossu_data', ['ajaxurl' => admin_url('admin-ajax.php'), 'user_id' => get_current_user_id()]);
}
add_action('wp_enqueue_scripts', 'bossu_enqueue_scripts');
function bossu_log_fingerprint() {
global $wpdb;
$fingerprint = sanitize_text_field($_POST['fingerprint']);
$user_id = get_current_user_id();
$wpdb->insert("{$wpdb->prefix}bossu_risk_events", ['user_id' => $user_id, 'type' => 'device_fingerprint', 'score' => 0.5, 'metadata' => json_encode(['fingerprint' => $fingerprint]), 'created_at' => current_time('mysql')]);
wp_die();
}
add_action('wp_ajax_bossu_log_fingerprint', 'bossu_log_fingerprint');
add_action('wp_ajax_nopriv_bossu_log_fingerprint', 'bossu_log_fingerprint');
function log_paste_risk() {
global $wpdb;
$user_id = sanitize_text_field($_POST['user_id']);
$wpdb->insert("{$wpdb->prefix}bossu_risk_events", ['user_id' => $user_id, 'type' => 'paste_spike', 'score' => 0.6, 'metadata' => json_encode(['event' => 'paste']), 'created_at' => current_time('mysql')]);
wp_die();
}
add_action('wp_ajax_log_paste_risk', 'log_paste_risk');
function log_speed_risk() {
global $wpdb;
$user_id = get_current_user_id();
$wpdb->insert("{$wpdb->prefix}bossu_risk_events", ['user_id' => $user_id, 'type' => 'speed_anomaly', 'score' => 0.8, 'metadata' => json_encode(['dwell' => 'low']), 'created_at' => current_time('mysql')]);
wp_die();
}
add_action('wp_ajax_log_speed_risk', 'log_speed_risk');