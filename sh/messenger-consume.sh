#!/bin/bash

start_date=$(date --date='-1 day' '+%Y/%m/%d %H'):00
end_date=$(date '+%Y/%m/%d %H'):59:59

cd /var/www/SF6/SMSBidalketa/
php bin/console messenger:stop-workers
php bin/console messenger:consume async -vv

