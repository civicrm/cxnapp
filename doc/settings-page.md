# cxnapp: Add a Settings Page

If you haven't already, generate a bundle for the app (e.g. `./app/console generate:bundle`).  For
this example, we'll assume you used the `yml` format.

When the CiviCRM admin views the list of available connections, he sees a link called "Settings"
and clicks.  With `cxnapp`, this ultimately opens a a URL like:

```
FORMULA: {baseUrl}/{appId}/{cxnToken}/{pageName}
EXAMPLE: http://localhost:8000/app:org.civicrm.cron/abcd1234abcd1234/settings
```

This URL is a pretty dynamic, so let's break down the variables that influence it:

 * `{baseUrl}` (eg `http://localhost:8000`) is the domain or path where you deployed cxnapp.
 * `{appId}` (eg `app:org.civicrm.cron`) is the the GUID for the app.
   `cxnapp` can host multiple apps on one installation, and we include
   this to help keep our routes and code organized.
 * `{cxnToken}` (eg `abcd1234abcd1234`) is a unique session ID. Any person who knows the `cxnToken`
   can manage settings on behalf of a particular `cxnId`.  In a less secure system, `cxnToken`
   might just be the `cxnId`, but that would be vulnerable to hijacking.  The `cxnToken` is a
   random and expires after a couple hours.
 * `{pageName}` (eg `settings`, `issues`, `docs`) is the symbolic name of a page.

To setup this route, we need to do a few things.

In `app/cxn/*/metadata.json`, enable the settings link for the app:

```json
  "links": {
    "settings": true
  }
```

In `app/config/routing.yml`, set the URL prefix which will be shared by any admin screens.  For
example, in `app:org.civicrm.cron`, we set the prefix:

```yaml
civi_cxn_cron:
    resource: "@CiviCxnCronBundle/Resources/config/routing.yml"
    prefix:   /app:org.civicrm.cron
    defaults:
      appId:  app:org.civicrm.cron
```

In the bundle's `routing.yml` (e.g.  `src/Civi/Cxn/CronBundle/Resources/config/routing.yml`),
define a route for the `settings` page. For example:

```yaml
org_civicrm_cron_settings:
    path:     /{cxnToken}/settings
    defaults: { _controller: CiviCxnCronBundle:Default:settings }
```

(*Note*: The route name (`org_civicrm_cron_settings`) is particularly important. The route-name
*must* be based on the appId and the page name -- with non-alphanumerics munged to "_".)

In `DefaultController.php` (e.g. `src/Civi/Cxn/CronBundle/Controller/DefaultController.php`),
define `function settingsAction()`, eg:

```php
class DefaultController extends Controller {
  public function settingsAction(Request $request) {
    $cxn = $request->attributes->get('cxn');
    return $this->render('CiviCxnCronBundle:Default:index.html.twig', array('name' => $cxn['cxnId']));
  }
}
```

(*Note*: The request attribute `cxn` is automatically populated based on `cxnToken`. If the `cxnToken`
is invalid or expired, then the page will return HTTP 403.)

Finally, to view this page, you'll need an active connection. You can either use the
CiviCRM administration GUI to open a link -- or generate a link via CLI:

```
$ ./app/console cxn:get
+----------------------+----------------------+-------------------------------------------------------+
| App ID               | Cxn ID               | Site URL                                              |
+----------------------+----------------------+-------------------------------------------------------+
| app:org.civicrm.cron | cxn:asdf4321asdf4321 | http://d46.l/sites/all/modules/civicrm/extern/cxn.php |
+----------------------+----------------------+-------------------------------------------------------+

$ ./app/console cxn:link cxn:asdf4321asdf4321 settings
http://localhost/app:org.civicrm.cron/abcd1234abcd1234/settings
```
