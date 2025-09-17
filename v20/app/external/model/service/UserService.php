<?php


namespace app\external\model\service;


use app\common\model\db\User;
use app\common\model\db\UserLabel;
use app\employee\model\db\EmployeeCardUser;
use app\employee\model\service\EmployeeCardUserService;
use app\life_tools\model\db\EmployeeCard;
use Model;
use net\Http;

class UserService
{
    /**
     * 获取路径
     */
    public function getPath($param)
    {
        //查询用户是否已经注册
        $userInfo = (new User())->where(['phone'=>$param['phone']])->field('uid')->find();
        if($userInfo){
            $uid = $userInfo['uid'];
        }else{//注册用户
            $add = [
                'phone' => $param['phone'],
                'nickname' => $param['realname'],
            ];
            $uid = (new User())->insertGetId($add);
            if(!$uid){
                throw new \think\Exception("注册用户失败");
            }
        }
        //查询是否已经是员工会员
        if(cfg('personnel_merchant')){//已经配置了会员商家
            $merId = cfg('personnel_merchant');
            //查询是否开通会员
            $employeeCard = (new EmployeeCard())->where(['mer_id'=>$merId])->field('card_id')->find();
            if($employeeCard && $employeeCard['card_id']){//支持员工卡，查询是否已创建员工
                $employeeCardUser = (new EmployeeCardUser())->where(['mer_id'=>$merId,'uid'=>$uid])->field('user_id,lable_ids')->find();
                $lableIds = cfg('personnel_lable_ids')?:'';
                $lableIds = $lableIds ? explode(',',$lableIds) : '';
                if(!$employeeCardUser){//没有则新增员工
                    (new EmployeeCardUserService())->saveCardUser([
                        'mer_id'=>$merId,
                        'user_id'=>0,
                        'card_id'=>$employeeCard['card_id'],
                        'name'=>$param['realname'],
                        'card_number'=>date('Ymd').$uid,
                        'phone'=>$param['phone'],
                        'identity'=>'会员',
                        'department'=>date('Y').'人才引进',
                        'uid' => $uid,
                        'card_money' => 0,
                        'card_score' => 0,
                        'lable_ids' => $lableIds,
                        'status' => 1,
                    ]);
                }else{
                    $oldLabel = $employeeCardUser['lable_ids'] ? explode(',',$employeeCardUser['lable_ids']) : [];
                    $addLabel = cfg('personnel_lable_ids') ? explode(',',cfg('personnel_lable_ids')) : [];
                    $newLabelId = array_unique(array_merge($oldLabel,$addLabel));
                    $newLabelId = $newLabelId ? implode(',',$newLabelId) : '';
                    (new EmployeeCardUser())->where('user_id',$employeeCardUser['user_id'])->update([
                        'lable_ids' => $newLabelId,
                        'last_time' => time()
                    ]);
                }
            }
        }
        $param['query'] = '';
        if($param['path']){
            $pathAry = explode('?',$param['path']);
            if(count($pathAry) >= 2){
                $param['path'] = $pathAry[0];
                unset($pathAry[0]);
                $param['query'] = count($pathAry) == 1 ? $pathAry[1] : implode('?',$pathAry);
                $param['query'] .= '&';
            }
        }
        $param['query'] .= 'source_type=personnel&uid='.$uid;
        $pathInfo = $this->miniPath($param);
        if(!$pathInfo){
            throw new \think\Exception("获取路径失败");
        }
        if($pathInfo->errcode){
            throw new \think\Exception($pathInfo->errmsg.'【'.$pathInfo->errcode.'】');
        }
        return ['path'=>$pathInfo->openlink];
    }

    /**
     * 获取小程序对外path
     */
    public function miniPath($param)
    {
        $wxapp_access_token = invoke_cms_model('Access_token_wxapp_expires/get_access_token');
        $wxapp_access_token = $wxapp_access_token['retval']['access_token'];
        $url = 'https://api.weixin.qq.com/wxa/generatescheme?access_token=' . $wxapp_access_token;
        $postData = [
            'jump_wxa'=>[
                'path' => $param['path'],
                'query' => $param['query'],
                'env_version' => cfg('personnel_wxapp_type'),//正式版为"release"，体验版为"trial"，开发版为"develop"
            ],
            'expire_type' => 1,
            'expire_interval' => 30
        ];
        $qrcode = Http::curlPostOwn($url, json_encode($postData));
        $qrcode = json_decode($qrcode);
        return $qrcode;
    }
}