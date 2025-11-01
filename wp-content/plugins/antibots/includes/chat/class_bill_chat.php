<?php

namespace antibots_BillChat;
// 2024-12=18 // 2025-01-04
if (!defined('ABSPATH')) {
    die('Invalid request.');
}
if (function_exists('is_multisite') && is_multisite()) {
    return;
}

class ChatPlugin
{
    public function __construct()
    {
        // Hooks para AJAX
        add_action('wp_ajax_bill_chat_send_message', [$this, 'bill_chat_send_message']);
        //add_action('wp_ajax_nopriv_bill_chat_send_message', [$this, 'bill_chat_send_message']);
        add_action('wp_ajax_bill_chat_reset_messages', [$this, 'bill_chat_reset_messages']);
        //add_action('wp_ajax_nopriv_bill_chat_reset_messages', [$this, 'bill_chat_reset_messages']);
        add_action('wp_ajax_bill_chat_load_messages', [$this, 'bill_chat_load_messages']);
        // Registrar os scripts
        add_action('admin_init', [$this, 'chat_plugin_scripts']);
        add_action('admin_init', [$this, 'enqueue_chat_scripts']);
    }
    // ⭐⭐ MOVER esta função para FORA da classe, ou torná-la método da classe


    private function filter_log_content($content)
    {
        if (is_array($content)) {
            $filteredArray = array_filter($content);
            return empty($filteredArray) ? '' : $content;
        } elseif (is_object($content)) {
            return '';
        } else {
            return $content;
        }
    }


    public function chat_plugin_scripts()
    {
        wp_enqueue_style(
            'chat-style',
            plugin_dir_url(__FILE__) . 'chat.css'
        );
    }
    public function enqueue_chat_scripts()
    {
        wp_enqueue_script(
            'chat-script',
            plugin_dir_url(__FILE__) . 'chat.js',
            array('jquery'),
            '',
            true
        );
        wp_localize_script('chat-script', 'bill_data', array(
            'ajax_url'                 => admin_url('admin-ajax.php'),
            'reset_nonce'              => wp_create_nonce('bill_chat_reset_messages_nonce'), // Linha adicionada
            'reset_success'            => esc_attr__('Chat messages reset successfully.', 'antibots'),
            'reset_error'              => esc_attr__('Error resetting chat messages.', 'antibots'),
            'invalid_message'          => esc_attr__('Invalid message received:', 'antibots'),
            'invalid_response_format'  => esc_attr__('Invalid response format:', 'antibots'),
            'response_processing_error' => esc_attr__('Error processing server response:', 'antibots'),
            'not_json'                 => esc_attr__('Response is not valid JSON.', 'antibots'),
            'ajax_error'               => esc_attr__('AJAX request failed:', 'antibots'),
            'send_error'               => esc_attr__('Error sending the message. Please try again later.', 'antibots'),
            'empty_message_error'      => esc_attr__('Please enter a message!', 'antibots'),
        ));
    }
    /**
     * Função para carregar as mensagens do chat.
     */
    public function bill_chat_load_messages()
    {
        if (ob_get_length()) {
            ob_clean();
        }
        $messages = get_option('chat_messages', []);
        $last_count = isset($_POST['last_count']) ? intval($_POST['last_count']) : 0;
        // Verifica se há novas mensagens
        $new_messages = [];
        if (count($messages) > $last_count) {
            $new_messages = array_slice($messages, $last_count);
        }
        // Retorna as mensagens no formato JSON
        wp_send_json([
            'message_count' => count($messages),
            'messages' => array_map(function ($message) {
                return [
                    'text' => esc_html($message['text']),
                    'sender' => esc_html($message['sender'])
                ];
            }, $new_messages)
        ]);
        wp_die();
    }
    public function bill_chat_load_messages_NEW()
    {
        // Verifica se é uma solicitação AJAX
        if (!wp_doing_ajax()) {
            wp_die('Acesso negado', 403);
        }
        $messages = get_option('chat_messages', []);
        $last_count = isset($_POST['last_count']) ? intval($_POST['last_count']) : 0;
        // Verifica se há novas mensagens
        $new_messages = [];
        if (count($messages) > $last_count) {
            $new_messages = array_slice($messages, $last_count);
        }
        // Retorna as mensagens no formato JSON
        wp_send_json([
            'message_count' => count($messages),
            'messages' => array_map(function ($message) {
                return [
                    'text' => esc_html($message['text']),
                    'sender' => esc_html($message['sender'])
                ];
            }, $new_messages)
        ]);
    }

