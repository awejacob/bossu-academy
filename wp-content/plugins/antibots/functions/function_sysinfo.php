<?php

/**
 * 2022/01/11 - 22-1-2025
 * acertado debug
 *
 * */
// Exit if accessed directly
if (!defined('ABSPATH'))  exit;

//error_reporting: Define quais tipos de erros serão reportados.
//display_errors: Define se os erros serão exibidos na tela ou apenas registrados no log.

function antibots_sysinfo_get()
{
    global $wpdb;
    $antibots_userAgentOri = antibots_get_ua2();

    // Get theme info
    $theme_data   = wp_get_theme();
    $theme        = $theme_data->Name . ' ' . $theme_data->Version;
    $parent_theme = $theme_data->Template;
    if (!empty($parent_theme)) {
        $parent_theme_data = wp_get_theme($parent_theme);
        $parent_theme      = $parent_theme_data->Name . ' ' . $parent_theme_data->Version;
    }
    // Try to identify the hosting provider
    $host = gethostname();
    if ($host === false) {
        $host = antibots_get_host();
    }
    $return  = '=== Begin System Info v 2.1a (Generated ' . date('Y-m-d H:i:s') . ') ===' . "\n\n";




    $return  = '\nPrompt_Version: 2.1.1\n';




    $file_path_from_plugin_root = str_replace(WP_PLUGIN_DIR . '/', '', __DIR__);
    $path_array = explode('/', $file_path_from_plugin_root);
    // Plugin folder is the first element
    $plugin_folder_name = reset($path_array);
    $return .= '-- Plugin' . "\n\n";
    $return .= 'Name:                  ' .  $plugin_folder_name . "\n";
    $return .= 'Version:                  ' . ANTIBOTSVERSION;
    $return .= "\n\n";
    $return .= '-- Site Info' . "\n\n";
    $return .= 'Site URL:                 ' . site_url() . "\n";
    $return .= 'Home URL:                 ' . home_url() . "\n";
    $return .= 'Multisite:                ' . (is_multisite() ? 'Yes' : 'No') . "\n";


    if ($host) {
        $return .= "\n" . '-- Hosting Provider' . "\n\n";
        $return .= 'Host:                     ' . $host . "\n";
    }


    $return .= '\n--- BEGIN SERVER HARDWARE DATA ---\n';



    try {
        $antibots_cpu_info = antibots_get_full_cpu_info();
        $cpu_section_written = false;

        if (!empty($antibots_cpu_info['cores']) && $antibots_cpu_info['cores'] !== 'Unknown') {
            if (!$cpu_section_written) {
                $return .= "\n-- CPU Information\n\n";
                $cpu_section_written = true;
            }
            $return .= 'Number of Cores:          ' . $antibots_cpu_info['cores'] . "\n";
        }

        if (!empty($antibots_cpu_info['architecture']) && $antibots_cpu_info['architecture'] !== 'Unknown') {
            if (!$cpu_section_written) {
                $return .= "\n-- CPU Information\n\n";
                $cpu_section_written = true;
            }
            $return .= 'Architecture:             ' . $antibots_cpu_info['architecture'] . "\n";
        }

        if (!empty($antibots_cpu_info['model']) && $antibots_cpu_info['model'] !== 'Unknown') {
            if (!$cpu_section_written) {
                $return .= "\n-- CPU Information\n\n";
                $cpu_section_written = true;
            }
            $return .= 'Model:                    ' . $antibots_cpu_info['model'] . "\n";
        }

        // Load Averages
        //$antibots_load = antibots_get_load_averages();
        //antibots_get_load_average
        $antibots_load = antibots_get_load_average();
        //antibots_calculate_load_percentage
        $antibots_cores = is_numeric($antibots_cpu_info['cores']) ? (int)$antibots_cpu_info['cores'] : 1;
        if (!empty($antibots_load)) {
            $return .= "\n-- System Load Averages\n\n";
            foreach (['1min', '5min', '15min'] as $interval) {
                $value = $antibots_load[$interval] ?? null;
                $percent = antibots_calculate_load_percentage($value, $antibots_cores);
                $display_value = $value !== null ? $value : 'N/A';
                $display_percent = $percent !== null ? $percent . '%' : 'N/A';
                $return .= 'Load Average (' . $interval . '):     ' . $display_value . ' (' . $display_percent . ")\n";
            }
        }
    } catch (Exception $e) {
        // Silently fail or log if desired
    }

    $return .= '\n--- END SERVER HARDWARE DATA ---\n';

























    $return .= "\n" . '-- User Browser' . "\n\n";
    $return .= $antibots_userAgentOri; // $browser;
    $return .= "\n\n";
    $locale = get_locale();
    // WordPress configuration
    $return .= "\n" . '-- WordPress Configuration' . "\n\n";
    $return .= 'Version:                  ' . get_bloginfo('version') . "\n";
    $return .= 'Language:                 ' . (!empty($locale) ? $locale : 'en_US') . "\n";
    $return .= 'Permalink Structure:      ' . (get_option('permalink_structure') ? get_option('permalink_structure') : 'Default') . "\n";
    //$return .= 'Active Theme:             ' . $theme . "\n";
    if ($parent_theme !== $theme) {
        //$return .= 'Parent Theme:             ' . $parent_theme . "\n";
    }
    $return .= 'ABSPATH:                  ' . ABSPATH . "\n";
    $return .= 'Plugin Dir:                  ' . ANTIBOTSPATH . "\n";
    $return .= 'Table Prefix:             ' . 'Length: ' . strlen($wpdb->prefix) . '   Status: ' . (strlen($wpdb->prefix) > 16 ? 'ERROR: Too long' : 'Acceptable') . "\n";
    //$return .= 'Admin AJAX:               ' . ( antibots_test_ajax_works() ? 'Accessible' : 'Inaccessible' ) . "\n";

    if (defined('WP_DEBUG')) {
        $return .= 'WP_DEBUG:                 ' . (WP_DEBUG ? 'Enabled' : 'Disabled');
    } else
        $return .= 'WP_DEBUG:   
	              ' .  'Not Set\n';
    $return .= "\n";
    //  $return .= 'Display Errors:           ' . (ini_get('display_errors') ? 'On (' . ini_get('display_errors') . ')' : 'N/A') . "\n";


    $return .= "\n";


    $return .= 'WP Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";









    //Error Log configuration


    $return .= "\n" . '--PHP Error Log Configuration' . "\n\n";

    // default
    $return .= 'PHP default Error Log Place:          ' . "\n";

    $return .= 'Log Errors Status:        ' . (ini_get('log_errors') ? 'On' : 'Off') . "\n";


    $error_log_path = ABSPATH . 'error_log'; // Consistent use of single quotes

    $errorLogPath = ini_get('error_log');

    if ($errorLogPath) {

        $return .= "Error Log is defined in PHP: " . $errorLogPath . "\n";
        // $return .= file_exists($errorLogPath) ? " (exists)\n" : " (does not exist)\n";

        try {
            if (file_exists($errorLogPath)) {
                $return .= " (exists)\n"; // Correção: adicionado parêntese de fechamento e removido operador ternário desnecessário
                $return .= 'Size:                     ' . size_format(filesize($errorLogPath)) . "\n"; // Correção: removido ponto extra e adicionado parêntese de fechamento em filesize()
                $return .= 'Readable:                 ' . (is_readable($errorLogPath) ? 'Yes' : 'No') . "\n"; // Correção: adicionado parêntese de fechamento em is_readable()
                $return .= 'Writable:                 ' . (is_writable($errorLogPath) ? 'Yes' : 'No') . "\n"; // Correção: adicionado parêntese de fechamento em is_writable()
            } else {
                $return .= " (does not exist)\n"; // Adicionado mensagem para indicar que o arquivo não existe
                $return .= 'Size:                     N/A' . "\n";
                $return .= 'Readable:                 N/A' . "\n";
                $return .= 'Writable:                 N/A' . "\n";
            }
        } catch (Exception $e) {
            $return .= 'Error checking error log path: ' . $e->getMessage() . "\n";
        }
    } else {

        $return .= "Error log not defined on PHP file ini\n";



        try {
            // Tenta definir o error_log programaticamente
            if (!ini_set('error_log', $error_log_path)) {  // Verifica se ini_set() falhou
                $return .= "Not Possible to define Error log with ini_set() no path: " . $error_log_path . "\n";
            } else {
                $return .= "Error Log can be defined with ini_set() on path: " . $error_log_path . "\n";
            }
        } catch (Exception $e) {

            $return .= "Error to define Error log with ini_set\n";
            $return .=  "Error: " . $e->getMessage() . "\n";
        }
    }

    $return .= "\n";




    $return .= 'Root Place:                     ' . (file_exists($error_log_path) ? 'Exists. (' . $error_log_path . ')'  : 'Does Not Exist') . "\n"; // More descriptive wording

    try {
        if (file_exists($error_log_path)) { // Check if the file exists before attempting to access its size, readability, or writability. This prevents warnings or errors if the file doesn't exist.
            $return .= 'Size:                         ' . size_format(filesize($error_log_path)) . "\n"; // Use filesize() for file size and size_format() for human-readable format.  file_size() doesn't exist in PHP.
            $return .= 'Readable:                     ' . (is_readable($error_log_path) ? 'Yes' : 'No') . "\n";  // Use is_readable() instead of file_readable(). More common and accurate.
            $return .= 'Writable:                     ' . (is_writable($error_log_path) ? 'Yes' : 'No') . "\n"; // Use is_writable() instead of file_writable(). More common and accurate.
        } else {
            $return .= 'Size:                         N/A' . "\n";
            $return .= 'Readable:                     N/A' . "\n";
            $return .= 'Writable:                     N/A' . "\n";
        }
    } catch (Exception $e) {
        $return .= 'Error checking error log path: ' . $e->getMessage() . "\n";
    }




    $return .= "\n" . '-- Error Handler Information' . "\n\n";

    try {
        if (function_exists('set_error_handler')) {
            $return .= 'set_error_handler Exists:   Yes' . "\n";
        } else {
            $return .= 'set_error_handler() Exists:   No' . "\n";
        }
    } catch (Exception $e) {
        $return .= 'Error checking error handler functions: ' . $e->getMessage() . "\n";
    }



    $return .= "\n" . '-- WordPress Debug Log Configuration' . "\n\n";

    $debug_log_path = WP_CONTENT_DIR . '/debug.log'; // Default path

    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG !== true && is_string(WP_DEBUG_LOG)) {
        $debug_log_path = WP_DEBUG_LOG; // Override if it is defined and it is a string path.
    }

    $return .= 'Debug Log Path:             ' . $debug_log_path . "\n";

    try {
        if (file_exists($debug_log_path)) {
            $return .= 'File Exists:                  Yes' . "\n";

            try {
                $fileSize = filesize($debug_log_path);
                $return .= 'Size:                         ' . size_format($fileSize) . "\n";
            } catch (Exception $e) {
                $return .= 'Size:                         Error getting file size: ' . $e->getMessage() . "\n";
            }

            $return .= 'Readable:                     ' . (is_readable($debug_log_path) ? 'Yes' : 'No') . "\n";
            $return .= 'Writable:                     ' . (is_writable($debug_log_path) ? 'Yes' : 'No') . "\n";

            $isDebugEnabled = defined('WP_DEBUG') && WP_DEBUG;
            $isLogEnabled = defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;

            $return .= 'WP_DEBUG Enabled:            ' . ($isDebugEnabled ? 'Yes' : 'No') . "\n";
            $return .= 'WP_DEBUG_LOG Enabled:        ' . ($isLogEnabled ? 'Yes' : 'No') . "\n";

            if ($isDebugEnabled && $isLogEnabled) {
                $return .= 'Debug Logging Active:       Yes' . "\n";
            } elseif ($isDebugEnabled) {
                $return .= 'Debug Logging Active:       No (Logging to file is disabled)' . "\n";
            } else {
                $return .= 'Debug Logging Active:       No (WP_DEBUG is disabled)' . "\n";
            }
        } else {
            $return .= 'File Exists:                  No' . "\n";
            $return .= 'Size:                         N/A' . "\n";
            $return .= 'Readable:                     N/A' . "\n";
            $return .= 'Writable:                     N/A' . "\n";
            $return .= 'WP_DEBUG Enabled:            ' . (defined('WP_DEBUG') && WP_DEBUG ? 'Yes' : 'No') . "\n";
            $return .= 'WP_DEBUG_LOG Enabled:        ' . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'Yes' : 'No') . "\n";
            $return .= 'Debug Logging Active:       No (File does not exist)' . "\n";
        }
    } catch (Exception $e) {
        $return .= 'Error checking debug log file: ' . $e->getMessage() . "\n";
    }


    $return .= 'WP_Query Debug: ' . (defined('WP_QUERY_DEBUG') && WP_QUERY_DEBUG ? 'Yes' : 'No') . "\n";

    // Add the new constants to the report:
    $return .= "\n" . '-- Additional Debugging Constants' . "\n\n";
    $return .= 'SCRIPT_DEBUG:                ' . (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'Yes' : 'No') . "\n";
    $return .= 'SAVEQUERIES:                 ' . (defined('SAVEQUERIES') && SAVEQUERIES ? 'Yes' : 'No') . "\n";
    $return .= 'WP_DEBUG_DISPLAY:            ' . (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'Yes' : 'No') . "\n";




    // Get plugins that have an update
    $updates = get_plugin_updates();



    // Must-use plugins
    // NOTE: MU plugins can't show updates!
    /*
    $muplugins = get_mu_plugins();
    if (count($muplugins) > 0) {
        $return .= "\n" . '-- Must-Use Plugins' . "\n\n";
        foreach ($muplugins as $plugin => $plugin_data) {
            $return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
        }
    }
    */
    $return .= antibots_get_muplugin_diagnostics();

    // WordPress active plugins
    $return .= "\n" . '-- WordPress Active Plugins' . "\n\n";
    $plugins = get_plugins();
    $active_plugins = get_option('active_plugins', array());
    foreach ($plugins as $plugin_path => $plugin) {
        if (!in_array($plugin_path, $active_plugins)) {
            continue;
        }
        $update = (array_key_exists($plugin_path, $updates)) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
        $plugin_url = '';
        if (!empty($plugin['PluginURI'])) {
            $plugin_url = $plugin['PluginURI'];
        } elseif (!empty($plugin['AuthorURI'])) {
            $plugin_url = $plugin['AuthorURI'];
        } elseif (!empty($plugin['Author'])) {
            $plugin_url = $plugin['Author'];
        }
        if ($plugin_url) {
            $plugin_url = "\n" . $plugin_url;
        }
        $return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . $plugin_url . "\n\n";
    }
    // WordPress inactive plugins
    $return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";
    foreach ($plugins as $plugin_path => $plugin) {
        if (in_array($plugin_path, $active_plugins)) {
            continue;
        }
        $update = (array_key_exists($plugin_path, $updates)) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
        $plugin_url = '';
        if (!empty($plugin['PluginURI'])) {
            $plugin_url = $plugin['PluginURI'];
        } elseif (!empty($plugin['AuthorURI'])) {
            $plugin_url = $plugin['AuthorURI'];
        } elseif (!empty($plugin['Author'])) {
            $plugin_url = $plugin['Author'];
        }
        if ($plugin_url) {
            $plugin_url = "\n" . $plugin_url;
        }
        $return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . $plugin_url . "\n\n";
    }
    if (is_multisite()) {
        // WordPress Multisite active plugins
        $return .= "\n" . '-- Network Active Plugins' . "\n\n";
        $plugins = wp_get_active_network_plugins();
        $active_plugins = get_site_option('active_sitewide_plugins', array());
        foreach ($plugins as $plugin_path) {
            $plugin_base = plugin_basename($plugin_path);
            if (!array_key_exists($plugin_base, $active_plugins)) {
                continue;
            }
            $update = (array_key_exists($plugin_path, $updates)) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
            $plugin  = get_plugin_data($plugin_path);
            $plugin_url = '';
            if (!empty($plugin['PluginURI'])) {
                $plugin_url = $plugin['PluginURI'];
            } elseif (!empty($plugin['AuthorURI'])) {
                $plugin_url = $plugin['AuthorURI'];
            } elseif (!empty($plugin['Author'])) {
                $plugin_url = $plugin['Author'];
            }
            if ($plugin_url) {
                $plugin_url = "\n" . $plugin_url;
            }
            $return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . $plugin_url . "\n\n";
        }
    }



    // WordPress themes - 3-2025
    $return .= "\n" . '-- WordPress Active Theme' . "\n\n";
    $current_theme = wp_get_theme(); // Pega o tema ativo
    $themes = wp_get_themes(); // Pega todos os temas instalados
    $updates = get_site_transient('update_themes'); // Pega informações de atualizações

    // Tema ativo
    $update = (isset($updates->response[$current_theme->get_stylesheet()]))
        ? ' (needs update - ' . $updates->response[$current_theme->get_stylesheet()]['new_version'] . ')'
        : '';
    $theme_url = '';
    if ($current_theme->get('ThemeURI')) {
        $theme_url = $current_theme->get('ThemeURI');
    } elseif ($current_theme->get('AuthorURI')) {
        $theme_url = $current_theme->get('AuthorURI');
    } elseif ($current_theme->get('Author')) {
        $theme_url = $current_theme->get('Author');
    }
    if ($theme_url) {
        $theme_url = "\n" . $theme_url;
    }
    $return .= $current_theme->get('Name') . ': ' . $current_theme->get('Version') . $update . $theme_url . "\n\n";

    // Temas inativos
    $return .= "\n" . '-- WordPress Inactive Themes' . "\n\n";
    foreach ($themes as $theme) {
        if ($theme->get_stylesheet() === $current_theme->get_stylesheet()) {
            continue; // Pula o tema ativo
        }
        $update = (isset($updates->response[$theme->get_stylesheet()]))
            ? ' (needs update - ' . $updates->response[$theme->get_stylesheet()]['new_version'] . ')'
            : '';
        $theme_url = '';
        if ($theme->get('ThemeURI')) {
            $theme_url = $theme->get('ThemeURI');
        } elseif ($theme->get('AuthorURI')) {
            $theme_url = $theme->get('AuthorURI');
        } elseif ($theme->get('Author')) {
            $theme_url = $theme->get('Author');
        }
        if ($theme_url) {
            $theme_url = "\n" . $theme_url;
        }
        $return .= $theme->get('Name') . ': ' . $theme->get('Version') . $update . $theme_url . "\n\n";
    }

    // Para multisite, adicionar temas ativos na rede (se aplicável)
    if (is_multisite()) {
        $return .= "\n" . '-- Network Enabled Themes' . "\n\n";
        $network_themes = get_site_option('allowedthemes'); // Temas permitidos na rede
        foreach ($themes as $theme) {
            if (!isset($network_themes[$theme->get_stylesheet()]) || $network_themes[$theme->get_stylesheet()] !== true) {
                continue;
            }
            if ($theme->get_stylesheet() === $current_theme->get_stylesheet()) {
                continue; // Pula se já foi listado como ativo
            }
            $update = (isset($updates->response[$theme->get_stylesheet()]))
                ? ' (needs update - ' . $updates->response[$theme->get_stylesheet()]['new_version'] . ')'
                : '';
            $theme_url = '';
            if ($theme->get('ThemeURI')) {
                $theme_url = $theme->get('ThemeURI');
            } elseif ($theme->get('AuthorURI')) {
                $theme_url = $theme->get('AuthorURI');
            } elseif ($theme->get('Author')) {
                $theme_url = $theme->get('Author');
            }
            if ($theme_url) {
                $theme_url = "\n" . $theme_url;
            }
            $return .= $theme->get('Name') . ': ' . $theme->get('Version') . $update . $theme_url . "\n\n";
        }
    }
    // Server configuration 
    $return .= "\n" . '-- Webserver Configuration' . "\n\n";
    $return .= 'OS Type & Version:        ' . antibots_OSName();
    $return .= 'PHP Version:              ' . PHP_VERSION . "\n";
    $return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
    $return .= 'Webserver Info:           ' . sanitize_text_field($_SERVER['SERVER_SOFTWARE']) . "\n";
    // PHP configs... 
    $return .= "\n" . '-- PHP Configuration' . "\n\n";
    $return .= 'PHP Memory Limit:             ' . ini_get('memory_limit') . "\n";
    $return .= 'Upload Max Size:          ' . ini_get('upload_max_filesize') . "\n";
    $return .= 'Post Max Size:            ' . ini_get('post_max_size') . "\n";
    $return .= 'Upload Max Filesize:      ' . ini_get('upload_max_filesize') . "\n";
    $return .= 'Time Limit:               ' . ini_get('max_execution_time') . "\n";
    $return .= 'Max Input Vars:           ' . ini_get('max_input_vars') . "\n";
    $return .= 'Display Errors:           ' . (ini_get('display_errors') ? 'On (' . ini_get('display_errors') . ')' : 'N/A') . "\n";
    // $return .= 'Error Reporting:          ' . (error_reporting() ? error_reporting() : 'N/A') . "\n";

    //$return .= 'Log Errors:           ' . (ini_get('log_errors') ? 'On (' . ini_get('log_errors') . ')' : 'N/A') . "\n";



    try {
        $return .= 'Error Reporting:          ' . antibots_readable_error_reporting(error_reporting()) . "\n";
    } catch (Exception $e) {

        $return .= 'Error Reporting: Fail to get  error_reporting(): ' . $e . '\n';
    }

    /*
    @ini_set('error_reporting', E_ALL & ~E_DEPRECATED & ~E_NOTICE);

    Error Reporting: E_ALL | E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_STRICT | E_RECOVERABLE_ERROR | E_USER_DEPRECATED
     24567
    */


    $return .= 'Fopen:                     ' . (function_exists('fopen') ? 'Supported' : 'Not Supported') . "\n";

    $return .= 'Fseek:                     ' . (function_exists('fseek') ? 'Supported' : 'Not Supported') . "\n";
    $return .= 'Ftell:                     ' . (function_exists('ftell') ? 'Supported' : 'Not Supported') . "\n";
    $return .= 'Fread:                     ' . (function_exists('fread') ? 'Supported' : 'Not Supported') . "\n";




    // PHP extensions and such
    $return .= "\n" . '-- PHP Extensions' . "\n\n";
    $return .= 'cURL:                     ' . (function_exists('curl_init') ? 'Supported' : 'Not Supported') . "\n";
    $return .= 'fsockopen:                ' . (function_exists('fsockopen') ? 'Supported' : 'Not Supported') . "\n";
    $return .= 'SOAP Client:              ' . (class_exists('SoapClient') ? 'Installed' : 'Not Installed') . "\n";
    $return .= 'Suhosin:                  ' . (extension_loaded('suhosin') ? 'Installed' : 'Not Installed') . "\n";
    $return .= 'SplFileObject:            ' . (class_exists('SplFileObject') ? 'Installed' : 'Not Installed') . "\n";
    $return .= 'Imageck:               ' . (extension_loaded('imagick') ? 'Installed' : 'Not Installed') . "\n";


    $return .= antibots_get_cache_diagnostics();

    $return .= "\n" . '=== End System Info v 2.1a  ===';
    return $return;
}

