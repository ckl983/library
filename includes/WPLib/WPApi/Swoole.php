<?php
/**
 * Swoole::send
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\WPApi;

use WPLib\WPApi,
	Logger,
	WPLib\Message;

class Swoole extends WPApi
{
	/**
	 *
	 */
	public static function send($module_name, $data = '', $type = null, $options = [], $timeout = 3, $connection_timeout = 3, $retry_time = 1)
	{
		self::init();

		if (isset(self::$_modules[$module_name])) {
			$module = self::$_modules[$module_name];

			if (!isset($module['tcp_servers'])) {
				return \Helper::appendMessage(new Message($module_name . '模块TCP SERVER配置不存在！', 10086));
			}

			try {

				if (!self::$_task_client[$module_name]) {
					$rand_key = \Helper::getRandByWeight($module['servers'], 'weight');
					$server = $module['tcp_servers'][$rand_key];
					Logger::info(sprintf('TCP服务器地址: %s[%s]', $server['host'], $server['port']));

					$i = 0;
					// 重试1-10次
					$retry_time = min(10, max(1, $retry_time));
					$is_connected = false;
					$client = new \swoole_client(SWOOLE_SOCK_TCP);
					while ($i < $retry_time) {
						if ($client->connect($server['host'], $server['port'], $timeout)) {
							$is_connected = true;
							break;
						}
						$client->close(true);
						$i++;
					}

					if (!$is_connected) {
						Logger::error("连接{$module_name}模块失败!");
						return \Helper::appendMessage(new Message("连接{$module_name}模块失败!", 10086));
					}
					self::$_task_client[$module_name] = $client;
					unset($rand_key, $server, $i, $is_connected, $client);
				}

				$begin_time = microtime(true);
				$len = self::$_task_client[$module_name]->send($data);

				$is_transaction = !Logger::isTransaction();
				$is_transaction && Logger::begin();
				Logger::info(sprintf('发送数据: %s', bin2hex($data)));
				Logger::info(sprintf("USE TIME: %s", microtime(true) - $begin_time));
				$is_transaction && Logger::commit();
				if ($len) {
					return true;
				}
			} catch (\Exception $e) {
				Logger::error(sprintf("异常：%s[%s]", $e->getMessage(), $e->getCode()));
				return \Helper::appendMessage(new Message($e->getMessage(), 10086));
			}

			return false;
		} else {
			return \Helper::appendMessage(new Message($module_name . '模块配置不存在！', 10086));
		}
	}
}