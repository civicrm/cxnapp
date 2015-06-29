# cxnapp: Code structure

The source generally follows Symfony conventions (with `app/`, `src/`, `web/`, `vendor/`).

Symfony projects organize their code into *bundles*. For `cxnapp`, these bundles are placed under
the `src` tree:

 * `Civi\Cxn\AppBundle` - General framework for registering and unregistering with CiviConnect
    applications.  It tracks application IDs and connection IDs, provides some CLI utilities for
    that data, and provides a web endpoints to receive registrations.
 * `Civi\Cxn\CronBundle` - A service (`app:org.civicrm.cron`) which periodically executes cron jobs
   on subscribed sites.  This requires a few functions beyond `AppBundle`, such as a CLI
   command for running all crons (`./app/console cron:run`), a settings table (`CronSettings`),
   and a settings UI (`/app:org.civicrm.cron/{cxnToken}/settings`).
 * `Civi\Cxn\DirBundle` - A directory service which publishes a list of available applications.

To implement a new CiviConnect application (such as an "address cleanup" service), one should
generate a new bundle (eg `./app/console generate:bundle`) and add any required functionality to
that bundle (eg new database tables, new console commands, new settings pages, new web services).

# Assets: CSS, Javascript

These files are generally managed using Symfony's asset functionality. More specifically,
in absence of some other consideration:

 * Common CSS rules shared across all bundles belong in `web/css` (esp. `web/css/main.css`).
   This file is autoloaded by way of `app/Resources/views/base.html.twig`).
 * CSS rules which are particular to an individual bundle should go in that bundle's
   `Resources` folder (e.g. `src/Civi/Cxn/CronBundle/Resources/public/cron.css`).
   They may be loaded in Twig, eg in `src/Civi/Cxn/CronBundle/Resources/views/Default/settings.html.twig`:

```
{% block stylesheets %}
    <link href="{{ asset('bundles/civicxncron/cron.css') }}" type="text/css" rel="stylesheet" />
{% endblock %}
```

(Note: After adding your first CSS/JS file in a new bundle, you may need to register the bundle by
editing `app/config/config.yml` => `assetic: bundles` and then running `composer install`.)