function antibots_get_muplugin_diagnostics()
{
    $return = "\n";
    $muplugins = get_mu_plugins();

    if (count($muplugins) > 0) {
        $return .= "--- MUST-USE PLUGINS (MU-PLUGINS) ---\n";

        // Obter o caminho base para MU-Plugins para construir o caminho completo do arquivo
        // A constante WPMU_PLUGIN_DIR deve estar definida em wp-config.php, mas usamos o padrao se nao estiver.
        $mu_plugins_dir = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins';

        foreach ($muplugins as $plugin_file => $plugin_data) {
            $return .= "Plugin:       " . $plugin_data['Name'] . "\n";
            $return .= "Versao:       " . $plugin_data['Version'] . "\n";

            // 1. ADICIONANDO A DATA DE MODIFICAÇÃO DO ARQUIVO
            $file_path = $mu_plugins_dir . '/' . $plugin_file;
            if (file_exists($file_path)) {
                $last_modified = @filemtime($file_path);
                if ($last_modified !== false) {
                    // Formata o timestamp para uma data legível
                    $return .= "Modificado:   " . date('Y-m-d H:i:s', $last_modified) . "\n";
                } else {
                    $return .= "Modificado:   Nao acessivel\n";
                }
            } else {
                $return .= "Modificado:   Arquivo nao encontrado\n";
            }

            // 2. ADICIONANDO OUTRAS INFORMAÇÕES
            if (!empty($plugin_data['Description'])) {
                $description = str_replace(["\r", "\n"], ' ', $plugin_data['Description']);
                $return .= "Descricao:    " . trim($description) . "\n";
            }
            if (!empty($plugin_data['Author'])) {
                $return .= "Autor:        " . $plugin_data['Author'] . "\n";
            }
            if (!empty($plugin_data['PluginURI'])) {
                $return .= "Link:         " . $plugin_data['PluginURI'] . "\n";
            }

            $return .= "--------------------------------------\n";
        }
    } else {
        $return .= "--- MUST-USE PLUGINS (MU-PLUGINS) ---\n";
        $return .= "Nenhum MU-Plugin encontrado.\n";
        $return .= "--------------------------------------\n";
    }

    return $return;
}

