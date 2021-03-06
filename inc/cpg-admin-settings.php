<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

$options = get_option('cpg_options');

//Add settings link to plugin on plugin page
add_filter( 'plugin_action_links_' . CPG_BASENAME, 'cpg_settings_link' );
function cpg_settings_link($links) {
	$links[] = '<a href="'. get_admin_url(null, 'upload.php?page='.CPG_NAME) .'">Settings</a>';
	return $links;
}

//Add settings page
add_action( 'admin_menu', 'cpg_create_menu' );
add_action( 'admin_init', 'cpg_register_settings' );
function cpg_create_menu(){
	add_submenu_page(
		'upload.php',
		'Color Palette Generator',
		'Color Palette Generator',
		'manage_options',
		CPG_NAME,
		'cpg_settings_page'
	);
}

//Add settings
function cpg_register_settings() {
	register_setting( 'cpg_options', 'cpg_options' );
	add_settings_section(
		'cpg_options',
		__('Settings', 'cpg'),
		'cpg_section_text',
		'cpg_settings_page'
	);
	add_settings_field(
		'cpg_colors',
		'Number of colors to generate',
		'cpg_create_field_colors',
		'cpg_settings_page',
		'cpg_options'
	);
	add_settings_field(
		'cpg_show_on_attachment',
		'Show palette on attachment pages?',
		'cpg_create_field_show_on_attachment',
		'cpg_settings_page',
		'cpg_options'
	);
	add_settings_field(
		'cpg_edit_colors',
		'Create your own color filters?',
		'cpg_create_field_color_filter',
		'cpg_settings_page',
		'cpg_options'
	);
}

function cpg_section_text() {
	echo '<hr style="margin: 0; display: block; padding:  0; border-top: 1px solid #eee;"/>';
}

function cpg_create_field_colors() {
	$options = get_option('cpg_options');
	$value = isset( $options['colors'] ) ? $options['colors'] : 10;

	echo '<input id="cpg_colors" name="cpg_options[colors]" type="number" value="'.$value.'" style="width: 100%;"/> <br/><small>The higher the number, the heavier the process.</small>';
}

function cpg_create_field_show_on_attachment() {
	$options = get_option('cpg_options');
	$checked = isset( $options['show_on_attachment'] ) && $options['show_on_attachment'] == 'true' ? 'checked' : '';

	echo '<input id="cpg_show_on_attachment" name="cpg_options[show_on_attachment]" type="checkbox" value="true" '.$checked.'/>';
}

function cpg_create_field_color_filter() {
	$options = get_option('cpg_options');
	$checked = isset( $options['edit_colors'] ) && $options['edit_colors'] == 'true' ? 'checked' : '';

	$substring = $checked ? __('You can now edit the color table below. If you do this, all palettes need to be regenerated', 'cpg') : __('Only check this if you know what you\'re doing', 'cpg');

	echo '<label><input id="cpg_edit_colors" name="cpg_options[edit_colors]" type="checkbox" value="true" '.$checked.'/>
	<small>'.$substring.'. <br/><a href="https://www.thearthunters.com/color-palette-generator/" target="_blank">' . __('Read more about this option', 'cpg') . '</a></small></label>';
}

