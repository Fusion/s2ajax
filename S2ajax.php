<?php	
/*
 * Copyright (c) 2009, Chris F. Ravenscroft
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 * 
 *   * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 *   * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 *   * The names of the contributors may be used to endorse or promote products derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/*
 * ACKNOWLEDGMENTS:
 *
 * The original package, Sajax, was written by the smart people at Modern Method and you can find more information
 * on that package at http://www.modernmethod.com/sajax/
 * Unlike this package, at least so far, the original package also supports Perl, CFM, Lua, IO, ASP and Ruby
 * I wrote this new package to add support for PHP objects plus a couple other refinements but 99% of the credit
 * should, again, go to these guys.
 *
 * Note: the blocks that are commented out have no specific use here.
 * They leverage my simple framework (http://github.com/Fusion/lenses/tree/master) that in turns
 * leverages PHP5's magic properties through a class loader.
 */

/*
 * CONVENTIONS:
 *
 * function names starting with an underscore ('_') are considered private and not part
 * of the public API.
 */

/*
 * PUBLIC API:
 *
 * s2ajax_export(function)
 * s2ajax_export(function1, function2, ...)
 * s2ajax_export(class$staticmethod)
 * s2ajax_export(array(class, method))
 * s2ajax_export(function1, class$staticmethod, array(class, method), function2, ...)
 *
 * s2ajax_export_method(clName, fnName)
 *
 * s2ajax_export_class(className)
 * s2ajax_export_class(className, array(method1, method2, ...))
 *
 * s2ajax_export_object(obj)
 * ...
 * s2ajax_handle_client_request();
 * ...
 * s2ajax_show_javascript();
 */

