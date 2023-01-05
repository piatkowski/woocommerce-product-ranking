<div class="ip-flex-row details ajax">
    <div class="desc">
        <h3><?php esc_html_e( $data->title ); ?></h3>
        <div class="badges">
			<?php
			if ( isset( $data->home ) ) {
				echo '<span class="badge">DO DOMU</span>';
			}
			if ( isset( $data->office ) ) {
				echo '<span class="badge">DO BIURA</span>';
			}
			if ( isset( $data->allergy ) ) {
				echo '<span class="badge">ALERGICY</span>';
			}
			if ( isset( $data->humid ) ) {
				echo '<span class="badge">NAWILŻACZ</span>';
			}
			if ( isset( $data->ions ) ) {
				echo '<span class="badge">JONIZACJA</span>';
			}
			if ( isset( $data->app ) ) {
				echo '<span class="badge">APLIKACJA MOBILNA</span>';
			}
			?>


        </div>

        <p><?php esc_html_e( $data->desc ); ?></p>
        <div>
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
                <a href="<?php esc_html_e( $data->link ); ?>" class="badge"><span>Zobacz produkt</span></a>
            </div>
        </div>
    </div>
    <div class="product_image">
        <img
                src="<?php esc_html_e( $data->image ); ?>"
                loading="lazy"
                alt="<?php esc_html_e( $data->title ); ?>">
    </div>
</div>