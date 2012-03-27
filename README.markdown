# ACKNOWLEDGMENTS:

The original package, Sajax, was written by the smart people at Modern Method and you can find more information on that package at [their web site](http://www.modernmethod.com/sajax/)

I originally wrote this new package to add support for PHP objects plus a couple other refinements but 99% of the credit should, again, go to these guys.

Unlike this package, at least so far, the original package also supports Perl, CFM, Lua, IO, ASP and Ruby

![S2ajax logo](S2ajax2.png)

# ABOUT

I wrote this package to add seamless support between Python < - > JavaScript and PHP < - > JavaScript so that backend objects's methods could be selectively exposed to the frontend JavaScript. In the process I added persistence over multiple Ajax calls, which is quite useful in a "share nothing" context (this concept was introduced so that multiple clients could not share the same memory space, not multiple queries from the same client, but of course nobody had Ajax in mind when it was designed, so this is one of the benefits of this package)

## UPDATE [03/2012]: PYTHON

Good Python solutions are finally available to work with most popular web servers (Apache, Lighttpd, Nginx...).
I can finally work with Python as my primary web language.
Therefore, here is S2ajax!

### HOWTO:

Very similar to PHP's howto found below.
However, I did away with the class_name$method_name syntax favoured in the old implementation.
Available:

* s2ajax_export(< function >)
* s2ajax_export(< function >, < function >)
* s2ajax_export([< class >, < method_name >], [< class >, < method_name >])
* s2ajax_method_export(< class_name >, < method_name >)
* or sajax_export_method...etc
* s2ajax_export_class(< class_name >, [ < method_name >, < method_name >)
* s2ajax_export_class(< class_name >)

It is still possible to invoke a function or a method.
However, methods can now be invoked either as class method or as instance method.
I recommend having a close look at test.py but this boils down to:

#### Class method

    class MyClass:
        @staticmethod
        def my_class_function(arg1, arg2):
            pass

    # JavaScript Invocation:
    MyClass.my_class_function(1, 2);

#### Instance method

    class MyClass:
        def __init__(self):
            # local initializations to self
            pass
        def my_instance_function(self, arg1, arg2):
            # modifications to self, etc.

        # JavaScript Invocation:
        var my_instance = new MyClass();
        my_instance(1, 2);

Did you notice the simple `@staticmethod` annotation?
It's all the magic that's necessary to declare a class method in Python.

Note that, unlike PHP, Python on the web can be used with different WSGI packages. As a result, it is difficult to predict what will be made available to our package and in what form.
I addressed this situation by making it mandatory for you, when calling s2ajax_init(), to pass three arguments to this call:

1. a dictionary of arguments passed using the current 'GET' request, if there is one
2. a dictionary of form fields passed using the current 'POST' request, if there is one
3. a session object; i.e. a persistent representation or your end-user's session

Note that you need either 1. or 2.

A very simple way to test this package is using the [Flask micro-framework](http://flask.pocoo.org/); test.py uses that framework's built-in variables when invoking s2ajax.

## PHP

Note: the blocks that are commented out have no specific use here.

They leverage my simple framework ([link](http://github.com/Fusion/lenses/tree/master)) that in turns leverages PHP5's magic properties through a class loader.

### WHAT'S NEW:

v1.0 introduces real class export. When a PHP class is instantiated in JavaScript, it is now fully handled like a regular JavaScript class with no special syntax.
Of course there are limitations but they are good practice anyway: use accessors to manipulate a class variables and do not access static variables from the client-side. 

### HOWTO:

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

