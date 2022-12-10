# Femot (Forget-Me-Not)

[![MIT License](https://img.shields.io/badge/License-MIT-yellow.svg)](https://choosealicense.com/licenses/mit/) [![version](https://img.shields.io/badge/version-1.0-blue)](https://img.shields.io/badge/version-1.0-blue)

The script performs some actions (sending emails, executing commands to servers, calling URLs) if execution is not canceled within a certain interval.

*e.g.:
If within 1 week there is no call to the script, it will send the selected message.*

## Documentation

`data/dataToSend` - directory with data to send by email

`data/emailNotice` - directory with data to send notification/reminder to admin

`cron.php` - to call cron

`call.php` - to call the cancel action in the active interval

`config.ini` - settings

## How To Use

Install on any web server.

Rename `config.ini.example` to `config.ini`

Set required parameters in `config.ini`

Make a cron call file cron.php

Enjoy!

## Author

👤 **SCaeR42@SpaceCoding**

* Website: [spacecoding.net](https://spacecoding.net/)
* Github: [@SCaeR42](https://github.com/SCaeR42)

## Show your support

Give a ⭐️ if this project helped you!

## License

Copyright (C) 2013 - 2022 [spacecoding.net](https://spacecoding.net/)

Licensed under the [MIT](https://choosealicense.com/licenses/mit/) License.
