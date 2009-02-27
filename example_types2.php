<?
require("S2ajax.php");
$sajax_request_type = "GET";
sajax_init();
// Note that exported methods are static!
sajax_export('TypeTester$return_array', 'TypeTester$return_object');
sajax_method_export(TypeTester, return_string);
sajax_export(array(TypeTester, return_int), array(TypeTester, return_float));
// And now, let's handle the user's request
sajax_handle_client_request();

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
	sajax_show_javascript();
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
