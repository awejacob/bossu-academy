<?php

/**
 * @author William Sergio Minozzi
 * @copyright 2020
 * */
if (!defined('ABSPATH')) {
    exit;
}
?>






<div id="antibots-steps0">
    <div class="antibots-block-title">
        <?php esc_attr_e("Quick Overview", "antibots"); ?>
    </div>

    <!-- CORRIGIDO: Usando a classe de container correta para ativar as colunas -->
    <div class="antibots-help-container1">

        <!-- Primeira Coluna: Status do Plugin -->
        <div class="antibots-help-2column antibots-help-column-1" style="text-align: center;">
            <h3 style="margin-top:0;"><?php esc_attr_e("Plugin Status", "antibots"); ?></h3>
            <?php
            $antibots_active = sanitize_text_field(get_option('antibots_is_active', ''));
            $antibots_active = strtolower($antibots_active);
            if ($antibots_active == 'yes') {
                echo '<img src="' . esc_url(ANTIBOTSURL . '/assets/images/lock-xxl.png') . '" alt="Enabled" style="width: 60px;margin-bottom: 10px;"  />';
                echo '<h4 style="color:green; margin-top:10px;">' . esc_html__("Protection Enabled", "antibots") . '</h4>';
            } else {
                echo '<img src="' . esc_url(ANTIBOTSURL . '/assets/images/unlock-icon-red-small.png') . '" alt="Disabled" style="max-width: 40px;margin-bottom: 10px;"  />';
                echo '<h4 style="color:red; margin-top:10px;">' . esc_html__("Protection Disabled", "antibots") . '</h4>';
                esc_html_e('Go to Settings to enable it.', "antibots");
            }


            $plugin = 'antihacker/antihacker.php';

            if (!function_exists('is_plugin_active')) {
                include_once ABSPATH . 'wp-admin/includes/plugin.php';
            }




            if (is_plugin_active($plugin)) {
                // 1. O plugin está ATIVO
                // echo 'O plugin está instalado e ATIVO.';
            } else {

                // O plugin NÃO está ativo. Agora, vamos verificar se ele está instalado ou não.
                if (file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {

                    // 2. O plugin está INSTALADO, mas INATIVO
                    // echo 'O plugin está instalado, mas INATIVO.';
                    echo '<br>';
                    //echo '<br>';

            ?>
                    <span class="dashicons dashicons-warning" style="color: #FF0000; font-size: 20px; margin-right: 1px;"></span>
                <?php

                    echo esc_attr__('AntiHacker Extension is Disabled! Activate it from the Plugins page to get a powerful, free firewall and block brute-force and XML-RPC attacks —currently a major source of attacks.', 'antibots');

                    echo '<br>';
                    echo '<br>';
                } else {
                    // 3. O plugin NÃO está INSTALADO
                    // echo 'O plugin NÃO está INSTALADO.';
                    // echo '<br>';
                    echo '<br>';
                ?>
                    <span class="dashicons dashicons-warning" style="color: #FF0000; font-size: 20px; margin-right: 1px;"></span>
                    <?php
                    echo esc_attr__('AntiHacker Extension is Not Installed! Install it from the Plugins page to get a powerful, free firewall and block brute-force and XML-RPC attacks —currently a major source of attacks.', 'antibots');
                    // Define the base page and the target tab
                    $base_page = 'anti_bots_plugin';
                    $target_tab = 'more';

                    // Build the URL dynamically
                    $link_url = admin_url('admin.php?page=' . $base_page . '&tab=' . $target_tab);

                    // https://minozzi.eu/wp-admin//admin.php?page=anti_bots_plugin&tab=more


                    ?>

                    <br>
                    <a href="<?php echo esc_url($link_url); ?>" style="background-color: #FF7F50; border-color: #FFA500; color: #ffffff;" class="button button-primary" target="_blank">
                <?php

                    esc_attr_e('Install with one click!', 'antibots');
                    echo '</a>';

                    echo '<br>';
                    echo '<br>';
                }
            }

                ?>
        </div>

        <!-- Segunda Coluna: Recomendação do Plugin (COM FONTE CORRIGIDA) -->
        <div class="antibots-help-2column antibots-help-column-2">
            <h3 style="margin-top:0;"><?php esc_attr_e('Plugin Recommendation', 'antibots'); ?></h3>

            <?php // Texto sem as tags <p> para manter o tamanho da fonte padrão 
            ?>
            <?php esc_attr_e('This plugin offers lightweight and effective protection, ideal for sites facing low to moderate bot traffic.', 'antibots'); ?>
            <br /><br />
            <?php esc_attr_e('For large-scale or persistent bot attacks, we recommend our more powerful (and also free) plugin, StopBadBots. It is specifically designed to handle high-volume threats.', 'antibots'); ?>
            <br /><br />

            <a href="https://wordpress.org/plugins/stopbadbots/" class="button button-primary" target="_blank">
                <?php esc_attr_e('Learn about StopBadBots', 'antibots'); ?>
            </a>
            &nbsp;&nbsp;

        </div>

    </div> <!-- Fim de .antibots-help-container1 -->
</div> <!-- Fim de #antibots-steps0 -->


<div id="antibots-services3">
    <div class="antibots-help-container1">
        <div class="antibots-help-column antibots-help-column-1">
            <img alt="aux" src="<?php echo ANTIBOTSURL ?>assets/images/service_configuration.png" />
            <div class="bill-dashboard-titles">
                <?php
                esc_attr_e('Start Up Guide and Settings', 'antibots');
                ?>
            </div>
            <br /><br />
            <?php esc_attr_e("Just click Settings in the left menu (Anti Bots).", "antibots"); ?>
            <br />
            <?php
            esc_attr_e("Dashboard => Anti Bots => Settings", "antibots");
            echo " (Settings)" ?>
            <br />
            <?php $site = ANTIBOTSHOMEURL . "admin.php?page=settings-anti-bots"; ?>
            <a href="<?php echo esc_url($site); ?>" class="button button-primary"><?php
                                                                                    esc_attr_e('Go', 'antibots');
                                                                                    ?></a>
            <br /><br />
        </div> <!-- "Column1">  -->
        <div class="antibots-help-column antibots-help-column-2">
            <img alt="aux" src="<?php echo ANTIBOTSURL ?>assets/images/support.png" />
            <div class="bill-dashboard-titles"><?php esc_attr_e("OnLine Guide, Support, Faq...", "antibots"); ?></div>
            <br /><br />

            <?php
            esc_attr_e("You will find our complete and updated OnLine guide, faqs page, link to support and more in our site.", "antibots");
            ?>
            <br />

            <?php
            $site = "http://antibotsplugin.com";
            ?>
            <a href="<?php echo esc_url($site); ?>" class="button button-primary"> <?php
                                                                                    esc_attr_e('Go', 'antibots');
                                                                                    ?>
            </a>
        </div> <!-- "columns 2">  -->
        <div class="antibots-help-column antibots-help-column-3">
            <img alt="aux" src="<?php echo ANTIBOTSURL ?>assets/images/system_health.png" />
            <div class="bill-dashboard-titles"> <?php esc_attr_e("Troubleshooting Guide", "antibots"); ?></div>
            <br />
            <?php esc_attr_e("Bots showing in your statistics tool, Use old WP version, Low memory, some plugin with Javascript error are some possible problems.", "antibots"); ?>
            <br /><br />
            <a href="http://siterightaway.net/troubleshooting/" class="button button-primary"><?php esc_attr_e('Troubleshooting Page', 'antibots'); ?></a>
        </div> <!-- "Column 3">  -->
    </div> <!-- "Container1 ">  -->
</div> <!-- "services"> -->
<div id="antibots-services3">
    <div class="antibots-help-container1">
        <div class="antibots-help-2column antibots-help-column-1">
            <h3><?php esc_attr_e("Top Bots Blocked Last 15 Days", "antibots"); ?></h3>
            <?php require_once "topips.php"; ?>
        </div>
        <div class="antibots-help-2column antibots-help-column-1">
            <h3><?php esc_attr_e("Top Bots Blocked Last 24 Hours", "antibots"); ?></h3>
            <?php require_once "topips_24.php"; ?>
        </div>
        <div class="antibots-help-2column antibots-help-column-2">
            <h3><?php esc_attr_e("Bots / Human Visits", "antibots"); ?></h3>
            <br />
            <?php require_once "botsgraph_pie2.php"; ?>
            <br /><br />
        </div> <!-- "Column 3">  -->
    </div> <!-- "Container1"> -->
</div> <!-- "Services"> -->
<div id="antibots-services3">
    <div class="antibots-help-container1">
        <div class="antibots-help-2column antibots-help-column-2">
            <h3><?php esc_attr_e("Total Bots Blocked Last 15 Days", "antibots"); ?></h3>
            <br />
            <?php require_once "botsgraph.php"; ?>
            <center><?php esc_attr_e("Days", "antibots"); ?></center>
        </div> <!-- "Column 3">  -->
        <div class="antibots-help-2column antibots-help-column-2">
            <h3><?php esc_attr_e("Total Bots Blocked Last 12 Hours", "antibots"); ?></h3>
            <br />
            <?php require_once "botsgraph_24.php";
            if ($totrow > 0) {
            ?>
                <center><?php esc_attr_e("Hours", "antibots"); ?></center>
            <?php


            }

            ?>

        </div> <!-- "Column 3">  -->
    </div> <!-- "Container1"> -->
</div> <!-- "Services"> -->
<center>
    <h4> <?php esc_attr_e("With our plugin, many blocked bots will give up of attack your site !", "antibots"); ?>
    </h4>
</center>