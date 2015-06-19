# CiviConnect Demo (cxnapp)

This is a demonstration app that accepts registrations using the CiviConnect
protocol.  Sites may register with the demo app, and then the app
administrator may issue adhoc commands via Civi's APIv3.

The app is built with Symfony 2 and the civicrm-cxn-rpc library. It may be
used as a base for developing more substantial applications.

## Tutorial

#### Get the code

```
git clone https://github.com/civicrm/cxnapp
cd cxnapp
composer install
```

#### Setup HTTPD, MySQL, Symfony

The quickest way to get started is to launch PHP's built-in
web server and run Symfony's configuration GUI.

To launch the webserver:

```
$ ./app/console server:run
Server running on http://127.0.0.1:8000

Quit the server with CONTROL-C.
```

Then, in a web browser, navigate to ```http://127.0.0.1:8000/config.php```.
The screens will prompt you to enter credentials for managing a MySQL
database.

#### Load database schema

```
$ ./app/console doctrine:schema:create
```

#### Generate an identity for the application

```
$ ./app/console cxnapp:init org.example.myapp 'O=MyOrg'
Create key file (app/cxn/org.example.myapp/keys.json)
Create demo CA file (app/cxn/org.example.myapp/democa.crt)
Create certificate request (app/cxn/org.example.myapp/app.req)
Create certificate self-signed (app/cxn/org.example.myapp/app.crt)
Create metadata file (app/cxn/org.example.myapp/metadata.json)
```

The arguments are:

 * The globally unique ID for the app (`org.example.myapp`)
 * The distinguished name ("DN", as in X.509 or LDAP) for your organization.

To ensure that the identity was generated, view the homepage:

```
$ curl http://127.0.0.1:8000
== Example App (app:org.example.myapp) ==

This is the adhoc connection app. Once connected, the app-provider can make API calls to your site.
```

The title, description, and other details come from `metadata.json`. You may customize the file
as desired.

(**Tip**:  If you need to setup a second server for testing/staging/production, then copy the file
`metadata.json` and run `cxnapp:init` on the new server.  The command will preserve `metadata.json`
and create the other files as needed.)

#### Connect a test instance of CiviCRM

In your local CiviCRM installation, edit civicrm.settings.php
and set:

```
define('CIVICRM_CXN_CA', 'none');
define('CIVICRM_CXN_APPS_URL', 'http://127.0.0.1:8000/cxn/apps');
```

(Note: The above configuration is vulnerable to man-in-the-middle attacks.
It's acceptable for local development but should not be used in production
sites.  Consequently, there is no API for reading or writing these
settings.)

You can now connect using the CiviCRM UI (`/civicrm/a/#/cxn`). Alternatively,
you can register on the command-line:

```
## Register via URL
$ cd /var/www/example.org
$ drush cvapi cxn.register app_meta_url=http://127.0.0.1:8000/app:org.example.myapp/cxn/metadata.json debug=1

## Register via app ID
$ cd /var/www/example.org
$ drush cvapi cxn.register app_guid=app:abcd1234abcd1234 debug=1
```

#### Ping the test instance of CiviCRM

The cxnapp will now be able to send requests to the registered instance of Civi. For example,
we can use the System.get API to determine the active version of Civi:

```
$ ./app/console cxn:get
+-----------------------+--------------------------------------+-------------------------------------------------------+
| App ID                | Cxn ID                               | Site URL                                              |
+-----------------------+--------------------------------------+-------------------------------------------------------+
| app:org.example.myapp | cxn:6bf52a5773fc8bbba8cc5befc85b7589 | http://d46.l/sites/all/modules/civicrm/extern/cxn.php |
+-----------------------+--------------------------------------+-------------------------------------------------------+

$ ./app/console cxn:call cxn:6bf52a5773fc8bbba8cc5befc85b7589 system.get
CxnID: cxn:6bf52a5773fc8bbba8cc5befc85b7589
Site URL: http://d46.l/sites/all/modules/civicrm/extern/cxn.php
Entity: system
Action: get
Params: Array
(
    [version] => 3
)

Result: Array
(
    [is_error] => 0
    [version] => 3
    [count] => 1
    [id] => 0
    [values] => Array
        (
            [0] => Array
                (
                    [version] => 4.6.0
                    [uf] => Drupal
                )

        )

)
```

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
   * Deploy the updated `app/cxn/org.example.myapp/app.crt`. (This is not strictly necessary but is good for consistency.)
   * In `civicrm.settings.php`, set `define('CIVICRM_CXN_CA', 'CiviTestRootCA');`
   * To connect, run `drush cvapi cxn.register app_meta_url=http://app.example.net/app:org.example.myapp/cxn/metadata.json debug=1`
 * Production, signed by civicrm.org
   * Deploy your app on a public web server (e.g. `http://app.example.net`).
   * Send `app/cxn/org.example.myapp/app.req` and the URL for `metadata.json` to your point-of-contact at civicrm.org.
   * Receive an updated `app/cxn/org.example.myapp/app.crt` with a certificate signed by `CiviRootCA`.
   * Deploy the updated `app/cxn/org.example.myapp/app.crt`. (This is not strictly necessary but is good for consistency.)
   * In civicrm.settings.php, let CIVICRM_CXN_CA use the default value (`CiviRootCA`).
   * To connect, use the UI.

(Aside: The processes for staging or private beta are a little more onerous
that I'd like.  It would take a day's work to improve this.)

## Tip: Console tools and URLs

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
