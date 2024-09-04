<?php 
defined('INVOICING') || exit('file is empty');

## log in
$logindb = file_get_contents(__DIR__.'/admin');
$logged_in = false;
if( isset($_POST['_admin_login']) ) {
	if( $_POST['_wd_admin_werd'] === $logindb ) {
		$_SESSION['wd_admin'] = $_POST['_wd_admin_werd'];
	}
	redirect(currenturl());
}
if( isset($_SESSION['wd_admin']) && $_SESSION['wd_admin'] === $logindb ) {
	$logged_in = true;
}

## write configs to json file
if( isset($_POST['_write_config']) ) {
	$config_save = json_encode(sanitize($_POST), JSON_PRETTY_PRINT);
	file_put_contents(CONFIG_FILE,$config_save);
	redirect(currenturl());
}

// funding methods
$ftype = get(config()->config_paypal_funding);
$card = (!empty($ftype->card) ? ' checked' : '');
$paylater = (!empty($ftype->later) ? ' checked' : '');
$venmo = (!empty($ftype->venmo) ? ' checked' : '');
$credit = (!empty($ftype->credit) ? ' checked' : '');
// button styles
$btnlayout = config()->config_paypal_btnlayout;
$btnshape = config()->config_paypal_btnshape;
$btncolor = config()->config_paypal_btncolor;
$btnheight = config()->config_paypal_btnheight;
//email
$mailapp = get(config()->config_email_smtp);
?>

