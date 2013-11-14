<?php
/*
*	Plugin Name: Hero Themes Gallery Manager
*	Plugin URI: http://wordpress.org/extend/plugins/ht-gallery-manager/
*	Description: A Replacement Gallery Manager
*	Author: Hero Themes
*	Version: 1.2.1
*	Author URI: http://www.herothemes.com/
*	Text Domain: ht-gallery-manager
*/


DEFINE( 'HERO_THEMES_REF_LINK','http://www.herothemes.com/?ref=ht_gallery' );


if( !class_exists( 'HT_Gallery_Manager' ) ){
	class HT_Gallery_Manager {
		//constructor
		function __construct(){
			add_action( 'init', array( $this,  'register_ht_gallery_post_cpt' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_hero_gallery_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_hero_gallery' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_ht_gallery_manager_scripts_and_styles' ) );
            add_action( 'media_buttons', array( $this, 'ht_add_form_button'), 20 );
			add_filter( 'media_view_settings', array($this, 'ht_gallery_media_view_settings'), 10, 2 );
			add_shortcode( 'ht_gallery', array( $this , 'ht_gallery_shortcode' ) );
			add_filter( 'get_ht_galleries', array ( $this, 'ht_get_galleries' ) );
			//set the meta key value
			$this->meta_value_key = '_ht_gallery_images';
			$this->starred_meta_value_key = '_ht_gallery_starred_image';
			$this->view_value_key = '_ht_gallery_view';
		}

		public static function get_meta_key_value(){
			return $this->meta_value_key;
		}

		/**
		* Registers the ht_gallery_post custom post type
		*/
		function register_ht_gallery_post_cpt() {
			$singular_item = __('Hero Gallery', 'ht-gallery-manager');
			$plural_item = __('Hero Galleries', 'ht-gallery-manager');
		  	$labels = array(
			    'name'               =>  $singular_item,
			    'singular_name'      => 'Gallery',
			    'add_new'            => __('Add New', 'ht-gallery-manager') . ' ' .  $singular_item,
			    'add_new_item'       => __('Add New', 'ht-gallery-manager') . ' ' .  $singular_item,
			    'edit_item'          => __('Edit', 'ht-gallery-manager') . ' ' .  $singular_item,
			    'new_item'           => __('New', 'ht-gallery-manager') . ' ' .  $singular_item,
			    'all_items'          => __('All', 'ht-gallery-manager') . ' ' .  $plural_item,
			    'view_item'          => __('View', 'ht-gallery-manager') . ' ' .  $singular_item,
			    'search_items'       => __('Search', 'ht-gallery-manager') . ' ' .  $plural_item,
			    'not_found'          => sprintf( __( 'No %s found', 'ht-gallery-manager' ), $plural_item ),
			    'not_found_in_trash' => sprintf( __( 'No %s found in trash', 'ht-gallery-manager' ), $plural_item ),
			    'parent_item_colon'  => '',
			    'menu_name'          => $plural_item,
		  	);

			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => 'herogallery' ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array( 'title', 'author' )
			);

		  register_post_type( 'ht_gallery_post', $args );
		}


		/**
		 * Adds the hero gallery meta box container.
		 */
		public function add_hero_gallery_meta_box() {
			add_meta_box(
				'hero_gallery_meta_shortocode',
				__( 'Hero Gallery Shortcode Information', 'ht-gallery-manager' ),
				array( $this, 'render_hero_gallery_meta_box_shortcode_info' ),
				'ht_gallery_post',
				'normal',
				'high'
				);
			add_meta_box(
				'hero_gallery_meta_main',
				__( 'Hero Gallery Images', 'ht-gallery-manager' ),
				array( $this, 'render_hero_gallery_meta_box_content' ),
				'ht_gallery_post',
				'normal',
				'high'
				);
			//add notice if current theme does not support the hero gallery manager
			if( !current_theme_supports( 'hero-gallery-manager' ) ){
				add_meta_box(
					'hero_gallery_meta_side',
					__( 'Like Hero Themes?', 'ht-gallery-manager' ),
					array( $this, 'render_hero_gallery_side_meta_box_content' ),
					'ht_gallery_post',
					'side',
					'low'
					);
			}
		}

		/**
		 * Save the hero gallery meta when the post is saved.
		 *
		 * @param int $post_id The ID of the post being saved.
		 */
		public function save_hero_gallery( $post_id ) {

			// Check if our nonce is set.
			if ( ! isset( $_POST['ht_gallery_manager_security'] ) )
				return $post_id;

			$nonce = $_POST['ht_gallery_manager_security'];

			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $nonce, 'ht_gallery_manager' ) )
				return $post_id;

			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
				return $post_id;

			// Check the user's permissions.
			if ( 'page' == $_POST['post_type'] ) {

				if ( ! current_user_can( 'edit_page', $post_id ) )
					return $post_id;
		
			} else {

				if ( ! current_user_can( 'edit_post', $post_id ) )
					return $post_id;
			}

			/* Permissions checked - we can now save the post  */

			//update the title if none has been set
			if( get_the_title($post_id)=='' ){
				//remove hook first
				remove_action( 'save_post', array( $this, 'save_hero_gallery' ) );
				$post = array(
							'ID' => $post_id,
							'post_title' => "Hero Gallery " . $post_id
					);
				//update post
				wp_update_post( $post );

				//re-add the save post hook
				add_action( 'save_post', array( $this, 'save_hero_gallery' ) );
			}

			//update the view in the user meta
			$view = sanitize_text_field( $_POST[$this->view_value_key] );
			update_user_meta( get_current_user_id(), $this->view_value_key.$post_id, $view );

			// Sanitize the user input.
			$ht_gallery_items = sanitize_text_field( $_POST[$this->meta_value_key] );
			$ht_gallery_starred_image = sanitize_text_field( $_POST[$this->starred_meta_value_key] );

			// Update the meta field.
			update_post_meta( $post_id, $this->meta_value_key, $ht_gallery_items );

			// Update the starred image.
			update_post_meta( $post_id, $this->starred_meta_value_key, $ht_gallery_starred_image );
		}

		/**
		 * Render Shortcode Info Meta Box content.
		 *
		 * @param WP_Post $post The post object.
		 */
		public function render_hero_gallery_meta_box_shortcode_info( $post ) {
			printf( __( 'To use this Hero Gallery enter the shortcode <b>[ht_gallery id="%s" name="%s"]</b> in your post or page', 'ht-gallery-manager' ), $post->ID, $post->post_title );
		}


		/**
		 * Render Main Hero Gallery Meta Box content.
		 *
		 * @param WP_Post $post The post object.
		 */
		public function render_hero_gallery_meta_box_content( $post ) {
		
			// Add an nonce field so we can check for it later.
			wp_nonce_field( 'ht_gallery_manager', 'ht_gallery_manager_security' );

			// Use get_post_meta to retrieve an existing value from the database.
			$value = get_post_meta( $post->ID, $this->meta_value_key, true );

			//enqueue media
			wp_enqueue_media( $post->ID );

			// Display the gallery, using the current value.
			echo $this->hero_gallery_metabox_html ( $post->ID );
		}
	

		/**
		 * Render Sidebar content for Gallery Meta
		 *
		 * @param WP_Post $post The post object.
		 */
		public function render_hero_gallery_side_meta_box_content( $post ) {
			_e( 'Want even more from your Hero Gallery?', 'hero-gallery-manager' );
			echo '<br/><br/>';
			_e( sprintf( 'Choose a %1$sHero Theme%2$s for even more power.', '<a href="'.HERO_THEMES_REF_LINK.'">', '</a>' ), 'hero-gallery-manager' );
			echo '<br/><br/>';				
			echo '<a href="'.HERO_THEMES_REF_LINK.'" title="Hero Themes" class="button button-primary button-large">';
			_e( 'Learn More', 'hero-gallery-manager' );
			echo '</a>';
		}
	


		/**
		* Display the hero gallery metabox
		*
		* @param int $post_id The id of the current post
		*/
		public function hero_gallery_metabox_html( $post_id ) {	

			$value = get_post_meta( $post_id, $this->meta_value_key, true );
			$starred_image = get_post_meta( $post_id, $this->starred_meta_value_key, true );
			$user_view = get_user_meta( get_current_user_id(), $this->view_value_key.$post_id, true );
			//set the default gallery view
			$gallery_view = !empty( $user_view ) ? $user_view : 'stamps';
			$stamps_view_active = $gallery_view == 'stamps' ? 'active' : '';
			$details_view_active = $gallery_view == 'details' ? 'active' : '';
			?>
			<p class="metabox-links">
			<div id="hero-gallery-manager">
			<input type="hidden" id="ht_gallery_values" name="<?php echo $this->meta_value_key; ?>" value="<?php echo esc_attr( $value ); ?>" size="25" />	
			<input type="hidden" id="ht_gallery_starred_image" name="<?php echo $this->starred_meta_value_key; ?>" value="<?php echo esc_attr( $starred_image ); ?>" size="25" />	
			<input type="hidden" id="ht_gallery_view" name="<?php echo $this->view_value_key; ?>" value="<?php echo esc_attr( $gallery_view ); ?>" size="25" />			

			<!-- top manager bar -->	

			<ul class="ht-gallery-manager-bar top clearfix">	
				<li>
					<a href="#" id=""  class="ht-gallery-add-images button button-secondary" title="<?php _e( 'Add Images', 'ht-gallery-manager' ); ?>"><?php _e( 'Select Images', 'ht-gallery-manager' ); ?></a>
				</li>
				<li>
					<a href="#" id=""  class="ht-gallery-refresh-images button button-secondary" title="<?php _e( 'Refresh Images', 'ht-gallery-manager' ); ?>"><?php _e( 'Refresh Images', 'ht-gallery-manager' ); ?></a>
				</li>
				
				<li class="ht-gallery-manager-views">
					<div class="divider"></div>
					<a href="#" class="ht-gallery-manager-view-button ht-gallery-manager-stamps-view <?php echo $stamps_view_active; ?>" alt="stamps"></a>
					<div class="divider"></div>
					<a href="#" class="ht-gallery-manager-view-button ht-gallery-manager-details-view <?php echo $details_view_active; ?>" alt="detail"></a>
					<div class="divider"></div>
				</li>

				<li class="ht-gallery-manager-gallery-details">
					<div class="ht-gallery-manager-gallery-details-div"><span class="ht-gallery-manager-gallery-details-count">0</span> <?php _e( 'Items in Gallery', 'ht-gallery-manager' ); ?></span>
				</li>
				
			</ul>		

			<!-- empty gallery placeholder -->		

			<div class="ht-gallery-manager-gallery-empty">
					<span><?php _e( 'Gallery Empty', 'ht-gallery-manager' ); ?></span><br/><br/>
					<a href="#" id=""  class="ht-gallery-add-images button button-secondary" title="<?php _e( 'Add Images', 'ht-gallery-manager' ); ?>"><?php _e( 'Click to Add Images', 'ht-gallery-manager' ); ?></a>
			</div>

			<!-- gallery content  -->	

			<ol id="ht-gallery-manager-list" class="clearfix <?php echo $gallery_view; ?>-view">
				<div class="ht-gallery-manager-loading">
					<span class="spinner"><?php _e( 'Loading Images', 'ht-gallery-manager' ); ?>...</span>
				</div>
			</ol>

			<!-- file drop area  -->	

			<div class="ht-drop-files drag-drop" id="ht-file-drop-area">
				<div class="drag-drop-inside" id="drag-drop-inside">
	                	<?php _e( 'Drop files here to add to gallery', 'ht-gallery-manager' ); ?>
	            </div>
			</div>

			<!-- bottom manager bar -->	

			<ul class="ht-gallery-manager-bar bottom clearfix">	
				<li>
					<a href="#" id=""  class="ht-gallery-add-images button button-secondary" title="<?php _e( 'Add Images', 'ht-gallery-manager' ); ?>"><?php _e( 'Select Images', 'ht-gallery-manager' ); ?></a>
				</li>
				<li>
					<a href="#" id=""  class="ht-gallery-refresh-images button button-secondary" title="<?php _e( 'Refresh Images', 'ht-gallery-manager' ); ?>"><?php _e( 'Refresh Images', 'ht-gallery-manager' ); ?></a>
				</li>
				
				<li class="ht-gallery-manager-views">
					<div class="divider"></div>
					<a href="#" class="ht-gallery-manager-view-button ht-gallery-manager-stamps-view active" alt="stamps"></a>
					<div class="divider"></div>
					<a href="#" class="ht-gallery-manager-view-button ht-gallery-manager-details-view" alt="detail"></a>
					<div class="divider"></div>
				</li>

				<li class="ht-gallery-manager-gallery-details">
					<div class="ht-gallery-manager-gallery-details-div"><span class="ht-gallery-manager-gallery-details-count">0</span> <?php _e( 'Items in Gallery', 'ht-gallery-manager' ); ?></span>
				</li>
				
			</ul>	


			</div> <!--hero-gallery-manager-->

			<?php
		}

		/**
		* Enqueue scripts and styles
		*/
		function enqueue_ht_gallery_manager_scripts_and_styles(){
			//localize?
			$screen = get_current_screen();
			if( $screen->post_type == 'ht_gallery_post' && $screen->base == 'post' ) {
				wp_enqueue_script('plupload-all'); 
				wp_enqueue_script( 'ht-gallery-manager-scripts', plugins_url( 'js/ht-gallery-manager-scripts.js', __FILE__ ), array( 'jquery' , 'jquery-effects-core', 'jquery-ui-draggable', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-sortable' ), 1.1, true );
				wp_enqueue_style( 'ht-gallery-manager-style', plugins_url( 'css/ht-gallery-manager-style.css', __FILE__ ));
				wp_localize_script( 'ht-gallery-manager-scripts', 'framework', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
				$this->uploader_localize();
			} else {
			}
		}
	 
	 	/**
	 	* Add gallery shortcode to the media view settings
	 	*
	 	* @param array $settings The settings to filter
	 	* @param WP_Post $post The post that is being rendered
	 	*/
		function ht_gallery_media_view_settings($settings, $post ) {
		    if (!is_object($post)) 
		    	return $settings;

		    $shortcode = 'ht_gallery';
		 
		    $settings['htGallery'] = array('shortcode' => $shortcode); 
		    return $settings;
		}


		/**
		* Hero Gallery shortcode function
		*
		* @param array $attrs The shortcode passed attribute
		* @param array $content The shortcode passed content (this will always be ignored in this context)
		*/
		function ht_gallery_shortcode($atts, $content = null){

			//extract arttributes
			extract(shortcode_atts(array(  
	                'name' => '',
	                'id' => '',
	            ), $atts));


			if( empty($name) && empty($id) ){
				return;
			} else if( !empty($name) && empty($id)  ){
				//id takes precendant over name
				//get gallery post 
				$gallery =  get_page_by_title( $name, 'OBJECT', 'ht_gallery_post' );
				if( $gallery && is_a( $gallery, 'WP_Post' ) ){
					//get the meta
					$gallery_ids = get_post_meta( $gallery->ID, $this->meta_value_key, true );
					if( $gallery_ids && $gallery_ids!='' ){
						return do_shortcode('[gallery ids="' . $gallery_ids . '"]');
					} else {
						return sprintf( __( 'The Hero Gallery with the name %s is empty.', 'hero-gallery-manager' ), $name );
					}

				} else {
					return sprintf( __( 'There is no Hero Gallery with the name %s.', 'hero-gallery-manager' ), $name );
				}
			} else if( !empty($id) ){
				//id takes precendant over name
				//get gallery post 
				$gallery =  get_post( $id );
				if( $gallery && is_a( $gallery, 'WP_Post' ) ){
					//get the meta
					$gallery_ids = get_post_meta( $gallery->ID, $this->meta_value_key, true );
					if( $gallery_ids && $gallery_ids!='' ){
						return do_shortcode('[gallery ids="' . $gallery_ids . '"]');
					} else {
						return sprintf( __( 'The Hero Gallery with the id %s is empty.', 'hero-gallery-manager' ), $id );
					}

				} else {
					return sprintf( __( 'There is no Hero Gallery with the id %s.', 'hero-gallery-manager' ), $id );
				}
			} else {
				return __( 'Could not get Hero Gallery', 'hero-gallery-manager' );
			}
		}


		/**
		* Custom upload box
		*/
		function uploader_localize() {
	          
	        // Localize variables for rw-uploader
	        $ht_plupload_init = array(
	            'runtimes'            => 'html5,silverlight,flash,html4',
	            'browse_button'       => 'plupload-browse-button',
	            'container'           => 'ht-file-drop-area',
	            'drop_element'        => 'drag-drop-inside',
	            'file_data_name'      => 'async-upload',
	            'multiple_queues'     => true,
	            'max_file_size'       => wp_max_upload_size() . 'b',
	            'url'                 => admin_url( 'async-upload.php' ),
	            'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
	            'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
	            'filters'             => array(
	                array(
	                    'title'      => __( 'Allowed Files', 'rw' ),
	                    'extensions' => '*'
	                )
	            ),
	            'multipart'           => true,
	            'urlstream_upload'    => true,
	            'multi_selection'     => false,
	            'multipart_params'    => array(
	                'wpnonce' => wp_create_nonce('media-form'),
	                'action'      => 'photo_gallery_upload',
	                'imgid'       => 0,
	            )
	        );
	        $plupload_init = apply_filters( 'ht_gallery_manager_uploader_init', $ht_plupload_init );
	        wp_localize_script( 'ht-gallery-manager-scripts', 'htGalleryUploaderInit', $ht_plupload_init );

		}

		/**
		* Get current Hero Galleries
		*/
		function ht_get_galleries(){
			$args = array(
				'offset'           => 0,
				'category'         => '',
				'orderby'          => 'post_date',
				'order'            => 'DESC',
				'include'          => '',
				'exclude'          => '',
				'meta_key'         => '',
				'meta_value'       => '',
				'post_type'        => 'ht_gallery_post',
				'post_mime_type'   => '',
				'post_parent'      => '',
				'post_status'      => 'publish',
				'suppress_filters' => true );

			$ht_gallery_posts = get_posts( $args );
			$gallery_names = array();

			foreach( $ht_gallery_posts as $ht_gallery_post ) {
				array_push( $gallery_names, array( 'id' => $ht_gallery_post->ID, 'name' => $ht_gallery_post->post_title ) );
			}

			return $gallery_names;
		}

		/**
		* Add the Hero Gallery button to the post editor
		*/
		function ht_add_form_button(){
			echo '<a href="#TB_inline?width=600&height=550&inlineId=select-hero-gallery-dialog" class="thickbox button" id="add_ht_gallery" title="' . __("Add Hero Gallery", 'hero-gallery-manager') . '"><span class="ht-gallery-media-icon "></span> ' . __("Add Hero Gallery", "hero-gallery-manager") . '</a>';
			add_action( 'admin_footer', array ( $this, 'ht_select_hero_gallery_form' ) );
		}

		/**
		* Displays the Insert a Hero Gallery Selector
		*/
		function ht_select_hero_gallery_form(){
				$this->ht_select_hero_gallery_styles();

			?>
				<div id="select-hero-gallery-dialog" style="display:none">
					<h3><?php _e('Insert a Hero Gallery', 'hero-gallery-manager'); ?></h3>
					<p><?php _e('Add a Hero Gallery to the current post', 'hero-gallery-manager'); ?></p>
			<?php
				$ht_galleries = apply_filters( 'get_ht_galleries', array() );

			?>	
					 
					<select name="ht-gallery-select" id="ht-gallery-select">
			<?php
				foreach ($ht_galleries as $gallery) {

						echo '<option value="' . $gallery['id'] . '">' . $gallery['name']  . '</option>';
				}

			?>
					</select>
					<br/><br/>
					<a href="#" id="insert-ht-gallery" class="button button-primary button-large" onClick="window.send_to_editor( '[ht_gallery id=&quot;' + jQuery('#ht-gallery-select').val() + '&quot; name=&quot;' + jQuery('#ht-gallery-select').children('option').filter(':selected').text() + '&quot;]' ); jQuery('#select-hero-gallery-dialog').fadeOut();">Add</a>
					<a href="#" id="cancel-insert-ht-gallery" class="button  button-large" onClick="window.send_to_editor( '' ); jQuery('#select-hero-gallery-dialog').fadeOut();">Cancel</a>
					 
				</div>
			<?php
		}

		/*
		* Enqueue styles for select box in editor
		*/
		function ht_select_hero_gallery_styles(){
			wp_enqueue_style( 'ht-gallery-selector-style', plugins_url( 'css/ht-gallery-selector-style.css', __FILE__ ));

			//custom styles
            echo '<style>.ht-gallery-media-icon{
                    background:url(' . plugins_url( 'img/ht-gallery-add-btn.png', __FILE__ )  . ') no-repeat top left;
                    display: inline-block;
                    height: 16px;
                    margin: 0 2px 0 0;
                    vertical-align: text-top;
                    width: 16px;
                    }
                 </style>';

		}


	} //end class HT_Gallery_Manager
}//end class exists test


//run the plugin
if( class_exists( 'HT_Gallery_Manager' ) ){
	$ht_gallery_manager_init = new HT_Gallery_Manager();
}