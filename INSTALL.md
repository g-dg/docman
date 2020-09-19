Garnet DeGelder's DocMan Installation Instructions
==================================================


0 - Requirements
----------------
- Requires all of CodeIgniter's requirements
- Requires PHP SQLite3 support for the database


1 - Setup database
------------------

Edit the `database` property of `$db['default']` in `/application/config/database.php` to match your configuration.
This database file must be manually created and must be readable & writable by the webserver.
Due to how SQLite3 works, the folder that the database is in must also be readable & writable by the webserver.


2 - Setup default username and password
---------------------------------------

Edit the default username and password in `/application/config/setup.php`
Ensure you remove these or change your password when you are done setting up


3 - Run setup
-------------

Visit `/index.php/setup` to run the setup.
