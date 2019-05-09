<?php
/**
 * 文件日志
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Logger;

use Phalcon\Logger\Adapter\File as FileLogger,
    Phalcon\Logger\Exception;


class File extends FileLogger
{
    private static $_pid = null;
	public function __construct($name, $options = null)
	{
        self::$_pid = posix_getpid();

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
		fwrite($handler, $this->getFormatter()->format($message, $type, $time, $context));
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
}