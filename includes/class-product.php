<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Product_Wpcmb' ) && class_exists( 'WC_Product' ) ) {
	class WC_Product_Wpcmb extends WC_Product_Simple {
		public function __construct( $product = 0 ) {
			parent::__construct( $product );
		}

		public function get_type() {
			return 'wpcmb';
		}

		public function add_to_cart_text() {
			if ( $this->is_purchasable() && $this->is_in_stock() ) {
				$text = WPCleverWpcmb_Helper()->localization( 'button_select', esc_html__( 'Add to cart', 'wpc-mystery-box' ) );
			} else {
				$text = WPCleverWpcmb_Helper()->localization( 'button_read', esc_html__( 'Read more', 'wpc-mystery-box' ) );
			}

			return apply_filters( 'wpcmb_product_add_to_cart_text', $text, $this );
		}

		public function single_add_to_cart_text() {
			$text = WPCleverWpcmb_Helper()->localization( 'button_single', esc_html__( 'Add to cart', 'wpc-mystery-box' ) );

			return apply_filters( 'wpcmb_product_single_add_to_cart_text', $text, $this );
		}

		public function get_stock_status( $context = 'view' ) {
			$parent_status = parent::get_stock_status( $context );

			if ( ( $assortments = $this->get_assortments() ) && ( ! empty( $assortments ) ) ) {
				foreach ( $assortments as $assortment ) {
					if ( isset( $assortment['necessary'] ) && ( $assortment['necessary'] === 'required' ) ) {
						// check required assortments
						$number              = isset( $assortment['number'] ) ? (int) $assortment['number'] : 1;
						$assortment_products = WPCleverWpcmb_Helper()->get_products( $assortment, 'mystery' );

						if ( empty( $assortment_products ) || ( count( $assortment_products ) < $number ) ) {
							return 'outofstock';
						}
					}
				}
			}

			return $parent_status;
		}

		public function get_assortments() {
			$product_id  = $this->id;
			$assortments = get_post_meta( $product_id, 'wpcmb_assortments', true );

			return apply_filters( 'wpcmb_product_get_assortments', $assortments, $this );
		}
	}
}
