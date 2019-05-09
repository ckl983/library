<?php

/**
 * 通用工具类
 *
 * @author
 * @copyright 2014-2018
 */
 
 
class Util
{
    /**
     * 将json字符串转化成数组
     * @param $str
     * @return array
     */
    public static function jsonToArray($str)
    {
        if (is_string($str)) {
            $str = json_decode($str);
        }
        $arr = array();
        foreach ($str as $k => $v) {
            if (is_object($v) || is_array($v)) {
                $arr[$k] = self::json_to_array($v);
            } else {
                $arr[$k] = $v;
            }
        }
        return $arr;
    }
	
    public static function formatTimeStamp($time)
    {
        return date("Y-m-d H:i:s",$time);
    }

    public static function array_sort($arr, $keys, $type = SORT_ASC) {
        if (!is_array($arr)) {
            return false;
        }

        $keysvalue = array();
        $new_array = array();
        foreach ($arr as $v) {
            $keysvalue[] = $v[$keys];
        }

        array_multisort($keysvalue, $type, $arr);

        return $arr;
    }

    /**
     * Tree
     *
     * @param $treeList
     */
    public static function tree($treeList, $fieldName = 'name')
    {
        $prefix = '';
        $content = '';
        $level = 1;
        for ($i = 0, $j = count($treeList); $i < $j; $i ++) {
            $node = $treeList[$i];
            $next = $treeList[$i + 1];
            $line = sprintf("%s%s%s", $prefix, $node['is_last'] ? '`-- ' : '|-- ', $node[$fieldName]);
            $content .= $line . "\n";
            if ($node['has_children']) {
                $level ++;
                $prefix .= $node['is_last'] ? '    ' : '|   ';
            } elseif ($node['is_last']) {
                $level_diff = $node['level'] - $next['level'];
                $prefix = substr($prefix, 0, $level_diff * -4);
            }
        }

        return $content;
    }
}
