.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt

Extension configuration
-----------------------

Varnish Server [basic.server]
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Enter the IP address of your Varnish server. Use a comma seperated list for multiple server support.

HTTP ban method [basic.httpMethod]
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you want to insert an own method which is sent to the Varnish server you can edit it here. Please note that this method has to be equal to the method used in your Varnish configuration file.

HTTP protocol [basic.httpProtocol]
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Set the version of the http protocol which should be used to contact the Varnish server.

Strip slash [basic.stripSlash]
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you use realurl extension (or any other url rendering) with the "appendMissingSlash" configuration you can configure vcc to strip the last slash. This can be useful if you want to customize the BAN handling in your varnish configuration e.g. use regular expressions.

Support index.php script [basic.enableIndexScript]
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you use realurl extension (or any other url rendering) this option enables the cache clearing for alternative
index.php url. This might help your editor to see the latest version if they view the page within the backend.

Logging mode [basic.loggingMode]
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

You can choose between three different types of logging. Log messages are stored in the database table "tx_vcc_log"

- Disabled: Nothing is logged
- Minimal: Only communication with Varnish server is logged
- Debug: Service actions and parameter are logged

Maximum age of log entries [basic.maxLogAge]
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

For each action one or multiple log entries (depends on debug setting) are generated in an own table. To minimize the table size you can set a specific age (in days) for the entries.
