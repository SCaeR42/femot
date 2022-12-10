# Femot (Незабудка)

[![MIT License](https://img.shields.io/badge/License-MIT-yellow.svg)](https://choosealicense.com/licenses/mit/)[![version](https://img.shields.io/badge/version-1.0-blue)](https://img.shields.io/badge/version-1.0-blue)

Скрипт выполняет некоторые действия (отправка писем, выполнение команд на сервера, вызов URLs) если за определённый интервал не будет отменено выполнение.

*e.g.:
Если в течении 1 недели не будет обращения к скрипту то он отправит выбранное сообщение.*

## Documentation

`data/dataToSend` - директория с данными для отправки по email

`data/emailNotice` - директория с данными для отправки уведомления/напоминалки админу

`cron.php` - для вызова cron

`call.php` - для вызова отменяющего действия в активном интервале

`config.ini` - настройки

## How To Use

Установиь на любой web сервер.

Переименовать `config.ini.example` в `config.ini`

Установить  в `config.ini` требуеые параметры

Завать в cron вызов файл cron.php

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
