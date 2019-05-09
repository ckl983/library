<?php
/**
 *
 * @author
 * @copyright 2014-2018
 */

namespace Mongo;

use WPLib\Mvc\Collection;

class Counters extends Collection
{
    public function getSource()
    {
        return "glx_counters";
    }

    public static function getLastInsertId(Collection $collection)
    {
        $name = $collection->getSource();

        $res = self::findAndModify([
                '_id' => $name,
            ],
            [
                '$inc' => ['last_insert_id' => 1],
            ],
            null,
            [
                'upsert' => true,
                'new'    => true,
            ]);

        return $res['last_insert_id'];
    }
}