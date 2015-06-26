# CiviConnect Demo (cxnapp): Code structure

The source generally follows Symfony conventions (with `app/`, `src/`, `web/`, `vendor/`).

Symfony projects organize their code into *bundles*. For `cxnapp`, these bundles are placed under
the `src` tree:

 * `Civi\Cxn\AppBundle` - General framework for registering and unregistering with CiviConnect
    applications.  It tracks application IDs and connection IDs, provides some CLI utilities for
    that data, and provides a web endpoints to receive registrations.
 * `Civi\Cxn\DirBundle` - A directory service which publishes a list of available applications.

To implement a new CiviConnect application (such as an "address cleanup" service), one should
generate a new bundle (eg `./app/console generate:bundle`) and add any required functionality to
that bundle (eg new database tables, new console commands, new settings pages, new web services).
