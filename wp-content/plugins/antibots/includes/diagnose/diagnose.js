/*
 * ARQUIVO 1: diagnose-general.js
 * Responsabilidade: Inicialização da UI (Accordions e Estilos).
 */
jQuery(document).ready(function ($) {

    // console.log("Diagnose General: UI Scripts Loaded.");

    // ==========================================================
    // 1. INICIALIZAÇÃO DOS ACCORDIONS
    // ==========================================================

    var accordions = $("#accordion1, #accordion2, #accordion3, #accordion4, #accordion5, #accordion6, #accordion7");

    // Inicializa todos os Accordions
    accordions.accordion({
        collapsible: true,
        active: false,
        heightStyle: "content"
    });
    // console.log("Diagnose General: Accordions jQuery UI inicializados.");


    // ==========================================================
    // 2. HABILITAR BOTÕES QUANDO A PÁGINA ESTIVER PRONTA
    // ==========================================================


    function enableChatButtons() {
        $('#send-button').prop('disabled', false);
        $('#auto-checkup').prop('disabled', false);
        $('#auto-checkup2').prop('disabled', false);
        // console.log("Chat buttons enabled");
    }

    // Habilitar botões após um pequeno delay para garantir que tudo carregou
    setTimeout(enableChatButtons, 2000);

    // ==========================================================
    // 3. ESTILOS PERSONALIZADOS
    // ==========================================================

    $("<style>")
        .prop("type", "text/css")
        .html(`
            /* Fundo e cor padrão para os títulos fechados */
            #accordion1 .ui-accordion-header,
            #accordion2 .ui-accordion-header,
            #accordion3 .ui-accordion-header,
            #accordion4 .ui-accordion-header,
            #accordion5 .ui-accordion-header,
            #accordion6 .ui-accordion-header,
            #accordion7 .ui-accordion-header {
                border: 1px solid gray;
            }

            /* Fundo e cor do título quando o box estiver aberto */
            #accordion1 .ui-accordion-header-active,
            #accordion2 .ui-accordion-header-active,
            #accordion3 .ui-accordion-header-active,
            #accordion4 .ui-accordion-header-active,
            #accordion5 .ui-accordion-header-active,
            #accordion6 .ui-accordion-header-active,
            #accordion7 .ui-accordion-header-active {
                /* Estilos do cabeçalho ativo */
            }  
            
            /* Estilo refinado para o ícone do cabeçalho ativo */
            .ui-accordion-header-active .ui-icon {
                background-color: #cc0000 !important;
                color: #ffffff !important;           
                border-radius: 50% !important;       
                padding: 1px !important;             
                margin: 2px;
                box-sizing: border-box !important;   
                display: inline-block !important;    
                line-height: 1;                      
            }
        `)
        .appendTo("head");
});
