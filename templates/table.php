<?php
namespace IPRanking;
?>
<div id="idTable">
	<div class="ip-flex" id="ipTableContent">
		<?php Template::get('table-sort'); ?>
        <?php Controller::renderTable(); ?>
	</div>
	<?php Template::get('table-load-more'); ?>
</div>