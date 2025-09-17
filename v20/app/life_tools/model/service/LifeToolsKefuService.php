<?php


namespace app\life_tools\model\service;


use app\life_tools\model\db\LifeToolsKefu;
use app\life_tools\model\db\User;

class LifeToolsKefuService
{
    public $lifeToolsKefuModel = null;
    public $userModel = null;

    public function __construct()
    {
        $this->lifeToolsKefuModel = new LifeToolsKefu();
        $this->userModel = new User();
    }

    public function addEditKefu($params)
    {
        if(empty($params['name'])){
            throw new \think\Exception('姓名不能为空！');
        }
        if(empty($params['phone'])){
            throw new \think\Exception('手机号不能为空！');
        }

        if(count($params['work']) == 0){
            throw new \think\Exception('业务不能为空！');
        }

        if(count($params['work_date']) == 0){
            throw new \think\Exception('工作日期不能为空！');
        }
        
        asort($params['work']);
        asort($params['work_date']);
        $work_text = implode(',', $params['work']);
        $work_date_text = implode(',', $params['work_date']);
        if(empty($params['pigcms_id'])){
            $user = $this->checkUser($params['phone']);
            $Kefu = $this->lifeToolsKefuModel;
            $Kefu->uid = $user->uid;
            $Kefu->phone = $params['phone'];
        }else{
            $condition = [];
            $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
            $condition[] = ['is_del', '=', 0];
            $Kefu = $this->lifeToolsKefuModel->where($condition)->find();
            if($Kefu->phone != $params['phone']){
                $user = $this->checkUser($params['phone']);
                $Kefu->phone = $params['phone'];
                $Kefu->uid = $user->uid;
            }
        } 
        
        $Kefu->name = $params['name'];
        $Kefu->work = $work_text;
        $Kefu->work_date = $work_date_text;
        $Kefu->create_time = time();
        $Kefu->status = 1;
        $Kefu->is_del = 0;
        return $Kefu->save();
    }

    /**
     * 获取客服列表
     */
    public function getKefuList($params)
    {
        $condition = [];
        $condition[] = ['is_del', '=', 0];
        if(!empty($params['keywords'])){
            $condition[] = ['name|phone', 'like', "%{$params['keywords']}%"];
        }
        $Kefu = $this->lifeToolsKefuModel->where($condition)->paginate($params['page_size'])->append(['work_text', 'work_date_text']);
        return $Kefu;
    }

    /**
     * 获取客服详情
     */
    public function getKefuDetail($params)
    {
        $condition = [];
        $condition[] = ['is_del', '=', 0];
        $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
        $Kefu = $this->lifeToolsKefuModel->where($condition)->append(['work_arr', 'work_date_arr'])->find();
        if(!$Kefu){
            throw new \think\Exception('客服不存在！');
        }
        return $Kefu;
    }

    /**
     * 获取客服详情
     */
    public function delKefu($params)
    {
        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['pigcms_id']];

        $Kefu = $this->lifeToolsKefuModel->where($condition)->find();
        if(!$Kefu){
            throw new \think\Exception('客服不存在！');
        }
        $Kefu->is_del = 1;
        return $Kefu->save();
    }


      /**
     * 检查用户是否可以绑定
     */
    private function checkUser($phone)
    {
        $condition = [];
        $condition[] = ['phone', '=', $phone];
        $user = $this->userModel->field(['uid', 'phone'])->where($condition)->find();
        if (!$user) {
            throw new \think\Exception('绑定用户不存在！');
        }

        //一个用户只能绑定一次
        $condition = [];
        $condition[] = ['uid', '=', $user->uid];
        $condition[] = ['is_del', '=', 0];
        $data = $this->lifeToolsKefuModel->field('pigcms_id')->where($condition)->find();
        if ($data) {
            throw new \think\Exception('该用户已被绑定！');
        }
        return $user;
    }

}