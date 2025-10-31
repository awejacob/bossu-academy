<?php
/**
 * @author William Sergio Minossi
 * @copyright 2016
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
//
// 



$ah_help = '<p style="font-family:arial; font-size:16px;">';
$ah_help .= '1) '.esc_attr__('Open the General Settings Tab and click over Test Mode.','antibots');
$ah_help .= '<br>';
$ah_help .= esc_attr__('Keep your site in Test Mode a couple of days.','antibots');
$ah_help .= '<br>';
$ah_help .= esc_attr__('If you mark Yes, the plugin begin to block the bots right away. Please, read all this page before click Yes.','antibots');
$ah_help .= '<br>';
$ah_help .= '<br>';


$ah_help .= '2) '.esc_attr__('You can go to WhiteList tab and manage  String and IP tables. ','antibots');
$ah_help .= '<br>';
$ah_help .= '<br>';





$ah_help .= '3) '.esc_attr__('At Email and Notifications tab, you can customize your contact email or left blank to use your WordPress eMail.','antibots');
$ah_help .= '<br>';

$ah_help .= esc_attr__('You can record your option by receive or not email alerts about bots attempts.','antibots');
$ah_help .= '<br>';

$ah_help .= esc_attr__('Remember to click Save Changes before to left each tab.','antibots');

$ah_help .= '<br>';
$ah_help .= '<br>';

$ah_help .= '<span style="background-color: #FFFF00">';
$ah_help .= '<big><b>';
$ah_help .= esc_attr__('Please, read this:','antibots'); 
$ah_help .= '</b></big>';
$ah_help .= '<br><b>';
$ah_help .= esc_attr__('Because not all bots are bad, you need manage the Whitelist Tables.','antibots'); 
$ah_help .= '</b><br>';
$ah_help .= esc_attr__('Open the Visitors Table  (under Anti Bots Menu) and take a look at Visitors List.','antibots');
$ah_help .= esc_attr__('You can see bots blocked (Response Code = 403 - Forbidden -).','antibots');
$ah_help .= esc_attr__('Click over the table title to Order. You can also type 403 (or other, as, for example, 404 - not found -) in the Search Bar.','antibots');
$ah_help .= esc_attr__('Response Code 404 (not found) they are not caused by our plugin. The plugin just record that happens to alert you.','antibots');

$ah_help .= esc_attr__('When the plugin is running in test mode, you can see Response 403 (blocked) in table but the bots are not really blocked.','antibots');
$ah_help .= esc_attr__('It is only a simulation to help you to create your particular whitelist.','antibots');


$ah_help .= '<br><b>';
$ah_help .= '<br>';
$ah_help .= esc_attr__("Check Visitor's table frequently, especially in the first days.", "antibots");
$ah_help .= esc_attr__("Maybe you will find, for example, some website checker/monitor or payment service
 (Autorize.net, Amazon or other).",'antibots');
$ah_help .= '<br>';
$ah_help .= esc_attr__('If you use RSS FEED services, probably they have their bot to read your feeds.','antibots'); 
$ah_help .= esc_attr__('Remember to Whitelist their bot.','antibots'); 
$ah_help .= esc_attr__('Same thing if you create some smartphone APP. Talk with them to know more about their bots (as name, for example).','antibots');
$ah_help .= '<br>';
$ah_help .= esc_attr__('Some search engine or social media, like Telegram, Whatsapp, Qwant, Mail.ru, LinkedIn, bitlybot, Applebot, AppleNewsBot, SkypeUriPreview, FacebookBot, twitterbot, vkShare
for example, sometimes send bots with empty user agent (or another bad practice) and our system catch them.','antibots'); 
$ah_help .= '<br>';
$ah_help .= esc_attr__('If you need more info about each bot or IP, visit the site www.StopBadBots.com (page Bots Table and Boats Table by IP)','antibots'); 
$ah_help .= '<br>';
$ah_help .= '<br>';
$ah_help .= esc_attr__('Antibots it is a powerfull tool. Then, like all powerfull tools it is necessary to use carefully.','antibots');
$ah_help .= '<br>';
$ah_help .= esc_attr__("It is up to you determine what bot is beneficial or detrimental.","antibots");
$ah_help .= '<br>';
$ah_help .= esc_attr__("Unfortunately the amount of bots is growing vertiginously. They can overload your site and you need invest time to manage this.","antibots");
$ah_help .= '</span>';
$ah_help .= '</b><br>';
$ah_help .= '<br>';
$ah_help .= esc_attr__("You don't need create any robots.txt or htaccess file. ","antibots");
$ah_help .= '<br>';
$ah_help .= '<br>';
$ah_help .= esc_attr__("The Plugin doesn't block main Google, Yahoo and Bing (Microsoft).",'antibots');
$ah_help .= '<br>';
$ah_help .= '<br>';


/*
$ah_help .= 'You have also the option to whitelist Yandex bot.';
$ah_help .= '<br>';

$ah_help .= 'Whitelist this 3 boots:';
$ah_help .= '<br>';
$ah_help .= '1) Yandex';
$ah_help .= '<br>';
$ah_help .= '2) Yandexbot';
$ah_help .= '<br>';
$ah_help .= '3) Exbot';

$ah_help .= '<br>';
$ah_help .= '<br>';
*/


$ah_help .= esc_attr__('Visit the plugin site for more detail, online guide, FAQ and Troubleshooting page and bot\'s and IP\'s details.','antibots');
$ah_help .= '<br>';
$ah_help .= '<br>';
$ah_help .= '<a href="http://antibotsplugin.com/help/" class="button button-primary">'.esc_attr__("OnLine Guide","antibots").'</a>';
$ah_help .= '&nbsp;&nbsp;';
$ah_help .= '<a href="http://billminozzi.com/dove/" class="button button-primary">'.esc_attr__("Support Page","antibots").'</a>';
$ah_help .= '&nbsp;&nbsp;';
$ah_help .= '<a href="http://siterightaway.net/troubleshooting/" class="button button-primary">'.esc_attr__("Troubleshooting Page","antibots").'</a>';
$ah_help .= '<br>';
$ah_help .= '<br>';

/*
$ah_help .= esc_attr__('That is all. Enjoy it.','antibots');
$ah_help .= '<br>';
$ah_help .= esc_attr__(' 'If you like this product, please write a few words about it. It will help other people find this useful plugin more quickly.', 'antibots'); 
$ah_help .= '<br>';
$ah_help .= '<a href="http://antibotsplugin.com/share/" class="button button-primary">'.esc_attr__("Share","antibots").'</a>';
*/
$ah_help .= '</p>';?>