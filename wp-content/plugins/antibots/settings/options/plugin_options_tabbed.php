<?php

namespace AntibotsWPSettings;
// $mypage = new Page('Anti Bots', array('type' => 'menu'));
$mypage = new antibot_Page('Settings Anti Bots', array('type' => 'submenu', 'parent_slug' => 'anti_bots_plugin'));
$settings = array();
require_once(ANTIBOTSPATH . "guide/guide.php");
$settings['Startup Guide']['Startup Guide'] = array('info' => $ah_help);
$fields = array();
$settings['Startup Guide']['Startup Guide']['fields'] = $fields;
$msg2 = '<b>' . esc_attr__('Block all Bots Less Whitelisted?', 'antibots') . '</b>';
$msg2 .= '<br />';
$msg2 .= esc_attr__('Mark Yes and the plugin will block bots right away.', 'antibots');
$msg2 .= '<strong>' . esc_attr__('Please, read the StartUp Guide before mark yes.', 'antibots') . '</strong>';
$msg2 .= '<br />';
$msg2 .= esc_attr__('Mark Test Mode and the plugin will create a log of visitors and statistics but doesn\'t block any bot.', 'antibots');
$msg2 .= '<br />';
$msg2 .= esc_attr__('Then click SAVE CHANGES.', 'antibots');
$msg2 .= '<br />';
$msg2 .= '<br />';
$settings['General Settings'][esc_attr__('Instructions')] = array('info' => $msg2);
$fields = array();
$fields[] = array(
	'type'  => 'radio',
	'name'  => 'antibots_is_active',
	'label' => esc_attr__('Block all Bots Less Whitelisted?'),
	'radio_options' => array(
		array('value' => 'yes', 'label' => esc_attr__('yes')),
		array('value' => 'test', 'label' => esc_attr__('Test mode'))
	)
);
// Select List
$fields[] = array(
	'type'  => 'select',
	'name'  => 'antibots_keep_data',
	'label' => esc_attr__('Keep Visitor Record Max', 'antibots'),
	'id' => 'antibots_keep_data', // (optional, will default to name)
	'value' => '4', // (optional, will default to '')
	'select_options' => array(
		array('value' => '1', 'label' => esc_attr__('1 Week', "antibots")),
		array('value' => '2', 'label' => esc_attr__('2 Weeks', "antibots")),
		array('value' => '3', 'label' => esc_attr__('3 Weeks', "antibots")),
		array('value' => '4', 'label' => esc_attr__('4 Weeks', "antibots")),
		array('value' => '5', 'label' => esc_attr__('5 Weeks', "antibots")),
		array('value' => '6', 'label' => esc_attr__('6 Weeks', "antibots")),
		array('value' => '7', 'label' => esc_attr__('7 Weeks', "antibots")),
		array('value' => '8', 'label' => esc_attr__('8 Weeks', "antibots")),
	)
);
$settings['General Settings']['Configuration']['fields'] = $fields; // Fixed the empty key
$msg2  = '<b>' . esc_attr__('You can create 2 whitelist in this page: String and IP.', 'antibots') . '</b>';
$msg2 .= '<br />';
$msg2 .= esc_attr__('Just add one string to unblock all User Agent that contain that string.', 'antibots');
$msg2 .= '<br />';
$msg2 .= esc_attr__('For IP withelist, just add the IP to unblock it.', 'antibots');
$msg2 .= '<br />';
$msg2 .= esc_attr__('Add only one for each line.', 'antibots');
$msg2 .= '<br />';
$settings['Whitelist'][esc_attr__('Instructions about User Agent String and IP Whitelist.', 'antibots')] = array('info' => $msg2);
$fields = array();
$fields[] = array(
	'type'  => 'radio',
	'name'  => 'antibots_enable_whitelist',
	'label' => esc_attr__('Enable Both Withelist?', 'antibots'),
	'radio_options' => array(
		array('value' => 'yes', 'label' => esc_attr__('yes', "antibots")),
		array('value' => 'no', 'label' => esc_attr__('no', "antibots"))
	)
);
$fields[] = array(
	'type'  => 'textarea',
	'name'  => 'antibots_string_whitelist',
	'label' => esc_attr__('String whitelist (no case sensitive)', 'antibots'),
);
$fields[] = array(
	'type'  => 'textarea',
	'name'  => 'antibots_ip_whitelist',
	'label' => esc_attr__('IP whitelist.', 'antibots') . ' ' . esc_attr__('Your Current IP:', 'antibots') . ' ' . antibots_findip(),
);
$settings['Whitelist']['Whitelist Tables']['fields'] = $fields;
$msg_email = esc_attr__('Fill out the email address to send messages.', 'antibots');
$msg_email .= '<br />';
$msg_email .= esc_attr__('Left Blank to use your default WordPress email. Then, click save changes.', 'antibots');
$fields = array();
$fields[] = array(
	'type'  => 'text',
	'name'  => 'antibots_my_email_to',
	'label' => 'email'
);
$notificatin_msg = esc_attr__('Do you want receive email alerts for each bot attempt?', 'antibots');
$notificatin_msg .= '<br />';
$notificatin_msg .= esc_attr__('If you under brute force attack, you will receive a lot of emails.', 'antibots');
$notificatin_msg .= '<br />';
$notificatin_msg .= esc_attr__('You can see the bots attacks info at Visitors Log Table. (column Num Blocked).', 'antibots');
$fields[] = array(
	'type'  => 'radio',
	'name'  => 'antibots_my_radio_report_all_visits',
	'label' => esc_attr__('Alert me by email each Bots Attempts', 'antibots'),
	'radio_options' => array(
		array('value' => 'yes', 'label' => esc_attr__('yes', "antibots")),
		array('value' => 'no', 'label' => esc_attr__('no', "antibots"))
	)
);
$settings['Email and Notifications'][esc_attr__('Email and Notifications', 'antibots')]['fields'] = $fields;
new OptionPageBuilderTabbed($mypage, $settings);
