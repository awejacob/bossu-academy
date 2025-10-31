<?php

/**
 * checkup server
 * Handles server health checks and database integrity verification
 * via AJAX requests from the admin panel.
 * 16 Oct 25
 * @package    Diagnose
 * @subpackage Server_Check
 * @author     Bill
 */

namespace antibots_BillDiagnose;

// Checagens de segurança permanecem
if (!\defined("ABSPATH")) {
    die("Invalid request.");
}
if (\function_exists("is_multisite") and \is_multisite()) {
    return;
}

class autocheckup_server
{
    /**
     * @var autocheckup_server A instância única da classe (Singleton).
     */
    private static $instance = null;

    /**
     * @var string O tipo de chamada para a API, fixo como 'auto-checkup2'. (SERVER)
     */
    private $chatType_server = 'auto-checkup2';

    /**
     * @var string O tipo de chamada para a API, fixo como 'database-check'. (DATABASE)
     */
    private $chatType_database = 'database-check'; // NOVO: Tipo para checagem de DB

    private $chatVersion = '5'; // Propriedade mantida


    /**
     * Retorna a instância única da classe.
     * @return autocheckup_server
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            // A instância é criada somente na primeira chamada
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // 1. Registro do Enqueue (para carregar o JS)
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_autocheckup_script']);

        // 2. Registro dos AJAX Handlers

        // Handler 1: Server Check (Existente)
        $ajax_action_server = 'antibots_start_autocheckup';
        \add_action("wp_ajax_{$ajax_action_server}", [$this, 'handle_server_check_ajax']);

        // Handler 2: Database Check (NOVO)
        $ajax_action_database = 'database_check_action'; // Corresponde ao JS
        \add_action("wp_ajax_{$ajax_action_database}", [$this, 'handle_database_check_ajax']);
    }

    private function __clone()
    {
    }

    // ==========================================================
    // MÉTODOS DE INSTÂNCIA (Lógica)
    // ==========================================================

    /**
     * Enfileira o script (Função de Instância).
     */
    public function enqueue_autocheckup_script($hook)
    {

        $ajax_handle    = 'antibots_-autocheckup-js';

        if ('site-health.php' !== $hook) {
            return;
        }


        \wp_enqueue_script(
            $ajax_handle,
            \plugin_dir_url(__FILE__) . 'autocheckup-ajax.js',
            ['jquery'],
            '1.0',
            true
        );

        \wp_localize_script(
            $ajax_handle,
            'antibotsAjaxParams',
            [
                'ajaxurl' => \admin_url('admin-ajax.php'),
                // Usamos a ação existente como a ação "base" (para compatibilidade com o JS)
                'action'  => 'antibots_start_autocheckup',
                'nonce'   => \wp_create_nonce('antibots_autocheck_nonce'),
            ]
        );
    }

    // ==========================================================
    // HANDLER AJAX 1: SERVER CHECK (Antigo handle_autocheckup_ajax renomeado)
    // ==========================================================
    public function handle_server_check_ajax()
    {
        $nonce_name = 'antibots_autocheck_nonce';

        if (!isset($_POST['nonce']) || !\wp_verify_nonce(\sanitize_text_field(\wp_unslash($_POST['nonce'])), $nonce_name)) {
            \wp_send_json_error([
                'message' => \__('Security check failed.', 'database-backup')
            ]);
            \wp_die();
        }

        // Usa o método de execução para checagem de servidor
        $result = $this->execute_server_checkup();

        $this->send_json_response($result);
        // wp_die() está em send_json_response
    }

    // ==========================================================
    // HANDLER AJAX 2: DATABASE CHECK (NOVO)
    // ==========================================================
    public function handle_database_check_ajax()
    {


        $nonce_name = 'antibots_autocheck_nonce';

        if (!isset($_POST['nonce']) || !\wp_verify_nonce(\sanitize_text_field(\wp_unslash($_POST['nonce'])), $nonce_name)) {
            \wp_send_json_error([
                'message' => \__('Security check failed.', 'database-backup')
            ]);
            \wp_die();
        }


        // Usa o novo método de execução para checagem de banco de dados
        $result = $this->execute_database_checkup();
        //$this->send_json_response('ok');
        $this->send_json_response($result);
    }

