<?php

require_once("functions.php");


$snr = json_decode(base64_decode($_POST['snr']));
$orig_snr=$snr;
$timestamps = json_decode(base64_decode($_POST['timestamps']));


for($i=0; $i<16; $i++) {
	$snr[$i]=doAnalyze($snr[$i]);
}
$body="<table cellpadding=\"3\" cellspacing=\"0\" border=\"1\">\n";
$body.="<tr><td colspan=\"18\">Reverse SNR Stability Analysis</td></tr>\n";
$body.="<tr><td>Interface</td><td align=\"right\">Avg</td>";
foreach($timestamps as $time) {
	$body.="<td align=\"right\">&nbsp;&nbsp{$time}</td>";
}
$body.="</tr>\n";



$ct=0;
foreach($snr as $s) {
	$lbls = getLabels();
	$avg=sprintf("%.02f",getAvg($orig_snr[$ct]));
	$iface=$lbls[$ct];
	if($avg > 30 ) {
		$bgcolor="lightgreen";
	} elseif($avg > 25) {
		$bgcolor="yellow";
	} else {
		$bgcolor="red";
	}
	$body.="<tr><td>{$iface}</td><td bgcolor=\"{$bgcolor}\"align=\"right\">&nbsp;&nbsp;{$avg}</td>";
	foreach($s as $val) {
		$abs=abs($val);
		if($abs < 1.5) {
			$bgcolor="lightgreen";
		} elseif($abs < 3) {
			$bgcolor="yellow";
		} else {
			$bgcolor="red";
		}
		$val=sprintf("%.02f",$val);
		$body.="<td bgcolor=\"{$bgcolor}\" align=\"right\">{$val}</td>";
	}
	$body.="</tr>\n";
	$ct++;
}

$body.="</table>\n";


?>
<html>
<head>
<title></title>
</head>
<body>
<?php echo $body; ?>
</body>
</html>
