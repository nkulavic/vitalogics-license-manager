<?php
/* Plugin Name: VitaLogics License Manager
 * Description: Controls license creation and managment for EMR Subscriptions. This integrates with WooCommerce to trigger on specific product purchases.
 * Version: 1.0.0
 * Author: Nick Kulavic
 * Author URI: http://nickkulavic.com
 */

function easy_search($array, $key, $value)
{
    $results = array();
    if (is_array($array)) {
        if (isset($array[$key]) && $array[$key] == $value) {
            $results[] = $array;
        } //isset($array[$key]) && $array[$key] == $value
        foreach ($array as $subarray) {
            $results = array_merge($results, easy_search($subarray, $key, $value));
        } //$array as $subarray
    } //is_array($array)
    return $results;
}

function get_emr_licenses($productId)
{
   $user          = wp_get_current_user();
    $user_meta     = get_user_meta($user->ID);
    $used_licenses = array();
    $count         = 1;
	$billing_company = $user_meta['billing_company'][0];
	$billing_phone = $user_meta['billing_phone'][0];
	$billing_email = $user_meta['billing_email'][0];
	$billing_name = $user_meta['billing_first_name'][0].' '.$user_meta['billing_last_name'][0];
    foreach ($user_meta as $key => $value) {
        if (substr($key, -9) == '_username') {
			echo $key.' - '.$value[0].'<br>';
			//delete_user_meta( $user->ID, $key, $value[0] );
            $count++;
        }
    }
	foreach ($user_meta as $key => $value) {
        if (substr($key, -5) == '_pass') {
			echo $key.' - '.$value[0].'<br>';
			//delete_user_meta( $user->ID, $key, $value[0] );
            $count++;
        }
    }
	$get_tickets = 'https://vitalogics.zendesk.com/api/v2/tickets/show_many.json?ids=';
	foreach ($user_meta as $key => $value) {
        if (substr($key, -10) == '_ticket_id') {
			echo $key.' - '.$value[0].'<br>';
			$get_tickets = $get_tickets.''.$value[0].',';
			//delete_user_meta( $user->ID, $key, $value[0] );
            $count++;
        }
    }
	$api_key     = 'Q95C1HppnK9PCR8wnDuQSMcDbgzcYNt16DjOOpDS';
    $api_user    = 'cesposito@vitalogics.net/token';
	$args     = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($api_user . ':' . $api_key),
            'Content-Type' => 'application/json'
        )
    );
	$tickets = wp_remote_get($get_tickets,$args);
	echo '<pre>';
	print_r(json_decode($tickets['body']));
	echo '</pre>';
}

function create_emr_license()
{
    $user          = wp_get_current_user();
    $user_meta     = get_user_meta($user->ID);
    $used_licenses = array();
    $count         = 0;
	$billing_company = $user_meta['billing_company'][0];
	$billing_phone = $user_meta['billing_phone'][0];
	$billing_email = $user_meta['billing_email'][0];
	$billing_name = $user_meta['billing_first_name'][0].' '.$user_meta['billing_last_name'][0];
	$billing_address = $user_meta['billing_address_1'][0];
	$billing_city = $user_meta['billing_city'][0];
	$billing_state = $user_meta['billing_state'][0];
	$billing_postcode = $user_meta['billing_postcode'][0];
	$install_type = 'emr_cloud_install';
    foreach ($user_meta as $key => $value) {
        if (substr($key, -9) == '_username') {
            $count++;
        }
    }
    $count++;
    $un_license  = 'VL-' . $user->user_lastname . '-' . $user->ID . '-' . $count . '';
    $un_password = wp_generate_password(8, false);
    $url         = 'https://vitalogics.zendesk.com/api/v2/tickets.json';
    $api_key     = 'Q95C1HppnK9PCR8wnDuQSMcDbgzcYNt16DjOOpDS';
    $api_user    = 'cesposito@vitalogics.net/token';
	$html_body = 'License Activation - Install for EMR<br><br><ul>
			<li>
				Client - <strong>'.$billing_name.'</strong></li>
			<li>
				Company - <strong>'.$billing_company.'</strong></li>
			<li>
				Phone - <strong>'.$billing_phone.'</strong></li>
				<li>
				Address
				<ul> <li>Street - <strong>'.$billing_address.'</strong></li>
				<li>City - <strong>'.$billing_city.'</strong></li>
				<li>State - <strong>'.$billing_state.'</strong></li>
				<li>Zip - <strong>'.$billing_postcode.'</strong></li>
				</ul></li>
			<li>
				<strong>Generated Username</strong> - '.$un_license.'</li>
			<li>
				<strong>Generated Password</strong> - '.$un_password.'</li>
		</ul>';
    $ticket      = array(
        'ticket' => array(
			'requester' => array ( 
				'name' => ''.$billing_name.'',
				'email' => ''.$billing_email.'',
				),
            'subject' => 'EMR License #'.$count.' Activation ',
			'type' => 'task',
			'due_at' => ''.date('Y-m-d',strtotime('+2 days')).'',
			'priority' => 'high',
            'comment' => array(
                'html_body' => stripcslashes($html_body),
				'public' => false
            ),
			'custom_fields' => array( 
					array(
					'id' => 22470633,
					'value' => 'install_vitalogics'
					),
					array(
					'id' => 22471863,
					'value' => ''.$billing_company.''
					),
					array(
					'id' => 22380013,
					'value' => ''.$billing_phone.''
					),
					array(
					'id' => 22696409,
					'value' => ''.$install_type.''
					)
				)
        )
    );
    $ticket      = json_encode($ticket);
    $args     = array(
        'body' => '' . $ticket . '',
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($api_user . ':' . $api_key),
            'Content-Type' => 'application/json'
        )
    );
    $response = wp_remote_post($url, $args);
    $ticket    = json_decode($response['body']);
    $ticket_id = $ticket->ticket->id;
    update_user_meta($user->ID, 'emr_license_' . $count . '_username', $un_license);
    update_user_meta($user->ID, 'emr_license_' . $count . '_pass', $un_password);
    update_user_meta($user->ID, 'emr_license_' . $count . '_ticket_id', $ticket_id);  
    return $un_license;
}
function license_page($atts)
{
   get_emr_licenses($productId);
    
}
add_shortcode('license_page', 'license_page');

?>