<?php

namespace antibots_BillDiagnose;
// 2023-08 upd: 2023-10-17 2024-06=21 2024-31-12 2025-Oct 16
if (!defined("ABSPATH")) {
    die("Invalid request.");
}
if (function_exists("is_multisite") and is_multisite()) {
    return;
}
$plugin_file_path = __DIR__ . "/function_time_loading.php";
if (file_exists($plugin_file_path)) {
    include_once $plugin_file_path;
} else {
    error_log("File not found: " . $plugin_file_path);
}
$plugin_file_path = ABSPATH . "wp-admin/includes/plugin.php";
if (file_exists($plugin_file_path)) {
    include_once $plugin_file_path;
}
if (function_exists("is_plugin_active")) {
    $bill_plugins_to_check = ["wptools/wptools.php"];
    foreach ($bill_plugins_to_check as $plugin_path) {
        if (is_plugin_active($plugin_path)) {
            return;
        }
    }
}
require_once __DIR__ . "/class_autocheckup_server.php";
// -- Help
// Função para exibir o ID da tela
function debug_screen_id_current_screen($screen)
{
    if ($screen) {
        error_log("Screen ID: " . $screen->id);
    }
}
//add_action('current_screen', __NAMESPACE__ . '\\debug_screen_id_current_screen');
// Função para adicionar uma aba de ajuda
function add_help_tab_to_screen()
{
    // Verifica se estamos na tela correta
    $screen = get_current_screen();
    // Verifica se o screen é o 'site-health' antes de adicionar a aba
    if ($screen && "site-health" === $screen->id) {
        $hmessage = esc_attr__(
            "This panel provides a comprehensive health status of your WordPress site, covering vital areas. Monitor for errors, low memory, and slow page speed. See real-time checks for database health, server configuration, and critical security issues (bots/attacks). Use the built-in AI support for instant troubleshooting. For deeper diagnosis, install the free WPTools plugin.",
            "antibots"
        );
        // Adiciona a aba de ajuda
        $screen->add_help_tab([
            "id" => "site-health", // ID único para a aba
            "title" => esc_attr__("Memory & Error Monitoring", "antibots"), // Título da aba
            "content" =>
            "<p>" .
                esc_attr__("Welcome to plugin Insights!", "antibots") .
                '</p>
                          <p>' .
                $hmessage .
                "</p>",
        ]);
    }
}
// Adiciona a aba de ajuda quando a tela 'site-health' for carregada
add_action("current_screen", __NAMESPACE__ . "\\add_help_tab_to_screen");
// -----------------------   2025 check server
// ----------------------------------------------------
// ARQUIVO PRINCIPAL DO PLUGIN - INICIALIZAÇÃO DA CLASSE
// ----------------------------------------------------
// 1. Incluir o arquivo da classe (Esta linha permanece no topo)
//require_once plugin_dir_path(__FILE__) . 'class_autocheckup_server.php';
// ADICIONE O USE AQUI, NO ESCOPO GLOBAL, ANTES DE QUALQUER FUNÇÃO
use antibots_BillDiagnose\autocheckup_server;
// 2. A função de enfileiramento DEVE estar no escopo global
//    e é onde o add_action('admin_enqueue_scripts', ...) aponta.
function antibots_enqueue_autocheckup_script($hook)
{
    // Sua lógica para enfileirar e localizar scripts...
    // Altere 'antibots_-autocheckup-js' para o handle do seu script
    wp_enqueue_script(
        "antibots_-autocheckup-js",
        plugin_dir_url(__FILE__) . "js/autocheckup.js",
        ["jquery", "jquery-ui-accordion"],
        "1.0",
        true
    ); // Adicionei jquery-ui-accordion
    // Novo handle (variável JS) e nomes de Nonce/Action ajustados
    wp_localize_script("antibots_-autocheckup-js", "DatabaseBackupAjaxParams", [
        "ajaxurl" => admin_url("admin-ajax.php"),
        "nonce" => wp_create_nonce("antibots_autocheck_nonce"), // Nonce ajustado
        "action" => "antibots_start_autocheckup", // Ação AJAX ajustada
    ]);
}
// O add_action para esta função será movido para dentro da função de setup.
/**
 * Função principal de SETUP: Instancia a classe e registra todos os hooks.
 * Esta função é chamada no hook 'plugins_loaded'.
 */
function antibots_setup_autocheckup()
{
    // Deve ser chamada APÓS o require_once
    //  use antibots_BillDiagnose\autocheckup_server;
    // Instanciar a classe
    $antibots_autocheckup_instance = new autocheckup_server();
    // Chamar o registro dos hooks (Exemplo: no hook 'admin_init' se for só para o Admin)
    add_action("admin_init", [
        $antibots_autocheckup_instance,
        "register_ajax_hooks",
    ]);
    // Registro da função de enfileiramento (agora separada)
    add_action("admin_enqueue_scripts", "antibots_enqueue_autocheckup_script");
}
// O WordPress garante que esta função será executada em um momento seguro.
add_action("plugins_loaded", "antibots_setup_autocheckup");
// -------------------   end 2025 check server


class misc_checker
{
    private $misc_data;

    public function __construct()
    {
        $this->misc_data = $this->collect_system_data();
    }

    public function collect_system_data()
    {
        $misc_checker = array();

        // a) Test if debug and display errors are true
        $misc_checker['debug_status'] = array(
            'wp_debug' => defined('WP_DEBUG') ? (WP_DEBUG ? 'Enabled' : 'Disabled') : 'Not Set',
            'display_errors' => ini_get('display_errors') ? 'On (' . ini_get('display_errors') . ')' : 'N/A',
            'script_debug' => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'Yes' : 'No',
            'wp_debug_display' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'Yes' : 'No'
        );

        // b) Check if ImageMagick is available
        $misc_checker['imageck_available'] = extension_loaded('imagick') ? 'Installed' : 'Not Installed';

        // c) PHP Version
        $misc_checker['php_version'] = PHP_VERSION;

        // d) OS Information
        //$misc_checker['os_info'] = $this->antibots_OSName();

        // e) Time Limit
        $misc_checker['time_limit'] = ini_get('max_execution_time');

        // f) Cache Status
        $misc_checker['cache_status'] = array(
            'opcache' => array(
                'installed' => (extension_loaded('opcache') || extension_loaded('Zend OPcache')) ? 'Yes' : 'No',
                'active' => 'No'
            ),
            'apcu' => array(
                'installed' => extension_loaded('apcu') ? 'Yes' : 'No',
                'status' => 'Not Available'
            ),
            'redis' => array(
                'installed' => extension_loaded('redis') ? 'Yes' : 'No'
            ),
            'memcached' => array(
                'installed' => (extension_loaded('memcached') || extension_loaded('memcache')) ? 'Yes' : 'No'
            )
        );

        // Additional OPcache details
        if ($misc_checker['cache_status']['opcache']['installed'] === 'Yes' && function_exists('opcache_get_status')) {
            $status = @opcache_get_status(false);
            $misc_checker['cache_status']['opcache']['active'] = ($status && $status['opcache_enabled']) ? 'Yes' : 'No';
        }

        // Additional APCu details
        if ($misc_checker['cache_status']['apcu']['installed'] === 'Yes') {
            $object_cache_file_exists = defined('WP_CONTENT_DIR') ? file_exists(WP_CONTENT_DIR . '/object-cache.php') : false;
            $misc_checker['cache_status']['apcu']['wp_dropin_file'] = $object_cache_file_exists ? 'Found' : 'Missing';
            $misc_checker['cache_status']['apcu']['status'] = $object_cache_file_exists ? 'READY FOR USE' : 'MISSING DROP-IN';
        }

        return $misc_checker;
    }

    /**
     * Analyzes the data collected by collect_system_data() and generates an HTML report.
     *
     * @return string The generated HTML output of the analysis.
     */
    public function antibots_analyze_misc_checker()
    {
        $misc_data = $this->misc_data;

        $output = '';

        // a) Debug Status Analysis
        $output .= "<h3>" . esc_html__('Debug and Error Display Analysis', 'antibots') . "</h3>\n";

        if ($misc_data['debug_status']['wp_debug'] === 'Enabled') {
            $output .= "<div class='antibots-warning'>⚠️ <strong>" . esc_html__('WP_DEBUG is Enabled', 'antibots') . "</strong><br>\n";
            $output .= esc_html__("This exposes sensitive information to users and should only be used in development environments.", 'antibots') . "<br>\n";
            $output .= esc_html__("Disable WP_DEBUG on production sites to prevent information disclosure.", 'antibots') . "</div>\n";
        }

        if (strpos($misc_data['debug_status']['display_errors'], 'On') === 0) {
            $output .= "<div class='antibots-warning'>⚠️ <strong>" . esc_html__('Display Errors is On', 'antibots') . "</strong><br>\n";
            $output .= esc_html__("Showing PHP errors on screen can reveal sensitive path information and system details.", 'antibots') . "<br>\n";
            $output .= esc_html__("Set display_errors to Off in production and use error logging instead.", 'antibots') . "</div>\n";
        }

        if ($misc_data['debug_status']['wp_debug'] === 'Disabled' && strpos($misc_data['debug_status']['display_errors'], 'N/A') !== false) {
            $output .= "<div class='antibots-success'>✓ <strong>" . esc_html__('Debug Settings: Good', 'antibots') . "</strong><br>\n";
            $output .= esc_html__("WP_DEBUG is disabled and errors are not displayed publicly.", 'antibots') . "</div>\n";
        }

        // b) ImageMagick Analysis
        $output .= "<h3>" . esc_html__('Image Processing Analysis', 'antibots') . "</h3>\n";

        if ($misc_data['imageck_available'] === 'Installed') {
            $output .= "<div class='antibots-success'>✓ <strong>" . esc_html__('ImageMagick Available', 'antibots') . "</strong><br>\n";
            $output .= esc_html__("ImageMagick extension is installed, which provides better image processing capabilities.", 'antibots') . "<br>\n";
            $output .= esc_html__("This improves image quality and performance for media operations.", 'antibots') . "</div>\n";
        } else {
            $output .= "<div class='antibots-info'>ℹ️ <strong>" . esc_html__('ImageMagick Not Available', 'antibots') . "</strong><br>\n";
            $output .= esc_html__("Consider installing ImageMagick for improved image processing performance.", 'antibots') . "<br>\n";
            $output .= esc_html__("WordPress will use GD library as fallback for image operations.", 'antibots') . "</div>\n";
        }

        // c) PHP Version Analysis
        $output .= "<h3>" . esc_html__('PHP Version Analysis', 'antibots') . "</h3>\n";

        $php_version = $misc_data['php_version'];
        if (version_compare($php_version, '8.2', '>=')) {
            $output .= "<div class='antibots-success'>✓ <strong>" . sprintf(esc_html__('PHP Version: %s (Current)', 'antibots'), $php_version) . "</strong><br>\n";
            $output .= esc_html__("You are running a current PHP version with active security support.", 'antibots') . "<br>\n";
            $output .= esc_html__("This provides better performance and security features.", 'antibots') . "</div>\n";
        } elseif (version_compare($php_version, '8.0', '>=')) {
            $output .= "<div class='antibots-warning'>⚠️ <strong>" . sprintf(esc_html__('PHP Version: %s (Update Recommended)', 'antibots'), $php_version) . "</strong><br>\n";
            $output .= esc_html__("Your PHP version is acceptable but not the latest.", 'antibots') . "<br>\n";
            $output .= esc_html__("Consider updating to PHP 8.2+ for better performance and security.", 'antibots') . "</div>\n";
        } else {
            $output .= "<div class='antibots-critical'>🚨 <strong>" . sprintf(esc_html__('PHP Version: %s (Outdated)', 'antibots'), $php_version) . "</strong><br>\n";
            $output .= esc_html__("You are running an outdated PHP version that may have security vulnerabilities.", 'antibots') . "<br>\n";
            $output .= esc_html__("Update to PHP 8.2+ immediately for security and performance improvements.", 'antibots') . "</div>\n";
        }

        // e) Time Limit Analysis
        $output .= "<h3>" . esc_html__('Execution Time Analysis', 'antibots') . "</h3>\n";

        $time_limit = $misc_data['time_limit'];
        if ($time_limit >= 300) {
            $output .= "<div class='antibots-success'>✓ <strong>" . sprintf(esc_html__('Time Limit: %s seconds', 'antibots'), $time_limit) . "</strong><br>\n";
            $output .= esc_html__("Adequate execution time for most WordPress operations.", 'antibots') . "<br>\n";
            $output .= esc_html__("This allows plugins and themes to complete complex tasks.", 'antibots') . "</div>\n";
        } elseif ($time_limit >= 120) {
            $output .= "<div class='antibots-info'>ℹ️ <strong>" . sprintf(esc_html__('Time Limit: %s seconds', 'antibots'), $time_limit) . "</strong><br>\n";
            $output .= esc_html__("Moderate execution time - sufficient for standard operations.", 'antibots') . "<br>\n";
            $output .= esc_html__("Monitor for timeout issues with resource-intensive plugins.", 'antibots') . "</div>\n";
        } else {
            $output .= "<div class='antibots-warning'>⚠️ <strong>" . sprintf(esc_html__('Time Limit: %s seconds', 'antibots'), $time_limit) . "</strong><br>\n";
            $output .= esc_html__("Low execution time limit may cause timeout issues.", 'antibots') . "<br>\n";
            $output .= esc_html__("Consider increasing max_execution_time for better performance.", 'antibots') . "</div>\n";
        }

        // f) Cache Status Analysis
        $output .= "<h3>" . esc_html__('Cache Status Analysis', 'antibots') . "</h3>\n";

        // OPcache Analysis
        if ($misc_data['cache_status']['opcache']['installed'] === 'Yes') {
            if ($misc_data['cache_status']['opcache']['active'] === 'Yes') {
                $output .= "<div class='antibots-success'>✓ <strong>" . esc_html__('OPcache: Active', 'antibots') . "</strong><br>\n";
                $output .= esc_html__("OPcache is properly configured and improving PHP performance.", 'antibots') . "<br>\n";
                $output .= esc_html__("This significantly speeds up PHP execution by caching compiled scripts.", 'antibots') . "</div>\n";
            } else {
                $output .= "<div class='antibots-warning'>⚠️ <strong>" . esc_html__('OPcache: Installed but Inactive', 'antibots') . "</strong><br>\n";
                $output .= "<div class='antibots-warning-detail'>" . esc_html__("OPcache is installed but not active in your PHP configuration.", 'antibots') . "</div>\n";
                $output .= esc_html__("Enable it in php.ini to improve WordPress performance.", 'antibots') . "</div>\n";
            }
        } else {
            $output .= "<div class='antibots-critical'>🚨 <strong>" . esc_html__('OPcache: Not Installed', 'antibots') . "</strong><br>\n";
            $output .= esc_html__("OPcache is missing, which significantly impacts PHP performance.", 'antibots') . "<br>\n";
            $output .= esc_html__("Install and enable Zend OPcache for major performance improvements.", 'antibots') . "</div>\n";
        }

        // Object Cache Analysis
        if ($misc_data['cache_status']['apcu']['installed'] === 'Yes') {
            if ($misc_data['cache_status']['apcu']['status'] === 'READY FOR USE') {
                $output .= "<div class='antibots-success'>✓ <strong>" . esc_html__('APCu: Ready', 'antibots') . "</strong><br>\n";
                $output .= esc_html__("APCu is installed and properly configured with WordPress drop-in.", 'antibots') . "<br>\n";
                $output .= esc_html__("This improves database performance by caching query results.", 'antibots') . "</div>\n";
            } else {
                $output .= "<div class='antibots-warning'>⚠️ <strong>" . esc_html__('APCu: Missing Drop-in', 'antibots') . "</strong><br>\n";
                $output .= esc_html__("APCu is installed but missing the WordPress object-cache.php file.", 'antibots') . "<br>\n";
                $output .= esc_html__("Install the object cache drop-in to enable database caching.", 'antibots') . "</div>\n";
            }
        } else {
            $output .= "<div class='antibots-info'>ℹ️ <strong>" . esc_html__('Object Cache: Not Available', 'antibots') . "</strong><br>\n";
            $output .= esc_html__("No persistent object cache detected (APCu, Redis, Memcached).", 'antibots') . "<br>\n";
            $output .= esc_html__("Consider installing APCu for improved database performance.", 'antibots') . "</div>\n";
        }

        return $output;
    }

