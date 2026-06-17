<?php
/**
 * UploadFileController.php
 * 视频和图片处理
 * Create on 2020/9/10 14:34
 * Created by zhumengqun
 */

namespace app\common\controller\common;

use app\BaseController;
use app\common\model\service\UploadFileService;
use app\community\model\service\contract\ContractHandleService;

class UploadFileController extends BaseController
{
    /**
     * 上传图片
     * @return \json
     */
    public function uploadPictures()
    {

        $service = new UploadFileService();
        $file = $this->request->file('reply_pic');
        $store_id = $this->request->param('store_id', '', 'intval');
        $upload_dir = $this->request->param('upload_dir');
        $source = $this->request->param('source', '', 'trim');
        $source_id = $this->request->param('source_id', 0, 'intval');
        $is_decorate = $this->request->param('is_decorate', 0, 'intval');
        try {
            $savepath = $service->uploadPictures($file, $upload_dir, $store_id, $is_decorate, $source, $source_id);
            return api_output(0, $savepath, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 上传图片
     * @return \json
     */
    public function uploadImg()
    {
        $service = new UploadFileService();
        $file = $this->request->file('reply_pic');
        $upload_dir = $this->request->param('upload_dir');
        try {
            $savepath = $service->uploadPictures($file, $upload_dir);
            $returnArr = [];
            $returnArr['full_url'] = replace_file_domain($savepath);
            $returnArr['image'] = str_replace(cfg('site_url'), '', $savepath);
            return api_output(0, $returnArr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }
    
    /**上传视频
     * @return \json
     */
    public function uploadVideos()
    {
        $service = new UploadFileService();
        $file = $this->request->file('video');
        $upload_dir = $this->request->param('upload_dir');
        if ($upload_dir == '/douyin/video') {
            $setting = ['fileSize' => 1024 * 1024 * 100];
        } else {
            $setting = ['fileSize' => 1024 * 1024 * 10];
        }
        try {
            $savepath = $service->uploadVideos($file, $upload_dir,$setting);
            $returnArr = [];
            $returnArr['video_url'] = $savepath['video_url'];
            $returnArr['video_full_url'] = replace_file_domain($savepath['video_url']);
            $returnArr['image_full_url'] = replace_file_domain($savepath['video_image']);
            $returnArr['video_image'] = $savepath['video_image'];
            return api_output(0, $returnArr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 上传视频
     */
    public function uploadVideo()
    {
        fdump_api(['file' => $this->request->file(), 'param' => $this->request->param()], 'uploadVideos');
        $service = new UploadFileService();
        $file = $this->request->file('reply_mv');
        $fileVideo = $this->request->file('video');
        !$file && $file = $fileVideo;
        $file1=$file;
        $store_id = $this->request->param('store_id', 0, 'intval');
        $upload_dir = $this->request->param('upload_dir');
        if ($upload_dir == '/work_order/video') {
            $params = ['fileSize' => 1024 * 1024 * 20];
        } elseif($upload_dir == '/social/activity/video') {
            $params = ['fileSize' => 1024 * 1024 * 50];
        } else {
            $params = ['fileSize' => 1024 * 1024 * 10];
        }
        $file1 = fopen($file1, "rb");
        $bin = fread($file1, 2); //只读2字节
        fclose($file1);
        $strInfo = @unpack("C2chars", $bin);
        $typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
        $fileType = '';
        switch ($typeCode)
        {
            case 7790:
                $fileType = 'exe';
                break;
            case 7784:
                $fileType = 'midi';
                break;
            case 8297:
                $fileType = 'rar';
                break;
            case 8075:
                $fileType = 'zip';
                break;
            case 255216:
                $fileType = 'jpg';
                break;
            case 7173:
                $fileType = 'gif';
                break;
            case 6677:
                $fileType = 'bmp';
                break;
            case 13780:
                $fileType = 'png';
                break;
            default:
                $fileType = 'mp4';
        }
        fdump_api(['fileType' => $fileType], 'uploadVideos', 1);
        try {
            if (!in_array($fileType, ['mp4', 'flv', 'mov', 'rmvb', 'avi', 'wmv'])) {
                return api_output_error(1003, "您上传的视频文件格式不对！请重新选择");
            } else {
                $savepath = $service->uploadVideo($file, $upload_dir, $store_id, $params);
                if (isset($savepath['image']) && $savepath['image']) {
                    $savepath['thumb_image'] = $savepath['image'];
                    $savepath['image'] = replace_file_domain($savepath['image']);
                }
                if (isset($savepath['url']) && $savepath['url']) {
                    $savepath['video'] = replace_file_domain($savepath['url']);
                }
                return api_output(0, $savepath, 'success');
            }
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 上传文件
     * @return \json
     */
    public function uploadFile()
    {
        $service = new UploadFileService();
        $file = $this->request->file('file');
        $upload_dir = $this->request->param('upload_dir');

        try {
            $savepath = $service->uploadFile($file, $upload_dir);
            return api_output(0, $savepath, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }

    }

    /**
     * 获取已经上传的图片/视频
     */
    public function getUploadImages()
    {
        $store_id = $this->request->param('store_id', '', 'intval');
        $dir = $this->request->param('dir', '', 'trim');
        $type = $this->request->param('type', '', 'trim');//image video
        $service = new UploadFileService();
        try {
            $res = $service->getUploadImages($type, $dir, $store_id);
            return api_output(0, $res, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 用户端图片上传
     * @date: 2021/06/01
     */
    public function uploadPic()
    {
        $service = new UploadFileService();
        $file = $this->request->file('pic');
        $type = $this->request->param('type', 'other', 'trim');
        try {
            $savepath = $service->uploadPictures($file, $type);
            $returnArr = [];
            $returnArr['path'] = $savepath;
            $returnArr['url'] = replace_file_domain($savepath);
            return api_output(0, $returnArr, 'success');
        } catch (\Exception $e) {
            return api_output_error(1003, $e->getMessage());
        }
    }

    /**
     * 公租房上传文件
     * @author: liukezhu
     * @date : 2022/8/5
     * @return \json
     */
    public function uploadPublicRental(){
        $file = $this->request->file('file');
        if(empty($file)){
            return api_output(1001,[],'请选择文件');
        }
        try {
            $list = (new UploadFileService())->uploadPublicRental($file,'house/publicRental');
            return api_output(0,$list);
        }catch (\Exception $e){
            return api_output_error(-1, $e->getMessage());
        }
    }

    /**
     * 删除素材库
     * @author: zt
     * @date: 2023/01/06
     */
    public function deleteMallImage()
    {
        $urlLists = $this->request->param('url_lists', []);
        $ids = $this->request->param('ids', []);
        if (empty($urlLists) && empty($ids)) {
            return api_output(1001, [], '请选择文件');
        }
        $path = [];
        $mallImageMod = new \app\mall\model\db\MallImage();
        if($ids){
            $mallImageMod->whereIn('id', $ids)->delete();
        }else{
            foreach ($urlLists as $v) {
                $path[] = $v;
                $path[] = parse_url($v, PHP_URL_PATH);
            }
            $mallImageMod->whereIn('url', $path)->delete();
        }
        return api_output(0, [], '删除成功');
    }

    /**
     * 合同附件上传
     */
    public function uploadContractAppendix()
    {
        $file = $this->request->file('file');
        if (empty($file)) {
            return api_output(1001, [], '请选择文件');
        }
        try {
            $params = [
                'fileExt' => 'jpg,png,jpeg,doc,docx,pdf', 'fileSize' => 1024 * 1024 * 10
            ];
            $list = (new UploadFileService())->commonUploadFile($file, 'house/contractAppendix', $params);
            $list['upload_dir'] = 'house/contractAppendix';
            $result = (new ContractHandleService())->addContractAppendix($list);
            return api_output(0, $result);
        } catch (\Exception $e) {
            return api_output_error(-1, $e->getMessage());
        }
    }
}
