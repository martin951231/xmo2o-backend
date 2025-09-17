<?php


namespace app\community\controller\street_api;


use app\community\controller\CommunityBaseController;
use app\community\model\service\AreaStreetService;
use app\community\model\service\HouseVillageUserService;
use app\community\model\service\UserService;
use file_handle\FileHandle;

class StreetSuggestsController extends CommunityBaseController
{
    /**
     * 随手拍列表
     * @author lijie
     * @date_time 2020/09/14
     * @return \json
     */
    public function suggestLists()
    {
        $street_id = $this->request->post('area_street_id',0);
        $pigcms_id = $this->request->post('pigcms_id',0);
        if(!$street_id) {
            return api_output_error(1001,'缺少必传参数');
        }
        $user_id = intval($this->request->log_uid);
        if (!$user_id) {
            return api_output_error(1002, "没有登录");
        }
        $page = $this->request->post('page',1);
        $limit = $this->request->post('limit',20);
        $service_suggest = new AreaStreetService();
        $where_street = [];
        $where_street[] = ['area_id','=',$street_id];
        $area_street_info = $service_suggest->getAreaStreet($where_street);
        if ($area_street_info && $area_street_info['area_type']==1) {
            $where['community_id'] = $street_id;
        } else {
            $where['street_id'] = $street_id;
        }
        if($pigcms_id){
            $where['bind_id'] = $pigcms_id;
        } else {
            $service_user = new UserService();
            $res = $service_user->getUserOne(['uid'=>$user_id],'phone,nickname');
            $where['phone'] = $res['phone'];
        }
        $field = 'suggestions_id,status,content,add_time';
        $order = 'suggestions_id DESC';
        $data = $service_suggest->getLimitSuggestsList($where,$page,$field,$order,$limit);
        $data['share_info']=[
            'share_switch'=>intval(cfg('share_switch')),
            'share_img'=>cfg('site_url') . '/static/wxapp/fenxiang/default.png',
            'share_wx'=>cfg('pay_wxapp_important') && cfg('pay_wxapp_username') ? 'wxapp' : 'h5',
            'userName'=>cfg('pay_wxapp_username'),
            'title'=>'随手拍',
            'info'=>'随手拍列表，进入可查看详情。'
        ];
        return api_output(0,$data);
    }

    /**
     * 随手拍详情
     * @author lijie
     * @date_time 2020/09/14
     * @return \json
     */
    public function suggestDetail()
    {
        $suggestions_id = $this->request->post('suggestions_id',0);
        if(!$suggestions_id)
            return api_output_error(1001,'缺少必传参数');
        $service_suggest = new AreaStreetService();
        $where['suggestions_id'] = $suggestions_id;
        $field = 'content,img,add_time,status,suggestions_id';
        $data = $service_suggest->getSuggestsDetail($where,$field);
        $data['share_info']=[
            'share_switch'=>intval(cfg('share_switch')),
            'share_img'=>isset($data['info']['img_arr'][0]) ? $data['info']['img_arr'][0] : cfg('site_url') . '/static/wxapp/fenxiang/default.png',
            'share_wx'=>cfg('pay_wxapp_important') && cfg('pay_wxapp_username') ? 'wxapp' : 'h5',
            'userName'=>cfg('pay_wxapp_username'),
            'title'=>'随手拍',
            'info'=>stringText($data['info']['content'])
        ];
        return api_output(0,$data);
    }

    /**
     * 添加留言
     * @author lijie
     * @date_time 2020/09/14
     * @return \json
     */
    public function addSuggest()
    {
        $street_id = $this->request->post('area_street_id',0);
        $pigcms_id = $this->request->post('pigcms_id',0);
        $village_id = $this->request->post('village_id',0);
        $content = $this->request->post('content','');
        if(!$street_id)
            return api_output_error(1001,'缺少必传参数');
        $img = $this->request->post('img','');
        $img = $img?serialize($img):'';
        if(empty($content) && empty($img)){
            return api_output_error(1001,'缺少必传参数');
        }
        $service_suggest = new AreaStreetService();
        $where_street[] = ['area_id','=',$street_id];
        $area_street_info = $service_suggest->getAreaStreet($where_street);
        $insertData = [];
        $user_id = intval($this->request->log_uid);
        if (!$user_id) {
            return api_output_error(1002, "没有登录");
        }
        $service_user = new UserService();
        $res = $service_user->getUserOne(['uid'=>$user_id],'phone,nickname');
        if ($area_street_info && $area_street_info['area_type']==1) {
            $insertData['community_id'] = $street_id;
            $insertData['street_id'] = 0;
        } else {
            $insertData['street_id'] = $street_id;
            $insertData['community_id'] = 0;
        }
        if($pigcms_id){
            $insertData['bind_id'] = $pigcms_id;
        } else {
            $insertData['bind_id'] = 0;
        }
        if($village_id){
            $insertData['village_id'] = $village_id;
        }
        $insertData['img'] = $img;
        $insertData['content'] = $content;
        $insertData['name'] = $res['nickname'];
        $insertData['phone'] = $res['phone'];
        $insertData['add_time'] = time();
        $service_suggest = new AreaStreetService();
        $res = $service_suggest->addSuggest($insertData);
        if($res)
            return api_output(0,'','添加成功');
        return api_output_error(1001,'服务异常');
    }

    /**
     * 上传图片
     * @author lijie
     * @date_time 2020/09/14
     * @return \json
     */
    public function upload(){
        $file = $this->request->file('imgFile');
        try {
            // 验证
//            validate(['imgFile' => [
//                'fileSize' => 1024 * 1024 * 10,   //10M
//                'fileExt' => 'jpg,png,jpeg,gif,ico',
//                'fileMime' => 'image/jpeg,image/jpg,image/png,image/gif,image/x-icon', //这个一定要加上，很重要！
//            ]])->check(['imgFile' => $file]);
            // 上传到本地服务器
            $savename = \think\facade\Filesystem::disk('public_upload')->putFile('adver', $file);
            if (strpos($savename, "\\") !== false) {
                $savename = str_replace('\\', '/', $savename);
            }
            $imgurl = '/upload/' . $savename;
            $data = [];
            $data['imageUrl_path'] = $imgurl;
//        $data['imageUrl'] = replace_file_domain($imgurl);
            $data['imageUrl'] = cfg('site_url') . $imgurl;
            $data['url'] = thumb_img($data['imageUrl'], '200', '200');
            $params = ['savepath'=>'/upload/' . $imgurl];
            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()) {
                invoke_cms_model('Image/oss_upload_image', $params);
            }
            return api_output(0, $data, "成功");
        }catch (\Exception $e) {
            throw new \think\Exception($e->getMessage());
        }
    }
}