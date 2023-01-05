<?php

namespace IPRanking;

class Template {

	protected static $templates = array();

	public static function init() {
		self::add( array(
			'page' => 'Page IP Ranking'
		) );
		add_filter( 'theme_page_templates', array( __CLASS__, 'theme_page_templates' ) );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
		add_shortcode( 'ipranking', array( __CLASS__, 'page_shortcode' ) );
	}

	public static function page_shortcode( $atts ) {
		self::enqueue_assets();

		return self::html( 'page' );
	}

	public static function add( $template ) {
		self::$templates = array_merge( self::$templates, $template );
	}

	public static function theme_page_templates( $templates ) {
		return array_merge( $templates, self::$templates );
	}

	public static function template_include( $template ) {
		global $post;

		$post_template = get_post_meta( $post->ID, '_wp_page_template', true );

		if ( is_search() || ! $post || ! isset( self::$templates[ $post_template ] ) ) {
			return $template;
		}

		$file = Plugin::$path . 'templates/' . $post_template . '.php';

		if ( file_exists( $file ) ) {
			add_filter( 'ozisti_filter_sidebar_present', array( __CLASS__, 'sidebar_present' ) );
			self::enqueue_assets();

			return $file;
		}

		return $template;
	}

	public static function sidebar_present() {
		return false;
	}

	private static function enqueue_assets() {
		wp_register_style(
			'ip-ranking-style-css',
			Plugin::$url . 'assets/css/styles.min.css',
			array(),
			Plugin::VERSION
		);
		wp_enqueue_style( 'ip-ranking-style-css' );
		wp_enqueue_script(
			'ip-ranking-js',
			Plugin::$url . 'assets/js/scripts' . ( Plugin::DEV ? '' : '.min' ) . '.js',
			array( 'jquery' ),
			Plugin::VERSION
		);
	}

	public static function get( $template, $data = array() ) {
		echo self::html( $template, $data );
	}

	public static function html( $template, $data = array() ) {
		$file = Plugin::$path . 'templates/' . $template . '.php';
		if ( file_exists( $file ) ) {
			ob_start();
			$data = (object) $data;
			include $file;

			return ob_get_clean();
		}

		return '';
	}
}
