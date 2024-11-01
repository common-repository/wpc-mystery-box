<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCleverWpcmb' ) && class_exists( 'WC_Product' ) ) {
	class WPCleverWpcmb {
		protected static $instance = null;

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		function __construct() {
			/* Backend */

			// settings
			add_action( 'admin_init', [ $this, 'register_settings' ] );
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
			add_filter( 'display_post_states', [ $this, 'display_post_states' ], 10, 2 );
			add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
			add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

			// ajax
			add_action( 'wp_ajax_wpcmb_add_assortment', [ $this, 'ajax_add_assortment' ] );
			add_action( 'wp_ajax_wpcmb_save_assortments', [ $this, 'ajax_save_assortments' ] );
			add_action( 'wp_ajax_wpcmb_export_assortments', [ $this, 'ajax_export_assortments' ] );
			add_action( 'wp_ajax_wpcmb_search_term', [ $this, 'ajax_search_term' ] );
			add_action( 'wp_ajax_wpcmb_search_product', [ $this, 'ajax_search_product' ] );

			// product editor
			add_filter( 'product_type_selector', [ $this, 'product_type_selector' ] );
			add_filter( 'woocommerce_product_data_tabs', [ $this, 'product_data_tabs' ] );
			add_action( 'woocommerce_product_data_panels', [ $this, 'product_data_panels' ] );
			add_action( 'woocommerce_process_product_meta_wpcmb', [ $this, 'process_meta_wpcmb' ] );

			/* Frontend */

			// shortcode
			add_shortcode( 'wpcmb', [ $this, 'shortcode' ] );

			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
			add_filter( 'woocommerce_get_price_html', [ $this, 'get_price_html' ], 99, 2 );
			add_action( 'woocommerce_wpcmb_add_to_cart', [ $this, 'add_to_cart_form' ] );

			// cart
			add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'add_to_cart_validation' ], 10, 2 );
			add_action( 'woocommerce_check_cart_items', [ $this, 'check_cart_items' ], 1 );

			// order
			add_filter( 'woocommerce_order_item_visible', [ $this, 'order_item_visible' ], 10, 2 );
			add_filter( 'woocommerce_available_payment_gateways', [ $this, 'available_payment_gateways' ] );
			add_action( 'woocommerce_hidden_order_itemmeta', [ $this, 'hidden_order_itemmeta' ], 10, 2 );
			add_action( 'woocommerce_order_status_processing', [ $this, 'add_assortment_products' ], 1 );
			add_action( 'woocommerce_order_status_completed', [ $this, 'add_assortment_products' ], 1 );

			// order confirmation or emails
			if ( WPCleverWpcmb_Helper()->get_setting( 'hide_assortment', 'no' ) === 'yes_text' || WPCleverWpcmb_Helper()->get_setting( 'hide_assortment', 'no' ) === 'yes_list' ) {
				add_action( 'woocommerce_order_item_meta_start', [ $this, 'order_item_meta_start' ], 10, 3 );
			}

			// admin order
			add_action( 'woocommerce_before_order_itemmeta', [ $this, 'order_item_meta_start' ], 10, 3 );

			// WPC Smart Messages
			add_filter( 'wpcsm_locations', [ $this, 'wpcsm_locations' ] );
		}

		function assortment( $active = false, $assortment = [], $key = null ) {
			if ( ! $key ) {
				$key = WPCleverWpcmb_Helper()->generate_key();
			}

			$assortment_default = [
				'name'       => 'Name',
				'desc'       => 'Description',
				'type'       => '',
				'products'   => [],
				'terms'      => [],
				'orderby'    => 'default',
				'order'      => 'default',
				'exclude'    => [],
				'number'     => 1,
				'quantity'   => 1,
				'necessary'  => 'normal',
				'prioritize' => 'random'
			];

			if ( ! empty( $assortment ) ) {
				$assortment = array_merge( $assortment_default, $assortment );
			} else {
				$assortment = $assortment_default;
			}
			?>
            <tr class="wpcmb_assortment">
                <td>
                    <div class="wpcmb_assortment_inner <?php echo esc_attr( $active ? 'active' : '' ); ?>">
                        <div class="wpcmb_assortment_heading">
                            <span class="wpcmb_move_assortment"></span>
                            <span class="wpcmb_assortment_name"><?php echo esc_html( $assortment['name'] ); ?></span>
                            <a class="wpcmb_duplicate_assortment" href="#"><?php esc_html_e( 'duplicate', 'wpc-mystery-box' ); ?></a>
                            <a class="wpcmb_remove_assortment" href="#"><?php esc_html_e( 'remove', 'wpc-mystery-box' ); ?></a>
                        </div>
                        <div class="wpcmb_assortment_content">
                            <div class="wpcmb_assortment_content_line">
                                <div class="wpcmb_assortment_content_line_label">
									<?php esc_html_e( 'Name', 'wpc-mystery-box' ); ?>
                                </div>
                                <div class="wpcmb_assortment_content_line_value">
                                    <label>
                                        <input name="<?php echo esc_attr( 'wpcmb_assortments[' . $key . '][name]' ); ?>" type="text" class="wpcmb_assortment_name_val" value="<?php echo esc_html( $assortment['name'] ); ?>"/>
                                    </label>
                                </div>
                            </div>
                            <div class="wpcmb_assortment_content_line">
                                <div class="wpcmb_assortment_content_line_label">
									<?php esc_html_e( 'Description', 'wpc-mystery-box' ); ?>
                                </div>
                                <div class="wpcmb_assortment_content_line_value">
                                    <label>
                                        <textarea name="<?php echo esc_attr( 'wpcmb_assortments[' . $key . '][desc]' ); ?>"><?php echo esc_textarea( $assortment['desc'] ); ?></textarea>
                                    </label>
                                </div>
                            </div>
                            <div class="wpcmb_assortment_content_line">
                                <div class="wpcmb_assortment_content_line_label">
									<?php esc_html_e( 'Source', 'wpc-mystery-box' ); ?>
                                </div>
                                <div class="wpcmb_assortment_content_line_value">
                                    <label>
                                        <select name="<?php echo esc_attr( 'wpcmb_assortments[' . $key . '][type]' ); ?>" class="wpcmb_assortment_type">
                                            <option value=""><?php esc_html_e( 'Select source', 'wpc-mystery-box' ); ?></option>
                                            <option value="products" <?php selected( $assortment['type'], 'products' ); ?>><?php esc_html_e( 'Products', 'wpc-mystery-box' ); ?></option>
											<?php
											$taxonomies = get_object_taxonomies( 'product', 'objects' ); //$taxonomies = get_taxonomies( [ 'object_type' => [ 'product' ] ], 'objects' );

											foreach ( $taxonomies as $taxonomy ) {
												echo '<option value="' . esc_attr( $taxonomy->name ) . '" ' . selected( $assortment['type'], $taxonomy->name, false ) . ' disabled>' . esc_html( $taxonomy->label ) . '</option>';
											}
											?>
                                        </select> </label>
                                    <span><?php esc_html_e( 'Order by', 'wpc-mystery-box' ); ?> <label>
<select name="<?php echo esc_attr( 'wpcmb_assortments[' . $key . '][orderby]' ); ?>">
                    <option value="default" <?php selected( $assortment['orderby'], 'default' ); ?>><?php esc_html_e( 'Default', 'wpc-mystery-box' ); ?></option>
                    <option value="none" <?php selected( $assortment['orderby'], 'none' ); ?>><?php esc_html_e( 'None', 'wpc-mystery-box' ); ?></option>
                    <option value="ID" <?php selected( $assortment['orderby'], 'ID' ); ?>><?php esc_html_e( 'ID', 'wpc-mystery-box' ); ?></option>
                    <option value="name" <?php selected( $assortment['orderby'], 'name' ); ?>><?php esc_html_e( 'Name', 'wpc-mystery-box' ); ?></option>
                    <option value="type" <?php selected( $assortment['orderby'], 'type' ); ?>><?php esc_html_e( 'Type', 'wpc-mystery-box' ); ?></option>
                    <option value="rand" <?php selected( $assortment['orderby'], 'rand' ); ?>><?php esc_html_e( 'Rand', 'wpc-mystery-box' ); ?></option>
                    <option value="date" <?php selected( $assortment['orderby'], 'date' ); ?>><?php esc_html_e( 'Date', 'wpc-mystery-box' ); ?></option>
                    <option value="price" <?php selected( $assortment['orderby'], 'price' ); ?>><?php esc_html_e( 'Price', 'wpc-mystery-box' ); ?></option>
                    <option value="modified" <?php selected( $assortment['orderby'], 'modified' ); ?>><?php esc_html_e( 'Modified', 'wpc-mystery-box' ); ?></option>
                </select>
</label></span> &nbsp; <span><?php esc_html_e( 'Order', 'wpc-mystery-box' ); ?> <label>
<select name="<?php echo esc_attr( 'wpcmb_assortments[' . $key . '][order]' ); ?>">
                    <option value="default" <?php selected( $assortment['order'], 'default' ); ?>><?php esc_html_e( 'Default', 'wpc-mystery-box' ); ?></option>
                    <option value="DESC" <?php selected( $assortment['order'], 'DESC' ); ?>><?php esc_html_e( 'DESC', 'wpc-mystery-box' ); ?></option>
                    <option value="ASC" <?php selected( $assortment['order'], 'ASC' ); ?>><?php esc_html_e( 'ASC', 'wpc-mystery-box' ); ?></option>
                    </select>
</label></span>
                                </div>
                            </div>
                            <div class="wpcmb_assortment_content_line wpcmb_hide wpcmb_show_if_terms">
                                <div class="wpcmb_assortment_content_line_label wpcmb_assortment_type_label">
									<?php esc_html_e( 'Terms', 'wpc-mystery-box' ); ?>
                                </div>
                                <div class="wpcmb_assortment_content_line_value">
                                    <label>
                                        <select class="wpcmb_terms" multiple="multiple" name="<?php echo esc_attr( 'wpcmb_assortments[' . $key . '][terms][]' ); ?>" data-<?php echo esc_attr( $assortment['type'] ); ?>="<?php echo esc_attr( implode( ',', $assortment['terms'] ) ); ?>">
											<?php
											if ( ! empty( $assortment['terms'] ) ) {
												foreach ( $assortment['terms'] as $t ) {
													if ( $term = get_term_by( 'slug', $t, $assortment['type'] ) ) {
														echo '<option value="' . esc_attr( $t ) . '" selected>' . esc_html( $term->name ) . '</option>';
													}
												}
											}
											?>
                                        </select> </label>
                                </div>
                            </div>
                            <div class="wpcmb_assortment_content_line wpcmb_hide wpcmb_show_if_products">
                                <div class="wpcmb_assortment_content_line_label">
									<?php esc_html_e( 'Products', 'wpc-mystery-box' ); ?>
                                </div>
                                <div class="wpcmb_assortment_content_line_value">
                                    <label>
                                        <select class="wpcmb_products" data-allow_clear="false" data-sortable="1" multiple="multiple" name="<?php echo esc_attr( 'wpcmb_assortments[' . $key . '][products][]' ); ?>" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'wpc-mystery-box' ); ?>">
											<?php
											if ( ! empty( $assortment['products'] ) ) {
												foreach ( $assortment['products'] as $_product_id ) {
													if ( ! empty( $_product_id ) ) {
														if ( is_numeric( $_product_id ) ) {
															// id
															$_product = wc_get_product( $_product_id );
														} else {
															// sku
															$_product = wc_get_product( wc_get_product_id_by_sku( $_product_id ) );
														}

														if ( $_product ) {
															echo '<option value="' . esc_attr( $_product_id ) . '" selected="selected">' . wp_kses_post( $_product->get_formatted_name() ) . '</option>';
														}
													}
												}
											}
											?>
                                        </select> </label>
                                </div>
                            </div>
                            <div class="wpcmb_assortment_content_line wpcmb_show wpcmb_hide_if_products">
                                <div class="wpcmb_assortment_content_line_label">
									<?php esc_html_e( 'Exclude', 'wpc-mystery-box' ); ?>
                                </div>
                                <div class="wpcmb_assortment_content_line_value">
                                    <label>
                                        <select class="wpcmb_products" data-allow_clear="false" style="width: 100%;" data-sortable="1" multiple="multiple" name="<?php echo esc_attr( 'wpcmb_assortments[' . $key . '][exclude][]' ); ?>" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'wpc-mystery-box' ); ?>">
											<?php
											if ( ! empty( $assortment['exclude'] ) ) {
												foreach ( $assortment['exclude'] as $_product_id ) {
													if ( ! empty( $_product_id ) ) {
														if ( is_numeric( $_product_id ) ) {
															// id
															$_product = wc_get_product( $_product_id );
														} else {
															// sku
															$_product = wc_get_product( wc_get_product_id_by_sku( $_product_id ) );
														}

														if ( $_product ) {
															echo '<option value="' . esc_attr( $_product_id ) . '" selected="selected">' . wp_kses_post( $_product->get_formatted_name() ) . '</option>';
														}
													}
												}
											}
											?>
                                        </select> </label>
                                </div>
                            </div>
                            <div class="wpcmb_assortment_content_line">
                                <div class="wpcmb_assortment_content_line_label">
									<?php esc_html_e( 'Number of products picked', 'wpc-mystery-box' ); ?>
                                </div>
                                <div class="wpcmb_assortment_content_line_value">
                                    <label>
                                        <input name="<?php echo esc_attr( 'wpcmb_assortments[' . $key . '][number]' ); ?>" type="number" min="1" step="1" value="<?php echo esc_attr( $assortment['number'] ); ?>"/>
                                    </label>
                                </div>
                            </div>
                            <div class="wpcmb_assortment_content_line">
                                <div class="wpcmb_assortment_content_line_label">
									<?php esc_html_e( 'Priority', 'wpc-mystery-box' ); ?>
                                </div>
                                <div class="wpcmb_assortment_content_line_value">
                                    <label>
                                        <select name="<?php echo esc_attr( 'wpcmb_assortments[' . $key . '][prioritize]' ); ?>">
                                            <option value="random" <?php selected( $assortment['prioritize'], 'random' ); ?>><?php esc_html_e( 'Randomized', 'wpc-mystery-box' ); ?></option>
                                            <option value="high_stock" <?php selected( $assortment['prioritize'], 'high_stock' ); ?>><?php esc_html_e( 'High to low stock', 'wpc-mystery-box' ); ?></option>
                                            <option value="low_stock" <?php selected( $assortment['prioritize'], 'low_stock' ); ?>><?php esc_html_e( 'Low to high stock', 'wpc-mystery-box' ); ?></option>
                                            <option value="orderby" <?php selected( $assortment['prioritize'], 'orderby' ); ?>><?php esc_html_e( 'Order of addition above', 'wpc-mystery-box' ); ?></option>
                                        </select> </label>
                                    <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'The priority level when picking out products for this assortment.', 'wpc-mystery-box' ); ?>"></span>
                                </div>
                            </div>
                            <div class="wpcmb_assortment_content_line">
                                <div class="wpcmb_assortment_content_line_label">
									<?php esc_html_e( 'Each product\'s quantity', 'wpc-mystery-box' ); ?>
                                </div>
                                <div class="wpcmb_assortment_content_line_value">
                                    <label>
                                        <input name="<?php echo esc_attr( 'wpcmb_assortments[' . $key . '][quantity]' ); ?>" type="number" min="0" step="1" value="<?php echo esc_attr( $assortment['quantity'] ); ?>"/>
                                    </label>
                                </div>
                            </div>
                            <div class="wpcmb_assortment_content_line">
                                <div class="wpcmb_assortment_content_line_label">
									<?php esc_html_e( 'Necessary level', 'wpc-mystery-box' ); ?>
                                </div>
                                <div class="wpcmb_assortment_content_line_value">
                                    <label>
                                        <select name="<?php echo esc_attr( 'wpcmb_assortments[' . $key . '][necessary]' ); ?>">
                                            <option value="normal" <?php selected( $assortment['necessary'], 'normal' ); ?>><?php esc_html_e( 'Normal', 'wpc-mystery-box' ); ?></option>
                                            <option value="required" <?php selected( $assortment['necessary'], 'required' ); ?>><?php esc_html_e( 'Required', 'wpc-mystery-box' ); ?></option>
                                            <option value="lucky" <?php selected( $assortment['necessary'], 'lucky' ); ?>><?php esc_html_e( 'Lucky', 'wpc-mystery-box' ); ?></option>
                                        </select> </label>
                                    <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'When remaining stocks are insufficient or no valid products are found: Normal assortment will be skipped, Required assortment will make the whole box unpurchasable, Lucky assortment will randomly be added or not.', 'wpc-mystery-box' ); ?>"></span>
                                </div>
                            </div>
                        </div>
                </td>
            </tr>
		<?php }

		function ajax_add_assortment() {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wpcmb-security' ) ) {
				die( 'Permissions check failed!' );
			}

			$assortment = [];
			$form_data  = isset( $_POST['form_data'] ) ? sanitize_post( $_POST['form_data'] ) : '';

			if ( ! empty( $form_data ) ) {
				$assortments = [];
				parse_str( $form_data, $assortments );

				if ( isset( $assortments['wpcmb_assortments'] ) && is_array( $assortments['wpcmb_assortments'] ) ) {
					$assortment = reset( $assortments['wpcmb_assortments'] );
				}
			}

			self::assortment( true, $assortment );
			wp_die();
		}

		function ajax_save_assortments() {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wpcmb-security' ) ) {
				die( 'Permissions check failed!' );
			}

			$pid       = sanitize_text_field( $_POST['pid'] );
			$form_data = isset( $_POST['form_data'] ) ? sanitize_post( $_POST['form_data'] ) : '';

			if ( $pid && $form_data ) {
				$assortments = [];
				parse_str( $form_data, $assortments );

				if ( isset( $assortments['wpcmb_assortments'] ) ) {
					update_post_meta( $pid, 'wpcmb_assortments', WPCleverWpcmb_Helper()->sanitize_array( $assortments['wpcmb_assortments'] ) );
				}
			}

			wp_die();
		}

		function ajax_export_assortments() {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wpcmb-security' ) ) {
				die( 'Permissions check failed!' );
			}

			$product_id  = isset( $_POST['pid'] ) ? absint( $_POST['pid'] ) : 0;
			$assortments = get_post_meta( $product_id, 'wpcmb_assortments', true );
			echo '<textarea style="width: 100%; height: 200px">' . ( ! empty( $assortments ) ? esc_textarea( serialize( $assortments ) ) : '' ) . '</textarea>';
			echo '<div>' . esc_html__( 'You can copy this field and use it for a CSV import file.', 'wpc-mystery-box' ) . '</div>';

			wp_die();
		}

		function ajax_search_term() {
			if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'wpcmb-security' ) ) {
				die( 'Permissions check failed!' );
			}

			$return = [];

			$args = [
				'taxonomy'   => sanitize_text_field( $_REQUEST['taxonomy'] ),
				'orderby'    => 'id',
				'order'      => 'ASC',
				'hide_empty' => false,
				'fields'     => 'all',
				'name__like' => sanitize_text_field( $_REQUEST['term'] ),
			];

			$terms = get_terms( $args );

			if ( count( $terms ) ) {
				foreach ( $terms as $term ) {
					$return[] = [ $term->slug, $term->name ];
				}
			}

			wp_send_json( $return );
		}

		function ajax_search_product() {
			if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'wpcmb-security' ) ) {
				die( 'Permissions check failed!' );
			}

			if ( isset( $_REQUEST['term'] ) ) {
				$term = (string) wc_clean( wp_unslash( $_REQUEST['term'] ) );
			}

			if ( empty( $term ) ) {
				wp_die();
			}

			$products   = [];
			$limit      = absint( apply_filters( 'wpcmb_json_search_limit', 30 ) );
			$data_store = WC_Data_Store::load( 'product' );
			$ids        = $data_store->search_products( $term, '', true, false, $limit );

			foreach ( $ids as $id ) {
				$product_object = wc_get_product( $id );

				if ( ! wc_products_array_filter_readable( $product_object ) ) {
					continue;
				}

				$formatted_name = $product_object->get_formatted_name();

				if ( apply_filters( 'wpcmb_use_sku', false ) ) {
					$products[] = [
						$product_object->get_sku() ?: $product_object->get_id(),
						rawurldecode( wp_strip_all_tags( $formatted_name ) )
					];
				} else {
					$products[] = [
						$product_object->get_id(),
						rawurldecode( wp_strip_all_tags( $formatted_name ) )
					];
				}
			}

			wp_send_json( apply_filters( 'wpcmb_json_search_found_products', $products ) );
		}

		function register_settings() {
			// settings
			register_setting( 'wpcmb_settings', 'wpcmb_settings' );

			// localization
			register_setting( 'wpcmb_localization', 'wpcmb_localization' );
		}

		function admin_menu() {
			add_submenu_page( 'wpclever', esc_html__( 'WPC Mystery Box', 'wpc-mystery-box' ), esc_html__( 'Mystery Box', 'wpc-mystery-box' ), 'manage_options', 'wpclever-wpcmb', [
				$this,
				'admin_menu_content'
			] );
		}

		function admin_menu_content() {
			add_thickbox();
			$active_tab = sanitize_key( $_GET['tab'] ?? 'settings' );
			?>
            <div class="wpclever_settings_page wrap">
                <h1 class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Mystery Box', 'wpc-mystery-box' ) . ' ' . esc_html( WPCMB_VERSION ) . ' ' . ( defined( 'WPCMB_PREMIUM' ) ? '<span class="premium" style="display: none">' . esc_html__( 'Premium', 'wpc-mystery-box' ) . '</span>' : '' ); ?></h1>
                <div class="wpclever_settings_page_desc about-text">
                    <p>
						<?php printf( /* translators: stars */ esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'wpc-mystery-box' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                        <br/>
                        <a href="<?php echo esc_url( WPCMB_REVIEWS ); ?>" target="_blank"><?php esc_html_e( 'Reviews', 'wpc-mystery-box' ); ?></a> |
                        <a href="<?php echo esc_url( WPCMB_CHANGELOG ); ?>" target="_blank"><?php esc_html_e( 'Changelog', 'wpc-mystery-box' ); ?></a> |
                        <a href="<?php echo esc_url( WPCMB_DISCUSSION ); ?>" target="_blank"><?php esc_html_e( 'Discussion', 'wpc-mystery-box' ); ?></a>
                    </p>
                </div>
				<?php if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) { ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php esc_html_e( 'Settings updated.', 'wpc-mystery-box' ); ?></p>
                    </div>
				<?php } ?>
                <div class="wpclever_settings_page_nav">
                    <h2 class="nav-tab-wrapper">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpcmb&tab=how' ) ); ?>" class="<?php echo $active_tab === 'how' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>">
							<?php esc_html_e( 'How to use?', 'wpc-mystery-box' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpcmb&tab=settings' ) ); ?>" class="<?php echo $active_tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>">
							<?php esc_html_e( 'Settings', 'wpc-mystery-box' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpcmb&tab=localization' ) ); ?>" class="<?php echo $active_tab === 'localization' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>">
							<?php esc_html_e( 'Localization', 'wpc-mystery-box' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-wpcmb&tab=premium' ) ); ?>" class="<?php echo esc_attr( $active_tab === 'premium' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>" style="color: #c9356e">
							<?php esc_html_e( 'Premium Version', 'wpc-mystery-box' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-kit' ) ); ?>" class="nav-tab">
							<?php esc_html_e( 'Essential Kit', 'wpc-mystery-box' ); ?>
                        </a>
                    </h2>
                </div>
                <div class="wpclever_settings_page_content">
					<?php if ( $active_tab === 'how' ) { ?>
                        <div class="wpclever_settings_page_content_text">
                            <p>
								<?php esc_html_e( 'When creating the product, please choose product data is "Smart mystery box" then you can add assortments and other settings for this mystery box.', 'wpc-mystery-box' ); ?>
                            </p>
                        </div>
						<?php
					} elseif ( $active_tab === 'settings' ) {
						$position          = WPCleverWpcmb_Helper()->get_setting( 'position', 'above' );
						$layout            = WPCleverWpcmb_Helper()->get_setting( 'layout', 'list' );
						$exclude_hidden    = WPCleverWpcmb_Helper()->get_setting( 'exclude_hidden', 'no' );
						$show_qty          = WPCleverWpcmb_Helper()->get_setting( 'show_qty', 'yes' );
						$show_image        = WPCleverWpcmb_Helper()->get_setting( 'show_image', 'yes' );
						$show_price        = WPCleverWpcmb_Helper()->get_setting( 'show_price', 'yes' );
						$show_availability = WPCleverWpcmb_Helper()->get_setting( 'show_availability', 'yes' );
						$product_link      = WPCleverWpcmb_Helper()->get_setting( 'product_link', 'yes' );
						$together          = WPCleverWpcmb_Helper()->get_setting( 'together', 'yes' );
						$hide_assortment   = WPCleverWpcmb_Helper()->get_setting( 'hide_assortment', 'no' );
						?>
                        <form method="post" action="options.php">
                            <table class="form-table">
                                <tr class="heading">
                                    <th colspan="2">
										<?php esc_html_e( 'General', 'wpc-mystery-box' ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Position', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label> <select name="wpcmb_settings[position]">
                                                <option value="above" <?php selected( $position, 'above' ); ?>><?php esc_html_e( 'Above add to cart button', 'wpc-mystery-box' ); ?></option>
                                                <option value="under" <?php selected( $position, 'under' ); ?>><?php esc_html_e( 'Under add to cart button', 'wpc-mystery-box' ); ?></option>
                                                <option value="none" <?php selected( $position, 'none' ); ?>><?php esc_html_e( 'None (hide it)', 'wpc-mystery-box' ); ?></option>
                                            </select> </label>
                                        <p class="description"><?php esc_html_e( 'Choose a position to show the assortment product list on the mystery box page. You also can use the shortcode [wpcmb] to show the list where you want.', 'wpc-mystery-box' ); ?></p>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Layout', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label> <select name="wpcmb_settings[layout]">
                                                <option value="list" <?php selected( $layout, 'list' ); ?>><?php esc_html_e( 'List', 'wpc-mystery-box' ); ?></option>
                                                <option value="grid-2" <?php selected( $layout, 'grid-2' ); ?>><?php esc_html_e( 'Grid - 2 columns', 'wpc-mystery-box' ); ?></option>
                                                <option value="grid-3" <?php selected( $layout, 'grid-3' ); ?>><?php esc_html_e( 'Grid - 3 columns', 'wpc-mystery-box' ); ?></option>
                                                <option value="grid-4" <?php selected( $layout, 'grid-4' ); ?>><?php esc_html_e( 'Grid - 4 columns', 'wpc-mystery-box' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Exclude hidden', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label> <select name="wpcmb_settings[exclude_hidden]">
                                                <option value="yes" <?php selected( $exclude_hidden, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-mystery-box' ); ?></option>
                                                <option value="no" <?php selected( $exclude_hidden, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-mystery-box' ); ?></option>
                                            </select> </label>
                                        <span class="description"><?php esc_html_e( 'Exclude hidden products from the list.', 'wpc-mystery-box' ); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Show quantity', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label> <select name="wpcmb_settings[show_qty]">
                                                <option value="yes" <?php selected( $show_qty, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-mystery-box' ); ?></option>
                                                <option value="no" <?php selected( $show_qty, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-mystery-box' ); ?></option>
                                            </select> </label>
                                        <span class="description"><?php esc_html_e( 'Show the quantity number before assortment product name.', 'wpc-mystery-box' ); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Show image', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label> <select name="wpcmb_settings[show_image]">
                                                <option value="yes" <?php selected( $show_image, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-mystery-box' ); ?></option>
                                                <option value="no" <?php selected( $show_image, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-mystery-box' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Show price', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label> <select name="wpcmb_settings[show_price]">
                                                <option value="yes" <?php selected( $show_price, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-mystery-box' ); ?></option>
                                                <option value="no" <?php selected( $show_price, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-mystery-box' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Show availability', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label> <select name="wpcmb_settings[show_availability]">
                                                <option value="yes" <?php selected( $show_availability, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-mystery-box' ); ?></option>
                                                <option value="no" <?php selected( $show_availability, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-mystery-box' ); ?></option>
                                            </select> </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Link to individual product', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label> <select name="wpcmb_settings[product_link]">
                                                <option value="yes" <?php selected( $product_link, 'yes' ); ?>><?php esc_html_e( 'Yes, open on the same tab', 'wpc-mystery-box' ); ?></option>
                                                <option value="yes_blank" <?php selected( $product_link, 'yes_blank' ); ?>><?php esc_html_e( 'Yes, open on a new tab', 'wpc-mystery-box' ); ?></option>
                                                <option value="yes_popup" <?php selected( $product_link, 'yes_popup' ); ?>><?php esc_html_e( 'Yes, open quick view popup', 'wpc-mystery-box' ); ?></option>
                                                <option value="no" <?php selected( $product_link, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-mystery-box' ); ?></option>
                                            </select> </label>
                                        <p class="description">If you choose "Open quick view popup", please install
                                            <a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=woo-smart-quick-view&TB_iframe=true&width=800&height=550' ) ); ?>" class="thickbox" title="WPC Smart Quick View">WPC Smart Quick View</a> to make it work.
                                        </p>
                                    </td>
                                </tr>
                                <tr class="heading">
                                    <th colspan="2">
										<?php esc_html_e( 'Cart & Checkout', 'wpc-mystery-box' ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Purchase alongside other products?', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label> <select name="wpcmb_settings[together]">
                                                <option value="yes" <?php selected( $together, 'yes' ); ?>><?php esc_html_e( 'Yes', 'wpc-mystery-box' ); ?></option>
                                                <option value="no" <?php selected( $together, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-mystery-box' ); ?></option>
                                            </select> </label>
                                        <span class="description"><?php esc_html_e( 'If Yes, customers can place an order with the mystery box and other products alongside.', 'wpc-mystery-box' ); ?></span>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Restricted payment method', 'wpc-mystery-box' ); ?></th>
                                    <td>
										<?php
										if ( ! is_object( WC()->payment_gateways ) || ! method_exists( WC()->payment_gateways, 'payment_gateways' ) ) {
											return [];
										}

										$wc_gateways               = WC()->payment_gateways->payment_gateways();
										$restrict_payment_gateways = WPCleverWpcmb_Helper()->get_setting( 'restrict_payment_gateways', [] );

										if ( ! empty( $wc_gateways ) ) {
											echo '<select name="wpcmb_settings[restrict_payment_gateways][]" multiple>';

											foreach ( $wc_gateways as $gateway ) {
												echo '<option value="' . esc_attr( $gateway->id ) . '" ' . esc_attr( in_array( $gateway->id, $restrict_payment_gateways ) ? 'selected' : '' ) . '>' . esc_html( $gateway->title ) . '</option>';
											}

											echo '</select>';
										}
										?>
                                        <p class="description"><?php esc_html_e( 'The selected payment methods will be hidden from the checkout form when the cart contains mystery boxes.', 'wpc-mystery-box' ); ?></p>
                                    </td>
                                </tr>
                                <tr class="show_if_section_none">
                                    <th><?php esc_html_e( 'Hide assortment products on order details', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label> <select name="wpcmb_settings[hide_assortment]">
                                                <option value="yes" <?php selected( $hide_assortment, 'yes' ); ?>><?php esc_html_e( 'Yes, just show the main mystery box', 'wpc-mystery-box' ); ?></option>
                                                <option value="yes_text" <?php selected( $hide_assortment, 'yes_text' ); ?>><?php esc_html_e( 'Yes, but shortly list assortment products under the main mystery box in one line', 'woo-product-bundle' ); ?></option>
                                                <option value="yes_list" <?php selected( $hide_assortment, 'yes_list' ); ?>><?php esc_html_e( 'Yes, but list assortment products under the main mystery box in separate lines', 'woo-product-bundle' ); ?></option>
                                                <option value="no" <?php selected( $hide_assortment, 'no' ); ?>><?php esc_html_e( 'No', 'wpc-mystery-box' ); ?></option>
                                            </select> </label>
                                        <p class="description"><?php esc_html_e( 'Hide assortment products, just show the main mystery box product on order details (order confirmation or emails).', 'wpc-mystery-box' ); ?></p>
                                    </td>
                                </tr>
                                <tr class="submit">
                                    <th colspan="2">
										<?php settings_fields( 'wpcmb_settings' ); ?><?php submit_button(); ?>
                                    </th>
                                </tr>
                            </table>
                        </form>
					<?php } elseif ( $active_tab === 'localization' ) { ?>
                        <form method="post" action="options.php">
                            <table class="form-table">
                                <tr class="heading">
                                    <th colspan="2">
										<?php esc_html_e( '"Add to cart" button labels', 'wpc-mystery-box' ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Shop/archive page', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <div style="margin-bottom: 5px">
                                            <label>
                                                <input type="text" class="regular-text" name="wpcmb_localization[button_select]" value="<?php echo esc_attr( WPCleverWpcmb_Helper()->localization( 'button_select' ) ); ?>" placeholder="<?php esc_attr_e( 'Add to cart', 'wpc-mystery-box' ); ?>"/>
                                            </label>
                                            <span class="description"><?php esc_html_e( 'For purchasable mystery box.', 'wpc-mystery-box' ); ?></span>
                                        </div>
                                        <div>
                                            <label>
                                                <input type="text" class="regular-text" name="wpcmb_localization[button_read]" value="<?php echo esc_attr( WPCleverWpcmb_Helper()->localization( 'button_read' ) ); ?>" placeholder="<?php esc_attr_e( 'Read more', 'wpc-mystery-box' ); ?>"/>
                                            </label>
                                            <span class="description"><?php esc_html_e( 'For unpurchasable mystery box.', 'wpc-mystery-box' ); ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Single product page', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" class="regular-text" name="wpcmb_localization[button_single]" value="<?php echo esc_attr( WPCleverWpcmb_Helper()->localization( 'button_single' ) ); ?>" placeholder="<?php esc_attr_e( 'Add to cart', 'wpc-mystery-box' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr class="heading">
                                    <th colspan="2">
										<?php esc_html_e( 'Cart & Checkout', 'wpc-mystery-box' ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Assortments', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" class="regular-text" name="wpcmb_localization[assortments]" value="<?php echo esc_attr( WPCleverWpcmb_Helper()->localization( 'assortments' ) ); ?>" placeholder="<?php /* translators: assortments */
											esc_attr_e( 'Assortments: %s', 'wpc-mystery-box' ); ?>"/> </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Assortment product', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" class="regular-text" name="wpcmb_localization[assortment_product]" value="<?php echo esc_attr( WPCleverWpcmb_Helper()->localization( 'assortment_product' ) ); ?>" placeholder="<?php /* translators: product name */
											esc_attr_e( 'Assortment product in: %s', 'wpc-mystery-box' ); ?>"/> </label>
                                    </td>
                                </tr>
                                <tr class="heading">
                                    <th colspan="2">
										<?php esc_html_e( 'Alert messages', 'wpc-mystery-box' ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Other products purchase error', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" class="large-text" name="wpcmb_localization[together_mystery]" value="<?php echo esc_attr( WPCleverWpcmb_Helper()->localization( 'together_mystery' ) ); ?>" placeholder="<?php esc_attr_e( 'You cannot purchase this product when there is a mystery box in the cart.', 'wpc-mystery-box' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Mystery box purchase error', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" class="large-text" name="wpcmb_localization[together_other]" value="<?php echo esc_attr( WPCleverWpcmb_Helper()->localization( 'together_other' ) ); ?>" placeholder="<?php esc_attr_e( 'You cannot purchase this mystery box when there are other products in the cart.', 'wpc-mystery-box' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Both mystery box & others purchase error', 'wpc-mystery-box' ); ?></th>
                                    <td>
                                        <label>
                                            <input type="text" class="large-text" name="wpcmb_localization[together_both]" value="<?php echo esc_attr( WPCleverWpcmb_Helper()->localization( 'together_both' ) ); ?>" placeholder="<?php esc_attr_e( 'You cannot purchase mystery boxes alongside other products. Please check your cart.', 'wpc-mystery-box' ); ?>"/>
                                        </label>
                                    </td>
                                </tr>
                                <tr class="submit">
                                    <th colspan="2">
										<?php settings_fields( 'wpcmb_localization' ); ?><?php submit_button(); ?>
                                    </th>
                                </tr>
                            </table>
                        </form>
					<?php } else if ( $active_tab === 'premium' ) { ?>
                        <div class="wpclever_settings_page_content_text">
                            <p>Get the Premium Version just $29!
                                <a href="https://wpclever.net/downloads/wpc-mystery-box?utm_source=pro&utm_medium=wpcmb&utm_campaign=wporg" target="_blank">https://wpclever.net/downloads/wpc-mystery-box</a>
                            </p>
                            <p><strong>Extra features for Premium Version:</strong></p>
                            <ul style="margin-bottom: 0">
                                <li>- Use Categories, Tags, or Attributes as the source for assortment options.</li>
                                <li>- Get the lifetime update & premium support.</li>
                            </ul>
                        </div>
					<?php } ?>
                </div><!-- /.wpclever_settings_page_content -->
                <div class="wpclever_settings_page_suggestion">
                    <div class="wpclever_settings_page_suggestion_label">
                        <span class="dashicons dashicons-yes-alt"></span> Suggestion
                    </div>
                    <div class="wpclever_settings_page_suggestion_content">
                        <div>
                            To display custom engaging real-time messages on any wished positions, please install
                            <a href="https://wordpress.org/plugins/wpc-smart-messages/" target="_blank">WPC Smart Messages</a> plugin. It's free!
                        </div>
                        <div>
                            Wanna save your precious time working on variations? Try our brand-new free plugin
                            <a href="https://wordpress.org/plugins/wpc-variation-bulk-editor/" target="_blank">WPC Variation Bulk Editor</a> and
                            <a href="https://wordpress.org/plugins/wpc-variation-duplicator/" target="_blank">WPC Variation Duplicator</a>.
                        </div>
                    </div>
                </div>
            </div>
			<?php
		}

		function shortcode() {
			ob_start();

			self::show_items();

			return ob_get_clean();
		}

		function enqueue_scripts() {
			wp_enqueue_style( 'wpcmb-frontend', WPCMB_URI . 'assets/css/frontend.css', [], WPCMB_VERSION );
			wp_enqueue_script( 'wpcmb-frontend', WPCMB_URI . 'assets/js/frontend.js', [ 'jquery' ], WPCMB_VERSION, true );
		}

		function admin_enqueue_scripts() {
			wp_enqueue_style( 'hint', WPCMB_URI . 'assets/css/hint.css' );
			wp_enqueue_style( 'wpcmb-backend', WPCMB_URI . 'assets/css/backend.css', [ 'woocommerce_admin_styles' ], WPCMB_VERSION );
			wp_enqueue_script( 'wpcmb-backend', WPCMB_URI . 'assets/js/backend.js', [
				'jquery',
				'jquery-ui-dialog',
				'jquery-ui-sortable',
				'wc-enhanced-select',
				'selectWoo'
			], WPCMB_VERSION, true );
			wp_localize_script( 'wpcmb-backend', 'wpcmb_vars', [
					'nonce' => wp_create_nonce( 'wpcmb-security' )
				]
			);
		}

		function action_links( $links, $file ) {
			static $plugin;

			if ( ! isset( $plugin ) ) {
				$plugin = plugin_basename( WPCMB_FILE );
			}

			if ( $plugin === $file ) {
				$settings             = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-wpcmb&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'wpc-mystery-box' ) . '</a>';
				$links['wpc-premium'] = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-wpcmb&tab=premium' ) ) . '">' . esc_html__( 'Premium Version', 'wpc-mystery-box' ) . '</a>';
				array_unshift( $links, $settings );
			}

			return (array) $links;
		}

		function row_meta( $links, $file ) {
			static $plugin;

			if ( ! isset( $plugin ) ) {
				$plugin = plugin_basename( WPCMB_FILE );
			}

			if ( $plugin === $file ) {
				$row_meta = [
					'support' => '<a href="' . esc_url( WPCMB_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'wpc-mystery-box' ) . '</a>',
				];

				return array_merge( $links, $row_meta );
			}

			return (array) $links;
		}

		function display_post_states( $states, $post ) {
			if ( 'product' == get_post_type( $post->ID ) ) {
				if ( ( $product = wc_get_product( $post->ID ) ) && $product->is_type( 'wpcmb' ) ) {
					$count = 0;

					if ( ( $assortments = $product->get_assortments() ) && is_array( $assortments ) ) {
						$count = count( $assortments );
					}

					$states[] = apply_filters( 'wpcmb_post_states', '<span class="wpcmb-state">' . sprintf( /* translators: count */ esc_html__( 'Mystery box (%s)', 'wpc-mystery-box' ), $count ) . '</span>', $count, $product );
				}
			}

			return $states;
		}

		function product_type_selector( $types ) {
			$types['wpcmb'] = esc_html__( 'Smart mystery box', 'wpc-mystery-box' );

			return $types;
		}

		function product_data_tabs( $tabs ) {
			$tabs['wpcmb'] = [
				'label'  => esc_html__( 'Mystery Box', 'wpc-mystery-box' ),
				'target' => 'wpcmb_settings',
				'class'  => [ 'show_if_wpcmb' ],
			];

			return $tabs;
		}

		function product_data_panels() {
			global $post, $thepostid, $product_object;

			if ( $product_object instanceof WC_Product ) {
				$product_id = $product_object->get_id();
			} elseif ( is_numeric( $thepostid ) ) {
				$product_id = $thepostid;
			} elseif ( $post instanceof WP_Post ) {
				$product_id = $post->ID;
			} else {
				$product_id = 0;
			}

			if ( ! $product_id ) {
				?>
                <div id='wpcmb_settings' class='panel woocommerce_options_panel wpcmb_table'>
                    <p style="padding: 0 12px; color: #c9356e"><?php esc_html_e( 'Product wasn\'t returned.', 'wpc-mystery-box' ); ?></p>
                </div>
				<?php
				return;
			}

			$assortments = get_post_meta( $product_id, 'wpcmb_assortments', true );
			?>
            <div id='wpcmb_settings' class='panel woocommerce_options_panel wpcmb_table'>
                <table class="wpcmb_assortments">
                    <thead></thead>
                    <tbody>
					<?php if ( ! empty( $assortments ) && is_array( $assortments ) ) {
						foreach ( $assortments as $assortment_key => $assortment ) {
							self::assortment( false, $assortment, $assortment_key );
						}
					} else {
						self::assortment( true );
					} ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td>
                            <div>
                                <a href="#" class="wpcmb_add_assortment button">
									<?php esc_html_e( '+ Add assortment', 'wpc-mystery-box' ); ?>
                                </a> <a href="#" class="wpcmb_expand_all">
									<?php esc_html_e( 'Expand All', 'wpc-mystery-box' ); ?>
                                </a> <a href="#" class="wpcmb_collapse_all">
									<?php esc_html_e( 'Collapse All', 'wpc-mystery-box' ); ?>
                                </a>
                            </div>
                            <div>
                                <!--
								<a href="#" class="wpcmb_export_assortments hint--left" aria-label="<?php esc_attr_e( 'Remember to save current assortments before exporting to get the latest version.', 'wpc-mystery-box' ); ?>">
									<?php esc_html_e( 'Export', 'wpc-mystery-box' ); ?>
								</a>
								-->
                                <a href="#" class="wpcmb_save_assortments button button-primary">
									<?php esc_html_e( 'Save assortments', 'wpc-mystery-box' ); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    </tfoot>
                </table>
                <table>
                    <tr class="wpcmb_tr_space">
                        <th><?php esc_html_e( 'Custom display price', 'wpc-mystery-box' ); ?></th>
                        <td>
                            <label>
                                <input type="text" name="wpcmb_custom_price" value="<?php echo esc_attr( get_post_meta( $product_id, 'wpcmb_custom_price', true ) ); ?>"/>
                            </label> E.g: <code>From $10 to $100</code>
                        </td>
                    </tr>
                    <tr class="wpcmb_tr_space">
                        <th><?php esc_html_e( 'Above text', 'wpc-mystery-box' ); ?></th>
                        <td>
                            <div class="w100">
                                <label>
                                    <textarea name="wpcmb_before_text"><?php echo esc_textarea( get_post_meta( $product_id, 'wpcmb_before_text', true ) ); ?></textarea>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr class="wpcmb_tr_space">
                        <th><?php esc_html_e( 'Under text', 'wpc-mystery-box' ); ?></th>
                        <td>
                            <div class="w100">
                                <label>
                                    <textarea name="wpcmb_after_text"><?php echo esc_textarea( get_post_meta( $product_id, 'wpcmb_after_text', true ) ); ?></textarea>
                                </label>
                            </div>
                        </td>
                    </tr>
					<?php wp_nonce_field( 'wpcmb_process_meta', 'wpcmb_nonce' ); ?>
					<?php do_action( 'wpcmb_product_settings', $product_id ); ?>
                </table>
            </div>
			<?php
		}

		function process_meta_wpcmb( $post_id ) {
			if ( isset( $_POST['wpcmb_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['wpcmb_nonce'] ), 'wpcmb_process_meta' ) ) {
				if ( isset( $_POST['wpcmb_assortments'] ) ) {
					update_post_meta( $post_id, 'wpcmb_assortments', WPCleverWpcmb_Helper()->sanitize_array( $_POST['wpcmb_assortments'] ) );
				}

				if ( isset( $_POST['wpcmb_custom_price'] ) ) {
					update_post_meta( $post_id, 'wpcmb_custom_price', sanitize_post_field( 'post_content', $_POST['wpcmb_custom_price'], $post_id, 'display' ) );
				}

				if ( isset( $_POST['wpcmb_before_text'] ) ) {
					update_post_meta( $post_id, 'wpcmb_before_text', sanitize_post_field( 'post_content', $_POST['wpcmb_before_text'], $post_id, 'display' ) );
				}

				if ( isset( $_POST['wpcmb_after_text'] ) ) {
					update_post_meta( $post_id, 'wpcmb_after_text', sanitize_post_field( 'post_content', $_POST['wpcmb_after_text'], $post_id, 'display' ) );
				}
			}
		}

		function get_price_html( $price, $product ) {
			if ( $product->is_type( 'wpcmb' ) ) {
				$product_id   = $product->get_id();
				$custom_price = stripslashes( get_post_meta( $product_id, 'wpcmb_custom_price', true ) );

				if ( ! empty( $custom_price ) ) {
					return $custom_price;
				}
			}

			return $price;
		}

		function add_to_cart_form() {
			global $product;

			if ( ( WPCleverWpcmb_Helper()->get_setting( 'position', 'above' ) === 'above' ) && apply_filters( 'wpcmb_show_items', true, $product ) ) {
				self::show_items( $product );
			}

			wc_get_template( 'single-product/add-to-cart/simple.php' );

			if ( ( WPCleverWpcmb_Helper()->get_setting( 'position', 'above' ) === 'under' ) && apply_filters( 'wpcmb_show_items', true, $product ) ) {
				self::show_items( $product );
			}
		}

		function show_items( $product = null ) {
			if ( ! $product ) {
				global $product;
			}

			if ( ! $product || ! $product->is_type( 'wpcmb' ) ) {
				return;
			}

			$product_id = $product->get_id();

			do_action( 'wpcmb_before_wrap', $product );

			if ( $assortments = $product->get_assortments() ) {
				$layout            = WPCleverWpcmb_Helper()->get_setting( 'layout', 'list' );
				$show_qty          = WPCleverWpcmb_Helper()->get_setting( 'show_qty', 'yes' ) === 'yes';
				$show_price        = WPCleverWpcmb_Helper()->get_setting( 'show_price', 'yes' ) === 'yes';
				$show_availability = WPCleverWpcmb_Helper()->get_setting( 'show_availability', 'yes' ) === 'yes';
				$show_image        = WPCleverWpcmb_Helper()->get_setting( 'show_image', 'yes' ) === 'yes';
				$product_link      = WPCleverWpcmb_Helper()->get_setting( 'product_link', 'yes' );
				$products_class    = apply_filters( 'wpcmb_assortment_products_class', 'wpcmb_assortment_products wpcmb-products wpcmb-products-layout-' . $layout, $product );
				$order             = 1;

				echo '<div class="' . esc_attr( apply_filters( 'wpcmb_wrap_class', 'wpcmb_wrap wpcmb-wrap wpcmb-wrap-' . $product_id, $product ) ) . '">';

				if ( $before_text = apply_filters( 'wpcmb_before_text', get_post_meta( $product_id, 'wpcmb_before_text', true ), $product_id ) ) {
					echo '<div class="wpcmb_before_text wpcmb-before-text wpcmb-text">' . wp_kses_post( do_shortcode( $before_text ) ) . '</div>';
				}

				do_action( 'wpcmb_before_assortments', $product );

				echo '<div class="' . esc_attr( apply_filters( 'wpcmb_assortments_class', 'wpcmb_assortments wpcmb-assortments', $product ) ) . '">';

				foreach ( $assortments as $assortment ) {
					$assortment_qty      = isset( $assortment['quantity'] ) ? (float) $assortment['quantity'] : 1;
					$assortment_products = WPCleverWpcmb_Helper()->get_products( $assortment );

					if ( empty( $assortment_products ) ) {
						continue;
					}

					echo '<div class="' . esc_attr( apply_filters( 'wpcmb_assortment_class', 'wpcmb_assortment wpcmb-assortment wpcmb_assortment_' . $order, $assortment, $order, $product ) ) . '">';
					do_action( 'wpcmb_before_assortment', $assortment, $order );

					if ( ! empty( $assortment['name'] ) ) {
						echo '<div class="wpcmb_assortment_name">' . esc_html( $assortment['name'] ) . '</div>';
					}

					if ( ! empty( $assortment['desc'] ) ) {
						echo '<div class="wpcmb_assortment_desc">' . wp_kses_post( do_shortcode( $assortment['desc'] ) ) . '</div>';
					}

					echo '<div class="' . esc_attr( $products_class ) . '">';

					foreach ( $assortment_products as $assortment_product ) {
						if ( $assortment_product_obj = wc_get_product( $assortment_product['id'] ) ) {
							$assortment_product_class = 'wpcmb_assortment_product wpcmb-product';
							$assortment_product_name  = $assortment_product_obj->get_name();

							if ( $show_qty ) {
								$assortment_product_name = $assortment_qty . ' &times; ' . $assortment_product_name;
							}

							if ( ! $assortment_product_obj->is_visible() ) {
								$assortment_product_class .= ' wpcmb_assortment_product_hidden';
							}

							if ( ! WPCleverWpcmb_Helper()->is_purchasable( $assortment_product_obj, $assortment_qty ) ) {
								$assortment_product_class .= ' wpcmb_assortment_product_unpurchasable';
							}

							echo '<div class="' . esc_attr( apply_filters( 'wpcmb_assortment_product_class', $assortment_product_class, $assortment_product_obj ) ) . '">';

							if ( $show_image ) {
								if ( $assortment_product_obj->is_visible() && ( $product_link !== 'no' ) ) {
									echo '<div class="wpcmb_assortment_product_image"><a class="' . esc_attr( $product_link === 'yes_popup' ? 'woosq-link wpcmb_assortment_product_image_link' : 'wpcmb_assortment_product_image_link' ) . '" data-id="' . esc_attr( $assortment_product['id'] ) . '" href="' . esc_url( $assortment_product_obj->get_permalink() ) . '" ' . ( $product_link === 'yes_blank' ? 'target="_blank"' : '' ) . '>' . wp_kses_post( $assortment_product_obj->get_image() ) . '</a></div>';
								} else {
									echo '<div class="wpcmb_assortment_product_image">' . wp_kses_post( $assortment_product_obj->get_image() ) . '</div>';
								}
							}

							echo '<div class="wpcmb_assortment_product_info">';

							if ( $assortment_product_obj->is_visible() && ( $product_link !== 'no' ) ) {
								echo '<div class="wpcmb_assortment_product_name"><a class="' . esc_attr( $product_link === 'yes_popup' ? 'woosq-link wpcmb_assortment_product_name_link' : 'wpcmb_assortment_product_name_link' ) . '" data-id="' . esc_attr( $assortment_product['id'] ) . '" href="' . esc_url( $assortment_product_obj->get_permalink() ) . '" ' . ( $product_link === 'yes_blank' ? 'target="_blank"' : '' ) . '>' . esc_html( $assortment_product_name ) . '</a></div>';
							} else {
								echo '<div class="wpcmb_assortment_product_name">' . esc_html( $assortment_product_name ) . '</div>';
							}

							if ( $show_price || $show_availability ) {
								echo '<div class="wpcmb_assortment_product_desc">';

								if ( $show_price ) {
									echo '<div class="wpcmb_assortment_product_price">' . wp_kses_post( $assortment_product_obj->get_price_html() ) . '</div>';
								}

								if ( $show_availability ) {
									echo '<div class="wpcmb_assortment_product_availability">' . wp_kses_post( wc_get_stock_html( $assortment_product_obj ) ) . '</div>';
								}

								echo '</div><!-- /wpcmb_assortment_product_desc -->';
							}

							echo '</div><!-- /wpcmb_assortment_product_info -->';
							echo '</div><!-- /wpcmb_assortment_product -->';
						}
					}

					echo '</div><!-- /wpcmb_assortment_products -->';

					do_action( 'wpcmb_after_assortment', $assortment, $order );
					echo '</div>';
					$order ++;
				}

				echo '</div>';

				do_action( 'wpcmb_after_assortments', $product );

				if ( $after_text = apply_filters( 'wpcmb_after_text', get_post_meta( $product_id, 'wpcmb_after_text', true ), $product_id ) ) {
					echo '<div class="wpcmb_after_text wpcmb-after-text wpcmb-text">' . wp_kses_post( do_shortcode( $after_text ) ) . '</div>';
				}

				echo '</div>';
			}

			do_action( 'wpcmb_after_wrap', $product );
		}

		function add_to_cart_validation( $validate, $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				return $validate;
			}

			if ( ( WPCleverWpcmb_Helper()->get_setting( 'together', 'yes' ) === 'no' ) && WPCleverWpcmb_Helper()->cart_has_mystery_box() && ( 'wpcmb' !== $product->get_type() ) ) {
				wc_add_notice( WPCleverWpcmb_Helper()->localization( 'together_mystery', esc_html__( 'You cannot purchase this product when there is a mystery box in the cart.', 'wpc-mystery-box' ) ), 'error' );

				return false;
			}

			if ( ( WPCleverWpcmb_Helper()->get_setting( 'together', 'yes' ) === 'no' ) && ! WPCleverWpcmb_Helper()->cart_is_empty() && ! WPCleverWpcmb_Helper()->cart_has_mystery_box() && ( 'wpcmb' === $product->get_type() ) ) {
				wc_add_notice( WPCleverWpcmb_Helper()->localization( 'together_other', esc_html__( 'You cannot purchase this mystery box when there are other products in the cart.', 'wpc-mystery-box' ) ), 'error' );

				return false;
			}

			return $validate;
		}

		function check_cart_items() {
			$return = true;

			if ( ( WPCleverWpcmb_Helper()->get_setting( 'together', 'yes' ) === 'no' ) && WPCleverWpcmb_Helper()->cart_has_both() ) {
				wc_add_notice( WPCleverWpcmb_Helper()->localization( 'together_both', esc_html__( 'You cannot purchase mystery boxes alongside other products. Please check your cart.', 'wpc-mystery-box' ) ), 'error' );

				$return = false;
			}

			return $return;
		}

		function available_payment_gateways( $available_gateways ) {
			if ( ! WPCleverWpcmb_Helper()->cart_has_mystery_box() ) {
				return $available_gateways;
			}

			$restrict_payment_gateways = WPCleverWpcmb_Helper()->get_setting( 'restrict_payment_gateways', [] );

			if ( is_array( $restrict_payment_gateways ) && ! empty( $restrict_payment_gateways ) ) {
				foreach ( $restrict_payment_gateways as $restrict_payment_gateway ) {
					unset( $available_gateways[ $restrict_payment_gateway ] );
				}
			}

			return $available_gateways;
		}

		function hidden_order_itemmeta( $hidden_order_itemmeta ) {
			$hidden_order_itemmeta[] = '_wpcmb_ids';
			$hidden_order_itemmeta[] = '_wpcmb_parent_id';

			return $hidden_order_itemmeta;
		}

		function order_item_meta_start( $item_id, $item, $order ) {
			if ( ! is_a( $order, 'WC_Order' ) ) {
				$order = $item->get_order();
			}

			$ids    = wc_get_order_item_meta( $item_id, '_wpcmb_ids' );
			$box_id = wc_get_order_item_meta( $item_id, '_wpcmb_parent_id' );

			if ( ! empty( $box_id ) && ( $box = $order->get_item( $box_id ) ) ) {
				echo wp_kses_post( '<div class="wpcmb_assortment_in">' . sprintf( WPCleverWpcmb_Helper()->localization( 'assortment_product', /* translators: product name */ esc_html__( 'Assortment product in: %s', 'wpc-mystery-box' ) ), esc_html( $box->get_name() ) ) . '</div>' );
			}

			if ( ! empty( $ids ) ) {
				$items = explode( ',', $ids );

				if ( WPCleverWpcmb_Helper()->get_setting( 'hide_assortment', 'no' ) === 'yes_text' ) {
					$items_str = [];

					if ( ! empty( $items ) ) {
						foreach ( $items as $item ) {
							if ( $order_item = $order->get_item( $item ) ) {
								$items_str[] = apply_filters( 'wpcmb_assortment_product_name', esc_html( $order_item->get_quantity() ) . ' × ' . esc_html( $order_item->get_name() ), $item );
							}
						}
					}

					$items_str = apply_filters( 'wpcmb_assortment_product_names', implode( '; ', $items_str ), $items );
				} else {
					$items_str = [];

					if ( ! empty( $items ) ) {
						foreach ( $items as $item ) {
							if ( $order_item = $order->get_item( $item ) ) {
								$items_str[] = apply_filters( 'wpcmb_assortment_product_name', '<li>' . esc_html( $order_item->get_quantity() ) . ' × ' . esc_html( $order_item->get_name() ) . '</li>', $item );
							}
						}
					}

					$items_str = apply_filters( 'wpcmb_assortment_product_names', '<ul>' . implode( '', $items_str ) . '</ul>', $items );
				}

				echo wp_kses_post( '<div class="wpcmb_assortment_products">' . sprintf( WPCleverWpcmb_Helper()->localization( 'assortments', /* translators: assortments */ esc_html__( 'Assortments: %s', 'wpc-mystery-box' ) ), $items_str ) . '</div>' );
			}

			return null;
		}

		function order_item_visible( $visible, $order_item ) {
			if ( $order_item->get_meta( '_wpcmb_parent_id' ) ) {
				$_visible = WPCleverWpcmb_Helper()->get_setting( 'hide_assortment', 'no' ) === 'no';

				return apply_filters( 'wpcmb_order_item_visible', $_visible, $order_item );
			}

			return $visible;
		}

		function add_assortment_products( $order_id ) {
			if ( $order = wc_get_order( $order_id ) ) {
				if ( apply_filters( 'wpcmb_separately', true ) ) {
					foreach ( $order->get_items() as $order_item_key => $order_item ) {
						if ( $order_item->meta_exists( '_wpcmb_ids' ) ) {
							continue;
						}

						$quantity = $order_item->get_quantity();
						$product  = $order_item->get_product();
						$name     = $order_item->get_name();

						if ( ! $product || ( 'wpcmb' != $product->get_type() ) || empty( $quantity ) ) {
							continue;
						}

						if ( $quantity > 1 ) {
							// add separately
							for ( $i = 1; $i <= $quantity; $i ++ ) {
								$product->set_name( $name . ' ' . str_pad( $i, 2, '0', STR_PAD_LEFT ) );
								$order->add_product( $product );
							}

							// remove main product
							$order->remove_item( $order_item_key );
						}
					}

					$order->save();
				}

				foreach ( $order->get_items() as $order_item_key => $order_item ) {
					if ( $order_item->meta_exists( '_wpcmb_ids' ) ) {
						continue;
					}

					$quantity = $order_item->get_quantity();
					$product  = $order_item->get_product();

					if ( ! $product || ( 'wpcmb' != $product->get_type() ) || empty( $quantity ) ) {
						continue;
					}

					if ( ( $assortments = $product->get_assortments() ) && is_array( $assortments ) ) {
						$mystery_products = [];

						foreach ( $assortments as $assortment ) {
							$assortment_products = WPCleverWpcmb_Helper()->get_products( $assortment, 'mystery' );
							$mystery_products    = array_merge( $mystery_products, $assortment_products );
						}

						if ( ! empty( $mystery_products ) ) {
							$ids = [];

							foreach ( $mystery_products as $mystery_product ) {
								$item_id = $order->add_product( wc_get_product( $mystery_product['id'] ), $quantity * (float) $mystery_product['qty'], [
									'total'    => 0,
									'subtotal' => 0
								] );
								$ids[]   = $item_id;
								wc_add_order_item_meta( $item_id, '_wpcmb_parent_id', $order_item_key );
							}

							wc_add_order_item_meta( $order_item_key, '_wpcmb_ids', implode( ',', $ids ) );

							$order->save();
						}
					}
				}
			}
		}

		function wpcsm_locations( $locations ) {
			$locations['WPC Mystery Box'] = [
				'wpcmb_before_wrap'        => esc_html__( 'Before wrapper', 'wpc-mystery-box' ),
				'wpcmb_after_wrap'         => esc_html__( 'After wrapper', 'wpc-mystery-box' ),
				'wpcmb_before_assortments' => esc_html__( 'Before assortments', 'wpc-mystery-box' ),
				'wpcmb_after_assortments'  => esc_html__( 'After assortments', 'wpc-mystery-box' ),
				'wpcmb_before_assortment'  => esc_html__( 'Before assortment', 'wpc-mystery-box' ),
				'wpcmb_after_assortment'   => esc_html__( 'After assortment', 'wpc-mystery-box' ),
			];

			return $locations;
		}
	}

	return WPCleverWpcmb::instance();
}