if (!isset($S2AJAX_INCLUDED)) {

	/*  
	 * GLOBALS AND DEFAULTS
	 *
	 */ 
	define('LEGACY',   0);
	define('CLASSES',  1);
	define('OBJECTS',  2);

	$GLOBALS['s2ajax_version'] = '1.0';	
	$GLOBALS['s2ajax_debug_mode'] = false;
	$GLOBALS['s2ajax_export_list'] = array(LEGACY => array(), CLASSES => array(), OBJECTS => array());
	$GLOBALS['s2ajax_request_type'] = 'GET';
	$GLOBALS['s2ajax_remote_uri'] = '';
	$GLOBALS['s2ajax_failure_redirect'] = '';
	
	/*
	 * CODE
	 *
	 */ 
	 
	//
	// Initialize the S2ajax library.
	// Only here for backward compatibility. Not needed anymore.
	//
	function s2ajax_init() {
	}
	
	/*
	 * Public API starts here
	 */
	function s2ajax_handle_client_request() {
		global $s2ajax_export_list, $inflector;
		
		$mode = "";
		
		if (! empty($_GET["rs"])) 
			$mode = "get";
		
		if (!empty($_POST["rs"]))
			$mode = "post";
			
		if (empty($mode)) 
			return;

		$target = "";
		$rsuuid = false;
		
		if ($mode == "get") {
			// Bust cache in the head
			header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
			header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			// always modified
			header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
			header ("Pragma: no-cache");                          // HTTP/1.0
			$func_name = $_GET["rs"];
			if (! empty($_GET["rsargs"])) 
				$args = $_GET["rsargs"];
			else
				$args = array();
			if(!empty($_GET["rsuuid"]))
				$rsuuid = $_GET["rsuuid"];
		}
		else {
			$func_name = $_POST["rs"];
			if (! empty($_POST["rsargs"])) 
				$args = $_POST["rsargs"];
			else
				$args = array();
			if(!empty($_POST["rsuuid"]))
				$rsuuid = $_POST["rsuuid"];
		}
		
		list($cn, $mn) = explode('$', $func_name);
		if(!empty($mn))
		{
			// A method, not a function...currently only works in a controller
#			include('controllers/' . $inflector->inflectee($cn)->toFile()->value() . '.php');
		}
		// CHECK!
		if($rsuuid) {
			if(	empty($s2ajax_export_list[CLASSES][$cn]) ||
				empty($s2ajax_export_list[CLASSES][$cn]['methods'][$mn])) {
				echo "-:$func_name not callable";
			}
			else {
				// Grab/create instance
				if('' == session_id()) {
					session_start();
				}

				if(!isset($_SESSION['S2AJAX'])) {
					$_SESSION['S2AJAX'] = array(OBJECTS => array());
				}
				if(!isset($_SESSION['S2AJAX'][OBJECTS][$rsuuid])) {
					$instance = new $cn();
				}
				else {
					$instance = unserialize(
						$_SESSION['S2AJAX'][OBJECTS][$rsuuid]);
				}

				echo "+:";
				$result = call_user_func_array(array($instance, $mn), $args);

				$si = serialize($instance);
				$_SESSION['S2AJAX'][OBJECTS][$rsuuid] = $si;

				echo "var res = " . trim(_s2ajax_get_js_repr($result)) . "; res;";
			}
			exit;
		}

		if(empty($s2ajax_export_list[LEGACY][$func_name]))
			echo "-:$func_name not callable";
		else {
			echo "+:";
			list($cn, $mn) = $s2ajax_export_list[LEGACY][$func_name];
			if(empty($cn))
				$result = call_user_func_array($func_name, $args);
			else
			{
				/*
				if(!method_exists($cn, $mn))
				{
					// Special case: the method we are looking for was in fact
					// declared in a mixin object -- or not.
					$links = call_user_func_array(array($cn, 'links'), array());
					$found = false;
					foreach($links as $key)
					{
						$packages = ClassLoader::instance()->packages($key);
						foreach($packages as $package)
						{
							if(method_exists($package, $mn))
							{
								$cn = $package;
								$found = true;
								break;
							}
						}
						if($found)
							break;
					}

				}
				*/
				$result = call_user_func_array(array($cn, $mn), $args);
			}
			echo "var res = " . trim(_s2ajax_get_js_repr($result)) . "; res;";
		}
		exit;
	}

	function s2ajax_export() {
		global $s2ajax_export_list;
		
		$n = func_num_args();
		for ($i = 0; $i < $n; $i++) {
			$nn = func_get_arg($i);
			if(is_array($nn))
			{
				list($cn, $mn) = $nn;
				$nn = $cn . '$' . $mn;
			}
			else
				list($cn, $mn) = explode('$', $nn);
			if(empty($mn))
			{
				$mn = $cn;
				$cn = null;
			}
			$s2ajax_export_list[LEGACY][$nn] = array($cn, $mn);
		}
	}
	
	// Only here for backward compatibility. This was a bad function name.
	function s2ajax_method_export($clName, $fnName) {
		s2ajax_export_method($clName, $fnName);		
	}

	function s2ajax_export_method($clName, $fnName) {
		s2ajax_export($clName . '$' . $fnName);
	}

	function s2ajax_export_class($className, $methods = null) {
		global $s2ajax_export_list;

		$s2ajax_export_list[CLASSES][$className] = array('methods' => array());

		if(is_array($methods)) {
			foreach($methods as $method) {
				$s2ajax_export_list[CLASSES][$className]['methods'][$method] = true;
			}
		}
		else
		{
			$classExplorer = new ReflectionClass($className);
			foreach($classExplorer->getMethods() as $method) {
				if($method->isPublic()) {
					$s2ajax_export_list[CLASSES][$className]['methods'][$method->getName()] = true;
				}
			}
		}
	}

	/*
 	 * Not yet. I am not sure that we will ever have a need to export instances directly from PHP.
	 * If we do, this code is a good place to start.
	 *
	function s2ajax_export_object($obj) {
		global $s2ajax_export_list;
		global $s2ajax_uuid;

		$s2ajax_uuid = 'instance_' . uniqid();
		if('' == session_id()) {
			session_start();
		}
		$_SESSION['S2AJAX'][$s2ajax_uuid] = array();

		$s = serialize($obj);
		$_SESSION['S2AJAX'][$s2ajax_uuid]['obj'] = $s;

		$varName = false;
		foreach($GLOBALS as $name => $value) {
			if($obj === $value) {
				$varName = $name;
			}
		}
		if(!$varName) {
			die("In s2ajax_object_export(): please use a global object");
		}

		$s2ajax_export_list[OBJECTS][$varName] = array('methods' => array());

		$classExplorer = new ReflectionClass($obj);
		foreach($classExplorer->getMethods() as $method) {
			if($method->isPublic()) {
				$s2ajax_export_list[OBJECTS][$varName]['methods'][$method->getName()] = true;
			}
		}
	}
	*/

	$s2ajax_js_has_been_shown = 0;
	function s2ajax_show_javascript()
	{
		global $s2ajax_js_has_been_shown;
		global $s2ajax_export_list;
		
		$html = "";
		if (! $s2ajax_js_has_been_shown) {
			$html .= _s2ajax_get_common_js();
			$s2ajax_js_has_been_shown = 1;
		}
		foreach ($s2ajax_export_list[LEGACY] as $func) {
			$html .= _s2ajax_get_one_stub($func);
		}
		foreach ($s2ajax_export_list[CLASSES] as $className => $struct) {
			$html .= _s2ajax_get_one_c_stub($className, $struct);
		}
		foreach ($s2ajax_export_list[OBJECTS] as $varName => $struct) {
			$html .= _s2ajax_get_one_o_stub($varName, $struct);
		}
		echo $html;
	}
	
	/*
	 * Private functions start here
	 * You can stop reading unless you are modifying S2ajax itself.
	 */
	
	//
	// Helper function to return the script's own URI. 
	// 
	function _s2ajax_get_my_uri() {
		return $_SERVER["REQUEST_URI"];
	}
	$s2ajax_remote_uri = _s2ajax_get_my_uri();
	
	//
	// Helper function to return an eval()-usable representation
	// of an object in JavaScript.
	// 
	function _s2ajax_get_js_repr($value) {
		$type = gettype($value);
		
		if ($type == "boolean") {
			return ($value) ? "Boolean(true)" : "Boolean(false)";
		} 
		elseif ($type == "integer") {
			return "parseInt($value)";
		} 
		elseif ($type == "double") {
			return "parseFloat($value)";
		} 
		elseif ($type == "array" || $type == "object" ) {
			//
			// XXX Arrays with non-numeric indices are not
			// permitted according to ECMAScript, yet everyone
			// uses them.. We'll use an object.
			// 
			$s = "{ ";
			if ($type == "object") {
				$value = get_object_vars($value);
			} 
			foreach ($value as $k=>$v) {
				$esc_key = _s2ajax_esc($k);
				if (is_numeric($k)) 
					$s .= "$k: " . _s2ajax_get_js_repr($v) . ", ";
				else
					$s .= "\"$esc_key\": " . _s2ajax_get_js_repr($v) . ", ";
			}
			if (count($value))
				$s = substr($s, 0, -2);
			return $s . " }";
		} 
		else {
			$esc_val = _s2ajax_esc($value);
			$s = "'$esc_val'";
			return $s;
		}
	}
	
	function _s2ajax_get_common_js() {
		global $s2ajax_uuid;
		global $s2ajax_debug_mode;
		global $s2ajax_request_type;
		global $s2ajax_remote_uri;
		global $s2ajax_failure_redirect;
		
		$t = strtoupper($s2ajax_request_type);
		if ($t != "" && $t != "GET" && $t != "POST") 
			return "// Invalid type: $t.. \n\n";
		
		$s2ajax_uuid_str = ($s2ajax_uuid) ? "var s2ajax_uuid = '$s2ajax_uuid';\n" : '';

		ob_start();
		?>
		
		//
		// remote scripting library (c) 2009 Chris F. Ravenscroft
		// original awesome remote scripting library (c) copyright 2005 modernmethod, inc
		//
		<?php echo $s2ajax_uuid_str; ?>
		var s2ajax_debug_mode = <?php echo $s2ajax_debug_mode ? "true" : "false"; ?>;
		var s2ajax_request_type = "<?php echo $t; ?>";
		var s2ajax_target_id = "";
		var s2ajax_failure_redirect = "<?php echo $s2ajax_failure_redirect; ?>";
		
		function s2ajax_debug(text) {
			if (s2ajax_debug_mode)
				alert(text);
		}
		
		function s2ajax_4c() {
			return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
		}

		function s2ajax_bogus_uuid() {
			return (
				s2ajax_4c() +
				s2ajax_4c() +
				'-' +
				s2ajax_4c() +
				'-' +
				s2ajax_4c() +
				'-' +
				s2ajax_4c() +
				'-' +
				s2ajax_4c() +
				s2ajax_4c() +
				s2ajax_4c());
		}
	
 		function s2ajax_init_object() {
 			s2ajax_debug("s2ajax_init_object() called..")
 			
 			var A;
 			
 			var msxmlhttp = new Array(
				'Msxml2.XMLHTTP.5.0',
				'Msxml2.XMLHTTP.4.0',
				'Msxml2.XMLHTTP.3.0',
				'Msxml2.XMLHTTP',
				'Microsoft.XMLHTTP');
			for (var i = 0; i < msxmlhttp.length; i++) {
				try {
					A = new ActiveXObject(msxmlhttp[i]);
				} catch (e) {
					A = null;
				}
			}
 			
			if(!A && typeof XMLHttpRequest != "undefined")
				A = new XMLHttpRequest();
			if (!A)
				s2ajax_debug("Could not create connection object.");
			return A;
		}
		
		var s2ajax_requests = new Array();
		
		function s2ajax_cancel() {
			for (var i = 0; i < s2ajax_requests.length; i++) 
				s2ajax_requests[i].abort();
		}
		
		function s2ajax_do_call(uuid, func_name, args) {
			var i, x, n;
			var uri;
			var post_data;
			var target_id;
			
			s2ajax_debug("in s2ajax_do_call().." + s2ajax_request_type + "/" + s2ajax_target_id);
			target_id = s2ajax_target_id;
			if (typeof(s2ajax_request_type) == "undefined" || s2ajax_request_type == "") 
				s2ajax_request_type = "GET";
			
			uri = "<?php echo $s2ajax_remote_uri; ?>";
			if (s2ajax_request_type == "GET") {
			
				if (uri.indexOf("?") == -1) 
					uri += "?rs=" + escape(func_name);
				else
					uri += "&rs=" + escape(func_name);
				uri += "&rst=" + escape(s2ajax_target_id);
				uri += "&rsrnd=" + new Date().getTime();
				if(uuid) {
					uri += "&rsuuid=" + uuid;
				}
				
				for (i = 0; i < args.length-1; i++) 
					uri += "&rsargs[]=" + escape(args[i]);

				post_data = null;
			} 
			else if (s2ajax_request_type == "POST") {
				post_data = "rs=" + escape(func_name);
				post_data += "&rst=" + escape(s2ajax_target_id);
				post_data += "&rsrnd=" + new Date().getTime();
				if(uuid) {
					post_data += "&rsuuid=" + uuid;
				}
				
				for (i = 0; i < args.length-1; i++) 
					post_data = post_data + "&rsargs[]=" + escape(args[i]);
			}
			else {
				alert("Illegal request type: " + s2ajax_request_type);
			}
			
			x = s2ajax_init_object();
			if (x == null) {
				if (s2ajax_failure_redirect != "") {
					location.href = s2ajax_failure_redirect;
					return false;
				} else {
					s2ajax_debug("NULL s2ajax object for user agent:\n" + navigator.userAgent);
					return false;
				}
			} else {
				x.open(s2ajax_request_type, uri, true);
				// window.open(uri);
				
				s2ajax_requests[s2ajax_requests.length] = x;
				
				if (s2ajax_request_type == "POST") {
					x.setRequestHeader("Method", "POST " + uri + " HTTP/1.1");
					x.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				}
			
				x.onreadystatechange = function() {
					if (x.readyState != 4) 
						return;

					s2ajax_debug("received " + x.responseText);
				
					var status;
					var data;
					var txt = x.responseText.replace(/^\s*|\s*$/g,"");
					status = txt.charAt(0);
					data = txt.substring(2);

					if (status == "") {
						// let's just assume this is a pre-response bailout and let it slide for now
					} else if (status == "-") 
						alert("Error: " + data);
					else {
						if (target_id != "") 
							document.getElementById(target_id).innerHTML = eval(data);
						else {
							try {
								var callback;
								var extra_data = false;
								if (typeof args[args.length-1] == "object") {
									callback = args[args.length-1].callback;
									extra_data = args[args.length-1].extra_data;
								} else {
									callback = args[args.length-1];
								}
								callback(eval(data), extra_data);
							} catch (e) {
								s2ajax_debug("Caught error " + e + ": Could not eval " + data );
							}
						}
					}
				}
			}
			
			s2ajax_debug(func_name + " uri = " + uri + "/post = " + post_data);
			x.send(post_data);
			s2ajax_debug(func_name + " waiting..");
			delete x;
			return true;
		}
		
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
	
	// javascript escape a value
	function _s2ajax_esc($val)
	{
		$val = str_replace("\\", "\\\\", $val);
		$val = str_replace("\r", "\\r", $val);
		$val = str_replace("\n", "\\n", $val);
		$val = str_replace("'", "\\'", $val);
		return str_replace('"', '\\"', $val);
	}

	function _s2ajax_get_one_stub($func_desc) {
		// wrapper for [?php echo $func_name; ?]
		if(empty($func_desc[0]))
			$func_name = $func_desc[1];
		else
			$func_name = $func_desc[0] . '$' . $func_desc[1];

		ob_start();	
		?>
		
		function <?php echo $func_name; ?>() {
			s2ajax_do_call(null, "<?php echo $func_name; ?>",
				<?php echo $func_name; ?>.arguments);
		}
		
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
	
	function _s2ajax_get_one_c_stub($varName, $struct) {
		ob_start();
		?>

		function <?php echo $varName; ?>() {
			this.uuid = s2ajax_bogus_uuid();
		}
		<?php
		foreach($struct['methods'] as $method => $ok) {
			$func_name = $varName . '$' . $method;
			echo $varName . '.prototype.' . $method; ?> = function() {
			s2ajax_do_call(this.uuid, "<?php echo $func_name; ?>", arguments);
		}
		<?php
		}

		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	function _s2ajax_get_one_o_stub($varName, $struct) {
		ob_start();
		?>

		function <?php echo $varName; ?>() {
		}

		<?php
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	$S2AJAX_INCLUDED = 1;
}
?>
