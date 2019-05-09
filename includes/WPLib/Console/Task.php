<?php
/**
 * WPLib\Console\Task
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Console;

class Task extends \Phalcon\CLI\Task
{
	/**
	 * 初始化
	 */
	public function initialize()
	{

	}

	/**
	 * @param object $var
	 * @return mixed
	 */
	public function object($var)
	{
	    return json_decode(json_encode($var));
	}

	/**
	 * 为空返回错误信息
	 *
	 * @param $var
	 * @param $info
	 * @param $status
	 */
	public function emptyReturn($var, $info, $status)
	{
		if (is_string($var)) {
			$var = trim($var);
		}

		if (empty($var)) {
			$this->jsonReturn([], $info, $status);
		}
	}

	public function errorReturn($var, $info, $status, $func = null)
	{
		if (is_callable($func)) {
			if (!$func($var)) {
				$this->jsonReturn([], $info, $status);
			}
		}

		return true;
	}

	/**
	 * 输出JSON结果
	 *
	 * @param $data
	 * @param string $info
	 * @param int $status
	 */
	public function jsonReturn($data = [], $info = '', $status = 200)
	{
		if (count($data) === 0) {
			$data = (object)$data;
		}

		header('Content-Type:application/json; charset=utf-8');
		echo json_encode([
			'status'  => $status,
			'info'    => $info,
			'data'    => $data,
		]);
		if ($eventsManager = $this->application->getEventsManager()) {
			$eventsManager->fire("application:afterHandleRequest", $this->application, $this);
			$eventsManager->fire("application:beforeSendResponse", $this->application, $this);
		}
		exit;
	}

	/**
	 * 返回对应错误信息
	 *
	 * @param $code 错误码
	 * @return array
	 */
	static public function getErrorInfo($code)
	{
		return [
			'code'    => $code,
			'message' => self::$errors[$code],
		];
	}

	public function defaultReturn($data = [], $info = "success", $status = 200)
	{
		if (count($data) === 0) {
			$data = (object)$data;
		}

		header('Content-Type:application/json; charset=utf-8');
		echo json_encode([
			'status'  => $status,
			'info'    => $info,
			'data'    => $data,
		]);
		if ($eventsManager = $this->application->getEventsManager()) {
			$eventsManager->fire("application:afterHandleRequest", $this->application, $this);
			$eventsManager->fire("application:beforeSendResponse", $this->application, $this);
		}
		exit;
	}
}
