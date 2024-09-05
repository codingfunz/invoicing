<?php
defined('INVOICING') || exit('file is empty');

// save invoice data
invoiceSave();

// email invoice
mailInvoice();

?>

<div class="mainwrap">
	<div class="flex gap10">
		<div class="invoice-list">
			<div class="mb10">
			<a href="<?php echo invurl(); ?>?do_invoice=1" class="btn">New invoice</a>
			<a href="<?php echo invurl(); ?>?do_configs=1" class="btn">Config</a>
			</div>
			<?php echo invoiceNav(); ?>
		</div>
		
		<div class="invoice-form">
			<?php if( _var()->editmode ) { ?>
			<h3>Invoice <?php echo get(edit()->inv_number); ?></h3>
			<?php } ?>
			<form action="" method="post">
				<div class="flex mb10 gap10 inputs flexitem-100">
					<input type="text" name="client_name" value="<?php echo get(edit()->client_name); ?>" placeholder="Client Name" />
					<input type="text" name="client_email" value="<?php echo get(edit()->client_email); ?>" placeholder="Client Email Address" />
				</div>
				<div class="flex mb10 gap10 inputs flexitem-100">
					<input type="text" name="item_name" value="<?php echo get(edit()->item_name); ?>" placeholder="Item Name" />
					<input type="text" name="item_cost" value="<?php echo get(edit()->item_cost); ?>" placeholder="Item Price" />
					<input type="hidden" name="inv_number" value="<?php echo _var()->invoice_num; ?>" />
					<input type="date" name="inv_final_date" value="<?php echo get(edit()->inv_final_date); ?>" min="2024-01-01" title="set final date" />
					<?php if( _var()->editmode ) { ?>
					<label>Paid <input type="checkbox" name="item_paid" value="1" <?php checked(get(edit()->item_paid),'1'); ?> /></label>
					<?php } ?>
				</div>
				<div class="flex mb10 flexcol gap10 flexitem-100">
					<textarea name="item_note" placeholder="Notes on invoice"><?php echo get(edit()->item_note); ?></textarea>
					<div>Add service details. Separate each item with 3 hyphens EG: ---</div>
					<div class="flex gap10">
					<textarea name="item_detail" placeholder="service details"><?php echo get(edit()->item_detail); ?></textarea>
					<textarea name="item_internal_notes" placeholder="internal notes"><?php echo get(edit()->item_internal_notes); ?></textarea>
					</div>
				</div>
				<input type="hidden" name="inv_date" value="<?php echo _var()->setdate; ?>" />
				<input type="submit" name="_do_invoice" value="<?php echo _var()->button_label; ?> Invoice" />
				
				<?php if( _var()->mail_button ) { ?>
				<div class="mt10">
					<h3>Send email notice</h3>
					<select name="_notice_type">
						<option value="">New invoice</option>
						<option value="remind">Reminder</option>
						<option value="warn">Warning</option>
					</select>
					<button name="_email_this_invoice">Send email notice</button>
				</div>
				<?php } ?>
			</form>
		</div>
	</div>
</div>
