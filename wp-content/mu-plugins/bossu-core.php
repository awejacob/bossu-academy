
<?php
/* 
Plugin Name: Bossu Core Module
Description: Core foundation for Bossu Academy functionality.
Author: Bossu Academy
Version: 1.0
*/

// Custom hooks for modularity
do_action('bossu_init');

// Add Bossu Console menu (placeholder)
function bossu_add_console() {
    add_menu_page(
        'Bossu Console',        
        'Bossu Console',     
        'manage_options',       
        'bossu-console',        
        function() {            
            echo '<h1>Bossu Console Placeholder</h1>';
        }
    );
}
add_action('admin_menu', 'bossu_add_console');

// Hook for future skill plugins
function bossu_register_skill($slug) {
    // Future: Register courses from plugin
}
add_action('bossu_register_skill', 'bossu_register_skill');




// ====================================
// DATABASE SETUP: Bossu Academy Tables
// ====================================

function bossu_install_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $tables = [
        "CREATE TABLE {$wpdb->prefix}bossu_points (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            course_id BIGINT UNSIGNED NOT NULL,
            points INT NOT NULL,
            integrity_score FLOAT DEFAULT 1.0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $charset;",

        "CREATE TABLE {$wpdb->prefix}bossu_rewards (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            trigger VARCHAR(50) NOT NULL,
            coin_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending','approved','held','rejected') DEFAULT 'pending',
            refs TEXT,
            audit_log TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $charset;",

        "CREATE TABLE {$wpdb->prefix}bossu_risk_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL,
            score FLOAT NOT NULL,
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $charset;",

        "CREATE TABLE {$wpdb->prefix}bossu_guardian_consents (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id BIGINT UNSIGNED NOT NULL,
            guardian_id BIGINT UNSIGNED NOT NULL,
            method ENUM('email','sms') NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verification_hash VARCHAR(255) NOT NULL
        ) $charset;"
    ];

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    foreach ($tables as $sql) {
        dbDelta($sql);
    }
}

// For mu-plugin (manual activation)
add_action('admin_init', function() {
    if (!get_option('bossu_tables_installed')) {
        bossu_install_tables();
        update_option('bossu_tables_installed', true);
    }
});





//SETTINGS PAGE
function bossu_add_settings_page() {
    add_submenu_page(
        'bossu-console', 
        'Settings',
        'Settings', 
        'manage_options',
        'bossu-settings', 
        'bossu_settings_callback' 
    );
}
add_action('admin_menu', 'bossu_add_settings_page');

