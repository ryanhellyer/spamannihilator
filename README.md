# Blog post ... 

Mention that queue was considered for storing increments in DB, but chose to simplify.
Mention that considered separate table to store daily stats, but chose to simplify.
Mention why Redis is used, then they're stashed in SQL after.
Mention why SQLite was not used - drives me nuts, lack of experience
Mention Laravel task secheduler and needing Cron job every minute, then Laravel handles whe to run it

## Redis vs cache layer
Uses Laravel cache layer for caching link redirects - fast lookup
Uses Redis directly for analytics, because it's not a cache and because Redis is required to use the increment() method (also works with caching layer, but makes this more explicit that the code needs Redis this way and can't be swapped out for another system).
Redis is not persistent to disk - am happy with losing data if Redis goes down, it's not important data anyway
Uses Redis::keys() instead of scan() because scan is more complex to implement and keys() should be fine since there won't be many keys since it gets updated every 15 mins anyway.

## Sync command
SyncAnalyticsCommand() - mention that there's an artisan command here for syncing it manually
AI kept suggesting all sorts of options, but I kept it simple.

## WordPress
Moved away, coz ... 
* Performance
* Overkill
* Messy
* Political reasons

## Login system
* Used FB, but tired of update
* Not necessary anyway
* New system much cleaner and simpler

## Google Pagespeed Insights results
100/100/100/100 * 2

## Images
From Bing Image Creator
Wanted friendly look instead of the weird space like previous version.
