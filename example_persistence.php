<?
require("S2ajax.php");
$s2ajax_request_type = "GET";
// Note that exported methods are static!
s2ajax_export_class(
	InstanceTester,
	array(
		'set_value',
		'get_value'
	)
);

s2ajax_export_class(CounterTester);

// And now, let's handle the user's request
s2ajax_handle_client_request();

class InstanceTester
{
	// Our little buddy will be happily serialized
	private $value;
	// See the variable below? It is static therefore it cannot be persisted: poof!
	private static $staticvalue;

	public function set_value($value) {
		$this->value = $value;
		return "Assigned $value to class";
	}

	public function get_value() {
		return "Retrieve value = ".$this->value;
	}
}

class CounterTester
{
	private $counter;

	function __construct() {
		$this->counter = 0;
	}

	public function increment_counter() {
		$this->counter++;
		return $this->counter;
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
	alert(val);
}

var var_a = new InstanceTester();
var var_b = new InstanceTester();
var counter = new CounterTester();
</script>
<body>
<button onclick="var_a.set_value('[A]', display_result);">Set instance 'a'</button>
<button onclick="var_b.set_value('[B]', display_result);">Set instance 'b'</button>
<button onclick="var_a.get_value(display_result);">Get instance 'a'</button>
<button onclick="var_b.get_value(display_result);">Get instance 'b'</button>
<button onclick="counter.increment_counter(display_result);">Increment counter</button>
</body>
</html>
