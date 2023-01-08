<?php

namespace IPRanking;

class Admin {
	const PAGE = "ipr_settings";

	const OPTIONS = "ipr_options";

	private static $fields;
	private static $import_error;
	private static $import_temp_data;

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'settings_init' ) );
		add_action( 'admin_init', array( __CLASS__, 'process_post_data' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 11 );
		add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'add_screen_to_woocommerce' ) );
	}

	public static function process_post_data() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( isset( $_POST['ipr_clear_cache'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipr_clear_cache' ) ) {
			delete_transient( 'ip_all_products_ids' );
			$query_args['limit'] = - 1;
			$product_ids         = Controller::getProducts( $query_args );
			foreach ( $product_ids as $id ) {
				delete_transient( 'ip_product_data_' . $id );
			}
			add_settings_error( 'ipr_messages', 'ipr_messages', 'Cache wyczyszczony.', 'updated' );

			return;
		}
		if ( isset( $_POST['ipr_csv_form_submit'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipr_csv_form' ) ) {
			$allowed = array(
				'_ipr_rating_count',
				'_ipr_rating',
				'_ipr_airfilter',
				'_ipr_features',
				'_ipr_points'
			);
			foreach ( $allowed as $key ) {
				if ( isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ) {
					foreach ( $_POST[ $key ] as $id => $value ) {
						$id      = (int) $id;
						$product = wc_get_product( $id );
						if ( $id > 0 && $product ) {
							$value = sanitize_textarea_field( $value );
							$product->update_meta_data( $key, $value );
							$product->save();
						}
					}
				}
			}
			add_settings_error( 'ipr_csv_messages', 'ipr_csv_messages', 'Zaktualizowano dane dodatkowe', 'updated' );

			return;
		}

		if ( isset( $_POST['ipr_csv_verify'] ) && isset( $_FILES['csv_file'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipr_csv_import' ) ) {
			if ( $_FILES['csv_file']['size'] > 0 ) {
				self::$import_temp_data = self::import_from_csv( $_FILES['csv_file']['tmp_name'], true );
				if ( is_array( self::$import_temp_data ) ) {
					add_settings_error( 'ipr_messages', 'ipr_messages', 'Plik jest prawidłowy. ', 'updated' );
				} else {
					add_settings_error( 'ipr_messages', 'ipr_messages', 'Weryfikacja negatywna. ' . self::$import_error);
				}
			}

			return;
		}

		if ( isset( $_POST['ipr_csv_import'] ) && isset( $_FILES['csv_file'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipr_csv_import' ) ) {
			if ( $_FILES['csv_file']['size'] > 0 ) {
				if ( self::import_from_csv( $_FILES['csv_file']['tmp_name'] ) ) {
					add_settings_error( 'ipr_messages', 'ipr_messages', 'Zaimportowano plik CSV', 'updated' );
				} else {
					add_settings_error( 'ipr_messages', 'ipr_messages', 'Import CSV nie powiódł się. ' . self::$import_error);
				}
			}

			return;
		}

		if ( isset( $_POST['ipr_csv_import_transient'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipr_csv_import' ) ) {
			$meta_data = get_transient('ipr_csv_import_dry_run_cache');
			if ( is_array($meta_data) ) {
				if ( self::import_from_transient() ) {
					delete_transient('ipr_csv_import_dry_run_cache');
					add_settings_error( 'ipr_messages', 'ipr_messages', 'Zaimportowano plik CSV.', 'updated' );
				}
			} else {
				add_settings_error( 'ipr_messages', 'ipr_messages', 'Import CSV nie powiódł się. Spróbuj ponownie.');
            }

			return;
		}

	}

	private static function import_from_csv( $file_path, $dry_run = false ) {

		if ( ! file_exists( $file_path ) ) {
			self::$import_error = 'Plik do zaimportowania nie istnieje!';

			return false;
		}
		$fp = fopen( $file_path, 'r' );
		if ( $fp === false ) {
			self::$import_error = 'Błąd odczytu pliku';

			return false;
		}
		if ( fgets( $fp, 4 ) !== "\xef\xbb\xbf" ) {
			rewind( $fp );
		}
		$meta_data       = array();
		$columns         = fgetcsv( $fp, null, ';' );
		$allowed_columns = array( 'id', 'ilość ocen', 'ocena', 'filtr', 'funkcje', 'punktacja' );
		$column_map      = array();
		if ( $columns === false ) {
			self::$import_error = 'Błąd odczytu kolumn!';

			return false;
		} else {
			foreach ( $columns as $id => $column ) {
				if ( ! in_array( strtolower( $column ), $allowed_columns ) ) {
					self::$import_error = 'Plik zawiera nierozponawalne kolumny. Sprawdź poprawność danych. Prawidłowy plik powinien zawierać wyłącznie kolumny o nazwach: ' . implode( ', ', $allowed_columns );

					return false;
				}
				$column_map[ strtolower( $column ) ] = $id;
			}
		}


		while ( ! feof( $fp ) && ( $line = fgetcsv( $fp, null, ';' ) ) !== false ) {

			$id = (int) trim( $line[ $column_map['id'] ] );
			if ( $id > 0 ) {
				$meta_data[ $id ] = array(
					'_ipr_rating_count' => sanitize_textarea_field( trim( $line[ $column_map['ilość ocen'] ] ) ),
					'_ipr_rating'       => sanitize_textarea_field( trim( $line[ $column_map['ocena'] ] ) ),
					'_ipr_airfilter'    => sanitize_textarea_field( trim( $line[ $column_map['filtr'] ] ) ),
					'_ipr_features'     => sanitize_textarea_field( trim( $line[ $column_map['funkcje'] ] ) ),
					'_ipr_points'       => sanitize_textarea_field( trim( $line[ $column_map['punktacja'] ] ) )
				);
			}
		}

		if ( $dry_run ) {
			self::$import_error = '';
			delete_transient('ipr_csv_import_dry_run_cache');
            set_transient('ipr_csv_import_dry_run_cache', $meta_data, 3600);
			return $meta_data;
		}

		if ( ! $dry_run ) {
			self::update_product_data($meta_data);
		}
		self::$import_error = '';

		return true;
	}

    private static function import_from_transient() {
        $meta_data = get_transient('ipr_csv_import_dry_run_cache');
        self::update_product_data($meta_data);
        return true;
    }

    private static function update_product_data($meta_data) {
	    foreach ( $meta_data as $id => $meta ) {
		    $product = wc_get_product( $id );
		    if ( $product ) {
			    foreach ( $meta as $key => $value ) {
				    if(!empty($value)) {
					    $product->update_meta_data( $key, $value );
				    }
			    }
			    $product->save();
		    }
	    }
    }

	public static function enqueue_scripts( $hook ) {
		if ( $hook === 'woocommerce_page_' . self::PAGE ) {
			wp_enqueue_script(
				'nbd-order-dashboard-table',
				Plugin::$url . 'assets/js/admin' . ( Plugin::DEV ? '' : '.min' ) . '.js',
				array( 'jquery' ),
				Plugin::VERSION
			);
		}
	}

	public static function add_screen_to_woocommerce( $screen_ids ) {
		$screen_ids[] = 'woocommerce_page_' . self::PAGE;

		return $screen_ids;
	}

	public static function settings_init() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$categories   = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'fields'     => 'id=>name',
			'orderby'    => 'name',
			'parent'     => 0
		) );
		self::$fields = array(
			'category_id' => array(
				'label'   => 'Kategoria oczyszczaczy',
				'desc'    => 'Numer ID kategorii oczyszczaczy (kategoria główna)',
				'type'    => 'select',
				'default' => 88,
				'options' => $categories
			),
			'manufacturer_ids' => array(
				'label'   => 'ID Producentów',
				'desc'    => 'ID kategorii producentów oddzielone przecinkiem. Pole służy do określenia kolejności producentów w filtrowaniu.',
				'type'    => 'text',
				'default' => ''
			),
			'per_page'    => array(
				'label'   => 'Ilość produktów na stronę',
				'desc'    => 'Ilość produktów na stronę',
				'type'    => 'number',
				'default' => 10,
			),
			'title'       => array(
				'label'   => 'Tytuł tabeli',
				'desc'    => 'Tytuł tabeli',
				'type'    => 'text',
				'default' => 'Ranking oczyszczaczy',
			),
			'sale_tag_id' => array(
				'label'   => 'Tag dla promocji',
				'desc'    => 'ID tagu lub wiele ID oddzielonych przecinkami. Jeżeli produkt posiada ten tag to będzie traktowany jako "w promocji".',
				'type'    => 'text',
				'default' => '902'
			)
		);

		register_setting( self::PAGE, self::OPTIONS, array( __CLASS__, 'validate' ) );

		$section = 'ipr_section_general';

		add_settings_section(
			$section,
			'',
			null,
			self::PAGE
		);

		foreach ( self::$fields as $name => $field ) {
			add_settings_field(
				'ipr_' . $name,
				$field['label'],
				array( __CLASS__, 'field_render' ),
				self::PAGE,
				$section,
				array(
					'name'    => $name,
					'type'    => $field['type'],
					'default' => $field['default'],
					'desc'    => $field['desc'],
					'options' => isset( $field['options'] ) ? $field['options'] : null
				)
			);
		}
	}

	public static function add_menu_page() {
		add_submenu_page(
			'woocommerce',
			'Ranking',
			'Ranking',
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'html_page' )
		);
	}

	public static function html_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( 'ipr_messages', 'ipr_messages', 'Ustawienia zostały zaktualizowane.', 'updated' );
		}
		settings_errors( 'ipr_messages' );
		if ( is_array( self::$import_temp_data ) ) {
			?>
            <div class="wrap">
                <h2>Podgląd danych z CSV</h2>
                <form method="POST">
		            <?php wp_nonce_field( 'ipr_csv_import' ); ?>
                    <button type="submit" name="ipr_csv_import_transient" value="1" class="button action">Zapisz dane do bazy</button>
                    <button type="button" onclick="location.reload()" class="button">Anuluj</button>
                </form>
                <table class="wp-list-table widefat fixed striped table-view-list pages">
                    <thead>
                    <tr>
                        <td style="width:50px">ID</td>
                        <td>Ilość ocen</td>
                        <td>Ocena</td>
                        <td>Filtr</td>
                        <td>Funkcje</td>
                        <td>Punktacja</td>
                    </tr>
                    </thead>
                    <tbody>
		            <?php
		            foreach ( self::$import_temp_data as $id => $row ) {
			            ?>
                        <tr>
                            <td><?php esc_html_e( $id ); ?></td>
                            <td><?php echo esc_textarea( $row[ '_ipr_rating_count' ] ?? '' ); ?></td>
                            <td><?php echo esc_textarea( $row[ '_ipr_rating' ] ?? '' ); ?></td>
                            <td style="white-space:pre"><?php echo esc_textarea( $row[ '_ipr_airfilter' ] ?? '' ); ?></td>
                            <td style="white-space:pre"><?php echo esc_textarea( $row[ '_ipr_features' ] ?? '' ); ?></td>
                            <td><?php echo esc_textarea( $row[ '_ipr_points' ] ?? '' ); ?></td>
                        </tr>
			            <?php
		            }
		            ?>
                    </tbody>
                </table>
            </div>
			<?php
		}
		?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?> - ustawienia</h1>
            <form action="options.php" method="post">
				<?php
				settings_fields( self::PAGE );
				do_settings_sections( self::PAGE );
				submit_button( 'Zapisz' );
				?>
            </form>
        </div>
		<?php

		self::csv_import_section();
	}

	private static function csv_import_section() {
		?>
        <hr/>
        <style>
            td.editable {
                white-space: pre;
                unicode-bidi: embed;
                border: 1px dashed #ccc;
                cursor: pointer;
                position: relative;
            }

            td.editable textarea {
                width: 95%;
                height: 150px;
                position: relative;
                z-index: 5;
            }

            td.editable:hover:before {
                content: 'Podwójne kliknięcie - edycja pola';
                position: absolute;
                color: #999;
                font-size: 11px;
                z-index: 1;
                background: #ffffff;
            }
        </style>
        <div class="wrap">
            <h2>Cache</h2>
            <div class="tablenav top">
                <form method="POST">
					<?php wp_nonce_field( 'ipr_clear_cache' ); ?>
                    <button type="submit" name="ipr_clear_cache" value="1" class="button action">Wyczyść cache</button>
                </form>
            </div>
        </div>
        <hr/>
        <div class="wrap">
            <h2>Import CSV</h2>
            <p>Plik CSV rozdzielany znakiem średnika ;. Kolumny: ID, Ilosc ocen, Ocena, Filtr, Funkcje, Punktacja. Uwaga
                nie dodawać kolumny "Nazwa produktu"!</p>
            <div class="tablenav top">

                <form method="POST" enctype="multipart/form-data">
					<?php wp_nonce_field( 'ipr_csv_import' ); ?>
                    <input type="file" name="csv_file" accept=".csv" required>
                    <button type="submit" name="ipr_csv_verify" value="1" class="button action">Importuj CSV</button>
                    <!--<button type="submit" name="ipr_csv_import" value="1" class="button action">Import CSV</button>-->
                </form>
            </div>
        </div>
        <hr/>
        <div class="wrap">
			<?php settings_errors( 'ipr_csv_messages' ); ?>
            <h2>Edycja danych</h2>
            <form method="POST">
				<?php wp_nonce_field( 'ipr_csv_form' ); ?>
                <div class="tablenav top">
                    <button type="submit" name="ipr_csv_form_submit" value="1" class="button button-primary action">
                        Zapisz zmiany
                    </button>
                </div>

                <table class="wp-list-table widefat fixed striped table-view-list pages">
                    <thead>
                    <tr>
                        <td style="width:50px">ID</td>
                        <td>Nazwa produktu</td>
                        <td>Ilość ocen</td>
                        <td>Ocena</td>
                        <td>Filtr</td>
                        <td>Funkcje</td>
                        <td>Punktacja (dla sortowania)</td>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					$product_ids = Controller::getProducts( array( 'limit' => - 1 ) );
					foreach ( $product_ids as $id ) {
						$product = wc_get_product( $id );
						?>
                        <tr>
                            <td><?php esc_html_e( $product->get_id() ); ?></td>
                            <td><?php esc_html_e( $product->get_title() ); ?></td>
                            <td class="editable"
                                data-name="_ipr_rating_count[<?php echo (int) $id; ?>]"><?php echo esc_textarea( $product->get_meta( '_ipr_rating_count' ) ); ?></td>
                            <td class="editable"
                                data-name="_ipr_rating[<?php echo (int) $id; ?>]"><?php echo esc_textarea( $product->get_meta( '_ipr_rating' ) ); ?></td>
                            <td class="editable"
                                data-name="_ipr_airfilter[<?php echo (int) $id; ?>]"><?php echo esc_textarea( $product->get_meta( '_ipr_airfilter' ) ); ?></td>
                            <td class="editable"
                                data-name="_ipr_features[<?php echo (int) $id; ?>]"><?php echo esc_textarea( $product->get_meta( '_ipr_features' ) ); ?></td>
                            <td class="editable"
                                data-name="_ipr_points[<?php echo (int) $id; ?>]"><?php echo esc_textarea( $product->get_meta( '_ipr_points' ) ); ?></td>
                        </tr>
						<?php
					}
					?>
                    </tbody>
                </table>
            </form>
        </div>
		<?php
	}

	public static function get( $name ) {
		$options = get_option( self::OPTIONS );
		if ( isset( $options[ $name ] ) ) {
			return $options[ $name ];
		}

		return self::$fields[ $name ]['default'];
	}

	/*
	 * ----------------------------------
	 *             Callbacks
	 * ----------------------------------
	 */

	public static function field_render( $args ) {
		$name  = $args['name'];
		$type  = $args['type'];
		$value = get_option( self::OPTIONS );

		$value = ( isset( $value[ $name ] ) ) ? $value[ $name ] : $args['default'];
		$name  = self::OPTIONS . '[' . $name . ']';

		switch ( $type ) {
			case 'text':
			case 'number':
				echo '<input type="' . esc_attr( $type ) . '" class="regular-text" name="' . esc_attr( $name ) . '" value="' . esc_html( $value ) . '" />';
				break;
			case 'checkbox':
				$checked = $value === 'yes';
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="no" />';
				echo '<input type="checkbox" name="' . esc_attr( $name ) . '" value="yes" ' . checked( $checked, true, false ) . ' />';
				break;
			case 'select':
				echo '<select name="' . esc_attr( $name ) . '" autocomplete="off">';
				if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
					foreach ( $args['options'] as $opt_val => $opt_title ) {
						echo '<option value="' . esc_attr( $opt_val ) . '"' . selected( $opt_val, $value, false ) . '>';
						echo esc_html( $opt_title ) . ' #' . esc_attr( $opt_val );
						echo '</option>';
					}
				}
				echo '</select>';
				break;
		}
        echo '<p>' . esc_html($args['desc']) . '</p>';
	}

	/**
	 * Validate and sanitize user input - callback
	 *
	 * @param $input
	 *
	 * @return array
	 * @since 1.3.0
	 */
	public static function validate( $input ) {
		$input['category_id'] = (int) $input['category_id'];
		$input['per_page']    = (int) $input['per_page'];
		$input['title']       = sanitize_text_field( $input['title'] );

		return $input;
	}
}