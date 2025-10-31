<?php
/**
 * Handle background upgrades / migrations
 *
 * @package LifterLMS_WooCommerce/Classes
 *
 * @since 2.0.0
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_WC_Background_Updater class.
 *
 * @since 2.0.0
 */
class LLMS_WC_Background_Updater extends LLMS_Background_Updater {

	/**
	 * Action name
	 *
	 * @var  string
	 */
	protected $action = 'llms_wc_bg_updater';

	/**
	 * Enables event logging
	 *
	 * @var  boolean
	 */
	private $enable_logging = true;

	/**
	 * Constructor
	 *
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function __construct() {

		parent::__construct();

		if ( ! defined( 'LLMS_BG_UPDATE_LOG' ) ) {
			define( 'LLMS_BG_UPDATE_LOG', true );
		}

		$this->enable_logging = ( defined( 'LLMS_BG_UPDATE_LOG' ) && LLMS_BG_UPDATE_LOG );

	}

	/**
	 * Called when queue is emptied and action is complete
	 *
	 * @return   void
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	protected function complete() {
		$this->log( 'Update complete' );
		parent::complete();
	}

	/**
	 * Log event data to an update file when logging enabled
	 *
	 * @param    mixed $data  data to log.
	 * @return   void
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	public function log( $data ) {

		if ( $this->enable_logging ) {
			llms_log( $data, 'wc-updater' );
		}

	}

	/**
	 * Processes an item in the queue
	 *
	 * @param    string $callback  name of the callback function to execute.
	 * @return   mixed                 false removes item from the queue.
	 *                                 truthy (callback function name) leaves it in the queue for further processing.
	 * @since    2.0.0
	 * @version  2.0.0
	 */
	protected function task( $callback ) {

		require_once dirname( __FILE__ ) . '/functions-llms-wc-upgrades.php';

		if ( is_callable( $callback ) ) {
			$this->log( sprintf( 'Running %s callback', $callback ) );
			if ( call_user_func( $callback ) ) {
				$this->log( sprintf( '%s callback will rerun', $callback ) );
				return $callback;
			}
			$this->log( sprintf( 'Finished %s callback', $callback ) );

		} else {
			$this->log( sprintf( 'Could not find %s callback', $callback ) );
		}

		return false;

	}

}
