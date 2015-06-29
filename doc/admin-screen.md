# cxnapp: Administration Screens

When a CiviCRM administrator reviews the list of available connections, each
connection may include a list of administrative links such as "Settings" or
"Support".

## Protocol

The `AppMeta` (`app/cxn/*/metadata.json`) for each application may include a stanza that enumerates
the administrative links, e.g.

```json
  "links": {
     "docs": false,
     "logs": false,
     "settings": true,
     "support": false
  }
```

Notice that the `metadata.json` only provides booleans to enable or disable links; there are no
significant options for each link.  Why not?

 * The labels and visual placement of links are standardized and localized; a `title`
   or `label` option would be more complex.
 * The links typically include authentication tokens so that an admin does not need
   to perform additional authentication. These tokens must be generated dynamically.
 * The URLs may change as the application evolves. If they URLs were included literally in
   `metadata.json`, any evolution would require publication and review/approval of a new
   `metadata.json`.

The CiviCRM administrative UI consumes these booleans and presents the admin with suitable
hyperlinks.  When the admin clicks on a link, CiviCRM sends a `RegistrationMessage` with a request
to execute `Cxn.getlink(page=settings)`.  The `Cxn.getlink` action returns the exact URL, e.g.

```json
{
  "url": "http:\/\/example.com\/settings?auth=abcd1234abcd1234",
  "mode": "iframe"
}
```

The `url` field is open-ended; any URL which points to a web page may be returned.

The `mode` field specifies how CiviCRM should display the link. Options are:

 * `iframe`: Display a Javascript dialog and load the link in an `<IFRAME>`.
 * `popup`: Display a new browser window (`window.open()`).
 * `redirect`: Redirect away from the CiviCRM installation and to the URL.

## Protocol: Recommendations

 * Use `mode=iframe`. This will blend in best with the CiviCRM user interface.
 * When using `mode=iframe`, the URL should point to a page with negligble decorations.
 * Include an authentication token in any URL which points to private information
   (such as `settings` or `logs`).

## Implementation

The `cxnapp` implements optional support for these links. At time of writing, it assumes
that all links will be handled internally by `cxnapp`. As a matter of convention, these
links follow a formula:

```
FORMULA: {baseUrl}/{appId}/{cxnId}/{pageName}?{cxnToken}
EXAMPLE: http://localhost:8000/app:org.civicrm.cron/cxn:abcd1234abcd1234/settings?cxnToken=efgh567
```

This formula is pretty dynamic, so let's break down the variables that influence it:

 * `{baseUrl}` (eg `http://localhost:8000`) is the domain or path where you deployed `cxnapp`.
 * `{appId}` (eg `app:org.civicrm.cron`) is the the GUID for the app.
   `cxnapp` can host multiple apps on one installation, and we include
   this to help keep our routes and code organized.
 * `{cxnId}` (eg `cxn:abcd1234abcd1234`) identifies the particular connection for which
   we want to manage settings.
 * `{pageName}` is the symbolic name of a page (eg `docs`, `logs`, `settings`, `support`).
 * `{cxnToken}` is a hashed authorization code granting access to the particular `cxnId`.

A few things to note:

 * If you haven't already, you should probably create a bundle that corresponds to the
   given CiviConnect app.
     * Example: `app:org.civicrm.cron` corresponds to `CiviCxnCronBundle`
 * The web page must be implemented using normal Symfony MVC. You may use code-generators
   like `app/console generate:controller` or `app/console generate:doctrine:form` to
   get started.
 * The name of the route (`src/**Bundle/Resources/config/routing.yml`) must be changed to match
   the `appId` and `page` name (with non-alphanumeric characters munged to "_").
     * Example: For `app:org.civicrm.cron`, the `settings` page is defined by route
       `org_civicrm_cron_settings`. (See `src/Civi/Cxn/CronBundle/Resources/config/routing.yml`)
 * The page should expect parameters `cxnId` and `cxnToken`. These parameters are automatically
   parsed and authenticated. Once authenticated, the details of the `cxn` are available in
   `$request->attributes->get('cxn')`.
 * Authentication tokens are interchangeable for all pages (`settings`, `docs`, etc). However,
   they do expire after a couple hours.

## Implementation: Example

`cxnapp` includes an implementation of `app:org.civicrm.cron` in the `CiviCxnCronBundle`. This
application has a `settings` page To trace through the example, look at these files:

 * [app/config/routing.yml](../app/config/routing.yml)
   * Note how it imports CiviCxnCronBundle
 * [src/Civi/Cxn/CronBundle/Resources/config/routing.yml](../src/Civi/Cxn/CronBundle/Resources/config/routing.yml)
   * Note how it declares the route `org_civicrm_cron_settings`. This maps to a particular service and function.
 * [src/Civi/Cxn/CronBundle/Resources/config/services.yml](../src/Civi/Cxn/CronBundle/Resources/config/services.yml)
   * Defines the service.
 * [src/Civi/Cxn/CronBundle/Controller/DefaultController.php](../src/Civi/Cxn/CronBundle/Controller/DefaultController.php)
   * Defines the `settingsAction()`. Note how it consumes `$request->attributes->get('cxn')` and ultimately saves `CronSettings`.
 * [src/Civi/Cxn/CronBundle/Entity/CronSettings.php](../src/Civi/Cxn/CronBundle/Entity/CronSettings.php)
   * Defines the data model. Any per-site settings are stored here.
   * Note that the the `cxnId` is flagged as the primary key (`@ORM\Id`). This is because there can be
     at most one `CronSettings` for each connection.

## Usage: CLI

During development or debugging, you may want to access a link directly (without going through the
CiviCRM GUI). You can obtain a link via CLI using either Civi API (on the CiviCRM installation) or
using `app/console` (on the `cxnapp` installation)

To use `cxnapp` and `app/console`:

```
$ cd /var/www/cxnapp

$ ./app/console cxn:get
+----------------------+----------------------+-------------------------------------------------------+
| App ID               | Cxn ID               | Site URL                                              |
+----------------------+----------------------+-------------------------------------------------------+
| app:org.civicrm.cron | cxn:abcd1234abcd1234 | http://d46.l/sites/all/modules/civicrm/extern/cxn.php |
+----------------------+----------------------+-------------------------------------------------------+

$ ./app/console cxn:link cxn:abcd1234abcd1234 settings
Array
(
    [cxn_id] => cxn:abcd1234abcd1234
    [url] => http://localhost:8000/app:org.civicrm.cron/cxn:abcd1234abcd1234/settings?cxnToken=asdf4321asdf4321
    [mode] => iframe
)
```

To use CiviCRM and its APIv3:

```
$ cd /var/www/drupal

$ drush cvapi Cxn.getlink app_guid=app:org.civicrm.cron page=settings
Array
(
    [cxn_id] => cxn:abcd1234abcd1234
    [url] => http://localhost:8000/app:org.civicrm.cron/cxn:abcd1234abcd1234/settings?cxnToken=asdf4321asdf4321
    [mode] => iframe
)
```
