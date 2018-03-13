# vcc

Extension to clear Varnish cache on demand.

## Extension settings

**Varnish Server**

Comma separated list of IP addresses of your Varnish server(s).

**HTTP ban method**

HTTP method to send to the Varnish server(s) to invalidate cache. Default: *BAN*

**HTTP protocol**

The HTTP protocol to use for the HTTP method.

**Cache handling**

- *Disabled*: No request is sent to the Varnish server(s).
- *Automatic*: Requests are sent immediately after a record was saved.
- *Manuel*: An icon is added to the toolbar and requests have to be triggered manually.

**Strip slash**

Strip appended slash in requested url to be able to adjust behaviour in Varnish vcl configuration.

**Support index.php script**

If enabled a request for index.php?id= script is sent as well.

**Logging mode**

- *Disabled*: No log information are stored in database.
- *Minimal*: One log information for each request is stored in database.
- *Debug*: Multiple log information for each request are stored in database.

**Maximum age of log entries**

Sets the maximum age of log entries in days.
