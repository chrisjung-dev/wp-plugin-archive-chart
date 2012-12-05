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
*/
add_action( 'init', function() {
// create anonymous instance on init
	new wp_archive_chart();
});
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
			'category' => null,
			'author' => null,
			'post_format' => null,
		), $atts);

		
		$this->get_archive_numbers( $atts, $content, $code );

		$this->draw_archive_chart( $atts, $content, $code );
	}

	/***
	 * Read the filters and return an array of year, month and post count
	 *
	 * http://plugins.trac.wordpress.org/browser/posts-per-month/trunk/magic.php
	 *
	 ***/
	public function get_archive_numbers( $atts, $content, $code ) {

		$latest_post_date = $this->get_latest_post_date();

		$year = $latest_post_date->format( 'Y' );
		$month = $latest_post_date->format( 'm' );

		$count = is_numeric( $atts[ 'count' ]  ) ? $atts[ 'count' ] : 12;
		
		// reset the filter
		unset( $post_filter );

		$post_filter = array(
			'nopaging' => true,
			'post_status' => 'publish',
			'suppress_filters' => false,
		);

		/***
		 * Filter by category
		 *
		 * If category is set, check if it's numeric
		 * get_posts only accepts category ids, but to get some
		 * really convenient usage, translate slug into id.
		 ***/

		$filter_category = esc_attr( $atts[ 'category' ] ); 
		if( $filter_category ) {

			if( ! is_numeric( $filter_category ) )
			{
				$filter_category_value = get_category_by_slug( $filter_category )->term_id;
			} else {
				$filter_category_value = $filter_category;
			}
			$post_filter[ 'category' ] = $filter_category_value;
		}

		/***
		 * Filter authors
		 *
		 ***/
		$filter_author = esc_attr( $atts[ 'author' ] );
		if( $filter_author ) {
			$post_filter[ 'author' ] = $filter_author;
		}




		/***
		 * After applying all filters, query the DB
		 *
		 ***/


		for( $i = $count; $i>0; $i-- ){


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

		$archivemonths = null;
		$archivecounts = null; 
		$archivemax = null;

		$chart_code =  '<img '.
		'width="' . esc_attr( $atts[ 'width' ] ) . '" '
		. 'height="' . esc_attr($atts[ 'height' ] ) . '" '
		. 'alt="" '
		. 'src="http://chart.apis.google.com/chart?'
		// title
		. 'chtt=' . esc_attr( $atts[ 'name'] ) . '&amp;'
		// fill labels of the x-axis
		. 'chxl=0:|' . join( '|', $archivemonths )  . '&amp;'
		//scale
		. 'chxr=0,0,' . ( $archivemax + 1 ) . '|1,0,' . ( $archivemax + 1 ) . '&amp;'
	#	. 'chxs=0,676767,11.5,0,lt,676767' . '&amp;'
		// select axises
		. 'chxt=x,y&amp;'
		// scaling
		. 'chs=' . esc_attr( $atts[ 'width' ] ) . 'x' . esc_attr( $atts[ 'height' ] ) .'&amp;'
		// chart type
		. 'cht=lc&amp;'
		// chart color (line)
		. 'chco=' . esc_attr( $atts[ 'linecolor' ] ) . '&amp;'
		// fill color marker
			// B, --> FILL path
			// C5D4B5 --> COLOR
			// BB --> TRANSPARENCY
			// 0,0,0 --> PRIORITY
		. 'chm=B,' . esc_attr( $atts[ 'fillcolor' ] ) . esc_attr( $atts[ 'filltrans' ] ) . ',0,0,0&amp;'
		// background-color and transparency of the image
		. 'chf=bg,s,' . esc_attr( $atts[ 'bgcolor' ] ) . esc_attr( $atts[ 'bgtrans' ] ) . '&amp;'
		// fill data of numbers
		. 'chd=t:' . join( ',', $archivecounts ) . '&amp;'
		// scale
		. 'chds=0,' . ( $archivemax + 1 ) . '&amp;'
		// line style
		. 'chls=2,4,0&amp;'
		// grid size, line-style of grid
		// (-1) -> automatic, which means: for every data point 1 vertical
		. 'chg=-1,-1,1,1">';

		return $chart_code;
	}

	public function transform_data(){


//		return 
	}

	public function save_data( $year, $month, $count ){
		$this->data[] = array( $year, $month, $count );
	}

	/***
	 * Return the date of the newest post
	 ***/
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
