<?php
namespace IPRanking;
?>
<div id="ipFilter">
    <form>
		<?php wp_nonce_field( 'ip_ranking_filter_nonce' ); ?>
        <input type="hidden" name="action" value="ip_ranking_filter">
        <input type="hidden" name="page" value="0">
        <input type="hidden" name="max_pages" value="<?php esc_html_e( $data->max_pages ); ?>">
        <input type="hidden" name="order_by" value="" autocomplete="off">
        <input type="hidden" name="order" value="" autocomplete="off">
        <div class="heading">
            <span>Filtracja produktów:</span>
        </div>
        <div class="ip-flex">
            <div class="ip-flex-item">
                <div class="ip-double-slider-container">
                    <span>Cena</span>
                    <div class="ip-double-slider">
                        <input type="range" data-name="price" name="price[from]" autocomplete="off" value="0" min="0"
                               max="<?php esc_html_e( $data->range['max_price'] ); ?>" step="5" class="from-slider"/>
                        <input type="range" data-name="price" name="price[to]" step="5" autocomplete="off"
                               value="<?php esc_html_e( $data->range['max_price'] ); ?>" min="0"
                               max="<?php esc_html_e( $data->range['max_price'] ); ?>"/>
                    </div>
                    <div class="ip-range">
                        <span id="ip-slider-price-from">0 zł</span>
                        <span id="ip-slider-price-to"><?php esc_html_e( $data->range['max_price'] ); ?> zł</span>
                    </div>
                </div>
                <div class="ip-double-slider-container">
                    <span>Roczny koszt filtrów</span>
                    <div class="ip-double-slider">
                        <input type="range" data-name="filter_price" name="filter_price[from]" autocomplete="off"
                               value="0" min="0"
                               max="<?php esc_html_e( $data->range['max_filter_price'] ); ?>" step="5"
                               class="from-slider"/>
                        <input type="range" data-name="filter_price" name="filter_price[to]" step="5" autocomplete="off"
                               value="<?php esc_html_e( $data->range['max_filter_price'] ); ?>" min="0"
                               max="<?php esc_html_e( $data->range['max_filter_price'] ); ?>"/>
                    </div>
                    <div class="ip-range">
                        <span id="ip-slider-filter_price-from">0 zł</span>
                        <span id="ip-slider-filter_price-to"><?php esc_html_e( $data->range['max_filter_price'] ); ?> zł</span>
                    </div>
                </div>
            </div>
            <div class="ip-flex-item ip-flex-padding">
                <div>
                    <label class="ip-checkbox red">promocja
                        <input type="checkbox" name="is_on_sale" value="1" autocomplete="off">
                        <span class="check"></span>
                    </label>
                    <label class="ip-checkbox blue">dla alergików
                        <input type="checkbox" name="feature[]" value="allergy" autocomplete="off">
                        <span class="check"></span>
                    </label>
                </div>
                <div>
                    <label class="ip-checkbox">dla domu
                        <input type="checkbox" name="feature[]" value="home" autocomplete="off">
                        <span class="check"></span>
                    </label>
                    <label class="ip-checkbox">dla firm
                        <input type="checkbox" name="feature[]" value="office" autocomplete="off">
                        <span class="check"></span>
                    </label>
                </div>
            </div>
            <div class="ip-flex-row">
                <label class="ip-checkbox">Z nawilżaczem powietrza
                    <input type="checkbox" name="feature[]" value="humid" autocomplete="off">
                    <span class="check"></span>
                </label>
                <label class="ip-checkbox">Jonizacja
                    <input type="checkbox" name="feature[]" value="ions" autocomplete="off">
                    <span class="check"></span>
                </label>
                <label class="ip-checkbox">Z aplikacją mobilną
                    <input type="checkbox" name="feature[]" value="app" autocomplete="off">
                    <span class="check"></span>
                </label>
            </div>
            <div class="ip-flex-item-fill">
                <div class="heading">
                    <span>Przepływ powietrza CADR:</span>
                </div>
                <div class="ip-flex">
                    <div class="ip-flex-item-fill">
                        <select name="airflow[from]" autocomplete="off">
							<?php
							foreach ( range( 50, 250, 50 ) as $value ) {
								echo '<option value="' . $value . '">' . $value . '</option>';
							}
							?>
                        </select>
                        <span class="dash">&dash;</span>
                        <select name="airflow[to]" autocomplete="off">
							<?php
							foreach ( range( 500, 1900, 100 ) as $value ) {
								echo '<option value="' . $value . '">' . $value . '</option>';
							}
							?>
                            <option value="2000" selected>2000</option>
                        </select>
                    </div>
                    <div class="ip-flex-item-fill hide-xs">
                        <span>m<sup>3</sup>/h</span>
                    </div>
                </div>
            </div>
            <div class="ip-flex-item-fill">
                <div class="heading">
                    <span>Głośność:</span>
                </div>
                <div class="ip-flex">
                    <div class="ip-flex-item-fill">
                        <select name="noise[from]" autocomplete="off">
							<?php
							foreach ( range( 15, 70, 5 ) as $value ) {
								echo '<option value="' . $value . '">' . $value . '</option>';
							}
							?>
                        </select>
                        <span class="dash">&dash;</span>
                        <select name="noise[to]" autocomplete="off">
							<?php
							foreach ( range( 15, 65, 5 ) as $value ) {
								echo '<option value="' . $value . '">' . $value . '</option>';
							}
							?>
                            <option value="70" selected>70</option>
                        </select>
                    </div>
                    <div class="ip-flex-item-fill hide-xs">
                        <span>dB</span>
                    </div>
                </div>
            </div>
            <div class="ip-flex-row">
                <div class="heading">
                    <span>Producent:</span>
                </div>
				<?php
				$terms_included = array();
				$terms_excluded = array();
				if ( ! empty( Plugin::config( 'manufacturer_ids' ) ) ) {
					$terms_included = get_terms( array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => true,
						'parent'     => Plugin::config( 'category_id' ),
						'orderby'    => 'include',
						'include'    => Plugin::config( 'manufacturer_ids' )
					) );
					$terms_excluded = get_terms( array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => true,
						'parent'     => Plugin::config( 'category_id' ),
						'exclude'    => Plugin::config( 'manufacturer_ids' )
					) );
				} else {
					$terms_included = get_terms( array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => true,
						'parent'     => Plugin::config( 'category_id' ),
					) );
                }
				?>
                <select name="manufacturer" autocomplete="off">
                    <option value="0"> - wybierz -</option>
					<?php
					if ( $terms_included ) {
						foreach ( $terms_included as $term ) {
							echo '<option value="' . $term->term_id . '">' . strtoupper( $term->slug ) . '</option>';
						}
					}
					if ( $terms_excluded ) {
						foreach ( $terms_excluded as $term ) {
							echo '<option value="' . $term->term_id . '">' . strtoupper( $term->slug ) . '</option>';
						}
					}
					?>
                </select>
            </div>
            <div class="ip-flex-row">
                <button type="submit" class="ip-button">Pokaż produkty</button>
                <button type="reset" class="ip-button reset">Resetuj</button>
            </div>
        </div>
    </form>
</div>