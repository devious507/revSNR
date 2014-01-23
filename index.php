<?php

require_once("Telnet.class.php");

define("MAX_REPEAT",30);

$cmts_ip = '38.108.136.1';

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
	$snr=json_decode($_POST['snr']);
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

function getJS($auto) {
	if($auto == 'true') {
		$js=file_get_contents('autoload.js');
		return $js;
	} else {
		return '';
	}
}
function getLabels() {
	$lbl[]="C3/0/U0";
	$lbl[]="C3/0/U1";
	$lbl[]="C3/0/U2";
	$lbl[]="C3/0/U3";
	$lbl[]="C3/0/U4";
	$lbl[]="C3/0/U5";
	$lbl[]="C3/0/U6";
	$lbl[]="C3/0/U7";
	$lbl[]="C4/0/U0";
	$lbl[]="C4/0/U1";
	$lbl[]="C4/0/U2";
	$lbl[]="C4/0/U3";
	$lbl[]="C4/0/U4";
	$lbl[]="C4/0/U5";
	$lbl[]="C4/0/U6";
	$lbl[]="C4/0/U7";
	return $lbl;
}
function getRevSNR($snr) {
	global $cmts_ip;
	$tel = new Telnet($cmts_ip,23,10,":");
	$tel->setPrompt(">");
	$tel->exec("gamester");
	$tel->setPrompt(":");
	$tel->exec("en");
	$tel->setPrompt("#");
	$tel->exec("renter30");
	$tel->setStreamTimeout(4);
	$data =$tel->exec("show contr cable 3/0 | incl SNR");
	$data.="\n";
	$data.=$tel->exec("show contr cable 4/0 | incl SNR");
	$tel->disconnect();
	$dat=preg_split("/\n/",$data);
	unset($data);
	$newVals=array();
	foreach($dat as $d) {
		$pat="/US phy MER\(SNR\)_estimate for good packets - /";
		if(preg_match($pat,$d)) {
			$d=preg_replace($pat,"",$d);
			$d=preg_replace("/ dB/","",$d);
			$d=preg_replace("/ /","",$d);
			$newVals[]=floatval($d);
		}
	}
	unset($dat);
	for($i=0; $i < 16; $i++) {
		array_unshift($snr[$i],$newVals[$i]);
		array_pop($snr[$i]);
	}
	global $timeStamps;
	array_unshift($timeStamps,date('h:i:s'));
	if(count($timeStamps) > 16) {
		array_pop($timeStamps);
	}
	return $snr;
}
function generateBody($snr,$auto_val) {
	global $timeStamps;
	global $repeatCount;
	global $auto_val;
	$maxRepeats=MAX_REPEAT;
	$color1="#ffffff";
	$color2="#cacaca";
	$color3="#fffacd";
	$active_color=$color1;
	$lbls=getLabels();
	$count=0;

	if($auto_val == 'true') {
		$body="Autorun in Progress: {$repeatCount} / {$maxRepeats}";
		$body.="<table cellpadding=\"3\" cellspacing=\"0\" border=\"1\">\n";
	} else {
		$body="<table cellpadding=\"3\" cellspacing=\"0\" border=\"1\">\n";
	}
	$body.="<tr><td bgcolor=\"{$color3}\">Interface&nbsp;</td>";
	foreach($timeStamps as $stamp) {
		$body.="<td bgcolor=\"{$color3}\" align=\"right\">&nbsp;&nbsp;{$stamp}</td>";
	}
	$body.="<td bgcolor=\"{$color3}\" align=\"right\">Avg&nbsp;</td></tr>";
	foreach($snr as $s) {
		$tot=0;
		$avg_ct=0;
		if($count < 4) {
			$active_color=$color1;
		} elseif ($count < 8) {
			$active_color=$color2;
		} elseif ($count < 12) {
			$active_color=$color1;
		} else {
			$active_color=$color2;
		}
		$body.="<tr>\t<td bgcolor=\"{$active_color}\">{$lbls[$count]}&nbsp;&nbsp;&nbsp;</td>\n";
		foreach($s as $e) {
			$tot+=$e;
			if($e > 0) {
				$avg_ct+=1;
				$fmt=sprintf("&nbsp;&nbsp;&nbsp;%.02f",$e);
				$body.="\t<td align=\"right\" bgcolor=\"{$active_color}\">{$fmt}</td>\n";
			}
		}
		if($avg_ct > 0) {
			$avg=sprintf("%.02f",$tot/$avg_ct);
			$body.="\t<td align=\"right\" bgcolor=\"{$color3}\">&nbsp;&nbsp;&nbsp;{$avg}</td>\n";
		} else {
			$body.="\t<td align=\"right\" bgcolor=\"{$color3}\">&nbsp;&nbsp;&nbsp;0.00</td>\n";
		}
		$body.="</tr>\n";
		$count++;
	}
	$json_snr=json_encode($snr);
	$json_timestamps=base64_encode(json_encode($timeStamps));
	$body.="</table>\n";
	$body.="<form name=\"myForm\" id=\"myForm\" method=\"post\" action=\"index.php\">\n";
	$body.="<input type=\"hidden\" name=\"snr\" value=\"{$json_snr}\">\n";
	$body.="<input type=\"hidden\" name=\"timestamps\" value=\"{$json_timestamps}\">\n";
	$body.="<input type=\"hidden\" name=\"repeatCount\" value=\"{$repeatCount}\">\n";
	$body.="<input type=\"hidden\" name=\"auto\" value=\"{$auto_val}\">\n";
	$body.="<input type=\"submit\" value=\"update\">\n";
	$body.="</form>\n";
	if($auto_val != 'true') {
		$body.="<br><a href=\"index.php?auto=true\">Start Auto Updates</a>";
	}
	//$body.=$json_snr;
	//$body.=$json_timestamps;
	return $body;
}

//print "<pre>"; var_dump($timeStamps); "</pre>"; exit();

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
