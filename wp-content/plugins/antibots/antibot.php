<?php /*
Plugin Name: Antibots
Plugin URI: http://antibotsplugin.com
Description: Anti Bots, SPAM bots and spiders. No DNS or Cloud Traffic Redirection. No Slow Down Your Site!
Version: 1.72
Text Domain: antibots
Domain Path: /language
Author: Bill Minozzi
Author URI: http://antibotsplugin.com
License:     GPL2
Copyright (c) 2016 Bill Minozzi
AntiBots is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
AntiBots_optin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with AntiBots_optin. If not, see {License URI}.
Permission is hereby granted, free of charge subject to the following conditions:
The above copyright notice and this FULL permission notice shall be included in
all copies or substantial portions of the Software.
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
DEALINGS IN THE SOFTWARE.
 */
if (!defined('ABSPATH')) {
    exit;
}
$antibots_maxMemory = @ini_get('memory_limit');
$antibots_last = strtolower(substr($antibots_maxMemory, -1));
$antibots_maxMemory = (int) $antibots_maxMemory;
if ($antibots_last == 'g') {
    $antibots_maxMemory = $antibots_maxMemory * 1024 * 1024 * 1024;
} else if ($antibots_last == 'm') {
    $antibots_maxMemory = $antibots_maxMemory * 1024 * 1024;
} else if ($antibots_last == 'k') {
    $antibots_maxMemory = $antibots_maxMemory * 1024;
}
if ($antibots_maxMemory < 134217728 /* 128 MB */ && $antibots_maxMemory > 0) {
    if (strpos(ini_get('disable_functions'), 'ini_set') === false) {
        @ini_set('memory_limit', '128M');
    }
}
global $wpdb;
define('ANTIBOTSVERSION', '1.29');
define('ANTIBOTSPATH', plugin_dir_path(__file__));
define('ANTIBOTSURL', plugin_dir_url(__file__));
define('ANTIBOTSDOMAIN', get_site_url());
define('ANTIBOTSIMAGES', plugin_dir_url(__file__) . 'assets/images');
define('ANTIBOTSPAGE', trim(sanitize_text_field($GLOBALS['pagenow'])));

define('ANTIBOTS_CHROME', '108'); // 131.0.6723.58
define('ANTIBOTS_FIREFOX', '108'); // 122
define('ANTIBOTS_EDGE', '110'); // 131




$antibots_ip = antibots_findip();
$antibots_method = sanitize_text_field($_SERVER["REQUEST_METHOD"]);
$antibotsserver = sanitize_text_field($_SERVER['SERVER_NAME']);
$antibots_request_url = esc_url($_SERVER['REQUEST_URI']);
$antibots_is_admin = antibots_check_wordpress_logged_in_cookie();

if ($antibots_is_admin) {
    add_action('plugins_loaded', 'antibots_localization_init');
}


$antibots_pos = stripos($antibots_request_url, "favicon.ico");
if ($antibots_pos !== false)
    return;

if (isset($_SERVER['HTTP_REFERER']))
    $antibots_referer = sanitize_text_field($_SERVER['HTTP_REFERER']);
else
    $antibots_referer = '';
$antibots_version = trim(sanitize_text_field(get_site_option('antibots_version', '')));
if (!function_exists('wp_get_current_user')) {
    // require_once(ABSPATH . "wp-includes/pluggable.php");
}
$antibots_enable_whitelist = sanitize_text_field(get_option('antibots_enable_whitelist', 'yes'));
// var_dump($antibots_enable_whitelist);

$antibots_my_radio_report_all_visits = sanitize_text_field(get_option('antibots_my_radio_report_all_visits', 'no'));
$antibots_my_radio_report_all_visits = strtolower($antibots_my_radio_report_all_visits);
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'antibots_add_action_links');

/*
function antibots_add_action_links($links)
{
    $mylinks = array(
        '<a href="' . admin_url('admin.php?page=settings-anti-bots') . '">Settings</a>',
        $dashboard_link = '<a href="admin.php?page=anti_hacker_plugin">Dashboard</a>'; 
    );
    return array_merge($links, $mylinks);
}
*/


