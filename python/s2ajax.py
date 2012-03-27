import sys
import inspect
from time import gmtime, strftime
from types import *
import pickle
from pprint import pprint

S2LEGACY  = 0
S2CLASSES = 1
S2OBJECTS = 2

CLASS_FN    = 0
INSTANCE_FN = 1

class S2ajax:
    def __init__(self, s2get, s2post, s2session):
        self.s2shown = False

        self.s2get  = s2get
        self.s2post = s2post
        self.s2session = s2session
        self.s2headers = None

        self.s2version = '1.0'
        self.s2debug   = False
        self.s2reqtype = 'GET'
        self.s2remote  = ''
        self.s2failure = ''
    
        self.s2export = {
            S2LEGACY: {},
            S2CLASSES: {},
            S2OBJECTS: {}
        }

        self.s2cached = {}
        self.s2stubbed = {}

    def js_esc(self, value):
        return value.replace("\\", "\\\\").replace("\r", "\\r").replace("\n", "\\n").replace("'", "\\'").replace('"', '\\"')

    def js_repr(self, value):
        t = type(value)
        if t is NoneType:
            return ''
        if t is InstanceType:
            value = {idx:value.__class__.__dict__[idx] for idx in value.__class__.__dict__ if idx[:2] != '__' and not isinstance(value.__class__.__dict__[idx], (classmethod, staticmethod))}
            t = DictType
        if t is BooleanType:
            return "Boolean(true)" if value else "Boolean(false)"
        if t is IntType:
            return "parseInt(%d)" % value
        if t is FloatType:
            return "parseFloat(%f)" % value
        if t is DictType:
            s = "{ "
            iterated = False
            for k,v in value.iteritems():
                iterated = True
                esc_k = self.js_esc(k)
                try:
                    int(k)
                    s += "%s: %s, " % (k, self.js_repr(v))
                except ValueError:
                    s += "\"%s\": %s, " % (esc_k, self.js_repr(v))
            if iterated:
                s = s[:-2]
            return s + " }"
        return "'%s'" % self.js_esc(value)

    def class_info(self, class_desc):
        class_info_obj = s2ajax.s2cached.get(class_desc.__name__)
        if class_info_obj == None:
            s2ajax.s2cached[class_desc.__name__] = {}
            for k,v in [member for member in  inspect.getmembers(class_desc, predicate = inspect.isfunction) if member[0][:2] != '__']:
                s2ajax.s2cached[class_desc.__name__][k] = CLASS_FN
            for k,v in [member for member in  inspect.getmembers(class_desc, predicate = inspect.ismethod)   if member[0][:2] != '__']:
                s2ajax.s2cached[class_desc.__name__][k] = INSTANCE_FN
            class_info_obj = s2ajax.s2cached[class_desc.__name__]
        return class_info_obj

    def common_js(self):
        if self.s2reqtype != 'GET' and self.s2reqtype != 'POST':
            return "// Invalid type: %s.. \n\n" % self.s2reqtype

        s2ajax_request_type = self.s2reqtype
        s2ajax_debug_mode = "true" if self.s2debug else "false"

        # todo: $s2ajax_uuid_str = ($s2ajax_uuid) ? "var s2ajax_uuid = '$s2ajax_uuid';\n" : '';
        s2ajax_uuid_str = ''

        return """
//
// remote scripting library (c) 2009 Chris F. Ravenscroft
// original awesome remote scripting library (c) copyright 2005 modernmethod, inc
//
%s
var s2ajax_debug_mode = %s;
var s2ajax_request_type = "%s";
var s2ajax_target_id = "";
var s2ajax_failure_redirect = "%s";

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

    uri = "%s";
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
            s2ajax_debug("NULL s2ajax object for user agent: " + navigator.userAgent);
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
        """ % ( s2ajax_uuid_str, s2ajax_debug_mode, s2ajax_request_type, self.s2failure, self.s2remote )

    def get_one_stub(self, func_desc):
        out = ''

        if func_desc[0] == None: # function alone
            func_name = func_desc[1].__name__
            out += """
function %s() {
s2ajax_do_call(null, "%s", arguments); // Deprecated: %s.arguments
}
            """ % (func_name, func_name, func_name)
        else: # class, method name
            class_info = s2ajax.class_info(func_desc[0])
            class_type = class_info.get(func_desc[1])
            class_name = func_desc[0].__name__
            if s2ajax.s2stubbed.get(class_name) == None: # Constructor
                s2ajax.s2stubbed[class_name] = True
                out += """
%s = function() {
    this.uuid = s2ajax_bogus_uuid();
}
""" % (class_name)

            if class_type == INSTANCE_FN:
                func_name = class_name + '.prototype.' + func_desc[1]
                remote_name = class_name + '.' + func_desc[1]
                uuid_str = 'this.uuid'
            elif class_type == CLASS_FN:
                func_name = class_name + '.' + func_desc[1]
                remote_name = func_name
                uuid_str = 'null'
        
            out += """
%s = function() {
s2ajax_do_call(%s, "%s", arguments);
}
            """ % (func_name, uuid_str, remote_name)

        return out

