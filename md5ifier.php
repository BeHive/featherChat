
<html>
<head>

</head>
<body>

<form action="md5ifier.php" method="POST">

<input type="text" name="textInput" placeholder="Message"/>
<input type="submit">
</form>
<?php

if(array_key_exists("textInput",$_POST) && $_POST["textInput"] != ""){
	echo($_POST["textInput"]." : ".md5($_POST["textInput"]));
	echo("<div style='width: 50px; height: 50px; background-color: #".substr(md5($_POST["textInput"]),0,6).";'></div>");
}
?>

</body>
</html>