    public function has_negative_issues()
    {
        // CORREÇÃO: Usar $this->misc_data em vez de chamar collect_system_data() novamente
        $data = $this->misc_data;
        $issues = array();

        // 1. Check Debug Issues
        if ($data['debug_status']['wp_debug'] === 'Enabled') {
            $issues[] = 'wp_debug_enabled';
        }

        if (strpos($data['debug_status']['display_errors'], 'On') === 0) {
            $issues[] = 'display_errors_on';
        }

        // 2. Check PHP Version
        if (version_compare($data['php_version'], '8.0', '<')) {
            $issues[] = 'php_version_outdated';
        }

        // 3. Check Time Limit (too low)
        if ($data['time_limit'] < 60) {
            $issues[] = 'low_time_limit';
        }

        // 4. Check OPcache (not installed or inactive)
        if ($data['cache_status']['opcache']['installed'] === 'No') {
            $issues[] = 'opcache_missing';
        } elseif ($data['cache_status']['opcache']['active'] === 'No') {
            $issues[] = 'opcache_inactive';
        }

        // 5. Check ImageMagick (optional - not critical but nice to have)
        if ($data['imageck_available'] === 'Not Installed') {
            $issues[] = 'imagick_missing';
        }

        return !empty($issues) ? $issues : false;
    }

    // Método para forçar atualização dos dados se necessário
    public function refresh_data()
    {
        $this->misc_data = $this->collect_system_data();
        return $this;
    }
}

class ErrorChecker
{
    public function __construct()
    {
        // Chama a função de enfileiramento de scripts automaticamente ao carregar a classe
        // add_action('admin_enqueue_scripts', array($this, 'enqueue_diagnose_scripts'));
    }
    public function limparString($string)
    {
        return preg_replace("/[[:^print:]]/", "", $string);
    }
    public function bill_parseDate_old_mexida($dateString, $locale)
    {
        if (isset($dateString) && !empty($dateString)) {
            $dateString = trim($dateString); // Remover espaços extras
            $dateString = ErrorChecker::limparString($dateString); // Remover caracteres invisíveis
        } else {
            return false;
        }
        // Mapeamento de formatos de data por idioma
        $dateFormatsByLanguage = [
            "pt" => "d/m/Y", // 31/12/2024 (Português)
            "en" => "m/d/Y", // 12/31/2024 (Inglês)
            "fr" => "d/m/Y", // 31/12/2024 (Francês)
            "de" => "d.m.Y", // 31.12.2024 (Alemão)
            "es" => "d/m/Y", // 31/12/2024 (Espanhol)
            "nl" => "d-m-Y", // 31-12-2024 (Holandês)
        ];
        // Extrai o código de idioma do locale (ex: 'pt_BR' -> 'pt')
        $language = substr($locale, 0, 2);
        // debug4($language);
        // Obtém o formato de data correspondente ao idioma
        $format = $dateFormatsByLanguage[$language] ?? "Y-m-d"; // Fallback para um formato padrão
        // Tenta criar o DateTime com o formato correspondente
        // debug4($format);
        $date = \DateTime::createFromFormat($format, $dateString);
        // debug4($date);
        if ($date !== false) {
            return $date;
        }
        // Se o formato específico do idioma falhar, tenta detectar o formato automaticamente
        $possibleFormats = [
            "d/m/Y", // 31/12/2024
            "m/d/Y", // 12/31/2024
            "Y-m-d", // 2024-12-31
            "d-M-Y", // 31-Dec-2024
            "d F Y", // 31 December 2024
            "d.m.Y", // 31.12.2024 (Alemão)
            "d-m-Y", // 31-12-2024 (Holandês)
        ];
        // debug4($locale);
        foreach ($possibleFormats as $format) {
            $timestamp = strtotime($dateString);
            if ($timestamp !== false) {
                return true;
            }
            /*
            $date = \DateTime::createFromFormat($format, $dateString);
            // debug4($date);
            // debug4($format);
            if ($date !== false) {
                // debug4($date);
                return $date;
            }
            */
        }
        // Se nenhum formato funcionar, lança uma exceção
        // throw new \Exception("Falha ao parsear a data: " . $dateString);
        // debug4('Falhou !!!');
        return false;
    }
    /* Transform data em objeto DateTime */
    // \DateTime::__set_state(array( 'date' => '2025-02-23 17:51:41.920019', 'timezone_type' => 3, 'timezone' => 'UTC', ))
    public function bill_parseDate($dateString, $locale)
    {
        if (isset($dateString) && !empty($dateString)) {
            $dateString = trim($dateString);
            $dateString = ErrorChecker::limparString($dateString);
        } else {
            // debug4("Data vazia ou inválida");
            return false;
        }
        // Formatos possíveis em inglês
        $possibleFormats = [
            "d/m/Y", // 31/12/2024
            "m/d/Y", // 12/31/2024
            "Y-m-d", // 2024-12-31
            "d-M-Y", // 31-Dec-2024
            "d F Y", // 31 December 2024
            "d.m.Y", // 31.12.2024
            "d-m-Y", // 31-12-2024
        ];
        foreach ($possibleFormats as $format) {
            $date = \DateTime::createFromFormat($format, $dateString);
            // debug4("Testando formato: $format");
            if ($date !== false) {
                // debug4("Data reconhecida: " . $date->format('Y-m-d'));
                return $date;
            }
        }
        // Fallback com strtotime para formatos em inglês não listados
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            $date = new DateTime();
            $date->setTimestamp($timestamp);
            // debug4("Data reconhecida via strtotime: " . $date->format('Y-m-d'));
            return $date;
        }
        // debug4("Falhou ao parsear a data: $dateString");
        return false;
    }
    public function enqueue_diagnose_scripts()
    {
        wp_enqueue_script("jquery-ui-accordion"); // Enfileira o jQuery UI Accordion
        wp_enqueue_script(
            "diagnose-script",
            plugin_dir_url(__FILE__) . "diagnose.js",
            ["jquery", "jquery-ui-accordion"],
            "",
            true
        );
        wp_enqueue_style(
            "site-monitor-style", // 1. Handle (nome único)
            plugin_dir_url(__FILE__) . "site-monitor-admin.css", // 2. URL para o arquivo CSS
            [], // 3. Dependências (nenhuma necessária para este CSS)
            "1.0" // 4. Versão (para controle de cache)
        );
        add_action("admin_enqueue_scripts", function () {
            wp_enqueue_style("dashicons");
        });
        /*
        add_action('wp_enqueue_scripts', function () {
            wp_enqueue_style('dashicons');
        });
        */
    }
    /**
     * Retrieves an array of paths to potential error log files.
     *
     * This function searches for common locations where error logs might be stored,
     * including PHP error logs, WordPress root directory, plugin and theme directories,
     * and the administration area.
     *
     * @return array An array of strings, where each string is a potential path to an error log file.
     */
    public static function get_path_logs()
    {
        $bill_folders = [];
        $bill_folders[] = trailingslashit(ABSPATH) . "error_log";
        $error_log_path = ini_get("error_log");
        if (!empty($error_log_path)) {
            $error_log_path = trim($error_log_path);
        } else {
            if (defined("WP_DEBUG") && WP_DEBUG) {
                $error_log_path = trailingslashit(WP_CONTENT_DIR) . "debug.log";
            } else {
                $error_log_path = trailingslashit(ABSPATH) . "error_log";
            }
        }
        $bill_folders[] = $error_log_path;
        // Logs in WordPress root directory
        //
        $bill_folders[] = WP_CONTENT_DIR . "/debug.log";
        // Logs in current plugin directory
        $bill_folders[] = plugin_dir_path(__FILE__) . "error_log";
        $bill_folders[] = plugin_dir_path(__FILE__) . "php_errorlog";
        // Logs in current theme directory
        $bill_folders[] = get_theme_root() . "/error_log";
        $bill_folders[] = get_theme_root() . "/php_errorlog";
        // Logs in administration area (if it exists)
        $bill_admin_path = str_replace(
            get_bloginfo("url") . "/",
            ABSPATH,
            get_admin_url()
        );
        $bill_folders[] = $bill_admin_path . "/error_log";
        $bill_folders[] = $bill_admin_path . "/php_errorlog";
        // Logs in plugin subdirectories
        try {
            $bill_plugins = array_slice(scandir(plugin_dir_path(__FILE__)), 2);
            foreach ($bill_plugins as $bill_plugin) {
                $plugin_path = plugin_dir_path(__FILE__) . $bill_plugin;
                if (is_dir($plugin_path)) {
                    $bill_folders[] = $plugin_path . "/error_log";
                    $bill_folders[] = $plugin_path . "/php_errorlog";
                }
            }
        } catch (Exception $e) {
            // Handle the exception
            error_log("Error scanning plugins directory: " . $e->getMessage());
        }
        // Logs in theme subdirectories
        /*
        $bill_themes = array_slice(scandir(get_theme_root()), 2);
        foreach ($bill_themes as $bill_theme) {
            $theme_path = get_theme_root() . "/" . $bill_theme;
            if (is_dir($theme_path)) {
                $bill_folders[] = $theme_path . "/error_log";
                $bill_folders[] = $theme_path . "/php_errorlog";
            }
        }
        */
        try {
            $bill_themes = array_slice(scandir(get_theme_root()), 2);
            foreach ($bill_themes as $bill_theme) {
                if (is_dir(get_theme_root() . "/" . $bill_theme)) {
                    $bill_folders[] =
                        get_theme_root() . "/" . $bill_theme . "/error_log";
                    $bill_folders[] =
                        get_theme_root() . "/" . $bill_theme . "/php_errorlog";
                }
            }
        } catch (Exception $e) {
            // Handle the exception
            error_log("Error scanning theme directory: " . $e->getMessage());
        }
        return array_unique($bill_folders);
        //return $bill_folders;
    }
    public function bill_check_errors_today($num_days, $filter = null)
    {
        // return true;
        $bill_count = 0;
        // $bill_folders = get_path_logs();
        $bill_folders = ErrorChecker::get_path_logs();
        // var_dump($bill_folders);
        // Data limite para comparação
        //$dateThreshold = new DateTime('now');
        $dateThreshold = new \DateTime("now");
        // $dateThreshold->modify('-3 days');
        $dateThreshold->modify("-{$num_days} days");
        // $dateThreshold->modify("-$num_days days");
        // Regex para identificar diferentes formatos de data
        $datePatterns = [
            "/\d{2}-[a-zA-ZÀ-ÿ]{3}-\d{4}/", // DD-Mon-YYYY (ex: 31-Dec-2024)
            "/\d{2}\s+[a-zA-ZÀ-ÿ]+\s+\d{4}/", // DD Month YYYY (ex: 31 December 2024)
            "/\d{4}-\d{2}-\d{2}/", // YYYY-MM-DD (ex: 2024-12-31)
            "/\d{2}\/\d{2}\/\d{4}/", // DD/MM/YYYY (ex: 31/12/2024)
            "/\d{2}-\d{2}-\d{4}/", // DD-MM-YYYY (ex: 31-12-2024)
            "/\d{2}\.\d{2}\.\d{4}/", // DD.MM.YYYY (ex: 31.12.2024)
            "/\d{4}\/\d{2}\/\d{2}/", // YYYY/MM/DD (ex: 2024/12/31)
        ];
        // Obtém o locale do WordPress
        $locale = get_locale(); // Exemplo: 'pt_BR', 'en_US', etc.
        $language = substr($locale, 0, 2); // Extrai o código de idioma (ex: 'pt', 'en')
        // Itera sobre as pastas
        //// debug4($bill_folders);
        foreach ($bill_folders as $bill_folder) {
            if (
                !empty($bill_folder) &&
                file_exists($bill_folder) &&
                filesize($bill_folder) > 0
            ) {
                // debug4($bill_folder);
                $bill_count++;
                $marray = $this->bill_read_file($bill_folder, 20);
                if (is_array($marray) && !empty($marray)) {
                    // debug4($marray);
                    foreach ($marray as $line) {
                        if (empty($line)) {
                            // // debug4();
                            continue;
                        }
                        if (
                            $filter !== null &&
                            stripos($line, $filter) === false
                        ) {
                            // // debug4();
                            continue;
                        }
                        if (substr($line, 0, 1) !== "[") {
                            // // debug4();
                            continue;
                        }
                        // Verifica se a linha corresponde a algum padrão de data
                        foreach ($datePatterns as $pattern) {
                            if (preg_match($pattern, $line, $matches)) {
                                try {
                                    // Usa a função parseDate para interpretar a data
                                    // debug4($matches[0]);
                                    // debug4($locale);
                                    $date = $this->bill_parseDate(
                                        $matches[0],
                                        $locale
                                    );
                                    //die(var_export($date));
                                    // \DateTime::__set_state(array( 'date' => '2025-02-26 17:48:55.000000', 'timezone_type' => 3, 'timezone' => 'UTC', ))
                                    // die(var_export($dateThreshold));
                                    // \DateTime::__set_state(array( 'date' => '2025-02-23 17:51:41.920019', 'timezone_type' => 3, 'timezone' => 'UTC', ))
                                    // debug4($date);
                                    if (!$date) {
                                        // // debug4();
                                        continue;
                                    }
                                    if (!$date instanceof \DateTime) {
                                        // // debug4();
                                        continue;
                                    }
                                    // Verifica se a data é anterior ao limite
                                    // // debug4($date);
                                    // // debug4($dateThreshold);
                                    if ($date < $dateThreshold) {
                                        // debug2('Antiga');
                                        // debug4("Data antiga encontrada: " . $date->format('Y-m-d'));
                                    } else {
                                        // debug4('Data Nova encontrada');
                                        return true;
                                    }
                                } catch (Exception $e) {
                                    // Ignorar linhas com datas inválidas
                                    // debug4("Erro ao processar a data: " . $e->getMessage());
                                    continue;
                                }
                            } else {
                                // // debug4('nao bateu');
                            }
                        }
                        // debug4('False ??');
                        return false;
                    }
                }
            }
        }
        return false;
    }
    public function bill_read_file($file, $lines)
    {
        // Check if the file exists and is readable
        //debug2($file);
        //debug2($lines);
        clearstatcache(true, $file); // Clear cache to ensure current file state
        if (!file_exists($file) || !is_readable($file)) {
            return []; // Return empty array in case of error
        }
        $text = [];
        // Fallback to original method with fopen
        $handle = fopen($file, "r");
        if (!$handle) {
            return [];
        }
        $bufferSize = 8192; // 8KB
        $currentChunk = "";
        $linecounter = 0;
        fseek($handle, 0, SEEK_END);
        $filesize = ftell($handle);
        if ($filesize < $bufferSize) {
            $bufferSize = $filesize;
        }
        if ($bufferSize < 1) {
            fclose($handle);
            return [];
        }
        $pos = $filesize - $bufferSize;
        while ($pos >= 0 && $linecounter < $lines) {
            if ($pos < 0) {
                $pos = 0;
            }
            fseek($handle, $pos);
            $chunk = fread($handle, $bufferSize);
            if ($chunk === false && file_exists($file)) {
                usleep(500000); // Wait 0.5 seconds if reading fails
                $chunk = fread($handle, $bufferSize); // Retry reading the chunk
            }
            $currentChunk = $chunk . $currentChunk;
            $linesInChunk = explode("\n", $currentChunk);
            $currentChunk = array_shift($linesInChunk);
            foreach (array_reverse($linesInChunk) as $line) {
                $text[] = $line;
                $linecounter++;
                if ($linecounter >= $lines) {
                    break 2;
                }
            }
            $pos -= $bufferSize;
        }
        if (!empty($currentChunk)) {
            $text[] = $currentChunk;
        }
        fclose($handle);
        return $text;
    }
} // end class error checker
class MemoryChecker
{
    public function check_memory()
    {
        try {
            // Check if ini_get function exists
            if (!function_exists("ini_get")) {
                $wpmemory["msg_type"] = "notok";
                return $wpmemory;
            } else {
                // Get the PHP memory limit
                $wpmemory["limit"] = (int) ini_get("memory_limit");
            }
            // Check if the memory limit is numeric
            if (!is_numeric($wpmemory["limit"])) {
                $wpmemory["msg_type"] = "notok";
                return $wpmemory;
            }
            // Convert the memory limit from bytes to megabytes if it is excessively high
            if ($wpmemory["limit"] > 9999999) {
                $wpmemory["limit"] = $wpmemory["limit"] / 1024 / 1024;
            }
            // Check if memory_get_usage function exists
            if (!function_exists("memory_get_usage")) {
                $wpmemory["msg_type"] = "notok";
                return $wpmemory;
            } else {
                // Get the current memory usage
                $wpmemory["usage"] = memory_get_usage();
            }
            // Check if the memory usage is valid
            if ($wpmemory["usage"] < 1) {
                $wpmemory["msg_type"] = "notok";
                return $wpmemory;
            } else {
                // Convert the memory usage to megabytes
                $wpmemory["usage"] = round($wpmemory["usage"] / 1024 / 1024, 0);
            }
            // Check if the usage value is numeric
            if (!is_numeric($wpmemory["usage"])) {
                $wpmemory["msg_type"] = "notok";
                return $wpmemory;
            }
            // Check if wpmemory_LIMIT is defined
            if (!defined("WP_MEMORY_LIMIT")) {
                $wpmemory["wp_limit"] = 40; // Default value of 40M
            } else {
                $wpmemory_limit = WP_MEMORY_LIMIT;
                $wpmemory["wp_limit"] = (int) $wpmemory_limit;
            }
            // Calculate the percentage of memory usage
            $wpmemory["percent"] = $wpmemory["usage"] / $wpmemory["wp_limit"];
            $wpmemory["color"] = "font-weight:normal;";
            if ($wpmemory["percent"] > 0.7) {
                $wpmemory["color"] = "font-weight:bold;color:#E66F00";
            }
            if ($wpmemory["percent"] > 0.85) {
                $wpmemory["color"] = "font-weight:bold;color:red";
            }
            // Calculate the available free memory
            $wpmemory["free"] = $wpmemory["wp_limit"] - $wpmemory["usage"];
            $wpmemory["msg_type"] = "ok";
        } catch (Exception $e) {
            $wpmemory["msg_type"] = "notok";
            return $wpmemory;
        }
        return $wpmemory;
    }
}
class antibots_Bill_Diagnose
{
    protected $global_plugin_slug;
    private static $instance = null;
    private $notification_url;
    private $notification_url2;
    private $global_variable_has_errors;
    private $global_variable_memory;
    protected $wpdb; // Declarar a propriedade aqui
    public function __construct($notification_url, $notification_url2)
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        add_action("admin_enqueue_scripts", [
            $this,
            "enqueue_diagnose_scripts",
        ]);
        $this->setNotificationUrl($notification_url);
        $this->setNotificationUrl2($notification_url2);
        //$this->global_variable_has_errors = $this->bill_check_errors_today();
        $errorChecker = new ErrorChecker(); //
        //
        $this->global_variable_has_errors = $errorChecker->bill_check_errors_today(
            3
        );
        // checar page load;
        $average = $this->site_health_check_page_load();
        if ($average > 3) {
            //die(var_dump($average));
            $this->global_variable_has_errors = true;
        }
        // NOT same class
        $memoryChecker = new MemoryChecker();
        $this->global_variable_memory = $memoryChecker->check_memory();
        $this->global_plugin_slug = $this->get_plugin_slug();
        // Adicionando as ações dentro do construtor
        //add_action("admin_notices", [$this, "show_dismissible_notification"]);
        //add_action("admin_notices", [$this, "show_dismissible_notification2"]);
        // 2024
        // // debug4($this->global_variable_has_errors);
        //var_dump($this->global_variable_has_errors);
        //die(var_export(__LINE__));





