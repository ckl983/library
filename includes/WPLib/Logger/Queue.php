<?php
/**
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Logger;

use Phalcon\Di,
    Phalcon\Logger\Adapter,
    Phalcon\Logger\Formatter\Line as LineFormatter,
    WPLib\Logger\Formatter\Object as ObjectFormatter,
    Phalcon\Logger\AdapterInterface,
    Exception;

class Queue extends Adapter implements AdapterInterface
{
    protected $mq = null;

    protected $di = null;

    protected $_formatter_mq = null;

    protected $_formatter_file = null;

    protected $prefix = 'fw::';

    protected $key = 'logger';

    public function __construct($name, $options = null)
	{
        $log_dir = dirname($name);
        if (!file_exists($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }

        if (is_array($options)) {
            if ($mode = $options["mode"]) {
                if (strstr($mode, "r")) {
					throw new Exception("Logger must be opened in append or write mode");
                }
            }
        }

        $this->di = Di::getDefault();
        if ($this->mq === null) {
            if ($this->di->has('mq')) {
                $this->mq = Di::getDefault()->get('mq');
            } else {
                throw new Exception('未定义消息队列服务！');
            }
        }

		if ($mode === null) {
            $mode = "ab";
		}

		/**
         * We use 'fopen' to respect to open-basedir directive
         */
		$handler = fopen($name, $mode);
		if (!is_resource($handler)) {
            throw new Exception("Can't open log file at '" . $name . "'");
        }

		$this->_path        = $name;
		$this->_options     = $options;
		$this->_fileHandler = $handler;
	}

	/**
     * Returns the internal formatter
     */
	public function getFormatter($type = 'MQ')
	{
        switch ($type) {
            case 'FILE':
                if (!is_object($this->_formatter_file)) {
                    $this->_formatter = new LineFormatter();
                }
                break;

            default:
                if (!is_object($this->_formatter_mq)) {
                    $this->_formatter = new ObjectFormatter();
                }
                break;
        }

		return $this->_formatter;
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

	/**
     * Writes the log to the stream itself
     */
	public function logInternal($message, $type, $time, $context)
	{
        if (!IN_CLI && $this->di->has('session')) {
            $user_id = isset($context['user_id']) ? $context['user_id'] : (int)$this->di->get('session')->get('user_id');
            $session_id = isset($context['session_id']) ? $context['session_id'] : (string)$this->di->get('session')->getId();
        } else {
            $user_id = 0;
            $session_id = '';
        }
        try {
            $request = $this->di->getShared('request');
            $mq = $this->mq;
            $params = [
                'app_id'       => \WPLib\WPApi::getAppId(),
                'app_identity' => APP_NAME,
                'app_name'     => \WPLib\WPApi::getAppName(),
                'server_name'  => $request->getServerName(),
                'server_addr'  => $request->getServerAddress(),
                'user_id'      => $user_id,
                'session_id'   => $session_id,
                'url'          => $request->getURI(),
                'metadata'     => $context,
                'message'      => $message,
                'time'         => $time,
            ];
            $params += $this->getFormatter()->format($message, $type, $time, $context);
            $mq->push($mq::KEY_ANALY_LOGGER, $params);
        } catch (Exception $e) {

            if ($e instanceof RedisException) {
                // \Logger::error('Redis服务未启动或已停止!');
            }
            $fileHandler = $this->_fileHandler;
		    if (!is_resource($fileHandler)) {
                throw new Exception("Cannot send message to the log because it is invalid");
            }

		    fwrite($fileHandler, $this->getFormatter('FILE')->format($message, $type, $time, $context));
        }
	}

	/**
     * Closes the logger
     */
	public function close()
	{
        return true;
	}
}