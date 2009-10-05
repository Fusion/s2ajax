# ACKNOWLEDGMENTS:

The original package, Sajax, was written by the smart people at Modern Method and you can find more information on that package at [their web site](http://www.modernmethod.com/sajax/)

Unlike this package, at least so far, the original package also supports Perl, CFM, Lua, IO, ASP and Ruby

I wrote this new package to add support for PHP objects plus a couple other refinements but 99% of the credit should, again, go to these guys.

![S2ajax logo](S2ajax2.png)

Note: the blocks that are commented out have no specific use here.

They leverage my simple framework ([link](http://github.com/Fusion/lenses/tree/master)) that in turns leverages PHP5's magic properties through a class loader.

# WHAT'S NEW:

v1.0 introduces real class export. When a PHP class is instantiated in JavaScript, it is now fully handled like a regular JavaScript class with no special syntax.
Of course there are limitations but they are good practice anyway: use accessors to manipulate a class variables and do not access static variables from the client-side. 

# HOWTO:

**Simple Syntax for exporting PHP methods to Javascript:**

* s2ajax_export(function) *Export a function*
* s2ajax_export(function1, function2, ...) *Export multiple functions*
* s2ajax_export(class$staticmethod) *Export a static method*
* s2ajax_export(array(class, method)) *Also export a static method*
* s2ajax_export(function1, class$staticmethod, array(class, method), function2, ...) *Export a mix of functions and static methods*

`s2ajax_export` is used to publish functions and methods. In v1.0, it is now possible to export complete classes.

*Example:*

    s2ajax_export(getBluePencil, getGreenPencil, 'Shapes$square', array(Shapes, circle))
    function getBluePencil() { return 'blue pencil'; }
    function getGreenPencil() { return 'green pencil'; }
    class Shapes
    {
        static function square()
        {
            return 'A square shape';
        }
    
        static function circle($color)
        {
            return 'A '.$color.' circle!';
        }
    }

**NEW in v1.0: s2ajax_export_class**

 * s2ajax_export_class(className) *Export a complete class, including all methods*
 * s2ajax_export_class(className, array(method1, method2, ...)) *Export class and specified methods*

This is a radically different approach. Now, a class can be defined in PHP, exported to JavaScript and multiple instances of this class can be instantiated and until we move to a different page, these instances are persisted, as they live both client- and server-side.

*Example:*

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

**Simple Syntax for the JavaScript side:**

* class$method(arguments, callback);

*Example:*

    Shapes$circle('red', got_a_shape);
    function got_a_shape(text_returned)
    {
        alert(text_returned); // 'A red circle!'
    }

**Note:**

Unlike the original Sajax, we do not need to prepend 'x_' before function names.

*Example for a class:*

    <script>
    function display_result(val) {
    	alert(val); // Display new counter value
    }
    var counter = new CounterTester();
    </script>
    <button onclick="counter.increment_counter(display_result);">Increment counter</button>

---

Now, take a look at the example files, they are pretty straightforward.

-Chris.

