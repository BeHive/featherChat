<?php
	require_once('lib/Spyc/Spyc.php');
	$settings = Spyc::YAMLLoad('featherChat.yml');	
	$host = $settings['server']['host']; //host
	$port = $settings['server']['port']; //port
	$address = $settings['server']['address']; //address
?>

<!DOCTYPE html>
<html>
<head profile="http://www.w3.org/2005/10/profile">
<meta charset='UTF-8' />
<link href="//fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="css/featherChat.css">
<link rel="stylesheet" type="text/css" href="css/tooltips.css">
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
<title>feather</title>
</head>
<body>	

<script>
var serverAddr = "<?php echo "ws://".$host.":".$port."/".$address;?>";
</script>

<div id="nameInput">
	<p class="bigText">
		Welcome to feather
	</p>
	<div class="field200">
		<input id="username" type="text" placeholder="username">
	</div>
	<br>
</div>

<div id="chat_wrapper">
	
	<div class="topHalf" id="message_box"></div>
	<div class="topHalf" id="clientList">
	</div>
	<div class="panel">
		<input type="text" id="message" placeholder="Message">
	</div>
</div>

<script src="script/featherChat.js"></script>

</body>
</html>