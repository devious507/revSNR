<?php

$filename = $_SERVER["SCRIPT_FILENAME"];
$dat = preg_split("/\//",$filename);
array_pop($dat);
$dir = implode("/",$dat);
unset($dat);

$dh = opendir($dir);
while(($file=readdir($dh))!==false) {
	switch($file) {
	case ".":
	case "..":
	case "index.php":
	case ".index.php.swp":
	case "index.php.swp":
		break;
	default:
		$tstamp=base64_decode($file);
		$myFiles[$file]=$tstamp;
		break;
	}
}

$server=$_SERVER['HTTP_HOST'];
$script=$_SERVER["REQUEST_URI"];

$data=preg_split("/\//",$script);
array_pop($data);
array_pop($data);
$data[]='loadData.php';
$baseUrl="http://".$server.implode("/",$data);


arsort($myFiles);
foreach($myFiles as $k=>$v) {
	$time=date('m/d/Y H:i:s',$v);
	$fname=urlencode($k);
	$urls[]="<li><a href=\"{$baseUrl}?fname=data/{$fname}\">{$time}</a></li>";
}

$list=implode("\n",$urls);
?>
<html>
<head>
<title></title>
</head>
<body>
<ul>
<?php echo $list; ?>
</ul>
</body>
</html>
