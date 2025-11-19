<?php

namespace  thirdLink;

class sftp
{
    public $connectLink = '';
    public $conf = [
        'host'=>'',//服务器地址
        'port'=>'',
        'user'=>'',
        'password'=>'',//密码
    ];

    public function __construct($conf=[]) {
        if (empty($conf)) {
            $conf['host'] = cfg('thirdSftpHost');
            $conf['port'] = cfg('thirdSftpPort');
            $conf['user'] = cfg('thirdSftpUser');
            $conf['password'] = cfg('thirdSftpPassword');
        }
        $this->conf = $conf;
    }

    public function downloadFile($path='aiHorse',$sftpFilePath='') {
        $conf = $this->conf;
        $conf['port'] = isset($conf['port'])&&$conf['port']?$conf['port']:'22';
        if (!$conf['host']||!$conf['port']||!$conf['user']||!$conf['password']){
            throw new \Exception('相关连接信息缺失');
        }
        $conn = ssh2_connect($conf['host'],$conf['port']);
        if (!ssh2_auth_password($conn,$conf['user'],$conf['password'])){
            throw new \Exception('sftp连接失败');
        }
        $filePathBase = '/upload/sftp/'.$path.'/';
        $localPath = app()->getRootPath().'..'.$filePathBase;//拉去文件后放置在本地的路由
        //创建文件夹
        if (!is_dir($localPath)) {
            $dir = mkdir($localPath, 0777, true);
            if (!$dir) {
                throw new \Exception('创建本地文件夹失败');
            }
        }
        $dateKey = date('Ymd');
        $filePathBase .= $dateKey;
        $toSftpUrl1 = $localPath.$dateKey;
        //创建文件夹
        if (!is_dir($toSftpUrl1)){
            $dir1 = mkdir($toSftpUrl1,0777,true);
            if (!$dir1){
                throw new \Exception('创建本地文件夹失败');
            }
        }
        try {
            $connection = ssh2_connect($conf['host']);
            ssh2_auth_password($connection,$conf['user'],$conf['password']);
            $sftp = ssh2_sftp($connection);
            $sftpFilePathArr = explode('/',$sftpFilePath);
            $localFile = array_pop($sftpFilePathArr);
            $localRealFile = $toSftpUrl1 .'/'. $localFile;//本地存放的文件路由
            $filePath = $filePathBase .'/'. $localFile;//本地存放的文件相对路由
            //如果文件存在则删除，当然这里也可以根据需求进行修改
            if (is_file($localRealFile)) {
                unlink($localRealFile);
            }
            $resource = "ssh2.sftp://{$sftp}" . $sftpFilePath;//远程服务器下的文件路由
            //远程文件拷贝到本地
            copy($resource, $localRealFile);
            ssh2_disconnect($sftp);
            return ['fileFullPath' => $localRealFile,'filePath' => $filePath];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}