def s2ajax_init(s2get, s2post, s2session):
    global s2ajax

    s2ajax = S2ajax(s2get, s2post, s2session)

# Return values:
# None: nothing happened
# String: error message
# True: OK
def s2ajax_handle_client_request():
    global s2ajax

    mode = ''
    func_name = None
    rsuuid = None

    if s2ajax.s2get.get('rs') != None:
        mode = 'get'
        func_name = s2ajax.s2get.get('rs')
    if s2ajax.s2post.get('rs') != None:
        mode = 'post'
        func_name = s2ajax.s2post.get('rs')
    if mode == '':
        return None

    if(mode == 'get'):
        s2ajax.s2headers = {
            'Expires':'Mon, 26 Jul 1997 05:00:00 GMT',
            'Last-Modified': strftime("%a, %d %b %Y %H:%M:%S +0000", gmtime()),
            'Cache-Control': 'no-cache, must-revalidate',
            'Pragma': 'no-cache'
        }
        if s2ajax.s2get.get('rsargs[]') != None:
            args = s2ajax.s2get.getlist('rsargs[]')
        else:
            args = []
        if s2ajax.s2get.get('rsuuid') != None:
            rsuuid = s2ajax.s2get.get('rsuuid')
    else:
        if s2ajax.s2post.get('rsargs[]') != None:
            args = s2ajax.s2post.getlist('rsargs[]')
        else:
            args = []
        if s2ajax.s2post.get('rsuuid') != None:
            rsuuid = s2ajax.s2post.get('rsuuid')

    if s2ajax.s2export[S2LEGACY].get(func_name) == None:
        return "-:%s not callable" % func_name
    else:
        class_desc, method_name = s2ajax.s2export[S2LEGACY].get(func_name)
        if class_desc == None: # function
            result = method_name(*args)
        else:
            if rsuuid != None: # instance as opposed to class call
                if s2ajax.s2session.get('S2AJAX') == None:
                    s2ajax.s2session['S2AJAX'] = {}
                if s2ajax.s2session['S2AJAX'].get('OBJECTS') == None:
                    s2ajax.s2session['S2AJAX']['OBJECTS'] = {}
                if s2ajax.s2session['S2AJAX']['OBJECTS'].get(rsuuid) != None:
                    thisinstance = pickle.loads(s2ajax.s2session['S2AJAX']['OBJECTS'][rsuuid])
                else:
                    thisinstance = class_desc()
                result = getattr(thisinstance, method_name)(*args)
                pickled = pickle.dumps(thisinstance)
                s2ajax.s2session['S2AJAX']['OBJECTS'][rsuuid] = pickled
                s2ajax.s2session.modified = True
            else:
                try:
                    result = getattr(class_desc, method_name)(*args) # will work is @ staticmethod
                except TypeError:
                    result = getattr(class_desc(), method_name)(*args) # else, instantiate first...
        return "+:var res = %s; res;" % s2ajax.js_repr(result).strip()

    return None

# To provide flexibility, these syntaxes need to be supported:
# s2ajax_export(< function >)
# s2ajax_export(< function >, < function >)
# s2ajax_export([< class >, < method_name >], [< class >, < method_name >])
#
# s2ajax_method_export(< class_name >, < method_name >)
# or sajax_export_method...etc
#
# s2ajax_export_class(< class_name >, [ < method_name >, < method_name >)
# s2ajax_export_class(< class_name >)
#
# Storage:
#
# function_name: None, function
# class_name$method_name: class, method name
#
def s2ajax_export(*args):
    global s2ajax

    for arg in args:
        if not isinstance(arg, (list, tuple)): # function
            index_name, class_desc, method_name = [arg.__name__, None, arg]
        else: # [class_desc, method_name]
            class_desc, method_name = arg

            class_info = s2ajax.class_info(class_desc)
            class_type = class_info.get(method_name)

            if class_type == INSTANCE_FN:
                index_name = class_desc.__name__ + '.' + method_name
            elif class_type == CLASS_FN:
                index_name = class_desc.__name__ + '.' + method_name
            else:
                print "-:Method %s does not exist in class %s" % (method_name, class_desc.__name__) # todo
                return

        s2ajax.s2export[S2LEGACY][index_name] = [class_desc, method_name]

def s2ajax_method_export(class_desc, method_name):
    s2ajax_export_method(class_desc, method_name)

def s2ajax_export_method(class_desc, method_name):
    s2ajax_export([class_desc, method_name])

def s2ajax_export_class(class_desc, methods = None):
    global s2ajax

    if isinstance(methods, (list, tuple)): # a list of methods was provided
        for method_name in methods:
            s2ajax_export([class_desc, method_name])
    else: # full class, minus magic methods
        class_info = s2ajax.class_info(class_desc)
        for k in class_info:
            s2ajax_export([class_desc, k])

def s2ajax_show_javascript():
    global s2ajax

    html = ''

    if s2ajax.s2shown == False:
        html += s2ajax.common_js()
        s2ajax.s2shown = True
        
    for func in s2ajax.s2export[S2LEGACY]:
        html += s2ajax.get_one_stub(s2ajax.s2export[S2LEGACY][func])
    
    print "EXPORTS:"
    pprint(s2ajax.s2export)
    return html
