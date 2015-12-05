# cxnapp: Getting Started

### Requirements

 * php-cli 5.4+
 * composer
 * sass
   * (eg `sudo su -c "gem install sass"`)

### Get the code

```
git clone https://github.com/civicrm/cxnapp
cd cxnapp
composer install
php app/console assetic:dump
```

### Setup HTTPD, MySQL, Symfony

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

### Load database schema

```
$ ./app/console doctrine:schema:create
```

### Generate an identity for the application

```
$ ./app/console cxnapp:init org.example.myapp 'O=MyOrg'
Create key file (app/cxn/org.example.myapp/keys.json)
Create demo CA file (app/cxn/org.example.myapp/democa.crt)
Create certificate request (app/cxn/org.example.myapp/app.csr)
Create certificate self-signed (app/cxn/org.example.myapp/app.crt)
Create metadata file (app/cxn/org.example.myapp/metadata.json)

$ ./app/console dirsvc:init 'O=MyOrg'
Create key file (/Users/totten/src/cxnapp-symfony/app/dirsvc/keys.json)
Create certificate request (app/dirsvc/cxndir.csr)
Create certificate (app/dirsvc/cxndir.crt)
Create apps file (app/dirsvc/apps.json)
```

The arguments are:

 * `org.example.myapp` - The globally unique ID for the app.
 * `O=MyOrg` - The distinguished name ("DN", as in X.509 or LDAP) for your organization.

To ensure that the identities were generated, view the homepage:

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

(**Tip**: In a production environment, one only needs `cxnapp:init` -- not `dirsvc:init`.  However,
in a dev/test environment, you can produce a more realistic simulation by creating a local
directory service.)

### Connect a test instance of CiviCRM

In your local CiviCRM installation, edit `civicrm.settings.php`
and set:

```
define('CIVICRM_CXN_CA', 'none');
define('CIVICRM_CXN_APPS_URL', 'http://127.0.0.1:8000/app_dev.php/cxn/apps');
```

Note: The above configuration is suitable for local development but should
not be used for production sites. In particular:

  * Disabling the CA makes registrations vulnerable to man-in-the-middle attacks.
  * Including the `app_dev.php` option enables Symfony's debugger -- which rejects
    all remote requests. For quasi-remote setups (e.g. docker/vagrant),
    you may need to omit `app_dev.php`.

You can now connect using the CiviCRM UI (`/civicrm/a/#/cxn`). Alternatively,
you can register on the command-line:

```
## Register via URL
$ cd /var/www/example.org
$ drush cvapi cxn.register app_meta_url=http://127.0.0.1:8000/app:org.example.myapp/cxn/metadata.json debug=1

## Or register via app ID
$ cd /var/www/example.org
$ drush cvapi cxn.register app_guid=app:org.example.myapp debug=1
```

TIP: By default, CiviCRM caches data about available apps. If you enable CiviCRM debugging, the data will
always be fresh.

### Ping the test instance of CiviCRM

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
