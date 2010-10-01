<?php
/*
Plugin Name: Archive Chart
Plugin URI: http://wiki.campino2k.de/programmierung/wp-plugin-archive-chart
Description: Displays a google api chart for Archive via shortcode
Version: 0.9.2 
Author: Christian Jung
Author URI: http://campino2k.de
Min WP Version: 3.0
 */

function display_archive_chart( $atts ) {

	// this function contains modified code from wp-includes/general-template.php
	// to get some archive options to arrays

	global $wpdb, $wp_locale;

	extract(shortcode_atts(array(
		'name' => 'Posting-H&auml;ufigkeit',
		'width' => '600',
		'height' => '120',
		'count' => '12',
		'linecolor' => '3D7930',
		'fillcolor' => 'C5D4B5',
		'filltrans' => 'BB',
	), $atts));

	$where = apply_filters( 'getarchives_where', "WHERE post_type = 'post' AND post_status = 'publish'", $r );
	$join  = apply_filters( 'getarchives_join', "", $r );

	$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC $limit";
	$key   = md5($query);
	$cache = wp_cache_get( 'wp_get_archives' , 'general');

	if ( !isset( $cache[ $key ] ) ) {
		$arcresults = $wpdb->get_results($query);
		$cache[ $key ] = $arcresults;
		wp_cache_set( 'wp_get_archives', $cache, 'general' );
	} else {
		$arcresults = $cache[ $key ];
	}

	foreach( (array) $arcresults as $arcresult ) {
		$archivemonths[] = $text = sprintf(__('%1$s %2$d'), substr( $wp_locale->get_month($arcresult->month), 0, 3 ) , $arcresult->year );
		$archivecounts[] = $arcresult->posts;
	};

	// cut the "last" n entries, default above is 12
	$archivemonths = array_slice( $archivemonths, 0, esc_attr( $count ) );
	$archivecounts = array_slice( $archivecounts, 0, esc_attr( $count ) );

	// reverse the arrays
	$archivemonths = array_reverse( $archivemonths );
	$archivecounts = array_reverse( $archivecounts );

	//find max val
	$archivemax = max( $archivecounts );
	
	$chart_code =  '<img '.
	'width="' . esc_attr( $width ) . '" '
	. 'height="' . esc_attr($height) . '" '
	. 'alt="" '
	. 'src="http://chart.apis.google.com/chart?'
	// title
	. 'chtt=' . esc_attr( $name ) . '&amp;'
	// fill labels of the x-axis
	. 'chxl=0:|' . join( '|', $archivemonths )  . '&amp;'
	//scale
	. 'chxr=0,0,' . $archivemax . '|1,0,' . ( $archivemax + 1 ) . '&amp;'
#	. 'chxs=0,676767,11.5,0,lt,676767' . '&amp;'
	// select axises
	. 'chxt=x,y&amp;'
	// scaling
	. 'chs=' . esc_attr( $width ) . 'x' . esc_attr( $height ) .'&amp;'
	// chart type
	. 'cht=lc&amp;'
	// chart color (line)
	. 'chco=' . esc_attr( $linecolor ) . '&amp;'
	// fill color marker
	// B, --> FILL path
	// C5D4B5 --> COLOR
	// BB --> TRANSPARENCY
	// 0,0,0 --> PRIORITY
	. 'chm=B,' . esc_attr( $fillcolor) . esc_attr( $filltrans ) . ',0,0,0">';
	// fill data of numbers
	. 'chd=t:' . join( ',', $archivecounts ) . '&amp;'
	// scale
	. 'chds=0,' . $archivemax . '&amp;'
	// funny stuff I don't know
	. 'chg=14.3,-1,1,1&amp;'
	. 'chls=2,4,0&amp;'

	return $chart_code;
}

add_shortcode( 'archive_chart', 'display_archive_chart' );

?>
