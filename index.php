<?php

require_once("Telnet.class.php");
require_once("functions.php");

if(file_exists('config.php')) {
	require_once("config.php");
} else {
	header("Content-type: text/plain");
	print "Error: config.php must be created.  An Example of what is needed follows\n\n";
	$data = file_get_contents('config.php.example');
	print $data;
	exit();
}

$cmts_ip = CMTS_IP;
$cmts_port = CMTS_PORT;
$cmts_pass1 = CMTS_PASS1;
$cmts_pass2 = CMTS_PASS2;

// SNR Array 16x16 elements

if(isset($_GET['auto'])) {
	$auto_val=$_GET['auto'];
} elseif(isset($_POST['auto'])) {
	$auto_val=$_POST['auto'];
} else {
	$auto_val="false";
}
if(!isset($_POST['snr'])) {
	for($i=0; $i < 16; $i++) {
		for($j=0; $j < 16; $j++) {
			if($j==0) {
				$snr[$i][$j]=0;
			} else {
				$snr[$i][$j]=0;
			}
		}
	}
	$timeStamps=array();
	$repeatCount=0;
} else {
	$snr=json_decode(base64_decode(($_POST['snr'])));
	$timeStamps=json_decode(base64_decode(($_POST['timestamps'])));
	$repeatCount=$_POST['repeatCount'];
}

$repeatCount++;
if($repeatCount >= MAX_REPEAT) {
	$repeatCount=1;
	$auto_val='false';
}
$js=getJS($auto_val);
$snr=getRevSNR($snr);
$body=generateBody($snr,$auto_val);

?>
<html>
<head>
<title>RevSNR Watcher</title>
<?php echo $js; ?>
</head>
<body>
<?php echo $body; ?>
</body>
</html>
