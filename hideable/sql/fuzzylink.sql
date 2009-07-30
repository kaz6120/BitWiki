CREATE TABLE fuzzylink_list(
	exp TEXT,
	pagename TEXT
);
CREATE INDEX index_fuzzylink_list_exp ON fuzzylink_list(exp);
