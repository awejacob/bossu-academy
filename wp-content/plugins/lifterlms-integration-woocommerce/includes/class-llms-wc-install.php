<?php
/**
 * Plugin installation
 *
 * @package LifterLMS_WooCommerce/Classes
 *
 * @since 1.3.6
 * @version 2.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_WC_Install class.
 *
 * @since 1.3.6
 */
class LLMS_WC_Install {

	/**
	 * LLMS_WC_Background_Updater class.
	 *
	 * @var obj
	 */
	private static $background_updater = null;

	/**
	 * DB Updates callback functions by version.
	 *
	 * @var array
	 */
	private static $db_updates = array(
		'1.3.6' => array(
			'llms_wc_upgrade_136_migrate_endpoints',
			'llms_wc_upgrade_136_update_version',
		),
		'2.0.0' => array(
			'llms_wc_upgrade_200_migrate_order_item_meta',
			'llms_wc_upgrade_200_migrate_products_to_plans',
			'llms_wc_upgrade_200_update_version',
		),
		'2.0.6' => array(
			'llms_wc_upgrade_206_migrate_access_plan_price',
			'llms_wc_upgrade_206_update_version',
		),
		'2.5.0' => array(
			'llms_wc_upgrade_250_migrate_default_options',
			'llms_wc_upgrade_250_update_version',
		),
	);

	/**
	 * Initialize the install class
	 * Hooks all actions.
	 *
	 * @return   void
	 * @since    1.3.6
	 * @version  1.3.6
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
	}

	/**
	 * Retrieve the background updater class.
	 *
	 * @return  obj
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public static function get_background_updater() {
		return self::$background_updater;
	}

	/**
	 * Retrieve the DB update callbacks array.
	 *
	 * @return  array
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public static function get_db_updates() {
		return self::$db_updates;
	}

	/**
	 * Initialize the BG Updater class.
	 *
	 * @return  void
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public static function init_background_updater() {
		require_once dirname( __FILE__ ) . '/class-llms-wc-background-updater.php';
		self::$background_updater = new LLMS_WC_Background_Updater();
	}

	/**
	 * Checks the current LLMS version and runs installer if required
	 *
	 * @return   void
	 * @since    1.3.6
	 * @version  2.0.0
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'llms_integration_woocommerce_version' ), LLMS_WooCommerce()->version, '<' ) ) {
			self::install();
			/**
			 * Action: llms_wc_updated
			 *
			 * Runs after LifterLMS WooCommerce is updated.
			 *
			 * @since    1.3.6
			 * @version  2.0.0
			 */
			do_action( 'llms_wc_updated' );
		}
	}

	/**
	 * Core install function
	 *
	 * @return  void
	 * @since   1.3.6
	 * @version 2.0.0
	 */
	public static function install() {

		if ( ! is_blog_installed() ) {
			return;
		}

		/**
		 * Action: llms_wc_before_install
		 *
		 * Runs before LifterLMS WooCommerce DB ugrades are run.
		 *
		 * @since    2.0.0
		 * @version  2.0.0
		 */
		do_action( 'llms_wc_before_install' );

		self::maybe_update_db_version();

		/**
		 * Action: llms_wc_after_install
		 *
		 * Runs after LifterLMS WooCommerce DB ugrades are run.
		 *
		 * @since    2.0.0
		 * @version  2.0.0
		 */
		do_action( 'llms_wc_after_install' );

	}

	/**
	 * Do DB updates if they're required.
	 *
	 * @return  void
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public static function maybe_update_db_version() {

		if ( self::needs_db_update() ) {
			self::init_background_updater();
			self::update();
		} else {
			self::update_version();
		}

	}

	/**
	 * Determines if a DB upgrade is required
	 *
	 * If db version is null, we have a fresh install and an update is not required.
	 * If db version is set, we need an update if the version is less than the largest version in the updates array.
	 *
	 * @return  bool
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public static function needs_db_update() {

		$db_version = get_option( 'llms_integration_woocommerce_version', null );
		return ( ! is_null( $db_version ) && version_compare( $db_version, max( array_keys( self::get_db_updates() ) ), '<' ) );

	}

	/**
	 * Do DB updates.
	 *
	 * @return  void
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public static function update() {

		$curr_ver      = get_option( 'llms_integration_woocommerce_version' );
		$update_queued = false;

		foreach ( self::get_db_updates() as $version => $update_callbacks ) {

			if ( version_compare( $curr_ver, $version, '<' ) ) {

				foreach ( $update_callbacks as $update_callback ) {

					self::$background_updater->log( sprintf( 'Queuing %1$s - %2$s', $version, $update_callback ) );
					self::$background_updater->push_to_queue( $update_callback );
					$update_queued = true;

				}
			}
		}

		if ( $update_queued ) {
			/**
			 * Action: llms_wc_updates_queued
			 *
			 * Runs when LifterLMS WC Updates are queued before they're saved and dispatched.
			 *
			 * @since    2.0.0
			 * @version  2.0.0
			 */
			do_action( 'llms_wc_updates_queued' );
			self::$background_updater->save()->dispatch();
		}

	}

	/**
	 * Update the LifterLMS version record to the latest version
	 *
	 * @param  string $version version number.
	 * @return void
	 * @since    1.3.6
	 * @version  1.3.6
	 */
	public static function update_version( $version = null ) {
		delete_option( 'llms_integration_woocommerce_version' );
		add_option( 'llms_integration_woocommerce_version', is_null( $version ) ? LLMS_WooCommerce()->version : $version );
	}

}

LLMS_WC_Install::init();
