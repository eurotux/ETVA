
-----------------------------------------------------------------------------
-- sf_guard_group
-----------------------------------------------------------------------------

DROP TABLE [sf_guard_group];


CREATE TABLE [sf_guard_group]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[name] VARCHAR(255)  NOT NULL,
	[description] MEDIUMTEXT,
	UNIQUE ([name])
);

-----------------------------------------------------------------------------
-- sf_guard_permission
-----------------------------------------------------------------------------

DROP TABLE [sf_guard_permission];


CREATE TABLE [sf_guard_permission]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[name] VARCHAR(255)  NOT NULL,
	[description] MEDIUMTEXT,
	UNIQUE ([name])
);

-----------------------------------------------------------------------------
-- sf_guard_group_permission
-----------------------------------------------------------------------------

DROP TABLE [sf_guard_group_permission];


CREATE TABLE [sf_guard_group_permission]
(
	[group_id] INTEGER  NOT NULL,
	[permission_id] INTEGER  NOT NULL
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([group_id]) REFERENCES sf_guard_group ([id])

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([permission_id]) REFERENCES sf_guard_permission ([id])

-----------------------------------------------------------------------------
-- sf_guard_user
-----------------------------------------------------------------------------

DROP TABLE [sf_guard_user];


CREATE TABLE [sf_guard_user]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[username] VARCHAR(128)  NOT NULL,
	[algorithm] VARCHAR(128) default 'sha1' NOT NULL,
	[salt] VARCHAR(128)  NOT NULL,
	[password] VARCHAR(128)  NOT NULL,
	[created_at] TIMESTAMP,
	[last_login] TIMESTAMP,
	[is_active] INTEGER default 1 NOT NULL,
	[is_super_admin] INTEGER default 0 NOT NULL,
	UNIQUE ([username])
);

-----------------------------------------------------------------------------
-- sf_guard_user_permission
-----------------------------------------------------------------------------

DROP TABLE [sf_guard_user_permission];


CREATE TABLE [sf_guard_user_permission]
(
	[user_id] INTEGER  NOT NULL,
	[permission_id] INTEGER  NOT NULL
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([user_id]) REFERENCES sf_guard_user ([id])

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([permission_id]) REFERENCES sf_guard_permission ([id])

-----------------------------------------------------------------------------
-- sf_guard_user_group
-----------------------------------------------------------------------------

DROP TABLE [sf_guard_user_group];


CREATE TABLE [sf_guard_user_group]
(
	[user_id] INTEGER  NOT NULL,
	[group_id] INTEGER  NOT NULL
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([user_id]) REFERENCES sf_guard_user ([id])

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([group_id]) REFERENCES sf_guard_group ([id])

-----------------------------------------------------------------------------
-- sf_guard_remember_key
-----------------------------------------------------------------------------

DROP TABLE [sf_guard_remember_key];


CREATE TABLE [sf_guard_remember_key]
(
	[user_id] INTEGER  NOT NULL,
	[remember_key] VARCHAR(32),
	[ip_address] VARCHAR(50)  NOT NULL,
	[created_at] TIMESTAMP
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([user_id]) REFERENCES sf_guard_user ([id])

-----------------------------------------------------------------------------
-- sf_guard_user_profile
-----------------------------------------------------------------------------

DROP TABLE [sf_guard_user_profile];


CREATE TABLE [sf_guard_user_profile]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[user_id] INTEGER  NOT NULL,
	[first_name] VARCHAR(20),
	[last_name] VARCHAR(20),
	[email] VARCHAR(255)
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([user_id]) REFERENCES sf_guard_user ([id])
