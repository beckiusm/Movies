<?php
/*
	Plugin Name: movies
	Plugin URI:  http://link to your plugin homepage
	Description: Describe what your plugin is all about in a few short sentences
	Version:     1.0
	Author:      Mathias Beckius
	Author URI:  http://link to your website
	License:     GPL2 etc
	License URI: http://link to your plugin license
*/

/**
 * Register movie custom post type
 */


function cptui_register_my_cpts_movie() {

	/**
	 * Post Type: movies.
	 */

	$labels = array(
		'name'          => __( 'movies', 'twentytwenty' ),
		'singular_name' => __( 'Movie', 'twentytwenty' ),
	);

	$args = array(
		'label'                 => __( 'movies', 'twentytwenty' ),
		'labels'                => $labels,
		'description'           => '',
		'public'                => true,
		'publicly_queryable'    => true,
		'show_ui'               => true,
		'show_in_rest'          => true,
		'rest_base'             => '',
		'rest_controller_class' => 'WP_REST_Posts_Controller',
		'has_archive'           => false,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => true,
		'delete_with_user'      => false,
		'exclude_from_search'   => false,
		'capability_type'       => 'post',
		'map_meta_cap'          => true,
		'hierarchical'          => false,
		'rewrite'               => array(
			'slug'       => 'movie',
			'with_front' => true,
		),
		'query_var'             => true,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
		'taxonomies'            => array( 'category', 'post_tag' ),
	);

	register_post_type( 'movie', $args );
}

add_action( 'init', 'cptui_register_my_cpts_movie' );

/**
 * Add custom fields for movie post type
 */

function wporg_add_custom_box() {
	add_meta_box(
		'wporg_box_id',           // Unique ID
		'IMDB id',  // Box title
		'wporg_custom_box_html',  // Content callback, must be of type callable
		'movie', // Post type
	);
}
add_action( 'add_meta_boxes', 'wporg_add_custom_box' );

/**
 * Save fields in database
 */

function wporg_save_postdata( $post_id ) {
	if ( ! empty( $_POST['imdb_id'] ) ) {
		$id    = sanitize_text_field( $_POST['imdb_id'] );
		$movie = imdb_api( $id );
		update_post_meta(
			$post_id,
			'imdb_id',
			$id,
		);
		update_post_meta(
			$post_id,
			'actors',
			$movie->Actors,
		);
		update_post_meta(
			$post_id,
			'year',
			$movie->Year,
		);
		$post_update = array(
			'ID'           => $post_id,
			'post_title'   => $movie->Title,
			'post_content' => $movie->Plot,
		);
		wp_update_post( $post_update );
	}
}
add_action( 'save_post', 'wporg_save_postdata' );

/**
 * HTML for custom fields
 */

function wporg_custom_box_html( $post ) {
	$value = get_post_meta( $post->ID, 'imdb_id', true );
	?>
	<input name="imdb_id" id="imdb_id" value="<?php echo esc_html( $value ); ?>">
	<?php
}

/**
 * IMDB api
 */

function imdb_api( $id ) {
	$movie = file_get_contents( "http://www.omdbapi.com/?i=$id&apikey=5a10c86c" );
	return json_decode( $movie );
}

/**
 * Add stuff to the_content
 */

function custom_content( $content ) {
	if ( is_home() || is_single() ) {
		$id = get_post_meta( get_the_ID(), 'imdb_id' )[0];
		if ( strlen( $id ) > 0 ) {
			$img      = plugin_dir_url( __FILE__ ) . 'imdb2.png';
			$year     = mb_substr( get_post_meta( get_the_ID(), 'year' )[0], 0, -1 );
			$actors   = get_post_meta( get_the_ID(), 'actors' )[0];
			$content .= "<p>Released: $year</p>";
			$content .= "<p>Actors: $actors</p>";
			$content .= "<p><img class='imdb-image' src='$img' alt='imdb icon'></p>";
		}
		if ( function_exists( 'the_ratings' ) ) {
			$content .= the_ratings();
		}
	}
	return $content;
}

add_filter( 'the_content', 'custom_content' );

/**
 * Modify main loop to display cpt, sort by rating
 */

function query_post_type( $query ) {
	if ( ( is_search() || is_home() || is_category() || is_tag() ) && ! is_admin() ) {
		$query->set( 'post_type', 'movie' );
		$query->set(
			'meta_query',
			array(
				'relation' => 'OR',
				array(
					'key'     => 'ratings_average',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'ratings_average',
					'compare' => 'EXISTS',
				),
			)
		);
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'order', 'DESC' );
		return $query;
	}
}
add_filter( 'pre_get_posts', 'query_post_type' );

/**
 * Add CSS
 */

function utm_user_scripts() {
	$plugin_url = plugin_dir_url( __FILE__ );
	wp_enqueue_style( 'movies', $plugin_url . '/style.css' );
}

add_action( 'wp_enqueue_scripts', 'utm_user_scripts' );
