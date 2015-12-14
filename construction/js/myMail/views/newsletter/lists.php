<?php
	$editable = !in_array($post->post_status, array('active', 'finished'));
	$taxonomy = 'newsletter_lists';
	
 if($editable):
	$defaults = array('taxonomy' => 'category');
	if ( !isset($box['args']) || !is_array($box['args']) )
		$args = array();
	else
		$args = $box['args'];
	extract( wp_parse_args($args, $defaults), EXTR_SKIP );
	$tax = get_taxonomy($taxonomy);

	?>
	<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydivs">
		
		<label><input type="checkbox" id="all_lists"> <?php _e('all', 'mymail'); ?></label>

		<div id="<?php echo $taxonomy; ?>-all">
			<?php
            $name = ( $taxonomy == 'category' ) ? 'post_category' : 'tax_input[' . $taxonomy . ']';
            echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
            ?>
			<ul>
				<?php wp_terms_checklist($post->ID, array( 'taxonomy' => $taxonomy, 'popular_cats' => false , 'checked_ontop' => false, 'walker' => new mymail_Walker_Category_Checklist )) ?>
			</ul>
		</div>
		
		<p class="totals"><?php _e('Total receivers', 'mymail'); ?>: <span id="mymail_total"></span></p>
		
	</div>
<?php else :
		$tax = get_the_taxonomies($post->ID, 'template=%2$l');
		if(isset($tax['newsletter_lists'])){
		
			echo strip_tags($tax['newsletter_lists']);
			
		}else{
			_e('no lists selected', 'mymail');
		}
 endif;?>
