<?php

use SCody\Femot;

require __DIR__ . '/SCFemot.php';
$femot = Femot::getInstance();

// Контроль отправки - проверяет дату отправки и в случае просрочки производит отправку сообщения
$femot->checkNeedToSend();
