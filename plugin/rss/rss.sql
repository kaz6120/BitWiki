/*
 * $Id: rss.sql,v 1.1.1.1 2005/06/12 15:38:47 youka Exp $
 */
CREATE TABLE plugin_rss(
	url TEXT PRIMARY KEY,
	data TEXT,
	time INTEGER
);
