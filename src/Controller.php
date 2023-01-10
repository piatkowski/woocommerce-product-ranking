<?php

namespace IPRanking;

class Controller {

	public static function init() {
		add_action(
			'wp_ajax_ip_ranking_filter', array( __CLASS__, 'actionFilter' )
		);
		add_action(
			'wp_ajax_nopriv_ip_ranking_filter', array( __CLASS__, 'actionFilter' )
		);
	}

	private static function sanitizeFilterInput( $input ) {
		$output = array();

		/* Sanitize range fields - from - to */
		$from_tos = array(
			'airflow',
			'price',
			'filter_price',
			'noise'
		); //@todo get array from Product class or Settings
		foreach ( $from_tos as $key ) {
			if ( isset( $input[ $key ] ) && is_array( $input[ $key ] ) && isset( $input[ $key ]['from'] ) && isset( $input[ $key ]['to'] ) ) {
				$from = floatval( $input[ $key ]['from'] );
				$to   = floatval( $input[ $key ]['to'] );
				if ( $from > 0 ) {
					$output[] = array(
						'field'   => $key,
						'compare' => '>=',
						'value'   => $from
					);
				}
				if ( $to > 0 ) {
					$output[] = array(
						'field'   => $key,
						'compare' => '<=',
						'value'   => $to
					);
				}
			}
		}

		/* Sanitize is on sale */
		if ( isset( $input['is_on_sale'] ) ) {
			$output[] = array(
				'field'   => 'is_on_sale',
				'compare' => '=',
				'value'   => 1
			);
		}

		/* Sanitize manufacturer */
		if ( isset( $input['manufacturer'] ) && (int) $input['manufacturer'] > 0 ) {
			$output[] = array(
				'field'   => 'manufacturer',
				'compare' => 'IN',
				'value'   => (int) $input['manufacturer']
			);
		}

		$features = array(
			'allergy',
			'home',
			'office',
			'humid',
			'ions',
			'app'
		); //@todo get array from Product class or Settings,

		if ( isset( $input['feature'] ) && is_array( $input['feature'] ) ) {
			foreach ( $features as $feature ) {
				if ( in_array( $feature, $input['feature'] ) ) {
					$output[] = array(
						'field'   => $feature,
						'compare' => '=',
						'value'   => 1
					);
				}
			}
		}

		return $output;

	}

	private static function sanitizeSortInput( $input ) {
		$allowedFields = array(
			'price',
			'reviews',
			'airflow',
			'humid',
			'area',
			'noise',
			//'airfilter',
			//'features',
			'filter_price',
			'energy',
			'points'
		);
		$defaultSortBy = 'points';

		return array(
			'order'    => isset( $input['order'] ) && $input['order'] === 'ASC' ? 'ASC' : 'DESC',
			'order_by' => isset( $input['order_by'] ) && in_array( $input['order_by'], $allowedFields ) ? sanitize_text_field( $input['order_by'] ) : $defaultSortBy
		);
	}

