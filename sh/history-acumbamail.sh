#!/bin/bash

start_date=$(date --date='-1 hour' '+%Y/%m/%d %H'):00
end_date=$(date '+%Y/%m/%d %H'):59:59

cd ..
php bin/console app:sms-history-acumbamail "$start_date" "$end_date"
cd sh
