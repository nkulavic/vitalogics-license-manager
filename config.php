<?php
/* Plugin Name: VitaLogics License Manager
 * Description: Controls license creation and managment for EMR Subscriptions. This integrates with WooCommerce to trigger on specific product purchases.
 * Version: 1.0.0
 * Author: Nick Kulavic
 * Author URI: http://nickkulavic.com
 */

function easy_convert($d)
{
    if (is_object($d)) {
        // Gets the properties of the given object
        // with get_object_vars function
        $d = get_object_vars($d);
    }
    
    if (is_array($d)) {
        /*
         * Return array converted to object
         * Using __FUNCTION__ (Magic constant)
         * for recursive call
         */
        return array_map(__FUNCTION__, $d);
    } else {
        // Return array
        return $d;
    }
}

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
    $user            = wp_get_current_user();
    $user_meta       = get_user_meta($user->ID);
    $used_licenses   = array();
    $count           = 1;
    $billing_company = $user_meta['billing_company'][0];
    $billing_phone   = $user_meta['billing_phone'][0];
    $billing_email   = $user_meta['billing_email'][0];
    $billing_name    = $user_meta['billing_first_name'][0] . ' ' . $user_meta['billing_last_name'][0];
	$app = 'nickkulavictrial';
    $get_tickets     = 'https://'.$app.'.zendesk.com/api/v2/tickets/show_many.json?ids=';
    foreach ($user_meta as $key => $value) {
        if (substr($key, -10) == '_ticket_id') {
            $get_tickets = $get_tickets . '' . $value[0] . ',';
             //delete_user_meta( $user->ID, $key, $value[0] );
        }
		if (substr($key, -9) == '_username') {
            //$get_tickets = $get_tickets . '' . $value[0] . ',';
             //delete_user_meta( $user->ID, $key, $value[0] );
        }
		if (substr($key, -5) == '_pass') {
            //$get_tickets = $get_tickets . '' . $value[0] . ',';
             //delete_user_meta( $user->ID, $key, $value[0] );
        }
    }
    $api_key  = 'CXvHgIQ8yQPnZpILTZTIM4KzcyW47BXcL9N6Sdww';
    $api_user = 'nick+trial@nickkulavic.com/token';
    $args     = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($api_user . ':' . $api_key),
            'Content-Type' => 'application/json'
        )
    );
    $tickets  = wp_remote_get($get_tickets, $args);
    $tickets  = easy_convert(json_decode($tickets['body']));
    $tickets  = $tickets['tickets'];
    //echo '<pre>';
    //print_r($tickets);
    //echo '</pre>';
    $count    = 1;
	if($_GET['new_license'] === 'created') {
		echo '<style type="text/css">
		div.license_created {
    color: #00bd26;
    background-color: #e8e8e8;
    padding: 15px;
    text-align: center;
    margin-bottom: 15px;
}</style>';
	echo '<div class="license_created">New user license was successfully created. View the status below.</div>';
	}
    echo '<table width="100%" border="1" cellpadding="5">
  <tbody>
    <tr>
      <th scope="col">Username</th>
      <th scope="col">Password</th>
      <th scope="col">Status</th>
    </tr>';
    foreach ($user_meta as $key => $value) {
        if (substr($key, -10) == '_ticket_id') {
            $username = $user_meta['emr_license_' . $count . '_username'][0];
            $password = $user_meta['emr_license_' . $count . '_pass'][0];
			$ticket_id = $user_meta['emr_license_' . $count . '_ticket_id'][0];
            // echo $user_meta['emr_license_'.$count.'_ticket_id'][0];
            // echo '<br>';
            $search   = easy_search($tickets, 'id', $user_meta['emr_license_' . $count . '_ticket_id'][0]);
            $status   = $search[0]['status'];
            if ($status === 'open' || $status === 'hold') {
                $status = '<a class="status" style="color: #ffac1c; border-bottom: none;" href="https://'.$app.'.zendesk.com/hc/requests/'.$ticket_id.'" target="_blank">Processing</a>';
            }
			if ($status === 'pending') {
                $status = '<a class="status" style="color: #ff0000; border-bottom: none;" href="https://'.$app.'.zendesk.com/hc/requests/'.$ticket_id.'" target="_blank">Needs Your Attention</a>';
            }
            if ($status === 'solved' || $status === 'closed') {
                $status = '<a class="status" style="color: #1bc900; border-bottom: none;" href="https://'.$app.'.zendesk.com/hc/requests/'.$ticket_id.'" target="_blank">Active</a>';
            }
            echo '
    <tr>
      <td>' . $username . '</td>
      <td>' . $password . '</td>
      <td>' . $status . '</td>
    </tr>';
            $count++;
        }
        
    }
	echo '</tbody>
