<?php 
/*
Plugin Name: Quick Publish Plus
Plugin URI: http://natko.com
Description: Publish statuses or images by simply dragging them from another tab and then dropping to the "drop" icon in the admin menu.
Author: Natko Hasić
Author URI: http://natko.com
Version: 1.0
*/

class QuickPublishPlus{

	/////////////////////////////////////////////////////////////
	// Plugin constructor
	/////////////////////////////////////////////////////////////

	public function __construct() {

		// Load plugin text domain
		load_plugin_textdomain( 'quick-publish-plus', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// Include scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'quick_publish_include_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'quick_publish_include_scripts' ) );

		// Create the admin toolbar for image publishing
		add_action( 'admin_bar_menu', array( $this, 'quick_publish_image_menu' ), 65);
		
		// AJAX actions
		add_action( 'wp_ajax_quick_publish_image', array( $this, 'quick_publish_image' ) );
		add_action( 'wp_ajax_quick_publish_status', array( $this, 'quick_publish_status' ) );

		// Create the hidden form markup in footer
		add_action( 'wp_footer', array( $this, 'quick_publish_form' ));
		add_action( 'admin_footer', array( $this, 'quick_publish_form' ));

	}

	/////////////////////////////////////////////////////////////
	// Include all scripts and styles
	/////////////////////////////////////////////////////////////

	public function quick_publish_include_scripts(){

		// Check if the user can publish posts
		if(!current_user_can('publish_posts')){
			return 0;
		}

		// Include JS
		wp_register_script('quick-publish-script', plugins_url( '/script/quick-publish-plus.js', __FILE__ ), array('jquery') ); 
		wp_enqueue_script('quick-publish-script');

		// Include CSS
		wp_register_style('quick-publish-style', plugins_url( '/style/quick-publish-plus.css', __FILE__ ), null, null, 'all' ); 
		wp_enqueue_style('quick-publish-style');

		// Create ajaxurl and nonce
		wp_localize_script( 'quick-publish-script', 'qp_ajax', array( 
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'quick-publish-nonce-check' ),
			)
		);

	}

	/////////////////////////////////////////////////////////////
	// Publish status
	/////////////////////////////////////////////////////////////

	public function quick_publish_status(){

		// Check the nonce
		check_ajax_referer( 'quick-publish-nonce-check', 'security' );

		// Get post info
		$post_author = get_current_user_id();
		$post_title = wp_strip_all_tags($_POST['post_title']);
		$post_content = $_POST['post_content'];
		$return_html = $_POST['return_html'];

		$args = array(
			'post_title'    => $post_title,
			'post_content'  => $post_content,
			'post_status'   => 'publish',
			'post_author'   => $post_author,
		);

		// Insert the post and change format to 'status'
		$post_id = wp_insert_post( $args );
		set_post_format($post_id, 'status' );

		// If the user is on the blog loop return the post HTML
		if($return_html == 'true'){
			$post_html = $this->get_quick_publish_post($post_id);
			echo json_encode(array( 'showPost' => true, 'postHTML' => $post_html ));
		} else {
			echo json_encode(array( 'showPost' => false ));
		}

		die();

	}

	/////////////////////////////////////////////////////////////
	// Create the admin toolbar for image publishing
	/////////////////////////////////////////////////////////////

	public function quick_publish_image_menu( $wp_admin_bar ) {

		global $wp_admin_bar;

		$args = array(
			'id' => 'quick-image-publish',
			'title' => '<span class="ab-icon" title="'.__('Drop an image here from another tab', 'quick-publish-plus') .'"></span>',
			'meta' => array(
				'class' => 'quick-image-publish'
			)
		);

		if (current_user_can('publish_posts')){
			$wp_admin_bar->add_node($args);
		}
	}

	/////////////////////////////////////////////////////////////
	// Create the hidden form markup in footer
	/////////////////////////////////////////////////////////////

	public function quick_publish_form() { 

		if (current_user_can('publish_posts')){ 
			include_once( 'quick-status.php' );
			include_once( 'quick-image.php' );
		}

	}

	/////////////////////////////////////////////////////////////
	// Upload image and publish the post
	/////////////////////////////////////////////////////////////

	public function quick_publish_image(){

		check_ajax_referer( 'quick-publish-nonce-check', 'security' );

		$post_title = wp_strip_all_tags($_POST['post_title']);
		$post_excerpt = $_POST['post_excerpt'];
		$post_author = get_current_user_id();
		$post_category = $_POST['post_category'];
		$image_url = $_POST['image_url'];
		$return_html = $_POST['return_html'];

		$upload_dir = wp_upload_dir();
		$image_data = file_get_contents($image_url);

		$filename = basename($image_url);

		if(wp_mkdir_p($upload_dir['path'])){
			$file = $upload_dir['path'] . '/' . $filename;
		} else{
			$file = $upload_dir['basedir'] . '/' . $filename;
		}

		$filename_add = '1';
		$info = pathinfo($file);
		$file_check = $file;
		while (file_exists($file_check)) {
			$filename_add++;
			$file_check = $info['dirname'] . '/' . $info['filename'] . $filename_add . '.' . $info['extension'];
		}
		$file = $file_check;

		file_put_contents($file, $image_data);

		$wp_filetype = wp_check_filetype($filename, null );

		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => sanitize_file_name($info['filename']),
			'post_content' => '',
			'post_status' => 'inherit'
		);

		$my_post = array(
			'post_title'    => $post_title,
			'post_excerpt'  => $post_excerpt,
			'post_status'   => 'publish',
			'post_author'   => $post_author,
		);

		if (current_user_can('publish_posts')){
			$post_id = wp_insert_post( $my_post );
		} else {
			die();
		}

		set_post_format($post_id, 'image' );

		wp_set_post_terms( $post_id, $post_category, 'category' );

		$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
		if ( is_wp_error($attach_id) ){
			$error = $attach_id->get_error_message();
			echo json_encode(array( 'errorInfo' => $error ));
		} else {
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
			wp_update_attachment_metadata( $attach_id, $attach_data );

			set_post_thumbnail( $post_id, $attach_id );

		}

		// If the user is on the blog loop return the post HTML
		if($return_html == 'true'){
			$post_html = $this->get_quick_publish_post($post_id);
			echo json_encode(array( 'showPost' => true, 'postHTML' => $post_html ));
		} else {
			echo json_encode(array( 'showPost' => false ));
		}

		die();

	}

	/////////////////////////////////////////////////////////////
	// Return post HTML
	/////////////////////////////////////////////////////////////

	public function get_quick_publish_post($post_id){
		$query = new WP_Query('p='.$post_id.'');

		ob_start();
			if($query->have_posts()) : while($query->have_posts()) : $query->the_post();
				get_template_part( 'content', get_post_format() );
			endwhile; endif;
			$post_html = ob_get_contents();
		ob_end_clean();

		return $post_html;
	}

}

new QuickPublishPlus();

?>