function antibots_add_action_links($links)
{
    $settings_link = '<a href="admin.php?page=settings-anti-bots">Settings</a>';
    $dashboard_link = '<a href="admin.php?page=anti_bots_plugin">Dashboard</a>';

    // Adiciona o link do Dashboard no início
    array_unshift($links, $dashboard_link);

    // Adiciona o link de Settings após o Dashboard
    array_unshift($links, $settings_link);

    return $links;
}


/* Begin Language */
/*
if (antibots_check_wordpress_logged_in_cookie()) {
    if (isset($_GET['page'])) {
        $page = sanitize_text_field($_GET['page']);
        if ($page == 'anti_bots_plugin' or $page == 'antibots_my-custom-submenu-page') {
            $path = dirname(plugin_basename(__FILE__)) . '/language/';
            $loaded = load_plugin_textdomain('antibots', false, $path);
        }
    }
} else {
    add_action('plugins_loaded', 'antibots_localization_init');
}
function antibots_localization_init()
{
    $path = dirname(plugin_basename(__FILE__)) . '/language/';
    $loaded = load_plugin_textdomain('antibots', false, $path);
}
*/
/* End language */
$antibots_active = sanitize_text_field(get_option('antibots_is_active', ''));
$antibots_active = strtolower($antibots_active);
$antibots_keep_data = sanitize_text_field(get_option('antibots_keep_data', '4'));
$antibots_keep_data = strtolower($antibots_keep_data);
$antibots_admin_email = trim(get_option('antibots_my_email_to', ''));
if (!empty($antibots_admin_email)) {
    if (!is_email($antibots_admin_email)) {
        $antibots_admin_email = '';
        update_option('antibots_my_email_to', '');
    }
}
require_once ANTIBOTSPATH . "functions/functions.php";
//require_once ABSPATH . 'wp-includes/pluggable.php';
require_once ANTIBOTSPATH . 'dashboard/main.php';
require_once(ANTIBOTSPATH . 'functions/function_sysinfo.php');
//require_once ANTIBOTSPATH . "settings/load-plugin.php";
//require_once ANTIBOTSPATH . "settings/options/plugin_options_tabbed.php";

add_action('init', 'antibots_delay_antibots_loading');

function antibots_delay_antibots_loading()
{
    require_once ANTIBOTSPATH . "settings/load-plugin.php";
    require_once ANTIBOTSPATH . "settings/options/plugin_options_tabbed.php";
}



if (antibots_check_wordpress_logged_in_cookie()) {
    function antibots_add_admscripts()
    {
        wp_enqueue_script("jquery");
        wp_enqueue_script('jquery-ui-core');
        // wp_enqueue_style('bill-datatables', ANTIBOTSURL . 'assets/css/dataTables.bootstrap4.min.css');
        wp_enqueue_style('bill-datatables-jquery', ANTIBOTSURL . 'assets/css/jquery.dataTables.min.css');
        wp_enqueue_script('flot-antibots', ANTIBOTSURL .
            'assets/js/jquery.flot.min.js', array('jquery'));
        wp_enqueue_script('flotpie-antibots', ANTIBOTSURL .
            'assets/js/jquery.flot.pie.js', array('jquery'));
        wp_enqueue_script('botstrap', ANTIBOTSURL .
            'assets/js/bootstrap.bundle.min.js', array('jquery'));
        wp_enqueue_script('easing', ANTIBOTSURL .
            'assets/js/jquery.easing.min.js', array('jquery'));
        wp_enqueue_script('datatables1', ANTIBOTSURL .
            'assets/js/jquery.dataTables.min.js', array('jquery'));
        wp_localize_script('datatables1', 'datatablesajax', array('url' => admin_url('admin-ajax.php')));
        wp_enqueue_script('botstrap4', ANTIBOTSURL .
            'assets/js/dataTables.bootstrap4.min.js', array('jquery'));
        wp_enqueue_script('datatables2', ANTIBOTSURL .
            'assets/js/dataTables.buttons.min.js', array('jquery'));


        wp_register_script('datatables_visitors_antibots', ANTIBOTSURL .
            'assets/js/antibots_table.js', array(), '1.0', true);

        wp_enqueue_script('datatables_visitors_antibots');
        wp_localize_script(
            'datatables_visitors',
            'antibots_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('antibots_add_whitelist_nonce'),
            )
        );

        //function carregar_jquery_ui() {
        wp_enqueue_script('jquery-ui-accordion');
        wp_enqueue_style('jquery-ui-theme', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        //   }
        // add_action('wp_enqueue_scripts', 'carregar_jquery_ui');





    }
    add_action('admin_enqueue_scripts', 'antibots_add_admscripts', 1000);
}
require_once ANTIBOTSPATH . 'table/visitors.php';
register_activation_hook(__FILE__, 'antibots_plugin_was_activated');