    // New function to count error types from all log files
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
        $max_total_lines = 5000;
        $max_execution_time = 15;
        $start_time = time();
        $total_lines_processed = 0;

        // ⭐⭐ CORREÇÃO - $bill_files contém ARQUIVOS
        $bill_files = self::get_log_files();

        // ⭐⭐ DEBUG 2 - Arquivos sendo verificados
        //error_log("=== FILES TO CHECK ===");
        //error_log(print_r($bill_files, true));
        //error_log("Number of files: " . count($bill_files));

        foreach ($bill_files as $bill_file) {
            // ⭐⭐ DEBUG 3 - Arquivo atual
            //error_log("=== CHECKING FILE: " . $bill_file . " ===");

            // Check global timeout
            if (time() - $start_time >= $max_execution_time) {
                //error_log("TIMEOUT REACHED - breaking file loop");
                break;
            }

            // ⭐⭐ CORREÇÃO: Verifica arquivo diretamente
            if (!file_exists($bill_file) || !is_readable($bill_file)) {
                //error_log("File not found or not readable: " . $bill_file);
                continue;
            }

            // Check global line limit
            if ($total_lines_processed >= $max_total_lines) {
                //error_log("MAX LINES REACHED - breaking file loop");
                break;
            }

            // ⭐⭐ CORREÇÃO: Processa o arquivo diretamente
            if (strpos($bill_file, "backup") != true) {
                // ⭐⭐ DEBUG 6 - Tentando abrir arquivo
                //error_log("Opening file: " . $bill_file);

                $file = @fopen($bill_file, 'r');
                if ($file) {
                    //error_log("File opened successfully");
                    $file_line_count = 0;

                    while (($line = fgets($file)) !== false) {
                        $file_line_count++;

                        // CHECK GLOBAL LIMITS EVERY 50 LINES FOR PERFORMANCE
                        if ($total_lines_processed % 50 === 0) {
                            if ($total_lines_processed >= $max_total_lines) {
                                //error_log("MAX LINES REACHED in file - breaking all");
                                break 2;
                            }
                            if (time() - $start_time >= $max_execution_time) {
                                //error_log("TIMEOUT REACHED in file - breaking all");
                                break 2;
                            }
                        }

                        $line = trim($line);

                        // ⭐⭐ DEBUG 7 - Primeiras linhas do arquivo
                        if ($file_line_count <= 3) {
                            //error_log("Line " . $file_line_count . ": " . $line);
                        }

                        if (empty($line) || strpos($line, "[") !== 0) {
                            continue;
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
                            // ⭐⭐ DEBUG 8 - JavaScript error encontrado
                            //error_log("JAVASCRIPT ERROR DETECTED: " . $line);

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
                                //error_log("JAVASCRIPT COUNT INCREMENTED: " . $error_types_count["JavaScript"]);
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

                            // ⭐⭐ DEBUG 9 - PHP error encontrado
                            //error_log("PHP ERROR DETECTED - Type: " . $error_type . " - Desc: " . $error_description);

                            // Enhanced PHP error categorization
                            if (stripos($error_type, "deprecated") !== false) {
                                $error_types_count["Deprecated"]++;
                                //error_log("DEPRECATED COUNT: " . $error_types_count["Deprecated"]);
                            } elseif (
                                stripos($error_type, "fatal") !== false &&
                                stripos($error_type, "recoverable") === false
                            ) {
                                $error_types_count["Fatal"]++;
                                //error_log("FATAL COUNT: " . $error_types_count["Fatal"]);
                            } elseif (stripos($error_type, "recoverable") !== false) {
                                $error_types_count["Recoverable"]++;
                            } elseif (
                                stripos($error_type, "warning") !== false &&
                                stripos($error_type, "core") === false &&
                                stripos($error_type, "compile") === false &&
                                stripos($error_type, "user") === false
                            ) {
                                $error_types_count["Warning"]++;
                                //error_log("WARNING COUNT: " . $error_types_count["Warning"]);
                            } elseif (
                                stripos($error_type, "notice") !== false &&
                                stripos($error_type, "user") === false
                            ) {
                                $error_types_count["Notice"]++;
                            } elseif (stripos($error_type, "parse") !== false) {
                                $error_types_count["Parse"]++;
                                //error_log("PARSE COUNT: " . $error_types_count["Parse"]);
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
                                //error_log("DATABASE COUNT: " . $error_types_count["Database"]);
                            }

                            // Filesystem error detection
                            if (preg_match("/(fopen|fwrite|unlink|file_get_contents|permission denied|No such file)/i", $error_description)) {
                                $error_types_count["Filesystem"]++;
                                //error_log("FILESYSTEM COUNT: " . $error_types_count["Filesystem"]);
                            }

                            // HTTP/API error detection
                            if (preg_match("/(curl|cURL error|HTTP error|timed out|Connection timed out|SSL)/i", $error_description)) {
                                $error_types_count["HTTP_API"]++;
                            }
                        }

                        $total_lines_processed++;
                    }
                    fclose($file);
                    //error_log("Finished processing file. Total lines: " . $file_line_count);
                } else {
                    // ⭐⭐ CORREÇÃO: Use $bill_file em vez de $bill_filename
                    //error_log("FAILED to open file: " . $bill_file);
                }
            } else {
                // ⭐⭐ CORREÇÃO: Use $bill_file em vez de $bill_filename
                //error_log("Skipping backup file: " . $bill_file);
            }
        } // ⭐⭐ FIM do foreach principal

