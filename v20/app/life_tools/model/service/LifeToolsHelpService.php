<?php


namespace app\life_tools\model\service;


use app\common\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\life_tools\model\db\LifeToolsHelpNotice;

class LifeToolsHelpService
{
    /**
     * 寻人求助列表
     */
    public function helpList($param)
    {
          $where=[['is_del','=',0],['uid','=',$param['uid']]];
          $list=(new LifeToolsHelpNotice())->helpList($where,true,'pigcms_id desc',$param['page'],$param['pageSize']);
          if(!empty($list['data'])){
            foreach ($list['data'] as $k=>$v){
                $list['data'][$k]['avatar']="";
                $list['data'][$k]['address']="";
                if(!empty($v['uid'])){
                    $user=(new UserService())->getUser($v['uid']);
                    $list['data'][$k]['avatar']=empty($user)?"":replace_file_domain($user['avatar']);
                }

                if(!empty($v['add_time'])){
                    $list['data'][$k]['add_time']=date("Y.m.d H:i:s",$v['add_time']);
                }

                if(!empty($v['images'])){
                    $list['data'][$k]['images']=unserialize($v['images']);
                }

                if($v['lat'] && $v['lng']){
                    $params1 = [
                        'lat' => $v['lat'],
                        'lng' => $v['lng'],
                    ];
                    $nowCity = invoke_cms_model('Area/cityMatching', $params1);
                    if ($nowCity['error_no'] == 0) {
                        $nowCity = $nowCity['retval'];
                        if (!empty($nowCity['area_id'])) {
                            $list['data'][$k]['address']= $nowCity['address_addr'];
                        }
                    }
                }
            }
          }
          return $list;
    }

    /**
     * 求助详情
     */
    public function helpDetail($param)
    {
        $where=[['pigcms_id','=',$param['pigcms_id']],['uid','=',$param['uid']]];
        $detail=(new LifeToolsHelpNotice())->getDetail($where);
        if(!empty($detail)){
            $detail['address']="";
            $user=(new UserService())->getUser($detail['uid']);
            $detail['avatar']=empty($user)?"":replace_file_domain($user['avatar']);
            $detail['images']=unserialize($detail['images']);
            if(!empty($detail['add_time'])){
                $detail['add_time']=date("Y.m.d H:i:s",$detail['add_time']);
            }

            if($detail['lat'] && $detail['lng']){
                $params1 = [
                    'lat' => $detail['lat'],
                    'lng' => $detail['lng'],
                ];
                $nowCity = invoke_cms_model('Area/cityMatching', $params1);
                if ($nowCity['error_no'] == 0) {
                    $nowCity = $nowCity['retval'];
                    if (!empty($nowCity['area_id'])) {
                        $detail['address']= $nowCity['address_addr'];
                    }
                }
            }
        }
        return $detail;
    }
    /**
     * 发布求助
     */
    public function helpUpdate($param)
    {
        $where=[['pigcms_id','=',$param['pigcms_id']]];
        $data['is_solve']=$param['is_solve'];
        $ret=(new LifeToolsHelpNotice())->updateThis($where,$data);
        if($ret!==false){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 发布求助
     */
    public function addHelp($param)
    {
        $data['uid']=$param['uid'];
        $data['name']=$param['name'];
        $data['phone']=$param['phone'];
        $data['images']=$param['images'];
        $data['lat']=$param['lat'];
        $data['lng']=$param['lng'];
        $data['add_time']=$param['add_time'];
        $data['content']=$param['content'];
        if($data['lat'] && $data['lng']){
            $params1 = [
                'lat' => $data['lat'],
                'lng' => $data['lng'],
            ];
            $nowCity = invoke_cms_model('Area/cityMatching', $params1);
            if ($nowCity['error_no'] == 0) {
                $nowCity = $nowCity['retval'];
                if (!empty($nowCity['area_id'])) {
                    $data['province_id']= $nowCity['area_info']['province_id'] ?? 0;
                    $data['city_id']=$nowCity['area_id'] ?? 0;
                    $data['area_id']=$nowCity['area_info']['area_id'] ?? 0;
                }
            }
        }
        $ret=(new LifeToolsHelpNotice())->add($data);
        $tempMsgArr = [];
        $tempMsgArr['title'] = mb_substr($data['content'], 0, 10) . '...';
        $tempMsgArr['status_text'] = '发布成功'; 
        (new TemplateNewsService())->sendTempMsg('TM00017', $tempMsgArr, 0, 0, 2);
        if($ret){
            return true;
        }else{
            return false;
        }
    }
}