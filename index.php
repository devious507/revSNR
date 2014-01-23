<?php

require_once("Telnet.class.php");
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
} else {
	$snr=json_decode($_POST['snr']);
}

$snr=getRevSNR($snr);
$body=generateBody($snr,$auto_val);

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
	//print "<pre>"; var_dump($snr); print "</pre>"; exit();

	return $snr;
}
function generateBody($snr,$auto_val) {
	$color1="#ffffff";
	$color2="#cacaca";
	$color3="#00caca";
	$active_color=$color1;
	$lbls=getLabels();
	$count=0;
	$body="<table cellpadding=\"3\" cellspacing=\"0\" border=\"1\">\n";
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
	$body.="</table>\n";
	$body.="<form method=\"post\" action=\"index.php\">\n";
	$body.="<input type=\"hidden\" name=\"snr\" value=\"{$json_snr}\">\n";
	$body.="<input type=\"hidden\" name=\"auto\" value=\"{$auto_val}\">\n";
	$body.="<input type=\"submit\" value=\"update\">\n";
	$body.="</form>\n";
	//$body.=$json_snr;
	return $body;
}

?>
<html>
<head>
<title>RevSNR Watcher</title>
</head>
<body>
<?php echo $body; ?>
</body>
</html>