    /**
     * Método auxiliar para enviar a resposta JSON (centraliza a lógica de sucesso/erro).
     * @param string $result O relatório de texto/HTML retornado.
     */
    private function send_json_response($result)
    {
        // NOTA: Se $result for HTML (como na checagem de DB), esta lógica de 'Error'/'ERRO' 
        // deve ser ajustada, pois o HTML não deve conter essas palavras no corpo.
        // No entanto, para manter a compatibilidade com a resposta da API externa, mantemos.
        if (\strpos($result, 'Error') !== false || \strpos($result, 'ERRO') !== false) {
            // Se houver erro, envia com sucesso=false para WP, mas inclui o relatório
            \wp_send_json_error([
                'analysis' => $result,
                'message' => \__('Checkup API returned an error.', 'database-backup')
            ]);
        } else {
            // Se for sucesso, envia com sucesso=true
            \wp_send_json_success([
                'analysis' => $result,
                'message' => \__('Auto-Checkup completed successfully.', 'database-backup')
            ]);
        }
        \wp_die();
    }


    // ==========================================================
    // MÉTODOS DE LÓGICA DE EXECUÇÃO
    // ==========================================================

    /**
     * Executa a checagem do servidor (Antigo execute_checkup renomeado).
     */
    public function execute_server_checkup()
    {
        $data = '';
        $retorna = $this->call_autocheckup_api($data, $this->chatType_server, $this->chatVersion);
        return $retorna;
    }

    /**
     * Executa a checagem do banco de dados (NOVO).
     */
    /**
     * Executa a checagem do banco de dados (NOVO).
     * Lógica da tic_check_all_tables() incorporada.
     */

