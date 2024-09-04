<?php
defined('INVOICING') || exit('file is empty');

$paypal='';
if( get(config()->config_paypal_key) ) {
$paypal = smartCheckout([
		'items_name' => (isset(invoice()->item) ? invoice()->item :''),
		'items_description' => (isset(invoice()->item) ? invoice()->item :'')
		]);
}

$paid_class= $paid_html='';
if( isset(invoice()->paid) && invoice()->paid ) {
	$paid_class = ' is-paid';
	$paid_html = '<div class="is-paid center pad5">Paid</div>';
}
?>

<div class="pay-btn">
	<?php if( payState() ) { ?>
	<div id="user_fields">
		<p class="alert alert-success">The payment was processed succesfully.</p>
	</div>
	<?php } ?>
	
	<?php if( !payState() ) { ?>
	<div id="donation_group">
		<div class="amount-field invoice-detail">
		
			<?php if( $invoice_exist ) { ?>
			<input type="hidden" name="amount_field" value="<?php echo invoice()->amt; ?>" /> 
			<h3 class="flex flex-between item-name<?php echo $paid_class; ?>">
				<span><?php echo invoice()->item; ?></span>
				<span>$<?php echo invoice()->dollar; ?></span>
			</h3>
			
			<?php echo $paid_html; ?>
			
			<div class="item-detail">
			<?php echo invoice()->client; ?>
			<?php echo invoice()->number; ?>
			<?php echo invoice()->note; ?>
			<?php echo (!invoice()->paid ? $paypal : ''); ?>
			<?php echo invoice()->detail; ?>
			</div>
			
			<?php }else{ ?>
			<div class="fund-block">
				<div class="flex flexmid flexcenter mb40">
					<span style="white-space: nowrap; margin-right: 10px; font-weight: 700;">Enter Amount</span> <span>$</span>
					<input type="number" name="amount_field" id="other_amt" min="5" value="1.00" />
				</div>
				<?php echo $paypal; ?>
			</div>
			
			<?php } ?>
		</div>
	</div>
	<?php } ?>
</div>