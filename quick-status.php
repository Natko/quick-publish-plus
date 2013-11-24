<?php
/**
 * Template for displaying Quick Publish status popup
 *
 */
?>

<form id="new-status-popup" autocomplete="off" class="new-post-popup" action="#">
	<a class="close enable" href=""></a>
	<h3><?php echo __('Quick Status', 'quick-publish-plus'); ?></h3>
	<textarea class="new-status" name="new-status" placeholder="<?php echo __('Write your status...', 'quick-publish-plus'); ?>"></textarea>
	<input class="status-title" type="text" placeholder="<?php echo __('Status title...', 'quick-publish-plus'); ?>" value="<?php echo __('Status Update - ', 'quick-publish-plus'); echo date(get_option('date_format')); ?>" data-value="<?php echo __('Status Update - ', 'quick-publish-plus'); echo date(get_option('date_format')); ?>" />
	<a href="" class="submit disable"><?php echo __('Publish', 'quick-publish-plus'); ?></a>
</form>