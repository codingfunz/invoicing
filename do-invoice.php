<?php
defined('INVOICING') || exit('file is empty');

date_default_timezone_set(TIMEZONE);
$lastid 		= __DIR__.'/lastid';
$invnum 		= file_get_contents($lastid);
$newinv 		= ((int)$invnum+1);
$editmode 		= (isset($_GET['edit']) ? true:false);
$invoice_num 	= ($editmode ? get(edit()->inv_number):$newinv);
$inv_date 		= date('Y-m-d');
$button_label 	= ($editmode ? 'Update':'Create');
$prefix			= get(edit()->client_name);

// post
if( isset($_POST['_do_invoice']) ) 
{
	if( $editmode ) {
		$invoice_file = INVOICE_DIR.'/'.$_GET['edit'];
	}else{
		file_put_contents($lastid,$newinv);
		$invoice_file = INVOICE_DIR.'/invoice-'.$_POST['inv_number'].'.json';
	}
	
	file_put_contents($invoice_file, json_encode(sanitize($_POST),JSON_PRETTY_PRINT));
	redirect(currenturl());
}

// invoices list
$invoice_files = filelist(INVOICE_DIR,'.json');
$invoice_list=[];
foreach($invoice_files as $inv) 
{
	$data = file_get_contents(INVOICE_DIR.'/'.$inv);
	$data = json_decode($data);
	$invoice_list[] = '
	<div class="flex gap5">
		<span>
			<a href="'.$_SERVER['SCRIPT_URI'].'?do_invoice=1&edit='.$inv.'" class="flex gap5 flex-between width-200" title="edit invoice">
				<span>'.ucwords($data->client_name).'</span>
				<span>'.$data->inv_number.'</span>
				<span>$'.number_format($data->item_cost, 2, '.', '').'</span>
			</a>
		</span>
		<span><a href="'.$_SERVER['SCRIPT_URI'].'?client='.str_replace('.json','',$inv).'" target="_blank" title="view invoice">View</a></span>
		<form method="post" action="">
			<button name="del_'.$data->inv_number.'" title="move to trash">X</button>
		</form>
	</div>';
	// move an invoice to trash folder
	if( isset($_POST['del_'.$data->inv_number]) ) 
	{
		$oldfile = INVOICE_DIR.'/invoice-'.$data->inv_number.'.json';
		$trash = INVOICE_DIR.'/trash/invoice-'.$data->inv_number.'.json';
		rename($oldfile,$trash);
		// uncomment following method if total delete is preferred
		//unlink($oldfile);
		redirect(invurl().'?do_invoice=1');
	}
}

// email invoice
$mail_button= $invoice_title= false;
if( $editmode ) 
{
	if( !empty(edit()->client_email) && !get(edit()->item_paid) ) 
	{
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
			invUrl().'?client='.str_replace('.json','',$_GET['edit']),
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
		
		$mail_button = true;
	}
	
	$invoice_title = true;
}

?>

<div class="mainwrap">
	<div class="flex gap10">
		<div class="invoice-list">
			<div class="mb10">
			<a href="<?php echo invurl(); ?>?do_invoice=1" class="btn">New invoice</a>
			<a href="<?php echo invurl(); ?>?do_configs=1" class="btn">Config</a>
			</div>
			<?php echo implode($invoice_list); ?>
		</div>
		
		<div class="invoice-form">
			<?php if( $invoice_title ) { ?>
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
					<input type="hidden" name="inv_number" value="<?php echo $invoice_num; ?>" />
					<input type="date" name="inv_final_date" value="<?php echo get(edit()->inv_final_date); ?>" min="2024-01-01" title="set final date" />
					<?php if( $editmode ) { ?>
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
				<input type="hidden" name="inv_date" value="<?php echo get(edit()->inv_date); ?>" />
				<input type="submit" name="_do_invoice" value="<?php echo $button_label; ?> Invoice" />
				
				<?php if( $mail_button ) { ?>
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