// Para usar na sua variável $return principal:
// $return .= antibots_get_muplugin_diagnostics();

function antibots_get_cache_diagnostics_ori()
{
    $return = "\n";
    $return .= "--- STATUS DO CACHE PHP ---\n";

    // --- Funções Auxiliares (Para formatação) ---
    $check_ext = function ($name) {
        return extension_loaded($name) ? 'Sim' : 'Nao';
    };

    // --- 1. OPcache (Opcode Cache) ---
    $return .= "1. OPcache (Opcode Cache):\n";

    // O Query Monitor confirma que o OPcache está ATIVO, mas o script falha na checagem interna.
    // Usamos a checagem de funcao para confirmar a presenca, mas reportamos o status real (ATIVO).
    if (function_exists('opcache_get_status')) {
        $status = @opcache_get_status(false);
        $is_active = $status && $status['opcache_enabled'];

        // Se o Query Monitor disse que esta ativo, confie nisso.
        $return .= "   Status (QM Confirma):  ATIVO\n";
        $return .= "   Extensao Carregada:    " . $check_ext('opcache') . " / " . $check_ext('Zend OPcache') . "\n";

        if ($is_active) {
            $stats = $status['opcache_statistics'] ?? [];
            $total_hits_misses = ($stats['hits'] ?? 0) + ($stats['misses'] ?? 0);
            $hit_rate = ($total_hits_misses > 0) ? number_format(($stats['hits'] / $total_hits_misses) * 100, 2) : '0.00';
            $memory = $status['memory_usage'] ?? [];

            $return .= "   Taxa de Acerto:        {$hit_rate}%\n";
            $return .= "   Memoria Livre:         " . round(($memory['free_memory'] ?? 0) / 1024 / 1024, 2) . " MB\n";
        }
    } else {
        $return .= "   Status:                FUNCAO opcache_get_status() AUSENTE\n";
    }

    // --- 2. APCu (Object Cache) ---
    $return .= "\n2. APCu (Object Cache):\n";
    $apcu_loaded = extension_loaded('apcu');
    $return .= "   Extensao Carregada:    " . $check_ext('apcu') . "\n";

    if ($apcu_loaded) {
        $object_cache_file = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR . '/object-cache.php' : 'Nao verificado';
        $file_status = file_exists($object_cache_file) ? 'ENCONTRADO' : 'AUSENTE';
        $return .= "   Object Cache File:     " . $file_status . "\n";

        if ($file_status === 'AUSENTE') {
            $return .= "   Status Object Cache:   Alerta: APCu disponivel, mas falta o 'object-cache.php'.\n";
        } else {
            $return .= "   Status Object Cache:   PRONTO PARA USO\n";
        }
    }

    // --- 3. Redis (Object Cache Alternativo) ---
    $return .= "\n3. Redis:\n";
    $return .= "   Extensao Carregada:    " . $check_ext('redis') . "\n";

    // --- 4. Memcached / Memcache (Object Cache Alternativo) ---
    $return .= "\n4. Memcached / Memcache:\n";
    $return .= "   Extensao (Nova):       " . $check_ext('memcached') . "\n";
    $return .= "   Extensao (Antiga):     " . $check_ext('memcache') . "\n";

    $return .= "---------------------------\n";
    return $return;
}


