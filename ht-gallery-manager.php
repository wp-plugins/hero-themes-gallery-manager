<?php
/*
*	Plugin Name: Heroic Gallery Manager
*	Plugin URI: http://wordpress.org/extend/plugins/ht-gallery-manager/
*	Description: A Drag and Drop Gallery Manager for WordPress
*	Author: Hero Themes
*	Version: 1.21
*	Author URI: http://www.herothemes.com/
*	Text Domain: ht-gallery-manager
*/


DEFINE( 'HERO_THEMES_REF_LINK','http://www.herothemes.com/?ref=ht_gallery' );
DEFINE( 'HT_GALLERY_META_KEY_VALUE', '_ht_gallery_images' );
DEFINE( 'HT_GALLERY_STARRED_META_KEY_VALUE', '_ht_gallery_starred_image' );
DEFINE( 'HT_GALLERY_VIEW_META_KEY_VALUE', '_ht_gallery_view' );
DEFINE( 'HT_GALLERY_VIDEO_URL_META_KEY_VALUE', '_ht_gallery_video_url' );
DEFINE( 'HT_GALLERY_VIDEO_URL', 'ht_gallery_video_url_features' );


if( !class_exists( 'HT_Gallery_Manager' ) ){
	class HT_Gallery_Manager {
		
		//constructor
		function __construct(){
			load_plugin_textdomain('ht-gallery-manager', false, basename( dirname( __FILE__ ) ) . '/languages' );
			
			add_action( 'init', array( $this,  'register_ht_gallery_post_cpt' ) );
			add_action( 'init', array( $this,  'register_ht_gallery_category_taxonomy' ) );
	
			add_action( 'add_meta_boxes', array( $this, 'add_hero_gallery_meta_box' ) );
			add_action( 'save_post', array( $this, 'save_hero_gallery' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_ht_gallery_manager_scripts_and_styles' ) );
            add_action( 'media_buttons', array( $this, 'ht_add_form_button'), 20 );
            add_action( 'wp_ajax_save_ht_gallery_order', array( $this, 'save_ht_gallery_menu_order_ajax' ) );
            add_action( 'wp_ajax_save_ht_gallery_images', array( $this, 'save_ht_gallery_images_ajax' ) );
            add_action( 'pre_get_posts', array( $this, 'show_all_gallery_posts' ) );

            //custom action to save video url
            remove_action('wp_ajax_save-attachment', 'wp_ajax_save_attachment', 1);
            add_action( 'wp_ajax_save-attachment', array( $this, 'ht_gallery_custom_save_attachment' ), 1 );
            //get video urls
            add_action( 'wp_ajax_get-video-urls', array( $this, 'ht_gallery_get_video_urls_ajax' ));
            
			add_filter( 'media_view_settings', array($this, 'ht_gallery_media_view_settings'), 10, 2 );
			add_shortcode( 'ht_gallery', array( $this , 'ht_gallery_shortcode' ) );
			add_filter( 'manage_ht_gallery_post_posts_columns', array( $this, 'ht_gallery_columns'), 10, 1 );
			add_filter( 'manage_ht_gallery_post_posts_custom_column', array( $this, 'ht_gallery_custom_column'), 10, 2 );
			add_filter( 'get_ht_galleries', array( $this, 'ht_get_galleries' ) );

			//add to menu items
			add_action( 'admin_head-nav-menus.php', array( $this, 'ht_gallery_menu_metabox' ) );
			add_filter( 'wp_get_nav_menu_items', array( $this,'ht_gallery_archive_menu_filter'), 10, 3 );

			//activation hook
			register_activation_hook(__FILE__, array( $this, 'ht_gallery_flush_rules' ) );

			//set the meta key value
			$this->meta_value_key = HT_GALLERY_META_KEY_VALUE;
			$this->starred_meta_value_key = HT_GALLERY_STARRED_META_KEY_VALUE;
			$this->view_value_key = HT_GALLERY_VIEW_META_KEY_VALUE;

			
			

			include_once('php/ht-gallery-manager-settings.php');
			include_once('php/ht-gallery-renderers.php');
			include_once('php/ht-gallery-widgets.php');

		}




		public static function get_meta_key_value(){
			return $this->meta_value_key;
		}


		/**
		* Flush the rewrite rules - run when plugin is activated
		*/
		function ht_gallery_flush_rules(){
			//defines the post type and taxonomy so the rules can be flushed.
			$this->register_ht_gallery_post_cpt();
			$this->register_ht_gallery_category_taxonomy();

			//and flush the rules.
			flush_rewrite_rules();
		}

		/**
		* Registers the ht_gallery_post category taxonomy
		*/
		function register_ht_gallery_category_taxonomy()  {

			$labels = array(
				'name'                       => _x( 'Heroic Gallery Category', 'Taxonomy General Name', 'ht-gallery-manager' ),
				'singular_name'              => _x( 'Heroic Gallery Category', 'Taxonomy Singular Name', 'ht-gallery-manager' ),
				'menu_name'                  => __( 'Heroic Gallery Categories', 'ht-gallery-manager' ),
				'all_items'                  => __( 'All Heroic Gallery Categories', 'ht-gallery-manager' ),
				'parent_item'                => __( 'Parent Heroic Gallery Category', 'ht-gallery-manager' ),
				'parent_item_colon'          => __( 'Parent Heroic Gallery Category:', 'ht-gallery-manager' ),
				'new_item_name'              => __( 'New Heroic Gallery Category', 'ht-gallery-manager' ),
				'add_new_item'               => __( 'Add New Heroic Gallery Category', 'ht-gallery-manager' ),
				'edit_item'                  => __( 'Edit Heroic Gallery Category', 'ht-gallery-manager' ),
				'update_item'                => __( 'Update Heroic Gallery Category', 'ht-gallery-manager' ),
				'separate_items_with_commas' => __( 'Separate Heroic Gallery Categories with commas', 'ht-gallery-manager' ),
				'search_items'               => __( 'Search Heroic Gallery Categories', 'ht-gallery-manager' ),
				'add_or_remove_items'        => __( 'Add or remove categories', 'ht-gallery-manager' ),
				'choose_from_most_used'      => __( 'Choose from the most used categories', 'ht-gallery-manager' ),
			);
			$args = array(
				'labels'                     => $labels,
				'hierarchical'               => true,
				'public'                     => true,
				'show_ui'                    => true,
				'rewrite'            		 => array( 'slug' => $this->get_default_category_slug() ),
				'show_admin_column'          => true,
				'show_in_nav_menus'          => true,
				'show_tagcloud'              => true,
				'exclude_from_search'        => true,
			);
			register_taxonomy( 'ht_gallery_category', 'ht_gallery_post', $args );

		}

		/**
		* Registers the ht_gallery_post custom post type
		*/
		function register_ht_gallery_post_cpt() {
			$singular_item = __('Heroic Gallery', 'ht-gallery-manager');
			$plural_item = __('Heroic Galleries', 'ht-gallery-manager');
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

			//check if theme supports comments
			if(current_theme_supports('mute_ht_gallery_comments')){
				$supports = array( 'title', 'editor', 'page-attributes' );
			} else {
				$supports = array( 'title', 'editor', 'page-attributes', 'comments' );
			}


			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => $this->get_default_post_slug() ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => $supports
			);

		  register_post_type( 'ht_gallery_post', $args );
		}

		/**
		* Get the default post slug
		*/
		public function get_default_post_slug(){
			$gallery_options_array = get_option( 'ht_gallery_manager_options' );
			$default_slug = 'herogallery';
			if( $gallery_options_array ){
				if( is_array($gallery_options_array) && array_key_exists( 'post_slug', $gallery_options_array ) ){
					$user_defined_slug = $gallery_options_array['post_slug'];
					if($user_defined_slug!=''){
						$default_slug = $user_defined_slug;
					}
				}
			}
			return $default_slug;
		}

		/**
		* Get the default category slug
		*/
		public function get_default_category_slug(){
			$gallery_options_array = get_option( 'ht_gallery_manager_options' );
			$default_slug = 'herogalleries';
			if( $gallery_options_array ){
				if( is_array($gallery_options_array) && array_key_exists( 'category_slug', $gallery_options_array ) ){
					$user_defined_slug = $gallery_options_array['category_slug'];
					if($user_defined_slug!=''){
						$default_slug = $user_defined_slug;
					}
				}
			}
			return $default_slug;
		}

		/**
		* Get the default post limit
		*/
		public function get_ht_post_limit(){
			$gallery_options_array = get_option( 'ht_gallery_manager_options' );
			$default_limit = '100000';
			if( $gallery_options_array ){
				if( is_array($gallery_options_array) && array_key_exists( 'ht_posts_limit_value', $gallery_options_array ) ){
					$user_defined_post_limit = $gallery_options_array['ht_posts_limit_value'];
					if($user_defined_post_limit!=''){
						$default_slug = $user_defined_post_limit;
					}
				}
			}
			return $default_limit;
		}



		/**
		 * Adds the hero gallery meta box container
		 */
		public function add_hero_gallery_meta_box() {
			global $_wp_post_type_features;
			if (isset($_wp_post_type_features['ht_gallery_post']['editor']) && $_wp_post_type_features['ht_gallery_post']['editor']) {
				unset($_wp_post_type_features['ht_gallery_post']['editor']);
				add_meta_box(
					'description_section',
					__('Gallery Description', 'ht-gallery-manager'),
					array( $this, 'inner_editor_box' ),
					'ht_gallery_post', 'normal', 'low'
				);
			}

			//post excerpt
			add_meta_box('postexcerpt', __( 'Gallery Excerpt', 'ht-gallery-manager' ), 'post_excerpt_meta_box', 'ht_gallery_post', 'normal', 'low');

			add_meta_box(
				'hero_gallery_meta_shortcode',
				__( 'Heroic Gallery Shortcode Information', 'ht-gallery-manager' ),
				array( $this, 'render_hero_gallery_meta_box_shortcode_info' ),
				'ht_gallery_post',
				'normal',
				'high'
				);
			add_meta_box(
				'hero_gallery_meta_main',
				__( 'Heroic Gallery Images', 'ht-gallery-manager' ),
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
		* The inner custom post box for the editor - requires content as name.
		*
		* @param $post The Post object.
		*/
		function inner_editor_box( $post ) {
			wp_editor( $post->post_content, 'content' );
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
							'post_title' => "Heroic Gallery " . $post_id
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
			printf( __( 'To use this Heroic Gallery enter the shortcode <b>[ht_gallery id="%s" name="%s"]</b> in your post or page or use the Insert Heroic Gallery button.', 'ht-gallery-manager' ), $post->ID, $post->post_title );
			echo '<br/><br/>';
			if(current_theme_supports('hero-gallery-manager'))
				printf( __( 'As you are using a theme that supports the Heroic Gallery Manager, you only need to use this shortcode to display images inline in posts and can link directly to this %sHeroic Gallery%s.', 'ht-gallery-manager' ), '<a href="'.get_permalink($post->ID).'">', '</a>'  );
			else
				printf( __( 'The current theme does not support Heroic Gallery Manager, you will need to use this shortcode to display galleries in posts.', 'ht-gallery-manager' ) );
		}


		/**
		 * Render Main Heroic Gallery Meta Box content.
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
			_e( 'Want even more from your Heroic Gallery?', 'hero-gallery-manager' );
			echo '<br/><br/>';
			_e( sprintf( 'Choose a %1$sHero Theme%2$s for even more power.', '<a href="'.HERO_THEMES_REF_LINK.'">', '</a>' ), 'hero-gallery-manager' );

			echo '<br/><br/>';				
			echo '<a href="' . HERO_THEMES_REF_LINK . '" title="Hero Themes" class="button button-primary button-large">';
			_e( 'Learn More', 'hero-gallery-manager' );
			echo '</a>';
		}
	


		/**
		* Display the Heroic Gallery metabox
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
					<a href="#" id=""  class="ht-gallery-add-images button button-secondary" title="<?php _e( 'Add Images', 'ht-gallery-manager' ); ?>"><?php _e( 'Add Images', 'ht-gallery-manager' ); ?></a>
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
					<a href="#" id="ht-select-files"  class="ht-gallery-add-images button button-secondary" title="<?php _e( 'Add Images', 'ht-gallery-manager' ); ?>"><?php _e( 'Select Images', 'ht-gallery-manager' ); ?></a>
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
					<a href="#" id=""  class="ht-gallery-add-images button button-secondary" title="<?php _e( 'Add Images', 'ht-gallery-manager' ); ?>"><?php _e( 'Add Images', 'ht-gallery-manager' ); ?></a>
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
			global $wp_version;
			//localize?
			$screen = get_current_screen();

			//use new uploader
			$pl2 = $wp_version >= 3.9 ? true : false;

			if( $screen->post_type == 'ht_gallery_post' && $screen->base == 'post' ) {
				wp_enqueue_script('plupload-all'); 
				wp_enqueue_script( 'ht-gallery-manager-scripts', plugins_url( 'js/ht-gallery-manager-scripts.js', __FILE__ ), array( 'jquery' , 'jquery-effects-core', 'jquery-ui-draggable', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-sortable', 'plupload' ), 1.21, true );
				wp_enqueue_style( 'ht-gallery-manager-style', plugins_url( 'css/ht-gallery-manager-style.css', __FILE__ ));
				$localization_array = array( 
					'ajaxurl' 	=> admin_url( 'admin-ajax.php' ), 
					'ajaxnonce' => wp_create_nonce('ht-ajax-nonce'),
					'title' 	=> __('Title', 'ht_gallery_manager'),
					'caption' 	=> __('Caption', 'ht_gallery_manager'),
					'alt' 		=> __('Alternative Text', 'ht_gallery_manager'),
					'pl2'		=> $pl2,
					'description' => __('Description', 'ht_gallery_manager'),
					'url'		 => __('Video URL (YouTube or Vimeo)', 'ht_gallery_manager'),
					'not_valid_url' => __('appears not be a valid YouTube or Vimeo URL', 'ht_gallery_manager'),
					'video_url_support'		=> current_theme_supports(HT_GALLERY_VIDEO_URL));
				wp_localize_script( 'ht-gallery-manager-scripts', 'framework', $localization_array );
				$this->uploader_localize();
			} else if( $screen->post_type == 'ht_gallery_post' && $screen->base == 'edit' ) {
				wp_enqueue_script( 'ht-gallery-sorter-scripts', plugins_url( 'js/ht-gallery-sorter-scripts.js', __FILE__ ), array( 'jquery' , 'jquery-effects-core', 'jquery-ui-draggable', 'jquery-ui-widget', 'jquery-ui-mouse', 'jquery-ui-sortable' ), 1.0, true );
				$localization_array = array( 
					'ajaxurl' => admin_url( 'admin-ajax.php' ), 
					'ajaxnonce' => wp_create_nonce('ht-ajax-nonce') );
				wp_localize_script( 'ht-gallery-sorter-scripts', 'framework', $localization_array );
				wp_enqueue_style( 'ht-gallery-sorter-style', plugins_url( 'css/ht-gallery-sorter-style.css', __FILE__ ));

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
		* Heroic Gallery shortcode function
		*
		* @param array $attrs The shortcode passed attribute
		* @param array $content The shortcode passed content (this will always be ignored in this context)
		*/
		function ht_gallery_shortcode($atts, $content = null){

			//extract arttributes
			extract(shortcode_atts(array(  
	                'name' => '',
	                'id' => '',
	                'columns' => '',
	                'link' => '',
	            ), $atts));

			$columns_string = !empty($columns) ? 'columns="' . $columns . '"' : '';
			$link_string = !empty($link) ? 'link="' . $link. '"' : 'link="file"';

			if( empty($name) && empty($id) ){
				return;
			} if( !empty($columns) && !is_numeric($columns) ){
				return  __( 'Columns value is not a number'.$columns, 'hero-gallery-manager' );
			} else if( !empty($name) && empty($id)  ){
				//id takes precendant over name
				//get gallery post 
				$gallery =  get_page_by_title( $name, 'OBJECT', 'ht_gallery_post' );
				if( $gallery && is_a( $gallery, 'WP_Post' ) ){
					//get the meta
					$gallery_ids = get_post_meta( $gallery->ID, $this->meta_value_key, true );
					if( $gallery_ids && $gallery_ids!='' ){
						
						return do_shortcode('[gallery ids="' . $gallery_ids . '" '.$columns_string.' '.$link_string.']');
					} else {
						return sprintf( __( 'The Heroic Gallery with the name %s is empty.', 'hero-gallery-manager' ), $name );
					}

				} else {
					return sprintf( __( 'There is no Heroic Gallery with the name %s.', 'hero-gallery-manager' ), $name );
				}
			} else if( !empty($id) ){
				//id takes precendant over name
				//get gallery post 
				$gallery =  get_post( $id );
				if( $gallery && is_a( $gallery, 'WP_Post' ) ){
					//get the meta
					$gallery_ids = get_post_meta( $gallery->ID, $this->meta_value_key, true );
					if( $gallery_ids && $gallery_ids!='' ){
						return do_shortcode('[gallery ids="' . $gallery_ids . '" '.$columns_string.' '.$link_string.']');
					} else {
						return sprintf( __( 'The Heroic Gallery with the id %s is empty.', 'hero-gallery-manager' ), $id );
					}

				} else {
					return sprintf( __( 'There is no Heroic Gallery with the id %s.', 'hero-gallery-manager' ), $id );
				}
			} else {
				return __( 'Could not get Heroic Gallery', 'hero-gallery-manager' );
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
	                    'title'      => __( 'Image Files', 'ht-gallery-manager' ),
	                    'extensions' => 'jpg,gif,png'
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
			add_filter( 'posts_where', array($this, 'get_ht_galleries_filter') ); 
			$args = array(
				'offset'           => 0,
				'category'         => '',
				'orderby'          => 'post_date',
				'order'            => 'DESC',
				'posts_per_page'   => -1,
				'include'          => '',
				'exclude'          => '',
				'meta_key'         => '',
				'meta_value'       => '',
				'post_type'        => 'ht_gallery_post',
				'post_mime_type'   => '',
				'post_parent'      => '',
				'post_status'      => 'publish',
				'suppress_filters' => false );

			$ht_gallery_posts = get_posts( $args );
			$gallery_names = array();

			foreach( $ht_gallery_posts as $ht_gallery_post ) {
				array_push( $gallery_names, array( 'id' => $ht_gallery_post->ID, 'name' => $ht_gallery_post->post_title ) );
			}

			remove_filter( 'posts_where', array($this, 'get_ht_galleries_filter') );

			return $gallery_names;
		}

		/**
		* A filter to overcome the WP limitation of get_posts args not working on both post_type and post_status simulataneously
		* @param $where The where clause to modify
		* @return $where The modified where clause
		*/
		function get_ht_galleries_filter($where = ''){
			global $wpdb;
 			//include private galleries in get galleries
		    $where .= $wpdb->prepare( ' OR ( post_status = %s AND post_type = %s )', 'private', 'ht_gallery_post' );
		 
		    return $where;
		}

		/**
		* Add the Heroic Gallery button to the post editor
		*/
		function ht_add_form_button(){
			$page = is_admin() ? get_current_screen() : null;

			if( $page == null || ( isset($page) && $page->id!='ht_gallery_post'  ) ){
				echo '<a href="#TB_inline?width=600&height=550&inlineId=select-hero-gallery-dialog" class="thickbox button" id="add_ht_gallery" title="' . __("Add Heroic Gallery", 'hero-gallery-manager') . '"><span class="ht-gallery-media-icon "></span> ' . __("Add Heroic Gallery", "hero-gallery-manager") . '</a>';
				add_action( 'admin_footer', array ( $this, 'ht_select_hero_gallery_form' ) );
			}		
		}

		/**
		* Displays the Insert a Heroic Gallery Selector
		*/
		function ht_select_hero_gallery_form(){
				$this->ht_select_hero_gallery_scripts_and_styles();

			?>
				<div id="select-hero-gallery-dialog" style="display:none">
					<h3><?php _e('Insert a Heroic Gallery', 'hero-gallery-manager'); ?></h3>
					<p><?php _e('Add a Heroic Gallery to the current post', 'hero-gallery-manager'); ?></p>
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
					<select name="ht-gallery-columns-select" id="ht-gallery-columns-select">
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3" selected="selected">3</option>
						<option value="4">4</option>
						<option value="5">5</option>
						<option value="6">6</option>
						<option value="7">7</option>
					</select>
					<span><?php _e('Columns', 'ht-gallery-manager'); ?></span>
					<br/><br/>
			<?php
				foreach ($ht_galleries as $gallery) {
						$gallery_id = $gallery['id'];
						echo '<div class="ht-gallery-select-preview" id="ht-gallery-select-preview-' . $gallery_id . '" data-gallery-id=' . $gallery_id . '>';
						//echo '<p>' . __('Preview' , 'ht-gallery-manager' ) . '</p>';
						$img = HT_Gallery_Manager::get_starred_image_src( $gallery_id );
						if( $img ){
							echo '<img src="' . $img[0] . '" width="' . $img[1] . '" height="' . $img[1] . '" />';
						} else {
							
						}
						echo '<div class="Image Count">' . sprintf( __('%d Images in Gallery' , 'ht-gallery-manager' ), HT_Gallery_Manager::get_hero_gallery_image_count( $gallery_id )  ). '</div>';
						echo '</div> <!-- ht-gallery-select-preview -->';
				}

			?>		
					<a href="#" id="insert-ht-gallery" class="button button-primary button-large" onClick="htSelectHTGallery(); return false;">Add</a>
					<a href="#" id="cancel-insert-ht-gallery" class="button  button-large" onClick="cancelSelectHTGallery(); return false;">Cancel</a>
					 
				</div>
			<?php
		}

		/*
		* Enqueue scripts styles for select box in editor
		*/
		function ht_select_hero_gallery_scripts_and_styles(){
			wp_enqueue_script( 'ht-gallery-selector-scripts', plugins_url( 'js/ht-gallery-selector-scripts.js', __FILE__ ), array( 'jquery' ), 1.0, true );

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

		/**
		* Modify the loop query on the backend to display all the Hero Galleries on one page.
		* On the front-end exclude hidden posts from the category view
		*
		* @param $query The WordPress query
		*/
		function show_all_gallery_posts($query) {
		    if(function_exists('get_current_screen'))
		    	$screen = get_current_screen();
		    
			if( is_admin() && $screen && $screen->post_type == 'ht_gallery_post' && $screen->base == 'edit' ) {
		        //-1 doesn't work here, need to use large int
		        $post_limit = $this->get_ht_post_limit();
		        $query->set('posts_per_page', $post_limit);
		        $query->set('orderby', 'menu_order');
		        $query->set('order', 'ASC');

		        return $query;
		    } else if( ( !is_single() && array_key_exists('post_type', $query->query_vars) && $query->query_vars['post_type'] == 'ht_gallery_post' ) ){
		    	//if is not single and post type = 'ht_gallery_post' CPT archive
		    	//exclude hidden posts
		    	$query->set( 'post_status', array( 'publish' ) );
		    } else if( array_key_exists('taxonomy', $query->query_vars) && $query->query_vars['taxonomy'] == 'ht_gallery_category' ) {
		    	//exclude from the taxonomy in related items
		    	//exclude hidden posts
		    	$query->set( 'post_status', array( 'publish' ) );
		    } else if( is_tax('ht_gallery_category')  ) {
		    	//exlude from the taxonomy archive
		    	//exclude hidden posts
		    	$query->set( 'post_status', array( 'publish' ) );
		    }


		}

		/**
		* Add the additional columns to the custom post type listing
		*
		* @param $columns The initial columns passed from the filter
		*/
		function ht_gallery_columns($columns){
			$id_columns = array(
				'id' => __('ID', 'ht-gallery-manager')
			);
			$preview_columns = array(
				'prev' => __('Preview', 'ht-gallery-manager')
			);
			$order_columns = array(
				'order' => __('Order', 'ht-gallery-manager')
			);
	    	//return array_merge(array_slice($columns, 0, 1), $preview_columns, array_slice($columns, 1), $order_columns);
	    	return array_merge($columns, $id_columns, $preview_columns, $order_columns);
		}

		/**
		* Display custom values for the column in custom post type listing
		*
		* @param $column Column name
		* @param $post_id The post id (though this appears not to get passed correct, use global $post instead)
		*/
		function ht_gallery_custom_column( $column, $post_id ) {
			global $post;
		    switch ( $column ) {
		    	case 'id' :
		            echo '<div class="ht-gallery post-id">' . $post_id . '</div>';
		            break;
		        case 'order' :
		            echo '<div class="ht-gallery post-order" data-post-id="' . $post->ID . '" data-menu-order="' . $post->menu_order . '">' . $post->menu_order . '</div>';
		            break;
		        case 'prev':
		        	echo HT_Gallery_Manager::get_starred_image_thumbnail( $post->ID, array(40, 40) );
		        	break;
		    }
		}

		/**
		* AJAX function to save the menu order of the galleries in the custom post type listing
		*/
		function save_ht_gallery_menu_order_ajax(){
			$ht_order_array =  $_POST['gallery_order'];

			foreach ($ht_order_array as $key => $current_gallery) {
				//filter
				$post_id = intval($current_gallery['postID']);
				$menu_order = intval($current_gallery['menuOrder']);
				$post_update = array(
						'ID' => $post_id,
						'menu_order' => $menu_order
					);
				wp_update_post( $post_update );
			}

			echo json_encode('updated gallery order sucessfully');

			die(); // this is required to return a proper result
		}

		/**
		* AJAX function to save gallery images 
		*/
		function save_ht_gallery_images_ajax(){

			///start here
			$images = sanitize_text_field( $_POST['images'] );
			$starred = sanitize_text_field( $_POST['starred'] );
			$post_id = intval( $_POST['post_id'] );

			//security check
			check_ajax_referer( 'ht-ajax-nonce', 'security' );

			if($post_id>0){
				update_post_meta( $post_id, $this->meta_value_key, $images);
				update_post_meta( $post_id, $this->starred_meta_value_key, $starred);
				echo json_encode('updated gallery images sucessfully');
			}

			die(); // this is required to return a proper result
		}

		/**
		* Get the attachment id of the starred image (or first image from a set) for a given id
		*
		* @param $post_id The post id of the Heroic Gallery
		*/
		public static function get_starred_image($gallery_post_id=null){
			$starred_image = '';

			$gallery_post_id = empty($gallery_post_id) ? get_the_ID() : $gallery_post_id;

			//get the post meta for starred image
			$starred_image = get_post_meta( $gallery_post_id, HT_GALLERY_STARRED_META_KEY_VALUE, true );

			//if no starred image set use the first image in the gallery
			if( $starred_image == ''){
				$gallery_ids = get_post_meta( $gallery_post_id, HT_GALLERY_META_KEY_VALUE, true );
				$gallery_array = ( !is_a('WP_Error', $gallery_ids) || $gallery_ids != '' ) ? explode( ',', $gallery_ids ) : array();
				if( count( $gallery_array ) > 0 ){
					$starred_image = $gallery_array[0];
				}
			}

			return $starred_image;
		}

		/**
		* Get the starred image - uses wp_get_attachment_image
		*
		* @param $gallery_post_id The Post ID of the gallery
		* @param $size The size required, either a string or Array(x, y) (Default 'thumbnail') 
		* @return an HTML img element or empty string on failure.
		*/
		public static function get_starred_image_thumbnail($gallery_post_id=null, $size = 'thumbnail'){
			$gallery_post_id = empty($gallery_post_id) ? get_the_ID() : $gallery_post_id;
			return wp_get_attachment_image( HT_Gallery_Manager::get_starred_image( $gallery_post_id ) , $size  );
		}

		/**
		* Get the starred image src - uses wp_get_attachment_image_src
		*
		* @param $gallery_post_id The Post ID of the gallery
		* @param $size The size required, either a string or Array(x, y) (Default 'thumbnail') 
		* @return array [0] => url, [1] => width, [2] => height, [3] => boolean: true if $url is a resized image, false if it is the original.
		*/
		public static function get_starred_image_src($gallery_post_id=null, $size = 'thumbnail'){
			$gallery_post_id = empty($gallery_post_id) ? get_the_ID() : $gallery_post_id;
			return wp_get_attachment_image_src( HT_Gallery_Manager::get_starred_image( $gallery_post_id ) , $size  );
		}

		/**
		* Get the number of images in a Heroic Gallery
		*
		* @param $gallery_post_id The Post ID of the gallery
		* @return int Count of items in gallery
		*/
		public static function get_hero_gallery_image_count($gallery_post_id=null){
			$gallery_post_id = empty($gallery_post_id) ? get_the_ID() : $gallery_post_id;
			$gallery_ids = get_post_meta( $gallery_post_id, HT_GALLERY_META_KEY_VALUE, true );
			$gallery_array = $gallery_ids != '' ? explode( ',', $gallery_ids ) : array();
			return count( $gallery_array );
		}

		/**
		* Get the number of images in a Heroic Gallery
		*
		* @param $gallery_post_id The Post ID of the gallery
		* @return an array of images
		*/
		public static function get_hero_gallery_images($gallery_post_id=null){
			$gallery_post_id = empty($gallery_post_id) ? get_the_ID() : $gallery_post_id;
			$ht_gallery = get_post_meta( $gallery_post_id, HT_GALLERY_META_KEY_VALUE, true );
			$ht_gallery = is_a($ht_gallery, 'WP_Error') ? array() : explode(",", $ht_gallery);
			return $ht_gallery;
		}

		/**
		* Get the related post by taxonomy
		*
		* @param $post_id The ID of the post
		* @param $taxonomy The taxonomy to query
		* @param $no_of_items The number of related posts to get
		* @return $query The WP query results
		*/
		public static function ht_gallery_get_posts_related_by_taxonomy($post_id, $taxonomy = 'ht_gallery_category', $no_of_items = 4) {
	        $query = new WP_Query();
	        $terms = wp_get_object_terms($post_id, $taxonomy);
		        if (count($terms) > 0) {
		        // Assumes only one term for per post in this taxonomy
		        $post_ids = get_objects_in_term($terms[0]->term_id,$taxonomy);
		        $post = get_post($post_id);
				$args = '';
		        $args = wp_parse_args($args,array(
		            'post_type' => $post->post_type, // The assumes the post types match
		            'post__not_in' => array($post_id),
		            'taxonomy' => $taxonomy,
		            'term' => $terms[0]->slug,
		            'orderby' => 'rand',
		            'posts_per_page' => $no_of_items
		        ));
		        $query = new WP_Query($args);
	        }
	        return $query;
	    }




		/**
		* Adds the HT Gallery Menu Metabox
		*/
		function ht_gallery_menu_metabox() {
	    	add_meta_box( 'add_ht_gallery_menu_item', __('Heroic Galleries Archive', 'ht-gallery-manager'), array( $this, 'ht_gallery_menu_metabox_content' ), 'nav-menus', 'side', 'default' );
	  	}
		
		/**
		* Adds the HT Gallery Menu Metabox Content
		*/
		function ht_gallery_menu_metabox_content() {
	    	
	    	// Create menu items and store IDs in array
			$item_ids = array();
			$post_type = 'ht_gallery_post';
			$post_type_obj = get_post_type_object( $post_type );

			if( ! $post_type_obj )
				continue;

			//add menu data
			$menu_item_data = array(
				 'menu-item-title'  => esc_attr( $post_type_obj->labels->name ),
				 'menu-item-type'   => $post_type,
				 'menu-item-object' => esc_attr( $post_type ),
				 'menu-item-url'    => get_post_type_archive_link( $post_type )
			);

			// add the menu item
			$item_ids[] = wp_update_nav_menu_item( 0, 0, $menu_item_data );

			// Die on error
			is_wp_error( $item_ids ) AND die( '-1' );

			// Set up the menu items
			foreach ( (array) $item_ids as $menu_item_id ) {
				$menu_obj = get_post( $menu_item_id );
				if ( ! empty( $menu_obj->ID ) ) {
					$menu_obj->classes = array();
					$menu_obj->label = __('Heroic Galleries Archive', 'ht-gallery-manager');
			        $menu_obj->object_id = $menu_obj->ID;
			        $menu_obj->object = 'ht-gallery-archive';						
					$menu_items[] = $menu_obj;

				}
			}

		    $menus = array_map('wp_setup_nav_menu_item', $menu_items);
			$walker = new Walker_Nav_Menu_Checklist( array() );
	
			echo '<div id="ht-gallery-post-archive" class="posttypediv">';
			echo '<div id="tabs-panel-ht-gallery-post-archive" class="tabs-panel tabs-panel-active">';
			echo '<ul id="ctp-archive-checklist" class="categorychecklist form-no-clear">';
			echo walk_nav_menu_tree( $menus, 0, (object) array( 'walker' => $walker) );
			echo '</ul>';
			echo '</div><!-- /.tabs-panel -->';
			echo '</div>';
			echo '<p class="button-controls">';
			echo '<span class="add-to-menu">';
			echo '<input type="submit" class="button-secondary submit-add-to-menu" value="' . __('Add to Menu', 'ht-gallery-manager') . '" name="add-ht-gallery-post-archive-menu-item" id="submit-ht-gallery-post-archive" />';
			echo '</span>';
			echo '</p>';
			
		}

		 
		
		/**
		* Menu filter for HT Gallery Posts Archive
		*
		* @param $items The Items
		* @param $menu Menu
		* @param $args Additional params
		*/
		function ht_gallery_archive_menu_filter( $items, $menu, $args ) {
	    	foreach( $items as $item ) {
	      		if( $item->object != 'ht-gallery-archive' ) continue;
	      		$item->url = get_post_type_archive_link( $item->type );
	      
	      		if( get_query_var( 'post_type' ) == $item->type ) {
	       			$item->classes[] = 'current-menu-item';
	        		$item->current = true;
	      		}
	    	}
	    	
	    	return $items;
		}


		/**
		* Intercept the save attachment ajax with a custom function to dave the video url
		*/
		function ht_gallery_custom_save_attachment(){
			if ( ! isset( $_REQUEST['id'] ) || ! isset( $_REQUEST['changes'] ) )
				wp_send_json_error();

			if ( ! $id = absint( $_REQUEST['id'] ) )
				wp_send_json_error();

			check_ajax_referer( 'update-post_' . $id, 'nonce' );

			$changes = $_REQUEST['changes'];
			$post    = get_post( $id, ARRAY_A );

			if ( isset( $changes['video'] ) ){
				$new_video_url = $changes['video'];
				update_post_meta($id, HT_GALLERY_VIDEO_URL_META_KEY_VALUE, $new_video_url);
			}
				

			//do default WorPress function
			wp_ajax_save_attachment();
		}


		/**
		* AJAX function to get the video urls
		*/
		function ht_gallery_get_video_urls_ajax(){
			//check_ajax_referer( 'ht-ajax-nonce', 'security' );
			try{
				$ids =  $_POST['ids'];
				$urls = array();

				foreach ($ids as $key => $attachment_id) {
					$url = get_post_meta( $attachment_id, HT_GALLERY_VIDEO_URL_META_KEY_VALUE, true  );
					$urls[$attachment_id] = $url;
				}
				//return success
				echo json_encode(array('state' => 'success', 'urls' => $urls));
			} catch (Exception $e){
				//return failure
				echo json_encode(array('state' => 'failure', 'message' => $e->getMessage() ) );
			}
			die(); // this is required to return a proper result
		}


		



	} //end class HT_Gallery_Manager
}//end class exists test


//run the plugin
if( class_exists( 'HT_Gallery_Manager' ) ){
	$ht_gallery_manager_init = new HT_Gallery_Manager();
}

