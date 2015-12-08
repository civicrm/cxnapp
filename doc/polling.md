# cxnapp: Polling

A CiviConnect application provides its value by exchanging data with a CiviCRM site.
This could be a real-time pass-through, or it could be a periodic job. To facilitate
periodic work, you can use the `cxn:poll` command; this command will enumerate
all connected sites and run some logic for each of them.

The `cxn:poll` command is designed with some expectations:

 * There may be several thousand sites which need to be polled. Therefore, polling supports
   parallel execution.
 * Sites may exhibit a range of different uptimes/reliability and may cease operations
   gracelessly. Therefore, polling supports retrying with de-escalation (reducing the retry
   frequency if a site appears to have failed).

## 1. Identify the job

Each periodic job needs to be identified in two parts:

 * `appId` - The application which does the polling (e.g. `org.civicrm.profile`).
 * `name` - A name like `get_site_info` or `sync_activities`. (If omitted, defaults to `default`.)

## 2. Prepare cron configuration

You will need to define a cron job for the poll-runner. For example, you might
fire the poll-runner for a given app every 5 minutes:

```
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp
```

This example fires the `default` job. To fire a specific job, use `--name`, e.g.

```
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --name=get_site_info
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --name=sync_activities
```

If you have a large number of connected sites, then you could run parallel poll-runners:

```
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --batch=0/3
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --batch=1/3
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --batch=2/3
```

(Aside: For any given combination of job+batch, only one thread can run at a time.
The `cxn:poll` will decline to run if the batch is already being handled by
another process.)

Firing the *poll-runner* every five minutes does *not* mean that every site is
individually polled every 5 minutes. `cxn:poll` tracks the individual times at which it last
visited each site and applies its own scheduling rules. These rules take the form of a *retry*
expression, eg:

```
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --retry='20min (x6); 1hour (x24); 1day (x30)'
```

In this example, we begin by polling at 20 minute intervals. If we encounter persistent failures (6 times in a row),
then switch to polling at 1 hour intervals. If we still get persistent failures (24 times in a row), then switch to
polling at daily intervals. If we still get persistent failures (30 times in a row), then give up on the site.

All of the above options can be combined. For example, suppose:

 * We have one application, `org.civicrm.myapp`.
 * We have two jobs: `get_site_info` (which fetches general metadata about a site) and `sync_activities`
   (which synchronizes a list of activities).
 * In normal usage, `get_site_info` runs every day. We give up after ~30 days of failures.
 * In normal usage, `sync_activities` runs every two hours. We gradually give up after ~30 days of failures.
 * We expect to have a thousand sites, so we break down into 4 batches.

Then we might prepare a crontab with:

```
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --name=get_site_info --batch=0/4 --retry='1 day (x30)'
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --name=get_site_info --batch=1/4 --retry='1 day (x30)'
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --name=get_site_info --batch=2/4 --retry='1 day (x30)'
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --name=get_site_info --batch=3/4 --retry='1 day (x30)'
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --name=sync_activities --batch=0/4 --retry='2 hour (x12); 1 day (x30)'
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --name=sync_activities --batch=1/4 --retry='2 hour (x12); 1 day (x30)'
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --name=sync_activities --batch=2/4 --retry='2 hour (x12); 1 day (x30)'
*/5 * * * * /var/www/cxnapp/app/console cxn:poll org.civicrm.myapp --name=sync_activities --batch=3/4 --retry='2 hour (x12); 1 day (x30)'
```

## 3. Define the job's logic

When the `cxn:poll` command decides to visit a particular site, it fires an event
that allows your code to perform some work. The event name is based on the formula:

```
{appId}:job={jobName}:poll
```

For example, the event name might be:

```
app:org.civicrm.myapp:job=default:poll
```

To define some logic for this event, you might add class `Civi\Cxn\MyAppBundle\MyPoll` with function
`onPoll(PollEvent $e)` and update `Civi/Cxn/MyAppBundle/Resources/config/services.yml` with:

```yaml
services:
 civi_cxn_myapp.my_poll:
   class: Civi\Cxn\MyAppBundle\MyPoll
   tags:
      - { name: kernel.event_listener, event: 'app:org.civicrm.myapp:job=default:poll', method: onPoll }
```

The `onPoll` function will be called several times with instances of `PollEvent`. Each `PollEvent`
includes information about the application (`$e->getAppMeta()`) and the connection (`$e->getCxnEntity()`) as
well as a client for sending API calls to the remote site (`$e->getApiClient()`).

## Addendum: Batching

The `--batch=<batchId>/<batchCount>` option divides the list of sites into stable, random batches as follows:

 * Every `Cxn` record is assigned a `batchCode`, which is a random number (0-10,000).
 * Based on the `batchCount`, we make several batches of equal sizes. For example, let's use 4 batches.
 * Batch 0 is the range "batchCode BETWEEN 0 AND 2499"
 * Batch 1 is the range "batchCode BETWEEN 2500 AND 4999"
 * Batch 2 is the range "batchCode BETWEEN 5000 AND 7499"
 * Batch 3 is the range "batchCode BETWEEN 7500 AND 10000"

## Addendum: Retry: Escalation and De-escalation

The example retry policies demonstrated de-escalation (ie polling *less* frequently
in response to failures). If timeliness/QoS is important (and you don't care about the
extra overhead), then you might flip it around (ie polling *more* frequently
in response to failures). Or you might try both -- initially, escalate in hopes
of improving QoS, but ultimately de-escalate:

 * De-escalation example: `--retry='2 hour (x12); 1 day (x30)'`
 * Escalation example: `--retry='2 hour (x1); 30 min (x480)'`
 * Both: `--retry='2 hour (x1); 30 min (x24); 1 day (x30)'`

## Addendum: Debug Tips

 * Check logs in `app/logs/*.log`
 * Call `cxn:poll` manually with options `-v --retry='1 sec (x100)'`
 * Inspect the database (esp. tables `Cxn` and `PollStatus`)

## Addendum: Patch Welcome

The batching/threading model here is simple and seems plausible for our target scale, but
it's probably not optimal and hasn't actually been used much in production.