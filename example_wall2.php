<?php
//
// The world's least efficient wall implementation
//
require("S2ajax.php");
$sajax_request_type = "GET";
sajax_init();
// Note that exported methods are static!
sajax_method_export(Wall, add_line);
sajax_method_export(Wall, server_refresh);
// And now, let's handle the user's request
sajax_handle_client_request();	

class Wall
{
	function __construct()
	{
	}

	function colorify_ip($ip)
	{
		$parts = explode(".", $ip);
		$color = sprintf("%02s", dechex($parts[1])) .
				 sprintf("%02s", dechex($parts[2])) .
				 sprintf("%02s", dechex($parts[3]));
		return $color;
	}
	
	static function add_line($msg)
	{
		$f = fopen("/tmp/wall.html", "a");
		$dt = date("Y-m-d h:i:s");
		$msg = strip_tags(stripslashes($msg));
		$remote = $_SERVER["REMOTE_ADDR"];
		// generate unique-ish color for IP
		$color = self::colorify_ip($remote);
		fwrite($f, "<span style=\"color:#$color\">$dt</span> $msg<br>\n");
		fclose($f);
	}
	
	static function server_refresh()
	{
		$lines = file("/tmp/wall.html");
		// return the last 25 lines
		return join("\n", array_slice($lines, -25));
	}
}
?>
<html>
<head>
	<title>Wall</title>
	<style>
	.date { 
		color: blue;
	}
	</style>
	<script>
	<?
	sajax_show_javascript();
	?>
	
	var check_n = 0;
	var old_data = "--";
	
	function refresh_cb(new_data) {	
		if (new_data != old_data) {
			document.getElementById("wall").innerHTML = new_data;
			setTimeout("refresh()", 1000);
			old_data = new_data;
		} else {
			setTimeout("refresh()", 2500);
		}
		document.getElementById("status").innerHTML = "Checked #" + check_n++;
	}
	
	function refresh() {
		document.getElementById("status").innerHTML = "Checking..";
		Wall$server_refresh(refresh_cb);
	}
	
	function add_cb() {
		// we don't care..
	}

	function add() {
		var line;
		var handle;
		handle = document.getElementById("handle").value;
		line = document.getElementById("line").value;
		if (line == "") 
			return;
		sajax_request_type = "POST";
		Wall$add_line("[" + handle + "] " + line, add_cb);
		document.getElementById("line").value = "";
	}
	</script>
	
</head>
<body onload="refresh();">

<form name="f" action="#" onsubmit="add();return false;">
	<b><a href="http://www.modernmethod.com/sajax">Sajax</a>
	v<?= $sajax_version; ?></b>
	-
	You are a guinea pig
	-
	This example illustrates the simplest possible graffiti wall.
	It isn't meant to be perfect, featureful, or even useful.<br/>
	
	<input type="text" name="handle" id="handle" value="(name)"
		onfocus="this.select()" style="width:130px;">
	<input type="text" name="line" id="line" value="(enter your message here)"
		onfocus="this.select()"
		style="width:300px;">
	<input type="button" name="check" value="Post message"
		onclick="add(); return false;">
	<div id="wall"></div>
	<div id="status"><em>Loading..</em></div>
</form>
	
</body>
</html>
