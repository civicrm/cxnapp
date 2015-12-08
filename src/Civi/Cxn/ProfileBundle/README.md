# cxnapp: Profiles (org.civicrm.profile)

The site profile service (aka `org.civicrm.profile` or
`Civi/Cxn/ProfileBundle`) tracks metadata about registered sites, such as
the list of active PHP extensions and the MySQL version.

## Setup

Generate an identity for the app `org.civicrm.profile` using the `cxnapp:init` as discussed in [tutorial.md](../../../../doc/tutorial.md), e.g.

```
./app/console cxnapp:init org.civicrm.profile 'O=MyOrg'
```

Configure one or more cron jobs for polling sites as discussed in [polling.md](../../../../doc/polling.md), e.g.

```
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.profile --batch=0/4 --retry='7 day (x1); 1 day (x7); 7 day (x50)'
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.profile --batch=1/4 --retry='7 day (x1); 1 day (x7); 7 day (x50)'
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.profile --batch=2/4 --retry='7 day (x1); 1 day (x7); 7 day (x50)'
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.profile --batch=3/4 --retry='7 day (x1); 1 day (x7); 7 day (x50)'
```