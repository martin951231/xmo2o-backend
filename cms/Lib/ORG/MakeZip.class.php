<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/9/27 16:16
 */
class MakeZip
{
    /**
     * Notes: 主方法：生成压缩包
     * @param string $dir_path  想要压缩的目录：如 './demo/'
     * @param string $zipName   压缩后的文件名：如 './folder/demo.zip'
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/27 16:19
     */
    public function zip($dir_path, $zipName)
    {
        $relationArr = array(
            $dir_path => array(
                'originName' => $dir_path,
                'is_dir' => true,
                'children' => array()
            )
        );
        $this->modifiyFileName($dir_path, $relationArr[$dir_path]['children']);
        $key = array_keys($relationArr);
        $val = array_values($relationArr);
        $zip = new \ZipArchive;
        //ZIPARCHIVE::CREATE没有即是创建
        $zip->open($zipName,\ZipArchive::CREATE);
        $this->zipDir($key[0], '', $zip, $val[0]['children']);
        $zip->close();
        return true;
    }

    /**
     * [zipDir 给zip文件添加目录及文件]
     * @param  [type] $real_path   [根目录]
     * @param  [type] $zip_path    [压缩目录]
     * @param  [type] &$zip        [引入zip资源]
     * @param  [type] $relationArr [参数变量]
     * @return [type]              [description]
     */
    function zipDir($real_path, $zip_path, &$zip, $relationArr)
    {
        $sub_zip_path = empty($zip_path) ? '' : $zip_path . '/';
        if (is_dir($real_path)) {
            foreach ($relationArr as $k => $v) {
                if ($v['is_dir']) {  //是文件夹
                    $zip->addEmptyDir($sub_zip_path . $v['originName']);
                    $this->zipDir($real_path . '/' . $k, $sub_zip_path . $v['originName'], $zip, $v['children']);
                } else { //不是文件夹
                    $zip->addFile($real_path . '/' . $k, $sub_zip_path . $k);
                }
            }
        }
    }
    /**
     * [modifiyFileName 遍历目录下所有文件夹及文件]
     * @param  [type] $path         [需要压缩的目录路径]
     * @param  [type] &$relationArr [引入参数变量]
     * @return [type]               [无]
     */
    function modifiyFileName($path, &$relationArr){
        if (!is_dir($path) || !is_array($relationArr)) {
            return false;
        }
        if ($dh = opendir($path)) {
            $count = 0;
            while (($file = readdir($dh)) !== false) {
                if(in_array($file,array('.', '..', null))) continue; //无效文件，重来
                if (is_dir($path . '/' . $file)) {
                    $relationArr[$file] = array(
                        'originName' => iconv('GBK', 'UTF-8', $file),
                        'is_dir' => true,
                        'children' => array()
                    );
                    $this->modifiyFileName($path . '/' . $file, $relationArr[$file]['children']);
                }else{
                    $relationArr[$file] = array(
                        'originName' => iconv('GBK', 'UTF-8', $file),
                        'is_dir' => false,
                        'children' => array()
                    );
                }
            }
        }
    }
}