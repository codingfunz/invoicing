<?php

// initialize mailjet
require __DIR__.'/vendor/autoload.php';
use \Mailjet\Resources;

session_start();

## SET CONSTANTS ##
define('INVOICING',		'ready');
define('INVOICE_DIR',	__DIR__.'/invoice');
define('TIMEZONE',		get(config()->config_page_timezone));
define('CONFIG_FILE',		__DIR__.'/_set_configs.json');
define('NL', "\n");

// confirm there is an invoice
$invoice_exist=false;
if( isset($_GET['client']) && file_exists(INVOICE_DIR.'/'.$_GET['client'].'.json') )
	$invoice_exist=true;

// payment complete
$complete=false;
if( isset($_GET['payment_status']) && $_GET['payment_status'] == 'Completed' )
	$complete=true;


function invoice() 
{
	date_default_timezone_set(TIMEZONE);
	if( isset($_GET['client']) ) 
	{
		if( !file_exists(INVOICE_DIR.'/'.$_GET['client'].'.json') )
			return;
		
		$invdata = file_get_contents(INVOICE_DIR.'/'.$_GET['client'].'.json');
		$invdata = json_decode($invdata);
		
		$detail='';
		if( !empty($invdata->item_detail) ) {
			$det = explode('---',$invdata->item_detail);
			$dets=[];
			foreach($det as $v) {
				$dets[] = '<li>'.$v.'</li>';
			}
			$detail = '<h4 class="mt40">Service Detail</h4><ol>'.implode($dets).'</ol>';
		}
		
		$data = [
		'client' => '<h4>'.ucwords($invdata->client_name).'</h4>',
		'number' => '<div class="mb10">Invoice: '.$invdata->inv_number.' '.date('F d, Y',strtotime($invdata->inv_date)).'</div>', 
		'amt' => $invdata->item_cost,
		'dollar' => number_format($invdata->item_cost, 2, '.', ''),
		'item' => $invdata->item_name,
		'note' => (!empty($invdata->item_note) ? '<div class="note">'.str_replace(PHP_EOL,'<br/>',$invdata->item_note).'</div>':''),
		'detail' => $detail,
		'paid' => (isset($invdata->item_paid) ? true:false)
		];
	
		return (object)$data;
	}
}

function edit()
{
	if( isset($_GET['do_invoice']) && isset($_GET['edit']) ) 
	{
		$v = file_get_contents(INVOICE_DIR.'/'.$_GET['edit']);
		$v = json_decode($v);
		
		return $v;
	}else{
		return (new stdClass());
	}
}

function config() 
{
	$config_file = __DIR__.'/_set_configs.json';
	$configs = file_get_contents($config_file);
	$configs = json_decode($configs, true);
	
	$ftype = (!empty($configs['config_paypal_funding']) ? $configs['config_paypal_funding'] : '');
	$ftypes = ['card','paylater','credit','venmo'];
	
	if( !empty($ftype) ) {
		$configs['disable'] = array_diff($ftypes,$configs['config_paypal_funding']);
	}
	
	return makeobj($configs);
}

// stuff formatted debug
function dump($var='', $myip='47.198.32.86') {
	if( empty($var) )
		return;
	
	$out = print_r($var, true);
	
	$devip=[];
	if( !empty($myip) ) {
		$devip[] = $myip;
	}

	echo '<pre>'.$out.'</pre>';
}

function bodyClass() 
{
	$class=[];
	if( isset($_GET['do_configs']) || isset($_GET['do_invoice']) )
		$class[] = 'admin';
	if( isset($_GET['do_configs']) )
		$class[] = 'configs';
	if( isset($_GET['do_invoice']) )
		$class[] = 'make-invoice';
	
	$attrib = 'class="'.implode(' ',$class).'"';
	
	return $attrib;
}