	public static function actionFilter() {
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ip_ranking_filter_nonce' ) ) {

			$page = isset( $_POST['page'] ) ? (int) $_POST['page'] : 0;

			$sanitizedFilterArgs = self::sanitizeFilterInput( $_POST );
			$sanitizedSortArgs   = self::sanitizeSortInput( $_POST );
			$filtered            = self::filterProducts(
				array_merge( $sanitizedFilterArgs, $sanitizedSortArgs ),
				array( 'page' => $page )
			);

			$html = '';
			foreach ( $filtered->products as $id ) {
				$html .= self::renderTableRow( $id );
			}
			if ( empty( $html ) ) {
				$html = Template::html( 'row-not-found' );
			}
			wp_send_json_success( array(
				'count'     => $filtered->count,
				'max_pages' => ceil( $filtered->count / Plugin::config( 'per_page' ) ),
				'html'      => $html,
				'sort'      => $sanitizedSortArgs,
				'filter'    => $sanitizedFilterArgs
			) );
		}
		wp_send_json_error( array(
			'count'     => 0,
			'max_pages' => 1,
			'html'      => 'Wystąpił nieoczekiwany błąd !',
			'sort'      => $sanitizedSortArgs
		) );
	}

	public static function getProducts( $query_args = array() ) {
		$category_id = Plugin::config( 'category_id' );
		$category    = get_term_by( 'id', $category_id, 'product_cat', 'ARRAY_A' );
		$args        = array(
			'status'   => 'publish',
			'return'   => 'ids',
			'category' => $category['slug'],
		);
		foreach ( $query_args as $key => $value ) {
			$args[ $key ] = $value;
		}

		return wc_get_products( $args );
	}

	public static function getAllProducts( $query_args = array() ) {
		$product_ids = get_transient( 'ip_all_products_ids' );
		if ( $product_ids === false ) {
			$query_args['limit'] = - 1;
			$product_ids         = self::getProducts( $query_args );
			set_transient( 'ip_all_products_ids', $product_ids, 500 );
		}

		return $product_ids;
	}

	public static function getFilterPriceRange() {
		$product_ids = self::getAllProducts();
		$range       = array(
			'max_price'        => 0,
			'max_filter_price' => 0
		);
		foreach ( $product_ids as $id ) {
			$product                   = ( new Product( $id ) )->getData();
			$range['max_price']        = max(
				$range['max_price'],
				floatval( $product['price'] ),
				floatval( $product['regular_price'] )
			);
			$range['max_filter_price'] = max(
				$range['max_filter_price'],
				floatval( $product['filter_price'] )
			);
		}

		$range['max_price']        = ceil( $range['max_price'] / 1000 ) * 1000;
		$range['max_filter_price'] = ceil( $range['max_filter_price'] / 1000 ) * 1000;

		return $range;
	}

	/*
	 * $filter_args = array(
	 *      0 => array(
	 *              'field' => 'name',
	 *              'compare' => '>',
	 *              'value'   =>
	 *      )
	 * )
	 */
	public static function filterProducts( $filter_args, $query_args = array() ) {
		$product_ids = self::getAllProducts();

		$per_page     = (int) Plugin::config( 'per_page' );
		$page         = isset( $query_args['page'] ) ? (int) $query_args['page'] : 0; //first page is 0
		$start_offset = $page * $per_page;

		if ( empty( $filter_args ) ) {
			$filter_args = self::sanitizeSortInput( array() );
			/*return (object) array(
				'count'    => count( $product_ids ),
				'products' => $per_page ? array_slice( $product_ids, $start_offset, $per_page ) : $product_ids
			);*/
		}

		$sort_args = array(
			'order'    => $filter_args['order'],
			'order_by' => $filter_args['order_by']
		);

		unset( $filter_args['order'] );
		unset( $filter_args['order_by'] );

		$products = array();
		foreach ( $product_ids as $id ) {
			$product = ( new Product( $id ) )->getData();
			if ( ! empty( $sort_args['order_by'] ) ) {
				$products[ $id ] = $product[ $sort_args['order_by'] ] ?? null;
			} else {
				$products[ $id ] = $id;
			}

		}

		unset( $product_ids );

		if ( ! empty( $sort_args['order_by'] ) ) {
			if ( $sort_args['order'] === 'ASC' ) {
				asort( $products, SORT_NUMERIC );
			} else if ( $sort_args['order'] === 'DESC' ) {
				arsort( $products, SORT_NUMERIC );
			}
		}

		$products_filtered = array();

		if ( ! empty( $filter_args ) ) {
			foreach ( $products as $id => $x ) {
				$product = ( new Product( $id ) )->getData();
				if ( self::isMatchingFilter( $product, $filter_args ) ) {
					$products_filtered[] = $id;
				}
			}
		} else {
			$products_filtered = array_keys( $products );
		}
		unset( $products );

		return (object) array(
			'count'    => count( $products_filtered ),
			'products' => $per_page ? array_slice( $products_filtered, $start_offset, $per_page ) : $products_filtered
		);
	}

	private static function isMatchingFilter( $product_data, $filter_args ) {
		$isMatching = false;

		foreach ( $filter_args as $arg ) {
			$field = $arg['field'];

			$value = isset( $product_data[ $field ] ) ? $product_data[ $field ] : null;

			if ( ! $value ) {
				$isMatching = false;
			} else {
				switch ( $arg['compare'] ) {
					case 'equals':
						$isMatching = (string) $value === (string) $arg['value'];
						break;
					case '=':
						$isMatching = floatval( $value ) === floatval( $arg['value'] );
						break;
					case '>=':
						$isMatching = floatval( $value ) >= floatval( $arg['value'] );
						break;
					case '<=':
						$isMatching = floatval( $value ) <= floatval( $arg['value'] );
						break;
					case 'IN':
						$isMatching = in_array( (int) $arg['value'], $value );
						break;
					default:
						$isMatching = false;
				}
			}
			if ( ! $isMatching ) {
				break;
			}
		}

		return $isMatching;
	}

	public static function renderTableRow( $id ) {
		$product = new Product( $id );
		$data    = $product->getData();

		return
			Template::html( 'row-toggle' ) .
			Template::html( 'row', $data ) .
			Template::html( 'row-details', $data ) .
			Template::html( 'row-mobile', $data );
	}

	public static function renderTable() {
		$products = ( self::filterProducts( array(
			'order'    => 'DESC',
			'order_by' => 'points'
		) ) )->products;

		foreach ( $products as $product ) {
			echo self::renderTableRow( $product );
		}
	}
}