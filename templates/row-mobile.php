<div class="ip-mobile-row ajax">

    <div class="w-15 number">
        <div class="ip-col-number">
            <span class="ip-row-mobile-nr">1</span>
        </div>
    </div>
    <div class="w-85 line number"></div>
    <div class="clear"></div>

    <div class="ip-flex">
        <div class="w-50 ip-col-image">
            <img
                    src="<?php esc_html_e( $data->image ); ?>"
                    loading="lazy"
                    alt="<?php esc_html_e( $data->title ); ?>">
        </div>
        <div class="w-25">
            <div class="ip-col-title"><?php esc_html_e( $data->title ); ?></div>
            <div class="ip-col-reviews">
                <div class="rating">
					<?php
					$rating    = $data->reviews;
					$maxRating = 5;
					for ( $r = 0; $r < $maxRating; $r ++ ) {
						$diff      = $rating - $r;
						$className = '';
						if ( $diff < 1 && $diff > 0 ) {
							$className = 'half';
						} else if ( $diff <= 0 ) {
							$className = 'off';
						}
						echo '<div class="rating-star' . ( $className ? ' ' . $className : '' ) . '"></div>';
					}
					?>
                </div>
				<?php echo intval( $data->reviews_num ) ?> opinii
            </div>
            <div class="ip-col-price">
				<?php if ( $data->is_on_sale ) : ?>
                    <span class="striked"><?php echo strip_tags( wc_price( $data->regular_price ) ); ?></span>
                    <span><?php echo strip_tags( wc_price( $data->price ) ); ?></span>
				<?php else: ?>
                    <span><?php echo strip_tags( wc_price( $data->price ) ); ?></span>
				<?php endif; ?>
            </div>
        </div>
        <div class="w-25">
            <div class="ip-icons">
				<?php
				if ( isset( $data->home ) ) {
					echo '<div><span class="ip-icon ip-icon-do-domu"></span></div>';
				}
				if ( isset( $data->office ) ) {
					echo '<div><span class="ip-icon ip-icon-do-pracy"></span></div>';
				}
				if ( isset( $data->allergy ) ) {
					echo '<div><span class="ip-icon ip-icon-alergicy"></span></div>';
				}
				?>
            </div>
            <div class="ip-col-humid">
                <div class="ip-icons">
					<?php if ( isset( $data->humid ) ) : ?>
                        <span class="ip-icon ip-icon-nawilzanie"></span>
                        <span class="ip-icon ip-icon-nawilzanie"></span>
                        <span class="ip-icon ip-icon-nawilzanie"></span>
					<?php else: ?>
                        -
					<?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="w-33">
        <div class="label">M2</div>
        <div class="ip-col-area">
			<?php echo wp_kses_post( $data->area ?? '-' ); ?>
			<?php echo ( (int) $data->area > 0 ) ? ' m<sup>2</sup>' : ''; ?>
        </div>
    </div>
    <div class="w-33">
        <div class="label">CADR</div>
        <div class="ip-col-airflow">
			<?php esc_html_e( $data->airflow ?? '-' ); ?>
			<?php echo isset( $data->airflow ) ? ' m<sup>3</sup>/h' : ''; ?>
        </div>
    </div>
    <div class="w-33">
        <div class="label">GŁOŚNOŚC</div>
        <div class="ip-col-noise">
			<?php esc_html_e( $data->noise ?? '-' ); ?>
			<?php echo ( (int) $data->noise > 0 ) ? ' dB' : ''; ?>
        </div>
    </div>
    <div class="clear"></div>

    <div class="w-33">
        <div class="ip-col-airfilter">
			<?php echo nl2br( esc_html( $data->airfilter ?? '-' ) ); ?>
        </div>
    </div>
    <div class="w-33">
        <div class="ip-col-features">
			<?php
			if ( is_array( $data->features ) ) {
				echo '<ul>';
				foreach ( $data->features as $feature ) {
					echo '<li>' . esc_html( $feature ) . '</li>';
				}
				echo '</ul>';
			} else {
				echo nl2br( esc_html( $data->features ) );
			}
			?>
        </div>
    </div>
    <div class="w-33">
        <div class="ip-col-filter-price">
			<?php if ( isset( $data->filter_price ) && (int) $data->filter_price > 0 ): ?>
                <?php echo wc_price( $data->filter_price ); ?> rocznie
			<?php else: ?>
                -
			<?php endif; ?>
        </div>
        <div class="ip-col-energy">
			<?php echo isset( $data->energy ) && (int) $data->energy > 0 ? wc_price( $data->energy ) . '/mc' : '-'; ?>
        </div>
    </div>
    <div class="clear"></div>

    <div class="ip-col-cta">
        <a href="<?php esc_html_e( $data->link ); ?>" target="_blank" class="badge<?php echo $data->is_on_sale ? ' sale' : '' ?>">Sprawdź</a>
    </div>

</div>