        /*
        if (current_user_can("manage_options")) {
            add_action(
                "admin_bar_menu",
                [$this, "add_site_health_link_to_admin_toolbar"],
                999
            );
        }
        */


        // No construtor, substitua tudo a partir da linha ~65:

        $memory = $this->global_variable_memory;

        // Verifica SE HÁ ALGUM PROBLEMA: memória OU erros recentes OU carga lenta
        $has_critical_issues = false;

        // 1. Verifica problemas de memória (se os dados estão disponíveis)
        if (!is_null($memory)) {
            //if ($memory["free"] < 30 || $memory["percent"] > 0.85) {
            if ($memory["free"] < 30 || $memory["percent"] > 0.85 || $memory["wp_limit"] > 256) {
                $has_critical_issues = true;
            }
        }

        // 2. Verifica erros recentes no error log (até 3 dias)
        if ($this->global_variable_has_errors) {
            $has_critical_issues = true;
        }

        // 3. Verifica carga lenta de páginas (média > 3 segundos)
        $average_load = $this->site_health_check_page_load();
        //debug4($average_load);

        if ($average_load > 3) {
            $has_critical_issues = true;
            $this->global_variable_has_errors = true; // Já está sendo feito, mas mantém por consistência
        }


        // 4. VERIFICA UPDATES PENDENTES DE PLUGINS E TEMAS
        $has_updates = $this->has_updates_available(); // Método simplificado


        if ($has_updates) {
            $has_critical_issues = true;
            $critical_issues_list[] = 'updates';
        }

        // miscellaneous
        $checker = new misc_checker();
        $issues = $checker->has_negative_issues();
        if ($issues) {
            //$critical_issues_list[] = 'miscellaneous';
            $has_critical_issues = true;
        }

        // SE HOUVER PROBLEMAS CRÍTICOS, adiciona os elementos na interface
        if ($has_critical_issues) {

            // Aba "Critical Issues" na saúde do site
            add_filter("site_health_navigation_tabs", [
                $this,
                "site_health_navigation_tabs",
            ]);
            add_action("site_health_tab_content", [
                $this,
                "site_health_tab_content",
            ]);

            // Menu na admin bar só para administradores quando há problemas
            if (current_user_can("manage_options")) {
                add_action(
                    "admin_bar_menu",
                    [$this, "add_site_health_link_to_admin_toolbar"],
                    999
                );
            }
            add_action("admin_head", [$this, "custom_help_tab"]);
        }



