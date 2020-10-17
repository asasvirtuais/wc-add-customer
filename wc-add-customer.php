<?php
/**
 * Plugin Name:       Add Customer in Dashboard
 * Plugin URI:        https://icaro-dev.pory.app/
 * Description:       Adds the page "Add Customer" to admin dashboard under the User menu with a form to quickly register WooCommerce customers
 * Version:           1.0.0
 * Author:            Ícaro C. Capobianco
 * Author URI:        https://icaro-dev.pory.app/
 * Developer:         Ícaor C. Capobianco
 * Developer URI:     https://icaro-dev.pory.app/
 * Text Domain:       wc-add-customer
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Wrap everything in a try catch
try {

	// Include needed files
	include_once 'class.manager.php';
	include_once 'class.customer-profile.php';

	// Instantiate the manager and initialize admin, hook to plugins_loaded to continue without touching global scope
	\AsasVirtuaisWP\WCAddCustomer\Manager::instance()
	->initialize_admin()
	->add_action( 'plugins_loaded', function() {

		$manager = \AsasVirtuaisWP\WCAddCustomer\Manager::instance();

		// Check for WooCommerce being active
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			$manager->admin_warning( __( 'The plugin "WC Add Customer" needs the plugin WooCommerce to be active in order to work.', 'wc-add-customer' ) );
			return;
		}

		// Hook to menu
		$manager->add_action( 'admin_menu', function() {

			// Add users page 'Add Customer'
			add_users_page(
				__( 'Add Customer', 'wc-add-customer' ),
				__( 'Add Customer', 'wc-add-customer' ),
				'create_users',
				'asas-add-customer', function() {

				// Another try catch
				try {
					$manager = \AsasVirtuaisWP\WCAddCustomer\Manager::instance();

					// Prepare the modifications to user-new.php via multiple hooks
					( new \AsasVirtuaisWP\WCAddCustomer\CustomerProfile( $manager ) )
					->init_hooks()
					->set_default_role()
					->enqueue_wc_assets();

					// Include user-new.php
					include_once ABSPATH.'/wp-admin/user-new.php';

					// Restore default role
					do_action( 'asas\wc_add_customer\restore_role' );
				} catch (\Throwable $th) {

					// Try to restore default role just in case
					do_action( 'asas\wc_add_customer\restore_role' );

					// Warn admin
					echo __( 'An error occured', 'wc-add-customer' );
					echo '<pre>' . $manager->get_error_details( $th ) . '</pre>';
				}
			}, 2 );
		} );
	} );

} catch ( \Throwable $th ) {

	// Only let the crash happen if WP_DEBUG is true
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
		throw $th;
	}

}
