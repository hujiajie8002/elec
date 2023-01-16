<?php

namespace app\common\library;

use app\common\exception\UploadException;
use app\common\model\Attachment;
use fast\Random;
use FilesystemIterator;
use think\Config;
use think\File;
use think\Hook;

/**
 * 文件上传类
 */
class Upload
{

    /**
     * 验证码有效时长
     * @var int
     */
    protected static $expire = 120;

    /**
     * 最大允许检测的次数
     * @var int
     */
    protected static $maxCheckNums = 10;

    protected $merging = false;

    protected $chunkDir = null;

    protected $config = [];

    protected $error = '';

    /**
     * @var \think\File
     */
    protected $file = null;
    protected $fileInfo = null;

    public function __construct($file = null)
    {
        $this->config = Config::get('upload');
        $this->chunkDir = RUNTIME_PATH . 'chunks';
        if ($file) {
            $this->setFile($file);
        }
    }

    public function setChunkDir($dir)
    {
        $this->chunkDir = $dir;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file)
    {
        if (empty($file)) {
            throw new UploadException(__('No file upload or server upload limit exceeded'));
        }

        $fileInfo = $file->getInfo();
        $suffix = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        $suffix = $suffix && preg_match("/^[a-zA-Z0-9]+$/", $suffix) ? $suffix : 'file';
        $fileInfo['suffix'] = $suffix;
        $fileInfo['imagewidth'] = 0;
        $fileInfo['imageheight'] = 0;

        $this->file = $file;
        $this->fileInfo = $fileInfo;
        $this->checkExecutable();
    }

    protected function checkExecutable()
    {
        //禁止上传PHP和HTML文件
        if (in_array($this->fileInfo['type'], ['text/x-php', 'text/html']) || in_array($this->fileInfo['suffix'], ['php', 'html', 'htm', 'phar', 'phtml']) || preg_match("/^php(.*)/i", $this->fileInfo['suffix'])) {
            throw new UploadException(__('Uploaded file format is limited'));
        }
        return true;
    }

    protected function checkMimetype()
    {
        $mimetypeArr = explode(',', strtolower($this->config['mimetype']));
        $typeArr = explode('/', $this->fileInfo['type']);
        //Mimetype值不正确
        if (stripos($this->fileInfo['type'], '/') === false) {
            throw new UploadException(__('Uploaded file format is limited'));
        }
        //验证文件后缀
        if ($this->config['mimetype'] === '*'
            || in_array($this->fileInfo['suffix'], $mimetypeArr) || in_array('.' . $this->fileInfo['suffix'], $mimetypeArr)
            || in_array($typeArr[0] . "/*", $mimetypeArr) || (in_array($this->fileInfo['type'], $mimetypeArr) && stripos($this->fileInfo['type'], '/') !== false)) {
            return true;
        }
        throw new UploadException(__('Uploaded file format is limited'));
    }

