<?php

include 'SCFemot.php';

use SCody\Femot;

class FemotTest extends Femot
{
	static string $dateTestNow = 'NOW';

	/**
	 * @param string $dateTestNow
	 */
	public function setDateTestNow(string $dateTestNow): void
	{
		self::$dateTestNow = $dateTestNow;
	}

	/**
	 * @return string
	 */
	public function getDateTestNow(): string
	{
		return self::$dateTestNow;
	}

	public function dateIterator()
	{
		$date = $this->getDateTestNow();
		$date .= ' +23 min';
		$oDate = strtotime($date);

		$dateIterator = date('Y-m-d H:i:s', $oDate);
		self::setDateTestNow($dateIterator);

		$this->checkControlFile();

		return $dateIterator;
	}

	public function getDate(string $date = 'NOW', bool $retStrToTime = false, string $format = 'Y-m-d')
	{

		if ($date == 'NOW') {
			$date = $this->getDateTestNow();
		}

		return parent::getDate($date, $retStrToTime, $format);
	}


}


/*
 * Сценарий тестирования
 *
 * Задаётся контрольный период: 7 дней
 * Прогоняются даты дял крон задачи от +1 день до начала и до + 2-а периода после окончания
 * Обращения имитируются по 2-а в каждый час 15/45 мин
 *
 * 1-й прогон без продления
 * 2-й прогон с продлением на 50% интервала и напоминалкой в 80 %
 * 3-й прогон с продлением на 90% интервала и напоминалкой в 80 %
 * 4-й прогон п. 1-3 с датой окончания
 *
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$params = [
	'codeName'           => 'FORGET ME NOT',
	'debug'              => 1,
	'debugPrint'         => true,
	// 1 - удалить директории со скриптом и всеми данными
	// 0 - дебаг отключен, 1 - только контроль вызовов, 2 - контроль вызовов и отправки, 3 - все данные
	'selfDestroy'        => 0,

	// дата начало работы скрипта
	'dateStart'          => 'now',
	// дата окончания работы скрипта
	'dateEnd'            => false,
	// 2022-12-01
	// интервал проведения проверки для отсрочки отправки дни
	'sendInterval'       => 7,
	// интервал, в днях, ожидания отсрочки отправки
	'selfNoticePercents' => 80,
	// напоминания админу при достижении % интервала

	// повторение отправки в течении последующих дней по 1 разу в день
	'repeatRuns'         => 4,
	'repeatSendNotice'   => 4,
	// повторение отправки напоминалки

	// директория логов
	'logDir'             => 'log',
	// директория данных для отправки в письме
	'dataDir'            => 'data',
	'controlFile'        => 'last-check-test.lock',

	'login'     => '',
	'pass'      => '',
	'emailFrom' => 'femot@femot.com',

	'emailRecipients'      => [],
	'selfNoticeRecipients' => [],
	'callUrls'             => [],
	'execCommands'         => [],
];

$femot = FemotTest::getInstance();

$oDateEnd = strtotime('NOW +' . (intval($params['sendInterval']) * 2 + 2) . ' day');
$dateEnd = date('Y-m-d', $oDateEnd);

$oDateSendPostPone = strtotime('NOW +' . ceil(intval($params['sendInterval']) * 0.55) . ' day');
$dateSendPostPone = date('Y-m-d H:i', $oDateSendPostPone);

$oDate = strtotime('NOW');
$dateIterator = date('Y-m-d', $oDate);
$femot->setDateTestNow($dateIterator . ' -1 day');
$testNow = $femot->getDateTestNow();

$femot->printMess('Интервал проверки [sendInterval]: ' . $params['sendInterval']);
$femot->printMess('Дата начала цикла проверки [$testNow]: ' . $femot->getDate($testNow));
$femot->printMess('Дата отправки продления [$oDateSendPostPone]: ' . $dateSendPostPone);
$femot->printMess('Дата окончания цикла проверки [$oDateEnd]: ' . $dateEnd);

$postponeSending = false;
$i = 0;
while ($i < 100000) {
	$i++;
	$testNowIterate = $femot->dateIterator();

	$femot->printMess('#' . $i . ' Дата проверки: ' . $testNowIterate);

	// Продлеение интервала
	$oDate = strtotime($testNowIterate);
	if ($oDateSendPostPone < $oDate && !$postponeSending) {
		$postponeSending = true;
		$femot->postponeSending();
	}

	if ($oDateEnd < $oDate) {
		$i = 100000000;
	}

	$femot->checkNeedToSend();
}




