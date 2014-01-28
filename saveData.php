<?php

$fname="data/".base64_encode(time());
$fname_url=urlencode($fname);

$data['snr']=json_decode(base64_decode($_POST['snr']));
$data['timestamps']=json_decode(base64_decode($_POST['timestamps']));


$jData=base64_encode(json_encode($data));
$fh=fopen($fname,'w');
fwrite($fh,$jData);
fclose($fh);

$loadUrl="loadData.php?fname={$fname_url}";

$server=$_SERVER['HTTP_HOST'];
$script=$_SERVER["REQUEST_URI"];

$data=preg_split("/\//",$script);
array_pop($data);
$data[]=$loadUrl;
$script=implode("/",$data);

$myUrl="http://".$server.$script;

$href="<a href=\"{$myUrl}\">Click Here</a>";
?>
<html>
<head>
<title>Data Saved</title>
</head>
<body>
Data Saved <?php echo $href; ?> to load for later review.
</body>
</html>
