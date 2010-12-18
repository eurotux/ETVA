
-----------------------------------------------------------------------------
-- agent
-----------------------------------------------------------------------------

DROP TABLE [agent];


CREATE TABLE [agent]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[server_id] INTEGER  NOT NULL,
	[name] VARCHAR(255)  NOT NULL,
	[description] MEDIUMTEXT,
	[uid] VARCHAR(255),
	[service] MEDIUMTEXT,
	[ip] VARCHAR(255),
	[state] INTEGER default 1 NOT NULL,
	[created_at] TIMESTAMP  NOT NULL,
	[updated_at] TIMESTAMP  NOT NULL
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([server_id]) REFERENCES server ([id])

-----------------------------------------------------------------------------
-- network
-----------------------------------------------------------------------------

DROP TABLE [network];


CREATE TABLE [network]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[server_id] INTEGER  NOT NULL,
	[port] VARCHAR(255),
	[ip] VARCHAR(255),
	[mask] VARCHAR(255),
	[mac] VARCHAR(255),
	[vlan] VARCHAR(255),
	[target] VARCHAR(255)
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([server_id]) REFERENCES server ([id])

-----------------------------------------------------------------------------
-- server
-----------------------------------------------------------------------------

DROP TABLE [server];


CREATE TABLE [server]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[logicalvolume_id] INTEGER  NOT NULL,
	[node_id] INTEGER  NOT NULL,
	[name] VARCHAR(255)  NOT NULL,
	[description] MEDIUMTEXT,
	[ip] VARCHAR(255)  NOT NULL,
	[vnc_port] INTEGER,
	[uid] VARCHAR(255),
	[mem] VARCHAR(255),
	[vcpu] INTEGER,
	[cpuset] VARCHAR(255),
	[location] VARCHAR(255),
	[network_cards] INTEGER,
	[state] VARCHAR(255)  NOT NULL,
	[mac_addresses] MEDIUMTEXT,
	[sf_guard_group_id] INTEGER  NOT NULL,
	[created_at] TIMESTAMP  NOT NULL,
	[updated_at] TIMESTAMP  NOT NULL
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([logicalvolume_id]) REFERENCES logicalvolume ([id])

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([node_id]) REFERENCES node ([id])

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([sf_guard_group_id]) REFERENCES sf_guard_group ([id])

-----------------------------------------------------------------------------
-- node
-----------------------------------------------------------------------------

DROP TABLE [node];


CREATE TABLE [node]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[name] VARCHAR(255)  NOT NULL,
	[memtotal] INTEGER,
	[memfree] INTEGER,
	[cputotal] INTEGER,
	[ip] VARCHAR(255),
	[port] INTEGER,
	[uid] VARCHAR(255),
	[network_cards] INTEGER,
	[state] INTEGER default 1 NOT NULL,
	[created_at] TIMESTAMP  NOT NULL,
	[updated_at] TIMESTAMP
);

-----------------------------------------------------------------------------
-- mac
-----------------------------------------------------------------------------

DROP TABLE [mac];


CREATE TABLE [mac]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[mac] VARCHAR(255),
	[in_use] INTEGER default 0
);

-----------------------------------------------------------------------------
-- vlan
-----------------------------------------------------------------------------

DROP TABLE [vlan];


CREATE TABLE [vlan]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[name] VARCHAR(255)
);

-----------------------------------------------------------------------------
-- physicalvolume
-----------------------------------------------------------------------------

DROP TABLE [physicalvolume];


CREATE TABLE [physicalvolume]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[node_id] INTEGER  NOT NULL,
	[name] VARCHAR(255),
	[device] VARCHAR(255),
	[devsize] BIGINT,
	[pv] VARCHAR(255),
	[pvsize] BIGINT,
	[pvfreesize] BIGINT,
	[pvinit] INTEGER default 0,
	[storage_type] VARCHAR(255),
	[allocatable] INTEGER
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([node_id]) REFERENCES node ([id])

-----------------------------------------------------------------------------
-- volumegroup
-----------------------------------------------------------------------------

DROP TABLE [volumegroup];


CREATE TABLE [volumegroup]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[node_id] INTEGER  NOT NULL,
	[vg] VARCHAR(255),
	[size] BIGINT,
	[freesize] BIGINT
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([node_id]) REFERENCES node ([id])

-----------------------------------------------------------------------------
-- logicalvolume
-----------------------------------------------------------------------------

DROP TABLE [logicalvolume];


CREATE TABLE [logicalvolume]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[volumegroup_id] INTEGER  NOT NULL,
	[node_id] INTEGER  NOT NULL,
	[lv] VARCHAR(255),
	[lvdevice] VARCHAR(255),
	[size] BIGINT,
	[freesize] BIGINT,
	[storage_type] VARCHAR(255),
	[writeable] INTEGER,
	[in_use] INTEGER default 0,
	[target] VARCHAR(255)
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([volumegroup_id]) REFERENCES volumegroup ([id])

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([node_id]) REFERENCES node ([id])

-----------------------------------------------------------------------------
-- volume_physical
-----------------------------------------------------------------------------

DROP TABLE [volume_physical];


CREATE TABLE [volume_physical]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[volumegroup_id] INTEGER  NOT NULL,
	[physicalvolume_id] INTEGER  NOT NULL
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([volumegroup_id]) REFERENCES volumegroup ([id])

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([physicalvolume_id]) REFERENCES physicalvolume ([id])

-----------------------------------------------------------------------------
-- vnc_token
-----------------------------------------------------------------------------

DROP TABLE [vnc_token];


CREATE TABLE [vnc_token]
(
	[user_id] INTEGER  NOT NULL,
	[username] VARCHAR(255)  NOT NULL,
	[token] VARCHAR(255)  NOT NULL,
	[enctoken] VARCHAR(255)  NOT NULL,
	[created_at] TIMESTAMP
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([user_id]) REFERENCES sf_guard_user ([id])

-----------------------------------------------------------------------------
-- service
-----------------------------------------------------------------------------

DROP TABLE [service];


CREATE TABLE [service]
(
	[id] INTEGER  NOT NULL PRIMARY KEY,
	[server_id] INTEGER  NOT NULL,
	[name] VARCHAR(255),
	[description] MEDIUMTEXT,
	[params] MEDIUMTEXT
);

-- SQLite does not support foreign keys; this is just for reference
-- FOREIGN KEY ([server_id]) REFERENCES server ([id])
