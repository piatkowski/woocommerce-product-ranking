<?php

/*
Plugin Name: Ranking Produktów
Description:
Version: 1.1.4
Author: Krzysztof Piątkowski
Author URI: https://github.com/piatkowski/
License: GPL2
*/

namespace IPRanking;

include 'src/Template.php';
include 'src/Product.php';
include 'src/Controller.php';
include 'src/Admin.php';

class Plugin {
	public static $path = '';
	public static $url = '';
	const DEV = true;
	const VERSION = '1.1.4';

	public static function init() {
		self::$path = plugin_dir_path( __FILE__ );
		self::$url  = plugins_url( '/', __FILE__ );
		add_action( 'plugins_loaded', array( Template::class, 'init' ) );
		Controller::init();
		Admin::init();
	}

	public static function config( $key ) {

		$config = array(
			'category_id'      => Admin::get( 'category_id' ),
			'per_page'         => Admin::get( 'per_page' ),
			'title'            => Admin::get( 'title' ),
			'sale_tag_id'      => Admin::get( 'sale_tag_id' ),
			'manufacturer_ids' => Admin::get( 'manufacturer_ids' )
		);

		if ( $key && isset( $config[ $key ] ) ) {
			return $config[ $key ];
		}

		return null;
	}

}

Plugin::init();