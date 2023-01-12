<?php

namespace SCody;

use Exception;

/**
 * Класс "Незабудка" (forget-me-not aka Femot) - обеспечивает отправку сообщений при просрочке контрольного события
 */
class Femot
{
	private static array $instances  = [];
	private array        $params     = [];
	private array        $paramsLock = [];
	private string       $cls        = '';

	protected function __construct()
	{
	}

	protected function __clone()
	{
	}

	/**
	 * @throws Exception
	 */
	public function __wakeup()
	{
		throw new Exception("Cannot serialize");
	}

	public static function getInstance($params = []):Femot
	{
		if (empty($params)) {
			$params = parse_ini_file(__DIR__ . '/config.ini');
		}

		$cls = 'Femot::' . md5(serialize($params));

		if (!isset(self::$instances[$cls])) {
			self::$instances[$cls]      = new static();
			self::$instances[$cls]->cls = $cls;
			self::$instances[$cls]->setParams($params);
			self::$instances[$cls]->checkControlFile();
		}

		return self::$instances[$cls];
	}

	private function setParams($params)
	{
		$paramsDef = [
			'codeName'           => 'FORGET ME NOT',
			'debug'              => 0,
			// вывод логов
			'debugPrint'         => false,
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
			'selfNoticePercents' => 90,
			// напоминание админу при достижении % интервала

			// повторение отправки в течении последующих дней по 1 разу в repeatRunsInterval дней
			'repeatRuns'         => 0,
			// интервал в днях через которое будут выполнены команды
			'repeatRunsInterval' => 1,
			// сколько раз будет отправлено уведомление админу
			'repeatSendNotice'   => 1,

			// директория логов
			'logDir'             => 'log',
			// директория данных для отправки в письме
			'dataDir'            => 'data',
			'controlFile'        => 'last-check.lock',

			'login'     => '',
			'pass'      => '',
			'emailFrom' => 'femot@femot.com',

			'emailRecipients'      => [],
			'selfNoticeRecipients' => [],
			'callUrls'             => [],
			'execCommands'         => [],
		];

		$this->params = array_merge($paramsDef, $params);

		if (substr($this->params['logDir'], 0, 1) != '/') {
			$this->params['logDir'] = __DIR__ . '/' . $this->params['logDir'];
		}

		if (substr($this->params['dataDir'], 0, 1) != '/') {
			$this->params['dataDir'] = __DIR__ . '/' . $this->params['dataDir'];
		}

		if (substr($this->params['controlFile'], 0, 1) != '/') {
			$this->params['controlFile'] = __DIR__ . '/' . $this->params['controlFile'];
		}

		$this->addLog($this->params, 3);
	}

	/**
	 * @return array
	 */
	public function getParams():array
	{
		return $this->params;
	}

	/**
	 * @return array
	 */
	public function getParamsLock():array
	{
		return $this->paramsLock;
	}

	/**
	 * @param array $paramsLock
	 */
	public function setParamsLock(array $paramsLock):void
	{
		$this->paramsLock = $paramsLock;
	}

	/**
	 * @param string $path
	 *
	 * @return void
	 *
	 * @created by: SCaeR
	 * @since   version 1.0.0
	 */
	private function CheckCreateDir(string $path):void
	{
		try {
			if (!is_dir($path)) {
				$ret = mkdir($path, 0777);
				if (!$ret) {
					throw new Exception('Error create dir');
				}
			}
		} catch (Exception $e) {
			echo "Exception: " . $e->getMessage();
			echo "<br>File: " . $e->getFile();
			echo "<br>Line: " . $e->getLine();

			return;
		}
	}

	/**
	 * @param string $path
	 *
	 * @return void
	 *
	 * @created by: SCaeR
	 * @since   version 1.0.0
	 */
	private function CheckCreateFile(string $path):void
	{
		try {
			if (empty($path)) {
				throw new Exception('Error create file');
			}

			if (!is_file($path)) {
				$ret = touch($path);

				if (!$ret) {
					throw new Exception('Error create file');
				}

				chmod($path, 0777);
			}
		} catch (Exception $e) {
			echo "Exception: " . $e->getMessage();
			echo "<br>File: " . $e->getFile();
			echo "<br>Line: " . $e->getLine();

			return;
		}
	}


