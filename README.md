# CiviConnect Reference Application (cxnapp)

This is a reference-implementation of the CiviConnect backend. It includes:

 * **A skeletal CiviConnect app.** Sites may register with the demo app,
   and then the app administrator may issue adhoc commands via Civi's
   APIv3.
 * **Any CiviConnect services operated by civicrm.org.** This includes the
   cron app, the directory service, and the CRL service.

The app is built with [Symfony 2](http://symfony.com/) and the
[civicrm-cxn-rpc](https://github.com/civicrm/civicrm-cxn-rpc) library. It may
be used as a base for developing more substantial applications.

## Documentation: General

 * [Getting Started](doc/tutorial.md)
 * [Code Structure](doc/structure.md)
 * [Administration Screens](doc/admin-screen.md)
 * [Polling Connections](doc/polling.md)
 * [Tips](doc/tips.md)

## Documentation: civicrm.org services

 * [Certificate Revocation List (crl)](src/Civi/Cxn/CrlBundle/README.md)
 * [Site Profiles (org.civicrm.profile)](src/Civi/Cxn/ProfileBundle/README.md)
 * [Cron (org.civicrm.cron)](src/Civi/Cxn/CronBundle/README.md)
