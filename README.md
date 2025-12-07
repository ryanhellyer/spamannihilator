# Blog post ... 

This was originally called the Spam Annihilator, but the name was too much of a mouthful and too hard to spell, so I went with Spam Destroyer to match my WordPress plugins name.

The old site ran on WordPress, with Facebook login, but it was always quite ugly, the UI wasn't very good and it had very few users. It also took too long to process the link checking; I was too aggressive with making the bots do work to get through and this was bothering users.

Eventually the Facebook login stopped working and I semi-retired the product, until someone recently complained that it wasn't live anymore and that they wanted to run more links through it. So I sat down and spent the day mapping out and implementing a plan to improve it. I stopped using Facebook logins, and just relied on users interacting with the app without logging in, and them needing to save a private admin URL if they ever needed to edit the URL (in most cases they won't need to). The new version is built in Laravel and uses around 10% of the system resources that the old one required on WordPress.

It rarely requires SQL DB requests, as the URL paths are stored in Redis. Only the admin pages regularly require SQL queries to be made.

The old WordPress based design was quite hideous. It was very poorly designed and wasn't ery interesting. I'm much happier with the newer fresher one, with some added spice from Bing AI image created images.

Google PageSpeed insights results for the previous site was appalling, only 93/100 for the performance on desktop. The new ersion gets 100/100 for performance, accessibliity, seo and best practices.

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
