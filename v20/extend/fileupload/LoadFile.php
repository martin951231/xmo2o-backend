<?php

namespace fileupload;
use think\image\Exception;

class LoadFile
{
    /**
     * 多图片上传(ajax)
     * @return \think\Response|void
     * @throws \Exception
     */
    public function uploadFile(){
        $files = request()->file('file_name');
        if(!$files){
            return json(['code'=>1,'msg'=>'没有选择上传文件']);
        }

        try{
            // 验证文件格式
            validate(['file'=>['fileExt' => 'zip','fileMime' => 'application/zip']])->check(['file' => $files]);

            $savename = [];
            foreach ($files as $file){
                // 移动到框架应用根目录/public/uploads/zip 目录下
                $savename[] = \think\facade\Filesystem::disk('public')->putFile( 'zip', $file);
            }
            //Cache::set('file',$savename,3600);
            return json(['code'=>200,'msg'=>'文件上传成功','files_name'=>$savename]);
        }catch(ValidateException $e){
            return json(['code'=>0,'msg'=>$e->getError()]);
        }
    }

    /**
     * 图片上传(ajax)
     * @return \think\Response|void
     * @throws \Exception
     */

    public function upload()
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file_name');
        try{
            // 验证
            validate(['imgFile'=>[
                'fileSize' => 410241024,
                'fileExt' => 'jpg,jpeg,png,bmp,gif',
                'fileMime' => 'image/jpeg,image/png,image/gif',
            ]])->check(['imgFile' => $file]);

            // 上传图片到本地服务器
            $saveName = \think\facade\Filesystem::disk('public')->putFile( 'mall', $file, 'data');
            return json(['code'=>1000,'msg'=>'图片上传成功','files_name'=>$saveName]);
        } catch (\Exception $e) {
            // 验证失败 输出错误信息
            return $this->exceptionHandle($e,'图片上传失败!' . $e->getMessage(),'');
        }
    }

}