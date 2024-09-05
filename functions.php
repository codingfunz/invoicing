<?php
session_start();

// initialize mailjet
$mj=false;
if( file_exists(__DIR__.'/vendor/autoload.php') ) {
	require __DIR__.'/vendor/autoload.php';
	$mj=true;
}

use \Mailjet\Resources;

## SET CONSTANTS ##
define('INVOICING',		'ready');
define('DB_DIR',		__DIR__.'/db');
define('INVOICE_DIR',	DB_DIR.'/invoices');
define('CONFIG_FILE',	DB_DIR.'/_set_configs.json');
define('PWD',			file_get_contents(DB_DIR.'/admin'));
define('CONFIG_DATA',	file_get_contents(CONFIG_FILE));
define('ASSET_DIR',		__DIR__.'/assets');
define('ASSET_URL',		invurl().'assets');
define('DB_INVNUM',		DB_DIR.'/lastid');
define('LAST_ID',		file_get_contents(DB_INVNUM));
define('TIMEZONE',		get(config()->config_page_timezone));
define('NL', 			"\n");

date_default_timezone_set(TIMEZONE);

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
	if( isset($_GET['do_invoice']) && isset($_GET['edit']) && file_exists(INVOICE_DIR.'/'.$_GET['edit']) ) 
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
	$configs = json_decode(CONFIG_DATA, true);
	
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
	
	$btn = get(config()->config_paypal_btn);
	
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
			shape: "'.$btn->shape.'",
			color: "'.$btn->color.'",
			layout: "'.$btn->layout.'",
			label: "checkout",
			height: '.$btn->height.',
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
	if( get($val) === $compare )
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
	if( !file_exists(__DIR__.'/vendor/autoload.php') )
		return;
	
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

function sanitize($post)
{
	$keep = '<br><div><img><ul><ol><li><a><p><section><hr><h1><h2><h3><h4><h5><h6><span><strong><table><tr><td><tbody><tfoot><th>';
	$data=[];
	foreach($post as $key => $val) {
		if( is_array($val) ) {
			$data[$key] = $val;
			continue;
		}
		$data[$key] = strip_tags($val,$keep);
	}
	
	return $data;
}

function strip_tags_content($text, $tags = '', $invert = FALSE) 
{
	preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
	$tags = array_unique($tags[1]);

	if(is_array($tags) AND count($tags) > 0) {
		if($invert == FALSE) {
			return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
		}else{
			return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
		}
	}elseif($invert == FALSE) {
		return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
	}

	return $text;
}

function writepage($inv_number)
{
	$setopt = invurl().'/?client=invoice-'.$inv_number;
	$html_cache_path = htmlCache().'/invoice-'.$inv_number.'.html';
	if( is_string($inv_number) && 'index' === $inv_number ) {
		$setopt = invurl();
		$html_cache_path = htmlCache().'/index.html';
	}
	$curl = curl_init(); 
	curl_setopt($curl, CURLOPT_URL, $setopt); 
	curl_setopt($curl, CURLOPT_BINARYTRANSFER, true); 
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 

	$html_string = curl_exec($curl); 
	curl_close($curl);
	
	file_put_contents($html_cache_path,$html_string);
}

function htmlCache($type='path')
{
	$html_cache_url = parse_url(get(config()->config_page_cache_dir));
	$path = $_SERVER['DOCUMENT_ROOT'].get($html_cache_url['path']);
	if( $type == 'uri' )
		$path = get(config()->config_page_cache_dir);
	
	return $path;
}

function adminLogin()
{
	$logged_in = false;
	if( isset($_POST['_admin_login']) ) {
		if( $_POST['_wd_admin_werd'] === PWD ) {
			$_SESSION['wd_admin'] = $_POST['_wd_admin_werd'];
		}
		redirect(currenturl());
	}
	if( isset($_SESSION['wd_admin']) && $_SESSION['wd_admin'] === PWD ) {
		$logged_in = true;
	}
	
	return $logged_in;
}

function configSave()
{
	if( isset($_POST['_write_config']) ) 
	{
		$config_save = json_encode(sanitize($_POST), JSON_PRETTY_PRINT);
		
		file_put_contents(CONFIG_FILE,$config_save);
		
		// create invoices html cache directory
		if( !file_exists(htmlCache()) )
			mkdir(htmlCache());
		
		// create index.html with default funding page
		if( !file_exists(htmlCache().'/index.html') )
			writepage('index');
		
		redirect(currenturl());
	}
	return;
}