// -------------------------  Step 2
//var_dump(antibots_whitelist_string($antibots_ua));

if (
    !antibots_whitelist_string($antibots_ua) &&
    !antibots_whitelist_IP($antibots_ip) &&
    $antibots_pos === false &&
    !$antibots_maybe_search_engine &&
    $ip_server != $antibots_ip &&
    !antibots_check_wordpress_logged_in_cookie() &&
    $antibots_is_human != '1'
) {


    // var_dump(antibots_whitelist_string($antibots_ua));



    if ($antibots_is_human != '1') {

        if (!isset($_COOKIE['_ga']) && !isset($_COOKIE['__utma'])) {


            // Firewall
            // Modsecurity 2025
            if (!isset($_SERVER['HTTP_ACCEPT'])) {
                antibots_response();
            }
            if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
                antibots_response();
            }
            // end Modsecurity 2025









            // Array de regras para a URI da requisição
            $antibots_request_uri_array   = array(
                // SUAS REGRAS ORIGINAIS (mantidas do código antigo)
                '@eval', 'eval\(', 'UNION(.*)SELECT', '\(null\)', 'base64_', '\/localhost', '\%2Flocalhost', '\/pingserver', 'wp-config\.php', '\/config\.', '\/wwwroot', '\/makefile', 'crossdomain\.', 'proc\/self\/environ', 'usr\/bin\/perl', 'var\/lib\/php', 'etc\/passwd', '\/https\:', '\/http\:', '\/ftp\:', '\/file\:', '\/php\:', '\/cgi\/', '\.cgi', '\.cmd', '\.bat', '\.exe', '\.sql', '\.ini', '\.dll', '\.htacc', '\.htpas', '\.pass', '\.asp', '\.jsp', '\.bash', '\/\.git', '\/\.svn', ' ', '\<', '\>', '\/\=', '\.\.\.', '\+\+\+', '@@', '\/&&', '\/Nt\.', '\;Nt\.', '\=Nt\.', '\,Nt\.', '\.exec\(', '\)\.html\(', '\{x\.html\(', '\(function\(', '\.php\([0-9]+\)', '(benchmark|sleep)(\s|%20)*\(', 'indoxploi', 'xrumer', '\/\.env', // <- Essa regra estava no antigo, mas foi mantida no novo também.

                // --- ADIÇÕES DO CÓDIGO NOVO (as `new_signatures`, agora aqui diretamente para simplicidade, ou mantidas em array separado se preferir mais modularidade) ---
                // Bloqueia acesso a backups e arquivos de configuração comuns (do antigo)
                '\.bak', '\.conf', '\.cfg', '\.ds_store',
                // Bloqueia acesso a backups compactados na raiz do site (do antigo)
                '\/(db|master|sql|wp|www|wwwroot)\.(gz|zip)',
                // Bloqueia padrões genéricos de execução de comandos e funções perigosas (do antigo, reintroduzido)
                '((curl_|shell_)?exec|(f|p)open|passthru|phpinfo|proc_open|system)(.*)(\()(.*)(\))',
                // Novas assinaturas detalhadas (do código novo)
                '\.htaccess', '\.htdigest', '\.htpasswd', // Apache (versões precisas)
                '\/\.gitignore', '\/\.hg', '\/\.hgignore', // Outros controles de versão
                'wp-config\.bak', 'wp-config\.old', 'wp-config\.temp', 'wp-config\.tmp', 'wp-config\.txt', // Backups de configuração do WP
                '\/sites\/default\/default\.settings\.php', '\/sites\/default\/settings\.php', // Drupal
                '\/app\/etc\/local\.xml', // Magento 1
                '\/Web\.config', // ASP.NET
                '\/sftp-config\.json', '\/gruntfile\.js', '\/npm-debug\.log', // Ferramentas de desenvolvimento e dependências
                '\/composer\.json', '\/composer\.lock', '\/packages\.json',
            );

            // Array de regras para a Query String
            $antibots_query_string_array  = array(
                // SUAS REGRAS ORIGINAIS (mantidas)
                '@@', '\(0x', '0x3c62723e', '\;\!--\=', '\(\)\}', '\:\;\}\;', '\.\.\/', '127\.0\.0\.1', 'UNION(.*)SELECT', '@eval', 'eval\(', 'base64_', 'localhost', 'loopback', '\%0A', '\%0D', '\%00', '\%2e\%2e', 'allow_url_include', 'auto_prepend_file', 'disable_functions', 'input_file', 'execute', 'file_get_contents', 'mosconfig', 'open_basedir', '(benchmark|sleep)(\s|%20)*\(', 'phpinfo\(', 'shell_exec\(', '\/wwwroot', '\/makefile', 'path\=\.', 'mod\=\.', 'wp-config\.php', '\/config\.', '\$_session', '\$_request', '\$_env', '\$_server', '\$_post', '\$_get', 'indoxploi', 'xrumer',

                // --- ADIÇÕES SUGERIDAS DO CÓDIGO ANTIGO (reintroduzidas para forte proteção) ---
                // Bloqueia tentativas de manipulação de variáveis globais de forma mais abrangente
                '(globals|request)(=|\[)',
                // Regra poderosa que bloqueia uma vasta gama de ataques de SQL Injection e XSS
                '(<|>|\'|")(.*)(\/\*|alter|base64|benchmark|cast|char|concat|create|declare|delete|drop|exec|function|html|insert|md5|request|script|select|set|union|update)'
            );

            // Array de regras para o User Agent (do código novo, não alterado)
            $antibots_user_agent_array   = array('drivermysqli', 'acapbot', '\/bin\/bash', 'binlar', 'casper', 'cmswor', 'diavol', 'dotbot', 'finder', 'flicky', 'md5sum', 'morfeus', 'nutch', 'planet', 'purebot', 'pycurl', 'semalt', 'shellshock', 'skygrid', 'snoopy', 'sucker', 'turnit', 'vikspi', 'zmeu');

            // --- INÍCIO DA LÓGICA DO FIREWALL ATUALIZADA ---

            // ADIÇÃO: Verificação de requisição excessivamente longa (reintroduzida do código antigo).
            // Usando dados brutos.
            $limite_comprimento_req = 2048; // Ajuste este valor se necessário
            if (isset($_SERVER['REQUEST_URI']) && strlen($_SERVER['REQUEST_URI']) > $limite_comprimento_req) {
                antibots_response();
            }

            // Variáveis RAW (brutas) para a verificação de segurança do firewall
            // SEM SANITIZAÇÃO AQUI, pois o firewall deve atuar sobre os dados originais.
            $firewall_raw_request_uri  = '';
            $firewall_raw_query_string = '';
            $firewall_raw_user_agent   = ''; // Nova variável raw para User Agent

            // Popula as variáveis RAW para o firewall
            if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
                $firewall_raw_request_uri = $_SERVER['REQUEST_URI'];
            }
            if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
                $firewall_raw_query_string = $_SERVER['QUERY_STRING'];
            }
            if (isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT'])) {
                $firewall_raw_user_agent = $_SERVER['HTTP_USER_AGENT'];
            }

            // Lógica de verificação agora usa as variáveis RAW para detectar ameaças
            if ($firewall_raw_request_uri || $firewall_raw_query_string || $firewall_raw_user_agent) {
                if (
                    ($firewall_raw_request_uri  && preg_match('/' . implode('|', $antibots_request_uri_array)  . '/i', $firewall_raw_request_uri,  $matches))
                    ||
                    ($firewall_raw_query_string && preg_match('/' . implode('|', $antibots_query_string_array) . '/i', $firewall_raw_query_string, $matches2))
                    ||
                    ($firewall_raw_user_agent   && preg_match('/' . implode('|', $antibots_user_agent_array)   . '/i', $firewall_raw_user_agent,   $matches3))
                ) {
                    antibots_response();
                } // Endif match...
            } // end if ($firewall_raw_request_uri || $firewall_raw_query_string || $firewall_raw_user_agent)

            // NOTA: As variáveis SANITIZADAS ($antibots_request_uri_string, $antibots_query_string_string, $antibots_user_agent_string)
            // foram removidas daqui para evitar confusão e garantir que o firewall sempre use dados brutos.
            // Se você precisar de versões sanitizadas para OUTRAS partes do seu código, você deve criá-las
            // APÓS a execução da lógica do firewall, ou em um escopo diferente.






            // end firewall



            $antibots_bad_host = array(
                '1and1.com',
                'ALICOULD',
                'ALISOFT',
                'ALIBABA',
                'a2hosting.com',
                'ahrefs.com',
                'akamai.com',
                'akamai.net',
                'Amazon',
                'apple',
                'ARUBA-NET',
                'azure.com',
                'bluehost',
                'bluehost.com',
                'CHINANET',
                'clients.your-server.de',
                'cloudflare',
                'colocrossing',
                'contabo.com',
                'CONTABO',
                'digitalocean.com',
                'DIGITALOCEAN',
                'dreamhost',
                'dreamhost.com',
                'ExonHost',
                'fastly.com',
                'fastly.net',
                'Gandi',
                'GoDaddy',
                'Go-Daddy',
                'googleusercontent.com',
                'greengeeks.com',
                'heroku.com',
                'Hetzner',
                'hipl',
                'hosting',
                'hostgator.com',
                'HostHatch',
                'hosteurope.com',
                'hostinger.com',
                'hostpapa.com',
                'hostwinds.com',
                'hwclouds',
                'huaway',
                'HWCLOUDS',
                'ibm.com',
                'inmotionhosting.com',
                'Internap',
                'IONOS',
                'ipage.com',
                'ipfire.org',
                'justhost.com',
                'kimsufi.com',
                'LeaseWeb',
                'lightningbase.com',
                'Limestone',
                'LINODE',
                'linode.com',
                'Linode',
                'liquidweb.com',
                'MICROSOFT',
                'MSFT',
                'moonfruit.com',
                'namecheap.com',
                'Netsons',
                'oraclecloud.com',
                'OVH',
                'reliablesite.net',
                'researchscan',
                'rackspace.com',
                'rev.synaix.de',
                'scaleway.com',
                'secureserver.net',
                'semrush',
                'server',
                'siteground.com',
                'startdedicated.com',
                'softlayer',
                'tencent.com',
                'TMDHosting',
                'upcloud.com',
                'verizon.net',
                'vps',
                'vps.ovh',
                'vultr.com',
                'webhostingpad.com',
                'wix.com',
            );


            if (antibots_is_bad_hosting($antibots_ip) || antibots_is_bad_hosting2($antibots_ip)) {
                // termina por aqui... bot...
                antibots_response();
            }


            $antibots_ua_browser = antibots_find_ua_browser($antibots_userAgentOri);
            $antibots_ua_version = antibots_find_ua_version($antibots_userAgentOri, $antibots_ua_browser);


            // $antibots_ua_os = antibots_find_ua_os($antibots_userAgentOri);

            if ($antibots_ua_browser == 'Chrome' and !empty($antibots_ua_version)) {
                if (version_compare($antibots_ua_version, ANTIBOTS_CHROME) <= 0) {
                    antibots_response();
                }
            }

            if ($antibots_ua_browser == 'Firefox' and !empty($antibots_ua_version)) {
                if (version_compare($antibots_ua_version, ANTIBOTS_FIREFOX) <= 0) {
                    antibots_response();
                }
            }

            if ($antibots_ua_browser == 'Edge' and !empty($antibots_ua_version)) {
                if (version_compare($antibots_ua_version, ANTIBOTS_EDGE) <= 0) {
                    antibots_response();
                }
            }

            if ($antibots_ua_browser == 'MSIE' and !empty($antibots_ua_version)) {
                antibots_response();
            }


            /////////////////////


            if ($antibots_is_human == '?') {
                antibots_record_log();
                add_filter('template_include', 'antibots_page_template');
            } elseif ($antibots_is_human == '0') {
                if (antibots_howmany_bots_visit($antibots_ip) > 3 and antibots_howmany_human_visit($antibots_ip) < 1) {
                    antibots_response();
                } else {
                    antibots_record_log();
                    add_filter('template_include', 'antibots_page_template');
                }
            }
            header("Refresh: 3;");
        } // has analytycs...



    }
} elseif (
    !antibots_check_wordpress_logged_in_cookie() &&
    $ip_server !== $antibots_ip &&
    $antibots_pos === false
) {
    //var_dump(antibots_whitelist_string($antibots_ua));
    antibots_record_log();
} else {
    // var_dump(antibots_whitelist_string($antibots_ua));
}
/*   ------------------------------     END STEP 2 */






