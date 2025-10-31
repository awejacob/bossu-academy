/*
 * ARQUIVO 2: autocheckup-ajax.js
 * Responsabilidade: Lógica AJAX para múltiplos accordions (#4 e #3).
 * Oct 16 2025 
 */
jQuery(document).ready(function ($) {

    // console.log("AUTOCHECKUP AJAX: Script de lógica carregado.");

    var Params = typeof antibotsAjaxParams !== 'undefined' ? antibotsAjaxParams : null;

    if (!Params || !Params.ajaxurl || !Params.nonce || !Params.action) {
        console.error("AUTOCHECKUP AJAX ERROR: ❌ Variáveis AJAX (antibotsAjaxParams) ausentes ou incompletas. Abortando lógica AJAX.");
        return;
    }
    // console.log("AUTOCHECKUP AJAX: ✅ Parâmetros AJAX carregados. Ação base:", Params.action);


    // --- Mapeamento de Accordions e Ações ---
    // Mapeamos o ID da DIV de CONTEÚDO para seus dados AJAX específicos
    const CHECKUP_MAPPING = {
        // 1. ACORDION EXISTENTE (SERVER CHECK)
        'autocheckup-content': {
            action: Params.action, // Ação existente (ex: 'server_check_action')
            loadingMsg: 'Please wait. Comprehensive server analysis in progress...',
            accordionId: '#accordion4',
            isHtml: false // NOVO: Retorno é texto/código (API)
        },
        // 2. NOVO ACORDION (DATABASE CHECK)
        'database-checkup-content': {
            action: 'database_check_action', // NOVA AÇÃO para o PHP
            loadingMsg: 'Please wait, executing database integrity analysis...',
            accordionId: '#accordion3',
            isHtml: true // NOVO: Retorno é HTML (Tabela)
        }
    };

    // ==========================================================
    // 2. INICIALIZAÇÃO E MANIPULADOR DE EVENTOS
    // ==========================================================

    // Inicializa AMBOS os accordions (se ainda não estiverem inicializados)
    $('#accordion4, #accordion3').accordion({
        collapsible: true,
        heightStyle: "content"
    });

    // Liga o evento em AMBOS os accordions
    $('#accordion4, #accordion3').on('accordionactivate', function (event, ui) {

        //console.log(">>> EVENTO 'accordionactivate' DISPARADO <<<");

        // 1. Se for um fechamento (ui.newPanel.length é 0), ignore.
        if (ui.newPanel.length === 0) {
            return;
        }

        // 2. Identifica a DIV de conteúdo.
        const activatedPanel = ui.newPanel;
        let contentArea = activatedPanel.find('#autocheckup-content, #database-checkup-content').first();

        if (contentArea.length === 0 && activatedPanel.is('#autocheckup-content, #database-checkup-content')) {
            contentArea = activatedPanel;
        }

        if (contentArea.length === 0) {
            console.warn("❌ Painel ativado, mas o ID interno de conteúdo não foi mapeado. Ignorando.");
            return;
        }

        const contentAreaId = contentArea.attr('id');
        const checkupData = CHECKUP_MAPPING[contentAreaId];

        // Adicionada a informação do tipo de conteúdo para debug
        //console.log(`AUTOCHECKUP AJAX: ✅ Painel '${contentAreaId}' ativado. Ação: ${checkupData.action}. Tipo de Conteúdo Esperado: ${checkupData.isHtml ? 'HTML' : 'Texto'}`);


        // 3. VERIFICA SE JÁ FOI CARREGADO
        if (contentArea.data('loaded') === true) {
            //console.log("AUTOCHECKUP AJAX: Conteúdo já carregado. Abortando AJAX.");
            return;
        }

        // 4. PREPARAÇÃO E SPINNER (DINÂMICO)
        //console.log("AUTOCHECKUP AJAX: Conteúdo não carregado. Iniciando AJAX.");

        const loadingHtml = `
            <div style="display: flex; align-items: center; justify-content: flex-start;">
                <span class="spinner is-active"></span> 
                <p style="margin: 0 0 0 10px;">${checkupData.loadingMsg}</p>
            </div>`;

        contentArea.html(loadingHtml);

        // 5. CHAMADA AJAX
        $.ajax({
            url: Params.ajaxurl,
            type: 'POST',
            data: {
                action: checkupData.action, // **AÇÃO DINÂMICA**
                nonce: Params.nonce,
            },
            dataType: 'json',

            success: function (response) {

                contentArea.empty(); // CRÍTICO: Limpa o spinner antes de injetar o resultado
                //console.log("RESPOSTA RECEBIDA (OBJETO):", response);

                const responseData = response.data || {}; // Garante que responseData existe

                // CRÍTICO: Loga o HTML gerado pela função tic_check_all_tables() para debug
                if (response.success && responseData.analysis) {
                    // console.log("Resultado Completo (HTML da Tabela):", responseData.analysis);
                }

                let contentToDisplay = '';
                let isSuccessfulContent = false;

                // ==========================================================
                // 1. TENTA O FLUXO DE SUCESSO CANÔNICO DO WP
                //    Busca 'analysis' (o conteúdo principal) ou 'message' dentro de 'data'
                // ==========================================================
                if (response && response.success === true) {
                    contentToDisplay = responseData.analysis || responseData.message || response.message;
                    isSuccessfulContent = true;
                }

                // 2. TENTA O FLUXO DE ERRO COM CONTEÚDO (Caso o servidor tenha retornado ERRO mas com análise)
                else if (response && response.success === false && responseData && (responseData.analysis || responseData.message)) {
                    contentToDisplay = responseData.analysis || responseData.message;
                    isSuccessfulContent = true;
                }

                // ==========================================================
                // EXIBIÇÃO FINAL (CORRIGIDA)
                // ==========================================================
                if (isSuccessfulContent) {

                    if (checkupData.isHtml) {
                        // CRÍTICO: Usa .html() para renderizar o código HTML
                        contentArea.html(contentToDisplay);
                    } else {
                        // Se for texto (Server Check), mantém o fluxo com <pre>
                        const preElement = $('<pre></pre>')
                            .text(contentToDisplay)
                            .css({
                                'white-space': 'pre-wrap',
                                'word-wrap': 'break-word',
                                'word-break': 'break-word'
                            });

                        contentArea.html('<p>Analysis completed successfully (Content extracted).</p>');
                        contentArea.append(preElement);
                    }

                    contentArea.data('loaded', true); // Marca como carregado

                }
                // 3. FALHA REAL 
                else {
                    console.error("FLUXO: ❌ FALHA REAL. Resposta não contém conteúdo de análise esperado.", response);

                    const errorMessage = response.error
                        || response.message
                        || responseData.error
                        || 'Erro desconhecido. Resposta de API não reconhecida.';

                    contentArea.html('<p style="color: red;">Error executing checkup:</p>');
                    contentArea.append('<pre>' + errorMessage + '</pre>');
                }
            },

            error: function (xhr, status, error) {
                // Limpa o spinner em caso de falha de rede/servidor
                contentArea.empty();
                console.error("AJAX ERROR: Network/Server failure.", status, error);
                contentArea.html('<p style="color: red;">Error executing checkup: Network or server error (' + status + ').</p>');
            }
        });
    });
});