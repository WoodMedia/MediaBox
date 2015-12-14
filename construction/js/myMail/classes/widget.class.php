<?php if(!defined('ABSPATH')) die('not allowed');


class mymail_signup extends WP_Widget {

	public function __construct() {
		parent::__construct(
	 		'mymail_signup', // Base ID
			__('Newsletter Signup Form', 'mymail'), // Name
			array( 'description' => __( 'Sign Up form for the newsletter', 'mymail' ), ) // Args
		);
	}

 	public function form( $instance ) {
		// outputs the options form on admin
		$title = apply_filters('widget_title', empty($instance['title']) ? __('Newsletter sign up', 'mymail') : $instance['title'], $instance, $this->id_base);
		$text_before = apply_filters('widget_text_before', empty($instance['text_before']) ? '' : $instance['text_before'], $instance, $this->id_base);
		$form = apply_filters('widget_form', empty($instance['form']) ? 0 : $instance['form'], $instance, $this->id_base);
		$text_after = apply_filters('widget_text_after', empty($instance['text_after']) ? '' : $instance['text_after'], $instance, $this->id_base);
		
		$forms = mymail_option('forms');
		
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'mymail' ); ?>:</label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		<label for="<?php echo $this->get_field_id( 'form' ); ?>"><?php _e( 'Form', 'mymail' ); ?>:</label> 
		<select class="widefat" id="<?php echo $this->get_field_id( 'form' ); ?>" name="<?php echo $this->get_field_name( 'form' ); ?>" >
		<?php foreach($forms as $id => $f){ ?>
			<option value="<?php echo $id ?>"<?php if($form == $id) echo " selected"?>><?php echo $f['name'] ?></option>
		<?php } ?>
		</select>
		<a href="options-general.php?page=newsletter-settings#forms"><?php _e('add form', 'mymail'); ?></a>
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'text_before' ); ?>"><?php _e( 'Text before the form', 'mymail' ); ?>:</label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'text_before' ); ?>" name="<?php echo $this->get_field_name( 'text_before' ); ?>" type="text" value="<?php echo esc_attr( $text_before ); ?>" />
		<label for="<?php echo $this->get_field_id( 'text_after' ); ?>"><?php _e( 'Text after the form', 'mymail' ); ?>:</label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'text_after' ); ?>" name="<?php echo $this->get_field_name( 'text_after' ); ?>" type="text" value="<?php echo esc_attr( $text_after ); ?>" />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['text_before'] = ( $new_instance['text_before'] );
		$instance['form'] = strip_tags( $new_instance['form'] );
		$instance['text_after'] = ( $new_instance['text_after'] );

		return $instance;
	}

	public function widget( $args, $instance ) {
		global $post;
		if($post && mymail_option('homepage') == $post->ID) return false;
		// outputs the content of the widget
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$text_before = apply_filters( 'widget_text_before', isset($instance['text_before']) ? $instance['text_before'] : false);
		$form = apply_filters( 'widget_form', $instance['form'] );
		$text_after = apply_filters( 'widget_text_after', isset($instance['text_after']) ? $instance['text_after'] : false);

		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;

			require_once MYMAIL_DIR . '/classes/form.class.php';

			global $mymail_form;

			if (!$mymail_form)
				$mymail_form = new mymail_form();

			if ($text_before) echo '<div class="mymail-widget-text mymail-widget-text-before">'.$text_before.'</div>';
			
			echo $mymail_form->form($form, 1, 'mymail-in-widget');
			
			if ($text_after) echo '<div class="mymail-widget-text mymail-widget-text-before">'.$text_after.'</div">';
			
		echo $after_widget;
	}

}

class mymail_list_newsletter extends WP_Widget {

	public function __construct() {
		parent::__construct(
	 		'mymail_list_newsletter', // Base ID
			__('Newsletter List', 'mymail'), // Name
			array( 'description' => __( 'Display the most recent newsletters', 'mymail' ), ) // Args
		);

		add_action( 'save_post', array(&$this, 'flush_widget_cache') );
		add_action( 'deleted_post', array(&$this, 'flush_widget_cache') );
		add_action( 'switch_theme', array(&$this, 'flush_widget_cache') );
	}

	public function widget($args, $instance) {
		$cache = wp_cache_get('widget_recent_newsletter', 'widget');

		if ( !is_array($cache) )
			$cache = array();

		if ( ! isset( $args['widget_id'] ) )
			$args['widget_id'] = $this->id;

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		ob_start();
		extract($args);
		
		
		$title = apply_filters('widget_title', empty($instance['title']) ? __('Latest Newsletter', 'mymail') : $instance['title'], $instance, $this->id_base);
		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) )
 			$number = 10;
 			
		$r = new WP_Query( apply_filters( 'widget_newsletter_args', array( 'post_type' => 'newsletter', 'posts_per_page' => $number, 'no_found_rows' => true, 'post_status' => array('finished', 'active'), 'ignore_sticky_posts' => true ) ) );
		if ($r->have_posts()) :
?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<ul>
		<?php  while ($r->have_posts()) : $r->the_post(); ?>
		<li><a href="<?php the_permalink() ?>" title="<?php echo esc_attr(get_the_title() ? get_the_title() : get_the_ID()); ?>"><?php if ( get_the_title() ) the_title(); else the_ID(); ?></a></li>
		<?php endwhile; ?>
		</ul>
		<?php echo $after_widget; ?>
<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		endif;

		$cache[$args['widget_id']] = ob_get_flush();
		wp_cache_set('widget_recent_newsletter', $cache, 'widget');
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_recent_entries']) )
			delete_option('widget_recent_entries');

		return $instance;
	}

	public function flush_widget_cache() {
		wp_cache_delete('widget_recent_newsletter', 'widget');
	}

	public function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : __('Latest Newsletter', 'mymail');
		$number = isset($instance['number']) ? absint($instance['number']) : 5;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of Newsletters:', 'mymail'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
}

?>