	/**
	 * Логирование действий, для отладки и не только
	 *
	 * @param     $mess
	 * @param int $debugLevel
	 *
	 * @return void
	 *
	 * @created by: SCaeR
	 * @since   version 1.0.0
	 */
	private function addLog($mess, int $debugLevel = 0):void
	{
		$params = $this->getParams();

		if (!$params['debug'] || $params['debug'] < $debugLevel) {
			return;
		}

		if (empty($params['logDir'])) {
			return;
		}

		$this->CheckCreateDir($params['logDir']);

		$logFile = $params['logDir'] . '/femot_log_' . date("Y.m.d") . '.txt';

		if (is_array($mess)) {
			$mess = print_r($mess, true);
		}
		$mess = date("d.m.Y H:i:s") . " - " . $mess . "\r\n";

		if ($this->params['debugPrint']) {

			$this->printMess($mess);

			return;
		}

		file_put_contents($logFile, $mess, FILE_APPEND);
	}

	public function printMess($mess)
	{
		$sapi = php_sapi_name();
		if ($sapi == 'cli') {
			echo $mess . "\r\n";
		}
		else {
			print_r('<pre>');
			print_r($mess);
			print_r('</pre>');
		}
	}

	/**
	 * @param string $date
	 * @param bool   $retStrToTime
	 * @param string $format
	 *
	 * @return false|string
	 *
	 * @created by: SCaeR
	 * @since   version 1.0.0
	 */
	public function getDate(string $date = 'NOW', bool $retStrToTime = false, string $format = 'Y-m-d')
	{
		if (!$date) {
			return false;
		}

		$oDate = strtotime($date);
		if ($retStrToTime) {
			return $oDate;
		}

		return date($format, $oDate);
	}

	/**
	 * контроль изменения параметров в lock файле
	 *
	 * @return void
	 *
	 * @created by: SCaeR
	 * @since   version 1.0.0
	 */
	protected function checkControlFile()
	{
		$params = $this->getParams();
		// Проверка наличия файла с записью проверки, если нет то создать такой
		$this->CheckCreateFile($params['controlFile']);

		$paramsLockDef = [
			'cid'                => $this->cls,
			//			'dateLastCheck'     => self::getDate(),
			'dateTimeSendNotice' => '1971-01-01',
			'sendNoticeCount'    => 0,
			'dateTimeRunActions' => '1971-01-01',
			'runCount'           => 0,
			'dateStart'          => $this->getDate($params['dateStart']),
			'dateEnd'            => $this->getDate($params['dateEnd']),
			'intervalStart'      => $this->getDate($params['dateStart']),
			'intervalEnd'        => $this->getDate($params['dateStart'] . ' +' . $params['sendInterval'] . ' day'),
		];

		$paramsLock = parse_ini_file($params['controlFile']);

		// записать текущие параметры в файл контроля
		if (empty($paramsLock) || $paramsLock['cid'] != $this->cls) {
			$paramsLock = $paramsLockDef;
			$this->saveParamsLock($paramsLock);
		}

		$this->setParamsLock($paramsLock);
	}

	/**
	 * @param $paramsLock
	 *
	 * @return void
	 *
	 * @created by: SCaeR
	 * @since   version 1.0.0
	 */
	public function saveParamsLock($paramsLock):void
	{
		$params = $this->getParams();

		$iniContent = '';
		foreach ($paramsLock as $key => $value) {
			$iniContent .= "{$key}={$value}\n";
		}
		try {
			$ret = file_put_contents($params['controlFile'], $iniContent, LOCK_EX);
			if (!$ret) {
				throw new RuntimeException('Error to save controlFile');
			}
		} catch (RuntimeException $e) {
			echo "Exception: " . $e->getMessage();
			echo "<br>File: " . $e->getFile();
			echo "<br>Line: " . $e->getLine();

			return;
		}

	}

	//region Вспомогательные функции

