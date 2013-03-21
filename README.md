newsblur_autorefresh
====================

Autorefresh feeds on newsblur

Programmed this in less than an hour, sorry for dirty code.
Just enter username and password on top of the file, and run this is a cronjob on your server.

Please note:
Newsblur is currently (March 2013) having problem scaling their servers because of new Google Reader arrivals, do not abuse.

Line 32 of this code, it skips a feed if it was updated less than 30 minutes ago. You can reduce this if needed, but I would recommend to keep it this way.