</table>';
$link = get_permalink();
if($_GET['new_license'] !== 'true') {
echo '<a href="'.$link.'?new_license=true"><button class="alignright" >Create Additional License</button></a>';
}
}

function create_emr_license()
{
    $user             = wp_get_current_user();
    $user_meta        = get_user_meta($user->ID);
    $used_licenses    = array();
    $count            = 0;
    $billing_company  = $user_meta['billing_company'][0];
    $billing_phone    = $user_meta['billing_phone'][0];
    $billing_email    = $user_meta['billing_email'][0];
    $billing_name     = $user_meta['billing_first_name'][0] . ' ' . $user_meta['billing_last_name'][0];
    $billing_address  = $user_meta['billing_address_1'][0];
    $billing_city     = $user_meta['billing_city'][0];
    $billing_state    = $user_meta['billing_state'][0];
    $billing_postcode = $user_meta['billing_postcode'][0];
    $install_type     = 'emr_cloud_install';
    foreach ($user_meta as $key => $value) {
        if (substr($key, -9) == '_username') {
            $count++;
        }
    }
    $count++;
    $un_license  = 'VL-' . $user->user_lastname . '-' . $user->ID . '-' . $count . '';
    $un_password = wp_generate_password(8, false);
    $url         = 'https://nickkulavictrial.zendesk.com/api/v2/tickets.json';
    $api_key     = 'CXvHgIQ8yQPnZpILTZTIM4KzcyW47BXcL9N6Sdww';
    $api_user    = 'nick+trial@nickkulavic.com/token';
    $html_body   = 'License Activation - Install for EMR<br><br><ul>
			<li>
				Client - <strong>' . $billing_name . '</strong></li>
			<li>
				Company - <strong>' . $billing_company . '</strong></li>
			<li>
				Phone - <strong>' . $billing_phone . '</strong></li>
				<li>
				Address
				<ul> <li>Street - <strong>' . $billing_address . '</strong></li>
				<li>City - <strong>' . $billing_city . '</strong></li>
				<li>State - <strong>' . $billing_state . '</strong></li>
				<li>Zip - <strong>' . $billing_postcode . '</strong></li>
				</ul></li>
			<li>
				<strong>Generated Username</strong> - ' . $un_license . '</li>
			<li>
				<strong>Generated Password</strong> - ' . $un_password . '</li>
		</ul>';
    $ticket      = array(
        'ticket' => array(
            'requester' => array(
                'name' => '' . $billing_name . '',
                'email' => '' . $billing_email . ''
            ),
            'subject' => 'EMR License #' . $count . ' Activation ',
            'type' => 'task',
            'due_at' => '' . date('Y-m-d', strtotime('+2 days')) . '',
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
                    'value' => '' . $billing_company . ''
                ),
                array(
                    'id' => 22380013,
                    'value' => '' . $billing_phone . ''
                ),
                array(
                    'id' => 22696409,
                    'value' => '' . $install_type . ''
                )
            )
        )
    );
    $ticket      = json_encode($ticket);
    $args        = array(
        'body' => '' . $ticket . '',
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($api_user . ':' . $api_key),
            'Content-Type' => 'application/json'
        )
    );
    $response    = wp_remote_post($url, $args);
    $ticket      = json_decode($response['body']);
    $ticket_id   = $ticket->ticket->id;
    update_user_meta($user->ID, 'emr_license_' . $count . '_username', $un_license);
    update_user_meta($user->ID, 'emr_license_' . $count . '_pass', $un_password);
    update_user_meta($user->ID, 'emr_license_' . $count . '_ticket_id', $ticket_id);
    return $un_license;
}
function license_page($atts)
{
    get_emr_licenses($productId);
	if($_GET['new_license'] === 'true') {	
    create_emr_license();
	$link = get_permalink();
	echo '<script type="text/javascript">
window.location = "'.$link.'?new_license=created";
</script>';
	}
    
}
add_shortcode('license_page', 'license_page');

?>