	/**
	 * Отправка напоминалки админу о предстоящем выполнении действий при достижении просрочки интервала в selfNoticePercents, по дефолту 90%
	 *
	 * @return bool
	 *
	 * @created by: SCaeR
	 * @since   version 1.0.0
	 */
	private function sendNotice():bool
	{
		$params     = $this->getParams();
		$paramsLock = $this->getParamsLock();

		$selfNoticeRecipients = $params['selfNoticeRecipients'];
		if (empty($selfNoticeRecipients)) {
			return true;
		}

		$entries = scandir($params['dataDir'] . '/emailNotice');
		$files   = array_diff($entries, array(
			'.',
			'..'
		));
		sort($files);

		array_walk($files, function (&$f) use ($params) {
			$f = $params['dataDir'] . '/emailNotice/' . $f;
		});

		$mailParams = [
			'subject'    => $params['codeName'],
			'recipients' => implode(',', $selfNoticeRecipients),
			'from'       => $params['emailFrom'],
			'files'      => $files,
			'body'       => "Прошло " . $params["selfNoticePercents"] . "% от интервала подтверждения. \r\n Если не подтвердить до " . $paramsLock['intervalEnd'] . " будут запущены заданые транзакции.",
		];

		return $this->sendMail($mailParams);
	}

	private function runActions():bool
	{
		$this->addLog('Выполнение транзакций', 1);

		$this->sendData();

		//todo выволнение действий при просрочке интервала

		return true;
	}

	/**
	 * @return void
	 *
	 * @created by: SCaeR
	 * @since   version 1.0.0
	 */
	private function sendData():void
	{
		$params     = $this->getParams();
		$paramsLock = $this->getParamsLock();

		$emailRecipients = $params['emailRecipients'];
		if (empty($emailRecipients)) {
			return;
		}

		$entries = scandir($params['dataDir'] . '/dataToSend');
		$files   = array_diff($entries, array(
			'.',
			'..'
		));
		sort($files);

		array_walk($files, function (&$f) use ($params) {
			$f = $params['dataDir'] . '/dataToSend/' . $f;
		});

		$mailParams = [
			'subject'    => $params['codeName'] . ' - робот @НЕЗАБУДКА: вам просили отправить это...',
			'recipients' => implode(',', $emailRecipients),
			'from'       => $params['emailFrom'],
			'files'      => $files,
			'body'       => "В течении последних  " . $params["sendInterval"] . " дней не был получен запрос на продление отсрочки отправки данного письма. \r\n ",
		];

		$this->sendMail($mailParams);
	}

	//	public function testMail($params)
	//	{
	//		return $this->sendMail($params);
	//	}

	/**
	 * @param array $params
	 *
	 * @return bool
	 *
	 * @created by: SCaeR
	 * @since   version 1.0.0
	 */
	private function sendMail(array $mailParams):bool
	{
		$ret    = false;
		$params = $this->getParams();

		$from       = $mailParams['from'];
		$recipients = $mailParams['recipients'];
		$subject    = $mailParams['subject'];
		$body       = $mailParams['body'];

		$headers = "From: $from \r\n";
		$headers .= "Reply-To: $from\r\n";
		$headers .= "MIME-Version: 1.0\r\n";

		$files        = $mailParams['files'];
		$addFiles     = false;
		$boundary     = "--" . md5(uniqid(time())); // разделитель
		$multipart    = '';
		$message_part = '';
		if (!empty($files)) {

			$multipart .= "--$boundary\n";
			$multipart .= "Content-Type: text/html; charset=utf-8\n";
			$multipart .= "Content-Transfer-Encoding: Quot-Printed\n\n";
			$multipart .= "$body\n\n";

			foreach ($files as $filepath) {
				$filename = basename($filepath);
				$fp       = fopen($filepath, "r");
				if (!$fp) {
					echo("Не удается открыть файл");
					exit;
				}
				else {
					$file = fread($fp, filesize($filepath));
					fclose($fp);

					$message_part .= "--$boundary\n";
					$message_part .= "Content-Type: application/octet-stream\n";
					$message_part .= "Content-Transfer-Encoding: base64\n";
					$message_part .= "Content-Disposition: attachment; filename = \"" . $filename . "\"\n\n";
					$message_part .= chunk_split(base64_encode($file)) . "\n";

					$addFiles = true;
				}
			}
		}

		if (!$addFiles) {
			$headers   .= "Content-type: text/html; charset=UTF-8\r\n";
			$multipart = $body;
		}
		else {
			$headers   .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\n";
			$multipart .= $message_part . "--$boundary--\n";
		}

		if (!$params['debugPrint']) {
			$ret = mail($recipients, $subject, $multipart, $headers);
		}

		$this->addLog('Отправка письма [' . intval($ret) . ']: ' . $subject, 1);

		return $ret;
	}
	//endregion Вспомогательные функции


	//region Внешние функции вызова

	//todo Немедленная отправка - немедленно отправляет сообщение
	public static function sendAction()
	{

	}