function antibots_include_scripts()
{
    //wp_enqueue_script("jquery");
    //wp_enqueue_script('jquery-ui-core');
    // debug2();

    wp_register_script(
        "antibots-scripts",
        ANTIBOTSURL . "assets/js/antibots_fingerprint.js",
        ["jquery"],
        null,
        true
    ); //true = footer
    wp_enqueue_script("antibots-scripts");
}

function antibots_findip()
{
    $ip = "";
    $headers = [
        "HTTP_CF_CONNECTING_IP", // CloudFlare
        "HTTP_CLIENT_IP", // Bill
        "HTTP_X_REAL_IP", // Bill
        "HTTP_X_FORWARDED", // Bill
        "HTTP_FORWARDED_FOR", // Bill
        "HTTP_FORWARDED", // Bill
        "HTTP_X_CLUSTER_CLIENT_IP", //Bill
        "HTTP_X_FORWARDED_FOR", // Squid and most other forward and reverse proxies
        "REMOTE_ADDR", // Default source of remote IP
    ];
    for ($x = 0; $x < 8; $x++) {
        foreach ($headers as $header) {
            if (!isset($_SERVER[$header])) {
                continue;
            }
            $myheader = trim(sanitize_text_field($_SERVER[$header]));
            if (empty($myheader)) {
                continue;
            }
            $ip = trim(sanitize_text_field($_SERVER[$header]));
            if (empty($ip)) {
                continue;
            }
            if (
                false !==
                ($comma_index = strpos(
                    sanitize_text_field($_SERVER[$header]),
                    ","
                ))
            ) {
                $ip = substr($ip, 0, $comma_index);
            }
            // First run through. Only accept an IP not in the reserved or private range.
            if ($ip == "127.0.0.1") {
                $ip = "";
                continue;
            }
            if (0 === $x) {
                $ip = filter_var(
                    $ip,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE
                );
            } else {
                $ip = filter_var($ip, FILTER_VALIDATE_IP);
            }
            if (!empty($ip)) {
                break;
            }
        }
        if (!empty($ip)) {
            break;
        }
    }
    if (!empty($ip)) {
        return $ip;
    } else {
        return "unknow";
    }
}
//
//


