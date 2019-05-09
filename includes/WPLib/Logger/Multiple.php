<?php
/**
 * 多日志适配
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Logger;

use Phalcon\Di,
    Phalcon\Logger\Multiple as MultipleStream,
    Exception;

class Multiple extends MultipleStream
{

    protected $_logLevel;

    /**
     * Sets a global level
     */
    public function setLogLevel($level)
    {
        $loggers = $this->_loggers;
        if (is_array($loggers)) {
            foreach ($loggers as $logger) {
                $logger->setLogLevel($level);
            }
        }
        $this->_logLevel = $level;
    }

    /**
     * Returns the current log level
     */
    public function getLogLevel()
    {
        return $this->_logLevel;
    }

    /**
     * Starts a transaction
     */
    public function begin()
    {
        $this->_transaction = true;

        $loggers = $this->_loggers;
        if (is_array($loggers)) {
            foreach ($loggers as $logger) {
                $logger->begin();
            }
        }

        return $this;
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

        $loggers = $this->_loggers;
        if (is_array($loggers)) {
            foreach ($loggers as $logger) {
                $logger->commit();
            }
            $this->_queue = [];
        }

        return $this;
    }

    /**
     * Rollbacks the internal transaction
     */
    public function rollback()
    {
        $transaction = $this->_transaction;
        if (!$transaction) {
            throw new Exception("There is no active transaction");
        }

        $this->_transaction = false;

        $loggers = $this->_loggers;
        if (is_array($loggers)) {
            foreach ($loggers as $logger) {
                $logger->rollback();
            }
            $this->_queue = [];
        }

        return $this;
    }

    public function isTransaction()
	{
		return $this->_transaction;
	}
}