function bossu_settings_callback() {
    ?>
    <div class="wrap">
        <h1>Bossu Settings</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('bossu_group');
                do_settings_sections('bossu-settings');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

function bossu_register_settings() {
    register_setting('bossu_group', 'bossu_ratio');
    add_settings_section('bossu_section', 'Bossu Settings', null, 'bossu-settings');

    add_settings_field('bossu_ratio', 'Points to Coin Ratio', function() {
        echo '<input name="bossu_ratio" value="' . get_option('bossu_ratio', '500:1') . '">';
    }, 'bossu-settings', 'bossu_section');

    register_setting('bossu_group', 'bossu_enroll_bonus');
    add_settings_field('bossu_enroll_bonus', 'Enroll Bonus %', function() {
        echo '<input name="bossu_enroll_bonus" value="' . get_option('bossu_enroll_bonus', '20') . '">';
    }, 'bossu-settings', 'bossu_section');

    register_setting('bossu_group', 'bossu_completion_rebate');
    add_settings_field('bossu_completion_rebate', 'Completion Rebate %', function() {
        echo '<input name="bossu_completion_rebate" value="' . get_option('bossu_completion_rebate', '10') . '">';
    }, 'bossu-settings', 'bossu_section');

    register_setting('bossu_group', 'bossu_daily_cap');
    add_settings_field('bossu_daily_cap', 'Daily Payout Cap', function() {
        echo '<input name="bossu_daily_cap" value="' . get_option('bossu_daily_cap', '5') . '">';
    }, 'bossu-settings', 'bossu_section');

    register_setting('bossu_group', 'bossu_risk_threshold');
    add_settings_field('bossu_risk_threshold', 'Risk Threshold', function() {
        echo '<input name="bossu_risk_threshold" value="' . get_option('bossu_risk_threshold', '0.7') . '">';
    }, 'bossu-settings', 'bossu_section');
}
add_action('admin_init', 'bossu_register_settings');




//////////////////////////////////////////////////////////////////////////////////
// 1. Add to bossu-core.php:
function bossu_payment_bonus($order_id) {
$order = wc_get_order($order_id);
if ($order->get_status() == 'completed') {
$fingerprint = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
$bonus = get_option('bossu_enroll_bonus', 20);
mycred_add('enroll_bonus', $order->get_user_id(), $bonus, 'Enrollment Bonus');
global $wpdb;
$coin_amount = $bonus / (int) explode(':', get_option('bossu_ratio', '500:1'))[0];
$wpdb->insert("{$wpdb->prefix}bossu_rewards", ['user_id' => $order->get_user_id(), 'trigger' => 'enroll', 'coin_amount' => $coin_amount, 'status' => 'pending', 'refs' => json_encode(['order_id' => $order_id]), 'audit_log' => 'Payment complete']);
do_action('bossu_reward_inserted', $wpdb->insert_id);
if (/* add suspicion logic, e.g., GeoIP VPN check */ true) {
$wpdb->insert("{$wpdb->prefix}bossu_risk_events", ['user_id' => $order->get_user_id(), 'type' => 'device_fingerprint', 'score' => 0.5, 'metadata' => json_encode(['fingerprint' => $fingerprint]), 'created_at' => current_time('mysql')]);
}
// Post-payment video redirect
$items = $order->get_items();
$first_item = reset($items);

// Safely get product ID
$course_id = is_object($first_item) ? $first_item->get_data()['product_id'] : 0;

if ($course_id) {
    wp_redirect(get_permalink($course_id) . '?welcome=1');
    exit;
}
}
}
add_action('woocommerce_payment_complete', 'bossu_payment_bonus');


////////////////////////////////////////////////////////////////////////////////////////////////////////////////



function bossu_course_completion_rebate($user_id, $course_id) {
$min_time = 300; // seconds
$actual_time = get_post_meta($course_id, '_user_time_spent', true); // Assume tracked
if ($actual_time >= $min_time) {
$rebate = get_option('bossu_completion_rebate', 10);
mycred_add('completion_rebate', $user_id, $rebate, 'Course Completion');
global $wpdb;
$coin_amount = $rebate / (int) explode(':', get_option('bossu_ratio', '500:1'))[0];
$wpdb->insert("{$wpdb->prefix}bossu_rewards", ['user_id' => $user_id, 'trigger' => 'complete', 'coin_amount' => $coin_amount, 'status' => 'pending', 'refs' => json_encode(['course_id' => $course_id]), 'audit_log' => 'Course completed']);
do_action('bossu_reward_inserted', $wpdb->insert_id);
$integrity = $actual_time / $min_time; // Simple score
$wpdb->update("{$wpdb->prefix}bossu_points", ['points' => $rebate, 'integrity_score' => $integrity], ['user_id' => $user_id, 'course_id' => $course_id]);
} else {
global $wpdb;
$wpdb->insert("{$wpdb->prefix}bossu_risk_events", ['user_id' => $user_id, 'type' => 'speed_anomaly', 'score' => 0.8, 'metadata' => json_encode(['actual_time' => $actual_time]), 'created_at' => current_time('mysql')]);
}
}
add_action('lifterlms_course_completed', 'bossu_course_completion_rebate', 10, 2);


/////////////////////////////////////
function bossu_quiz_submitted($quiz_id, $submission) {
    $user_id = $submission['user_id'];
    $attempts = get_user_meta($user_id, 'quiz_attempts_' . $quiz_id, true) ?: 0;
    update_user_meta($user_id, 'quiz_attempts_' . $quiz_id, $attempts + 1);

    if ($attempts > 5) {
        global $wpdb;
        $wpdb->insert("{$wpdb->prefix}bossu_risk_events", [
            'user_id' => $user_id,
            'type' => 'excess_attempts',
            'score' => 0.7,
            'metadata' => json_encode(['attempts' => $attempts]),
            'created_at' => current_time('mysql')
        ]);
        return;
    }

    mycred_add('quiz_points', $user_id, 50, 'Quiz Completed');

    global $wpdb;
    $course_id = get_post_meta($quiz_id, '_llms_parent_course', true); // Get course

    // Example integrity formula: 1 - (attempts / 6)
    $integrity = 1 - ($attempts / 6);

    $wpdb->insert("{$wpdb->prefix}bossu_points", [
        'user_id' => $user_id,
        'course_id' => $course_id,
        'points' => 50,
        'integrity_score' => $integrity,
        'updated_at' => current_time('mysql')
    ]);
}
add_action('qsm_quiz_submitted', 'bossu_quiz_submitted', 10, 2);



//////////////////////////
function bossu_hold_high_rewards($reward_id) {
global $wpdb;
$amount = $wpdb->get_var("SELECT coin_amount FROM {$wpdb->prefix}bossu_rewards WHERE id = $reward_id");
$cap = get_option('bossu_daily_cap', 5);
if ($amount > $cap) { $wpdb->update("{$wpdb->prefix}bossu_rewards", ['status' => 'held'], ['id' => $reward_id]); }
}
add_action('bossu_reward_inserted', 'bossu_hold_high_rewards');


/////////////////////////////////////////////

// Page 7: Day 2 - Step 3: Basic Anti-Fraud Layer and Testing (2-3 Hours)
// 1. Add to bossu-core.php:

function add_honeypot_to_forms($args, $key, $value) {
if ($key === 'honeypot') { $args['class'][] = 'hidden'; $args['label'] = ''; }
return $args;
}
add_filter('woocommerce_form_field_args', 'add_honeypot_to_forms', 10, 3);
add_filter('wp_registration_form_field_args', 'add_honeypot_to_forms', 10, 3); // Extend to registration
function check_honeypot() {
if (!empty($_POST['honeypot'])) { global $wpdb; $user_id = get_current_user_id() ? get_current_user_id() : 0; $wpdb->insert("{$wpdb->prefix}bossu_risk_events", ['user_id' => $user_id, 'type' => 'honeypot', 'score' => 1.0, 'metadata' => json_encode(['ip' => $_SERVER['REMOTE_ADDR']]), 'created_at' => current_time('mysql')]); die('Bot detected - please contact support.'); }
}
add_action('woocommerce_checkout_process', 'check_honeypot');
add_action('register_post', 'check_honeypot');
add_action('lifterlms_before_quiz_submit_button', 'check_honeypot');



function bossu_session_check() {
$current_ip = $_SERVER['REMOTE_ADDR'];
$stored_ip = get_user_meta(get_current_user_id(), '_bossu_ip', true);
if ($stored_ip && $stored_ip != $current_ip) { global $wpdb; $wpdb->insert("{$wpdb->prefix}bossu_risk_events", ['user_id' => get_current_user_id(), 'type' => 'ip_change', 'score' => 0.8, 'metadata' => json_encode(['old_ip' => $stored_ip, 'new_ip' => $current_ip]), 'created_at' => current_time('mysql')]); // Optional: Hold rewards for user }
update_user_meta(get_current_user_id(), '_bossu_ip', $current_ip);
}
add_action('wp_login', 'bossu_session_check');
add_action('lifterlms_lesson_start', 'bossu_session_check');
add_action('woocommerce_checkout_process', 'bossu_session_check');
}



// P Step 1: Importer and Subject Packs 
function bossu_add_importer_page() {
add_submenu_page('bossu-console', 'Import Packs', 'Import Packs', 'manage_options', 'bossu-import', 'bossu_importer_callback');
}
add_action('admin_menu', 'bossu_add_importer_page');
function bossu_importer_callback() {
if (isset($_FILES['import_pack'])) {
$file = $_FILES['import_pack']['tmp_name'];
$zip = new ZipArchive();
if ($zip->open($file) === TRUE) {
$extract_path = WP_CONTENT_DIR . '/bossu-imports/';
if (!file_exists($extract_path)) mkdir($extract_path, 0755, true);
$zip->extractTo($extract_path);
$zip->close();
$json_data = json_decode(file_get_contents($extract_path . 'pack.json'), true);
if ($json_data && isset($json_data['uuid'])) {
global $wpdb;
$existing = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'bossu_uuid' AND meta_value = %s", $json_data['uuid']));
if (!$existing) {
$course_id = wp_insert_post(['post_title' => $json_data['course_title'], 'post_type' => 'course', 'post_status' => 'publish']);
add_post_meta($course_id, 'bossu_uuid', $json_data['uuid']);
foreach ($json_data['lessons'] as $lesson) {
$lesson_id = llms_insert_lesson($course_id, $lesson);
if (isset($lesson['video_url'])) { update_post_meta($lesson_id, '_llms_video', esc_url($lesson['video_url'])); }
if (isset($lesson['chart_data'])) { $chart_id = visualizer_create_chart($lesson['chart_data']); update_post_meta($lesson_id, '_bossu_chart', $chart_id); }
}
if (isset($json_data['quizzes'])) {
foreach ($json_data['quizzes'] as $quiz) {
$quiz_id = wp_insert_post(['post_title' => $quiz['title'], 'post_type' => 'qsm_quiz', 'post_status' => 'publish']);
update_post_meta($quiz_id, 'quiz_settings', serialize(['timer_limit' => $quiz['timer'] ?? 0, 'random_questions' => $quiz['randomize'] ? 1 : 0, 'enable_quick_result_mc' => $quiz['real_time_feedback'] ? 1 : 0]));
$questions = [];
foreach ($quiz['questions'] as $q) {
$questions[] = ['question' => $q['text'], 'answers' => $q['options'], 'question_type_new' => $q['type'] ?? 0, 'comments' => $q['feedback'] ?? ''];
}
update_post_meta($quiz_id, 'mlw_quiz_questions', maybe_serialize($questions));
update_post_meta($lesson_id, '_llms_quiz', $quiz_id);
}
}
pll_set_post_language($course_id, $json_data['lang']);
// AI check
// $lt = new LanguageTool();
// $content = get_post_field('post_content', $course_id);
// $check_result = $lt->check($content);
// if (!empty($check_result['matches'])) { error_log('Grammar issues in imported course: ' . print_r(value: $check_result, true)); }
// Optional: Auto-apply suggestions
}
}
echo '<div class="notice notice-success">Pack imported successfully.</div>';
} else {
echo '<div class="notice notice-error">Failed to open zip.</div>';
}
}
?>
<div class="wrap">
<h1>Import Subject Packs</h1>
<form method="post" enctype="multipart/form-data">
<input type="file" name="import_pack" accept=".zip">
<input type="submit" value="Upload and Import">
</form>
</div>
<?php
}

// Step 2: Admin Console and Reports (3-4 Hours)

function bossu_add_console_pages() {
add_menu_page('Bossu Console', 'Bossu Console', 'manage_options', 'bossu-console', 'bossu_dashboard_callback');
add_submenu_page('bossu-console', 'Dashboard', 'Dashboard', 'manage_options', 'bossu-console', 'bossu_dashboard_callback');
add_submenu_page('bossu-console', 'Import Packs', 'Import Packs', 'manage_options', 'bossu-import', 'bossu_importer_callback');
add_submenu_page('bossu-console', 'Plugin Uploader', 'Plugin Uploader', 'manage_options', 'bossu-plugins', 'bossu_plugin_uploader_callback');
add_submenu_page('bossu-console', 'Rewards', 'Rewards', 'manage_options', 'bossu-rewards', 'bossu_rewards_callback');
add_submenu_page('bossu-console', 'Risk & Integrity', 'Risk & Integrity', 'manage_options', 'bossu-risk', 'bossu_risk_callback');
add_submenu_page('bossu-console', 'Settings', 'Settings', 'manage_options', 'bossu-settings', 'bossu_settings_callback');
add_submenu_page('bossu-console', 'Reports', 'Reports', 'manage_options', 'bossu-reports', 'bossu_reports_callback');
add_submenu_page('bossu-console', 'Content Enhancer', 'Content Enhancer', 'manage_options', 'bossu-enhancer', 'bossu_enhancer_callback');
add_submenu_page('bossu-console', 'Quiz Manager', 'Quiz Manager', 'manage_options', 'bossu-quizzes', 'bossu_quiz_manager_callback');
add_submenu_page('bossu-console', 'Fraud Review', 'Fraud Review', 'manage_options', 'bossu-fraud', 'bossu_fraud_callback');
add_submenu_page('bossu-console', 'Student Management', 'Student Management', 'manage_options', 'bossu-students', 'bossu_students_callback');
}
add_action('admin_menu', 'bossu_add_console_pages');
function bossu_dashboard_callback() {
?>
<div class="wrap">
<h1>Bossu Console Dashboard</h1>
<canvas id="progressChart" width="400" height="200"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var ctx = document.getElementById('progressChart').getContext('2d');
var chart = new Chart(ctx, { type: 'bar', data: { labels: ['Cohort 1'], datasets: [{ label: 'Progress', data: [75] }] } });
</script>
</div>
<?php
}
function bossu_plugin_uploader_callback() {
if (isset($_FILES['plugin_zip'])) {
$file = $_FILES['plugin_zip']['tmp_name'];
$plugins_dir = WP_PLUGIN_DIR . '/bossu-plugins/';
if (!file_exists($plugins_dir)) mkdir($plugins_dir, 0755, true);
$zip = new ZipArchive();
if ($zip->open($file) === TRUE) {
$zip->extractTo($plugins_dir);
$zip->close();
$plugin_folder = $zip->getNameIndex(0);
$plugin_file = $plugins_dir . $plugin_folder . 'plugin.php';
if (file_exists($plugin_file)) {
activate_plugin($plugin_folder . 'plugin.php');
do_action('bossu_register_skill', $plugin_folder);
echo '<div class="notice notice-success">Plugin uploaded and integrated.</div>';
} else {
echo '<div class="notice notice-error">Invalid plugin structure.</div>';
}
} else {
echo '<div class="notice notice-error">Failed to open zip.</div>';
}
}
?>
<div class="wrap">
<h1>Upload Custom Plugins</h1>
<p>Upload .zip plugins that extend Bossu module.</p>
<form method="post" enctype="multipart/form-data">
<input type="file" name="plugin_zip" accept=".zip">
<input type="submit" value="Upload Plugin">
</form>
</div>
<?php
}
function bossu_rewards_callback() {
global $wpdb;
$rewards = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bossu_rewards");
require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
class Bossu_Rewards_Table extends WP_List_Table {
function get_columns() { return ['id' => 'ID', 'user_id' => 'User', 'status' => 'Status']; }
function prepare_items() { $this->items = $GLOBALS['rewards']; }
}
$table = new Bossu_Rewards_Table();
$table->prepare_items();
$table->display();
// Add form for approve/hold
}
// Full similar callbacks for other subpages: risk (list events), reports (export CSV button: if (isset($_POST['export'])) { header('Content-Type: text/csv'); /* query and echo CSV */ }), enhancer (checkboxes/input for video/chart, submit to update post), quiz (dynamic JS for questions, submit to create QSM quiz), fraud (flagged accounts), students (table with wc_get_orders and llms_get_student_progress)
function bossu_students_callback() {
$students = get_users(['role' => 'bossu_student']);
echo '<table><tr><th>User</th><th>Payments</th><th>Grades</th></tr>';
foreach ($students as $student) {
$payments = wc_get_orders(['customer_id' => $student->ID]);
$total_paid = array_reduce($payments, fn($sum, $o) => $sum + $o->get_total(), 0);
$grades = /* use LifterLMS API llms_get_student_progress($student->ID) or query */ 'N/A';
echo '<tr><td>' . $student->display_name . '</td><td>$' . $total_paid . '</td><td>' . $grades . '</td></tr>';
}
echo '</table>';
}



// Step 3: APIs, Roles, Final Testing, and Modular Finalization (2-3 Hours)

function bossu_register_rest_api() {
register_rest_route('bossu/v2', '/points/(?P<user_id>\d+)', ['methods' => 'GET', 'callback' => 'bossu_get_points', 'permission_callback' => function() { return current_user_can('manage_options'); }]);
register_rest_route('bossu/v2', '/rewards/(?P<user_id>\d+)', ['methods' => 'GET', 'callback' => 'bossu_get_rewards', 'permission_callback' => function() { return current_user_can('manage_options'); }]);
register_rest_route('bossu/v2', '/risk/(?P<user_id>\d+)', ['methods' => 'GET', 'callback' => 'bossu_get_risk', 'permission_callback' => function() { return current_user_can('manage_options'); }]);
register_rest_route('bossu/v2', '/consents/(?P<student_id>\d+)', ['methods' => 'GET', 'callback' => 'bossu_get_consents', 'permission_callback' => function() { return current_user_can('manage_options'); }]);
}
add_action('rest_api_init', 'bossu_register_rest_api');
function bossu_get_points(WP_REST_Request $request) {
global $wpdb;
$user_id = $request['user_id'];
$points = $wpdb->get_var($wpdb->prepare("SELECT SUM(points) FROM {$wpdb->prefix}bossu_points WHERE user_id = %d", $user_id));
return new WP_REST_Response(['total_points' => $points ?? 0], 200);
}
function bossu_get_rewards(WP_REST_Request $request) {
global $wpdb;
$user_id = $request['user_id'];
$rewards = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bossu_rewards WHERE user_id = %d", $user_id));
return new WP_REST_Response($rewards, 200);
}
function bossu_get_risk(WP_REST_Request $request) {
global $wpdb;
$user_id = $request['user_id'];
$risks = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bossu_risk_events WHERE user_id = %d", $user_id));
return new WP_REST_Response($risks, 200);
}
function bossu_get_consents(WP_REST_Request $request) {
global $wpdb;
$student_id = $request['student_id'];
$consents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}bossu_guardian_consents WHERE student_id = %d", $student_id));
return new WP_REST_Response($consents, 200);
}


//////////////////////////////////////////////////
function bossu_add_roles() {
add_role('bossu_student', 'Student', ['read' => true, 'enroll_courses' => true]);
add_role('bossu_guardian', 'Guardian', ['read' => true, 'view_consents' => true]);
add_role('bossu_instructor', 'Instructor', ['edit_posts' => true, 'manage_courses' => true]);
}
register_activation_hook(__FILE__, 'bossu_add_roles');