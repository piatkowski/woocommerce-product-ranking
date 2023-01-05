<div class="ip-flex-row ajax">
    <div class="ip-flex-col ip-col-number">
        <span class="ip-row-nr"></span>
    </div>
    <div class="ip-flex-col ip-col-image">
        <img
                src="<?php esc_html_e( $data->thumbnail ); ?>"
                loading="lazy"
                alt="<?php esc_html_e( $data->title ); ?>">
    </div>
    <div class="ip-flex-col ip-col-title">
		<?php esc_html_e( $data->title ); ?>
        <div class="ip-icons">
			<?php
			if ( isset( $data->home ) ) {
				echo '<span class="ip-icon ip-icon-do-domu"></span>';
			}
			if ( isset( $data->office ) ) {
				echo '<span class="ip-icon ip-icon-do-pracy"></span>';
			}
			if ( isset( $data->allergy ) ) {
				echo '<span class="ip-icon ip-icon-alergicy"></span>';
			}
			?>
        </div>
    </div>
    <div class="ip-flex-col ip-col-price">
		<?php if ( $data->is_on_sale ) : ?>
            <span class="striked"><?php echo strip_tags( wc_price( $data->regular_price ) ); ?></span>
            <span><?php echo strip_tags( wc_price( $data->price ) ); ?></span>
		<?php else: ?>
            <span><?php echo strip_tags( wc_price( $data->price ) ); ?></span>
		<?php endif; ?>
    </div>
    <div class="ip-flex-col ip-col-reviews">
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
    <div class="ip-flex-col ip-col-cta">
        <a href="<?php esc_html_e( $data->link ); ?>" target="_blank"><span class="badge">Sprawdź</span></a><br/>
		<?php if ( $data->is_on_sale ) : ?>
            <a href="<?php esc_html_e( $data->link ); ?>" target="_blank">
                <span class="badge red">Promocja</span>
            </a>
		<?php endif; ?>
    </div>
    <div class="ip-flex-col ip-col-airflow">
		<?php esc_html_e( $data->airflow ?? '-' ); ?>
		<?php echo isset( $data->airflow ) ? ' m<sup>3</sup>/h' : ''; ?>
    </div>
    <div class="ip-flex-col ip-col-humid">
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
    <div class="ip-flex-col ip-col-area">
		<?php echo wp_kses_post( $data->area ?? '-' ); ?>
		<?php echo ( (int) $data->area > 0 ) ? ' m<sup>2</sup>' : ''; ?>
    </div>
    <div class="ip-flex-col ip-col-noise">
		<?php esc_html_e( $data->noise ?? '-' ); ?>
		<?php echo ( (int) $data->noise > 0 ) ? ' dB' : ''; ?>
    </div>
    <div class="ip-flex-col ip-col-airfilter">
		<?php echo nl2br( esc_html( $data->airfilter ?? '-' ) ); ?>
    </div>
    <div class="ip-flex-col ip-col-features">

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
    <div class="ip-flex-col ip-col-filter-price">
		<?php if ( isset( $data->filter_price ) && (int) $data->filter_price > 0 ): ?>
            <?php echo wc_price( $data->filter_price ); ?> rocznie
		<?php else: ?>
            -
		<?php endif; ?>
    </div>
    <div class="ip-flex-col ip-col-energy">
		<?php echo isset( $data->energy ) && (int) $data->energy > 0 ? wc_price( $data->energy ) . '/mc' : '-'; ?>
    </div>
</div>