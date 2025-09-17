<?php
/**
 * zatao数据对接
 * Created by PhpStorm.
 * User: zhubaodi
 * DateTime: 2021/11/29 14:06
 */

class ThirdZataoAction extends BaseAction {

    /**
     * Notes: 配置的基本信息
     * @author: zuhbaodi
     * @date_time: 2021/11/29 14:06
     */
    public function index() {
        $where = [];
        $house_village_third_config = M('House_village_third')->where($where)->find();
        $this->assign('third_config',$house_village_third_config);
        $this->display();
    }

    /**
     * Notes: 添加编辑配置项
     * @author: zuhbaodi
     * @date_time: 2021/11/29 14:06
     */
    public function edit() {
        $village_id =  $this->house_session['village_id'];
        $third_id = I('third_id', 0, 'intval');
        if ($third_id<=0) {
            $where = [];
            $house_village_third_config = M('House_village_third')->where($where)->find();
            if ($house_village_third_config) {
                $third_id = $house_village_third_config['id'];
            }
        }
        $orgCode = I('orgCode', '', 'trim');
        $clientId = I('clientId', '', 'trim');

        if (!$orgCode) {
            $this->error('请填写组织编码！');
        }
        if (!$clientId) {
            $this->error('请填写客户ID！');
        }
        if ($third_id) {
            $set = [
                'orgCode' => $orgCode,
                'clientId' => $clientId,
                'last_time' => time(),
            ];
            $set = M('House_village_third')->where(['id' =>$third_id])->save($set);
            if (!$set) {
                $this->error('配置编辑失败！');
            }
        } else {
            $set = [
                'village_id' => $village_id,
                'orgCode' => $orgCode,
                'clientId' => $clientId,
                'add_time' => time(),
                'last_time' => time(),
            ];
            $set = M('House_village_third')->add($set);
            if (!$set) {
                $this->error('配置添加失败！');
            }
        }
        $this->success('配置成功！',U('index'));
    }


    public function handle_third_user_list() {
        $village_id =  $this->house_session['village_id'];
        $status = I('status','0','intval');
        $where['village_id'] = $village_id;
        $searchtype = I("searchtype");
        $keyword = trim(I("keyword"));
        $begin_time = trim(I("begin_time"));
        $end_time = trim(I("end_time"));
        if($keyword){
            switch ($searchtype) {
                case 'content':
                    $where['content'] = ['like', '%'.$keyword.'%'];
                    break;
                case 'admin':
                    $where['realname'] = ['eq', $keyword];
                    break;
                case 'dataid':
                    $where['data_id'] = ['eq', $keyword];
                    break;
            }
        }
        if($begin_time && $end_time){
            $begin_time = strtotime($begin_time);
            $end_time = strtotime($end_time)+86400;
            $where['addtime'] = ['between', [$begin_time, $end_time]];
        }
        if ($status) {
            if (-1==$status) {
                $where['status'] = 0;
            } else {
                $where['status'] = $status;
            }
        }
        $info = D('House_village_third_log')->handle_third_user_list($where,10);
        $this->assign($info);
        $this->display();
    }

    public function detail(){
        $id = $_GET['id'] + 0;

        if(!$id){
            $this->error('传递参数有误！');
        }
        $database_house_village_third_log = D('House_village_third_log');
        $where['id'] = $id;
        $detail = $database_house_village_third_log->handle_third_user_info($where);

        if(!$detail){
            $this->error('该信息不存在！');
        }

        $this->assign('detail',$detail);
        $this->display();
    }

}