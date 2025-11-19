<?php


namespace app\community\controller\village_api;
use app\community\controller\manage_api\BaseController;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\KefuService;

class KefuController extends BaseController
{
    /**
     * 用户信息
     * @author lijie
     * @date_time 2021/10/25
     * @return \json
     */
    public function getUserInfo()
    {
        $user = $this->request->post('user','');
        if(empty($user)){
            return api_output_error(1001,'缺少必传参数');
        }
        $arr = explode('_',$user);
        if(count($arr) == 2){
            $pigcms_id = $arr[1];
            if(!$pigcms_id){
                return api_output_error(1001,'参数格式错误');
            }
            $service_house_village_user_bind = new HouseVillageUserBindService();
            try{
                $where['hvb.pigcms_id'] = $pigcms_id;
                $field = 'hvb.name,hvb.phone,hvb.vacancy_id,s.single_name,f.floor_name,l.layer_name,v.village_name,r.room,hvb.type,u.avatar';
                $data = $service_house_village_user_bind->getVillageBindInfo($where,$field);
                if(empty($data)){
                    return api_output_error(1001,'用户不存在');
                }
                if($data['type'] == 0 || $data['type'] == 3){
                    $data['relation'] = '本人';
                    $data['owner_name'] = $data['name'];
                    $data['owner_phone'] = $data['phone'];
                }else{
                    $owner_info = $service_house_village_user_bind->getBindInfo([['vacancy_id','=',$data['vacancy_id']],['status','=',1],['type','in',[0,3]]],'name,phone');
                    $data['owner_name'] = isset($owner_info['name'])?$owner_info['name']:'';
                    $data['owner_phone'] = isset($owner_info['phone'])?$owner_info['phone']:'';
                    if($data['type'] == 2){
                        $data['relation'] = '租客';
                    }else{
                        $data['relation'] = '家属';
                    }
                }
                $list = [
                    ['title'=>'头像','value'=>$data['avatar'],'type'=>'img'],
                    ['title'=>'姓名','value'=>$data['name'],'type'=>'text'],
                    ['title'=>'手机号码','value'=>$data['phone'],'type'=>'text'],
                    ['title'=>'楼栋名称','value'=>$data['village_name'].$data['single_name'],'type'=>'text'],
                    ['title'=>'所在单元','value'=>$data['floor_name'],'type'=>'text'],
                    ['title'=>'所在楼层','value'=>$data['layer_name'],'type'=>'text'],
                    ['title'=>'房间号','value'=>$data['room'],'type'=>'text'],
                    ['title'=>'业主名称','value'=>$data['owner_name'],'type'=>'text'],
                    ['title'=>'手机号','value'=>$data['owner_phone'],'type'=>'phone'],
                    ['title'=>'与业主关系','value'=>$data['relation'],'type'=>'text'],
                ];
                $type = 'customer';
            }catch (\Exception $e){
                return  api_output_error(-1,$e->getMessage());
            }
        }elseif(count($arr) == 3){
            $service_kefu = new KefuService();
            $username = $user;
            $where['username'] = $username;
            $field='store_id,bind_uid';
            try{
                $info = $service_kefu->getKefu($where,$field);
                if(empty($info)){
                    return api_output_error(1001,'数据不存在');
                }
            }catch (\Exception $e){
                return  api_output_error(-1,$e->getMessage());
            }
            $list = [
                ['title'=>'头像','value'=>$info['avatar'],'type'=>'img'],
                ['title'=>'工号','value'=>$info['job_number'],'type'=>'text'],
                ['title'=>'姓名','value'=>$info['name'],'type'=>'text'],
                ['title'=>'手机号码','value'=>$info['phone'],'type'=>'phone'],
                ['title'=>'管理楼栋','value'=>$info['single_name'],'type'=>'text'],
            ];
            $type = 'kefu';
        }else{
            return api_output_error(1001,'参数格式错误');
        }
        $res['list'] = $list;
        $res['type'] = $type;
        return api_output(0,$res);
    }


}