function antibots_localization_init()
{
    $path = ANTIBOTSPATH . 'language/';
    $locale = apply_filters('plugin_locale', determine_locale(), 'antibots');

    // Full path of the specific translation file (e.g., es_AR.mo)
    $specific_translation_path = $path . "antibots-$locale.mo";
    $specific_translation_loaded = false;

    // Check if the specific translation file exists and try to load it
    if (file_exists($specific_translation_path)) {
        $specific_translation_loaded = load_textdomain('antibots', $specific_translation_path);
    }

    // List of languages that should have a fallback to a specific locale
    $fallback_locales = [
        'de' => 'de_DE',  // German
        'fr' => 'fr_FR',  // French
        'it' => 'it_IT',  // Italian
        'es' => 'es_ES',  // Spanish
        'pt' => 'pt_BR',  // Portuguese (fallback to Brazil)
        'nl' => 'nl_NL'   // Dutch (fallback to Netherlands)
    ];

    // If the specific translation was not loaded, try to fallback to the generic version
    if (!$specific_translation_loaded) {
        $language = explode('_', $locale)[0];  // Get only the language code, ignoring the country (e.g., es from es_AR)

        if (array_key_exists($language, $fallback_locales)) {
            // Full path of the generic fallback translation file (e.g., es_ES.mo)
            $fallback_translation_path = $path . "antibots-{$fallback_locales[$language]}.mo";

            // Check if the fallback generic file exists and try to load it
            if (file_exists($fallback_translation_path)) {
                load_textdomain('antibots', $fallback_translation_path);
            }
        }
    }

    // Load the plugin
    load_plugin_textdomain('antibots', false, plugin_basename(ANTIBOTSPATH) . '/language/');
}


