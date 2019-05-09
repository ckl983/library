<?php
/**
 * 阿里云文件适配器
 *
 * @author
 * @copyright 2018-2019 深圳市冠林轩实业有限公司 <http://www.jiabeiplus.com/>
 */

namespace WPLib\File\Adapter;

use WPLib\File\Adapter,
    WPLib\File\FileInterface;

use OSS\OssClient;
use OSS\Core\OssException;
use WPLib\Message;

class Aliyun extends Adapter implements FileInterface
{
    public $accessKeyId;
    public $accessKeySecret;
    public $endpoint;
    public $bucket;

    public $access = [];

    protected $client = null;

    /**
     * 构建函数
     */
    public function __construct()
    {

    }

    /**
     * 初始化
     *
     * @return bool
     */
    public function initialize()
    {
        try {
            $this->client = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint, false);
        } catch (OssException $e) {
            return $this->appendMessage(new Message($e->getMessage(), 10086));
        }

        return true;
    }

    /**
     * 上传文件
     *
     * @param $filename
     * @param $content
     * @return bool
     */
    public function upload($filename, $content)
    {
        if (self::initialize() === false) {
            return false;
        }

        try {
            $res = $this->client->putObject($this->bucket, $filename, $content);

            return true;
        } catch (OssException $e) {
            return $this->appendMessage(new Message($e->getMessage(), 10086));
        }
    }

    /**
     * 下载文件
     *
     * @param $filename
     * @return bool
     */
    public function download($filename)
    {
        if (self::initialize() === false) {
            return false;
        }

        try {
            $res = $this->client->getObject($this->bucket, $filename);

            return $res['body'];
        } catch (OssException $e) {
            return $this->appendMessage(new Message($e->getMessage(), 10086));
        }
    }

    /**
     * 删除文件
     *
     * @param $filename
     * @return bool
     */
    public function delete($filename)
    {
        if (self::initialize() === false) {
            return false;
        }

        try {
            $res = $this->client->deleteObject($this->bucket, $filename);

            return true;
        } catch (OssException $e) {
            return $this->appendMessage(new Message($e->getMessage(), 10086));
        }
    }

    public function getUrl($filename)
    {
        if (self::initialize() === false) {
            return false;
        }

        try {
            switch ($this->access->type) {
                case 'expired':
                    $res = $this->client->signUrl($this->bucket, $filename, $this->access->timeout);
                    return $res;
                    break;

                case 'public':
                default:
                    $number = mt_rand(0, count($this->access->domain) - 1);
                    return $this->access->domain[$number] . $filename;
            }
        } catch (OssException $e) {
            return $filename;
        }
    }
}