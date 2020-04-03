# magento2-RunCronJob
Small CLI addition to allow running of cronjobs by job_code. This could be for debuging purposes or to add to your crontab.


## Example
To test generation of sitemaps
`php bin/magento cron:run-job --job-code=sitemap_generate`

The `job-code` is that same as would be found in the `cron_schedule` table
Or in a modules `crontab.xml` for example:
https://github.com/magento/magento2/blob/2.4-develop/app/code/Magento/Sitemap/etc/crontab.xml#L10
