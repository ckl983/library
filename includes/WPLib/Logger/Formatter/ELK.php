<?php

/**
 * ELK日志格式
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Logger\Formatter;

use Phalcon\Logger\Formatter;
use Phalcon\Logger\FormatterInterface;

/**
 * Phalcon\Logger\Formatter\Json
 *
 * Formats messages using JSON encoding
 */
class ELK extends Formatter implements FormatterInterface
{

    /**
     * Applies a format to a message before sent it to the internal log
     *
     * @param string message
     * @param int type
     * @param int timestamp
     * @param array $context
     * @return string
     */
    public function format($message, $type, $timestamp, $context = null)
	{
		if (is_array($context)) {
			$message = $this->interpolate($message, $context);
		}

		$timestamp_str = sprintf('%s.%03s%s', date('Y-m-d\TH:i:s'), $timestamp * 1000 % 1000, date('P'));

        return [
            "log_type"   => $this->getTypeString($type),
			"message"    => $message,
			"@timestamp" => $timestamp_str,
		];
	}
}