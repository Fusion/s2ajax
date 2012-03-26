<?
require("S2ajax.php");
$s2ajax_request_type = "GET";
// Note that exported methods are static!
s2ajax_export('TypeTester$return_array', 'TypeTester$return_object');
s2ajax_method_export(TypeTester, return_string);
s2ajax_export(array(TypeTester, return_int), array(TypeTester, return_float));
// And now, let's handle the user's request
s2ajax_handle_client_request();

class MyObj
{
var $name, $age;

	function MyObj($name, $age)
	{
		$this->name = $name;
		$this->age = $age;
	}
}

class TypeTester
{
	static function return_array() {
		return array("name" => "Tom", "age" => 26);
	}
	
	static function return_object() {
		$o = new MyObj("Tom", 26);
		return $o;
	}
	
	static function return_string() {
		return "Name: Tom / Age: 26";
	}
	
	static function return_int() {
		return 26;
	}
	
	static function return_float() {
		return 26.25;
	}
}
?>
<html>
<head>
<script>
<?
	s2ajax_show_javascript();
?>
function display_result(val) {
	var repr;
	
	repr  = "";
	repr += "Type: " + typeof val + "\n";
	repr += "Value: ";
	if (typeof val == "object" ||
		typeof val == "array") {
		repr += "{ ";
		for (var i in val) 
			repr += i + ": " + val[i] + ", ";
		repr = repr.substr(0, repr.length-2) + " }";
	} else {
		repr += val;
	}
	alert(repr);
}
</script>
<body>
<button onclick="TypeTester$return_array(display_result);">Return as array (will become an object)</button>
<button onclick="TypeTester$return_object(display_result);">Return as object</button>
<button onclick="TypeTester$return_string(display_result);">Return as string</button>
<button onclick="TypeTester$return_int(display_result);">Return as int</button>
<button onclick="TypeTester$return_float(display_result);">Return as float/double</button>
</body>
</html>
