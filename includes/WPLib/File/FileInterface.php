<?php
/**
 * 文件接口
 *
 * @author
 * @copyright 2018-2019 深圳市冠林轩实业有限公司 <http://www.jiabeiplus.com/>
 */

namespace WPLib\File;


interface FileInterface
{
    public function upload($filename, $content);

    public function download($filename);

    public function delete($filename);
}