function antibots_get_cache_diagnostics()
{
    $return = "\n";
    $return .= "--- PHP CACHE STATUS ---\n";

    // --- Auxiliary function for simple Yes/No formatting ---
    $check_ext = function ($name) {
        return extension_loaded($name) ? 'Yes' : 'No';
    };

    // --- 1. OPcache (Opcode Cache) ---
    $return .= "1. OPcache (Opcode Cache):\n";
    $opcache_loaded = extension_loaded('opcache') || extension_loaded('Zend OPcache');

    // OUTPUT: Single 'Yes' or 'No' for installation status
    $return .= "   Is Installed?:         " . ($opcache_loaded ? 'Yes' : 'No') . "\n";

    if ($opcache_loaded && function_exists('opcache_get_status')) {
        $status = @opcache_get_status(false);
        $is_active = $status && $status['opcache_enabled'];

        // Output the specific active status (if enabled)
        $return .= "   Status (Active):       " . ($is_active ? 'Yes' : 'No') . "\n";

        if ($is_active) {
            $stats = $status['opcache_statistics'] ?? [];
            $total_hits_misses = ($stats['hits'] ?? 0) + ($stats['misses'] ?? 0);

            $hit_rate = ($total_hits_misses > 0) ? number_format(($stats['hits'] / $total_hits_misses) * 100, 2) : '0.00';
            $memory = $status['memory_usage'] ?? [];
            $free_memory_mb = round(($memory['free_memory'] ?? 0) / 1024 / 1024, 2);

            // COMMENTARY/METRICS
            //$return .= "   Hit Rate:              {$hit_rate}% \n";
            //$return .= "   Free Memory:           {$free_memory_mb} MB \n";
            //$return .= "   Comment:               " . ($hit_rate < 95 ? 'LOW. Review opcache.validate_timestamps in php.ini.' : 'Healthy. Rate is high.') . "\n";
        }
    } elseif (!$opcache_loaded) {
        $return .= "   Status (Active):       No\n";
        //$return .= "   Comment:               OPcache extension is not loaded in PHP.\n";
    }

    // --- 2. APCu (Object Cache) ---
    $return .= "\n2. APCu (Object Cache):\n";
    $apcu_loaded = extension_loaded('apcu');

    // OUTPUT: Single 'Yes' or 'No' for installation status
    $return .= "   Is Installed?:         " . ($apcu_loaded ? 'Yes' : 'No') . "\n";

    $object_cache_file_exists = false;
    if (defined('WP_CONTENT_DIR')) {
        $object_cache_file = WP_CONTENT_DIR . '/object-cache.php';
        $object_cache_file_exists = file_exists($object_cache_file);
    }

    if ($apcu_loaded) {
        // Output on the crucial object-cache.php file
        $return .= "   WP Drop-in File:       " . ($object_cache_file_exists ? 'Found' : 'Missing') . "\n";

        if ($object_cache_file_exists) {
            $return .= "   Status:                READY FOR USE\n";
            //$return .= "   Comment:               APCu is loaded, and the 'object-cache.php' drop-in is present.\n";
        } else {
            $return .= "   Status:                MISSING DROP-IN\n";
            // $return .= "   Comment:               APCu is available, but 'object-cache.php' is required for persistent cache.\n";
        }
    } else {
        $return .= "   Status:                Not Available\n";
    }


    // --- 3 & 4. Redis / Memcached (Conditional Check) ---

    // We only display Redis/Memcached if APCu is NOT installed, as APCu is often preferred for simple setups.
    // If APCu is installed, we assume the user intends to use it, simplifying the report.
    if (!$apcu_loaded) {

        $redis_loaded = extension_loaded('redis');
        $memcached_loaded = extension_loaded('memcached') || extension_loaded('memcache');

        $return .= "\n--- ALTERNATIVE OBJECT CACHES ---\n";

        // 3. Redis
        $return .= "3. Redis:\n";
        $return .= "   Is Installed?:         " . ($redis_loaded ? 'Yes' : 'No') . "\n";

        // 4. Memcached / Memcache
        $return .= "4. Memcached / Memcache:\n";
        $return .= "   Is Installed?:         " . ($memcached_loaded ? 'Yes' : 'No') . "\n";

        if (!$redis_loaded && !$memcached_loaded) {
            $return .= "\n   Note: No alternative external object cache extensions were found.\n";
        }
    }

    $return .= "\n-----------------------------------\n";
    return $return;
}

