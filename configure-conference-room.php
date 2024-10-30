<?php
/*
Plugin Name: WP Virtual Room Configurator
Description: Configure your desired virtual room.
Plugin URI: http://www.telberia.com/
Author: codemenschen
Author URI: http://www.codemenschen.at/
Text Domain: wpvrc
Version: 1.0.0
Domain Path: /languages
*/

if( !defined( 'ABSPATH' ) ) exit;  // Exit if accessed directly

define( 'WPCCR_VERSION', '1.0.10' );
define( 'WPCCR__MINIMUM_WP_VERSION', '4.0' );
define( 'WPCCR__PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WPCCR__PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

class configure_conference_room {

    public function __construct() {
    	$CT_TAX_META = new CT_TAX_META();
    	$device_room_meta_box = new device_room_meta_box();
    	$device_meta_box = new device_meta_box();
        add_action('init', array($this,'init_func'));
        add_filter('manage_edit-room_columns', array($this,'room_shortcode_columns'));
    	add_action('manage_room_posts_custom_column', array($this,'room_shortcode_column_value'), 10, 2);
    	add_action('wp_enqueue_scripts', array($this,'plugin_script_func'));
    	add_shortcode('room_device',array($this,'room_device_func'));
	    function load_js_library() {
		    if ( ! wp_script_is( 'jquery', 'enqueued' )) {

			    //Enqueue
			    wp_enqueue_script( 'jquery' );

		    }
		    if ( ! wp_script_is( 'bootstrap.min.js', 'enqueued' )) {

			    //Enqueue
  				wp_register_script('bootstrap.min.js', WPCCR__PLUGIN_URL  . '/assets/js/bootstrap.min.js', array('jquery'), '3.3.7', true);
			    wp_enqueue_script( 'bootstrap.min.js' );

		    }
	    }
	    add_action( 'wp_enqueue_scripts', 'load_js_library' );

    	include_once('multi-post-thumbnails.php');
		new MultiPostThumbnails(array(
			'label' => 'Nacht Bild',
			'id' => 'secondary-image',
			'post_type' => 'ccr_images'
		));
    }
 
    public function init_func(){
    	register_post_type( 'ccr_room',
	        array(
	            'labels' => array(
	                'name' => __( 'Rooms' ),
	                'singular_name' => __( 'Room' ),
					'add_new'  => __( 'Add new room'),
					'add_new_item' => __( 'Add New Room'),
					'edit_item' => __('Edit Room'),
	            ),
	            'public' => true,
	            'has_archive' => true,
	            'rewrite' => array(),
	            'supports' => array(
		            'title',
		            'thumbnail',
		            'editor'
		        ),
		        'show_in_menu' => 'edit.php?post_type=ccr_images'
	        )
	    );

        register_post_type( 'ccr_images',
	        array(
				'label' => __( 'Objects' ),
	            'labels' => array(
	                'name' => __( 'Objects' ),
	                'singular_name' => __( 'Object' ),
	                'add_new'  => __( 'Add New Object'),
					'add_new_item' => __( 'Add new object'),
					'menu_name' => __( 'Room Configurator'),
					'all_items' => __('Objects'),
					'edit_item' => __('Edit Object'),
	            ),
	            'public' => true,
	            'has_archive' => true,
	            'rewrite' => array(),
	            'supports' => array(
		            'title',
		            'thumbnail',
		            'editor'
		        )
	        )
	    );
		$labels = array(
			'name' => _x( 'Object Categories', 'wpvrc' ),
			'singular_name' => _x( 'Object Category', 'wpvrc' ),
			'search_items' =>  __( 'Search Category' ),
			'all_items' => __( 'Object Categories' ),
			'parent_item' => __( 'Parent Category' ),
			'menu_name' => __( 'Object Categories' ),
		);    
	    register_taxonomy('images_type',array('ccr_images'), array(
	    	'label' => __( 'Object Categories' ),
		    'hierarchical' => true,
		    'labels' => $labels,
		    'show_ui' => true,
		    'show_admin_column' => true,
		    'query_var' => true,
		    'rewrite' => array(),
		));
    }

    function room_shortcode_columns($columns) {
    	$date = $columns['date'];
    	unset($columns['date']);
	    $columns["shortcode"] = "Shortcode";
	    $columns['date'] = $date;
	    return $columns;
	}

	function room_shortcode_column_value( $colname, $cptid ) {
	    if ($colname == 'shortcode'){
	        echo '[room_device id="'.$cptid.'"]';
	    }
	}

	function plugin_script_func(){
		wp_enqueue_style('style-room-device', plugins_url( '/assets/css/style.css', __FILE__ ));
	}

	function room_device_func($atts){
		extract(shortcode_atts(array(
	        'id' => 0
	    ), $atts));
	    $_post  = get_post($id);
		ob_start();
		?>
		<?php if(@$_post->ID != null): ?>
		<?php
			$terms = get_terms(array(
				'taxonomy' => 'ccr_room',
				'hide_empty' => false,
				'orderby' => 'name',
				'order' => 'ASC'
			));
			$device_type = json_decode(get_post_meta($_post->ID,'_device_type_meta_key',true),true);
			$devices = json_decode(get_post_meta($_post->ID,'_device_meta_key',true),true);
			if($device_type == null){
				return '';
			}

			$results = array();
			foreach ($device_type as $key => $term_id) {
				$category_order = get_term_meta($term_id, 'category-order',true);
				$results[$category_order]['current'] = get_term_by('id',$term_id,'images_type');
				foreach ($devices as $key => $device_id) {
					$terms = wp_get_post_terms($device_id,'images_type');
					foreach ($terms as $key => $term) {
						if($term->term_id == $term_id){
							$results[$category_order]['children'][] = get_post($device_id);
						}
					}
				}
			}				
			ksort($results, SORT_NUMERIC);
		?>
		<div class="configure-block">
			<!--<h2><?php //echo $_post->post_title; ?></h2>-->
			<div class="configure-box">
			
				<div id="room-box" class="room-box">
					<?php if(has_post_thumbnail($_post->ID)): ?>
						<img src="<?php echo get_the_post_thumbnail_url($_post->ID,'full'); ?>" class="main-image main-image-day">
						<?php $image_id = get_post_meta($_post->ID,'featured_image_night', true); ?>
						<?php if($image_id): ?>
							<?php $image = wp_get_attachment_image_src($image_id,'full'); ?>
							<img src="<?php echo @$image[0]; ?>" class="main-image main-image-night">
						<?php endif; ?>
					<?php endif; ?>
					<?php foreach ($results as $key => $item): ?>
						<?php foreach ($results[$key]['children'] as $key1 => $device): ?>
						<?php 
							$z_index = $key + $key1 + 6;
							$z_index = get_post_meta($device->ID,'device_order', true) ? get_post_meta($device->ID,'device_order', true) : $z_index;
						?>
							<?php if(has_post_thumbnail($device->ID)): ?>
								<div class="device-room device-room-<?php echo $device->ID; ?>" style="z-index: <?php echo $z_index; ?>;">
									<img src="<?php echo get_the_post_thumbnail_url($device->ID,'full'); ?>" class="img-day">
									<?php if(MultiPostThumbnails::has_post_thumbnail($device->post_type, 'secondary-image',$device->ID)): ?>
										<?php MultiPostThumbnails::the_post_thumbnail($device->post_type, 'secondary-image',$device->ID,'full',array('class' => 'img-night')); ?>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</div>
				<div class="devices-box">
					<ul class="device-list" id="device-list">
						<?php foreach ($results as $key => $item): ?>
							<li>
								<h4><span class="cat-device-title"><?php echo @$item['current']->name; ?></span><a class="visible-xs button device-arrow" href="javascript:void(0)" onClick="toggleDeviceList('device-inner-list-<?php echo $item['current']->term_id;?>',this)"><i class="arrow-down"></i></a></h4>
								
								<?php if(isset($results[$key]['children']) && $results[$key]['children'] != null): ?>
									<ul id="device-inner-list-<?php echo $item['current']->term_id;?>" class="device-innerlist nav nav-list collapse" data-type="<?php echo get_term_meta($item['current']->term_id, 'category-type',true) != 'radio' ? 'checkbox' : 'radio'; ?>">
										<?php foreach ($results[$key]['children'] as $key1 => $device): ?>
										   <li class="nav-header" data-toggle="collapse" data-target="#device-inner-list-<?php echo $item['current']->term_id;?>"><span class="device-item device-item-<?php echo $device->ID; ?>" data-id="<?php echo $device->ID; ?>" data_is_day_night="<?php echo get_post_meta($device->ID,'is_day_night', true) == 1 ? 'true' : 'false'; ?>"><?php echo $device->post_title; ?></span></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
			
<script type="text/javascript">
				
var specialElementHandlers = {
    '#editor': function (element, renderer) {
        return true;
    }
};

function toggleDeviceList(objectId,currentObj) {
	jQuery("#"+objectId).collapse('toggle');
	if(jQuery('i',currentObj).hasClass('arrow-down')) {
				jQuery("#"+objectId).css({"display":"block"});
				jQuery('i',currentObj).addClass('arrow-up').removeClass('arrow-down');
            } else {
				jQuery("#"+objectId).css({"display":"none"});
                jQuery('i',currentObj).removeClass('arrow-up').addClass('arrow-down');
            }
}

</script>
<script type="text/javascript">

jQuery(document).ready(function($){
		 
	$(".wpcf7-form .wpcf7-submit").click(function () {
		$("#selected-devices").val("");
		$(".device-item").each (function (){
			if ($(this).hasClass("active"))
				$("#selected-devices").val($("#selected-devices").val() + "[" + $(this).html() + "]");
		}

		);
		selected_devices = $("#selected-devices").val().replace(/\]\[/g,", ");
		selected_devices = selected_devices.replace("[","");
		selected_devices = selected_devices.replace("]","");
		$("#selected-devices").val(selected_devices);
		$("#current-url").val($(location).attr('href'));
		}
	);
	if (document.documentElement.clientWidth > 992) {
		$('.device-innerlist').collapse('show');
		// scripts
	}
	if (document.documentElement.clientWidth <= 480) {
		$('.device-innerlist').css({"display":"none"});
	}
					
	$('.configure-box .devices-box .device-innerlist .device-item').click(function(){
		var term_id = $(this).attr('data-id');
		if($(this).hasClass('active')){
			if($(this).attr('data_is_day_night') == 'true'){
				var check = true;
				$(".configure-box .devices-box .device-innerlist .device-item[data_is_day_night='true']").not(this).each(function(){
					if($(this).hasClass('active')){
						check = false;
					}
				});
				if(check){
					$(".configure-box .devices-box .device-innerlist .device-item[data-id='14308']").removeClass('active');
					$('.configure-box .room-box .device-room-14308').fadeOut('slow');
					$('.configure-box .room-box .main-image-day').show();
					$('.configure-box .room-box .main-image-night').hide();
					$('.configure-box .room-box .device-room .img-day').show();
					$('.configure-box .room-box .device-room .img-night').hide();																		
					$('.configure-box .room-box .device-room-' + term_id).fadeOut('slow');
				}																	
			}
			else{
				$('.configure-box .room-box .device-room-' + term_id).fadeOut('slow');
			}
			$(this).removeClass('active');
			
			
		}
		else{
			if($(this).attr('data_is_day_night') == 'true'){
				$(".configure-box .devices-box .device-innerlist .device-item[data_is_day_night='true']").not(this).each(function(){
					if($(this).hasClass('active')){
						$(this).removeClass('active');
					}
				});
				$(".configure-box .devices-box .device-innerlist .device-item[data-id='14308']").addClass('active');
				$('.configure-box .room-box .device-room-14308').fadeIn('slow');
				$('.configure-box .room-box .main-image-day').hide();
				$('.configure-box .room-box .main-image-night').show();
				$('.configure-box .room-box .device-room .img-day').hide();
				$('.configure-box .room-box .device-room .img-night').show();
				if (term_id=='14305')									
					$('.configure-box .room-box .device-room-' + term_id).fadeIn('slow');
				else
					$('.configure-box .room-box .device-room-14305').fadeOut('slow');
			}
			else{
				if($(this).parents('.device-innerlist').attr('data-type') == 'radio'){
					$(this).parents('.device-innerlist').find('.device-item').each(function(){
						var item_id = $(this).attr('data-id');
						$(this).removeClass('active');
						$('.configure-box .room-box .device-room-' + item_id).fadeOut('slow');
					});
				}
				if (term_id=='14315') {
					$('.configure-box .room-box .device-room-14318').fadeOut('slow');
					$('.device-innerlist .device-item-14318').removeClass('active');
					}								
				if (term_id=='14318')	{									
					$('.device-innerlist .device-item-14315').removeClass('active');
					$('.configure-box .room-box .device-room-14315').fadeOut('slow');
					}
				$('.configure-box .room-box .device-room-' + term_id).fadeIn('slow');
			}
			$(this).addClass('active');
		}
		return false;
	});
});
</script>
		<?php endif; ?>
<?php
		return ob_get_clean();
	}

}

class device_room_meta_box
{
	public function __construct() {
		add_action('admin_enqueue_scripts', array( $this, 'load_media'));
		add_action('add_meta_boxes',array($this,'add'));
		add_action('save_post',array($this,'save'));
	}

    public function add()
    {
        $screens = ['ccr_room'];
        foreach ($screens as $screen) {
            add_meta_box(
                'wporg_box_id',// Unique ID
                'Attitude', // Box title
                array($this, 'html'),   // Content callback, must be of type callable
                $screen // Post type
            );
        }
    }
 
    public function save($post_id)
    {	
    	$post = get_post($post_id);
    	if($post->post_type != 'ccr_room'){
    		return;
    	}
    	$device_type = $_POST['device_type'];
        if (isset($device_type) && $device_type != null) {
            update_post_meta(
                $post_id,
                '_device_type_meta_key',
	            sanitize_text_field(json_encode($device_type))
            );
        }
        else{
        	delete_post_meta($post_id, '_device_type_meta_key', '');
        }
        $input_device = $_POST['device'];
        if (isset($input_device) && $input_device != null) {
            update_post_meta(
                $post_id,
                '_device_meta_key',
	            sanitize_text_field(json_encode($input_device))
            );
        }
        else{
        	delete_post_meta($post_id, '_device_meta_key', '');
        }
        $input_featured_image_night =  $_POST['featured_image_night'];
        if (isset($input_featured_image_night) && $input_featured_image_night != null) {
            update_post_meta(
                $post_id,
                'featured_image_night',
	            sanitize_text_field($input_featured_image_night)
            );
        }
        else{
        	delete_post_meta($post_id, 'featured_image_night', '');
        }
    }
 
    public function html($post)
    {
    	if($post->post_type != 'ccr_room'){
    		return;
    	}
        $device_type = get_post_meta($post->ID, '_device_type_meta_key', true);
        $device = get_post_meta($post->ID, '_device_meta_key', true);
        $image_id = get_post_meta($post->ID,'featured_image_night', true);
        $terms = get_terms(array(
		    'taxonomy' => 'ccr_images_type',
		    'hide_empty' => false,
		    'parent' => 0,
		    'orderby' => 'name',
    		'order' => 'ASC'
		));
		$args = array(
			'post_type' => 'ccr_images',
			'posts_per_page' => -1
		);
		global $post;
		$query = new WP_Query($args);
		?>
	        <label style="display: block;margin-bottom: 5px;">Object Categories:</label>
	        <?php 
	        	$device_type = json_decode($device_type,true);
	        	if($device_type == null){
	        		$device_type = array();
	        	}
	        ?>
	        <select name="device_type[]" class="multil-select" multiple>
	        	<?php foreach($terms as $key => $term): ?>
		            <option <?php echo in_array($term->term_id, $device_type) ? 'selected' : ''; ?> value="<?php echo $term->term_id; ?>"><?php echo @$term->name; ?></option>
	        		<?php $term_children = get_term_children($term->term_id,'ccr_images_type'); ?>
	        		<?php foreach($term_children as $key => $term_child_id): ?>
	        			<?php $term_child = get_term_by('id',$term_child_id,'ccr_images_type'); ?>
	        			<option <?php echo in_array($term_child->term_id, $device_type) ? 'selected' : ''; ?> value="<?php echo $term_child->term_id; ?>">--<?php echo @$term_child->name; ?></option>
	        		<?php endforeach; ?>
	        	<?php endforeach; ?>
	        </select>
	        <div style="height: 30px;"></div>
	        <label style="display: block;margin-bottom: 5px;">Object:</label>
	        <?php 
	        	$device = json_decode($device,true); 
	        	if($device == null){
	        		$device = array();
	        	}
	        ?>
	        <select name="device[]" class="multil-select" multiple>
	        	<?php while($query->have_posts()): $query->the_post();?>
		            <option <?php echo in_array($post->ID, $device) ? 'selected' : ''; ?>  value="<?php echo $post->ID; ?>"><?php echo get_the_title(); ?></option>
	        	<?php endwhile; wp_reset_query(); ?>
	        </select>
	        <script type="text/javascript">
	        	jQuery(document).ready(function($){
	        		$('.multil-select').multiSelect();
	        	});
	        </script>
	        <div style="height: 30px;"></div>
	       	<label style="display: block;margin-bottom: 5px;">Night Picture:</label>
	       	<input type="hidden" name="featured_image_night" value="<?php echo $image_id; ?>">
	       	<div id="category-image-wrapper" style="margin-bottom: 10px;">
		        <?php if($image_id) { ?>
		           <?php echo wp_get_attachment_image($image_id,'thumbnail'); ?>
		        <?php } ?>
	       	</div>
	       	<p>
	         	<input type="button" class="button button-secondary ct_tax_media_button" id="ct_tax_media_button" name="ct_tax_media_button" value="<?php _e( 'Add Picture', 'wpvrc' ); ?>" />
	         	<input type="button" class="button button-secondary ct_tax_media_remove" id="ct_tax_media_remove" name="ct_tax_media_remove" value="<?php _e( 'Delete Picture', 'wpvrc' ); ?>" />
	       	</p>
        <?php
    }

	public function load_media() {
		wp_enqueue_media();
		wp_enqueue_style('style-room-device', plugins_url( '/assets/css/style.css', __FILE__ ));
		wp_register_script('multil-select-custom',plugins_url( '/assets/js/jquery.multi-select.js' , __FILE__ ),array('jquery'), '1.0');
        wp_enqueue_script('multil-select-custom');
	}
}

class device_meta_box
{
	public function __construct() {
		add_action('add_meta_boxes',array($this,'add'));
		add_action('save_post',array($this,'save'));
	}

    public function add()
    {
        $screens = ['ccr_images'];
        foreach ($screens as $screen) {
            add_meta_box(
                'images_box_id',// Unique ID
                'Attitude', // Box title
                array($this, 'html'),   // Content callback, must be of type callable
                $screen // Post type
            );
        }
    }
 
    public function save($post_id)
    {	
    	$post = get_post($post_id);
    	if($post->post_type != 'ccr_images'){
    		return;
    	}
    	$is_day_night = $_POST['is_day_night'];
        if (isset($is_day_night) && $is_day_night != null) {
            update_post_meta(
                $post_id,
                'is_day_night',
	            sanitize_text_field($is_day_night)
            );
        }
        else{
        	delete_post_meta($post_id, 'is_day_night', '');
        }
        $device_order = $_POST['device_order'];
		if (isset($device_order) && $device_order != null) {
            update_post_meta(
                $post_id,
                'device_order',
	            sanitize_text_field($device_order)
            );
        }
        else{
        	delete_post_meta($post_id, 'device_order', '');
        }
    }
 
    public function html($post)
    {
    	if($post->post_type != 'ccr_images'){
    		return;
    	}
        $is_day_night = get_post_meta($post->ID, 'is_day_night', true);
		$device_order = get_post_meta($post->ID, 'device_order', true);
		?>
			<div class="row <?php echo $device_order;?>" style="padding-bottom:20px;">
				<label style="margin-bottom: 5px;padding-right:10px;">Exterior Blackout:</label>
				<input type="checkbox" <?php echo $is_day_night == 1 ? 'checked' : ''; ?> name="is_day_night" value="1">
			</div>
			<div class="row">
				<label style="margin-bottom: 5px;padding-right:10px;">Assignment:</label>
				<input type="text" name="device_order" value="<?php echo $device_order ?>">
			</div>
        <?php
    }
}

if ( ! class_exists( 'CT_TAX_META' ) ) {
	class CT_TAX_META {
	  	public function __construct() {
	    	add_action( 'images_type_add_form_fields', array ( $this, 'add_category_image' ), 10, 2 );
		   	add_action( 'created_images_type', array ( $this, 'save_category_image' ), 10, 2 );
		   	add_action( 'images_type_edit_form_fields', array ( $this, 'update_category_image' ), 10, 2 );
		   	add_action( 'edited_images_type', array ( $this, 'updated_category_image' ), 10, 2 );
		   	add_action( 'admin_enqueue_scripts', array( $this, 'load_media'));
		   	add_action( 'admin_footer', array($this, 'add_script'));
	  	}

		public function load_media() {
		 	wp_enqueue_media();
		}
	 
		/*
		  * Add a form field in the new category page
		  * @since 1.0.0
		*/
		public function add_category_image( $taxonomy ) { 
			?>
				<div class="form-field term-group">
				    <label for="category-order"><?php _e('Order', 'plugin'); ?></label>
				    <input type="text" name="category-order" value="" />
				    	
			   	</div>
				<div class="form-field term-group">
				    <label for="category-type"><?php _e('Type', 'plugin'); ?></label>
				    <select name="category-type">
				    	<option value="checkbox">Checkbox</option>
				    	<option value="radio">Radio</option>
				    </select>
			   	</div>
			   	<div class="form-field term-group">
				    <label for="category-image-id"><?php _e('Image', 'wpvrc'); ?></label>
				    <input type="hidden" id="category-image-id" name="category-image-id" class="custom_media_url" value="">
				    <div id="category-image-wrapper" style="margin-bottom: 10px;"></div>
				    <p>
				       <input type="button" class="button button-secondary ct_tax_media_button" id="ct_tax_media_button" name="ct_tax_media_button" value="<?php _e( 'Add Picture', 'wpvrc' ); ?>" />
				       <input type="button" class="button button-secondary ct_tax_media_remove" id="ct_tax_media_remove" name="ct_tax_media_remove" value="<?php _e( 'Delete Picture', 'wpvrc' ); ?>" />
				    </p>
			   	</div>
		 	<?php
		}
	 
		/*
		  * Save the form field
		  * @since 1.0.0
		*/
		public function save_category_image( $term_id, $tt_id ) {
			
			if( isset( $_POST['category-order'] ) && '' !== $_POST['category-order'] ){
		     	$category_order = sanitize_text_field($_POST['category-order']);
		     	add_term_meta($term_id, 'category-order',$category_order, true );
		   	}

		  	if( isset( $_POST['category-type'] ) && '' !== $_POST['category-type'] ){
		     	$type = sanitize_text_field($_POST['category-type']);
		     	add_term_meta($term_id, 'category-type',$type, true );
		   	}

		  	if( isset( $_POST['category-image-id'] ) && '' !== $_POST['category-image-id'] ){
		     	$image = sanitize_text_field($_POST['category-image-id']);
		     	add_term_meta( $term_id, 'category-image-id', $image, true );
		   	}
		}
	 
		/*
		  * Edit the form field
		  * @since 1.0.0
		*/
		public function update_category_image( $term, $taxonomy ) { 
			?>
				<tr class="form-field term-group-wrap">
				    <th scope="row">
				       <label for="category-order"><?php _e( 'Order', 'wpvrc' ); ?></label>
				    </th>
				    <td>
				       	<?php $category_order = get_term_meta($term->term_id, 'category-order', true ); 
						?>
				       	<input type="text" name="category-order" value="<?php echo $category_order; ?>"/>
					</td>
			  	</tr>
				<tr class="form-field term-group-wrap">
				    <th scope="row">
				       <label for="category-type"><?php _e( 'Type', 'wpvrc' ); ?></label>
				    </th>
				    <td>
				       	<?php $type = get_term_meta($term->term_id, 'category-type', true ); ?>
				       	<select name="category-type">
					    	<option value="checkbox">Checkbox</option>
					    	<option <?php echo $type == 'radio' ? 'selected' : ''; ?> value="radio">Radio</option>
					    </select>
				    </td>
			  	</tr>
			   	<tr class="form-field term-group-wrap">
				    <th scope="row">
				       <label for="category-image-id"><?php _e( 'Image', 'wpvrc' ); ?></label>
				    </th>
				    <td>
				       	<?php $image_id = get_term_meta($term->term_id, 'category-image-id', true ); ?>
				       	<input type="hidden" id="category-image-id" name="category-image-id" value="<?php echo $image_id; ?>">
				       	<div id="category-image-wrapper" style="margin-bottom: 10px;">
					        <?php if($image_id) { ?>
					           <?php echo wp_get_attachment_image($image_id,'thumbnail'); ?>
					        <?php } ?>
				       	</div>
				       	<p>
				         	<input type="button" class="button button-secondary ct_tax_media_button" id="ct_tax_media_button" name="ct_tax_media_button" value="<?php _e( 'Add Picture', 'wpvrc' ); ?>" />
				         	<input type="button" class="button button-secondary ct_tax_media_remove" id="ct_tax_media_remove" name="ct_tax_media_remove" value="<?php _e( 'Delete Picture', 'wpvrc' ); ?>" />
				       	</p>
				    </td>
			  	</tr>
		 	<?php
		}

		/*
		 * Update the form field value
		 * @since 1.0.0
		*/
		public function updated_category_image( $term_id, $tt_id ) {

		   	if( isset( $_POST['category-type'] ) && '' !== $_POST['category-type'] ){
		     	$type = sanitize_text_field($_POST['category-type']);
		     	update_term_meta($term_id, 'category-type',$type);
		   	} else {
		     	update_term_meta ($term_id,'category-type','');
		   	}
			
			if( isset( $_POST['category-order'] ) && '' !== $_POST['category-order'] ){
		     	$category_order = sanitize_text_field($_POST['category-order']);
		     	update_term_meta($term_id, 'category-order',$category_order);
		   	} else {
		     	update_term_meta ($term_id,'category-order','');
		   	}

		   	if( isset( $_POST['category-image-id'] ) && '' !== $_POST['category-image-id'] ){
		     	$image = sanitize_text_field($_POST['category-image-id']);
		     	update_term_meta($term_id, 'category-image-id',$image);
		   	} else {
		     	update_term_meta ($term_id,'category-image-id','');
		   	}
		}

		/*
		 * Add script
		 * @since 1.0.0
		*/
		public function add_script() { 
			?>


			   	<script type="text/javascript">
				
var specialElementHandlers = {
    '#editor': function (element, renderer) {
        return true;
    }
};


				    jQuery(document).ready( function($) {
				       	function ct_media_upload(button_class) {
					        var _custom_media = true,
					        _orig_send_attachment = wp.media.editor.send.attachment;
						    $('body').on('click', button_class, function(e) {
					           	var button_id = '#'+$(this).attr('id');
					           	var send_attachment_bkp = wp.media.editor.send.attachment;
					           	var button = $(button_id);
					          	 _custom_media = true;
					           	wp.media.editor.send.attachment = function(props, attachment){
					             	if (_custom_media ) {
						               	$('#category-image-id').val(attachment.id);
						               	$('input[name="featured_image_night"]').val(attachment.id);
						               	$('#category-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
						               	$('#category-image-wrapper .custom_media_image').attr('src',attachment.url).css('display','block');
					             	} else {
					               		return _orig_send_attachment.apply( button_id, [props, attachment] );
					             	}
					            }
						        wp.media.editor.open(button);
					         	return false;
					       	});
				     	}
				     	ct_media_upload('.ct_tax_media_button.button'); 
					    $('body').on('click','.ct_tax_media_remove',function(){
					       	$('#category-image-id').val('');
					       	$('#category-image-wrapper').html('<img class="custom_media_image" src="" style="margin:0;padding:0;max-height:100px;float:none;" />');
					    });
					    // Thanks: http://stackoverflow.com/questions/15281995/wordpress-create-category-ajax-response
					    $(document).ajaxComplete(function(event, xhr, settings) {
					       	var queryStringArr = settings.data.split('&');
					       	if( $.inArray('action=add-tag', queryStringArr) !== -1 ){
						        var xml = xhr.responseXML;
						        $response = $(xml).find('term_id').text();
						        if($response!=""){
						           // Clear the thumb image
						           $('#category-image-wrapper').html('');
						        }
					       	}
					    });
				   	});
					
			 	</script>
			<?php 
		}
	}
}
$wpdocsclass = new configure_conference_room();