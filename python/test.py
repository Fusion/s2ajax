# -*- coding: utf-8 -*-
from __future__ import with_statement
from sqlite3 import dbapi2 as sqlite3
from contextlib import closing
from flask import Flask, request, session, g, redirect, url_for, abort, \
     render_template, flash
from types import StringTypes
from s2ajax import *

# configuration

SECRET_KEY = 'cfr_demo' # Change this for your sessions privacy

app = Flask(__name__)
app.config.from_object(__name__)
app.config.from_envvar('TEST_SETTINGS', silent=True)

class MoreColors:
    brown = 4
    magenta = 5
    purple = 6

    def __init__(self):
        pass

class Tester:
    def __init__(self):
        self.counter = 0

    def increment_counter(self):
        self.counter += 1
        return self.counter

    @staticmethod
    def sayhello(name):
        return "Hello, %s" % name

    @staticmethod
    def colors():
        colors = { 'blue':1, 'red':2, 'green':3 }
        return colors

    @staticmethod
    def morecolors():
        morecolors = MoreColors()
        return morecolors

def hello(arg1, arg2):
    return "Oh I am sooo exported, %s" % arg2

@app.before_request
def before_request():
    s2ajax_init(request.args, request.form, session)
    s2ajax_export(hello)
    #s2ajax_export([Tester, 'sayhello'], [Tester, 'colors'])
    s2ajax_export_class(Tester)

def header():
    txt = """
            <html>
            <head>
                <title>A Test!</title>
                <script>
        """
    txt += s2ajax_show_javascript()
    txt += """
function hello_cb(reply) {
    alert(reply);
}
function colors_cb(reply) {
    var out = '';
    var sep = '';
    for(var k in reply) {
        out += sep + k + ' = ' + reply[k];
        sep = "\\n";
    }
    alert(out);
}
function display_result(reply) {
    alert("New value: " + reply);
}

var mytester = new Tester();
                </script>
            </head>
            <body>
            <h1>A test</h1>
<p><button onclick="hello('cruel', 'world', hello_cb);">Test Function</button></p>
<p><button onclick="Tester$sayhello('John', hello_cb);">Test Static Method</button></p>
<p><button onclick="Tester$colors(colors_cb);">A Series Of Colors</button></p>
<p><button onclick="Tester$morecolors(colors_cb);">More Colors</button></p>
<p><button onclick="mytester.increment_counter(display_result);">Increment Counter</button></p>
        """
    return txt

@app.route('/')
def show_entries():
    r = s2ajax_handle_client_request()
    if r == None:
        return header()
    else:
        print r
        return r

if __name__ == '__main__':
    app.run(host='0.0.0.0', debug=True)
