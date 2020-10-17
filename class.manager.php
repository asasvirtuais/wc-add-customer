<?php
/** 
 * @since 1.0.0
 */
namespace AsasVirtuaisWP\WCAddCustomer;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( '\AsasVirtuaisWP\WCAddCustomer\Manager' ) ) {

	/**
	 * Manager class for the plugin.
	 * 
	 * Contains methods for preventing and handling exceptions
	 * @since 1.0.0
	 */
	class Manager {

		private function __construct() {			
		}

		private static $instance;
		public static function instance() {

			if( self::$instance === null ){
				self::$instance = new self;
			}

			return self::$instance;
		}
	
		public $notices = [];
		/**
		 * Hook to admin_notices to display registered notices, errors, warnings and success messages 
		 * @since 1.0.0
		 * @return \AsasVirtuaisWP\WCAddCustomer\Manager
		 */
		public function initialize_admin() {
			$this->add_action( 'admin_notices', [ $this, 'display_admin_notices' ] );
			return $this;
		}
		/**
		 * Hooked to admin_notices
		 * @see \AsasVirtuaisWP\WCAddCustomer\Manager::initialize_admin()
		 * @return void
		 */
		public function display_admin_notices() {
			foreach ( $this->notices as $notice ) {
				echo "<div class='notice $notice->class'><p>$notice->message</p></div>";
			}
		}
		/**
		 * Adds a notice to be displayed via admin_notices hook
		 * @param string $message
		 * @param string $type
		 * @param boolean $dismissible
		 * @return \AsasVirtuaisWP\WCAddCustomer\Manager
		 */
		public function admin_notice( $message, $type = 'info', $dismissible = false ) {
			$class = $dismissible ? 'is-dismissible ' : '';
			$class .= "notice-$type";
			$this->notices[] = (object) compact( 'message', 'class' );
			return $this;
		}
		/**
		 * Adds an error to be displayed via admin_notices hook
		 * @param string $message
		 * @param boolean $dismissible
		 * @return \AsasVirtuaisWP\WCAddCustomer\Manager
		 */
		public function admin_error( $message, $dismissible = false ) {
			$this->admin_notice( $message, 'error', $dismissible );
			return $this;
		}
		/**
		 * Adds a warning to be displayed via admin_notices hook
		 * @param string $message
		 * @param boolean $dismissible
		 * @return \AsasVirtuaisWP\WCAddCustomer\Manager
		 */
		public function admin_warning( $message, $dismissible = false ) {
			$this->admin_notice( $message, 'warning', $dismissible );
			return $this;
		}
		/**
		 * Adds a success to be displayed via admin_notices hook
		 * @param string $message
		 * @param boolean $dismissible
		 * @return \AsasVirtuaisWP\WCAddCustomer\Manager
		 */
		public function admin_success( $message, $dismissible = false ) {
			$this->admin_notice( $message, 'success', $dismissible );
			return $this;
		}
		/**
		 * Receives an exception and adds a error message to be displayed via admin_notices hook
		 * @param Throwable $th
		 * @return void
		 */
		public function admin_error_from_exception( $th ) {
			$this->admin_error( $this->get_error_details( $th ) );
		}
		/**
		 * Makes a descriptive message from an exception
		 * @param Throwable $e
		 * @param string $pre_msg
		 * @param string $s
		 * @return string
		 */
		public function get_error_details( $e ) {
			$s = "\n";
			$msg = "";
			$class = get_class($e);
			$e_msg = $e->getMessage();
			$msg .= "File: {$e->getFile()}$s";
			$msg .= "Line: {$e->getLine()}$s";
			$msg .= "Type: {$class}$s";
			$msg .= "Msg: $e_msg$s";
			$previous = $e->getPrevious();
			if ( $previous ) {
				$msg .= "$s" . $this->get_error_details( $previous, '', $s );
			}
			return $msg;
		}
		/**
		 * Wraps a function in another function with a try/catch before hooking it to a action hook,
		 * returns the wrapper function.
		 * 
		 * @param string $name hook name
		 * @param callable $callback
		 * @param integer $priority
		 * @param integer $variables
		 * @return callable
		 */
		public function add_action( $name, $callback, $priority = 10, $variables = 1 ) {
			$true_callback = function( $anything = false ) use ( $callback, $variables ) {
				try {
					$args = func_get_args();
					return call_user_func_array( $callback, $args );
				} catch ( \Throwable $th ) {
					$this->admin_error_from_exception( $th );
				}
				return $anything;
			};
			add_action( $name, $true_callback, $priority, $variables );
			return $true_callback;
		}

	}
}
