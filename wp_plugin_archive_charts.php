<?php
/*
Plugin Name: WP Archive Chart
Plugin URI: http://wiki.campino2k.de/programmierung/wp-plugin-archive-chart
Author: Christian Jung
Author URI: http://campino2k.de
Description: Displays a google api chart for Archive via shortcode
Version: 1.0
Min WP Version: 3.0
 */

/**
* create instance of archive chart  at init action
* needs to be be before print_styles since we add some own styles
* /
add_action( 'init', function() {
// create anonymous instance on init
	new wp_archive_chart();
});
*/
class wp_archive_chart {

	private $data;

	public function __construct() {
		/*
		* Add shortcode function
		*/
		add_shortcode( 'archive_chart', array( $this, 'execute_archive_shortcode' ) );
		
		/*
		* Add Custom Links to Plugin information
		*/
		add_filter( 'plugin_row_meta', array( $this, 'init_meta_links' ),	10,	2 );
	}

	public function execute_archive_shortcode( $atts, $content=null, $code="" ) {

		$atts = shortcode_atts(array(
			'name' => 'posting frequency',
			'width' => '600',
			'height' => '120',
			'count' => 12,
			'linecolor' => '3D7930',
			'fillcolor' => 'C5D4B5',
			'filltrans' => 'BB',
			'bgcolor' => 'FFFFFF',
			'bgtrans' => '',
			'category' => '',
			'author' => '',
			'post_format' => '',
		), $atts);

		
		$this->get_archive_numbers( $atts, $content, $code );
	}

	/***
	 * Read the filters and return an array of year, month and post count
	 *
	 * http://plugins.trac.wordpress.org/browser/posts-per-month/trunk/magic.php
	 *
	 */
	public function get_archive_numbers( $atts, $content, $code ) {

		$latest_post_date = $this->get_latest_post_date();

		$year = $latest_post_date->format( 'Y' );
		$month = $latest_post_date->format( 'm' );

		$count = is_numeric( $atts[ 'count' ]  ) ? $atts[ 'count' ] : 12;
		

		for( $i = $count; $i>0; $i-- ){

			// reset the filter
			unset( $post_filter );

			$post_filter = array(
				'nopaging' => true,
				'post_status' => 'publish',
				'suppress_filters' => false,
			);

			if( $atts[ 'category' ] ) {
				$post_filter[ 'category' ] = $atts[ 'category' ];
			}

			$post_filter[ 'year' ] = $year;
			$post_filter[ 'monthnum' ] = $month;
			
			$posts = get_posts(
				$post_filter
			);

			$post_num = count( $posts );

			$this->save_data( $year, $month, $post_num );

			if( $month == 1 ) {
				$month = 12;
				$year--;
			} else {
				$month--;
			}
		}

	}

	public function draw_archive_chart( $atts, $content, $code ) {

	}

	public function save_data( $year, $month, $count ){
		$this->data[] = array( $year, $month, $count );
		echo  $year .'-'. $month. ':'. $count.'<br>'; 
	}

	/***
	 * Return the date of the newest post
	 */
	public function get_latest_post_date() {
		$posts = get_posts( array(
			'post_status' => 'publish',
			'order' => 'DESC',
		));
		$latest = $posts[0];

		$datetime = new DateTime( $latest->post_date );
		return $datetime;
	}

	public function init_meta_links( $links, $file ) {
		if( plugin_basename( __FILE__) == $file ) {
			return array_merge(
				$links,
				array(
					sprintf(
						'<a href="https://flattr.com/thing/66415/WordPress-Archive-Chart-Plugin" target="_blank">%s</a>',
						esc_html__('Flattr')
					)
				)
			);
		}
		return $links;
	}
};
$wp_archive_chart = new wp_archive_chart();