//Add palette options to image insert
function cpg_add_media_edit_fields( $form_fields, $post ) {
    $cpg_settings = array(
    	'cpg_show' => get_post_meta( $post->ID, '_cpg_show', true ),
    	'cpg_show_dominant' => get_post_meta( $post->ID, '_cpg_show_dominant', true ),
    	'cpg_number_of_colors' => get_post_meta( $post->ID, '_cpg_number_of_colors', true )
    );
	$cpg_options = get_option( 'cpg_options' );
	$cpg_num_options = isset( $cpg_options['colors'] ) ? $cpg_options['colors'] : 10;

	$cpg_show_checked = (
			isset( $cpg_settings['cpg_show'] ) &&
			$cpg_settings['cpg_show'] == '1'
		) ? 'checked' : '';

    $cpg_show_dominant =(
    		isset( $cpg_settings['cpg_show_dominant'] ) &&
    		$cpg_settings['cpg_show_dominant'] == '1'
    	) ? 'checked' : '' ;

 	$cpg_number_of_colors = (
 			isset( $cpg_settings['cpg_number_of_colors'] ) &&
 			$cpg_settings['cpg_number_of_colors'] <= $cpg_num_options &&
 			$cpg_settings['cpg_number_of_colors'] > 0
		)  ? $cpg_settings['cpg_number_of_colors'] : $cpg_num_options;

	$fields = array(
		'cpg_show' => array(
			'label' => __( 'Enable palette', 'cpg' ),
			'application' => 'image',
       	 	'show_in_edit' => false,
       	 	'input' => 'html',
       	 	'html' => '<input type="checkbox" value="1" ' . $cpg_show_checked . '
    					name="attachments['.$post->ID.'][cpg_show]"
    					id="cpg-palette-toggle" />',
        	'exclusions'   => array( 'audio', 'video' )
		),
		'cpg_show_dominant' => array(
			'label' => __( 'Show dominant color', 'cpg' ),
			'application' => 'image',
       	 	'show_in_edit' => false,
       	 	'input' => 'html',
	        'html' => '<input type="checkbox" value="1" ' . $cpg_show_dominant . '
	    					name="attachments['.$post->ID.'][cpg_show_dominant]"
	    					id="cpg-palette-dominant" />',
        	'exclusions'   => array( 'audio', 'video' )
		),
		'cpg_number_of_colors' => array(
			'label' => __( 'Number of colors to show', 'cpg' ),
			'application' => 'image',
       	 	'show_in_edit' => false,
	        'input' => 'html',
	        'html' => '<input type="number" value="'.$cpg_number_of_colors.'" min="1" max="'.$cpg_num_options.'"
	    					name="attachments['.$post->ID.'][cpg_number_of_colors]"
	    					id="cpg-palette-colors" />',
        	'exclusions'   => array( 'audio', 'video' )
		),
	);

	foreach ($fields as $key => $value) {
		$form_fields[$key] = $value;
	}

    return $form_fields;
}
add_filter( 'attachment_fields_to_edit', 'cpg_add_media_edit_fields', 10, 2 );

//Add output when inserted
function cpg_send_image_to_editor($html, $id, $caption, $title, $align, $url, $size) {
    $cpg_settings = array(
    	'cpg_show' => get_post_meta( $id, '_cpg_show', true ),
    	'cpg_show_dominant' => get_post_meta( $id, '_cpg_show_dominant', true ),
    	'cpg_number_of_colors' => get_post_meta( $id, '_cpg_number_of_colors', true )
    );

	if( $cpg_settings['cpg_show'] ){
		$cpg_show_dominant = $cpg_settings['cpg_show_dominant'] == 1 ? 'true' : 'false';
		$cpg_number_of_colors = $cpg_settings['cpg_number_of_colors'];
		$atts = array(
			'attachment' => $id,
			'dominant' => $cpg_show_dominant,
			'colors' => $cpg_number_of_colors,
			'size' => $size
		);

		$html = '[colorpalette attachment="'.$id.'" dominant="'.$cpg_show_dominant.'" colors="'.$cpg_number_of_colors.'" size="'.$size.'"] <br/>';
	}

    return $html;
}
add_filter( 'image_send_to_editor', 'cpg_send_image_to_editor', 12, 9 );

//add meta box to edit attachment pages
function add_our_attachment_meta(){
	$options = get_option('cpg_options');
	if(
		isset( $_GET['post'] ) &&
		wp_attachment_is_image( intval($_GET['post']) ) &&
		isset( $options['show_on_attachment'] ) &&
		$options['show_on_attachment'] == 'true'
	){
   		add_meta_box(
			'cpg-attachment-meta-box',
         	'Palette',
         	'cpg_attachment_meta_box_callback',
         	'attachment',
         	'side',
         	'low'
        );
	}
}
add_action( 'admin_init', 'add_our_attachment_meta' );

function cpg_attachment_meta_box_callback(){
    global $post;
    wp_enqueue_style( 'cpg-generate-palette-column-css' );
    wp_enqueue_script( 'cpg-generate-palette' );
	$dominant = get_the_terms( $post->ID, 'cpg_dominant_color' );
	if($dominant){
		$dominant = $dominant[0];
		$palette = get_the_terms( $post->ID, 'cpg_palette' );
		echo '<div class="cpg__meta-box cpg_dominant_color_column">' . cpg_admin_show_palette( $dominant->name, $palette, $post->ID ) . '</div>';
	}else{
		echo '<div class="cpg__meta-box cpg_dominant_color_column">' . cpg_admin_no_palette( $post->ID ) . '</div>';
	}
}

