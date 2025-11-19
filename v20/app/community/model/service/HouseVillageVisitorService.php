<?php


namespace app\community\model\service;
use app\community\model\db\FaceUserBindDevice;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageVisitor as HouseVillageVisitorModel;
use app\community\model\service\Car\CarHandleGetInfoService;

class HouseVillageVisitorService
{
    public $model = '';

    public function __construct()
    {
        $this->model = new HouseVillageVisitorModel();
    }
    public $status_desc = array('未通行','已通行（业主）','已通行（小区）','未到访','已到访','已通行（人脸门禁）');
    public $status_desc_color = array('red','green','green','red','green','green');
    public $status_desc_img = array(
        '/static/images/house/visitor/passStatus0.png',
        '/static/images/house/visitor/passStatus1.png',
        '/static/images/house/visitor/passStatus2.png',
        '/static/images/house/visitor/passStatus3.png',
        '/static/images/house/visitor/passStatus4.png',
        '/static/images/house/visitor/passStatus5.png',
    );

    /**
     * 添加访客记录
     * @author lijie
     * @date_time 2020/07/13
     * @param $data
     * @return int|string
     */
    public function addVisitor($data)
    {
        $res = $this->model->addOne($data);
        if (isset($data['car_id']) && trim($data['car_id']) && isset($data['village_id']) && intval($data['village_id']) > 0) {
            // 存在车牌和小区id 清空下相关缓存
            (new CarHandleGetInfoService())->clearPassInfo($data['village_id']);
        }
        return $res;
    }

    /**
     * 根据条件获取访客列表
     * @author lijie
     * @date_time 2020/07/13
     * @param $where
     * @param $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getVisitorLists($where,$field,$page=1,$limit=15,$order='id DESC')
    {
        $data = $this->model->getLists($where,$field,$page,$limit,$order);
        $site_url = cfg('site_url');
        foreach ($data as $k=>$v){
            if ($v['time']) {
                $data[$k]['time_txt'] = date('Y-m-d H:i:s',$v['time']);
            }
            $data[$k]['add_time_txt'] = date('Y-m-d',$v['add_time']);
            if ($v['pass_time']) {
                $data[$k]['pass_time_txt'] = date('Y-m-d H:i:s',$v['pass_time']);
            }
            if($v['visitor_type'] == 1){
                $data[$k]['color'] = '#ff5d5e';
                $data[$k]['visitor_type'] = '亲属';
            }elseif ($v['visitor_type'] == 2){
                $data[$k]['color'] = '#ffa200';
                $data[$k]['visitor_type'] = '朋友';
            }elseif ($v['visitor_type'] == 3){
                $data[$k]['color'] = '#1ed19f';
                $data[$k]['visitor_type'] = '同事';
            }else{
                $data[$k]['color'] = '#56638d';
                $data[$k]['visitor_type'] = '其他';
            }
            $data[$k]['pass_url']        = $site_url . '/wap.php?c=Login&a=visitor_pass&visitor_id='.$v['id'];
            $data[$k]['status_desc']     = isset($this->status_desc[$v['status']]) ? $this->status_desc[$v['status']] : '';
            $data[$k]['status_desc_img'] = isset($this->status_desc_img[$v['status']]) ? $site_url . $this->status_desc_img[$v['status']] : '';
            
        }
        return $data;
    }

    /**
     * 更改访客记录状态
     * @author lijie
     * @date_time 2020/07/13
     * @param $where
     * @param $save
     * @return bool
     */
    public function updateVisitorStatus($where,$save)
    {
        $res = $this->model->saveOne($where,$save);
        return $res;
    }

    /**
     * 获取访客和主人信息
     * @author lijie
     * @date_time 2020/09/02 9:45
     * @param $where
     * @param string $field
     * @return mixed
     */
    public function getVisitorInfo($where,$field='v.visitor_name,v.visitor_phone,v.owner_name,v.owner_phone,v.owner_address,hvb.type')
    {
        $data = $this->model->getOne($where,$field);
        if($data){
            $data['add_time'] = date('Y-m-d',$data['add_time']);
            switch ($data['type']){
                case 0:
                    $data['type'] = '房主';
                    break;
                case 1:
                    $data['type'] = '家属';
                    break;
                case 2:
                    $data['type'] = '租客';
                    break;
                default:
                    $data['type'] = '房主';
            }
        }
        return $data;
    }

    /**
     * 删除访客
     * @author lijie
     * @date_time 2020/11/16
     * @param $where
     * @return bool
     * @throws \Exception
     */
    public function delVisitor($where)
    {

        $visitorInfo = $this->model->get_one($where);
        if (!$visitorInfo) {
            return 1;
        }
        $res = $this->model->delOne($where);
        //删除对应的同步人员信息
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        $condition_user = [];
        $condition_user[] = ['pigcms_id', '=', $visitorInfo['visitor_bind_id']];
        $house_village_bind = $dbHouseVillageUserBind->getOne($condition_user);
        $house_village_bind = $house_village_bind && !is_array($house_village_bind) ? $house_village_bind->toArray() : $house_village_bind;
        $pigcms_id  = isset($house_village_bind['pigcms_id']) && $house_village_bind['pigcms_id'] ? $house_village_bind['pigcms_id'] : 0;
        $village_id = isset($house_village_bind['village_id']) && $house_village_bind['village_id'] ? $house_village_bind['village_id'] : 0;
        if ($pigcms_id && $village_id) {
            $whereBindType = [];
            $whereBindType[] = ['bind_type', '=', 40];
            $whereBindType[] = ['device_type', '=', 5];
            $whereBindType[] = ['person_id', '=', $pigcms_id];
            $whereBindType[] = ['groupID', '=', $village_id];
            $db_face_user_bind_device = new FaceUserBindDevice();
            $aboutFaceUser = $db_face_user_bind_device->getOne($whereBindType);
            $aboutFaceUser = $aboutFaceUser && !is_array($aboutFaceUser) ? $aboutFaceUser->toArray() : $aboutFaceUser;
            $cardNo = isset($aboutFaceUser['code']) && $aboutFaceUser['code'] ? $aboutFaceUser['code'] : '';
            if ($cardNo) {
                $cardNos = [];
                $cardNos[] = $cardNo;
            } else {
                $cardNos = [];
            }
            try {
                $params = [
                    'pigcms_id'          => $pigcms_id,
                    'village_id'         => $village_id,
                    'house_village_bind' => [],
                    'cardNos'            => $cardNos,
                ];
                $delAboutDevice = invoke_cms_model('House_village_face_door/del_a3_user_bind', $params);
            }catch (Exception $e) {
                fdump_api(['删除时同步设备出错'.__LINE__,$pigcms_id,$house_village_bind,$e->getMessage()],'house_user_bind/del_a3_user_bind_log_err',1);
            }
        }
        return $res;
    }

    /**
     * 获取访客数量
     * @author lijie
     * @date_time 2020/12/05
     * @param $where
     * @return int
     */
    public function getVisitorNum($where)
    {
        $count = $this->model->getVisitorCount($where);
        return $count;
    }

    public function getVisitorCount($where)
    {
        $count = $this->model->getCount($where);
        return $count>0 ? $count:0;
    }
}