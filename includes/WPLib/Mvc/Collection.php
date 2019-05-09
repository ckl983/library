<?php

/**
 * Collection扩展类
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Mvc;

use Phalcon\Mvc\Collection as BaseCollection;

class Collection extends BaseCollection
{
    public static function findAndModify($query, $update, $fields = null, $options = [])
    {
		$className = get_called_class();

		$model = new $className();

		$connection = $model->getConnection();

		$source = $model->getSource();
		if (empty($source)) {
            throw new \Exception("Method getSource() returns empty string");
        }

		return $connection->selectCollection($source)->findAndModify($query, $update, $fields, $options);
    }

	/**
	 * Perform an aggregation using the Mongo aggregation framework
	 */
	public static function command(array $parameters = null, array $options = [], &$hash = '')
	{
		$className = get_called_class();

		$model = new $className();

		$connection = $model->getConnection();

		return $connection->command($parameters, $options, $hash);
	}

	/**
	 * Perform an aggregation using the Mongo aggregation framework
	 */
	public static function mapReduce(array $parameters = [], array $options = [], &$hash = '')
	{
		if (!is_array($parameters)) {
			throw new \Exception("Parameters must be an array");
		}

		$className = get_called_class();

		$model = new $className();

		$connection = $model->getConnection();

		if (!isset($parameters['mapreduce'])) {
			$source = $model->getSource();
			if (empty($source)) {
				throw new \Exception("Method getSource() returns empty string");
			}
			$parameters = ['mapreduce' => $source] + $parameters;
		}

		return $connection->command($parameters, $options, $hash);
	}
}