    protected function checkImage($force = false)
    {
        //验证是否为图片文件
        if (in_array($this->fileInfo['type'], ['image/gif', 'image/jpg', 'image/jpeg', 'image/bmp', 'image/png', 'image/webp']) || in_array($this->fileInfo['suffix'], ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'webp'])) {
            $imgInfo = getimagesize($this->fileInfo['tmp_name']);
            if (!$imgInfo || !isset($imgInfo[0]) || !isset($imgInfo[1])) {
                throw new UploadException(__('Uploaded file is not a valid image'));
            }
            $this->fileInfo['imagewidth'] = isset($imgInfo[0]) ? $imgInfo[0] : 0;
            $this->fileInfo['imageheight'] = isset($imgInfo[1]) ? $imgInfo[1] : 0;
            return true;
        } else {
            return !$force;
        }
    }

    protected function checkSize()
    {
        preg_match('/([0-9\.]+)(\w+)/', $this->config['maxsize'], $matches);
        $size = $matches ? $matches[1] : $this->config['maxsize'];
        $type = $matches ? strtolower($matches[2]) : 'b';
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int)($size * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0));
        if ($this->fileInfo['size'] > $size) {
            throw new UploadException(__('File is too big (%sMiB). Max filesize: %sMiB.',
                round($this->fileInfo['size'] / pow(1024, 2), 2),
                round($size / pow(1024, 2), 2)));
        }
    }

    public function getSuffix()
    {
        return $this->fileInfo['suffix'] ?: 'file';
    }

    public function getSavekey($savekey = null, $filename = null, $md5 = null)
    {
        if ($filename) {
            $suffix = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $suffix = $suffix && preg_match("/^[a-zA-Z0-9]+$/", $suffix) ? $suffix : 'file';
        } else {
            $suffix = $this->fileInfo['suffix'];
        }
        $filename = $filename ? $filename : ($suffix ? substr($this->fileInfo['name'], 0, strripos($this->fileInfo['name'], '.')) : $this->fileInfo['name']);
        $md5 = $md5 ? $md5 : md5_file($this->fileInfo['tmp_name']);
        $replaceArr = [
            '{year}'     => date("Y"),
            '{mon}'      => date("m"),
            '{day}'      => date("d"),
            '{hour}'     => date("H"),
            '{min}'      => date("i"),
            '{sec}'      => date("s"),
            '{random}'   => Random::alnum(16),
            '{random32}' => Random::alnum(32),
            '{filename}' => substr($filename, 0, 100),
            '{suffix}'   => $suffix,
            '{.suffix}'  => $suffix ? '.' . $suffix : '',
            '{filemd5}'  => $md5,
        ];
        $savekey = $savekey ? $savekey : $this->config['savekey'];
        $savekey = str_replace(array_keys($replaceArr), array_values($replaceArr), $savekey);

        return $savekey;
    }

    /**
     * 清理分片文件
     * @param $chunkid
     */
    public function clean($chunkid)
    {
        if (!preg_match('/^[a-z0-9\-]{36}$/', $chunkid)) {
            throw new UploadException(__('Invalid parameters'));
        }
        $iterator = new \GlobIterator($this->chunkDir . DS . $chunkid . '-*', FilesystemIterator::KEY_AS_FILENAME);
        $array = iterator_to_array($iterator);
        foreach ($array as $index => &$item) {
            $sourceFile = $item->getRealPath() ?: $item->getPathname();
            $item = null;
            @unlink($sourceFile);
        }
    }




    /**
     * 普通上传
     * @return \app\common\model\attachment|\think\Model
     * @throws UploadException
     */
    public function upload($savekey = null)
    {
        if (empty($this->file)) {
            throw new UploadException(__('No file upload or server upload limit exceeded'));
        }

        $this->checkSize();
        $this->checkExecutable();
        $this->checkMimetype();
        $this->checkImage();

        $savekey = $savekey ? $savekey : $this->getSavekey();
        $savekey = '/' . ltrim($savekey, '/');
        $uploadDir = substr($savekey, 0, strripos($savekey, '/') + 1);
        $fileName = substr($savekey, strripos($savekey, '/') + 1);

        $destDir = ROOT_PATH . 'public' . str_replace('/', DS, $uploadDir);

        $sha1 = $this->file->hash();

        //如果是合并文件
        if ($this->merging) {
            if (!$this->file->check()) {
                throw new UploadException($this->file->getError());
            }
            $destFile = $destDir . $fileName;
            $sourceFile = $this->file->getRealPath() ?: $this->file->getPathname();
            $info = $this->file->getInfo();
            $this->file = null;
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            rename($sourceFile, $destFile);
            $file = new File($destFile);
            $file->setSaveName($fileName)->setUploadInfo($info);
        } else {
            $file = $this->file->move($destDir, $fileName);
            if (!$file) {
                // 上传失败获取错误信息
                throw new UploadException($this->file->getError());
            }
        }
        $this->file = $file;
        $category = request()->post('category');
        $category = array_key_exists($category, config('site.attachmentcategory') ?? []) ? $category : '';
        $auth = Auth::instance();
        $params = array(
            'admin_id'    => (int)session('admin.id'),
            'user_id'     => (int)$auth->id ?? 0,
            'filename'    => mb_substr(htmlspecialchars(strip_tags($this->fileInfo['name'])), 0, 100),
            'category'    => $category,
            'filesize'    => $this->fileInfo['size'],
            'imagewidth'  => $this->fileInfo['imagewidth'],
            'imageheight' => $this->fileInfo['imageheight'],
            'imagetype'   => $this->fileInfo['suffix'],
            'imageframes' => 0,
            'mimetype'    => $this->fileInfo['type'],
            'url'         => $uploadDir . $file->getSaveName(),
            'uploadtime'  => time(),
            'storage'     => 'local',
            'sha1'        => $sha1,
            'extparam'    => '',
        );
        $attachment = new Attachment();
        $attachment->data(($params));
        $attachment->save();

        \think\Hook::listen("upload_after", $attachment);
        return $attachment;
    }

    public function setError($msg)
    {
        $this->error = $msg;
    }

    public function getError()
    {
        return $this->error;
    }
}