//Save palette options to image insert
function cpg_add_media_edit_fields_save( $post, $attachment ) {
    $show_value = isset( $attachment['cpg_show'] ) ? sanitize_text_field( $attachment['cpg_show']) : '0';
    update_post_meta( $post['ID'], '_cpg_show', $show_value );

    if($show_value == '1'){
    	$show_dominant = isset( $attachment['cpg_show_dominant'] ) ? sanitize_text_field( $attachment['cpg_show_dominant']) : "0";
    	update_post_meta( $post['ID'], '_cpg_show_dominant', $show_dominant );
    	$num_options = get_option( 'cpg_options' );
    	$num_colors = isset( $attachment['cpg_number_of_colors'] ) && $attachment['cpg_number_of_colors'] < $num_options['colors'] ? sanitize_text_field( $attachment['cpg_number_of_colors'] ) : $num_options['colors'];
    	update_post_meta( $post['ID'], '_cpg_number_of_colors', $num_colors );
    }

    return $post;
}
add_filter( 'attachment_fields_to_save', 'cpg_add_media_edit_fields_save', 10, 2 );

//Layout for settings page
function cpg_settings_page(){
	if( isset($_GET['reset']) ){
		$options = get_option('cpg_options');
		$default_colors = cpg_default_color_table();
		$options['color_table'] = $default_colors;
		update_option( 'cpg_options', $options);
	}
	$total = cpg_img_count();
	$with = cpg_img_count( true );
	$excluded = cpg_img_count( false, true );
	$options = get_option('cpg_options');
	$colors = isset( $options['colors'] ) ? $options['colors'] : 10;
	$edit_colors = isset( $options['edit_colors'] ) ? $options['edit_colors'] : false;
?>
	<div class="wrap">
		<h1><?php _e("Color Palette Generator",'cpg'); ?></h1>
		<?php settings_errors(); ?>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<?php if( isset( $_GET['action'] ) && sanitize_text_field($_GET['action']) != '' ){ ?>

					<div id="cpg-stats" class="postbox cpg-postbox">
						<h2 class="hndle cpg-hndle"><?php _e('Error', 'cpg'); ?></h2>
						<div class="inside">
							<p>
								<?php _e('Something went wrong. You probably landed here because of a javascript error. Make sure your javascript is enabled, this plugin will not work without it.', 'cpg'); ?>
								<a href="<?php echo esc_url(get_admin_url(null, 'upload.php?page='.CPG_NAME)); ?>">
									<?php _e('Click here to refresh.', 'cpg'); ?>
								</a>
							</p>
						</div>
					</div>

					<?php }elseif( isset( $_GET['reset'] ) && sanitize_text_field($_GET['reset']) == 'true'  ){ ?>

					<div id="cpg-stats" class="postbox cpg-postbox">
						<h2 class="hndle cpg-hndle"><?php _e('Reset successful', 'cpg'); ?></h2>
						<div class="inside">
							<p>
								<?php _e('The color table has been reset successfully. The colors initially added by this plugin are now your default colors again. If you generated the palettes with altered colors and you no longer want to use them, be sure to regenereate all your palettes.', 'cpg'); ?>
								<a href="<?php echo esc_url(get_admin_url(null, 'upload.php?page='.CPG_NAME)); ?>">
									<?php _e('Click here to see your color table.', 'cpg'); ?>
								</a>
							</p>
						</div>
					</div>

					<?php }else{ ?>

					<div id="cpg-stats" class="postbox cpg-postbox">
						<h2 class="hndle cpg-hndle">
							<span><?php _e( 'Bulk generator', 'cpg' ); ?> (<span data-with><?php echo esc_html($with); ?></span> / <span data-total><?php echo esc_html($total); ?></span>)</span>
							<?php if($total - $excluded != $with){ ?>
								<small>
									<?php _e( 'Generate individual palettes via your', 'cpg' ); ?>
									<a href="<?php echo esc_url(get_admin_url(null, 'upload.php')); ?>">
										<?php _e( 'Media Library', 'cpg' ); ?>
									</a>
								</small>
							<?php } ?>
						</h2>
						<div class="inside cpg__inside cpg__inside--btn cpg__inside--generate">
							<p>
								<?php if( $total - $with - $excluded == 0 ) { ?>
									<?php _e( 'All images have a palette. Well done!', 'cpg' ); ?>
								<?php } else { $img = get_attachment_without_colors(); ?>
									<a href="<?php echo get_admin_url( null, 'upload.php?page='.CPG_NAME ) . '&action=cpg_bulk_generate_palette&post_id='.$img['id'].'&_wpnonce=' . wp_create_nonce( 'cpg_bulk_generate_palette_'.$img['id'].'_nonce' ); ?>&colors=<?php echo $colors; ?>&regenerate=false" class="button cpg-button-bulk" data-src="<?php echo $img['src']; ?>">
											<?php _e( 'Generate palettes', 'cpg' ); ?>
									</a>
								<?php } ?>

								<?php if( $with > 0 ) { ?>
									<a href="<?php echo get_admin_url( null, 'upload.php?page='.CPG_NAME ) . '&action=cpg_bulk_generate_palette&_wpnonce=' . wp_create_nonce( 'cpg_bulk_regenerate_palette_nonce' ); ?>&regenerate=true" class="cpg-button-bulk--regenerate">
											<?php _e( 'Regenerate palettes', 'cpg' ); ?>
									</a>
								<?php } ?>
							</p>
						</div>
					</div>

					<div id="cpg-stats" class="postbox cpg-postbox">
						<h2 class="hndle cpg-hndle"><?php _e( 'Shortcodes', 'cpg' ); ?></h2>
						<div class="inside cpg__inside cpg__inside--btn">
							<p><?php _e('To show individual images within a post, use the following shortcode (or enter the required options while adding images)', 'cpg'); ?>: <pre><code>[colorpalette attachment="56" dominant="false" colors="3" size="thumbnail"]</code></pre></p>
							<ul>
								<li><?php _e('attachment: the id of the image you want to show', 'cpg'); ?></li>
								<li><?php _e('dominant: whether you want to show the dominant color or not', 'cpg'); ?></li>
								<li><?php _e('colors: the number of colors you want to show', 'cpg'); ?></li>
								<li><?php _e('size: the format of the artwork you want to show (thumbnail, medium and large are WordPress defaults)', 'cpg'); ?></li>
							</ul>
							<br/><br/>
							<p><?php _e('To show generated palettes, without the images use the following shortcode', 'cpg'); ?>: <pre><code>[colorpalettes attachments="21, 28, 32" colors="5"]</code></pre> <?php _e('To show all palettes, simply use', 'cpg'); ?> <pre><code>[colorpalettes]</code></pre></p>
							<ul>
								<li><?php _e('attachments: the ids of the images of which you want to show the palettes. Leave empty to show the palettes of all images', 'cpg'); ?></li>
								<li><?php _e('colors: the number of colors you want to show, default to the number of colors you\'ve defined above', 'cpg'); ?></li>
								<li><?php _e('total: the maximum number of palettes you want to show, by default, all palettes are shown', 'cpg'); ?></li>
							</ul>
						</div>
					</div>

					<form method="post" action="options.php" class="postbox cpg-postbox">
						<?php
							settings_fields( 'cpg_options' );
							do_settings_sections( 'cpg_settings_page' );
						?>
						<div class="cpg-wrap cpg-wrap--<?php echo esc_attr($edit_colors) ? 'visible' : 'hidden'; ?>">
							<table class="cpg-color-table">
								<thead>
									<tr>
										<th><?php _e('Color', 'cpg'); ?></th>
										<th><?php _e('Color name', 'cpg'); ?></th>
										<th><?php _e('Color tints', 'cpg'); ?></th>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<td colspan="3">
											<p>
												<button class="button cpg-color-table__add-row"><?php _e('Add color row', 'cpg'); ?></button>
												<a href="<?php echo esc_url( add_query_arg( 'reset', 'true', $_SERVER['REQUEST_URI'] ) ); ?>" data-reset class="cpg-reset-table"><?php _e('Reset to default', 'cpg'); ?></a>
											</p>
										</td>
									</tr>
								</tfoot>
								<tbody>
									<?php
										$maincolors = cpg_return_colors();
										foreach ($maincolors as $name => $code) {
											$tints = cpg_return_tints($name);
											$code = '#'.$code;
											$name = ucwords(str_replace('-', ' ', $name));
											$name_in_array = strtolower(str_replace(' ', '-', $name));
									?>
										<tr>
											<td>
												<div class="cpg-color__main-color">
													<input type="text" maxlength="7" style="background-color: <?php echo esc_attr( $code ); ?>" value="<?php echo esc_attr( $code ); ?>" class="cpg-color-picker" name="cpg_options[color_table][<?php echo esc_attr( $name_in_array ); ?>][code]"/>
												</div>
												<div class="row-actions">
													<span class="trash"><a href="#"><?php _e('Trash row', 'cpg'); ?></a></span>
												</div>
											</td>
											<td><input type="text" value="<?php echo esc_attr( $name ); ?>" name="cpg_options[color_table][<?php echo esc_attr( $name_in_array ); ?>][name]" class="cpg-color-name"/></td>
											<td>
												<div class="cpg-color-table__colors">
												<?php foreach ($tints as $tint) { $tint = '#'.$tint; ?>
													<div class="cpg-color-table__div">
														<input type="text" style="background-color: <?php echo esc_attr( $tint ); ?>"  value="<?php echo esc_attr( $tint ); ?>" class="cpg-color-picker" name="cpg_options[color_table][<?php echo esc_attr( $name_in_array ); ?>][tints][]" />
														<button class="cpg-delete-color">&times;</button>
													</div>
												<?php } ?>
												</div>
												<div class="cpg-color-table__div">
													<button class="button tiny" data-add-color ><?php _e('Add color tint', 'cpg'); ?></button>
												</div>
											</td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
						<?php submit_button(); ?>
					</form>
					<?php } ?>
				</div>
				<div id="postbox-container-1" class="postbox-container">
					<div id="cpg-stats" class="postbox cpg-postbox">
						<h2 class="hndle cpg-hndle"><span><?php _e( 'Stats', 'cpg' ); ?></span></h2>
						<div class="inside cpg__inside">
							<p class="cpg__stats <?php if( $total > 100000 ) { ?>cpg__stats--small<?php } ?>">
								<span data-total><?php echo esc_html( $total ); ?></span>
								<?php _e( 'Total images', 'cpg' ); ?>
							</p>
							<p class="cpg__stats">
								<span data-with><?php echo esc_html( $with ); ?></span>
								<?php _e( 'With palette', 'cpg' ); ?>
							</p>
							<p class="cpg__stats">
								<span data-without><?php echo esc_html( $total-$with-$excluded ); ?></span>
								<?php _e( 'Without palette', 'cpg' ); ?>
							</p>
							<p class="cpg__stats--full <?php if( $excluded < 1 ) { ?>cpg__stats--hidden<?php } ?>">
								<span data-excluded><?php echo esc_html( $excluded ); ?></span>
								<?php _e( 'skipped due to error', 'cpg'); ?>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="clear"></div>
	<?php
}
//Setup settings & taxonomies used to store colors
function cpg_register_default_settings(){
	//set default colors for filtering
	$default_opts = array(
		'color_table' => cpg_default_color_table(),
		'show_on_attachment' => true
	);
	add_option( 'cpg_options', $default_opts );

    $args = array(
        'public' => true,
        'update_count_callback' => '_update_generic_term_count',
        'query_var' => false,
        'hierarchical' => true
    );

    register_taxonomy( 'cpg_dominant_color', array( 'attachment' ), $args );

    $args_palette = array(
        'public' => false,
        'update_count_callback' => '_update_generic_term_count',
        'query_var' => false
    );
    register_taxonomy( 'cpg_palette', array( 'attachment' ), $args_palette );

	cpg_setup_taxonomies();
}
add_action( 'init', 'cpg_register_default_settings' );

function cpg_colorpalette_shortcode( $atts, $content = "" ) {

	$options = get_option('cpg_options');
	$colors = isset( $options['colors'] ) ? $options['colors'] : 10;
	$orig_content = $content;

	$atts = shortcode_atts( array(
		'attachment' => '',
		'dominant' => false,
		'colors' => $colors,
		'size' => 'large'
	), $atts, 'colorpalette' );

	$att_id = $atts['attachment'];

	if( $att_id != '' && wp_attachment_is_image( $att_id ) && file_exists( get_attached_file( $att_id ) ) ){
		wp_enqueue_style( 'cpg-frontend-styles-css' );
		$img = wp_get_attachment_image( $att_id, $atts['size'] );
		$img = wp_make_content_images_responsive($img);

		$content = '<div class="cpg-image cpg__palette-holder">'.$img;
		$colors = get_the_terms( $att_id, 'cpg_dominant_color' );
		$palette = get_the_terms( $att_id, 'cpg_palette' );

		if( $atts['dominant'] == 'true' && $colors ) {
			$dominant = $colors[0];
	    	$dominant = $dominant->name;
	    	$content .= '<div class="cpg__dominant-color cpg__color-item" style="background-color:'.$dominant.';" data-title="Dominant: '.$dominant.'"></div>';
	    }

		if( $palette ){
			$content .= '<ul class="cpg__palette-list">';
			shuffle($palette);
			foreach ( $palette as $i => $color ) {
				if ( $i == $atts['colors'] ){
   					break;
				}

				if( is_object( $color ) ){
					$color = $color->name;
				}
				$content .= '<li class="cpg__palette-item cpg__color-item" style="background-color:'.$color.';" data-title="'.$color.'"></li>';
			}
			$content .= '</ul>';
		}

		if(!$colors && !$palette){
			$content .= '<p class="cpg__text">No palette</p>';
		}

		if( $orig_content != "" ){
			$content .= '<p class="cpg__text">'.$orig_content.'</p>';
		}
		$content .= '</div>';
	}else{
		$content = "<p>".__( "Attachment doesn't exist or isn't an image", "cpg"). "</p>";
	}

	return $content;
}
add_shortcode( 'colorpalette', 'cpg_colorpalette_shortcode' );

function cpg_colorpalettes_shortcode( $atts ) {

	$options = get_option('cpg_options');
	$colors = isset( $options['colors'] ) ? $options['colors'] : 10;
	$content = '<div class="cpg__palette-holder">';

	$atts = shortcode_atts( array(
		'attachments' => '',
		'colors' => $colors,
		'orderby' => 'post_date',
		'order' => 'DESC',
		'total' => -1
	), $atts, 'colorpalettes' );

	$attachments = $atts['attachments'];
	$colors = $atts['colors'];
	$orderby = $atts['orderby'];
	$order = $atts['order'];
	$ppp = $atts['total'];

	$terms = get_terms( array(
    	'taxonomy' => 'cpg_dominant_color'
    ) );

	$args = array(
		'post_type' => 'attachment',
		'orderby' => $orderby,
		'order' => $order,
		'fields' => 'ids',
		'posts_per_page' => $ppp,
		'tax_query' => array(
            array(
                'taxonomy' => 'cpg_dominant_color',
                'field' => 'slug',
                'terms' => wp_list_pluck( $terms, 'slug' )
            )
        )
	);

	if( $attachments != '' ){
		$args['post__in'] = explode(',', $attachments);
	}

	$attachments = get_posts( $args );

	if( $attachments ){
		wp_enqueue_style( 'cpg-frontend-styles-css' );
		foreach ($attachments as $attachment) {
			$content .= '<ul class="cpg__palette-list">';
			$palette = get_the_terms( $attachment, 'cpg_palette' );
			shuffle( $palette );
			foreach ( $palette as $i => $color ) {
				if ( $i == $atts['colors'] ){
   					break;
				}

				if( is_object( $color ) ){
					$color = $color->name;
				}
				$content .= '<li class="cpg__palette-item cpg__color-item" style="background-color:'.$color.';" data-title="'.$color.'"></li>';
			}
			$content .= '</ul>';
		}
	}else{
		$content .= '<p>' . __( "You don't have any palettes. Start generating them in your media library.", "cpg" ) . '</p>';
	}

	$content .= '</div>';

	return $content;
}
add_shortcode( 'colorpalettes', 'cpg_colorpalettes_shortcode' );

//Register taxonomies on install
function cpg_install(){
    cpg_register_default_settings();
    flush_rewrite_rules();
}
register_activation_hook( CPG_DIR, 'cpg_install' );

//Flush rules on deactivate
function cpg_deactivation(){
    flush_rewrite_rules();
}
register_deactivation_hook( CPG_DIR, 'cpg_deactivation' );
