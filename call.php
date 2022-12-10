<?php

use SCody\Femot;

require __DIR__ . '/SCFemot.php';
$femot = Femot::getInstance();

// Откладывание отправки
$femot->postponeSending();

