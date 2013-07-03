<?php
	/*
		Plugin Name: Simplelest-Seo
		Description: Little Plugin to create simple SEO fields for the Title, the Description and Robots
		Version: 0.1
		Author: Richard Thiel
		Author URI: www.richardthiel.de
	*/

  add_action( 'add_meta_boxes', 'simpleSeo' );
  add_action( 'save_post', 'simpleSeoSave' );
  add_filter( 'wp_title', 'simpleSeoTitle', 10, 2 );
  add_action( 'wp_head', 'simpleSeoTags');

  function simpleSeo() {
    $screens = array( 'post', 'page' );
    foreach ($screens as $screen) { add_meta_box('simpleseo', 'Simple-SEO', 'createSEOBox', $screen, 'side'); }
  }

  /* Prints the box content */
  function createSEOBox( $post ) {

  	wp_nonce_field( plugin_basename( __FILE__ ), 'nonceTheSimpleSEO' );
    wp_enqueue_style('simpleseo-css',plugins_url('/simple-seo.css', __FILE__));

    $simpleseo['title'] = get_post_meta( $_GET['post'], 'simpleseoTitle', true );
    $simpleseo['description'] = get_post_meta( $_GET['post'], 'simpleseoDescription', true );
    $simpleseo['robots'] = get_post_meta( $_GET['post'], 'simpleseoRobots', true );
  ?>
  <div class="box">
    <input type="text" placeholder="SEO Titel" value="<?php echo $simpleseo['title']; ?>" name="simpleseoTitle" />
    <p>Geben Sie hier den SEO-Titel der Seite an</p>
  </div>
  <div class="box">
    <textarea placeholder="SEO Beschreibung" name="simpleseoDescription"><?php echo $simpleseo['description']; ?></textarea>
    <p>Geben Sie hier Ihren Text für die Meta-Description dieser Seite ein.</p>
  </div>
  <div class="box">
    <label><input name="simpleseoRobots" type="radio"<?php if(isset($simpleseo['robots']) && $simpleseo['robots'] == 1){ echo ' checked="checked"'; } ?> value="1"> Verstecken</label>
    <label><input name="simpleseoRobots" type="radio"<?php if(isset($simpleseo['robots']) && $simpleseo['robots'] == 0){ echo ' checked="checked"'; } ?> value="0"> Anzeigen</label>
    <p>Wählen Sie aus, ob die Seite von Google gefunden werden soll</p>
  </div>
  <?php
  }

  function simpleSeoSave($post_id) {
    if('page' == $_POST['post_type']){ if(!current_user_can('edit_page',$post_id)){ return; } }else{   if(!current_user_can('edit_post',$post_id)){ return; } }
    if(!isset($_POST['nonceTheSimpleSEO']) || !wp_verify_nonce($_POST['nonceTheSimpleSEO'], plugin_basename(__FILE__))){ return; }
    $post_ID = $_POST['post_ID'];
    $simpleseoTitleData = sanitize_text_field( $_POST['simpleseoTitle'] );
    $simpleseoDescriptionData = sanitize_text_field( $_POST['simpleseoDescription'] );
    $simpleseoRobotsData = sanitize_text_field( $_POST['simpleseoRobots'] );
    add_post_meta($post_ID, 'simpleseoTitle', $simpleseoTitleData, true) or update_post_meta($post_ID, 'simpleseoTitle', $simpleseoTitleData);
    add_post_meta($post_ID, 'simpleseoDescription', $simpleseoDescriptionData, true) or update_post_meta($post_ID, 'simpleseoDescription', $simpleseoDescriptionData);
    add_post_meta($post_ID, 'simpleseoRobots', $simpleseoRobotsData, true) or update_post_meta($post_ID, 'simpleseoRobots', $simpleseoRobotsData);
  }



  function simpleSeoTitle( $title, $sep ) {
    global $paged, $page, $post;
    $postID = $post->ID;
    $site_description = get_bloginfo( 'description', 'display' );
    if ( $site_description && ( is_home() || is_front_page() ) ){ return $title = get_bloginfo( 'name' )." $sep $site_description"; }
    if ( $paged >= 2 || $page >= 2 ){ $title = "$title $sep " . sprintf( __( 'Page %s', 'twentytwelve' ), max( $paged, $page ) ); }
    if(is_feed()){ return $title; }
    if(is_page()){
      if(get_post_meta( $postID, 'simpleseoTitle', true ) != '') {
        $title = get_post_meta( $postID, 'simpleseoTitle', true );
      }
    }else{
      if(get_post_meta( $postID, 'simpleseoTitle', true ) != '') {
        $title = get_post_meta( $postID, 'simpleseoTitle', true );
      }else{
        $title = get_bloginfo( 'name' ).$title;
      }
    }
    return $title;
  }



function simpleSeoTags() {
    global $paged, $page, $post;
    $postID = $post->ID;

    $do_add_metadata = true;

    $metadata_arr = array();
    $metadata_arr[] = "";
    $metadata_arr[] = "<!-- BEGIN Metadata -->";

    // Check for NOINDEX,FOLLOW on archives.
    // There is no need to further process metadata as we explicitly ask search
    // engines not to index the content.
    if ( is_archive() || is_search() ) {
        if (
            is_search()  ||          // Search results
            is_date()  ||             // Date and time archives
            is_category()  ||     // Category archives
            is_tag() ||               // Tag archives
            is_author()             // Author archives
        ) {
            $metadata_arr[] = '<meta name="robots" content="noindex,nofollow" />';
            $do_add_metadata = false;   // No need to process metadata
        }
    }else{
        if(get_post_meta( $post->ID, 'simpleseoRobots', true ) == 1){
          $metadata_arr[] = '<meta name="robots" content="noindex,nofollow" />';
          $do_add_metadata = false;   // No need to process metadata
        }
    }


    // Add Metadata
    if ($do_add_metadata) {
        // Basic Meta tags
        $metadata_arr[] = '<meta name="robots" content="index,follow" />';
        if(get_post_meta( $post->ID, 'simpleseoDescription', true ) != ''){
          $metadata_arr[] = '<meta name="description" content="'.get_post_meta( $post->ID, 'simpleseoDescription', true ).'" />';
        }
    }
    $metadata_arr[] = "<!-- END Metadata -->";
    $metadata_arr[] = "";
    $metadata_arr[] = "";

    echo implode("\n", $metadata_arr);
}
?>
