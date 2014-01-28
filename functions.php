<?php

require_once("Telnet.class.php");
define("MAX_REPEAT",16);

function doAnalyze($snr) {
	$rv=array();
	$lbls = getLabels();
	$upstream=$lbls[$up];
	$avg=getAvg($snr);
	$ct=0;
	foreach ($snr as $val ){
		$diff = $val - $avg;
		$time=$times[$ct];
		$newSNR[$ct]=$diff;
		$ct++;
	}
	return $newSNR;
}

function getAvg($snr) {
	$tot=0;
	$count=0;
	foreach($snr as $val) {
		$tot+=$val;
		if($val >0) {
			$count++;
		}
	}
	if($count >0) {
		return $tot/$count;
	} else {
		// Div0 Error
		return NULL;
	}
}


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
	global $cmts_port;
	global $cmts_pass1;
	global $cmts_pass2;
	$tel = new Telnet($cmts_ip,$cmts_port,10,":");
	$tel->setPrompt(">");
	$tel->exec($cmts_pass1);
	$tel->setPrompt(":");
	$tel->exec("en");
	$tel->setPrompt("#");
	$tel->exec("$cmts_pass2");
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
	array_unshift($timeStamps,date('H:i:s'));
	if(count($timeStamps) > 16) {
		array_pop($timeStamps);
	}
	return $snr;
}
function generateBody($snr,$auto_val,$fname=' ') {
	global $timeStamps;
	global $repeatCount;
	global $auto_val;
	$maxRepeats=MAX_REPEAT;

	if($fname!=' ') {
		$fname=preg_replace("/^data\//","",$fname);
		$tstamp=base64_decode($fname);
		$tstamp=date('m/d/Y H:i:s',$tstamp);
		$header="Generated: {$tstamp}";
	}
	$color1="#ffffff";
	$color2="#cacaca";
	$color3="#fffacd";
	$active_color=$color1;
	$lbls=getLabels();
	$count=0;

	$body="<table cellpadding=\"3\" cellspacing=\"0\" border=\"1\">\n";
	if($auto_val == 'true') {
		$col=$repeatCount+2;
		$body.="<tr><td colspan=\"{$col}\">Autorun in Progress: {$repeatCount} / {$maxRepeats}</td></tr>";
	} else {
		$dateVal=date("F d, Y ");
		$dateVal.="(All times ";
		$dateVal.=date('T');
		$dateVal.=")";
		if(isset($header)) {
			$body.="<tr><td colspan=\"18\">&nbsp;&nbsp;&nbsp;{$header}</td></tr>\n";
		} else {
			$body.="<tr><td colspan=\"18\">&nbsp;&nbsp;&nbsp;{$dateVal}</td></tr>\n";
		}
	}
	$body.="<tr><td bgcolor=\"{$color3}\">Interface&nbsp;</td>";
	$body.="<td bgcolor=\"{$color3}\" align=\"right\">Avg&nbsp;</td>";
	foreach($timeStamps as $stamp) {
		$body.="<td bgcolor=\"{$color3}\" align=\"right\">&nbsp;&nbsp;{$stamp}</td>";
	}
	$body.="</tr>";
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
			}
		}
		if($avg_ct > 0) {
			$avg=sprintf("%.02f",$tot/$avg_ct);
			$body.="\t<td align=\"right\" bgcolor=\"{$color3}\">&nbsp;&nbsp;&nbsp;{$avg}</td>\n";
		} else {
			$body.="\t<td align=\"right\" bgcolor=\"{$color3}\">&nbsp;&nbsp;&nbsp;0.00</td>\n";
		}
		foreach($s as $e) {
			if($e > 0) {
				$fmt=sprintf("&nbsp;&nbsp;&nbsp;%.02f",$e);
				$body.="\t<td align=\"right\" bgcolor=\"{$active_color}\">{$fmt}</td>\n";
			}
		}
		$body.="</tr>\n";
		$count++;
	}
	$json_snr=base64_encode(json_encode($snr));
	$json_timestamps=base64_encode(json_encode($timeStamps));
	// Update Form
	$body.="<tr><td colspan=\"3\"><form name=\"myForm\" id=\"myForm\" method=\"post\" action=\"index.php\">\n";
	$body.="<input type=\"hidden\" name=\"snr\" value=\"{$json_snr}\">\n";
	$body.="<input type=\"hidden\" name=\"timestamps\" value=\"{$json_timestamps}\">\n";
	$body.="<input type=\"hidden\" name=\"repeatCount\" value=\"{$repeatCount}\">\n";
	$body.="<input type=\"hidden\" name=\"auto\" value=\"{$auto_val}\">\n";
	$body.="<input type=\"submit\" value=\"update\">\n";
	$body.="</form></td>\n";

	if($avg_ct >= 4) {
		// Analyze Form
		$body.="<td colspan=\"3\"><form name=\"myForm2\" id=\"myForm\" method=\"post\" action=\"analyze.php\">\n";
		$body.="<input type=\"hidden\" name=\"snr\" value=\"{$json_snr}\">\n";
		$body.="<input type=\"hidden\" name=\"timestamps\" value=\"{$json_timestamps}\">\n";
		$body.="<input type=\"hidden\" name=\"repeatCount\" value=\"{$repeatCount}\">\n";
		$body.="<input type=\"hidden\" name=\"auto\" value=\"{$auto_val}\">\n";
		$body.="<input type=\"submit\" value=\"Stability Analysis\">\n";
		$body.="</form></td>\n";
	}

	
	if(($avg_ct != 1) && ($avg_ct != 4)) {
		$body.="<td colspan=\"12\">&nbsp;</td></tr>\n";
	}
	$body.="</table>\n";
	if($auto_val != 'true') {
		$body.="<br><a href=\"index.php?auto=true\">Start Auto Updates</a> | <a href=\"data/index.php\">Load Old Results</a>";
	}
	//$body.=$json_snr;
	//$body.=$json_timestamps;
	return $body;
}
?>