<div class="mainwrap">
	<form action="" method="post">
		<?php if( !$logged_in ) { ?>
			<input type="text" name="_wd_admin_werd" value="" placeholder="password" />
			<div class="mt10"><input type="submit" name="_admin_login" value="Login" /></div>
		<?php return; } ?>
		
		<div class="mb30">
			<h2>PayPal</h2>
			<div class="flex flexcol gap10 mb10">
				<div class="flex gap10 flex-between flexitem-100">
					<input type="text" name="config_paypal_key" value="<?php echo get(config()->config_paypal_key); ?>" placeholder="client key" title="paypal client key" />
					<input type="text" name="config_paypal_currency" value="<?php echo strtoupper(get(config()->config_paypal_currency)); ?>" placeholder="currency code" style="width: 130px;" />
					<input type="text" name="config_paypal_return" value="<?php echo get(config()->config_paypal_return); ?>" placeholder="return URL" title="return URL" />
				</div>
				<h3>Sandbox</h3>
				<div class="flex gap10 flex-between flexitem-100">
					<input type="text" name="config_paypal_sb_key" value="<?php echo get(config()->config_paypal_sb_key); ?>" placeholder="sandbox key" title="paypal sandbox key" />
					<input type="text" name="config_paypal_sb_ip" value="<?php echo get(config()->config_paypal_sb_ip); ?>" placeholder="ip addresses" title="ip addresses" />
				</div>
			</div>
			
			<div class="flex gap10 check-group">
				<div>
					<h3>Pay methods</h3>
					<label>card<input type="checkbox" name="config_paypal_funding[card]" value="card" <?php echo $card; ?> /></label>
					<label>pay later<input type="checkbox" name="config_paypal_funding[later]" value="paylater" <?php echo $paylater; ?>/></label>
					<label>venmo<input type="checkbox" name="config_paypal_funding[venmo]" value="venmo" <?php echo $venmo; ?>/></label>
					<label>credit<input type="checkbox" name="config_paypal_funding[credit]" value="credit" <?php echo $credit; ?>/></label>
				</div>
				<div>
					<h3>Button Color</h3>
					<label>blue<input type="radio" name="config_paypal_btncolor" value="blue" <?php checked($btncolor,'blue'); ?> /></label>
					<label>gold<input type="radio" name="config_paypal_btncolor" value="gold" <?php checked($btncolor,'gold'); ?> /></label>
				</div>
				<div>
					<h3>Button Shape</h3>
					<label>pill<input type="radio" name="config_paypal_btnshape" value="pill" <?php checked($btnshape,'pill'); ?> /></label>
					<label>rectangle<input type="radio" name="config_paypal_btnshape" value="rect" <?php checked($btnshape,'rect'); ?> /></label>
				</div>
				<div>
					<h3>Button Layout</h3>
					<label>vertical<input type="radio" name="config_paypal_btnlayout" value="vertical" <?php checked($btnlayout,'vertical'); ?> /></label>
					<label>horizontal<input type="radio" name="config_paypal_btnlayout" value="horizontal" <?php checked($btnlayout,'horizontal'); ?> /></label>
				</div>
				<div>
					<h3>Button Height</h3>
					<select name="config_paypal_btnheight">
						<option value="30"<?php selected($btnheight,'30'); ?>>30</option>
						<option value="35"<?php selected($btnheight,'35'); ?>>35</option>
						<option value="40"<?php selected($btnheight,'40'); ?>>40</option>
						<option value="45"<?php selected($btnheight,'45'); ?>>45</option>
						<option value="50"<?php selected($btnheight,'50'); ?>>50</option>
						<option value="55"<?php selected($btnheight,'55'); ?>>55</option>
					</select>
				</div>
			</div>
		</div>
		
		<div class="mb10">
			<h2>Page Stuff</h2>
			<div class="flex flexmid gap10 flex-even">
				<input type="text" name="config_page_title" value="<?php echo get(config()->config_page_title); ?>" placeholder="page title" />
				<input type="text" name="config_page_timezone" value="<?php echo get(config()->config_page_timezone); ?>" placeholder="set timezone" />
				<textarea name="config_page_head" placeholder="page head"><?php echo get(config()->config_page_head); ?></textarea>
				<textarea name="config_page_foot" placeholder="page foot"><?php echo get(config()->config_page_foot); ?></textarea>
				
			</div>
		</div>
		
		<div class="mb10">
			<h2>Email</h2>
			<div class="flex flexmid gap10 flex-even">
				<input type="text" name="config_email_from_address" value="<?php echo get(config()->config_email_from_address); ?>" placeholder="sending email" title="sending email" />
				<input type="text" name="config_email_from_name" value="<?php echo get(config()->config_email_from_name); ?>" placeholder="sending name" title="sending name" />
				<input type="text" name="config_email_subject" value="<?php echo get(config()->config_email_subject); ?>" placeholder="subject" title="subject" />
				<div class="flex gap10 check-group">
					<div>
					<h3>SMTP Tool</h3>
					<select name="config_email_smtp">
						<option value="php"<?php selected($mailapp,'php'); ?>>php send mail</option>
						<option value="mailjet"<?php selected($mailapp,'mailjet'); ?>>mailjet</option>
					</select>
					</div>
				</div>
			</div>
			
			<?php if( config()->config_email_smtp == 'mailjet' ) { ?>
			<div class="flex gap10 mb10">
				<input type="text" name="config_email_mailjet_api" value="<?php echo get(config()->config_email_mailjet_api); ?>" placeholder="mailjet api" title="mailjet api" />
				<input type="text" name="config_email_mailjet_sk" value="<?php echo get(config()->config_email_mailjet_sk); ?>" placeholder="mailjet secret" title="subject" />
				<input type="text" name="config_email_mailjet_user" value="<?php echo get(config()->config_email_mailjet_user); ?>" placeholder="mailjet user" title="subject" />
				<input type="text" name="config_email_mailjet_pwd" value="<?php echo get(config()->config_email_mailjet_pwd); ?>" placeholder="mailjet password" title="subject" />
			</div>
			<?php } ?>
			
			<div class="flex gap10 flexitem-100 email-msg">
				<div class="flex flexcol gap10">
					<textarea name="config_email_notice_invoice" placeholder="new invoice notice"><?php echo get(config()->config_email_notice_invoice); ?></textarea>
					<textarea name="config_email_remind_invoice" placeholder="reminder"><?php echo get(config()->config_email_remind_invoice); ?></textarea>
					<textarea name="config_email_warn_invoice" placeholder="warning"><?php echo get(config()->config_email_warn_invoice); ?></textarea>
				</div>
				<div>
					<textarea name="config_email_signature" placeholder="signature"><?php echo get(config()->config_email_signature); ?></textarea>
					<div class="mt10">
						<h3>Message place holders</h3>
						<p>used within email message fields</p>
<textarea class="readonly" readonly>
[CUSTOMER]
[INVOICENUMBER]
[TOTAL]
[URL]
[FINALDATE]
</textarea>
					</div>
				</div>
			</div>
		</div>
		
		<div class="mt10"><input type="submit" name="_write_config" value="Save" /></div>
	</form>
	<div>
	<h3>Resources Documentation</h3>
	<ul><?php echo references(); ?></ul>
	</div>
</div>
