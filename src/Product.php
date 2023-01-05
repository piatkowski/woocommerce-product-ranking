<?php

namespace IPRanking;

class Product {
	private $id;
	private $product;
	private $template_data = array();

	// slug => name
	const ACF_MAP = array(
		'i1-desc' => 'area', // powierzchnia
		'i2-desc' => 'noise', //hałas
		'i4-desc' => 'filter_price', //roczny koszt filtrów
		'i3-desc' => 'energy' //koszt prądu (zł/mc)
	);

	// slug => name
	const ACF_MAP_MEDIA = array(/*'sp-icon4' => 'icon1', //ikona
		'sp-icon5' => 'icon2', //ikona
		'sp-icon6' => 'icon3' //ikona*/
	);

	// ID => name
	const PA_MAP = array(
		7 => 'airflow', //pa_wydajnosc
	);

	// ID => name
	const TAG_MAP = array(
		118 => 'allergy',
		231 => 'app',
		528 => 'office',
		529 => 'home',
		116 => 'home',
		294 => 'humid',
		139 => 'humid',
		311 => 'ions'
	);

	const CSV_MAP_NUM = array(
		'_ipr_rating_count' => 'reviews_num',
		'_ipr_rating'       => 'reviews',
		'_ipr_points'       => 'points'
	);

	const CSV_MAP_TEXT = array(
		'_ipr_airfilter' => 'airfilter',
		'_ipr_features'  => 'features'
	);

	const UNITS = array();

	public function __construct( $product_id ) {
		$this->id      = $product_id;
		$this->product = wc_get_product( $this->id );
		if ( $this->product ) {
			$transient = get_transient( 'ip_product_data_' . $this->id );
			if ( $transient === false ) {
				$this->template_data = array_merge( $this->template_data, $this->getMeta() );
				set_transient( 'ip_product_data_' . $this->id, $this->template_data, 500 );
			} else {
				$this->template_data = $transient;
			}
		}
	}

	public function getData() {
		return $this->template_data;
	}

	private function mapAttributes( $attributes ) {
		$output = array();
		foreach ( $attributes as $id => $value ) {
			if ( isset( self::PA_MAP[ $id ] ) ) {
				$output[ self::PA_MAP[ $id ] ] = floatval( $value );
			}
		}

		return $output;
	}

	private function mapTags( $tags ) {
		$output = array();
		foreach ( $tags as $id => $slug ) {
			if ( isset( self::TAG_MAP[ $id ] ) ) {
				$output[ self::TAG_MAP[ $id ] ] = 1;
			}
		}

		return $output;
	}

	private function getTags() {
		$terms = get_the_terms( $this->id, 'product_tag' );

		$tags = array();
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$tags[ $term->term_id ] = $term->slug;
			}
		}

		return $tags;
	}

	private function mapCSVFields() {
		$output = array();
		foreach ( self::CSV_MAP_NUM as $meta_key => $key ) {
			$output[ $key ] = floatval( str_replace( ',', '.', trim( $this->product->get_meta( $meta_key ) ) ) );
		}
		foreach ( self::CSV_MAP_TEXT as $meta_key => $key ) {
			$output[ $key ] = trim( $this->product->get_meta( $meta_key ) );
		}

		return $output;
	}

	private function getAttributes() {
		$attributes = array();
		foreach ( $this->product->get_attributes() as $attribute ) {
			if ( $attribute->is_taxonomy() ) {
				$attribute_values = wc_get_product_terms( $this->id, $attribute->get_name(), array( 'fields' => 'all' ) );
				$values           = array();
				foreach ( $attribute_values as $attribute_value ) {
					$value_name = esc_html( $attribute_value->name );
					$values[]   = $value_name;
				}
				if ( count( $values ) === 1 ) {
					$attributes[ $attribute->get_id() ] = $values[0];
				} else {
					$attributes[ $attribute->get_id() ] = $values;
				}
			} else {
				$attributes[ $attribute->get_id() ] = $attribute->get_options();
			}
		}

		return $attributes;
	}

	private function getMeta() {

		if ( $this->product->is_on_sale() ) {
			if ( $this->product->is_type( 'variable' ) ) {
				$regular_price = $this->product->get_variation_regular_price( 'min' );
				$price         = $this->product->get_variation_sale_price( 'min' );
			} else {
				$regular_price = $this->product->get_regular_price();
				$price         = $this->product->get_sale_price();
			}
		} else {
			if ( $this->product->is_type( 'variable' ) ) {
				$price = $this->product->get_variation_regular_price( 'min' );
			} else {
				$price = $this->product->get_price();
			}
			$regular_price = 0;
		}

		$attributes = $this->getAttributes();
		$tags       = $this->getTags();
		$output     = array(
			'is_on_sale'    => $this->product->is_on_sale(),
			'price'         => $price,
			'regular_price' => $regular_price,
			'link'          => $this->product->get_permalink(),
			'thumbnail'     => wp_get_attachment_image_src( $this->product->get_image_id() )[0],
			'image'         => wp_get_attachment_image_src( $this->product->get_image_id(), 'medium' )[0],
			'title'         => $this->product->get_name(),
			'desc'          => $this->product->get_short_description(),
			'manufacturer'  => $this->product->get_category_ids(),
			// for development
			/*'attributes'    => $attributes,
			'tags'          => $tags*/
		);
		$output     = array_merge( $output, $this->mapAttributes( $attributes ) );
		$output     = array_merge( $output, $this->mapTags( $tags ) );
		$output     = array_merge( $output, $this->mapAcfFields() );
		$output     = array_merge( $output, $this->mapCSVFields() );

		return $this->cleanOutput( $output );
	}

	private function mapAcfFields() {
		$output = array();
		foreach ( self::ACF_MAP as $acf_field => $var_name ) {
			$field_val = get_field( $acf_field, $this->id );
			if ( $field_val ) {
				$output[ $var_name ] = floatval( $field_val );
			} else {
				$output[ $var_name ] = '-';
			}
		}
		foreach ( self::ACF_MAP_MEDIA as $acf_field => $var_name ) {
			$attachment_id = (int) get_field( $acf_field, $this->id );
			if ( $attachment_id > 0 ) {
				$output[ $var_name ] = wp_get_attachment_image_url( $attachment_id );
			} else {
				$output[ $var_name ] = '';
			}
		}

		return $output;
	}

	private function cleanOutput( $output ) {
		if ( isset( $output['airflow'] ) ) {
			$output['airflow'] = floatval( $output['airflow'] );
		}

		return $output;
	}
}