function smartCheckout($params=[])
{
	$v = json_decode(json_encode($params));
	$_SESSION['uip'] = 'user'.vip();
	$clid = config()->config_paypal_key;
	$sbid = config()->config_paypal_sb_key;
	$returnUrl = config()->config_paypal_return;
	
	if( !empty(config()->config_paypal_sb_ip) ) {
		$iparray = explode(',',config()->config_paypal_sb_ip);
		if( in_array(vip(),$iparray) && browserCheck('Firefox') )
			$clid = $sbid;
	}
	
	$item = [
	'name'=>(isset($v->items_name) && !empty($v->items_name) ? $v->items_name:''),
	'desc'=>(isset($v->items_description) && !empty($v->items_description) ? $v->items_description:'')
	];
	$currency_code = config()->config_paypal_currency;

	$urlquery = [
	'client-id' => $clid,
	'currency' => strtoupper($currency_code),
	'enable-funding' => implode(',',(array)config()->config_paypal_funding)
	];
	
	$disables = (array)config()->disable;
	if( count($disables) != '' )
		$urlquery['disable-funding'] = implode(',',$disables);
	
	$success_form = '';
	
	$html = '
	<div id="smart-button-container">
		<div class="pp-buttons">
			<div id="paypal-button-container"></div>
		</div>
	</div>';

	$js = '
	<script src="https://www.paypal.com/sdk/js?'.http_build_query($urlquery).'"></script>
	<script>
	let po = document.querySelector("input[name=amount_field]");
	let amnt = po.value;
	
	po.addEventListener("input", function(event) {
		if( "" !== event.target.value )
			amnt = event.target.value;
	});
	
	/*document.querySelectorAll("input[name=pwyw]").forEach((elem) => {
		elem.addEventListener("click", function(event) {
			amnt = event.target.value;
		});
	});*/
	
	var ref_id = "'.get(edit()->inv_number).'-'.$_SESSION['uip'].'-'.time().'";
	console.log(amnt);
	function initPayPalButton() 
	{
		paypal.Buttons({
			style: {
			shape: "'.config()->config_paypal_btnshape.'",
			color: "'.config()->config_paypal_btncolor.'",
			layout: "'.config()->config_paypal_btnlayout.'",
			label: "checkout",
			height: '.config()->config_paypal_btnheight.',
			tagline: false
			},

			createOrder: function(data, actions) {
				return actions.order.create({
					purchase_units: [{
						"reference_id":ref_id,
						"description":"'.$item['desc'].'",
						"item":{
							"name":"'.$item['name'].'",
							"description":"'.$item['desc'].'",
							"category": "DIGITAL_GOODS",
						},
						"unit_amount": {
							"currency_code": "'.$currency_code.'",
							"value": amnt
						},
						"amount":{
							"currency_code":"'.$currency_code.'",
							"value": amnt
						}
					}]
				});
			},

			onApprove: function(data, actions) {
				return actions.order.capture().then(function(orderData) {
					const pu = orderData.purchase_units[0];
					const transaction = pu.payments.captures[0];
					const cust = orderData.payer.name.given_name;
					const element = document.getElementById("paypal-button-container");
					element.innerHTML = "";
					
					actions.redirect("'.$returnUrl.'?paysuccess=1");
					element.innerHTML = "Hi "+cust+", your payment of $"+pu.amount.value+" was successful. Thanks <span class=\'mdi mdi-thumb-up\'></span>";
					//console.log(orderData);
					if( orderData.status === "COMPLETED" ) {
						jQuery(function($) {
							$("#donation_group").hide("slow");
							$("#regbtn,#user_fields").show("slow");
							$("#smart-button-container").css("color","#ffcc00");
						});
						setTimeout(function() {
							location.assign("'.$returnUrl.'?paysuccess=1#account_form");
						},2000);
					}

					// Full available details
					//console.log("Capture result", orderData, JSON.stringify(orderData, null, 2));
				});
			},

			onError: function(err) {
			console.log(err);
			}
		}).render("#paypal-button-container");
	}
	initPayPalButton();
	</script>';
	
	return $html . $js;
}

function payState() 
{
	$state = false;
	if( isset($_REQUEST['paysuccess']) && !isset($_SERVER['HTTP_REFERER']) ) {
		redirect();
		return;
	}
	
	if( isset($_REQUEST['paysuccess']) && isset($_SERVER['HTTP_REFERER']) && strstr($_SERVER['HTTP_REFERER'],'https://websitedons.com/billing/payment') )
		$state = true;
	
	$status = $state;
	
	return $status;
}

function redirect($url='', $method = 301) {
	if( empty($url) )
		$url = 'https://websitedons.com/billing/payment';
	if( !headers_sent() ) {
		return header('Location: '.$url, true, $method);
		exit;
	}else{
		echo '<meta http-equiv="refresh" content="0; URL='.$url.'">';
	}
}

