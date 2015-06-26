# cxnapp: Tips

## Console tools and URLs

The cxnapp involves console tools which communicate over the network.
Several messages involved with "cxn" require passing an absolute URL.
[Console tools may have difficulty composing a correct
URL](http://symfony.com/doc/current/cookbook/console/sending_emails.html).
To address this, edit `app/config/parameters.yml`:

```
parameters:
    router.request_context.host: example.org
    router.request_context.scheme: https
    router.request_context.base_url: my/path
```

(At time of writing, this isn't issue, but it could become an issue as
the tool evolves.)


## From development to production

One may deploy instances of cxnapp to development, staging and production
using essentially the same procedure -- download the code, configure the web
server, and run "cxnapp init" to produce an appId and keypair.  However, as
you progress, the certification requirements become more stringent.

Here are a few deployment recipes:

 * Local development
   * Deploy your app on localhost (e.g. `http://127.0.0.1:8000`).
   * Don't bother with certificates.
   * In civicrm.settings.php, set `define('CIVICRM_CXN_CA', 'none');`
   * To connect, run `drush cvapi cxn.register app_meta_url=http://127.0.0.1:8000/app:org.example.myapp/cxn/metadata.json debug=1`
 * Staging or private beta, unsigned / self-managed / insecure
   * Deploy your app on a public web server (e.g. `http://app.example.net`).
   * In civicrm.settings.php, set `define('CIVICRM_CXN_CA', 'none');`
   * To connect, run `drush cvapi cxn.register app_meta_url=http://app.example.net/app:org.example.myapp/cxn/metadata.json debug=1`
 * Staging or private beta, signed by civicrm.org
   * Deploy your app on a public web server (e.g. `http://app.example.net`).
   * Send `app/cxn/org.example.myapp/app.req` and the URL for `metadata.json` to your point-of-contact at civicrm.org.
   * Receive an updated `app/cxn/org.example.myapp/app.crt` with a certificate signed by `CiviTestRootCA`.
   * Deploy the updated `app/cxn/org.example.myapp/app.crt`.
   * In `civicrm.settings.php`, set `define('CIVICRM_CXN_CA', 'CiviTestRootCA');`
   * To connect, run `drush cvapi cxn.register app_meta_url=http://app.example.net/app:org.example.myapp/cxn/metadata.json debug=1`
 * Production, signed by civicrm.org
   * Deploy your app on a public web server (e.g. `http://app.example.net`).
   * Send `app/cxn/org.example.myapp/app.req` and the URL for `metadata.json` to your point-of-contact at civicrm.org.
   * Receive an updated `app/cxn/org.example.myapp/app.crt` with a certificate signed by `CiviRootCA`.
   * Deploy the updated `app/cxn/org.example.myapp/app.crt`.
   * In civicrm.settings.php, let CIVICRM_CXN_CA use the default value (`CiviRootCA`).
   * To connect, use the UI.

(Aside: The processes for staging or private beta are a little more onerous
that I'd like.  It would take a day's work to improve this.)
