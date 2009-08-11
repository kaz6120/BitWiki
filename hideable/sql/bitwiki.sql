CREATE TABLE purepage(
	pagename TEXT NOT NULL UNIQUE,
	num INTEGER PRIMARY KEY,	--ページ番号
	source TEXT,
	timestamp INTEGER,	--「タイムスタンプを更新しない」を反映させる、表示のための時刻。
	realtimestamp INTEGER	--最終更新時刻。「タイムスタンプを更新しない」を反映しない。
);
CREATE INDEX index_purepage_pagename ON purepage(pagename);


CREATE VIEW page AS
	SELECT * FROM purepage WHERE (source != '' AND pagename NOT LIKE ':%' AND pagename NOT LIKE '%/:%');

CREATE VIEW allpage AS
	SELECT * FROM purepage WHERE source != '';


CREATE TABLE pagebackup(
	number INTEGER PRIMARY KEY,	--バックアップの番号
	pagename TEXT,
	source TEXT,
	timestamp INTEGER,	--「タイムスタンプを更新しない」を反映させる、表示のための時刻。
	realtimestamp INTEGER	--最終更新時刻。「タイムスタンプを更新しない」を反映しない。
);
CREATE INDEX index_pagebackup_pagename ON pagebackup(pagename);


CREATE TABLE autolink(
	dir TEXT PRIMARY KEY,
	exp TEXT
);


CREATE TABLE cache(
	key TEXT PRIMARY KEY,
	data TEXT
);


CREATE TABLE linklist(
	linker TEXT,
	linked TEXT,
	times INTEGER
);
CREATE INDEX index_linklist_linked ON linklist(linked);


CREATE TABLE attach(
	pagename TEXT,
	filename TEXT,
	binary BLOB,
	size INTEGER,
	timestamp INTEGER,
	count INTEGER
);
CREATE UNIQUE INDEX index_attach ON attach(pagename, filename);