function antibots_readable_error_reporting($level)
{
    $error_levels = [
        E_ALL => 'E_ALL',
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];

    $active_errors = [];

    foreach ($error_levels as $level_value => $level_name) {
        if ($level & $level_value) {
            $active_errors[] = $level_name;
        }
    }

    return empty($active_errors) ? 'N/A' : implode(' | ', $active_errors);
}



function antibots_OSName()
{
    try {
        if (false == function_exists("shell_exec") || false == @is_readable("/etc/os-release")) {
            return false;
        }
        $os = shell_exec('cat /etc/os-release | grep "PRETTY_NAME"');
        return explode("=", $os)[1];
    } catch (Exception $e) {
        // echo 'Message: ' .$e->getMessage();
        return false;
    }
}
function antibots_get_host()
{
    if (isset($_SERVER['SERVER_NAME'])) {
        $server_name = sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']));
    } else {
        $server_name = 'Unknow';
    }
    $host = 'DBH: ' . DB_HOST . ', SRV: ' . $server_name;
    return $host;
}
function antibots_get_ua2()
{
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return '';
    }
    $ua = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
    if (!empty($ua))
        return trim($ua);
    else
        return "";
}


/**
 * Get system load averages
 * @return array Load averages for 1, 5, and 15 minutes
 */
