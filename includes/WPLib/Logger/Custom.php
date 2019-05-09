<?php
/**
 * 自定义日志
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Logger;

use Phalcon\Di,
	Phalcon\Logger;
use Phalcon\Logger\Adapter\File as FileLogger,
	WPLib\Logger\Formatter\ELK as ELKFormatter,
	Phalcon\Logger\Exception;


class Custom extends FileLogger
{
	private $di = null;

	private static $_pid = null;

	private $user_id = 0;

	private $session_id = '';

	public function __construct($name, $options = null)
	{
		$this->di = Di::getDefault();
		self::$_pid = posix_getpid();

		if (!IN_CLI && $this->di->has('session')) {
			$this->user_id = (int)$this->di->get('session')->get('user_id');
			$this->session_id = (string)$this->di->get('session')->getId();
		}

		$log_dir = dirname($name);
		if (!file_exists($log_dir)) {
			@mkdir($log_dir, 0755, true);
		}

		$options['maxFilePersistNum'] = $options['maxFilePersistNum'] ?: 100;
		$options['rotateByTruncate']  = $options['rotateByTruncate'] ?: 0;

		parent::__construct($name, $options);
	}

	/**
	 * Commits the internal transaction
	 */
	public function commit()
	{
		if (!$this->_transaction) {
			throw new Exception("There is no active transaction");
		}

		$this->_transaction = false;

		/**
		 * Check if the queue has something to log
		 */
		$queue = $this->_queue;
		if (is_array($queue)) {
			foreach ($queue as $message) {
				$this->logInternal(
					$message->getMessage(),
					$message->getType(),
					$message->getTime(),
					$message->getContext()
				);
			}
			$this->_queue = [];
		}

		return $this;
	}

	public function logInternal($message, $type, $time, array $context)
	{
		if (self::$_pid != posix_getpid()) {
			$this->reopen();
			self::$_pid = posix_getpid();
		}

		$handler = $this->_fileHandler;
		if (!is_resource($handler)) {
			throw new Exception("Cannot send message to the log because it is invalid");
		}
		$max_file_size = isset($this->_options['maxFileSize']) ? $this->_options['maxFileSize'] : 1024 * 128;
		@flock($handler, LOCK_EX);
		$stat_info = fstat($handler);
		if ($stat_info['size'] > $max_file_size * 1024) {
			if (@filesize($this->_path) > $max_file_size * 1024) {
				$this->rotate();
				@flock($handler, LOCK_UN);
				$handler = $this->reopen();
			} else {
				@flock($handler, LOCK_UN);
				$handler = $this->reopen();
			}
		} else {
			@flock($handler, LOCK_UN);
		}

		try {
			$server_name = '-';
			$server_addr = '-';
			$url         = '-';
			$argv        = '-';
			if ($this->di->has('request')) {
				$req = $this->di->getShared('request');
				$server_name = $req->getServerName();
				$server_addr = $req->getServerAddress();
				$url = $req->getURI();
			} elseif (IN_CLI) {
				$server_name = $_SERVER['HOSTNAME'];
				$argv        = json_encode($_SERVER['argv']);
			}
			$params = [
				'app_id'           => (int)\WPLib\WPApi::getAppId(),
				'app_name'         => (string)\WPLib\WPApi::getAppName(),
				'client_ip'        => (string)\Helper::getClientIp(),
				'user_id'          => (int)$this->user_id,
				'session_id'       => (string)$this->session_id,
				'logger_type'      => 'backend',
				'time'             => (int)($time * 1000),
			];

			$params += $this->getFormatter()->format($message, $type, $time, $context);
			fwrite($handler, json_encode($params, JSON_UNESCAPED_UNICODE) . "\n");

		} catch (Exception $e) {
			//
		}
	}

	protected function rotate()
	{
		$file     = $this->_path;
		$handler = $this->_fileHandler;
		if (is_file($file)) {
			$rotate_file = $file . '-' . date('YmdHis');
			if ($this->_options['rotateByTruncate'] == 1) {
				@copy($file, $rotate_file);
				@ftruncate($handler, 0);
			}
			else {
				@rename($file, $rotate_file);
			}
		}
	}

	protected function reopen()
	{
		$name = $this->_path;
		$mode = $this->_options['mode'] ?: 'ab';

		$handler = @fopen($name, $mode);
		if (!is_resource($handler)) {
			throw new Exception("Can't open log file at '" . $name . "'");
		}

		$this->_fileHandler = $handler;

		return $handler;
	}

	/**
	 * Returns the internal formatter
	 */
	public function getFormatter()
	{
		$this->_formatter = new ELKFormatter();

		return $this->_formatter;
	}

	/**
	 * Logs messages to the internal logger. Appends logs to the logger
	 */
	public function log($type, $message = null, array $context = null)
	{
		/**
		 * PSR3 compatibility
		 */
		if (is_string($type) && is_int($message)) {
			$toggledMessage = $type;
			$toggledType = $message;
		} else {
			if (is_string($type) && is_null($message)) {
				$toggledMessage = $type;
				$toggledType = $message;
			} else {
				$toggledMessage = $message;
				$toggledType = $type;
			}
		}

		if ($toggledType === null) {
			$toggledType = Logger::DEBUG;
		}

		/**
		 * Checks if the log is valid respecting the current log level
		 */
		if ($this->_logLevel >= $toggledType) {
			$timestamp = microtime(true);
			if ($this->_transaction) {
				$this->_queue[] = new Item($toggledMessage, $toggledType, $timestamp, $context);
			} else {
				$this->logInternal($toggledMessage, $toggledType, $timestamp, $context);
			}
		}

		return $this;
	}
}