    public function execute_database_checkup()
    {
        global $wpdb;

        // 1. Obtém todos os nomes de tabelas do banco de dados atual.
        $tables_result = $wpdb->get_results("SHOW TABLES FROM `" . $wpdb->dbname . "`", \ARRAY_N);


        if (empty($tables_result)) {
            return '<p>' . esc_html__('Tables not found!', 'database-backup') . '</p>';
        }

        $all_tables = \array_map(function ($item) {
            return $item[0];
        }, $tables_result);

        $output = '<style>
            .tic-error { background-color: #ffe8e8 !important; }
            .tic-success { background-color: #e8ffe8 !important; }
            #database-checkup-content table { width: 100%; border-collapse: collapse; }
        </style>';

        $output .= '<h3>' . esc_html__('Table Integrity Status', 'your-text-domain') . '</h3>';
        $output .= '<table class="wp-list-table widefat striped">';
        $output .= '<thead><tr><th>' . esc_html__('Table', 'your-text-domain') . '</th><th>' . esc_html__('Engine', 'your-text-domain') . '</th><th>' . esc_html__('Operation', 'your-text-domain') . '</th><th>' . esc_html__('Status', 'your-text-domain') . '</th><th>' . esc_html__('Message', 'your-text-domain') . '</th></tr></thead>';
        $output .= '<tbody>';

        // Determina o prefixo a ser removido (ex: "dbname.")
        $db_prefix = $wpdb->dbname . '.';

        foreach ($all_tables as $table_name) {
            // NOTA: Se tic_get_table_engine for um método da classe, use $this->get_table_engine()
            // Se tic_get_table_engine for uma função global/namespace, chame-a diretamente
            $engine = tic_get_table_engine($table_name);

            $safe_table_name = \esc_sql($table_name);
            $query = "CHECK TABLE `{$safe_table_name}`";

            // Usamos $wpdb->get_results() diretamente
            $check_results = $wpdb->get_results($query);

            if (!empty($check_results)) {
                $result = $check_results[0];
                $status_text = \strtoupper($result->Msg_text);

                // --- Lógica de Status ---
                $is_innodb_note = ($result->Msg_type === 'note' &&
                    $status_text === "THE STORAGE ENGINE FOR THE TABLE DOESN'T SUPPORT CHECK"
                );

                if ($is_innodb_note) {
                    $row_class = 'tic-success'; // Usamos uma classe específica
                    $status_style = 'style="color: green; font-weight: bold;"';

                    // CORREÇÃO: Internacionaliza o prefixo da mensagem de status
                    $message_display = 'OK (' . esc_html__('Verification Inapplicable for ', 'database-backup') . esc_html($engine) . ')';
                    $type_display = 'OK';
                } elseif ($status_text === 'OK') {
                    $row_class = 'tic-success';
                    $status_style = 'style="color: green; font-weight: bold;"';
                    $message_display = \esc_html($result->Msg_text);
                    $type_display = \esc_html($result->Msg_type);
                } else {
                    $row_class = 'tic-error';
                    $status_style = 'style="color: red; font-weight: bold;"';
                    $message_display = \esc_html($result->Msg_text);
                    $type_display = \esc_html($result->Msg_type);
                }
                // ----------------------------------

                $output .= '<tr class="' . \esc_attr($row_class) . '">';

                // CORREÇÃO AQUI: Remove o prefixo 'dbname.' do nome da tabela.
                $clean_table_name = str_replace($db_prefix, '', $result->Table);
                $output .= '<td>' . \esc_html($clean_table_name) . '</td>';

                $output .= '<td>' . \esc_html($engine) . '</td>';
                $output .= '<td>' . \esc_html($result->Op) . '</td>';
                $output .= '<td ' . $status_style . '>' . \esc_html($type_display) . '</td>';
                $output .= '<td>' . \esc_html($message_display) . '</td>';
                $output .= '</tr>';
            } else {
                // Caso de erro na execução da query
                $output .= '<tr class="tic-error">';

                // CORREÇÃO AQUI: Remove o prefixo do nome da tabela se houver falha.
                $clean_table_name = str_replace($db_prefix, '', $table_name);
                $output .= '<td>' . \esc_html($clean_table_name) . '</td>';

                $output .= '<td>N/A</td>';
                $output .= '<td>N/A</td>';

                // CORREÇÃO AQUI: Internacionalizando ERRO e a mensagem de falha
                $output .= '<td style="color: red; font-weight: bold;">' . esc_html__('ERROR', 'database-backup') . '</td>';
                $output .= '<td>' . esc_html__('Failed to execute CHECK TABLE.', 'database-backup') . '</td>';
                $output .= '</tr>';
            }
        }

        $output .= '</tbody></table>';
        $output .= '<br>';
        $output .= esc_html__('An OK Status means the table is intact. Any other messages (Warning, Error) indicate the need for investigation and possible REPAIR. Our WP Tools plugin can help you perform this repair.', 'database-backup');

        // Retorna o HTML completo.
        return $output;
    }



    private function call_autocheckup_api($data, $chatType, $chatVersion)
    {
        if ($chatType === 'auto-checkup2') {
            $data = "Auto Checkup Server button clicked...";
        } elseif ($chatType === 'auto-checkup') {
            $data = "Auto Checkup for Errors button clicked...";
        }

        $bill_chat_erros = '';

        $plugin_path = \plugin_basename(__FILE__);
        $language = \get_locale();

        $plugin_slug = \explode('/', $plugin_path)[0];
        $domain = \parse_url(\home_url(), \PHP_URL_HOST);

        if (!function_exists('\antibots_sysinfo_get')) {
            $antibots_checkup = 'ERROR: antibots_sysinfo_get function not found!';
        } else {
            $antibots_checkup = \antibots_sysinfo_get();
        }

        $data2 = [
            'param1' => $data,
            'param2' => $antibots_checkup,
            'param3' => $bill_chat_erros,
            'param4' => $language,
            'param5' => $plugin_slug,
            'param6' => $domain,
            'param7' => $chatType, // ChatType dinâmico
            'param8' => $chatVersion,
        ];

        $response = \wp_remote_post('https://BillMinozzi.com/chat/api/api.php', [
            'timeout' => 60,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => \json_encode($data2),
        ]);

        $message = '';
        if (\is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $message = \esc_attr__("Error contacting the remote server (HTTP Error): ", 'database-backup') . \sanitize_text_field($error_message);
        } else {
            $body = \wp_remote_retrieve_body($response);
            $data = \json_decode($body, true);

            if (isset($data['success']) && $data['success'] === true) {
                // Se a API externa retornou sucesso, usamos a mensagem dela
                $message = $data['message'];
            } else {
                // Se a API externa falhou, retornamos a mensagem de erro padrão
                $message = \esc_attr__("Error contacting the Artificial Intelligence (API). Please try again later.", 'database-backup');
            }
        }
        return $message;
    }
}

/**
 * Obtém o motor de armazenamento (Engine) de uma tabela.
 * @global \wpdb $wpdb
 * @param string $table_name
 * @return string|null
 */
function tic_get_table_engine($table_name)
{
    global $wpdb;
    $query = $wpdb->prepare("SHOW TABLE STATUS LIKE %s", $table_name);
    $result = $wpdb->get_row($query);
    return $result ? $result->Engine : 'N/A';
}




// Chamamos get_instance() para garantir que a classe é instanciada e 
// o Construtor (com o add_action) é executado IMEDIATAMENTE.
autocheckup_server::get_instance();
