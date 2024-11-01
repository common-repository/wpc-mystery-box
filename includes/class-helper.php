<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCleverWpcmb_Helper' ) ) {
	class WPCleverWpcmb_Helper {
		protected static $instance = null;
		protected static $settings = [];
		protected static $localization = [];

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		function __construct() {
			// settings
			self::$settings = (array) get_option( 'wpcmb_settings', [] );
			// localization
			self::$localization = (array) get_option( 'wpcmb_localization', [] );
		}

		public static function get_settings() {
			return apply_filters( 'wpcmb_get_settings', self::$settings );
		}

		public static function get_setting( $name, $default = false ) {
			if ( ! empty( self::$settings ) && isset( self::$settings[ $name ] ) ) {
				$setting = self::$settings[ $name ];
			} else {
				$setting = get_option( 'wpcmb_' . $name, $default );
			}

			return apply_filters( 'wpcmb_get_setting', $setting, $name, $default );
		}

		public static function localization( $key = '', $default = '' ) {
			$str = '';

			if ( ! empty( $key ) && ! empty( self::$localization[ $key ] ) ) {
				$str = self::$localization[ $key ];
			} elseif ( ! empty( $default ) ) {
				$str = $default;
			}

			return apply_filters( 'wpcmb_localization_' . $key, $str );
		}

		public static function sanitize_array( $arr ) {
			foreach ( (array) $arr as $k => $v ) {
				if ( is_array( $v ) ) {
					$arr[ $k ] = self::sanitize_array( $v );
				} else {
					$arr[ $k ] = sanitize_post_field( 'post_content', $v, 0, 'db' );
				}
			}

			return $arr;
		}

		public static function generate_key() {
			$key         = '';
			$key_str     = apply_filters( 'wpcmb_key_characters', 'abcdefghijklmnopqrstuvwxyz0123456789' );
			$key_str_len = strlen( $key_str );

			for ( $i = 0; $i < apply_filters( 'wpcmb_key_length', 4 ); $i ++ ) {
				$key .= $key_str[ random_int( 0, $key_str_len - 1 ) ];
			}

			if ( is_numeric( $key ) ) {
				$key = self::generate_key();
			}

			return apply_filters( 'wpcmb_generate_key', $key );
		}

		public static function is_purchasable( $product, $quantity ) {
			return $product->is_purchasable() && $product->is_in_stock() && $product->has_enough_stock( $quantity ) && ( 'trash' !== $product->get_status() );
		}

		public static function cart_is_empty() {
			if ( ! is_object( WC()->cart ) || ! method_exists( WC()->cart, 'get_cart' ) ) {
				return true;
			}

			$cart_contents = WC()->cart->get_cart();

			if ( ! is_array( $cart_contents ) || empty( $cart_contents ) ) {
				return true;
			}

			return false;
		}

		public static function cart_has_mystery_box() {
			if ( ! is_object( WC()->cart ) || ! method_exists( WC()->cart, 'get_cart' ) ) {
				return false;
			}

			$cart_contents = WC()->cart->get_cart();

			if ( is_array( $cart_contents ) && ! empty( $cart_contents ) ) {
				foreach ( $cart_contents as $cart_item ) {
					if ( 'wpcmb' === $cart_item['data']->get_type() ) {
						return true;
					}
				}
			}

			return false;
		}

		public static function cart_has_both() {
			if ( ! is_object( WC()->cart ) || ! method_exists( WC()->cart, 'get_cart' ) ) {
				return false;
			}

			$cart_contents = WC()->cart->get_cart();

			if ( is_array( $cart_contents ) && ! empty( $cart_contents ) ) {
				$has_other   = false;
				$has_mystery = false;

				foreach ( $cart_contents as $cart_item ) {
					if ( 'wpcmb' === $cart_item['data']->get_type() ) {
						$has_mystery = true;
					} else {
						$has_other = true;
					}
				}

				return $has_mystery && $has_other;
			}

			return false;
		}

		public static function get_product_id_by_sku( $id = null ) {
			if ( ! is_numeric( $id ) ) {
				return wc_get_product_id_by_sku( $id );
			}

			return $id;
		}

		public static function get_products( $assortment, $context = 'all' ) {
			$type = isset( $assortment['type'] ) ? (string) $assortment['type'] : 'products';

			if ( $type === 'products' ) {
				$val = $assortment['products'] ?? [];
			} else {
				return [];
			}

			$exclude    = $assortment['exclude'] ?? [];
			$number     = isset( $assortment['number'] ) ? absint( $assortment['number'] ) : 1;
			$quantity   = isset( $assortment['quantity'] ) ? (float) $assortment['quantity'] : 1;
			$prioritize = $assortment['prioritize'] ?? 'random';
			$lucky      = isset( $assortment['necessary'] ) && ( $assortment['necessary'] === 'lucky' );
			$order      = isset( $assortment['order'] ) ? (string) $assortment['order'] : 'default';
			$orderby    = isset( $assortment['orderby'] ) ? (string) $assortment['orderby'] : 'default';

			if ( $orderby === 'name' ) {
				$orderby = 'title';
			}

			$products       = $mystery_products = [];
			$val_arr        = array_unique( ! is_array( $val ) ? array_map( 'trim', explode( ',', $val ) ) : $val );
			$exclude_ids    = $type != 'products' ? ( ! is_array( $exclude ) ? explode( ',', $exclude ) : $exclude ) : [];
			$exclude_ids    = array_map( [ __CLASS__, 'get_product_id_by_sku' ], $exclude_ids );
			$limit          = apply_filters( 'wpcmb_limit', 500 );
			$exclude_hidden = apply_filters( 'wpcmb_exclude_hidden', WPCleverWpcmb_Helper()->get_setting( 'exclude_hidden', 'no' ) === 'yes' );

			// query args
			if ( $type === 'products' ) {
				if ( ( $orderby === 'default' ) && ( $order === 'DESC' ) ) {
					$val_arr = array_reverse( $val_arr );
				}

				$val_arr = array_map( [ __CLASS__, 'get_product_id_by_sku' ], $val_arr );

				if ( $orderby === 'default' ) {
					$orderby = 'post__in';
				}

				$args = [
					'is_wpcmb' => true,
					'type'     => array_merge( [ 'variation' ], array_keys( wc_get_product_types() ) ),
					'include'  => $val_arr,
					'orderby'  => $orderby,
					'order'    => $order,
					'limit'    => $limit
				];
			} else {
				$args = [
					'is_wpcmb'  => true,
					'orderby'   => $orderby,
					'order'     => $order,
					'limit'     => $limit,
					'tax_query' => [
						[
							'taxonomy' => $type,
							'field'    => 'slug',
							'terms'    => $val_arr,
							'operator' => 'IN',
						]
					]
				];
			}

			// order by price
			if ( $orderby === 'price' ) {
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_price';
			}

			$args['status'] = [ 'publish' ];

			// filter
			$args = apply_filters( 'wpcmb_wc_get_products_args', $args );

			// query products
			$_products = apply_filters( 'wpcmb_wc_get_products', wc_get_products( $args ), $assortment );

			if ( empty( $_products ) ) {
				// try get_posts in some cases get_products does not work
				if ( $type === 'products' ) {
					$args = apply_filters( 'wpcmb_wp_get_posts_args', [
						'is_wpcmb'       => true,
						'fields'         => 'ids',
						'post_type'      => [ 'product', 'product_variation' ],
						'post_status'    => [ 'publish' ],
						'include'        => $val_arr,
						'orderby'        => $orderby,
						'order'          => $order,
						'posts_per_page' => $limit
					] );
				} else {
					$args = apply_filters( 'wpcmb_wp_get_posts_args', [
						'is_wpcmb'       => true,
						'fields'         => 'ids',
						'post_type'      => [ 'product', 'product_variation' ],
						'post_status'    => [ 'publish' ],
						'tax_query'      => [
							[
								'taxonomy' => $type,
								'field'    => 'slug',
								'terms'    => $val_arr,
								'operator' => 'IN',
							]
						],
						'orderby'        => $orderby,
						'order'          => $order,
						'posts_per_page' => $limit
					] );
				}

				$_posts = apply_filters( 'wpcmb_wp_get_posts', get_posts( $args ), $assortment );

				if ( ! empty( $_posts ) && is_array( $_posts ) ) {
					$_products = array_map( 'wc_get_product', $_posts );
				}
			}

			$_products = apply_filters( 'wpcmb_pre_get_products', $_products, $assortment );

			if ( is_array( $_products ) && ! empty( $_products ) ) {
				foreach ( $_products as $_product ) {
					if ( $_product->is_type( 'wpcmb' ) || $_product->is_type( 'woosb' ) || $_product->is_type( 'composite' ) || $_product->is_type( 'woosg' ) || apply_filters( 'wpcmb_ignore_product', false, $_product ) ) {
						continue;
					}

					$_product_id = $_product->get_id();

					if ( in_array( $_product_id, $exclude_ids ) ) {
						continue;
					}

					if ( ! apply_filters( 'wpcmb_product_visible', true, $_product ) || ( ! $_product->is_visible() && $exclude_hidden ) ) {
						continue;
					}

					if ( $_product->is_type( 'variable' ) ) {
						$children = $_product->get_children();

						if ( ! empty( $children ) ) {
							foreach ( $children as $child ) {
								if ( in_array( $child, $exclude_ids ) ) {
									continue;
								}

								$child_product = wc_get_product( $child );

								if ( ! $child_product || ( ! $child_product->variation_is_visible() && $exclude_hidden ) || ! WPCleverWpcmb_Helper()->is_purchasable( $child_product, $quantity ) ) {
									continue;
								}

								if ( apply_filters( 'wpcmb_check_variation_attribute', true ) && ( substr( $type, 0, 3 ) === 'pa_' ) ) {
									// check variation attribute
									$attrs = $child_product->get_attributes();

									if ( ! isset( $attrs[ $type ] ) || ( ! in_array( $attrs[ $type ], $val_arr ) ) ) {
										continue;
									}
								}

								$products[] = [
									'id'    => $child,
									'stock' => $child_product->get_stock_quantity() ?? 1000000,
									'qty'   => $quantity
								];
							}
						}
					} else {
						if ( ! WPCleverWpcmb_Helper()->is_purchasable( $_product, $quantity ) ) {
							continue;
						}

						$products[] = [
							'id'    => $_product_id,
							'stock' => $_product->get_stock_quantity() ?? 1000000,
							'qty'   => $quantity
						];
					}
				}
			}

			if ( $context === 'mystery' ) {
				if ( $number && ! empty( $products ) ) {
					if ( $prioritize === 'random' ) {
						// random
						shuffle( $products );
					}

					if ( $prioritize === 'high_stock' ) {
						// order by high_stock
						array_multisort( array_column( $products, 'stock' ), SORT_DESC, $products );
					}

					if ( $prioritize === 'low_stock' ) {
						// order by high_stock
						array_multisort( array_column( $products, 'stock' ), SORT_ASC, $products );
					}

					$x = 0;

					while ( $x < $number ) {
						if ( isset( $products[ $x ] ) ) {
							if ( $lucky ) {
								if ( random_int( 0, 1 ) ) {
									$mystery_products[] = $products[ $x ];
								}
							} else {
								$mystery_products[] = $products[ $x ];
							}
						}

						$x ++;
					}
				}

				return apply_filters( 'wpcmb_get_products', $mystery_products, $assortment, $context );
			}

			return apply_filters( 'wpcmb_get_products', $products, $assortment, $context );
		}
	}

	function WPCleverWpcmb_Helper() {
		return WPCleverWpcmb_Helper::instance();
	}
}
