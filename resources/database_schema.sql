PRAGMA journal_mode=WAL;
PRAGMA synchronous=NORMAL;

PRAGMA foreign_keys = ON;

BEGIN TRANSACTION;

PRAGMA user_version = 1;

DROP TABLE IF EXISTS "favourites";
DROP TABLE IF EXISTS "file_history";
DROP TABLE IF EXISTS "tagged_files";
DROP TABLE IF EXISTS "tags";
DROP TABLE IF EXISTS "file_permissions";
DROP TABLE IF EXISTS "files";
DROP TABLE IF EXISTS "mountpoints";
DROP TABLE IF EXISTS "users_in_groups";
DROP TABLE IF EXISTS "groups";
DROP TABLE IF EXISTS "logins";
DROP TABLE IF EXISTS "users";
DROP TABLE IF EXISTS "ci_sessions";

-- CodeIgniter sessions table
CREATE TABLE "ci_sessions"("id" TEXT PRIMARY KEY NOT NULL,"ip_address" TEXT NOT NULL,"timestamp" INTEGER DEFAULT 0 NOT NULL,"data" TEXT DEFAULT '' NOT NULL);CREATE INDEX "ci_sessions_timestamp" ON "ci_sessions"("timestamp");

-- Stores users
CREATE TABLE "users" (
	"id" INTEGER PRIMARY KEY NOT NULL,
	"guid" TEXT NOT NULL, -- reserved for future account synchronization system
	"username" TEXT NOT NULL UNIQUE, -- username used to log in
	"password" TEXT, -- hashed password
	"type" INTEGER NOT NULL, -- 0: administrator, 1: standard user, 2: guest
	"full_name" TEXT, -- User's full name (only used for display)
	"last_password_change" INTEGER, -- last password change (if null, they must change their password at login)
	"settings" TEXT -- JSON object containing user settings
);

-- Stores user logins
CREATE TABLE "logins" (
	"id" INTEGER PRIMARY KEY NOT NULL,
	"user_id" INTEGER NOT NULL REFERENCES "users",
	"client_addr" TEXT,
	"user_agent" TEXT
	"login_time" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	"last_used" INTEGER NOT NULL
);

-- Stores groups
CREATE TABLE "groups" (
	"id" INTEGER PRIMARY KEY NOT NULL,
	"name" TEXT NOT NULL UNIQUE
);

-- Maps users to groups
CREATE TABLE "users_in_groups" (
	"user_id" INTEGER NOT NULL,
	"group_id" INTEGER NOT NULL,
	UNIQUE("user_id", "group_id")
);

-- This table describes which mountpoints exist and where they are hooked into the user-facing filesystem
CREATE TABLE "mountpoints" (
	"id" INTEGER PRIMARY KEY NOT NULL,
	"destination_path" TEXT NOT NULL UNIQUE, -- where in the user-facing filesystem the mountpoint is mounted
	"driver" TEXT NOT NULL, -- which fs driver to use
	"driver_options" TEXT -- fs driver options, stored in JSON
);

-- This table is abstracted through the fs driver and doesn't have to be used since files shall not be refered to by their numeric id outside of the driver.
CREATE TABLE "files" (
	"id" INTEGER PRIMARY KEY NOT NULL,
	"mountpoint_id" INTEGER NOT NULL REFERENCES "mountpoints", -- which mountpoint this file is part of
	"path_in_mountpoint" TEXT NOT NULL, -- path inside of the mountpoint
	"display_name" TEXT -- human-friendly display name
	"type" INTEGER NOT NULL DEFAULT 0, -- type of file; 0: file, 1: directory
	"mountpoint_driver_info" TEXT NOT NULL, -- JSON object with file information stored by fs driver
	UNIQUE("mountpoint_id", "path_in_mountpoint")
);

-- Defines the permissions on a file for a group. Abstracted through the fs driver
CREATE TABLE "file_permissions" (
	"id" INTEGER PRIMARY KEY NOT NULL,
	"file_id" INTEGER NOT NULL REFERENCES "files",
	"group_id" INTEGER NOT NULL REFERENCES "groups", -- the group that has access set
	"owner_user_id" INTEGER REFERENCES "users", -- the user that can change owner or permissions
	"read" INTEGER NOT NULL, -- boolean whether the group can read the file
	"write" INTEGER NOT NULL, -- boolean whether the group can modify the file
	"share" INTEGER NOT NULL, -- boolean whether the group can share the file to another user
	"expires" INTEGER -- when the file permission is no longer valid, this is what file sharing uses
);

-- Contains the available tags
CREATE TABLE "tags" (
	"id" INTEGER PRIMARY KEY NOT NULL,
	"name" TEXT NOT NULL UNIQUE,
	"description" TEXT
);

-- Describes the tags on each file, managed by the fs driver
CREATE TABLE "tagged_files" (
	"file_id" INTEGER NOT NULL REFERENCES "files",
	"tag_id" INTEGER NOT NULL REFERENCES "tags",
	PRIMARY KEY("file_id", "tag_id")
);

-- Stores the file history of users
CREATE TABLE "file_history" (
	"id" INTEGER PRIMARY KEY,
	"user_id" INTEGER NOT NULL REFERENCES "users",
	"file_path" TEXT NOT NULL,
	"timestamp" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now')),
	"action" TEXT NOT NULL
);

CREATE TABLE "favourites" (
	"id" INTEGER PRIMARY KEY,
	"user_id" INTEGER NOT NULL REFERENCES "users",
	"file_path" TEXT NOT NULL,
	"sort_order" INTEGER NOT NULL
);

-- Contains search index
CREATE TABLE "search_index" (
	"id" INTEGER PRIMARY KEY NOT NULL,
	"file_path" TEXT NOT NULL,
	"keyword" TEXT NOT NULL, -- lower-cased keyword
	"type" TEXT, -- first part of "Content-Type" header or "directory" for directories
	"mtime" INTEGER, -- last modified time
	"size" INTEGER, -- file size
	"last_indexed" INTEGER NOT NULL DEFAULT (STRFTIME('%s', 'now'))
);
-- Index everything (bad for updating the index, very good for reading the index)
CREATE INDEX "search_index_file_path" ON "search_index"("file_path");
CREATE INDEX "search_index_keyword" ON "search_index"("keyword");
CREATE INDEX "search_index_type" ON "search_index"("type");
CREATE INDEX "search_index_mtime" ON "search_index"("mtime");
CREATE INDEX "search_index_size" ON "search_index"("size");
CREATE INDEX "search_index_last_indexed" ON "search_index"("last_indexed");

COMMIT TRANSACTION;
