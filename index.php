<?php
/*
Plugin Name: Archive Chart
Plugin URI: http://campino2k.de
Description: Displays a google api chart for Archive via Shortcode
Version: 1.0
Author: Christian Jung
Author URI: http://campino2k.de
Min WP Version: 1.5
 */

function display_archive_chart( $atts ) {

	// this function contains modified code from wp-includes/general-template.php
	// to get some archive options to arrays

	global $wpdb, $wp_locale;

	extract(shortcode_atts(array(
		'name' => 'Posting-H&auml;ufigkeit',
		'width' => '600',
		'count' => '12',
	), $atts));

	$where = apply_filters('getarchives_where', "WHERE post_type = 'post' AND post_status = 'publish'", $r );
	$join = apply_filters('getarchives_join', "", $r);

	$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC $limit";
	$key = md5($query);
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
	. 'height="107" '
	. 'alt="" '
	. 'src="http://chart.apis.google.com/chart?'
	// title
	. 'chtt=' . esc_attr( $name ) . '&amp;'
	// fill labels of the x-axis
	. 'chxl=0:|' . join( '|', $archivemonths )  . '&amp;'
	//scale
	. 'chxr=0,0,'.($archivemax).'|1,0,' . ($archivemax + 1) . '&amp;'
#	. 'chxs=0,676767,11.5,0,lt,676767' . '&amp;'
	// select axises
	. 'chxt=x,y&amp;'
	// scaling
	. 'chs='.$width.'x107&amp;'
	// chart type
	. 'cht=lc&amp;'
	. 'chco=3D7930&amp;'
	// fill data of numbers
	. 'chd=t:' . join( ',', $archivecounts ) . '&amp;'
	// scale
	. 'chds=0,' . ($archivemax) . '&amp;'
	// funny stuff I don't know
	. 'chg=14.3,-1,1,1&amp;'
	. 'chls=2,4,0&amp;'
	. 'chm=B,C5D4B5BB,0,0,0">';

	return $chart_code;
}

add_shortcode('archive_chart', 'display_archive_chart');

?>
