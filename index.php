<?php
include 'functions.php';

?>

<!DOCTYPE html>

<html lang="en-US">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width; initial-scale=1.0;" />
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<meta name="robots" content="noindex">
<meta name="googlebot" content="noindex">
<meta name="googlebot-news" content="nosnippet">
<title><?php echo get(config()->config_page_title); ?></title>
<link href="<?php echo ASSET_URL; ?>/style.css?v=<?php echo nocache(ASSET_DIR.'/style.css'); ?>" type="text/css" media="all" rel="stylesheet" />
</head>

<body <?php echo bodyClass(); ?>>

<?php 
echo get(config()->config_page_head);

// do forms
if( isset($_GET['do_invoice']) || isset($_GET['do_configs']) ) { 
	if( isset($_GET['do_invoice']) )
		include 'do-invoice.php';
	if( isset($_GET['do_configs']) )
		include 'configs.php';
}else{
	include 'frontpage.php';
}

echo get(config()->config_page_foot); 
?>

</body>
</html>
