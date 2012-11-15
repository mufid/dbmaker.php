# dbmaker.php

A simple, ready-to-use, key-value store for Codeigniter. Not really complete library.

## Installation

1. Download the `dbmaker.php`
2. Copy it to `application/libraries/dbmaker.php`
3. Add `database` and `dbmaker` to your `config/autoload.php`. For example:

		$autoload['libraries'] = array("database", "dbmaker");
		// make sure database is loaded first

4. Copy those SQL, and run in your DBMS: (Note to check key configuration on your DBMS. Here i use MySQL for the database, but you may want use another DBMS).  
   Actually, i don't have any idea with the index.

		CREATE TABLE IF NOT EXISTS `things` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `kunci` varchar(45) NOT NULL,
		  `jenis` int(10) unsigned NOT NULL,
		  `isi` text,
		  `induk` int(10) unsigned DEFAULT NULL,
		  PRIMARY KEY (`id`,`kunci`,`jenis`),
		  KEY `index2` (`kunci`),
		  KEY `index3` (`jenis`),
		  KEY `index4` (`induk`),
		  KEY `index5` (`id`) USING BTREE
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=31880 ;

		CREATE TABLE IF NOT EXISTS `things_meta` (
		  `type` int(10) unsigned NOT NULL,
		  `parent` int(10) unsigned NOT NULL,
		  `valueint` int(11) DEFAULT NULL,
		  `value` text,
		  PRIMARY KEY (`type`,`parent`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

5. Configure your dbmaker configuration. Open dbmaker.php and edit those example lines:

		var $tables = array(
			// I don't have any idea, but please do not use the zero
			// Index of table
			"dummy" => "NOT AVAILABLE",
			// Fill the table name
			"user" =>
				// And the table fields
				array("username", "userpassword", "email", "description"),
			"posts" =>
				array("uid", "post-title", "post-")
		);

		var $things_table = "things";
		var $metas_table  = "things_meta";

## How to Use

**Setting a Value**: `this->dbmaker->set(/*table*/, /*key*/, /*metas*/);`

	$this->dbmaker->set("user", "mufid", array(
		"username" => "mufid",
		"userpassword" => md5("test"),
		"email" => "mufid@tes.local"
	));

**Getting an Object**: `this->dbmaker->get(/*table*/, /*key*/);`

	$this->dbmaker->get("user", "mufid");
	// stdObject (
	//   "id" => depends_on_db,
	//   "username" => "mufid",
	//   "userpassword" => ",
	//   "email" => "mufid@tes.local",
	//   "description" => ""
	// )

**Getting all rows in Table**: `this->dbmaker->gets(/*table*/);`

**Get rows with metas**: `this->dbmaker->get_where(/*table*/, /*metas*/);`

	$this->dbmaker->get_where("user", array("email" => "mufid@test.local"));

**Delete row with key**: `$this->dbmaker->del("user", "mufid");`

**Delete row key's meta**: `$this->dbmaker->del("user", "mufid", "email");`

**Delete all row in table**: `$this->dbmaker->delall("user");`