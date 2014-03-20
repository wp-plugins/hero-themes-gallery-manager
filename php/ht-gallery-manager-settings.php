<?php
class HT_Gallery_Manager_Settings_Page {
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_head', array( $this, 'hero_gallery_menu_icons' )  );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );

        $default_slug = __('gallery', 'ht-gallery-manager');

        $this->options = array(
                'slug' => $default_slug
            );
    }


 
    /**
    * Add Heroic Gallery Menu Icons
    */
    public function hero_gallery_menu_icons() {
        ?>

        <style type="text/css" media="screen">
            #menu-posts-ht_gallery_post .wp-menu-image {
                background: url(<?php echo plugins_url( 'img/ht-gallery-menu-icon.png' , dirname( __FILE__ ) ); ?>) no-repeat 6px -17px !important;
            }
            #menu-posts-ht_gallery_post:hover .wp-menu-image, #menu-posts-ht_gallery_post.wp-has-current-submenu .wp-menu-image {
                background-position:6px 7px!important;
            }
        </style>
    <?php 
    } 

    /**
     * Add options page
     */
    public function add_plugin_page() {
        // This page will be under "Settings"
        add_submenu_page('edit.php?post_type=ht_gallery_post', 
            __( 'Heroic Gallery Settings' , 'ht-gallery-manager' ), 
            __( 'Settings' , 'ht-gallery-manager' ),
            'edit_posts', 'ht_gallery_manager_settings_page', 
            array( $this, 'create_admin_page' )
        );
       
    }

    /**
     * Options page callback
     */
    public function create_admin_page() {
        // Set class property
        $this->options = get_option( 'ht_gallery_manager_options' );

        if(!$this->options){
            
        }
        $page = get_current_screen();

       ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e( 'Settings' , 'ht-gallery-manager'); ?></h2>  
            <?php settings_errors(); ?>        
            <form method="post" id="ht-gallery-manager-form" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'ht_gallery_manager_option' );   
                do_settings_sections( 'ht-gallery-manager-options' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }




    /**
     * Register and add settings
     */
    public function page_init() {       
        register_setting(
            'ht_gallery_manager_option', // Option group 
            'ht_gallery_manager_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );
        
        add_settings_section(
            'gallery_slug', 
            __( 'Gallery Slug' , 'ht-gallery-manager'), 
            array( $this, 'gallery_slug_section_callback' ), 
            'ht-gallery-manager-options' 
        );  


        add_settings_field(
            'post_slug', 
            __( 'Gallery Slug' , 'ht-gallery-manager'), 
            array( $this, 'post_slug_field_callback' ), 
            'ht-gallery-manager-options', 
            'gallery_slug'           
        );

        add_settings_field(
            'category_slug', 
            __( 'Category' , 'ht-gallery-manager'), 
            array( $this, 'category_slug_field_callback' ), 
            'ht-gallery-manager-options', 
            'gallery_slug'           
        ); 

        add_settings_section(
            'ht_posts_limit_section', 
            __( 'Post Limit' , 'ht-gallery-manager'), 
            array( $this, 'ht_posts_limit_section_callback' ), 
            'ht-gallery-manager-options' 
        );

        add_settings_field(
            'ht_posts_limit_value', 
            __( 'Post Limit' , 'ht-gallery-manager'), 
            array( $this, 'ht_posts_limit_field_callback' ), 
            'ht-gallery-manager-options', 
            'ht_posts_limit_section'           
        ); 

       
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {
        $old_value = get_option( 'ht_gallery_manager_options' );
        $new_input = array();
        if( isset( $input['post_slug'] ) ){
            //use santize title function as this  will be a slug
            $new_input['post_slug'] = sanitize_title( $input['post_slug'] );
        }

        if( isset( $input['category_slug'] ) ){
            //use santize title function as this  will be a slug
            $new_input['category_slug'] = sanitize_title( $input['category_slug'] );
        }

        if( isset( $input['ht_posts_limit_value'] ) ){
            //use santize key to ensure we get a santized alphanumeric value here
            $new_input['ht_posts_limit_value'] = sanitize_key( $input['ht_posts_limit_value'] );
        }

        //validate
        if( $new_input['category_slug'] ==  $new_input['post_slug'] &&  $new_input['category_slug']  != '' &&  $new_input['post_slug'] != '' ){
            add_settings_error( 'ht-gallery-manager-options', 'invalid-slugs', __('The slugs cannot be the same.', 'ht-gallery-manager' ) );
            $new_input = $old_value;
        } else if ( !is_numeric($new_input['ht_posts_limit_value']) ){
            print_r($new_input['ht_posts_limit_value']);
            add_settings_error( 'ht-gallery-manager-options', 'invalid-post-limit', __('The post limit must be numeric', 'ht-gallery-manager' ) );
            $new_input = $old_value;
        } else {
            //flush the rewrite rules
            flush_rewrite_rules();
        }


        return $new_input;
    }


    /** 
     * Print the section title
     */
    public function gallery_slug_section_callback(){
        _e('Gallery Slug', 'ht-gallery-manager');
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function post_slug_field_callback() {
        printf(
            '<input type="text" id="post_slug" name="ht_gallery_manager_options[post_slug]" value="%s" />',
            isset( $this->options['post_slug'] ) ? esc_attr( $this->options['post_slug']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function category_slug_field_callback() {
        printf(
            '<input type="text" id="category_slug" name="ht_gallery_manager_options[category_slug]" value="%s" />',
            isset( $this->options['category_slug'] ) ? esc_attr( $this->options['category_slug']) : ''
        );
    }


    /** 
     * Print the section title
     */
    public function ht_posts_limit_section_callback(){
        _e('Heroic Gallery Posts Limit', 'ht-gallery-manager');
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function ht_posts_limit_field_callback() {
        printf(
            '<input type="text" id="ht_posts_limit_value" name="ht_gallery_manager_options[ht_posts_limit_value]" value="%s" />',
            isset( $this->options['ht_posts_limit_value'] ) ? esc_attr( $this->options['ht_posts_limit_value']) : '100000'
        );
    }


    /**
    * Enqueue scripts and styles
    */
    public function enqueue_scripts_and_styles() {
        $screen = get_current_screen();
        if(  $screen->base == 'settings_page_ht-gallery-manager-options' ) {
        }
    }

} //end class

//run the settings page
if( is_admin() )
    $ht_gallery_manager_settings_page_init = new HT_Gallery_Manager_Settings_Page();