/*
if ($antibots_is_admin) {
    add_action('plugins_loaded', 'antibots_localization_init');
}
    */


function antibots_bill_more()
{
    global $antibots_is_admin;
    if ($antibots_is_admin and current_user_can("manage_options")) {
        $declared_classes = get_declared_classes();
        foreach ($declared_classes as $class_name) {
            if (strpos($class_name, "Bill_show_more_plugins") !== false) {
                //    return;
            }
        }
        require_once dirname(__FILE__) . "/includes/more-tools/class_bill_more.php";
    }
}
add_action("init", "antibots_bill_more", 5);


function antibots_load_chat()
{
    if (function_exists('is_admin') && function_exists('current_user_can')) {
        if (is_admin() and current_user_can("manage_options")) {
            if (!class_exists('wpmemory_BillChat\ChatPlugin')) {
                require_once dirname(__FILE__) . "/includes/chat/class_bill_chat.php";
            }
        }
    }
}
add_action('wp_loaded', 'antibots_load_chat');

function antibots_bill_hooking_diagnose()
{
    global $antibots_is_admin;
    // if (function_exists('is_admin') && function_exists('current_user_can')) {
    if ($antibots_is_admin and current_user_can("manage_options")) {
        $declared_classes = get_declared_classes();
        foreach ($declared_classes as $class_name) {
            if (strpos($class_name, "Bill_Diagnose") !== false) {
                return;
            }
        }
        $plugin_slug = 'recaptcha-for-all';
        $plugin_text_domain = $plugin_slug;
        $notification_url = "https://wpmemory.com/fix-low-memory-limit/";
        $notification_url2 =
            "https://wptoolsplugin.com/site-language-error-can-crash-your-site/";
        require_once dirname(__FILE__) . "/includes/diagnose/class_bill_diagnose.php";
    }
    // } 
}
add_action("init", "antibots_bill_hooking_diagnose", 10);