function invoiceSave()
{
	if( isset($_POST['_do_invoice']) ) 
	{
		if( _var()->editmode ) {
			$invoice_file = INVOICE_DIR.'/'.$_GET['edit'];
			$redirect_url = currenturl();
			$inv_number = get(edit()->inv_number);
		}else{
			file_put_contents(DB_INVNUM,_var()->newinv);
			$invoice_file = INVOICE_DIR.'/invoice-'.$_POST['inv_number'].'.json';
			$redirect_url = currenturl().'&edit=invoice-'.$_POST['inv_number'].'.json';
			$inv_number = $_POST['inv_number'];
		}
		
		file_put_contents($invoice_file, json_encode(sanitize($_POST),JSON_PRETTY_PRINT));
		writepage($inv_number);
		redirect($redirect_url);
	}
	return;
}

function _var()
{
	$editmode = (isset($_GET['edit']) ? true:false);
	$v = new stdClass();
	$v->newinv 		= ((int)LAST_ID+1);
	$v->editmode 	= $editmode;
	$v->invoice_num = ($editmode ? get(edit()->inv_number):((int)LAST_ID+1));
	$v->inv_date 	= date('Y-m-d');
	$v->button_label = ($editmode ? 'Update':'Create');
	
	$v->invoices	= filelist(INVOICE_DIR,'.json');
	$v->mail_button = (!empty(edit()->client_email) && !get(edit()->item_paid) ? true:false);
	
	return $v;
}

function mailInvoice()
{
	if( _var()->editmode ) 
	{
		if( !empty(edit()->client_email) && !get(edit()->item_paid) ) 
		{
			// send invoice email
			if( isset($_POST['_email_this_invoice']) && !is_null($_POST['_email_this_invoice']) ) 
			{
				$invoice_notice = get(config()->config_email_notice_invoice);
				if( isset($_POST['_notice_type']) ) {
					$pt = $_POST['_notice_type'];
					if( $pt == 'remind' )
						$invoice_notice = get(config()->config_email_remind_invoice);
					if( $pt == 'warn' )
						$invoice_notice = get(config()->config_email_warn_invoice);
				}
				$invoice_notice .= NL.NL.'--'.NL.get(config()->config_email_signature);
				
				$sc = ['[CUSTOMER]','[TOTAL]','[URL]','[FINALDATE]','[INVOICENUMBER]',PHP_EOL];
				$data = [
				ucwords(get(edit()->client_name)),
				number_format(get(edit()->item_cost),2,'.',''), 
				htmlCache('uri').'/'.str_replace('.json','',$_GET['edit']).'.html',
				date('F d, Y',strtotime(get(edit()->inv_final_date))),
				'#'.get(edit()->inv_number),
				NL
				];
				
				$message = str_replace($sc,$data,$invoice_notice);
				
				$val = [
				'subject' => str_replace('[INVOICENUMBER]',' #'.get(edit()->inv_number),get(config()->config_email_subject)),
				'message' => $message
				];
				
				if( config()->config_email_smtp == 'mailjet' ) {
					sendMailjet($val);
				}else{
					sendMail($val);
				}
				redirect(currenturl());
			}
		}
	}
	return;
}

function invoiceNav()
{
	$invoice_list=[];
	foreach(_var()->invoices as $inv) 
	{
		$data = file_get_contents(INVOICE_DIR.'/'.$inv);
		$data = json_decode($data);
		$filename = str_replace('.json','',$inv);
		$invoice_url = htmlCache('uri').'/'.$filename.'.html';
		
		$invoice_list[] = '
		<div class="flex gap5">
			<span>
				<a href="'.$_SERVER['SCRIPT_URI'].'?do_invoice=1&edit='.$inv.'" class="flex gap5 flex-between width-200" title="edit invoice">
					<span>'.ucwords($data->client_name).'</span>
					<span>'.$data->inv_number.'</span>
					<span>$'.number_format($data->item_cost, 2, '.', '').'</span>
				</a>
			</span>
			<span><a href="'.$invoice_url.'" target="_blank" title="view invoice">View</a></span>
			<form method="post" action="">
				<span class="flex gap5">
				<button name="trash_'.$data->inv_number.'" title="move to trash"><img src="'.ASSET_URL.'/trash-fill.svg" /></button>
				<button name="del_'.$data->inv_number.'" title="delete"><img src="'.ASSET_URL.'/x-circle-fill.svg" /></button>
				</span>
			</form>
		</div>';
		
		// move an invoice to trash folder
		if( isset($_POST['trash_'.$data->inv_number]) || isset($_POST['del_'.$data->inv_number]) ) 
		{
			$oldfile = INVOICE_DIR.'/invoice-'.$data->inv_number.'.json';
			$trash = INVOICE_DIR.'/trash/invoice-'.$data->inv_number.'.json';
			$cached_file = htmlCache().'/'.$filename.'.html';
			// deleted cached html file
			if( file_exists($cached_file) )
				unlink($cached_file);
			
			if( isset($_POST['trash_'.$data->inv_number]) ) {
				rename($oldfile,$trash);
			}else
			if( isset($_POST['del_'.$data->inv_number]) ) {
				unlink($oldfile);
			}
			redirect(invurl().'?do_invoice=1');
		}
	}
	
	return implode($invoice_list);
}
