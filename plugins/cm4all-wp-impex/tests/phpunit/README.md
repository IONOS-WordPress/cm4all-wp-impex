# caveats

https://wordpress.stackexchange.com/questions/220275/wordpress-unit-testing-cannot-create-tables

> an important feature of the core test suite: it forces any tables created during the test to be temporary tables.

> If you look in the WP_UnitTestCase::setUp() method you'll see that it calls a method called start_transaction(). That start_transaction() method starts a MySQL database transaction:

> It does this so that any changes that your test makes in the database can just be rolled back afterward in the tearDown() method. This means that each test starts with a clean WordPress database, untainted by the prior tests.
