-- Separate database for the test suite, so tests never touch your dev data.
CREATE DATABASE agendai_test OWNER agendai;
-- btree_gist powers the "no double booking" exclusion constraint (see docs/ESPEC.md §3.3).
\c agendai
CREATE EXTENSION IF NOT EXISTS btree_gist;
\c agendai_test
CREATE EXTENSION IF NOT EXISTS btree_gist;
