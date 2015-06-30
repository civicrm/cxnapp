# CiviConnect Reference Application (cxnapp)

This is a reference-implementation of the CiviConnect backend. It includes:

 * **A skeletal CiviConnect app.** Sites may register with the demo app,
   and then the app administrator may issue adhoc commands via Civi's
   APIv3.
 * **Any CiviConnect services operated by civicrm.org.** This includes the
   cron app, the directory service, and the CRL service.

The app is built with Symfony 2 and the civicrm-cxn-rpc library. It may be
used as a base for developing more substantial applications.

## Documentation: General

 * [Getting Started](doc/tutorial.md)
 * [Code Structure](doc/structure.md)
 * [Administration Screens](doc/admin-screen.md)
 * [Tips](doc/tips.md)

## Documentation: civicrm.org services

 * [Certificate Revocation Lists](doc/crl.md)
