CREATE TABLE plugin_referrer(
	pagename TEXT,
	url TEXT,
	count INTEGER
);
CREATE INDEX plugin_referrer_index_pagename ON plugin_referrer(pagename);
