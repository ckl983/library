<?php

use WPLib\Message;

/**
 * Url工具类
 *
 * @author
 * @copyright 2014-2018
 */


class UploadFile
{
    protected static $adapter = [];

    /**
     * Form表单方式上传文件
     *
     * @param $id
     * @param $type
     * @return array|bool
     */
    public static function form($id, $type)
    {
        if (!isset($_FILES[$id])) {
            return \Helper::appendMessage(new Message("form表单{$id}不存在", 10086));
        }

        if ($_FILES[$id]['error'] > 0) {
            $errorList = [
                UPLOAD_ERR_INI_SIZE => '上传的文件大小超过限制',
                UPLOAD_ERR_FORM_SIZE => '上传的文件大小超过限制',
                UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
                UPLOAD_ERR_NO_FILE => '没有文件被上传',
                UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
                UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            ];
            if (isset($errorList[$_FILES[$id]['error']])) {
                return \Helper::appendMessage(new Message($errorList[$_FILES[$id]['error']], 10086));
            } else {
                return \Helper::appendMessage(new Message("文件上传错误", 10086));
            }
        }

        if (!isset(self::$adapter[$type])) {
            $di = \Phalcon\Di::getDefault();
            if (isset($di->get('config')->file->$type)) {
                $config = $di->get('config')->file->$type;
                $adapter = new $config->Adapter();
                if (isset($config->config)) {
                    foreach ($config->config as $k => $v) {
                        $adapter->$k = $v;
                    }
                }
                self::$adapter[$type] = $adapter;
            } else {
                return \Helper::appendMessage(new Message('请检查配置', 10086));
            }
        }

        $adapter = self::$adapter[$type];

        $content  = file_get_contents($_FILES[$id]['tmp_name']);
        $filename = self::filename($type, $_FILES[$id]['name'], $content);
        $res = $adapter->upload($filename, $content);

        if ($res === false) {
            $message = $adapter->getLastMessage();

            return \Helper::appendMessage(new Message($message->getMessage(), $message->getCode()));
        }

        return [
            'type' => $type,
            'filename' => $filename,
            'size' => $_FILES[$id]['size'],
        ];
    }

    /**
     * 删除文件
     *
     * @param $filename
     */
    public static function delete($filename)
    {
        $type = explode('/', $filename)[0];

        if (!isset(self::$adapter[$type])) {
            $di = \Phalcon\Di::getDefault();
            if (isset($di->get('config')->file->$type)) {
                $config = $di->get('config')->file->$type;
                $adapter = new $config->Adapter();
                if (isset($config->config)) {
                    foreach ($config->config as $k => $v) {
                        $adapter->$k = $v;
                    }
                }
                self::$adapter[$type] = $adapter;
            } else {
                return \Helper::appendMessage(new Message('请检查配置', 10086));
            }
        }

        $adapter = self::$adapter[$type];

        if ($adapter->delete($filename) === false) {
            $message = $adapter->getLastMessage();

            return \Helper::appendMessage(new Message($message->getMessage(), $message->getCode()));
        }

        return $adapter->delete($filename);
    }

    /**
     * 生成文件名
     *
     * @param $type
     * @param $name
     * @param $content
     * @return string
     */
    public static function filename($type, $name, $content)
    {
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $md5 = md5($content);
        $filename = sprintf('%s/%s/%s/%s.%s', $type, substr($md5, 0, 2), substr($md5, 2, 2), substr($md5, 4), $ext);

        return $filename;
    }

    /**
     *
     * @param $filename
     * @return string
     */
    public static function url($filename)
    {
        $type = explode('/', $filename)[0];

        $di = \Phalcon\Di::getDefault();
        if (isset($di->get('config')->file->$type)) {
            $config = $di->get('config')->file->$type;
            $access = $config->config->access;

            if ($access->type == 'public') {
                $number = mt_rand(0, count($access->domain) - 1);
                return $access->domain[$number] . $filename;
            }
        } else {
            return \Helper::appendMessage(new Message('请检查配置', 10086));
        }

        if (!isset(self::$adapter[$type])) {
            $config = $di->get('config')->file->$type;
            $adapter = new $config->Adapter();
            if (isset($config->config)) {
                foreach ($config->config as $k => $v) {
                    $adapter->$k = $v;
                }
            }
            self::$adapter[$type] = $adapter;
        } else {
            $adapter = self::$adapter[$type];
        }

        return $adapter->getUrl($filename);
    }
}