function antibots_get_load_average()
{
    try {
        // Attempt to use sys_getloadavg()
        if (function_exists('sys_getloadavg')) {
            $antibots_load = sys_getloadavg();
            if ($antibots_load !== false && is_array($antibots_load)) {
                return [
                    '1min'  => $antibots_load[0],
                    '5min'  => $antibots_load[1],
                    '15min' => $antibots_load[2],
                ];
            }
        }

        // Fallback to reading /proc/loadavg
        return antibots_get_load_average_from_proc();
    } catch (Exception $e) {
        return [
            '1min'  => null,
            '5min'  => null,
            '15min' => null,
        ];
    }
}

/**
 * Fallback function to read load averages from /proc/loadavg
 * @return array Load averages for 1, 5, and 15 minutes
 */
function antibots_get_load_average_from_proc()
{
    try {
        if (file_exists('/proc/loadavg')) {
            $antibots_contents = @file_get_contents('/proc/loadavg');
            if ($antibots_contents !== false) {
                $antibots_parts = explode(' ', trim($antibots_contents));
                if (count($antibots_parts) >= 3) {
                    return [
                        '1min'  => (float) $antibots_parts[0],
                        '5min'  => (float) $antibots_parts[1],
                        '15min' => (float) $antibots_parts[2],
                    ];
                }
            }
        }
        return [
            '1min'  => null,
            '5min'  => null,
            '15min' => null,
        ];
    } catch (Exception $e) {
        return [
            '1min'  => null,
            '5min'  => null,
            '15min' => null,
        ];
    }
}

