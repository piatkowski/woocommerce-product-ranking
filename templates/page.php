<?php
    namespace IPRanking;
    get_header(); ?>
    <article id="post-<?php the_ID(); ?>" class="ip-ranking-wrapper">
        <h1><?php echo esc_html(Plugin::config('title')) ?></h1>
        <?php Template::get('filter-button'); ?>
	    <?php Template::get('filter', array(
                'range' => Controller::getFilterPriceRange(),
                'max_pages' => ceil(count(Controller::getAllProducts()) / Plugin::config('per_page'))
        )); ?>
	    <?php Template::get('table'); ?>
    </article>
<?php get_footer();
