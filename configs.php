<?php 
defined('INVOICING') || exit('file is empty');

## write configs to json file
configSave();

// paypal funding methods
$ftype = get(config()->config_paypal_funding);
// paypal button style
$ppbtn = get(config()->config_paypal_btn);
//email
$mailapp = get(config()->config_email_smtp);
?>

<div class="mainwrap">
	<form action="" method="post">
		<?php if( !adminLogin() ) { ?>
			<input type="text" name="_wd_admin_werd" value="" placeholder="password" />
			<div class="mt10"><input type="submit" name="_admin_login" value="Login" /></div>
			
		<?php }else{ ?>
		
		<div class="mb30">
			<div class="flex gap10 flexmid">
			<h2>PayPal</h2><span><a href="<?php echo invurl(); ?>?do_invoice=1" class="btn">Invoices</a></span>
			</div>
			<div class="flex flexcol gap10 mb10">
				<div class="flex gap10 flex-between flexitem-100">
					<input type="text" name="config_paypal_key" value="<?php echo get(config()->config_paypal_key); ?>" placeholder="client key" title="paypal client key" required />
					<input type="text" name="config_paypal_currency" value="<?php echo strtoupper(get(config()->config_paypal_currency)); ?>" placeholder="currency code" style="width: 130px;" required />
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
					<label>card<input type="checkbox" name="config_paypal_funding[card]" value="card" <?php checked(get($ftype->card),'card'); ?> /></label>
					<label>pay later<input type="checkbox" name="config_paypal_funding[later]" value="paylater" <?php checked(get($ftype->later),'paylater'); ?>/></label>
					<label>venmo<input type="checkbox" name="config_paypal_funding[venmo]" value="venmo" <?php checked(get($ftype->venmo),'venmo'); ?>/></label>
					<label>credit<input type="checkbox" name="config_paypal_funding[credit]" value="credit" <?php checked(get($ftype->credit),'credit'); ?>/></label>
				</div>
				<div>
					<h3>Button Color</h3>
					<label>blue<input type="radio" name="config_paypal_btn[color]" value="blue" <?php checked($ppbtn->color,'blue'); ?> /></label>
					<label>gold<input type="radio" name="config_paypal_btn[color]" value="gold" <?php checked($ppbtn->color,'gold'); ?> /></label>
				</div>
				<div>
					<h3>Button Shape</h3>
					<label>pill<input type="radio" name="config_paypal_btn[shape]" value="pill" <?php checked($ppbtn->shape,'pill'); ?> /></label>
					<label>rectangle<input type="radio" name="config_paypal_btn[shape]" value="rect" <?php checked($ppbtn->shape,'rect'); ?> /></label>
				</div>
				<div>
					<h3>Button Layout</h3>
					<label>vertical<input type="radio" name="config_paypal_btn[layout]" value="vertical" <?php checked($ppbtn->layout,'vertical'); ?> /></label>
					<label>horizontal<input type="radio" name="config_paypal_btn[layout]" value="horizontal" <?php checked($ppbtn->layout,'horizontal'); ?> /></label>
				</div>
				<div>
					<h3>Button Height</h3>
					<select name="config_paypal_btn[height]">
						<option value="30"<?php selected($ppbtn->height,'30'); ?>>30</option>
						<option value="35"<?php selected($ppbtn->height,'35'); ?>>35</option>
						<option value="40"<?php selected($ppbtn->height,'40'); ?>>40</option>
						<option value="45"<?php selected($ppbtn->height,'45'); ?>>45</option>
						<option value="50"<?php selected($ppbtn->height,'50'); ?>>50</option>
						<option value="55"<?php selected($ppbtn->height,'55'); ?>>55</option>
					</select>
				</div>
			</div>
		</div>
		
		<div class="mb10">
			<h2>Page Stuff</h2>
			<div class="flex flexmid gap10 flex-even mb10">
				<input type="text" name="config_page_title" value="<?php echo get(config()->config_page_title); ?>" placeholder="page title" />
				<input type="text" name="config_page_timezone" value="<?php echo get(config()->config_page_timezone); ?>" placeholder="set timezone" />
				<input type="text" name="config_page_cache_dir" value="<?php echo get(config()->config_page_cache_dir); ?>" placeholder="invoices HTML pages cache URL" required />
			</div>
			<div class="flex flexmid gap10 flex-even">
				<textarea name="config_page_head" placeholder="page head"><?php echo get(config()->config_page_head); ?></textarea>
				<textarea name="config_page_foot" placeholder="page foot"><?php echo get(config()->config_page_foot); ?></textarea>
			</div>
		</div>
		
		<div class="mb10">
			<h2>Email</h2>
			<div class="flex flexmid gap10 flex-even">
				<input type="text" name="config_email_from_address" value="<?php echo get(config()->config_email_from_address); ?>" placeholder="sending email" title="sending email" required />
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
				<input type="text" name="config_email_mailjet_api" value="<?php echo get(config()->config_email_mailjet_api); ?>" placeholder="mailjet api" title="mailjet api" required />
				<input type="text" name="config_email_mailjet_sk" value="<?php echo get(config()->config_email_mailjet_sk); ?>" placeholder="mailjet secret" title="mailjet secret key" required />
				<input type="text" name="config_email_mailjet_user" value="<?php echo get(config()->config_email_mailjet_user); ?>" placeholder="mailjet user" title="mailjet user (optional)" />
				<input type="text" name="config_email_mailjet_pwd" value="<?php echo get(config()->config_email_mailjet_pwd); ?>" placeholder="mailjet password" title="mailjet password (optional)" />
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
		<?php } // end logged in condition ?>
	</form>
	<div>
	<h3>Resources Documentation</h3>
	<ul><?php echo references(); ?></ul>
	</div>
</div>
