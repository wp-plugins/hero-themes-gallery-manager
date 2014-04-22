<?php

if(!class_exists('HT_Gallery_Display_Widget')){


  class HT_Gallery_Display_Widget extends WP_Widget {
  /*--------------------------------------------------*/
  /* Constructor
  /*--------------------------------------------------*/

  /**
  * Specifies the classname and description, instantiates the widget,
  * loads localization files, and includes necessary stylesheets and JavaScript.
  */
  public function __construct() {

  // TODO: update classname and description
  parent::__construct(
    'ht-gallery-display-widget',
    __( 'Heroic Gallery Widget', 'ht-gallery-manager' ),
    array(
      'classname' =>  'HT_Gallery_Display_Widget',
      'description' =>  __( 'A widget for displaying a heroic gallery.', 'ht-gallery-manager' )
    )
  );

  } // end constructor


  /*-----------------------------------------------------------------------------------*/
  /*  Display Widget
  /*-----------------------------------------------------------------------------------*/
  public function widget( $args, $instance ) {

    extract( $args, EXTR_SKIP );

    $title = $instance['title'];
    $exclude_ids = array( $instance['exclude'] );

    $valid_sort_orders = array('date', 'title', 'comment_count', 'rand', 'modified', 'menu_order');
    if ( in_array($instance['sort_by'], $valid_sort_orders) ) {
      $sort_by = $instance['sort_by'];
      $sort_order = (bool) $instance['asc_sort_order'] ? 'ASC' : 'DESC';
    } else {
      // by default, display latest first
      $sort_by = 'date';
      $sort_order = 'DESC';
    }

    $category=$instance['category'];

    $tax_query = null;

    if($category!="all"&&$category!=""){
      $tax_query = array(
                    array(
                        'taxonomy' => 'ht_gallery_category',
                        'field' => 'id',
                        'terms' => array($category)
                    )
                  );
    }



    // query array  
    $args = array(
      'post_type' => 'ht_gallery_post',
      'posts_per_page' => $instance["num"],
      'orderby' => $sort_by,
      'order' => $sort_order,
      'post__not_in' => $exclude_ids,
      'ignore_sticky_posts' => 1,
      'tax_query' => $tax_query,
      'post_status' => 'publish'
    );    

    echo $before_widget;

    if ( $title )
    echo $before_title . $title . $after_title; 

    $wp_query = new WP_Query($args);
    if($wp_query->have_posts()) :
    ?>

    <ul id="latest-heroic-galleries" class="clearfix">
      <?php while($wp_query->have_posts()) : $wp_query->the_post(); ?>
      <li>
        <a rel="nofollow" href="<?php the_permalink(); ?>">
        <?php 
        if ( class_exists('HT_Gallery_Manager')  ) {
        echo HT_Gallery_Manager::get_starred_image_thumbnail( get_the_ID(), 'gallery'); 
        }
        ?>
        </a>
        <h3><a rel="bookmark" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
      </li>
      <?php endwhile; ?>
    </ul>

    <?php endif;
    echo $after_widget;

  } // end widget

  /*-----------------------------------------------------------------------------------*/
  /*  Update Widget
  /*-----------------------------------------------------------------------------------*/
  public function update( $new_instance, $old_instance ) {

   
 $instance = $old_instance;
    //update  widget's old values with the new, incoming values
    $instance['title'] = strip_tags( $new_instance['title'] );
    $instance['category'] = $new_instance['category'];
    $instance['sort_by'] = $new_instance['sort_by'];
    $instance['asc_sort_order'] = $new_instance['asc_sort_order'] ? 1 : 0;
    $instance['num'] = $new_instance['num'];
    $instance['exclude'] = $new_instance['exclude'];

    return $instance;
  } // end widget

  /*-----------------------------------------------------------------------------------*/
  /*  Widget Settings
  /*-----------------------------------------------------------------------------------*/
  public function form( $instance ) {

    //Define default values forvariables
    $defaults = array(
      'title' => 'Gallery',
      'num' => '3',
      'sort_by' => '',
      'asc_sort_order' => '',
      'exclude' => '',
      'category' => 'all'
    );
    $instance = wp_parse_args((array) $instance, $defaults);

    //category option
    $args = array(
      'hide_empty'    => 0,
      'child_of'    => 0,
      'pad_counts'  => 1,
      'hierarchical'  => 1,
      'orderby' => 'name',
      'order' => 'ASC'
    ); 

    $categories = get_terms('ht_gallery_category', $args);

    

    // Store the values of the widget in their own variable
    $title = strip_tags($instance['title']);
    $num = $instance['num'];
    $exclude = $instance['exclude'];
    ?>
    <label for="<?php echo $this->get_field_id("title"); ?>">
      <?php _e( 'Title', 'ht-gallery-manager' ); ?>
      :
      <input class="widefat" id="<?php echo $this->get_field_id("title"); ?>" name="<?php echo $this->get_field_name("title"); ?>" type="text" value="<?php echo esc_attr($instance["title"]); ?>" />
    </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id("category"); ?>">
        <?php _e( 'Category', 'ht-gallery-manager' ); ?>
        :
        <select id="<?php echo $this->get_field_id("category"); ?>" name="<?php echo $this->get_field_name("category"); ?>">
           <option value="all"<?php selected( $instance["category"], "all" ); ?>><?php _e('All', 'ht-gallery-manager'); ?></option>
          <?php foreach ($categories as $category): ?> 
            <option value="<?php echo $category->term_id; ?>"<?php selected( $instance["category"], $category->term_id ); ?>><?php echo $category->name; ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id("num"); ?>">
        <?php _e( 'Number of galleries to show', 'ht-gallery-manager' ); ?>
        :
        <input style="text-align: center;" id="<?php echo $this->get_field_id("num"); ?>" name="<?php echo $this->get_field_name("num"); ?>" type="text" value="<?php echo absint($instance["num"]); ?>" size='3' />
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id("sort_by"); ?>">
        <?php _e( 'Sort by', 'ht-gallery-manager' ); ?>
        :
        <select id="<?php echo $this->get_field_id("sort_by"); ?>" name="<?php echo $this->get_field_name("sort_by"); ?>">
          <option value="date"<?php selected( $instance["sort_by"], "date" ); ?>><?php _e( 'Date', 'ht-gallery-manager' ); ?></option>
          <option value="title"<?php selected( $instance["sort_by"], "title" ); ?>><?php _e( 'Title', 'ht-gallery-manager' ); ?></option>
          <option value="rand"<?php selected( $instance["sort_by"], "rand" ); ?>><?php _e( 'Random', 'ht-gallery-manager' ); ?></option>
          <option value="modified"<?php selected( $instance["sort_by"], "modified" ); ?>><?php _e( 'Modified', 'ht-gallery-manager' ); ?></option>
          <option value="menu_order"<?php selected( $instance["sort_by"], "menu_order" ); ?>><?php _e( 'Order', 'ht-gallery-manager' ); ?></option>
        </select>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id("asc_sort_order"); ?>">
        <input type="checkbox" class="checkbox"
    id="<?php echo $this->get_field_id("asc_sort_order"); ?>"
    name="<?php echo $this->get_field_name("asc_sort_order"); ?>"
    <?php checked( (bool) $instance["asc_sort_order"], true ); ?> />
        <?php _e( 'Reverse sort order (ascending)', 'ht-gallery-manager' ); ?>
      </label>
    </p>
    <p>
      <label for="<?php echo $this->get_field_id("exclude"); ?>">
        <?php _e( 'Excluded Galleries (ex. 1,2,3)', 'ht-gallery-manager' ); ?>
        :
        <input style="text-align: center;" id="<?php echo $this->get_field_id("exclude"); ?>" name="<?php echo $this->get_field_name("exclude"); ?>" type="text" value="<?php echo $instance["exclude"]; ?>" size='3' />
      </label>
    </p>
    <?php 
  } // end form


  } // end class

  add_action( 'widgets_init', create_function( '', 'register_widget("HT_Gallery_Display_Widget");' ) );


}