function antibots_bill_hooking_catch_errors()
{
    global $antibots_is_admin;
    global $antibots_plugin_slug;

    if (!function_exists("bill_check_install_mu_plugin")) {
        require_once dirname(__FILE__) . "/includes/catch-errors/bill_install_catch_errors.php";
    }

    $declared_classes = get_declared_classes();
    foreach ($declared_classes as $class_name) {
        if (strpos($class_name, "bill_catch_errors") !== false) {
            return;
        }
    }
    $antibots_plugin_slug = 'antibots';
    require_once dirname(__FILE__) . "/includes/catch-errors/class_bill_catch_errors.php";
}
add_action("init", "antibots_bill_hooking_catch_errors", 15);

function antibots_new_more_plugins()
{
    //antibots_show_logo();
    $plugin = new antibots_Bill_show_more_plugins();
    $plugin->bill_show_plugins();
}

function antibots_check_wordpress_logged_in_cookie()
{
    /**
     * Use a static variable to cache the result ONLY IF it's TRUE.
     * This ensures the full logic is re-executed if the previous result was FALSE
     * or if there was an error, forcing a fresh check.
     */
    static $is_admin_cached_true = null;

    // If the previous result was TRUE, return immediately from cache.
    if ($is_admin_cached_true === true) {
        return true;
    }

    // --- Start of Full Verification Logic ---

    $current_is_admin_status = false; // Default status for this execution.

    /**
     * Optimization: Fast path for the majority of users (non-logged-in visitors).
     * If no cookie with the 'wordpress_logged_in_' prefix exists, it's impossible
     * for the user to be a logged-in admin.
     */
    $has_auth_cookie = false;
    if (!empty($_COOKIE)) {
        foreach ($_COOKIE as $key => $value) {
            // Check if any cookie name starts with the WordPress logged-in prefix.
            if (strpos($key, 'wordpress_logged_in_') === 0) {
                $has_auth_cookie = true;
                break; // Found one, no need to check the rest.
            }
        }
    }

    // If no potential authentication cookie was found, the user is definitely not an admin.
    if (!$has_auth_cookie) {
        // Not an admin, do not cache (as cache is only for TRUE results).
        return $current_is_admin_status; // Returns false.
    }

    /**
     * If we reach this point, a cookie exists. Now, we must validate it securely.
     * The only reliable way is to use WordPress's own functions.
     * First, we check if the required function has been loaded by WordPress yet.
     */
    if (!function_exists('current_user_can') || !function_exists('wp_get_current_user')) {
        /**
         * The function does not exist yet. This means we are running too early in the
         * WordPress load order.
         * The solution is to manually load the file where this function is defined.
         */


        // Only load the file if functions still don't exist after all attempts
        if (!function_exists('current_user_can') || !function_exists('wp_get_current_user')) {
            if (defined('ABSPATH') && defined('WPINC')) {
                try {
                    $pluggable_file = ABSPATH . WPINC . '/pluggable.php';
                    if (file_exists($pluggable_file)) {
                        require_once $pluggable_file;
                    }
                } catch (Throwable $e) {
                    // Silently continue if loading fails
                }
            }
        }
    }

    /**
     * Now that we have attempted to load the file (if it wasn't already),
     * we can check if the current_user_can() function is available.
     * We can now perform the secure check.
     */

    if (!function_exists('current_user_can') || !function_exists('wp_get_current_user')) {
        // If, for some reason, current_user_can still does not exist (critical error or unusual environment),
        // assume non-admin. Do not cache.
        return $current_is_admin_status; // Returns false.
    }

    if (current_user_can('manage_options')) {
        // The secure check passed. The user is a confirmed administrator.
        $current_is_admin_status = true;
    } else {
        // The cookie exists, but the secure check failed.
        // This could be a forged cookie from an attacker or an expired session.
        $current_is_admin_status = false;
    }

    // --- End of Full Verification Logic ---

    // If the current result is TRUE, then cache it for future calls within this request.
    if ($current_is_admin_status === true) {
        $is_admin_cached_true = true;
    }

    // Return the final, securely determined result.
    return $current_is_admin_status;
}