        // ⭐⭐ DEBUG 10 - Resultado final
        ////error_log("=== FINAL ERROR COUNTS ===");
        ////error_log(print_r($error_types_count, true));
        ////error_log("Total lines processed: " . $total_lines_processed);
        ////error_log("=== COUNT_ERROR_TYPES FINISHED ===");

        return $error_types_count;
    } // ⭐⭐ FIM da função
    // New function to generate error summary table for API
    private function generate_error_summary_table($error_types_count)
    {
        $table = "ERROR SUMMARY TABLE:\n";
        $table .= "Error Type | Count\n";
        $table .= "-------------------\n";

        foreach ($error_types_count as $type => $count) {
            if ($count > 0) {
                $table .= str_pad($type, 12) . " | " . $count . "\n";
            }
        }

        return $table;
    }

    public function bill_read_file($file, $lines)
    {
        // Check if the file exists and is readable
        clearstatcache(true, $file); // Clear cache to ensure current file state
        if (!file_exists($file) || !is_readable($file)) {
            return []; // Return empty array in case of error
        }
        $text = [];
        $handle = fopen($file, "r");
        if (!$handle) {
            return [];
        }
        $bufferSize = 8192; // 8KB
        $currentChunk = '';
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
        // }
        return $text;
    }
    /**
     * Função para chamar a API do ChatGPT.
     */
    public function bill_chat_call_chatgpt_api($data, $chatType, $chatVersion)
    {
        $bill_chat_erros = '';
        $all_logs = [];

        // ⭐⭐ CORREÇÃO - $bill_files contém ARQUIVOS, não pastas
        $bill_files = self::get_log_files();

        foreach ($bill_files as $bill_file) {
            if (!file_exists($bill_file) || !is_readable($bill_file)) {
                continue;
            }

            // ⭐⭐ Lê diretamente o ARQUIVO (não é uma pasta)
            $returned_bill_chat_erros = $this->bill_read_file($bill_file, 40);
            //$returned_bill_chat_erros = filter_log_content($returned_bill_chat_erros);
            $returned_bill_chat_erros = $this->filter_log_content($returned_bill_chat_erros);

            if (!empty($returned_bill_chat_erros) && is_array($returned_bill_chat_erros)) {
                $all_logs = array_merge($all_logs, $returned_bill_chat_erros);
            }
        }

        // ⭐⭐ TRUNCAMENTO INTELIGENTE NO FINAL
        $total_chars = 0;
        $final_logs = [];

        foreach ($all_logs as $line) {
            if ($total_chars + strlen($line) <= 3500) {
                $final_logs[] = $line;
                $total_chars += strlen($line);
            } else {
                $final_logs[] = "... [logs truncated - " . (count($all_logs) - count($final_logs)) . " more lines] ...";
                break;
            }
        }

        $bill_chat_erros = $final_logs;



        // ⭐⭐ MODIFICAÇÃO FINAL - Enviar como texto identificável ⭐⭐

        // Gerar tabela primeiro para estimar tamanho
        $error_types_count = $this->count_error_types();

        // //error_log("var error_types_count: " . var_export($error_types_count, true));

        $error_summary_table = $this->generate_error_summary_table($error_types_count);

        // Converter array para string
        $bill_chat_erros_text = implode("\n", $bill_chat_erros);

        ////error_log(var_export($error_types_count, true));


        $bill_chat_erros_text .= "\n\n===ERROR_SUMMARY_START===\n";
        $bill_chat_erros_text .= "COMPLETE ERROR SUMMARY TABLE\n";
        // ⭐⭐ CORREÇÃO - Usar chaves com primeira letra maiúscula ⭐⭐
        $bill_chat_erros_text .= "Fatal Errors: " . (isset($error_types_count['Fatal']) ? $error_types_count['Fatal'] : 0) . "\n";
        $bill_chat_erros_text .= "Parse Errors: " . (isset($error_types_count['Parse']) ? $error_types_count['Parse'] : 0) . "\n";
        $bill_chat_erros_text .= "JavaScript Errors: " . (isset($error_types_count['JavaScript']) ? $error_types_count['JavaScript'] : 0) . "\n";
        $bill_chat_erros_text .= "Warnings: " . (isset($error_types_count['Warning']) ? $error_types_count['Warning'] : 0) . "\n";
        $bill_chat_erros_text .= "Deprecated: " . (isset($error_types_count['Deprecated']) ? $error_types_count['Deprecated'] : 0) . "\n";
        $bill_chat_erros_text .= "Filesystem: " . (isset($error_types_count['Filesystem']) ? $error_types_count['Filesystem'] : 0) . "\n";
        $bill_chat_erros_text .= "===ERROR_SUMMARY_END===";



        $plugin_path = plugin_basename(__FILE__); // Retorna algo como "plugin-folder/plugin-file.php"
        $language = get_locale();
        $plugin_slug = explode('/', $plugin_path)[0]; // Pega apenas o primeiro diretório (a raiz)
        $domain = parse_url(home_url(), PHP_URL_HOST);
        if (empty($bill_chat_erros)) {
            $bill_chat_erros = 'No errors found!';
        }
        //2025
        $antibots_checkup = \antibots_sysinfo_get();
        $data2 = [
            'param1' => $data,
            'param2' => $antibots_checkup,
            'param3' => $bill_chat_erros_text,
            'param4' => $language,
            'param5' => $plugin_slug,
            'param6' => $domain,
            'param7' => $chatType,
            'param8' => $chatVersion
        ];
        //             'param9' => $error_summary_table, // New parameter with error summary table

        $response = wp_remote_post('https://BillMinozzi.com/chat/api/api.php', [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data2),
        ]);
        if (is_wp_error($response)) {
            $error_message = sanitize_text_field($response->get_error_message());
        } else {
            $body = sanitize_text_field(wp_remote_retrieve_body($response));
            $data = json_decode($body, true);
        }
        if (isset($data['success']) && $data['success'] === true) {
            $message = $data['message'];
        } else {
            $message = esc_attr__("Error contacting the Artificial Intelligence (API). Please try again later.", 'antibots');
        }
        return $message;
    }
    /*
    This function compiles a comprehensive list of potential error log files
    from WordPress installation, plugins, themes, and server configurations.
        */
    public static function get_log_files()
    {
        $log_files = [];
        $log_files[] = trailingslashit(ABSPATH) . "error_log";

        $error_log_path = ini_get("error_log");
        if (!empty($error_log_path)) {
            $error_log_path = trim($error_log_path);
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $error_log_path = trailingslashit(WP_CONTENT_DIR) . 'debug.log';
            } else {
                $error_log_path = trailingslashit(ABSPATH) . 'error_log';
            }
        }

        $log_files[] = $error_log_path;

        // Log files in WordPress root directory
        $log_files[] = WP_CONTENT_DIR . "/debug.log";
        // Log files in current plugin directory
        $log_files[] = plugin_dir_path(__FILE__) . "error_log";
        $log_files[] = plugin_dir_path(__FILE__) . "php_errorlog";
        // Log files in current theme directory
        $log_files[] = get_theme_root() . "/error_log";
        $log_files[] = get_theme_root() . "/php_errorlog";
        // Log files in administration area (if it exists)
        $bill_admin_path = str_replace(get_bloginfo("url") . "/", ABSPATH, get_admin_url());
        $log_files[] = $bill_admin_path . "/error_log";
        $log_files[] = $bill_admin_path . "/php_errorlog";
        // Log files in plugin subdirectories
        try {
            $bill_plugins = array_slice(scandir(plugin_dir_path(__FILE__)), 2);
            foreach ($bill_plugins as $bill_plugin) {
                $plugin_path = plugin_dir_path(__FILE__) . $bill_plugin;
                if (is_dir($plugin_path)) {
                    $log_files[] = $plugin_path . "/error_log";
                    $log_files[] = $plugin_path . "/php_errorlog";
                }
            }
        } catch (Exception $e) {
            // Handle the exception
            //error_log("Error scanning plugins directory: " . $e->getMessage());
        }
        // Log files in theme subdirectories
        try {
            $bill_themes = array_slice(scandir(get_theme_root()), 2);
            foreach ($bill_themes as $bill_theme) {
                if (is_dir(get_theme_root() . "/" . $bill_theme)) {
                    $log_files[] = get_theme_root() . "/" . $bill_theme . "/error_log";
                    $log_files[] = get_theme_root() . "/" . $bill_theme . "/php_errorlog";
                }
            }
        } catch (Exception $e) {
            // Handle the exception
            //error_log("Error scanning theme directory: " . $e->getMessage());
        }

        return array_unique($log_files);
    }
    public function bill_chat_send_message()
    {
        // Captura e sanitiza a mensagem
        $message = sanitize_text_field($_POST['message']);
        // Verifica e sanitiza o chat_type, atribuindo 'default' caso não exista
        $chatType = isset($_POST['chat_type']) ? sanitize_text_field($_POST['chat_type']) : 'default';
        if (empty($message)) {
            if ($chatType == 'auto-checkup') {
                $message = esc_attr("Auto Checkup for Erros button clicked...", 'antibots');
            } elseif ($chatType == 'auto-checkup2') {
                $message = esc_attr("Auto Checkup Server button clicked...", 'antibots');
            }
        }

        // //error_log(var_export($chatType));
        $chatVersion = isset($_POST['chat_version']) ? sanitize_text_field($_POST['chat_version']) : '1.00';
        // Chama a API e obtém a resposta
        //debug4();

        $response_data = $this->bill_chat_call_chatgpt_api($message, $chatType, $chatVersion);
        // Verifique se a resposta foi obtida corretamente
        if (!empty($response_data)) {
            $output = $response_data;
            $resposta_formatada = $output;
        } else {
            $output = "Error to get response from AI source!";
            $output = esc_attr__("Error to get response from AI source!", 'antibots');
        }
        // Prepara as mensagens
        $messages = get_option('chat_messages', []);
        $messages[] = [
            'text' => $message,
            'sender' => 'user'
        ];
        $messages[] = [
            'text' => $resposta_formatada,
            'sender' => 'chatgpt'
        ];
        update_option('chat_messages', $messages);
        wp_die();
    }
    /**
     * Função para resetar as mensagens.
     */

    public function bill_chat_reset_messages()
    {
        // 1. Verificar o Nonce para proteção contra CSRF
        check_ajax_referer('bill_chat_reset_messages_nonce', 'security');

        // 2. Verificar se o utilizador tem permissão para esta ação (apenas administradores)
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to perform this action.');
            wp_die();
        }

        // Se as verificações passarem, apagar as mensagens
        update_option('chat_messages', []);

        // Enviar uma resposta de sucesso
        wp_send_json_success('Chat messages have been reset.');

        wp_die();
    }
}
new ChatPlugin();
