<?php
/**
 * @since 1.0.0
 */
namespace AsasVirtuaisWP\WCAddCustomer;

use \Automattic\Jetpack\Constants;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( '\AsasVirtuaisWP\WCAddCustomer\CustomerProfile' ) ) {

	/**
	 * Based of of WC_Admin_CustomerProfile in version 2.4.0
	 * https://woocommerce.github.io/code-reference/classes/WC-Admin-CustomerProfile.html
	 * https://github.com/woocommerce/woocommerce/blob/master/includes/admin/class-wc-admin-profile.php
	 * 
	 */
	class CustomerProfile {

		/**
		 * Plugin Manager class
		 *
		 * @var \AsasVirtuaisWP\WCAddCustomer\Manager
		 */
		private $manager;

		public function __construct( $manager ) {
			$this->manager = $manager;
		}

		/**
		 * Hooks to user_new_form and user_register
		 * 
		 * Hook to user_new_form to display WooCommerce fields with add_customer_meta_fields
		 * Hook to user_register to save WooCommerce fields with save_customer_meta_fields
		 *
		 * @return \AsasVirtuaisWP\WCAddCustomer\CustomerProfile
		 */
		public function init_hooks() {
			$this->manager->add_action( 'user_new_form', [ $this, 'add_customer_meta_fields' ] );
			$this->manager->add_action( 'user_register', [ $this, 'save_customer_meta_fields' ] );
			return $this;
		}

		/**
		 * Change the default role to customer then back to the original
		 *
		 * Changes the default_role option to 'customer' then hooks to the internal hook 'asas\wc_add_customer\restore_role'
		 * to return back to the previous value.
		 * 
		 * @return \AsasVirtuaisWP\WCAddCustomer\CustomerProfile
		 */
		public function set_default_role() {
			$default_role = get_option( 'default_role' );
			update_option( 'default_role', 'customer' );
			$this->manager->add_action( 'asas\wc_add_customer\restore_role', function() use ( $default_role ) {
				update_option( 'default_role', $default_role );
			} );
			return $this;
		}

		/**
		 * Enqueue WooCommerce scripts
		 * 
		 * From WC_Admin_Assets 
		 * https://woocommerce.github.io/code-reference/files/woocommerce-includes-admin-class-wc-admin-assets.html#source-view.42
		 * https://woocommerce.github.io/code-reference/files/woocommerce-includes-admin-class-wc-admin-assets.html#source-view.439-448
		 *
		 * @return \AsasVirtuaisWP\WCAddCustomer\CustomerProfile
		 */
		public function enqueue_wc_assets() {
			$version = Constants::get_constant( 'WC_VERSION' );
			$suffix  = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';
			wp_register_script( 'wc-users', WC()->plugin_url() . '/assets/js/admin/users' . $suffix . '.js', array( 'jquery', 'wc-enhanced-select', 'selectWoo' ), $version, true );
			wp_enqueue_script( 'wc-users' );
			wp_localize_script(
				'wc-users',
				'wc_users_params',
				[
					'countries'              => wp_json_encode( array_merge( WC()->countries->get_allowed_country_states(), WC()->countries->get_shipping_country_states() ) ),
					'i18n_select_state_text' => esc_attr__( 'Select an option&hellip;', 'woocommerce' ),
				]
			);
			wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), $version );
			wp_enqueue_style( 'woocommerce_admin_styles' );
			return $this;
		}

		/**
		 * Based of of WC_Admin_CustomerProfile
		 * 
		 * @see \WC_Admin_CustomerProfile::get_customer_meta_fields
		 * @return mixed
		 */
		public function add_customer_meta_fields() {
			if ( ! apply_filters( 'woocommerce_current_user_can_edit_customer_meta_fields', current_user_can( 'manage_woocommerce' ), 0 ) ) {
				return;
			}

			$show_fields = $this->get_customer_meta_fields();
	
			foreach ( $show_fields as $fieldset_key => $fieldset ) :
				?>
				<h2><?php echo $fieldset['title']; ?></h2>
				<table class="form-table" id="<?php echo esc_attr( 'fieldset-' . $fieldset_key ); ?>">
					<?php foreach ( $fieldset['fields'] as $key => $field ) : ?>
						<tr>
							<th>
								<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
							</th>
							<td>
								<?php if ( ! empty( $field['type'] ) && 'select' === $field['type'] ) : ?>
									<select name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" class="<?php echo esc_attr( $field['class'] ); ?>" style="width: 25em;">
										<?php
										foreach ( $field['options'] as $option_key => $option_value ) :
											?>
											<option value="<?php echo esc_attr( $option_key ); ?>"><?php echo esc_html( $option_value ); ?></option>
										<?php endforeach; ?>
									</select>
								<?php elseif ( ! empty( $field['type'] ) && 'checkbox' === $field['type'] ) : ?>
									<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="1" class="<?php echo esc_attr( $field['class'] ); ?>" <?php checked( (int) get_user_meta( $user->ID, $key, true ), 1, true ); ?> />
								<?php elseif ( ! empty( $field['type'] ) && 'button' === $field['type'] ) : ?>
									<button type="button" id="<?php echo esc_attr( $key ); ?>" class="button <?php echo esc_attr( $field['class'] ); ?>"><?php echo esc_html( $field['text'] ); ?></button>
								<?php else : ?>
									<input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="" class="<?php echo ( ! empty( $field['class'] ) ? esc_attr( $field['class'] ) : 'regular-text' ); ?>" />
								<?php endif; ?>
								<p class="description"><?php echo wp_kses_post( $field['description'] ); ?></p>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php
			endforeach;
		}

		/**
		 * Based of of WC_Admin_CustomerProfile
		 * 
		 * @see \WC_Admin_CustomerProfile::get_customer_meta_fields
		 * @return mixed
		 */
		public function get_customer_meta_fields() {
			$show_fields = apply_filters(
				'woocommerce_customer_meta_fields',
				array(
					'billing'  => array(
						'title'  => __( 'Customer billing address', 'woocommerce' ),
						'fields' => array(
							'billing_first_name' => array(
								'label'       => __( 'First name', 'woocommerce' ),
								'description' => '',
							),
							'billing_last_name'  => array(
								'label'       => __( 'Last name', 'woocommerce' ),
								'description' => '',
							),
							'billing_company'    => array(
								'label'       => __( 'Company', 'woocommerce' ),
								'description' => '',
							),
							'billing_address_1'  => array(
								'label'       => __( 'Address line 1', 'woocommerce' ),
								'description' => '',
							),
							'billing_address_2'  => array(
								'label'       => __( 'Address line 2', 'woocommerce' ),
								'description' => '',
							),
							'billing_city'       => array(
								'label'       => __( 'City', 'woocommerce' ),
								'description' => '',
							),
							'billing_postcode'   => array(
								'label'       => __( 'Postcode / ZIP', 'woocommerce' ),
								'description' => '',
							),
							'billing_country'    => array(
								'label'       => __( 'Country / Region', 'woocommerce' ),
								'description' => '',
								'class'       => 'js_field-country',
								'type'        => 'select',
								'options'     => array( '' => __( 'Select a country / region&hellip;', 'woocommerce' ) ) + WC()->countries->get_allowed_countries(),
							),
							'billing_state'      => array(
								'label'       => __( 'State / County', 'woocommerce' ),
								'description' => __( 'State / County or state code', 'woocommerce' ),
								'class'       => 'js_field-state',
							),
							'billing_phone'      => array(
								'label'       => __( 'Phone', 'woocommerce' ),
								'description' => '',
							),
							'billing_email'      => array(
								'label'       => __( 'Email address', 'woocommerce' ),
								'description' => '',
							),
						),
					),
					'shipping' => array(
						'title'  => __( 'Customer shipping address', 'woocommerce' ),
						'fields' => array(
							'copy_billing'        => array(
								'label'       => __( 'Copy from billing address', 'woocommerce' ),
								'description' => '',
								'class'       => 'js_copy-billing',
								'type'        => 'button',
								'text'        => __( 'Copy', 'woocommerce' ),
							),
							'shipping_first_name' => array(
								'label'       => __( 'First name', 'woocommerce' ),
								'description' => '',
							),
							'shipping_last_name'  => array(
								'label'       => __( 'Last name', 'woocommerce' ),
								'description' => '',
							),
							'shipping_company'    => array(
								'label'       => __( 'Company', 'woocommerce' ),
								'description' => '',
							),
							'shipping_address_1'  => array(
								'label'       => __( 'Address line 1', 'woocommerce' ),
								'description' => '',
							),
							'shipping_address_2'  => array(
								'label'       => __( 'Address line 2', 'woocommerce' ),
								'description' => '',
							),
							'shipping_city'       => array(
								'label'       => __( 'City', 'woocommerce' ),
								'description' => '',
							),
							'shipping_postcode'   => array(
								'label'       => __( 'Postcode / ZIP', 'woocommerce' ),
								'description' => '',
							),
							'shipping_country'    => array(
								'label'       => __( 'Country / Region', 'woocommerce' ),
								'description' => '',
								'class'       => 'js_field-country',
								'type'        => 'select',
								'options'     => array( '' => __( 'Select a country / region&hellip;', 'woocommerce' ) ) + WC()->countries->get_allowed_countries(),
							),
							'shipping_state'      => array(
								'label'       => __( 'State / County', 'woocommerce' ),
								'description' => __( 'State / County or state code', 'woocommerce' ),
								'class'       => 'js_field-state',
							),
						),
					),
				)
			);
			return $show_fields;
		}

		/** Save customer meta fields */
		public function save_customer_meta_fields( $user_id ) {
			if ( ! apply_filters( 'woocommerce_current_user_can_edit_customer_meta_fields', current_user_can( 'manage_woocommerce' ), $user_id ) ) {
				return;
			}

			$save_fields = $this->get_customer_meta_fields();

			foreach ( $save_fields as $fieldset ) {

				foreach ( $fieldset['fields'] as $key => $field ) {

					if ( isset( $field['type'] ) && 'checkbox' === $field['type'] ) {
						update_user_meta( $user_id, $key, isset( $_POST[ $key ] ) );
					} elseif ( isset( $_POST[ $key ] ) ) {
						update_user_meta( $user_id, $key, wc_clean( $_POST[ $key ] ) );
					}
				}
			}
		}

		/**
		 * Returns empty user metadata
		 *
		 * @param string $user_id
		 * @param string $key
		 * @return void
		 */
		protected function get_user_meta() {
			return (object) [ 'user_email' => '', 'ID' => 0 ];
		}
	}
}