/**
 * Get the number of CPU cores
 * @return int|string Number of cores or error message
 */
function antibots_get_cpu_cores()
{
    $antibots_cores = false;

    // Método 1: exec()
    if (function_exists('exec')) {
        try {
            @exec('nproc --all', $output);
            if (isset($output[0]) && is_numeric($output[0])) {
                return (int) $output[0];
            }
        } catch (Throwable $e) {
        }
    }

    // Método 2: system()
    if ($antibots_cores === false && function_exists('system')) {
        try {
            ob_start();
            @system('nproc --all');
            $output = trim(ob_get_clean());
            if (is_numeric($output)) {
                return (int) $output;
            }
        } catch (Throwable $e) {
        }
    }

    // Método 3: passthru()
    if ($antibots_cores === false && function_exists('passthru')) {
        try {
            ob_start();
            @passthru('nproc --all');
            $output = trim(ob_get_clean());
            if (is_numeric($output)) {
                return (int) $output;
            }
        } catch (Throwable $e) {
        }
    }

    // Método 4: popen()
    if ($antibots_cores === false && function_exists('popen')) {
        try {
            $handle = @popen('nproc --all', 'r');
            $output = $handle ? trim(fread($handle, 128)) : '';
            if ($handle) {
                pclose($handle);
            }
            if (is_numeric($output)) {
                return (int) $output;
            }
        } catch (Throwable $e) {
        }
    }

    // Método 5: proc_open()
    if ($antibots_cores === false && function_exists('proc_open')) {
        try {
            $descriptorspec = [
                1 => ['pipe', 'w']
            ];
            $process = @proc_open('nproc --all', $descriptorspec, $pipes);
            if (is_resource($process)) {
                $output = trim(stream_get_contents($pipes[1]));
                fclose($pipes[1]);
                proc_close($process);
                if (is_numeric($output)) {
                    return (int) $output;
                }
            }
        } catch (Throwable $e) {
        }
    }

    // Método 6: getenv() para Windows
    if ($antibots_cores === false) {
        try {
            $env = @getenv('NUMBER_OF_PROCESSORS');
            if ($env && is_numeric($env)) {
                return (int) $env;
            }
        } catch (Throwable $e) {
        }
    }

    // Método 7: Contagem de "processor" em /proc/cpuinfo (Linux)
    if ($antibots_cores === false && is_readable('/proc/cpuinfo')) {
        try {
            $cpuinfo = @file_get_contents('/proc/cpuinfo');
            if ($cpuinfo !== false) {
                preg_match_all('/^processor/m', $cpuinfo, $matches);
                if (!empty($matches[0])) {
                    return count($matches[0]);
                }
            }
        } catch (Throwable $e) {
        }
    }

    return 'Unable to detect CPU cores';
}