        $memory = $this->global_variable_memory;
        if (is_null($memory)) {
            return;
        }
        if (
            $memory["free"] < 30 or
            $memory["percent"] > 0.85 or
            $this->global_variable_has_errors
        ) {
            add_filter("site_health_navigation_tabs", [
                $this,
                "site_health_navigation_tabs",
            ]);
            add_action("site_health_tab_content", [
                $this,
                "site_health_tab_content",
            ]);
        }
    }
    //public function enqueue_diagnose_scripts()
    public function enqueue_diagnose_scripts($hook_suffix)
    {
        // ⚠️ PASSO CRÍTICO: Apenas carrega scripts se o slug for 'site-health.php'.
        // O $hook_suffix identifica a página Admin atual.
        if ("site-health.php" !== $hook_suffix) {
            // return;
        }
        wp_enqueue_script("jquery-ui-accordion"); // Enfileira o jQuery UI Accordion
        wp_enqueue_script(
            "diagnose-script",
            plugin_dir_url(__FILE__) . "diagnose.js",
            ["jquery", "jquery-ui-accordion"],
            "",
            true
        );
        wp_enqueue_style(
            "site-monitor-style", // 1. Handle (nome único)
            plugin_dir_url(__FILE__) . "site-monitor-admin.css", // 2. URL para o arquivo CSS
            [], // 3. Dependências (nenhuma necessária para este CSS)
            "1.0" // 4. Versão (para controle de cache)
        );
        add_action("admin_enqueue_scripts", function () {
            wp_enqueue_style("dashicons");
        });
        /*
        add_action('wp_enqueue_scripts', function () {
            wp_enqueue_style('dashicons');
        });
        */
    }
    public function get_plugin_slug()
    {
        // Get the plugin directory path
        $plugin_dir = plugin_dir_path(__FILE__);
        // Function to get the base directory of the plugin
        function get_base_plugin_dir($dir, $base_dir)
        {
            // Remove the base directory part from the full path
            $relative_path = str_replace($base_dir, "", $dir);
            // Get the first directory in the relative path
            $parts = explode("/", trim($relative_path, "/"));
            return $parts[0];
        }
        // Check if the plugin is in the normal plugins directory
        if (strpos($plugin_dir, WP_PLUGIN_DIR) === 0) {
            $plugin_slug = get_base_plugin_dir($plugin_dir, WP_PLUGIN_DIR);
        }
        // Check if the plugin is in the mu-plugins directory
        elseif (
            defined("WPMU_PLUGIN_DIR") &&
            strpos($plugin_dir, WPMU_PLUGIN_DIR) === 0
        ) {
            $plugin_slug = get_base_plugin_dir($plugin_dir, WPMU_PLUGIN_DIR);
        } else {
            // If the plugin is not in any expected directory, return an empty string
            return "";
        }
        return $plugin_slug;
    }
    public function setNotificationUrl($notification_url)
    {
        $this->notification_url = $notification_url;
    }
    public function setNotificationUrl2($notification_url2)
    {
        $this->notification_url2 = $notification_url2;
    }
    public function setPluginTextDomain($plugin_text_domain)
    {
        $this->plugin_text_domain = $plugin_text_domain;
    }
    public function setPluginSlug($plugin_slug)
    {
        $this->plugin_slug = $this->get_plugin_slug();
    }
    public static function get_instance($notification_url, $notification_url2)
    {
        if (self::$instance === null) {
            self::$instance = new self($notification_url, $notification_url2);
        }
        return self::$instance;
    }
    //
    public function show_dismissible_notification()
    {
        return;
        if ($this->is_notification_displayed_today()) {
            return;
        }
        $memory = $this->global_variable_memory;
        if ($memory["free"] > 30 and $wpmemory["percent"] < 0.85) {
            return;
        }
        $message = esc_attr__("Our plugin", "antibots");
        $message .= " (" . $this->plugin_slug . ") ";
        $message .= esc_attr__(
            "cannot function properly because your WordPress Memory Limit is too low. Your site will experience serious issues, even if you deactivate our plugin.",
            "antibots"
        );
        $message .=
            '<a href="' .
            esc_url($this->notification_url) .
            '">' .
            " " .
            esc_attr__("Learn more", "antibots") .
            "</a>";
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p style="color: red;">' . wp_kses_post($message) . "</p>";
        echo "</div>";
    }
    // Helper function to check if a notification has been displayed today
    public function is_notification_displayed_today()
    {
        $last_notification_date = get_option("antibots_bill_show_warnings");
        $today = date("Y-m-d");
        return $last_notification_date === $today;
    }
    // Add Tab
    public function site_health_navigation_tabs($tabs)
    {
        // translators: Tab heading for Site Health navigation.
        $tabs["Critical Issues"] = esc_html_x(
            "Critical Issues",
            "Site Health",
            "antibots"
        );
        return $tabs;
    }
    private function site_health_check_page_load()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "wptools_page_load_times";
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
            id INT PRIMARY KEY AUTO_INCREMENT,
            page_url VARCHAR(255) NOT NULL,
            load_time FLOAT NOT NULL,
            timestamp DATETIME NOT NULL
            ) $charset_collate;";
            require_once ABSPATH . "wp-admin/includes/upgrade.php";
            dbDelta($sql);
            // echo var_export($sql);
        }
        $query = "SELECT DATE(timestamp) AS date, AVG(load_time) AS average_load_time
        FROM $table_name
        WHERE timestamp >= CURDATE() - INTERVAL 6 DAY
        AND NOT page_url LIKE 'wp-admin'
        GROUP BY DATE(timestamp)
        ORDER BY date";
        $results9 = $wpdb->get_results($query, ARRAY_A);
        if ($results9) {
            $total = count($results9);
            if ($total < 1) {
                $wptools_empty = true;
                return false;
            }
        } else {
            $wptools_empty = true;
            return false;
        }
        // Calcula a média
        $total = 0;
        $count = 0;
        foreach ($results9 as $entry) {
            $total += (float) $entry["average_load_time"];
            $count++;
        }
        $average = $total / $count;
        $roundedAverage = round($average); // Arredonda para o número mais próximo
        return $roundedAverage;
    }
    public function site_health_check_page_load_main()
    {
        $average = $this->site_health_check_page_load();
        // $average = 7;
        //Excelente: Menos de 2 segundos
        //Bom: Entre 2 e 3 segundos
        //Regular: Entre 3 e 5 segundos
        //Pobre: Entre 5 e 8 segundos
        //Muito pobre: Mais de 8 segundos
        if ($average > 0) {
            echo "<br>";
            // 1. DETERMINA A COR E A MENSAGEM DO CABEÇALHO COM BASE NAS NOVAS REGRAS
            $header_color = "#008000"; // Padrão: Excelente (Dark Green)
            $message = esc_html__(
                "The page load time is Excellent.",
                "antibots"
            );
            // ...
            // 1. DETERMINA A COR E A MENSAGEM DO CABEÇALHO COM BASE NAS NOVAS REGRAS
            $header_color = "#008000"; // Padrão: Excelente (Dark Green)
            $message = esc_html__(
                "The page load time is Excellent.",
                "antibots"
            );
            if ($average >= 2 && $average < 3) {
                // Bom: Entre 2 e 3 segundos
                $header_color = "#90EE90"; // Light Green
                $message = esc_html__(
                    "The page load time is Good.",
                    "antibots"
                );
            } elseif ($average >= 3 && $average <= 5) {
                // REGULAR: Entre 3 e 5 segundos (INCLUINDO 5)
                $header_color = "orange";
                $message = esc_html__(
                    "The page load time is Regular",
                    "antibots"
                );
            } elseif ($average > 5 && $average <= 8) {
                // POBRE: Entre 5 e 8 segundos (EXCLUINDO 5, COMEÇANDO EM 5.00001...)
                $header_color = "darkorange";
                $message = esc_html__("The page load time is Poor", "antibots");
            } elseif ($average > 8) {
                // Muito pobre: Mais de 8 segundos
                $header_color = "red";
                $message = esc_html__(
                    "The page load time is Very Poor",
                    "antibots"
                );
            }
            // ...
            echo '<h2 style="color: ' . esc_attr($header_color) . ';">';
            echo esc_html__($message); // Exibe a mensagem determinada
            echo "</h2>";
            echo "<div>";
            // O CONTEÚDO DETALHADO E SUGESTÕES SÓ SÃO EXIBIDOS SE O STATUS NÃO FOR BOM/EXCELENTE/REGULAR (i.e., POBRE ou PIOR)
            if ($average >= 5) {
                echo esc_html__(
                    "The Load average of your front pages is: ",
                    "antibots"
                );
                echo esc_html($average);
                echo "<br>";
                echo esc_html__(
                    "Loading time can significantly impact your SEO.",
                    "antibots"
                );
                echo "<br>";
                echo esc_html__(
                    "Many users will abandon the site before it fully loads.",
                    "antibots"
                );
                echo "<br>";
                echo esc_html__(
                    "Search engines prioritize faster-loading pages, as they improve user experience and reduce bounce rates.",
                    "antibots"
                );
                echo "<br>";
                echo "<br>";
                echo "<strong>";
                echo esc_html__("Suggestions:", "antibots") . "<br>";
                echo "</strong>";
                echo esc_html__(
                    "Block bots: They overload the server and steal your content. Install our free plugin Antihacker.",
                    "antibots"
                ) . "<br>";
                echo esc_html__(
                    "Protect against hackers: They use bots to search for vulnerabilities and overload the server. Install our free plugin AntiHacker",
                    "antibots"
                ) . "<br>";
                echo esc_html__(
                    "Check your site for errors with free plugin wpTools. Errors and warnings can increase page load time by being recorded in log files, consuming resources and slowing down performance.",
                    "antibots"
                );
                echo "<br>";
                echo "<br>";
                echo '<a href="https://wptoolsplugin.com/page-load-times-and-their-negative-impact-on-seo/">';
                echo esc_html__(
                    "Learn more about Page Load Times and their negative impact on SEO and more",
                    "antibots"
                ) . "...";
                echo "</a>";
            } else {
                // Exibe a informação básica para status BOM, EXCELENTE ou REGULAR.
                echo esc_html__(
                    "The Load average of your front pages is: ",
                    "antibots"
                );
                echo esc_html($average);
                echo "<br>";
                if ($average < 3) {
                    echo esc_html__(
                        "Your page load time is excellent! Keep up the good work.",
                        "antibots"
                    );
                } else {
                    echo esc_html__(
                        "Your page load time is acceptable, but can still be improved.",
                        "antibots"
                    );
                }
            }
            echo "</div>";
        }
    }
    private function has_updates_available()
    {
        if (!function_exists('get_plugin_updates') || !function_exists('get_theme_updates')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        $plugin_updates = get_plugin_updates();
        $theme_updates = get_theme_updates();

        return !empty($plugin_updates) || !empty($theme_updates);
    }
    public function site_health_check_updates()
    {
        // -----------------Plugins -----------------------
        // Get available updates for plugins
        $updates = get_plugin_updates();
        $plugins = get_plugins();
        // Filter the list to include only plugins with updates
        $update_plugins = array_filter(
            $plugins,
            function ($plugin_path) use ($updates) {
                return array_key_exists($plugin_path, $updates);
            },
            ARRAY_FILTER_USE_KEY
        );
        $num_updates = count($update_plugins);
        // Conditional styling and text for the header
        $header_style = "";
        $header_text = esc_attr__("Plugins with Updates Available", "antibots");
        // Apply red style if updates are pending
        if ($num_updates > 0) {
            $header_style = 'style="color: red; font-weight: bold;"';
            $header_text = esc_attr__(
                "Plugins with Updates Available - ATTENTION!",
                "antibots"
            );
        }
        // Output section only if there are updates
        if ($num_updates > 0) {
            echo "<br>";
            // Display the alert message
            echo "<h2 " . $header_style . ">";
            echo $header_text . " (" . $num_updates . ")";
            echo "</h2>";
            // General recommendation text
            esc_attr_e(
                "Keeping your plugins up to date is crucial for ensuring security, performance, and compatibility.",
                "antibots"
            );
            echo "<br><br>";
            // --- START PLUGIN TABLE OUTPUT ---
            echo '<table class="widefat fixed striped">';
            // Table header
            echo "<thead>";
            echo "<tr>";
            echo '<th style="width: 50%;">Plugin Name</th>';
            echo '<th style="width: 25%;">Current Version</th>';
            echo '<th style="width: 25%;">Available Version</th>';
            echo "</tr>";
            echo "</thead>";
            // Table body
            echo "<tbody>";
            foreach ($update_plugins as $plugin_path => $plugin) {
                // Get the new version available
                $update_version = $updates[$plugin_path]->update->new_version;
                // Start a new table row for the plugin
                echo "<tr>";
                // Column 1: Plugin Name
                echo "<td><strong>" .
                    esc_html($plugin["Name"]) .
                    "</strong></td>";
                // Column 2: Current Version
                echo "<td>" . esc_html($plugin["Version"]) . "</td>";
                // Column 3: Available Version
                echo "<td><strong style='color: red;'>" .
                    esc_html($update_version) .
                    "</strong></td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            // --- END PLUGIN TABLE OUTPUT ---
        } else {
            // Output if no updates are required
            echo "<p>No plugins require updates at the moment. Good job! 👍</p>";
        }
        // -----------------END Plugins -----------------------
        // -----------------Themes -----------------------
        // Get available updates for themes
        $theme_updates = get_theme_updates();
        // get_theme_updates() already returns only themes with updates.
        $update_themes = $theme_updates;
        $num_theme_updates = count($update_themes);
        // Conditional styling and text for the header
        $theme_header_style = "";
        $theme_header_text = esc_attr__(
            "Themes with Updates Available",
            "antibots"
        );
        // Apply red style if updates are pending
        if ($num_theme_updates > 0) {
            $theme_header_style = 'style="color: red; font-weight: bold;"';
            $theme_header_text = esc_attr__(
                "Themes with Updates Available - ATTENTION!",
                "antibots"
            );
        }
        // Output section only if there are theme updates
        if ($num_theme_updates > 0) {
            echo "<br><hr>"; // Separator between Plugins and Themes
            echo "<br>";
            // Display the alert message
            echo "<h2 " . $theme_header_style . ">";
            echo $theme_header_text . " (" . $num_theme_updates . ")";
            echo "</h2>";
            // General recommendation text for themes
            esc_attr_e(
                "Updating themes is vital for security and compatibility with the current version of WordPress.",
                "antibots"
            );
            echo "<br><br>";
            // --- START THEME TABLE OUTPUT ---
            echo '<table class="widefat fixed striped">';
            // Table header
            echo "<thead>";
            echo "<tr>";
            echo '<th style="width: 50%;">Theme Name</th>';
            echo '<th style="width: 25%;">Current Version</th>';
            echo '<th style="width: 25%;">Available Version</th>';
            echo "</tr>";
            echo "</thead>";
            // Table body
            echo "<tbody>";
            foreach ($update_themes as $stylesheet => $theme) {
                // $theme->get('Name') is used because $theme is a WP_Theme object
                $theme_name = $theme->get("Name");
                $current_version = $theme->get("Version");
                // The update information is stored in the 'update' property
                $update_version = $theme->update["new_version"];
                // Start a new table row for the theme
                echo "<tr>";
                // Column 1: Theme Name
                echo "<td><strong>" . esc_html($theme_name) . "</strong></td>";
                // Column 2: Current Version
                echo "<td>" . esc_html($current_version) . "</td>";
                // Column 3: Available Version
                echo "<td><strong style='color: red;'>" .
                    esc_html($update_version) .
                    "</strong></td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            // --- END THEME TABLE OUTPUT ---
        } else {
            // Output if no theme updates are required
            echo "<p>No themes require updates at the moment. Excellent! 🌟</p>";
        }
        // -----------------END Themes -----------------------
    }
    public function site_health_bots_and_hackers()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "bill_catch_some_bots";
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists == $table_name) {
            $charset_collate = $this->wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            data timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip varchar(45) DEFAULT NULL,
            pag text DEFAULT NULL,
            ua text DEFAULT NULL,
            bot tinyint(1) DEFAULT 0,
            http_code smallint(3) DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
            require_once ABSPATH . "wp-admin/includes/upgrade.php";
            dbDelta($sql);
        }
        //$result = $wpdb->get_row("SELECT COUNT(*) AS total_bots FROM $table_name WHERE bot = 1;");
        //if ($result && $result->total_bots > 0) {
        // $num_attacks = $result->total_bots;
        // Obter 30 registros onde bot = 1
        $rows = $wpdb->get_results("
            SELECT data 
            FROM $table_name 
            WHERE bot = 1 
            ORDER BY data DESC 
            LIMIT 30
            ");
        // Verificar se há registros suficientes
        $num_attacks = 0;
        $diferenca_segundos = 0;
        if (!empty($rows) && count($rows) > 0) {
            $num_attacks = count($rows);
            $max_data = $rows[0]->data; // Primeiro registro
            $min_data = $rows[count($rows) - 1]->data; // Último registro
            // echo $max_data;
            // Calcular a diferença em segundos
            $diferenca_segundos = strtotime($max_data) - strtotime($min_data);
            // Função para formatar a diferença de tempo
            function format_time_difference2($seconds)
            {
                if ($seconds < 60) {
                    return "$seconds" . " " . esc_attr__("seconds", "antibots");
                } elseif ($seconds < 3600) {
                    return round($seconds / 60) .
                        " " .
                        esc_attr__("minutes", "antibots");
                } elseif ($seconds < 86400) {
                    return round($seconds / 3600) .
                        " " .
                        esc_attr__("hour(s)", "antibots");
                } elseif ($seconds < 604800) {
                    return round($seconds / 86400) .
                        " " .
                        esc_attr__("day(s)", "antibots");
                } elseif ($seconds < 2592000) {
                    return round($seconds / 604800) .
                        " " .
                        esc_attr__("week(s)", "antibots");
                } else {
                    return round($seconds / 2592000) .
                        " " .
                        esc_attr__("month(s)", "antibots");
                }
            }
            function format_time_difference($seconds)
            {
                if ($seconds < 60) {
                    return "{$seconds}s";
                }
                $minutes = floor($seconds / 60);
                $seconds = $seconds % 60;
                if ($minutes < 60) {
                    return "{$minutes}m" . ($seconds > 0 ? " {$seconds}s" : "");
                }
                $hours = floor($minutes / 60);
                $minutes = $minutes % 60;
                if ($hours < 24) {
                    return "{$hours}h" . ($minutes > 0 ? " {$minutes}m" : "");
                }
                $days = floor($hours / 24);
                $hours = $hours % 24;
                if ($days < 7) {
                    return "{$days}d" . ($hours > 0 ? " {$hours}h" : "");
                }
                $weeks = floor($days / 7);
                $days = $days % 7;
                if ($weeks < 4) {
                    return "{$weeks}w" . ($days > 0 ? " {$days}d" : "");
                }
                $months = floor($weeks / 4);
                $weeks = $weeks % 4;
                return "{$months}mo" . ($weeks > 0 ? " {$weeks}w" : "");
            }
            $hours = max($diferenca_segundos / 3600, 0.0167);
            $attacks_per_hour = round($num_attacks / $hours, 1);
            $time_formatted = format_time_difference($diferenca_segundos);
            echo "<strong>";
            echo esc_attr__("Number of last attacks: ", "antibots") .
                "{$num_attacks} attacks in {$time_formatted} (avg. {$attacks_per_hour} attacks/hour)";
            echo "</strong>";
            echo "<br>";
            //echo $diferenca_segundos;
            echo "<br>";
            //echo '</strong>';
            esc_attr_e(
                "Bots aren't human—they're automated scripts that visit your site. They steal your content, making it less unique. They overload your server, slowing it down and hurting your SEO.",
                "antibots"
            );
            echo "<br>";
            esc_attr_e(
                "Hackers look for vulnerabilities to access your server. Even small sites are targets—they use your server to send spam and attack others, damaging your IP and email reputation.",
                "antibots"
            );
            echo "<br>";
            esc_attr_e(
                "If you doubt the accuracy of the table below, check with your hosting provider or check the IPs with the site https://ipinfo.io.",
                "antibots"
            );
            echo "<br>";
            echo "<br>";
            echo "<strong>";
            echo sprintf(
                __(
                    'Our free <a href="%1$s">antibots</a> and <a href="%2$s">AntiHacker</a> plugins help safeguard your site.',
                    "antibots"
                ),
                esc_url("https://antibots.com"),
                esc_url("https://antihackerplugin.com")
            );
            echo "</strong>";
            echo "<hr>";
            $results = $wpdb->get_results("
            SELECT data, ip, pag, http_code, bot, ua 
            FROM $table_name 
            WHERE bot = 1
            ORDER BY data DESC 
            LIMIT 30
             ");
            if ($results) {
                echo '<div class="wrap"><h2>Partial Last Records (Bots and Hacker Attacks)</h2>';
                echo '<table class="widefat fixed striped">';
                echo '<thead>
                    <tr>
                        <th>Date</th>
                        <th>IP</th>
                        <th>Page</th>
                        <th>Response <br> Code</th>
                        <!-- <th>Bot?</th> -->
                        <th>User Agent</th>
                    </tr>
                  </thead>';
                echo "<tbody>";
                foreach ($results as $row) {
                    echo "<tr>";
                    // echo '<td>' . esc_html($row->data) . '</td>';
                    echo "<td>";
                    echo date("Y-m-d", strtotime($row->data)) .
                        "<br>" .
                        date("H:i:s", strtotime($row->data));
                    echo "</td>";
                    echo "<td>" . esc_html($row->ip) . "</td>";
                    echo "<td>" . esc_html($row->pag) . "</td>";
                    echo "<td>" . esc_html($row->http_code) . "</td>";
                    //echo '<td>' . ($row->bot ? '<span style="color:red;">Sim</span>' : 'Não') . '</td>';
                    echo "<td>" . esc_html($row->ua) . "</td>";
                    echo "</tr>";
                }
                echo "</tbody></table></div>";
            } else {
                echo "<p>No records found. (2)</p>";
            }
        } else {
            echo "<p>No records found. Please, try later.</p>";
        }
    }
    // //////////////////   Add Content

    /**
     * Get file size in bytes.
     *
     * @param string $bill_filename File path.
     * @return int|string Size in bytes or error message.
     */
    public function getFileSizeInBytes($bill_filename)
    {
        if (!file_exists($bill_filename) || !is_readable($bill_filename)) {
            // return "File not readable.";
            return esc_attr__("File not readable.", "antibots");
        }
        $fileSizeBytes = filesize($bill_filename);
        if ($fileSizeBytes === false) {
            //return "Size not determined.";
            return esc_attr__("Size not determined.", "antibots");
        }
        return $fileSizeBytes;
    }

    /**
     * Convert bytes to human readable size.
     *
     * @param int $sizeBytes Size in bytes.
     * @return string Human readable size.
     */
    public function convertToHumanReadableSize($sizeBytes)
    {
        if (!is_int($sizeBytes) || $sizeBytes < 0) {
            // Return error message for invalid size
            return esc_attr__("Invalid size.", "antibots");
        }
        $units = ["B", "KB", "MB", "GB", "TB"];
        $unitIndex = 0;
        while ($sizeBytes >= 1024 && $unitIndex < count($units) - 1) {
            $sizeBytes /= 1024;
            $unitIndex++;
        }
        // Return value with unit
        return sprintf("%.2f %s", $sizeBytes, $units[$unitIndex]);
    }

    // Lists the errors (echoes the details).
    public function list_errors()
    {
        $bill_count = 0;
        // Create ErrorChecker object:
        $errorChecker = new ErrorChecker();
        // Call get_path_logs() method:
        $bill_folders = $errorChecker->get_path_logs(); // Use -> (arrow)
        //echo "<br />";
        echo esc_attr__(
            "This is a partial list of the errors found.",
            "antibots"
        );
        echo "</div>";
        echo "<br />";

        // ENHANCED ERROR TYPES COUNT ARRAY
        $error_types_count = [
            "Deprecated" => 0,
            "Fatal" => 0,
            "Warning" => 0,
            "Notice" => 0,
            "Parse" => 0,
            "Strict" => 0,
            "Recoverable" => 0,
            "Core" => 0,
            "Compile" => 0,
            "User" => 0,
            "Database" => 0,
            "JavaScript" => 0,
            "Filesystem" => 0,
            "HTTP_API" => 0,
            "Other" => 0,
        ];

        // Start showing errors...
        //
        foreach ($bill_folders as $bill_folder) {
            $files = glob($bill_folder);
            if ($files === false) {
                continue; // skip ...
            }
            // foreach (glob($bill_folder) as $bill_filename)
            foreach ($files as $bill_filename) {
                if (strpos($bill_filename, "backup") != true) {
                    echo "<strong>";
                    echo esc_attr($bill_filename);
                    echo "<br />";
                    echo esc_attr__("File Size: ", "antibots");
                    echo "&nbsp;";
                    $fileSizeBytes = $this->getFileSizeInBytes($bill_filename);
                    if (is_int($fileSizeBytes)) {
                        echo esc_attr(
                            $this->convertToHumanReadableSize($fileSizeBytes)
                        );
                    } else {
                        echo esc_attr($fileSizeBytes); // Show error message
                    }
                    echo "</strong>";
                    $bill_count++;
                    $errorChecker = new ErrorChecker();
                    // debug2($bill_filename);
                    $marray = $errorChecker->bill_read_file(
                        $bill_filename,
                        3000
                    );
                    //$marray = $this->bill_read_file($bill_filename, 3000);
                    if (gettype($marray) != "array" or count($marray) < 1) {
                        continue;
                    }
                    // debug2($bill_filename);
                    $total = count($marray);
                    if (count($marray) > 0) {
                        echo '<textarea style="width:99%;" id="anti_hacker" rows="12">';
                        if ($total > 1000) {
                            $total = 1000;
                        }
                        for ($i = 0; $i < $total; $i++) {
                            if (strpos(trim($marray[$i]), "[") !== 0) {
                                continue; // Skip lines without correct date format
                            }
                            $logs = [];
                            $line = trim($marray[$i]);
                            if (empty($line)) {
                                continue;
                            }
                            // debug2($line);
                            //  stack trace
                            //[30-Sep-2023 11:28:52 UTC] PHP Stack trace:
                            $pattern = "/PHP Stack trace:/";
                            if (preg_match($pattern, $line, $matches)) {
                                continue;
                            }
                            $pattern =
                                "/\d{4}-\w{3}-\d{4} \d{2}:\d{2}:\d{2} UTC\] PHP \d+\./";
                            if (preg_match($pattern, $line, $matches)) {
                                continue;
                            }
                            //  end stack trace
                            // Javascript ?
                            if (strpos($line, "Javascript") !== false) {
                                $is_javascript = true;
                            } else {
                                $is_javascript = false;
                            }
                            if ($is_javascript) {
                                $matches = [];
                                // die($line);
                                $apattern = [];
                                // ENHANCED JAVASCRIPT PATTERN - more flexible
                                $apattern[] =
                                    "/(JavaScript\s+)?(Error|SyntaxError|TypeError|ReferenceError|RangeError|EvalError|URIError):?\s*(.*?)\s*(at\s+.*)?(\s*URL:\s*(https?:\/\/\S+))?.*?(Line\s*:?[\s\d]+)?/i";
                                $apattern[] =
                                    "/(Error|Syntax|Type|TypeError|Reference|ReferenceError|Range|Eval|URI|Error .*?): (.*?) - URL: (https?:\/\/\S+).*?Line: (\d+).*?Column: (\d+).*?Error object: ({.*?})/";
                                $apattern[] =
                                    "/(SyntaxError|Error|Syntax|Type|TypeError|Reference|ReferenceError|Range|Eval|URI|Error .*?): (.*?) - URL: (https?:\/\/\S+).*?Line: (\d+)/";
                                // Google Maps !
                                //$apattern[] = "/Script error(?:\. - URL: (https?:\/\/\S+))?/i";
                                $pattern = $apattern[0];
                                for ($j = 0; $j < count($apattern); $j++) {
                                    if (
                                        preg_match(
                                            $apattern[$j],
                                            $line,
                                            $matches
                                        )
                                    ) {
                                        $pattern = $apattern[$j];
                                        break;
                                    }
                                }
                                if (preg_match($pattern, $line, $matches)) {
                                    // COUNT JAVASCRIPT ERRORS - ENHANCED
                                    $error_types_count["JavaScript"]++;

                                    $matches[1] = str_replace(
                                        "Javascript ",
                                        "",
                                        $matches[1]
                                    );
                                    // $filteredDate = strstr(substr($line, 1, 26), ']', true);
                                    if (
                                        preg_match(
                                            "/\[(.*?)\]/",
                                            $line,
                                            $dateMatches
                                        )
                                    ) {
                                        $filteredDate = $dateMatches[1];
                                    } else {
                                        $filteredDate = "";
                                    }
                                    // die(var_export(substr($line, 1, 25)));
                                    // $filteredDate = substr($line, 1, 20);
                                    if (count($matches) == 2) {
                                        $log_entry = [
                                            "Date" => $filteredDate,
                                            "Message Type" => "Script error",
                                            "Problem Description" => "N/A",
                                            "Script URL" => $matches[1],
                                            "Line" => "N/A",
                                        ];
                                    } else {
                                        $log_entry = [
                                            "Date" => $filteredDate,
                                            "Message Type" => $matches[1],
                                            "Problem Description" =>
                                            $matches[2],
                                            "Script URL" => $matches[3],
                                            "Line" => isset($matches[4]) ? $matches[4] : "Unknown",
                                        ];
                                    }
                                    $script_path = $matches[3];
                                    $script_info = pathinfo($script_path);
                                    // Split script name based on ":"
                                    $parts = explode(
                                        ":",
                                        $script_info["basename"]
                                    );
                                    // Script name is now in first part
                                    $scriptName = $parts[0];
                                    $log_entry["Script Name"] = $scriptName; // Get the script name

                                    $log_entry["Script Location"] = $script_info["dirname"] ?? "Unknown";


                                    if (
                                        $log_entry["Script Location"] ==
                                        "http:" or
                                        $log_entry["Script Location"] ==
                                        "https:"
                                    ) {
                                        $log_entry["Script Location"] =
                                            $matches[3];
                                    }
                                    if (
                                        strpos(
                                            $log_entry["Script URL"],
                                            "/wp-content/plugins/"
                                        ) !== false
                                    ) {
                                        // Error occurred in a plugin
                                        $parts = explode(
                                            "/wp-content/plugins/",
                                            $log_entry["Script URL"]
                                        );
                                        if (count($parts) > 1) {
                                            $plugin_parts = explode(
                                                "/",
                                                $parts[1]
                                            );
                                            $log_entry["File Type"] = "Plugin";
                                            $log_entry["Plugin Name"] =
                                                $plugin_parts[0];
                                            //   $log_entry["Script Location"] =
                                            //      "/wp-content/plugins/" .
                                            //       $plugin_parts[0];
                                        }
                                    } elseif (
                                        strpos(
                                            $log_entry["Script URL"],
                                            "/wp-content/themes/"
                                        ) !== false
                                    ) {
                                        // Error occurred in a theme
                                        $parts = explode(
                                            "/wp-content/themes/",
                                            $log_entry["Script URL"]
                                        );
                                        if (count($parts) > 1) {
                                            $theme_parts = explode(
                                                "/",
                                                $parts[1]
                                            );
                                            $log_entry["File Type"] = "Theme";
                                            $log_entry["Theme Name"] =
                                                $theme_parts[0];
                                            // $log_entry["Script Location"] =
                                            //     "/wp-content/themes/" .
                                            //     $theme_parts[0];
                                        }
                                    } else {
                                        // If not a theme or plugin, may need to adjust behavior here.
                                        //$log_entry["Script Location"] = $matches[1];
                                    }
                                    // Extract script name from URL
                                    $script_name = basename(
                                        wp_parse_url(
                                            $log_entry["Script URL"],
                                            PHP_URL_PATH
                                        )
                                    );
                                    $log_entry["Script Name"] = $script_name;
                                    //echo $line."\n";
                                    if (isset($log_entry["Date"])) {
                                        echo "DATE: " .
                                            esc_html($log_entry["Date"]) .
                                            "\n";
                                    }
                                    if (isset($log_entry["Message Type"])) {
                                        echo "MESSAGE TYPE: (Javascript) " .
                                            esc_html(
                                                $log_entry["Message Type"]
                                            ) .
                                            "\n";
                                    }
                                    if (
                                        isset($log_entry["Problem Description"])
                                    ) {
                                        echo "PROBLEM DESCRIPTION: " .
                                            esc_html(
                                                $log_entry["Problem Description"]
                                            ) .
                                            "\n";
                                    }
                                    if (isset($log_entry["Script Name"])) {
                                        echo "SCRIPT NAME: " .
                                            esc_html(
                                                $log_entry["Script Name"]
                                            ) .
                                            "\n";
                                    }
                                    if (isset($log_entry["Line"])) {
                                        echo "LINE: " .
                                            esc_html($log_entry["Line"]) .
                                            "\n";
                                    }
                                    if (isset($log_entry["Column"])) {
                                        //	echo "COLUMN: {$log_entry['Column']}\n";
                                    }
                                    if (isset($log_entry["Error Object"])) {
                                        //	echo "ERROR OBJECT: {$log_entry['Error Object']}\n";
                                    }
                                    if (isset($log_entry["Script Location"])) {
                                        echo "SCRIPT LOCATION: " .
                                            esc_html(
                                                $log_entry["Script Location"]
                                            ) .
                                            "\n";
                                    }
                                    if (isset($log_entry["Plugin Name"])) {
                                        echo "PLUGIN NAME: " .
                                            esc_html(
                                                $log_entry["Plugin Name"]
                                            ) .
                                            "\n";
                                    }
                                    if (isset($log_entry["Theme Name"])) {
                                        echo "THEME NAME: " .
                                            esc_html($log_entry["Theme Name"]) .
                                            "\n";
                                    }
                                    echo "------------------------\n";
                                    continue;
                                } else {
                                    // echo "-----------x-------------\n";
                                    echo esc_html($line);
                                    echo "\n-----------x------------\n";
                                }
                                continue;
                                // END JAVASCRIPT
                            } else {
                                // ---- PHP //
                                // continue;
                                $apattern = [];
                                // ENHANCED PHP PATTERN - complete error coverage
                                $apattern[] =
                                    "/^\[.*\] PHP (Warning|Error|Notice|Fatal error|Parse error|Recoverable fatal error|Core error|Core warning|Compile error|Compile warning|Deprecated|Strict Standards|User Error|User Warning|User Notice|User Deprecated): (.*) in \/([^ ]+) on line (\d+)/";
                                $apattern[] =
                                    "/^\[.*\] PHP (Warning|Error|Notice|Fatal error|Parse error|Recoverable fatal error|Core error|Core warning|Compile error|Compile warning|Deprecated|Strict Standards|User Error|User Warning|User Notice|User Deprecated): (.*) in \/([^ ]+):(\d+)$/";
                                $pattern = $apattern[0];
                                for ($j = 0; $j < count($apattern); $j++) {
                                    if (
                                        preg_match(
                                            $apattern[$j],
                                            $line,
                                            $matches
                                        )
                                    ) {
                                        $pattern = $apattern[$j];
                                        break;
                                    }
                                }
                                if (preg_match($pattern, $line, $matches)) {
                                    // ENHANCED ERROR TYPE COUNTING - PHP
                                    $error_type = $matches[1];
                                    $error_description = $matches[2];

                                    // Enhanced PHP error categorization
                                    if (
                                        stripos($error_type, "deprecated") !==
                                        false
                                    ) {
                                        $error_types_count["Deprecated"]++;
                                    } elseif (
                                        stripos($error_type, "fatal") !==
                                        false &&
                                        stripos($error_type, "recoverable") ===
                                        false
                                    ) {
                                        $error_types_count["Fatal"]++;
                                    } elseif (
                                        stripos($error_type, "recoverable") !==
                                        false
                                    ) {
                                        $error_types_count["Recoverable"]++;
                                    } elseif (
                                        stripos($error_type, "warning") !==
                                        false &&
                                        stripos($error_type, "core") ===
                                        false &&
                                        stripos($error_type, "compile") ===
                                        false &&
                                        stripos($error_type, "user") === false
                                    ) {
                                        $error_types_count["Warning"]++;
                                    } elseif (
                                        stripos($error_type, "notice") !==
                                        false &&
                                        stripos($error_type, "user") === false
                                    ) {
                                        $error_types_count["Notice"]++;
                                    } elseif (
                                        stripos($error_type, "parse") !== false
                                    ) {
                                        $error_types_count["Parse"]++;
                                    } elseif (
                                        stripos($error_type, "strict") !== false
                                    ) {
                                        $error_types_count["Strict"]++;
                                    } elseif (
                                        stripos($error_type, "core") !== false
                                    ) {
                                        $error_types_count["Core"]++;
                                    } elseif (
                                        stripos($error_type, "compile") !==
                                        false
                                    ) {
                                        $error_types_count["Compile"]++;
                                    } elseif (
                                        stripos($error_type, "user") !== false
                                    ) {
                                        $error_types_count["User"]++;
                                    } else {
                                        $error_types_count["Other"]++;
                                    }

                                    // ENHANCED DATABASE ERROR DETECTION
                                    if (
                                        preg_match(
                                            "/(database|mysql|mysqli|wpdb|pdo|SQLSTATE|Query failed|Connection refused|Access denied|database connection|Unknown column|Syntax error in SQL)/i",
                                            $error_description
                                        )
                                    ) {
                                        $error_types_count["Database"]++;
                                    }

                                    // FILESYSTEM ERROR DETECTION
                                    if (
                                        preg_match(
                                            "/(fopen|fwrite|unlink|file_get_contents|permission denied|No such file)/i",
                                            $error_description
                                        )
                                    ) {
                                        $error_types_count["Filesystem"]++;
                                    }

                                    // HTTP/API ERROR DETECTION
                                    if (
                                        preg_match(
                                            "/(curl|cURL error|HTTP error|timed out|Connection timed out|SSL)/i",
                                            $error_description
                                        )
                                    ) {
                                        $error_types_count["HTTP_API"]++;
                                    }

                                    //die(var_export($matches));
                                    // $filteredDate = strstr(substr($line, 1, 26), ']', true);
                                    if (
                                        preg_match(
                                            "/\[(.*?)\]/",
                                            $line,
                                            $dateMatches
                                        )
                                    ) {
                                        $filteredDate = $dateMatches[1];
                                    } else {
                                        $filteredDate = "";
                                    }
                                    $log_entry = [
                                        "Date" => $filteredDate,
                                        "News Type" => $matches[1],
                                        "Problem Description" => antibots_bill_strip_strong99(
                                            $matches[2]
                                        ),
                                    ];
                                    $script_path = $matches[3];
                                    $script_info = pathinfo($script_path);
                                    // Split script name based on ":"
                                    $parts = explode(
                                        ":",
                                        $script_info["basename"]
                                    );
                                    // Script name is now in first part
                                    $scriptName = $parts[0];
                                    $log_entry["Script Name"] = $scriptName; // Get the script name
                                    $log_entry["Script Location"] =
                                        $script_info["dirname"]; // Get the script location
                                    $log_entry["Line"] = $matches[4];
                                    // Check if the "Script Location" contains "/plugins/" or "/themes/"
                                    if (
                                        strpos(
                                            $log_entry["Script Location"],
                                            "/plugins/"
                                        ) !== false
                                    ) {
                                        // Extract the plugin name
                                        $parts = explode(
                                            "/plugins/",
                                            $log_entry["Script Location"]
                                        );
                                        if (count($parts) > 1) {
                                            $plugin_parts = explode(
                                                "/",
                                                $parts[1]
                                            );
                                            $log_entry["File Type"] = "Plugin";
                                            $log_entry["Plugin Name"] =
                                                $plugin_parts[0];
                                        }
                                    } elseif (
                                        strpos(
                                            $log_entry["Script Location"],
                                            "/themes/"
                                        ) !== false
                                    ) {
                                        // Extract the theme name
                                        $parts = explode(
                                            "/themes/",
                                            $log_entry["Script Location"]
                                        );
                                        if (count($parts) > 1) {
                                            $theme_parts = explode(
                                                "/",
                                                $parts[1]
                                            );
                                            $log_entry["File Type"] = "Theme";
                                            $log_entry["Theme Name"] =
                                                $theme_parts[0];
                                        }
                                    }
                                } else {
                                    // stack trace...
                                    $pattern = "/\[.*?\] PHP\s+\d+\.\s+(.*)/";
                                    preg_match($pattern, $line, $matches);
                                    if (!preg_match($pattern, $line)) {
                                        echo "-----------y-------------\n";
                                        echo esc_html($line);
                                        echo "\n-----------y------------\n";
                                    }
                                    continue;
                                }
                                //$in_error_block = false; // End the error block
                                $logs[] = $log_entry; // Add this log entry to the array of logs
                                foreach ($logs as $log) {
                                    if (isset($log["Date"])) {
                                        echo "DATE: " .
                                            esc_html($log["Date"]) .
                                            "\n";
                                    }
                                    if (isset($log["News Type"])) {
                                        echo "MESSAGE TYPE: " .
                                            esc_html($log["News Type"]) .
                                            "\n";
                                    }
                                    if (isset($log["Problem Description"])) {
                                        echo "PROBLEM DESCRIPTION: " .
                                            esc_html(
                                                $log["Problem Description"]
                                            ) .
                                            "\n";
                                    }
                                    // Check if the 'Script Name' key exists before printing
                                    if (
                                        isset($log["Script Name"]) &&
                                        !empty(trim($log["Script Name"]))
                                    ) {
                                        echo "SCRIPT NAME: " .
                                            esc_html($log["Script Name"]) .
                                            "\n";
                                    }
                                    // Check if the 'Line' key exists before printing
                                    if (isset($log["Line"])) {
                                        echo "LINE: " .
                                            esc_html($log["Line"]) .
                                            "\n";
                                    }
                                    // Check if the 'Script Location' key exists before printing
                                    if (isset($log["Script Location"])) {
                                        echo "SCRIPT LOCATION: " .
                                            esc_html($log["Script Location"]) .
                                            "\n";
                                    }
                                    // Check if the 'File Type' key exists before printing
                                    if (isset($log["File Type"])) {
                                        // echo "FILE TYPE: " . esc_html($log["File Type"]) . "\n";
                                    }
                                    // Check if the 'Plugin Name' key exists before printing
                                    if (
                                        isset($log["Plugin Name"]) &&
                                        !empty(trim($log["Plugin Name"]))
                                    ) {
                                        echo "PLUGIN NAME: " .
                                            esc_html($log["Plugin Name"]) .
                                            "\n";
                                    }
                                    // Check if the 'Theme Name' key exists before printing
                                    if (isset($log["Theme Name"])) {
                                        echo "THEME NAME: " .
                                            esc_html($log["Theme Name"]) .
                                            "\n";
                                    }
                                    echo "------------------------\n";
                                }
                            }
                            // end if PHP ...
                        } // end for...
                        echo "</textarea>";
                    }
                    echo "<br />";
                }
            } // end for next each error_log...
            //echo "<br>";
        } // end fo next each folder...

        return $error_types_count;
    }

    public function count_error_types_old()
    {
        // ENHANCED ERROR TYPES COUNT ARRAY
        $error_types_count = [
            "Deprecated" => 0,
            "Fatal" => 0,
            "Warning" => 0,
            "Notice" => 0,
            "Parse" => 0,
            "Strict" => 0,
            "Recoverable" => 0,
            "Core" => 0,
            "Compile" => 0,
            "User" => 0,
            "Database" => 0,
            "JavaScript" => 0,
            "Filesystem" => 0,
            "HTTP_API" => 0,
            "Other" => 0,
        ];

        // Create ErrorChecker object and get log paths
        $errorChecker = new ErrorChecker();
        $bill_folders = $errorChecker->get_path_logs();

        foreach ($bill_folders as $bill_folder) {
            $files = glob($bill_folder);
            if ($files === false) {
                continue; // skip invalid patterns
            }

            foreach ($files as $bill_filename) {
                if (strpos($bill_filename, "backup") != true) {
                    // Process file line by line for memory efficiency
                    $file = fopen($bill_filename, 'r');
                    if ($file) {
                        while (($line = fgets($file)) !== false) {
                            $line = trim($line);
                            if (empty($line) || strpos($line, "[") !== 0) {
                                continue; // Skip lines without correct date format
                            }

                            // Skip stack trace lines
                            if (
                                preg_match("/PHP Stack trace:/", $line) ||
                                preg_match("/\d{4}-\w{3}-\d{4} \d{2}:\d{2}:\d{2} UTC\] PHP \d+\./", $line)
                            ) {
                                continue;
                            }

                            // Check for JavaScript errors
                            if (strpos($line, "Javascript") !== false) {
                                $apattern = [];
                                $apattern[] = "/(JavaScript\s+)?(Error|SyntaxError|TypeError|ReferenceError|RangeError|EvalError|URIError):?\s*(.*?)\s*(at\s+.*)?(\s*URL:\s*(https?:\/\/\S+))?.*?(Line\s*:?[\s\d]+)?/i";
                                $apattern[] = "/(Error|Syntax|Type|TypeError|Reference|ReferenceError|Range|Eval|URI|Error .*?): (.*?) - URL: (https?:\/\/\S+).*?Line: (\d+).*?Column: (\d+).*?Error object: ({.*?})/";
                                $apattern[] = "/(SyntaxError|Error|Syntax|Type|TypeError|Reference|ReferenceError|Range|Eval|URI|Error .*?): (.*?) - URL: (https?:\/\/\S+).*?Line: (\d+)/";

                                $pattern = $apattern[0];
                                for ($j = 0; $j < count($apattern); $j++) {
                                    if (preg_match($apattern[$j], $line, $matches)) {
                                        $pattern = $apattern[$j];
                                        break;
                                    }
                                }

                                if (preg_match($pattern, $line, $matches)) {
                                    $error_types_count["JavaScript"]++;
                                }
                                continue;
                            }

                            // Process PHP errors
                            $apattern = [];
                            $apattern[] = "/^\[.*\] PHP (Warning|Error|Notice|Fatal error|Parse error|Recoverable fatal error|Core error|Core warning|Compile error|Compile warning|Deprecated|Strict Standards|User Error|User Warning|User Notice|User Deprecated): (.*) in \/([^ ]+) on line (\d+)/";
                            $apattern[] = "/^\[.*\] PHP (Warning|Error|Notice|Fatal error|Parse error|Recoverable fatal error|Core error|Core warning|Compile error|Compile warning|Deprecated|Strict Standards|User Error|User Warning|User Notice|User Deprecated): (.*) in \/([^ ]+):(\d+)$/";

                            $pattern = $apattern[0];
                            for ($j = 0; $j < count($apattern); $j++) {
                                if (preg_match($apattern[$j], $line, $matches)) {
                                    $pattern = $apattern[$j];
                                    break;
                                }
                            }

                            if (preg_match($pattern, $line, $matches)) {
                                $error_type = $matches[1];
                                $error_description = $matches[2];

                                // Enhanced PHP error categorization (same logic as original)
                                if (stripos($error_type, "deprecated") !== false) {
                                    $error_types_count["Deprecated"]++;
                                } elseif (
                                    stripos($error_type, "fatal") !== false &&
                                    stripos($error_type, "recoverable") === false
                                ) {
                                    $error_types_count["Fatal"]++;
                                } elseif (stripos($error_type, "recoverable") !== false) {
                                    $error_types_count["Recoverable"]++;
                                } elseif (
                                    stripos($error_type, "warning") !== false &&
                                    stripos($error_type, "core") === false &&
                                    stripos($error_type, "compile") === false &&
                                    stripos($error_type, "user") === false
                                ) {
                                    $error_types_count["Warning"]++;
                                } elseif (
                                    stripos($error_type, "notice") !== false &&
                                    stripos($error_type, "user") === false
                                ) {
                                    $error_types_count["Notice"]++;
                                } elseif (stripos($error_type, "parse") !== false) {
                                    $error_types_count["Parse"]++;
                                } elseif (stripos($error_type, "strict") !== false) {
                                    $error_types_count["Strict"]++;
                                } elseif (stripos($error_type, "core") !== false) {
                                    $error_types_count["Core"]++;
                                } elseif (stripos($error_type, "compile") !== false) {
                                    $error_types_count["Compile"]++;
                                } elseif (stripos($error_type, "user") !== false) {
                                    $error_types_count["User"]++;
                                } else {
                                    $error_types_count["Other"]++;
                                }

                                // Enhanced database error detection
                                if (preg_match("/(database|mysql|mysqli|wpdb|pdo|SQLSTATE|Query failed|Connection refused|Access denied|database connection|Unknown column|Syntax error in SQL)/i", $error_description)) {
                                    $error_types_count["Database"]++;
                                }

                                // Filesystem error detection
                                if (preg_match("/(fopen|fwrite|unlink|file_get_contents|permission denied|No such file)/i", $error_description)) {
                                    $error_types_count["Filesystem"]++;
                                }

                                // HTTP/API error detection
                                if (preg_match("/(curl|cURL error|HTTP error|timed out|Connection timed out|SSL)/i", $error_description)) {
                                    $error_types_count["HTTP_API"]++;
                                }
                            }
                        }
                        fclose($file);
                    }
                }
            }
        }

        return $error_types_count;
    }

    public function count_error_types()
    {
        // ENHANCED ERROR TYPES COUNT ARRAY
        $error_types_count = [
            "Deprecated" => 0,
            "Fatal" => 0,
            "Warning" => 0,
            "Notice" => 0,
            "Parse" => 0,
            "Strict" => 0,
            "Recoverable" => 0,
            "Core" => 0,
            "Compile" => 0,
            "User" => 0,
            "Database" => 0,
            "JavaScript" => 0,
            "Filesystem" => 0,
            "HTTP_API" => 0,
            "Other" => 0,
        ];

        // GLOBAL SAFETY LIMITS
        $max_total_lines = 5000;      // Maximum lines to process across all files
        $max_execution_time = 15;     // Maximum execution time in seconds
        $start_time = time();         // Start time for timeout control
        $total_lines_processed = 0;   // Global line counter

        // Create ErrorChecker object and get log paths
        $errorChecker = new ErrorChecker();
        $bill_folders = $errorChecker->get_path_logs();

        foreach ($bill_folders as $bill_folder) {
            // Check global timeout
            if (time() - $start_time >= $max_execution_time) {
                break;
            }

            $files = glob($bill_folder);
            if ($files === false) {
                continue; // skip invalid patterns
            }

            foreach ($files as $bill_filename) {
                // Check global line limit
                if ($total_lines_processed >= $max_total_lines) {
                    break 2; // Exit both loops
                }

                // Check global timeout
                if (time() - $start_time >= $max_execution_time) {
                    break 2; // Exit both loops
                }

                if (strpos($bill_filename, "backup") != true) {
                    // Process file line by line for memory efficiency
                    $file = fopen($bill_filename, 'r');
                    if ($file) {
                        while (($line = fgets($file)) !== false) {
                            // CHECK GLOBAL LIMITS EVERY 50 LINES FOR PERFORMANCE
                            if ($total_lines_processed % 50 === 0) {
                                if ($total_lines_processed >= $max_total_lines) {
                                    break 3; // Exit all loops
                                }
                                if (time() - $start_time >= $max_execution_time) {
                                    break 3; // Exit all loops
                                }
                            }

                            $line = trim($line);
                            if (empty($line) || strpos($line, "[") !== 0) {
                                continue; // Skip lines without correct date format
                            }

                            // Skip stack trace lines
                            if (
                                preg_match("/PHP Stack trace:/", $line) ||
                                preg_match("/\d{4}-\w{3}-\d{4} \d{2}:\d{2}:\d{2} UTC\] PHP \d+\./", $line)
                            ) {
                                continue;
                            }

                            // Check for JavaScript errors
                            if (strpos($line, "Javascript") !== false) {
                                $apattern = [];
                                $apattern[] = "/(JavaScript\s+)?(Error|SyntaxError|TypeError|ReferenceError|RangeError|EvalError|URIError):?\s*(.*?)\s*(at\s+.*)?(\s*URL:\s*(https?:\/\/\S+))?.*?(Line\s*:?[\s\d]+)?/i";
                                $apattern[] = "/(Error|Syntax|Type|TypeError|Reference|ReferenceError|Range|Eval|URI|Error .*?): (.*?) - URL: (https?:\/\/\S+).*?Line: (\d+).*?Column: (\d+).*?Error object: ({.*?})/";
                                $apattern[] = "/(SyntaxError|Error|Syntax|Type|TypeError|Reference|ReferenceError|Range|Eval|URI|Error .*?): (.*?) - URL: (https?:\/\/\S+).*?Line: (\d+)/";

                                $pattern = $apattern[0];
                                for ($j = 0; $j < count($apattern); $j++) {
                                    if (preg_match($apattern[$j], $line, $matches)) {
                                        $pattern = $apattern[$j];
                                        break;
                                    }
                                }

                                if (preg_match($pattern, $line, $matches)) {
                                    $error_types_count["JavaScript"]++;
                                }
                                $total_lines_processed++;
                                continue;
                            }

                            // Process PHP errors
                            $apattern = [];
                            $apattern[] = "/^\[.*\] PHP (Warning|Error|Notice|Fatal error|Parse error|Recoverable fatal error|Core error|Core warning|Compile error|Compile warning|Deprecated|Strict Standards|User Error|User Warning|User Notice|User Deprecated): (.*) in \/([^ ]+) on line (\d+)/";
                            $apattern[] = "/^\[.*\] PHP (Warning|Error|Notice|Fatal error|Parse error|Recoverable fatal error|Core error|Core warning|Compile error|Compile warning|Deprecated|Strict Standards|User Error|User Warning|User Notice|User Deprecated): (.*) in \/([^ ]+):(\d+)$/";

                            $pattern = $apattern[0];
                            for ($j = 0; $j < count($apattern); $j++) {
                                if (preg_match($apattern[$j], $line, $matches)) {
                                    $pattern = $apattern[$j];
                                    break;
                                }
                            }

                            if (preg_match($pattern, $line, $matches)) {
                                $error_type = $matches[1];
                                $error_description = $matches[2];

                                // Enhanced PHP error categorization
                                if (stripos($error_type, "deprecated") !== false) {
                                    $error_types_count["Deprecated"]++;
                                } elseif (
                                    stripos($error_type, "fatal") !== false &&
                                    stripos($error_type, "recoverable") === false
                                ) {
                                    $error_types_count["Fatal"]++;
                                } elseif (stripos($error_type, "recoverable") !== false) {
                                    $error_types_count["Recoverable"]++;
                                } elseif (
                                    stripos($error_type, "warning") !== false &&
                                    stripos($error_type, "core") === false &&
                                    stripos($error_type, "compile") === false &&
                                    stripos($error_type, "user") === false
                                ) {
                                    $error_types_count["Warning"]++;
                                } elseif (
                                    stripos($error_type, "notice") !== false &&
                                    stripos($error_type, "user") === false
                                ) {
                                    $error_types_count["Notice"]++;
                                } elseif (stripos($error_type, "parse") !== false) {
                                    $error_types_count["Parse"]++;
                                } elseif (stripos($error_type, "strict") !== false) {
                                    $error_types_count["Strict"]++;
                                } elseif (stripos($error_type, "core") !== false) {
                                    $error_types_count["Core"]++;
                                } elseif (stripos($error_type, "compile") !== false) {
                                    $error_types_count["Compile"]++;
                                } elseif (stripos($error_type, "user") !== false) {
                                    $error_types_count["User"]++;
                                } else {
                                    $error_types_count["Other"]++;
                                }

                                // Enhanced database error detection
                                if (preg_match("/(database|mysql|mysqli|wpdb|pdo|SQLSTATE|Query failed|Connection refused|Access denied|database connection|Unknown column|Syntax error in SQL)/i", $error_description)) {
                                    $error_types_count["Database"]++;
                                }

                                // Filesystem error detection
                                if (preg_match("/(fopen|fwrite|unlink|file_get_contents|permission denied|No such file)/i", $error_description)) {
                                    $error_types_count["Filesystem"]++;
                                }

                                // HTTP/API error detection
                                if (preg_match("/(curl|cURL error|HTTP error|timed out|Connection timed out|SSL)/i", $error_description)) {
                                    $error_types_count["HTTP_API"]++;
                                }
                            }

                            $total_lines_processed++;
                        }
                        fclose($file);
                    }
                }
            }
        }

        return $error_types_count;
    }

    /**
     * Helper to count processed files
     */
    private function get_processed_files_count($all_log_files, $lines_processed)
    {
        if ($lines_processed === 0) return 0;

        // Simple estimation based on whether we processed any lines
        $count = 0;
        foreach ($all_log_files as $file) {
            if ($lines_processed > 0) {
                $count++;
                $lines_processed -= 1000; // Approximate
                if ($lines_processed <= 0) break;
            }
        }
        return $count;
    }

    public function print_error_summary($error_types_count)
    {
        // ENHANCED FUNCTIONALITY: ERROR SUMMARY TABLE
        echo '<div style="border: 1px solid gray; background-color: #ffffff; padding: 10px; margin-top: 20px;">';
        echo '<h3 style="color: blue;">' .
            esc_attr__("Complete Error Summary - All Log Files", "antibots") .
            "</h3>";

        echo '<a href="https://wptoolsplugin.com/site-language-error-can-crash-your-site/">';
        echo esc_attr__("Learn More", "antibots");
        echo '</a>';
        echo '<br> <br>';

        echo '<table style="width: 100%; border-collapse: collapse;">';
        echo '<tr style="background-color: #f5f5f5;">';
        echo '<th style="border: 1px solid #ddd; padding: 8px; text-align: center;">' .
            esc_attr__("Error Type", "antibots") .
            "</th>";
        echo '<th style="border: 1px solid #ddd; padding: 8px; text-align: center;">' .
            esc_attr__("Count", "antibots") .
            "</th>";
        echo "</tr>";

        foreach ($error_types_count as $type => $count) {
            if ($count > 0) {
                echo "<tr>";
                echo '<td style="border: 1px solid #ddd; padding: 8px;">' .
                    esc_html($type) .
                    "</td>";
                echo '<td style="border: 1px solid #ddd; padding: 8px;">' .
                    esc_html($count) .
                    "</td>";
                echo "</tr>";
            }
        }

        echo "</table>";
        echo "</div>";
    }




    // //////////////////   Add Content
    public function site_health_tab_content($tab)
    {
        global $wpdb;
        if (!function_exists("antibots_bill_strip_strong99")) {
            function antibots_bill_strip_strong99($htmlString)
            {
                // return $htmlString;
                // Use preg_replace para remover as tags <strong>
                $textWithoutStrongTags = preg_replace(
                    "/<strong>(.*?)<\/strong>/i",
                    '$1',
                    $htmlString
                );
                return $textWithoutStrongTags;
            }
        }
        // Do nothing if this is not our tab.
        if ("Critical Issues" !== $tab) {
            return;
        }
?>
        <div class="wrap health-check-body privacy-settings-body" style="text-align: center; max-width: 800px; margin: 0 auto;">
            <div class="wrap health-check-body, privacy-settings-body">
                <p style="border: 1px solid red; padding: 10px;">
                    <strong>
                        <?php
                        echo esc_attr__(
                            "This report provides detailed health checks covering errors, memory, performance, database status, server config, updates, and security, courtesy of the plugin",
                            "antibots"
                        );
                        echo ": " . esc_attr($this->global_plugin_slug) . ". ";
                        echo esc_attr__(
                            "Disabling our plugin does not stop the errors from occurring; it simply means you will no longer be notified here that they are happening, but they can still harm your site.",
                            "antibots"
                        );
                        echo "<br>";
                        echo esc_attr__(
                            "Use the free chat below to get more instant details and tips on how to improve your site.",
                            "antibots"
                        );
                        ?>
                    </strong>
                </p>

                <div id="chat-box" class="stopbadbots-dashboard-chat-support-version stopbadbots-dashboard-new-chat-support">
                    <div id="chat-header">
                        <h2><?php echo esc_attr__("Artificial Intelligence 24X7 Support Chat for Issues and Solutions", "stopbadbots"); ?></h2>
                    </div>
                    <div id="gif-container">
                        <div class="spinner999"></div>
                    </div>

                    <div id="chat-messages" style="border-bottom: 1px solid #cccccc; padding: 10px;"></div>

                    <div id="error-message" style="display:none;"></div>

                    <form id="chat-form">
                        <div id="input-group">
                            <input type="text" id="chat-input" placeholder="<?php echo esc_attr__('Describe your issue, or use the buttons below to check for errors or server settings...', "stopbadbots"); ?>" />
                            <button type="submit" id="send-button" disabled><?php echo esc_attr__('Send', "stopbadbots"); ?></button>
                        </div>

                        <div class="auto-checkup-container" style="text-align: center; margin-top: 10px;">
                            <button type="button" id="auto-checkup" class="stopbadbots-dashboard-new-chat-button" disabled>
                                <img id="stopbadbots_img_robot" src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'robot2.png'); ?>" alt="" width="35" height="30">
                                <?php echo esc_attr__('Auto Checkup for Errors', "stopbadbots"); ?>
                            </button>
                            &nbsp;&nbsp;&nbsp;
                            <button type="button" id="auto-checkup2" class="stopbadbots-dashboard-new-chat-button" disabled>
                                <img id="stopbadbots_img_robot" src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'robot2.png'); ?>" alt="" width="35" height="30">
                                <?php echo esc_attr__('Auto Checkup Server ', "stopbadbots"); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <h2 style="color: red;">
                    <?php echo esc_attr__(
                        "Potential Problems",
                        "antibots"
                    ); ?>
                </h2>
                <div style="text-align: left;"> <!-- pai dos acordeos -->
                    <!--  // --------------------   Memory   -->
                    <div id="accordion1">
                        <?php
                        $wpmemory = $this->global_variable_memory;
                        $show_memory_info = true;
                        // --- Lógica de Verificação de Variáveis ---
                        if (empty($wpmemory) || !is_array($wpmemory)) {
                            $show_memory_info = false;
                        }
                        $required_keys = [
                            "wp_limit",
                            "usage",
                            "limit",
                            "free",
                            "percent",
                            "color",
                        ];
                        foreach ($required_keys as $key) {
                            if (!array_key_exists($key, $wpmemory)) {
                                $show_memory_info = false;
                            }
                        }
                        if ($show_memory_info) {
                            // --- NOVO: Defina a Condição de Alerta e a Cor ---
                            $header_color = esc_attr($wpmemory["color"]);
                            $header_text  = esc_attr__("WordPress Memory Status", "antibots");
                            $show_warning_content = false;
                            $warning_style = "";
                            // Condição para ALERTA: Memória Livre < 30MB OU Porcentagem de Uso > 85%
                            if ($wpmemory["free"] < 30 || $wpmemory["percent"] > 0.85) {
                                $header_color = "red"; // Define a cor vermelha
                                $header_text  = esc_attr__("Low WordPress Memory Limit - CRITICAL", "antibots"); // Altera o texto para alerta
                                $show_warning_content = true; // Indica que o conteúdo de aviso também deve ser exibido
                                $warning_style = 'style="color: red; background-color: #ffcccc; border: 1px solid red;"'; // NOVO: Cor de fundo e borda adicionadas
                            }
                        ?>
                            <h2 <?php echo $warning_style; ?>>
                                <?php echo $header_text; // Use a variável de texto definida 
                                ?>
                            </h2>
                            <div>
                                <b>
                                    <?php
                                    $mb = "MB";
                                    echo "WordPress Memory Limit: " .
                                        esc_attr($wpmemory["wp_limit"]) .
                                        esc_attr($mb) .
                                        "&nbsp;&nbsp;&nbsp;  |&nbsp;&nbsp;&nbsp;";
                                    // Aplica a cor de ALERTA no uso se a porcentagem for alta (originalmente 0.7)
                                    $usage_style = '';
                                    if ($wpmemory["percent"] > 0.7) {
                                        $usage_style = 'style="color:' . esc_attr($wpmemory["color"]) . ';"';
                                    }
                                    echo '<span ' . $usage_style . '>';
                                    echo esc_attr__("Your usage now", "antibots") .
                                        ": " .
                                        esc_attr($wpmemory["usage"]) .
                                        "MB &nbsp;&nbsp;&nbsp;";
                                    echo "</span>";
                                    echo "|&nbsp;&nbsp;&nbsp;" .
                                        esc_attr__("Total Php Server Memory", "antibots") .
                                        " : " .
                                        esc_attr($wpmemory["limit"]) .
                                        "MB";
                                    ?>
                                </b>
                                <?php
                                // --- Verifica se o limite do WordPress é maior que 256MB ---
                                // Converte o limite de string (ex: "512M") para inteiro (ex: 512)
                                $wp_memory_limit_int = intval($wpmemory["wp_limit"]);
                                if ($wp_memory_limit_int > 256) {
                                    // NOVO AVISO: Limite de memória > 256MB
                                ?>
                                    <hr>
                                    <p>
                                        <br>
                                        <?php
                                        // Usamos uma tag strong para dar mais destaque ao texto.
                                        echo '<big>';
                                        echo esc_html("More isn't always better. We recommend configuring the WordPress memory limit to a maximum of 256MB.", "antibots");
                                        echo '</big>';
                                        echo '<br>';
                                        echo '<strong>' . esc_html__(
                                            "A 256MB WP memory limit per PHP instance acts as a safety cap. It prevents a single memory leak—caused by a visitor or faulty plugin—from consuming all server RAM. This isolation protects the server from being overwhelmed, ensuring one problem doesn't crash the site or harm the experience for all other visitors.",
                                            "antibots"
                                        ) . '</strong>';
                                        ?>
                                    </p>
                                <?php
                                }
                                // --- Exibe o conteúdo de ALERTA apenas se a condição CRÍTICA for atendida ---
                                if ($show_warning_content) {
                                ?>
                                    <hr>
                                    <?php
                                    echo "<p>";
                                    echo "<br>";
                                    echo esc_attr__(
                                        "Your WordPress Memory Limit is too low, which can lead to critical issues on your site due to insufficient resources. Promptly address this issue before continuing.",
                                        "antibots"
                                    );
                                    echo "</p>";
                                    ?>
                                    <a href="https://wpmemory.com/fix-low-memory-limit/">
                                        <?php echo esc_attr__("Learn More", "antibots"); ?>
                                    </a>
                                <?php
                                } // Fim do if ($show_warning_content)
                                ?>
                            </div>
                        <?php
                        } // Fim do if ($show_memory_info)
                        ?>
                    </div>
                    <br>
                    <?php
                    /* --------------------- PAGE LOAD -----------------------------*/
                    ?>
                    <div id="accordion2">
                        <h2><?php echo esc_attr__("Page Speed Report", "antibots"); ?></h2>
                        <div>
                            <?php $this->site_health_check_page_load_main(); ?>
                        </div>
                    </div>
                    <br>
                    <div id="accordion3">
                        <h2>Database Health Check</h2>
                        <div>
                            <div id="database-checkup-content">Status: Not run.</div>
                        </div>
                    </div>
                    <br>
                    <?php
                    echo '<div id="accordion4">';
                    echo '<h2>' . esc_attr__("Server Health & Config Status", "antibots") . '</h2>';
                    //echo '<div>';
                    echo '<div id="autocheckup-content">'; // <-- ADICIONE O ID AQUI
                    echo 'Servers...wait...';
                    //$this->site_health_check_page_load();
                    // $this->site_health_check_page_load_main();
                    echo '</div>';
                    echo '</div>';
                    echo '<br>';
                    /* --------------------- UPDATES -----------------------------*/
                    echo '<div id="accordion5">';
                    echo '<h2>' . esc_attr__("Plugins & Themes Update Status", "antibots") . '</h2>';
                    echo '<div>';
                    //echo 'Updates...';
                    $this->site_health_check_updates();
                    echo '</div>';
                    echo '</div>';
                    echo '<br>';
                    // -------------------- BOTS & HACKERS  ---------------
                    $check_for_bots = true;
                    if (is_plugin_active("antibots/antibot.php")) {
                        $check_for_bots = false;
                    }
                    if (is_plugin_active("stopbadbots/stopbadbots.php")) {
                        $check_for_bots = false;
                    }
                    if (is_plugin_active("antihacker/antihacker.php")) {
                        $check_for_bots = false;
                    }
                    if ($check_for_bots) {
                        echo '<div id="accordion6">';
                        echo '<h2>' . esc_attr__("Bots & Hackers attack", "antibots") . '</h2>';
                        echo '<div>';
                        $this->site_health_bots_and_hackers();
                        echo '</div>';
                        echo '</div>';
                    }


                    echo '<div id="accordion7">';
                    echo '<h2>' . esc_attr__("Miscellaneous", "antibots") . '</h2>';
                    echo '<div>';
                    $checker = new misc_checker();
                    echo $checker->antibots_analyze_misc_checker();
                    echo '</div>';
                    echo '</div>';
                    echo '<br>';





                    echo "</div>"; //  <!-- end pai dos acordeos -->
                    //var_dump($this->global_variable_has_errors);
                    //
                    /* --------------------- ERRORS -----------------------------*/
                    if ($this->global_variable_has_errors) { ?>
                        <div id="site-errors">
                            <br>
                            <div style="border: 1px solid gray; background-color: #ffffff; padding: 10px;">
                                <h2 style="color: red;">
                                    <?php echo esc_attr__("Site Errors", "antibots"); ?>
                                </h2>
                                <p>
                                    <?php echo esc_attr__("Your site has experienced errors for the past 2 days. These errors, including JavaScript issues, can result in visual problems or disrupt functionality, ranging from minor glitches to critical site failures. JavaScript errors can terminate JavaScript execution, leaving all subsequent commands inoperable.", "antibots"); ?>
                                    <a href="https://wptoolsplugin.com/site-language-error-can-crash-your-site/">
                                        <?php echo esc_attr__("Learn More", "antibots"); ?>
                                    </a>
                                </p>
                                <?php

                                // Apenas chama list_errors() para exibir a lista parcial
                                $this->list_errors();

                                $start_time_total = microtime(true);

                                // Chama count_error_types() para obter contagens COMPLETAS
                                $error_types_count_completo = $this->count_error_types();

                                // Usa as contagens completas para o resumo
                                $this->print_error_summary($error_types_count_completo);

                                // Calcula e mostra o tempo TOTAL no final
                                $end_time_total = microtime(true);
                                $total_time = round(($end_time_total - $start_time_total) * 1000, 2);
                                echo "<div style='margin-top: 15px; font-size: 12px; color: #666; text-align: center;'>";
                                echo "TOTAL TIME: " . $total_time . " ms (count_error_types + display)";
                                echo "</div>";

                                ?>




                            </div>
                        </div>
            <?php } // end tem errors...


                    //echo "</div>"; // end id site errors
                    //echo "</div>";
                    echo "</div>"; // fecha div content...




                } // end function site_health_tab_content($tab)

                public function add_site_health_link_to_admin_toolbar($wp_admin_bar)
                {
                    $text_label = "Plugin Sentinel";
                    $active_class = ""; // Use a classe que você precisa, ou deixe vazio
                    $title_content =
                        // Ícone: Baseado no seu formato que funciona, com a classe ab-icon
                        '<span class="ab-icon dashicons dashicons-lightbulb' .
                        $active_class .
                        '"></span>' .
                        // Texto: Deve ter a classe .ab-label e vir logo após o ícone
                        '<span class="ab-label">' .
                        esc_html($text_label) .
                        "</span>";
                    // $pname = "Database Backup Plugin";
                    $pname = $this->global_plugin_slug;
                    //$tooltip_text = "The " . $pname . " Monitors server and configuration conditions for reliable plugin performance.";
                    $tooltip_text = sprintf(
                        esc_attr__(
                            "The %s Monitors server and configuration conditions for reliable plugin performance.",
                            "antibots"
                        ),
                        $pname
                    );

                    // $this->global_variable_has_errors = false;
                    //#0073aa
                    if ($this->global_variable_has_errors) {
                        $background_color = "red";
                    } else {
                        $background_color = "black";
                    }
                    $wp_admin_bar->add_node([
                        "id" => "plugin-sentinel",
                        "title" => $title_content,
                        "href" => false,
                        "meta" => [
                            "class" => "ab-site-monitor plugin-sentinel-tooltip",
                            "title" => esc_attr($tooltip_text),
                            // Usa a variável de cor definida acima.
                            "html" =>
                            "<style>#wp-admin-bar-plugin-sentinel .ab-item { background-color: " .
                                $background_color .
                                " !important; }</style>",
                        ],
                    ]);
                    // 4. Adiciona as Sub-Opções (Nós Filhos)
                    $dashboard_url1 =
                        admin_url("site-health.php?tab=Critical+Issues") . "#chat-box";

                    $wp_admin_bar->add_node([
                        "parent" => "plugin-sentinel",
                        "id" => "chat",
                        "title" => esc_html__("AI-Powered Instant Support", "antibots"), // Título traduzível
                        "href" => $dashboard_url1,
                        "meta" => [
                            "title" => esc_attr__(
                                "Chat for free with our AI support anytime, 24/7.",
                                "antibots"
                            ), // Tooltip traduzível
                        ],
                    ]);
                    $dashboard_url2 =
                        admin_url("site-health.php?tab=Critical+Issues") . "#site-errors";
                    // Opção 1: Errors and Warnings
                    $wp_admin_bar->add_node([
                        "parent" => "plugin-sentinel",
                        "id" => "ps-errors",
                        // TITLE: Conciso e listando o que está sendo monitorado
                        "title" => esc_html__("Errors, Warnings, & JS Issues", "antibots"),
                        "href" => $dashboard_url2,
                        "meta" => [
                            // TOOLTIP: Explicação do que o painel exibe
                            "title" => esc_attr__(
                                "View all PHP errors, system warnings, and JavaScript issues.",
                                "antibots"
                            ),
                        ],
                    ]);
                    // Opção 2: Memory
                    // Modifique esta linha:
                    $dashboard_url1 =
                        admin_url("site-health.php?tab=Critical+Issues") . "#accordion1";
                    $wp_admin_bar->add_node([
                        "parent" => "plugin-sentinel",
                        "id" => "ps-memory",
                        // TITLE: Focado na capacidade vital do WP
                        "title" => esc_html__("WordPress Memory Status", "antibots"),
                        "href" => $dashboard_url1,
                        "meta" => [
                            // TOOLTIP: Menciona o limite e o uso
                            "title" => esc_attr__(
                                "View your WordPress memory limit and current consumption.",
                                "antibots"
                            ),
                        ],
                    ]);
                    // Opção 3: Page Load Speed
                    $dashboard_url =
                        admin_url("site-health.php?tab=Critical+Issues") . "#accordion2";
                    $wp_admin_bar->add_node([
                        "parent" => "plugin-sentinel",
                        "id" => "ps-speed",
                        // TITLE: Focado no Relatório (Análise)
                        "title" => esc_html__("Page Speed Report", "antibots"),
                        "href" => $dashboard_url,
                        "meta" => [
                            // TOOLTIP: Deixa claro que ele mede e sugere ações.
                            "title" => esc_attr__(
                                "View page load metrics and suggested actions for improvement.",
                                "antibots"
                            ),
                        ],
                    ]);
                    $dashboard_url =
                        admin_url("site-health.php?tab=Critical+Issues") . "#accordion3";
                    // Opção 4: Database Health and Performance
                    $wp_admin_bar->add_node([
                        "parent" => "plugin-sentinel",
                        "id" => "ps-database",
                        // TITLE: Focado no Status e Verificação
                        "title" => esc_html__("Database Health Check", "antibots"),
                        "href" => $dashboard_url,
                        "meta" => [
                            // TOOLTIP: Deixa claro que ele verifica e reporta
                            "title" => esc_attr__(
                                "View the status of database tables and identify potential issues.",
                                "antibots"
                            ),
                        ],
                    ]);
                    $dashboard_url =
                        admin_url("site-health.php?tab=Critical+Issues") . "#accordion4";
                    $wp_admin_bar->add_node([
                        "parent" => "plugin-sentinel",
                        "id" => "ps-server",
                        // TITLE: Focado no Status de Funcionamento do Servidor
                        "title" => esc_html__("Server Health & Config Status", "antibots"),
                        "href" => $dashboard_url,
                        "meta" => [
                            // TOOLTIP: Menciona recursos (Recursos) e configuração
                            "title" => esc_attr__(
                                "Check server resources, configuration status, and critical environment variables.",
                                "antibots"
                            ),
                        ],
                    ]);
                    $dashboard_url =
                        admin_url("site-health.php?tab=Critical+Issues") . "#accordion5";
                    // Opção 6: Configuration Status
                    $wp_admin_bar->add_node([
                        "parent" => "plugin-sentinel",
                        "id" => "ps-config",
                        // TITLE: Focado no Status e necessidade de Ação
                        "title" => esc_html__("Plugin & Themes Update Status", "antibots"),
                        "href" => $dashboard_url,
                        "meta" => [
                            // TOOLTIP: Menciona a verificação de segurança (crucial para o tema do plugin)
                            "title" => esc_attr__(
                                "Check for available plugins & themes updates and security risks.",
                                "antibots"
                            ),
                        ],
                    ]);

                    // include_once(ABSPATH . 'wp-admin/includes/plugin.php');

                    if (function_exists("is_plugin_active")) {
                        $add_node = true;
                        if (is_plugin_active("antibots/antibots.php")) {
                            $add_node = false;
                        }

                        if (is_plugin_active("stopbadbots/stopbadbots.php")) {
                            $add_node = false;
                        }

                        if (is_plugin_active("antihacker/antihacker.php")) {
                            $add_node = false;
                        }

                        if ($add_node) {
                            // Opção 7: Suspiscius
                            $dashboard_url =
                                admin_url("site-health.php?tab=Critical+Issues") .
                                "#accordion6";
                            $wp_admin_bar->add_node([
                                "parent" => "plugin-sentinel",
                                "id" => "ps-bots",
                                // TITLE: Focused on the Threat Report
                                "title" => esc_html__(
                                    "Attack & Bot Security Report",
                                    "antibots"
                                ),
                                "href" => $dashboard_url,
                                "meta" => [
                                    // TOOLTIP: Mentions attack stats and suspicious activity.
                                    "title" => esc_attr__(
                                        "View statistics on detected bots and hacker attacks.",
                                        "antibots"
                                    ),
                                ],
                            ]);
                        }
                    }
                }
                public function custom_help_tab()
                {
                    $screen = get_current_screen();
                    // Verifique se você está na página desejada
                    if ("site-health" === $screen->id) {
                        // Adicione uma guia de ajuda
                        $message = esc_attr__(
                            "These are critical issues that can have a significant impact on your site's performance. They can cause many plugins and functionalities to malfunction and, in some cases, render your site completely inoperative, depending on their severity. Address them promptly.",
                            "antibots"
                        );
                        $screen->add_help_tab([
                            "id" => "custom-help-tab",
                            "title" => esc_attr__("Critical Issues", "antibots"),
                            "content" => "<p>" . $message . "</p>",
                        ]);
                    }
                }
                // add_action("admin_head", "custom_help_tab");
            } // end class antibots_Bill_Diagnose

            $diagnose_instance = antibots_Bill_Diagnose::get_instance(
                $notification_url,
                $notification_url2
            );
            update_option("antibots_bill_show_warnings", date("Y-m-d"));
