# This is a basic VCL configuration file for varnish. See the vcl(7)
# man page for details on VCL syntax and semantics.
#
# Default backend definition. Set this to point to your content
# server.
#
backend default {
	.host = "127.0.0.1";
	.port = "80";
}

# Enable flushing access only to internals
acl flushers {
	"127.0.0.1";
}

sub vcl_recv {
	# Set backend
	set req.backend = default;

	# Set a unique cache header with client ip
	if (req.http.x-forwarded-for) {
		set req.http.X-Forwarded-For = req.http.X-Forwarded-For + ", " + client.ip;
	} else {
		set req.http.X-Forwarded-For = client.ip;
	}

	# Allow the backend to deliver old content up to 1 day
	set req.grace = 24h;

	if (req.request != "GET" &&
		req.request != "HEAD" &&
		req.request != "PUT" &&
		req.request != "POST" &&
		req.request != "TRACE" &&
		req.request != "OPTIONS" &&
		req.request != "DELETE") {

		# Add BAN request for acl flushers
		if (req.request == "BAN" || req.request == "BANALL") {
			if (client.ip ~ flushers) {
				if (req.http.X-Host) {
					if (req.url != "/") {
						ban("req.http.host ~ ^" + req.http.X-Host + "$ && req.url ~ ^" + req.url + "[/]?(\?.*)?$");
					} else {
						ban("req.http.host ~ ^" + req.http.X-Host + "$");
					}
					error 200 "OK";
				} else {
					error 400 "Bad Request";
				}
			} else {
				error 405 "Not allowed";
			}
		} else {
			/* Non-RFC2616 or CONNECT which is weird. */
			return (pipe);
		}
	}

	# If we work in backend don't cache anything
	if (req.http.Cookie ~ "be_typo_user") {
		return (pipe);
	}

	# If neither GET nor HEAD request, send to backend but do not cached
	# This means that POST requests are not cached
	if (req.request != "GET" && req.request != "HEAD") {
		return (pass);
	}

	# Always cache the following file types for all users.
	if (req.url ~ "\.(png|gif|jpeg|jpg|ico|swf|css|js|pdf|txt)$") {
		unset req.http.Cookie;
	}

	# If any authorisation was set do not cache
	if (req.http.Authorization || req.http.Cookie ~ "fe_typo_user") {
		return (pass);
	} else {
		# Pass all no_cache=1 sites and ajax requests
		if (req.url ~ "no_cache=1" || req.url ~ "(\?|&)eID=") {
			return (pass);
		}

		# Delete cookies
		unset req.http.Cookie;
	}

	# Handle compression correctly. Different browsers send different
	# "Accept-Encoding" headers, even though they mostly all support the same
	# compression mechanisms.
	if (req.http.Accept-Encoding) {
		if (req.http.Accept-Encoding ~ "gzip") {
			# If the browser supports gzip, that is what we use
			set req.http.Accept-Encoding = "gzip";
		} else if (req.http.Accept-Encoding ~ "deflate") {
			# Next try deflate encoding
			set req.http.Accept-Encoding = "deflate";
		} else {
			# Unknown algorithm. Remove it and send unencoded.
			unset req.http.Accept-Encoding;
		}
	}

	# Lookup in cache
	return (lookup);
}

sub vcl_hash {
	# Build hash depending on url
	hash_data(req.url);

	# Build hash depending on host or server ip
	if (req.http.host) {
		hash_data(req.http.host);
	} else {
		hash_data(server.ip);
	}

	return (hash);
}

sub vcl_fetch {
	# Set default cache to 3 days
	set beresp.ttl = 3d;

	# Deliver old content up to 1 day
	set req.grace = 24h;

	# Set cache to 10 days
	if (req.url ~ "\.(png|gif|jpeg|jpg|ico|swf|css|js|pdf|txt)$") {
		set beresp.ttl = 10d;
	}

	# Delete cookie
	if (req.request == "POST" || req.url ~ "^/typo3" || req.url ~ "no_cache=1" || req.url ~ "(\?|&)eID=") {
	} else {
		unset beresp.http.Set-Cookie;
	}

	if (beresp.http.Set-Cookie || beresp.http.Vary == "*") {
		# Mark as "Hit-For-Pass" for the next 2 minutes
		set beresp.ttl = 120s;
		return (hit_for_pass);
	}

	# Add additional information for acl flushers
	set beresp.http.X-Host = req.http.Host;

	return (deliver);
}

sub vcl_deliver {
	# Remove some suspect headers
	remove resp.http.Age;
	remove resp.http.Via;
	remove resp.http.X-Powered-By;
	remove resp.http.X-Varnish;
	remove resp.http.X-Host;

	return (deliver);
}