/**
 * Get full CPU information
 * @return array CPU cores, architecture, and model
 */
function antibots_get_full_cpu_info()
{
    $antibots_info = [
        'cores' => null,
        'architecture' => null,
        'model' => null,
    ];

    try {
        // 1. Get cores
        $antibots_cores = antibots_get_cpu_cores();
        if (is_numeric($antibots_cores)) {
            $antibots_info['cores'] = $antibots_cores;
        } else {
            $antibots_info['cores'] = 'Unknown';
        }

        // 2. Get architecture
        try {
            $antibots_info['architecture'] = php_uname('m') ?: 'Unknown';
        } catch (Exception $e) {
            $antibots_info['architecture'] = 'Unknown';
        }

        // 3. Get model (prefer /proc/cpuinfo)
        $cpu_model_found = false;

        if (file_exists('/proc/cpuinfo') && is_readable('/proc/cpuinfo')) {
            try {
                $antibots_cpuinfo = @file_get_contents('/proc/cpuinfo');
                if ($antibots_cpuinfo !== false && preg_match('/model name\s+:\s+(.+)/', $antibots_cpuinfo, $matches)) {
                    $antibots_info['model'] = trim($matches[1]);
                    $cpu_model_found = true;
                }
            } catch (Exception $e) {
                // fallback later
            }
        }

        // 4. Try lscpu (Linux)
        if (!$cpu_model_found && function_exists('shell_exec')) {
            $lscpu_output = @shell_exec('lscpu 2>/dev/null');
            if (!empty($lscpu_output) && preg_match('/Model name:\s+(.+)/', $lscpu_output, $matches)) {
                $antibots_info['model'] = trim($matches[1]);
                $cpu_model_found = true;
            }
        }

        // 5. Try exec('lscpu')
        if (!$cpu_model_found && function_exists('exec')) {
            $output = [];
            @exec('lscpu 2>/dev/null', $output);
            if (!empty($output)) {
                foreach ($output as $line) {
                    if (stripos($line, 'Model name:') === 0) {
                        $antibots_info['model'] = trim(substr($line, strpos($line, ':') + 1));
                        $cpu_model_found = true;
                        break;
                    }
                }
            }
        }

        // 6. Try sysctl (macOS)
        if (!$cpu_model_found && function_exists('shell_exec') && stripos(PHP_OS, 'Darwin') === 0) {
            $sysctl_output = @shell_exec("sysctl -n machdep.cpu.brand_string");
            if (!empty($sysctl_output)) {
                $antibots_info['model'] = trim($sysctl_output);
                $cpu_model_found = true;
            }
        }

        // 7. Try WMIC (Windows)
        if (!$cpu_model_found && function_exists('shell_exec') && stripos(PHP_OS, 'WIN') === 0) {
            $wmic_output = @shell_exec("wmic cpu get Name /format:list");
            if (!empty($wmic_output) && preg_match('/Name=(.+)/i', $wmic_output, $matches)) {
                $antibots_info['model'] = trim($matches[1]);
                $cpu_model_found = true;
            }
        }

        // Final fallback
        if (!$cpu_model_found) {
            $antibots_info['model'] = 'Unknown';
        }

        return $antibots_info;
    } catch (Exception $e) {
        return $antibots_info;
    }
}


/**
 * Calculate CPU load percentage
 * @param float|null $load Load value
 * @param int $cores Number of CPU cores
 * @return float|null Percentage or null if invalid
 */
function antibots_calculate_load_percentage($load, $cores)
{
    try {
        if ($cores <= 0 || $load === null) {
            return null;
        }
        return round(($load / $cores) * 100, 2);
    } catch (Exception $e) {
        return null;
    }
}
