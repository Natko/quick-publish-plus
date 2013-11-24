<?php
/**
 * Template for displaying Quick Publish image popup
 *
 */
?>

<form id="new-image-popup" class="new-post-popup" action="#">
	<a class="close enable" href=""></a>
	<h3><?php echo __('Quick Image', 'quick-publish-plus'); ?></h3>
	<img class="preview-image" src="#" alt="preview image" />
	<div>
		<input autocomplete="off" class="image-title" type="text" placeholder="<?php echo __('Image title...', 'quick-publish-plus'); ?>" value="" />
		<input autocomplete="off" class="image-excerpt" type="text" placeholder="<?php echo __('Image excerpt (optional)...', 'quick-publish-plus'); ?>" value="" />
	</div>
	<select name="categories" class="select-category">
		<?php 
			$categories = get_categories();
			$default_category_ID = get_option('default_category');
			$default_category_name = get_cat_name($default_category_ID);
			foreach ($categories as $category) {
				echo '<option value="'.$category->cat_ID.'"';
				if($default_category_ID == $category->cat_ID){
					echo ' selected="selected"';
				}
				echo '>'.$category->cat_name.'</option>';
			}
		?>
	</select>
	<a href="" class="submit disable"><?php echo __('Publish', 'quick-publish-plus'); ?></a>
</form>