	/**
	 * Откладывание отправки - откладывает отправку сообщения на заданный интервал
	 *
	 * @param array $param
	 *
	 * @return void
	 *
	 * @created by: SCaeR
	 * @since   version 1.0.0
	 */
	public function postponeSending(array $param = [])
	{
		$this->addLog('Попытка продления', 2);

		$params     = $this->getParams();
		$paramsLock = $this->getParamsLock();

		//переключить на следующий интервал выполнения, только если текущая дата находится в текущем интервале
		$dateTimeNow   = $this->getDate('NOW', true);
		$intervalStart = $this->getDate($paramsLock['intervalStart'], true);
		$intervalEnd   = $this->getDate($paramsLock['intervalEnd'], true);

		if ($dateTimeNow > $intervalStart && $dateTimeNow < $intervalEnd) {
			$paramsLock['intervalStart'] = $this->getDate($paramsLock['intervalEnd']);
			$paramsLock['intervalEnd']   = $this->getDate($paramsLock['intervalEnd'] . ' +' . $params['sendInterval'] . ' day');

			$this->saveParamsLock($paramsLock);

			$this->addLog('Транзакции отложены', 1);

		}
		/*	else {
				//bug: после продления интервала данный код выполняется всегда
				if ($paramsLock['sendNoticeCount'] < $params['repeatSendNotice']) {
					$sendNotice = self::sendNotice();
					if ($sendNotice) {
						self::addLog('Отправка уведомления админу при неудачной поппытке продления', 2);
					}
				}
			}*/

	}

	/**
	 * Контроль отправки - проверяет дату отправки и в случае просрочки производит отправку сообщения
	 *
	 * @return void
	 *
	 * @created by: SCaeR
	 * @since   version 1.0.0
	 */
	public function checkNeedToSend()
	{
		$this->addLog('Проверка продления: checkNeedToSend', 2);

		$params     = $this->getParams();
		$paramsLock = $this->getParamsLock();

		$dateTimeNow    = $this->getDate('NOW', true);
		$dateTimeNowStr = $this->getDate('NOW');
		$intervalStart  = $this->getDate($paramsLock['intervalStart'], true);
		$intervalEnd    = $this->getDate($paramsLock['intervalEnd'], true);

		// Сверка даты из файла проверки с текущей датой
		if ($dateTimeNow > $intervalEnd) {
			if ($paramsLock['runCount'] < $params['repeatRuns']) {

				//по 1 разу в день
				$dateRunActionsStr = $this->getDate($paramsLock['dateTimeRunActions'] . ' +' . ($params['repeatRunsInterval'] - 1) . ' day');

				if ($dateRunActionsStr < $dateTimeNowStr || $paramsLock['runCount'] < 1) {

					//если текущая дата больше: выполнить транзакции
					$runActions = $this->runActions();

					//					if ($runActions) {
					//добавление отметки, что транзакции запущены
					$paramsLock['dateTimeRunActions'] = $this->getDate();
					$paramsLock['runCount']           = $paramsLock['runCount'] + 1;
					$this->saveParamsLock($paramsLock);
					//					}
				}
			}
		}
		else {
			// если текущая дата меньше даты окончания текущего интервала: проверить время до конца текущего интервала, и если прошло больше чем задано в параметре selfNoticePercents, то отправить уведомления админу
			// Равномерная отправка уведомлений за оставшийся интервал

			$selfNoticePercentsOnce = ceil((100 - $params['selfNoticePercents']) / $params['repeatSendNotice']);
			$selfNoticePercents     = $params['selfNoticePercents'] + ($selfNoticePercentsOnce * $paramsLock['sendNoticeCount']);

			if ($paramsLock['sendNoticeCount'] < $params['repeatSendNotice']) {
				if (($dateTimeNow - $intervalStart) > (($intervalEnd - $intervalStart) * $selfNoticePercents / 100)) {

					self::addLog('Отправка уведомления админу', 1);

					// Отправка уведомления админу
					$sendNotice = self::sendNotice();

					//					if ($sendNotice) {
					//добавление отметки, что уведомление отправлено
					$paramsLock['dateTimeSendNotice'] = $this::getDate();
					$paramsLock['sendNoticeCount']    = $paramsLock['sendNoticeCount'] + 1;

					$this->saveParamsLock($paramsLock);
					//					}
				}
			}
		}
	}
	//endregion Внешние функции вызова

}