function vip() 
{
	if( isset($_SERVER['HTTP_CLIENT_IP']) ) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	}elseif( isset($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}else{
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	
	return $ip;
}

function browserCheck($type) {
	if( isset($_SERVER['HTTP_USER_AGENT']) ) {
		$agent = $_SERVER['HTTP_USER_AGENT'];
		if( preg_match('#'.$type.'#i',$agent) )
			return true;
	}
}

function nocache($file, $convert=false) 
{
	$val='';
	if( file_exists($file) ) 
	{
		$val = md5(filemtime($file));
		
		if( $convert ) {
			$makedate = filemtime($file);
			clearstatcache();
			$val = date('m-d-Y-H-i-s', $makedate);
		}
	}
	
	return $val;
}

function filelist($path, $filter=null, $getpath=false)
{
	if( empty($path) || !file_exists($path) )
		return; 
	
	$files = new \DirectoryIterator($path);
	$filelist=[];
	foreach($files as $file) 
	{
		if( $file->isFile() && !$file->isDot() ) 
		{
			// include only files in $filter 
			// methods: 'css' or 'css|txt' or starting with '^cat' or ending with '$er'
			if( !empty($filter) && !preg_match(chr(1).$filter.chr(1), $file) ) {
				continue;
			}
			
			$filelist[] = ($getpath == true ? $file->getPath().'/'.$file->getFilename() : $file->getFilename());
		}
	}
	
	return $filelist;
}

function currentUrl($filter=false) {
	$url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://').$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	if( $filter == true ) {
		return filter_var($url, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
	}else{
		return $url;
	}
}

function makeObj($data) {
	if( empty($data) )
		return;
	
	return json_decode(json_encode($data, JSON_FORCE_OBJECT));
}

function checked($val,$compare) {
	if( $val === $compare )
		echo ' checked';
	return;
}

function selected($val,$compare) {
	if( $val === $compare )
		echo ' selected';
	return;
}

function get(&$var)
{
	$val='';
	if( isset($var) && !empty($var) )
		$val = $var;
	return $val;
}

function sendMail($val)
{
	$to = get(edit()->client_email);
	$subject = $val['subject'];
	$message = $val['message'];
	$headers = [
	'From' => get(config()->config_email_from_address),
	'Reply-To' => get(config()->config_email_from_address),
	'X-Mailer' => 'PHP/'.phpversion()
	];
	/*
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'Content-type: text/html; charset=iso-8859-1';
	$headers[] = 'To: <'.$val['to'].'>';
	$headers[] = 'From: WD <'.$val['from'].'>';
	$headers[] = 'X-Mailer: PHP/'.phpversion();
	*/
	
	mb_send_mail($to, $subject, $message, $headers);
}

//https://github.com/mailjet/mailjet-apiv3-php-no-composer
function sendMailjet($val)
{
	$api = get(config()->config_email_mailjet_api);
	$sk = get(config()->config_email_mailjet_sk);
	
	include __DIR__.'/Mailjet/Client.php';
	include __DIR__.'/Mailjet/Config.php';
	include __DIR__.'/Mailjet/Request.php';
	include __DIR__.'/Mailjet/Resources.php';
	include __DIR__.'/Mailjet/Response.php'; 

	$mail = [
		'Messages' => [
			[
				'From' => [
					'Email' => get(config()->config_email_from_address),
					'Name' => get(config()->config_email_from_name)
				],
				'To' => [
					[
						'Email' => get(edit()->client_email),
						'Name' => get(edit()->client_name)
					]
				],
				'Subject' => $val['subject'],
				'TextPart' => $val['message'],
				//'HTMLPart' => ''
			]
		]
	];
	
	$mj = new \Mailjet\Client($api, $sk, true, ['version' => 'v3.1']);
	
	$response = $mj->post(Resources::$Email, ['body' => $mail]);
	//$response->success() && var_dump($response->getData());
}

function invUrl() {
	$p = parse_url(currenturl());
	
	$url='';
	if( !empty($p['scheme']) )
		$url .= $p['scheme'].'://';
	if( !empty($p['host']) )
		$url .= $p['host'];
	if( !empty($p['path']) )
		$url .= $p['path'];
	
	return $url;
}

function references() {
	$urls = [
	'https://developer.paypal.com/dashboard/creditCardGenerator' => 'PayPal sandbox test credit cards',
	'https://developer.paypal.com/sdk/js/reference/' => 'PayPal SDK reference',
	'https://github.com/mailjet/mailjet-apiv3-php-no-composer' => 'Mailjet API',
	'https://app.mailjet.com/signup' => 'Create mailjet account',
	'https://en.wikipedia.org/wiki/List_of_tz_database_time_zones' => 'Timezones table',
	];
	
	$list=[];
	foreach($urls as $url => $label) {
		$list[] = '<li><a href="'.$url.'" target="_blank">'.$label.'</a></li>';
	}
	
	return implode($list);
}
