# cxnapp: Cron (org.civicrm.cron)

The cron service (aka `org.civicrm.cron` or `Civi/Cxn/CronBundle`) provides
a quick cron setup for new sites.

## Setup

Generate an identity for the app `org.civicrm.cron` using the `cxnapp:init` as discussed in [tutorial.md](../../../../doc/tutorial.md), e.g.

```
./app/console cxnapp:init org.civicrm.cron 'O=MyOrg'
```

Configure one or more cron jobs for polling sites as discussed in [polling.md](../../../../doc/polling.md), e.g.

```
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.cron --batch=0/4 --retry='1 day (x90)'
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.cron --batch=1/4 --retry='1 day (x90)'
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.cron --batch=2/4 --retry='1 day (x90)'
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.cron --batch=3/4 --retry='1 day (x90)'
```