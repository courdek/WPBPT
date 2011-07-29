<?php 
/*
Plugin Name: Brown Paper Tickets API
Plugin URI: http://rynoweb.com/wordpress-plugins/
Description: Plugin that uses Brown Paper Tickets API for Fair Trade Ticketing Event Listing on WordPress sites
Version: 0.1
Author: VUURR
Author URI: http://vuurr.com
License: GPL2
*/
/*
	Copyright 2011 WordPress Brown Paper Tickets API (email: info@vuurr.com)
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.
	
	This program is distributed in the hope that it will be useful, 
	but WITHOUT ANY WARRANTY; without even the implied warranty of 
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the 
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

define('WPBPT_VERSION', '0.1');

// version check
function wpbpt_url( $path = '' ) {
	global $wp_version;
	if (version_compare( $wp_version, '3.0', '<' )) { // using at least WordPress 3.0?
		$folder = dirname(plugin_basename( __FILE__ ));
		if ('.' != $folder)
			$path = path_join(ltrim($folder, '/'), $path);

		return plugins_url($path);
	}
	return plugins_url($path,__FILE__);
}


// do stuff with brownpapertickets api
function bpt_handler($atts)
	{	
		extract(shortcode_atts(array(
				"event_id" => '',
				"class" => 'bpt_events'
			), $atts));
			
		// get wpbpt db options
		$wpbpt_data = get_option('wpbpt');
		
		$event_data = getBptEvent($event_id,  $wpbpt_data['wpbpt_api_id'], $wpbpt_data['wpbpt_client_name']);
		
		$output = '<div class="'.$class.'">';
		
		foreach($event_data as $event)
		{
			$output .= '<div class="bpt_event">';
			$output .='<span class="bpt_event_title"><a href="'.$event['link'].'" target="_blank">'.$event['title'].'</a></span>';
			$output .= '<ul>';
			foreach($event['dates'] as $date)
			{
				
				$time_raw = strtotime($date['timestart'].' '.$date['datestart']);
				if($time_raw < time()) continue; // If show is in the past, skip to next entry
				$output .= '<li><span class="bpt_event_time">'.date('l, F j, Y \a\t g:i A', $time_raw)."</span></li>";
				
			}
			$output .= '</ul>';
		
			$output .='<span class="bpt_event_link"><a href="'.$event['link'].'" target="_blank">Buy Tickets</a></span>';
			$output .='</div>';
		}
		
		$output .= '</div>';
		
		return $output;
	
	}
	function getBptEvent($event_id='', $api_id, $client_name)
		{
			
			$endpoint = "https://www.brownpapertickets.com/api2/eventlist";

			if(empty($event_id) OR $event_id == null OR $event_id == '')
			{
				$params = array(
					'id' => $api_id,
					'client' => $client_name
				);
			}
			else
			{
				$params = array(
					'id' => $api_id,
					'client' => $client_name,
					'event_id' => $event_id			
				);
			}

			$callback_data = $params;

			$encoded = "";
			foreach($callback_data AS $key=>$value)
			    $encoded .= "$key=".urlencode($value)."&";
			$encoded = substr($encoded, 0, -1);

			//open connection
			$ch = curl_init();

			//set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_URL,$endpoint);
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$encoded);
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,50);
			curl_setopt($ch,CURLOPT_FAILONERROR,TRUE);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			//execute post
			if(FALSE === ($result = curl_exec($ch)))
			{
				//echo "Curl failed with error " . curl_error($ch);
				return;
			}
			// get result code
			$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (intval($responseCode / 100) != 2) 
			{
				//echo 'Error while requesting. Service returned HTTP code '.$responseCode.' and curl error '.curl_error($ch);
				return;
			}

			//close connection
			curl_close($ch);

			$event = new SimpleXMLElement($result, LIBXML_NOWARNING);

			foreach($event->event as $e)
			{
				$event_individual = get_object_vars($e);

				//$e->dates = $this->getBptEventDates($e->event_id);
				$event_individual['dates'] = getBptEventDates($e->event_id, $api_id);

				$output[] = $event_individual;
			}

			return $output;
		}


		function getBptEventDates($event_id, $api_id)
		{
			$endpoint = "https://www.brownpapertickets.com/api2/datelist";

				$params = array(
					'id' => $api_id,
					'event_id' => $event_id			
				);

			$callback_data = $params;

			$encoded = "";
			foreach($callback_data AS $key=>$value)
			    $encoded .= "$key=".urlencode($value)."&";
			$encoded = substr($encoded, 0, -1);

			//open connection
			$ch = curl_init();

			//set the url, number of POST vars, POST data
			curl_setopt($ch,CURLOPT_URL,$endpoint);
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_USERPWD, TWILIO_TOKEN.":X");
			curl_setopt($ch,CURLOPT_POSTFIELDS,$encoded);
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 50);
			curl_setopt($ch,CURLOPT_FAILONERROR, TRUE);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER  ,1);
			//execute post
			if(FALSE === ($result = curl_exec($ch)))
			{
				// echo "Curl failed with error " . curl_error($ch);
				return;
			}
			// get result code
			$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (intval($responseCode / 100) != 2) 
			{
				// echo 'Error while requesting. Service returned HTTP code '.$responseCode.' and curl error '.curl_error($ch);
				return;
			}

			//close connection
			curl_close($ch);

			$date = new SimpleXMLElement($result, LIBXML_NOWARNING);

			foreach($date->date as $d)
			{

				$dates[] = get_object_vars($d);

			}

			return $dates;

		}

	add_shortcode('bpt', 'bpt_handler');

	add_action('admin_init','wpbpt_init');
	add_action('admin_menu','wpbpt_add_page');

	// register settings and sanitization callback
	function wpbpt_init() {
		register_setting('wpbpt_options','wpbpt','wpbpt_validate');
	}

	// add admin page to menu
	function wpbpt_add_page() {
		add_options_page('Brown Paper Tickets Events API','BPT Options','manage_options','wpbpt','wpbpt_buildpage');
	}

	// build admin page
	function wpbpt_buildpage() {
?>

<div class="wrap">
	<h2>Brown Paper Tickets API <em>v<?php echo WPBPT_VERSION; ?></em></h2>
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div id="side-info-column" class="inner-sidebar">
				<div class="meta-box-sortables">
					<div id="about" class="postbox">
						<h3 class="hndle" id="about-sidebar">About the Plugin:</h3>
						<div class="inside">
							<p>Built in a few hours at Startup Weekend Chandler AZ, 25 June 2011 by Chuck &amp; Jonathan <a href="http://twitter.com/vuurr" target="_blank">@VUURR</a>. Praise and/or support via liking our <a href="http://facebook.com/vuurr" target="_blank">Facebook</a> page.</p>
							<p>Thank <a href="http://www.thetorchtheatre.com" target="_blank">TheTorchTheatre</a> in Phoenix AZ for inspiring us to build this.</p>
						</div>
					</div>
				</div>
			</div> <!-- // #side-info-column .inner-sidebar -->


			<div id="post-body" class="has-sidebar">
				<div id="post-body-content" class="has-sidebar-content">
					<div id="normal-sortables" class="meta-box-sortables">
						<div class="postbox">
							<h3 class="hndle">BPT Settings:</h3>
							<div class="inside">
								<form method="post" action="options.php">
									<?php settings_fields('wpbpt_options'); ?>
									<?php $options = get_option('wpbpt'); ?>

								<table class="form-table">
									<tr valign="top">
										<th scope="row">BPT API ID:</th>
										<td><input type="text" name="wpbpt[wpbpt_api_id]" value="<?php echo $options['wpbpt_api_id']; ?>" class="regular-text" /></td>
									</tr>
									<tr valign="top">
										<th scope="row">BPT Client Name:</th>
										<td><input type="text" name="wpbpt[wpbpt_client_name]" value="<?php echo $options['wpbpt_client_name']; ?>" class="regular-text" /></td>
									</tr>
								</table>

								<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
								</form>
								<br class="clear" />
								
						</div>
					</div>
				</div>
				<div id="normal-sortables" class="meta-box-sortables">
					<div class="postbox">
						<h3 class="hndle">How to Use:</h3>
						<div class="inside">
							<p>Use <strong>[bpt]</strong> in a page or post to display all events you have setup in BPT.</p>
							<p>Use <strong>[bpt event_id="<em>youreventnumber</em>"]</strong> to display a single event in a post or page. <br />
								<em>exp: [bpt event_id="123456"]</em></p>
							<p>CSS Classes to style the output:<br/>
<pre>
	.bpt_events
		.bpt_event
			.bpt_event_title
				UL, LI, .bpt_event_time
			.bpt_event_link
</pre>
						</div>
					</div>
				</div>
			</div>
		</div>
		</div>
	</div>
	<?php	
	}

	function wpbpt_validate($input) {
		$input['wpbpt_api_id'] = wp_filter_nohtml_kses($input['wpbpt_api_id']);
		$input['wpbpt_client_name'] = wp_filter_nohtml_kses($input['wpbpt_client_name']);
		return $input;
	}
?>