<?php

require_once("functions.php");
if(isset($_GET['fname'])) {
	if(preg_match("/^data/",$_GET['fname'])) {
		$fname=$_GET['fname'];
		$data=file_get_contents($fname);
		if(preg_match("/\.\./",$_GET['fname'])) {
			print "Error 1";
			exit();
		}
	} else {
		print "Error 2";
		exit();
	}
} else {
	print "Error 3";
	exit();
}

$post=json_decode(base64_decode($data));

$timeStamps=$post->timestamps;
$snr=$post->snr;
$repeatCount=1;
$auto_val='false';

$body=generateBody($snr,'false',$_GET['fname']);

?>
<html>
<head>
<title></title>
<body>
<?php echo $body; ?>
</body>
</html>
