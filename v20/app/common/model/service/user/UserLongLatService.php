<?php
/**
 * 用户定位
 * @author: 衡婷妹
 * @date: 2021/02/02
 */
namespace app\common\model\service\user;

use app\common\model\db\UserLongLat;
use map\longLat;

class UserLongLatService
{   
    public $userLongLatModel = null;

    public function __construct()
    {
        $this->userLongLatModel = new UserLongLat();
    }

    /**
     * 保存地理位置
     * @param $openid
     * @param $long string
     * @param $lat string
     * @param $type int
     * @author: 衡婷妹
     * @date: 2021/02/02
     */
    public function saveLocation($openid,$long,$lat,$type=1){
        if($openid){
            $where = [
                'open_id' => $openid
            ];
            if ($this->getOne($where)) {
                $saveData = [
                    'long' => $long,
                    'lat' => $lat,
                    'dateline' => $_SERVER['REQUEST_TIME'],
                    'type'=>$type
                ];
                $this->updateThis($where,$saveData);
            } else {
                $saveData = [
                    'long' => $long,
                    'lat' => $lat,
                    'dateline' => $_SERVER['REQUEST_TIME'],
                    'type'=>$type,
                    'open_id' => $openid
                ];
                $this->add($saveData);
            }
            if(cfg('im_appid') && cfg('open_kefu_plat') != 'zendesk' && $type == 1){
                //20分钟一次推送 TODO
                if(empty($_SESSION['last_im_time']) || $_SESSION['last_im_time'] - $_SERVER['REQUEST_TIME'] < 1200){
//                    $im = new im();
//                    $im->saveLocation($openid,$long,$lat);
                    $_SESSION['last_im_time'] = $_SERVER['REQUEST_TIME'];
                }
            }
        }else{
            return array('errCode'=>true,'errMsg'=>L_('没有携带openid'));
        }
    }

    /*
     * 得到地理位置
     *
     * 时效120秒
     *
     * 存的是 GPS定位，系统使用的是百度地图，进行转换
     *
    */
    public function getLocation($openid,$timeout=120,$userLongLat=array()){
        if($openid){
            $where = [
                'open_id' => $openid
            ];

            if(empty($userLongLat)){
                $userLongLat = $this->getOne($where);
                if($timeout == 120 && request()->agent == 'wechat_mini'){
                    $timeout = 240;
                }
            }

            if($userLongLat && $userLongLat['long']){
                if($timeout != 0 && $userLongLat['dateline'] < time() - $timeout){
                    $this->removeCookieLocation();
                    return array();
                }
                if(cfg('map_config')=='baidu' || cfg('map_config')=='' || !cfg('map_config')){
                    $longlat_class = new longLat();
                    $location2 = $longlat_class->toBaidu($userLongLat['lat'], $userLongLat['long'],$userLongLat['type'] ? $userLongLat['type'] : 1);
                    if(empty($location2['lng'])){
                        $this->removeCookieLocation();
                        return array();
                    }
                    return array('long'=>$location2['lng'],'lat'=>$location2['lat'],'dateline'=>$userLongLat['dateline']);
                }else{
                    return array('long'=>$userLongLat['long'],'lat'=>$userLongLat['lat'],'dateline'=>$userLongLat['dateline']);
                }

            }
        }
        if ($timeout==0) {
            // 没有时效的拿缓存
            if(isset($_REQUEST['latitude']) && isset($_REQUEST['longitude'])){
                if (isset($_REQUEST['locateType']) && $_REQUEST['locateType']=='baidu') {
                    $longlat_class = new longlat();
                    $location2 = $longlat_class->toBaidu($_REQUEST['latitude'], $_REQUEST['longitude'],$userLongLat['type'] ? $userLongLat['type'] : 1);
                    if(empty($location2['lng'])){
                        $this->removeCookieLocation();
                        return array();
                    }
                    return array('long'=>$location2['lng'],'lat'=>$location2['lat'],'dateline'=>$_SERVER['REQUEST_TIME']);
                }else{
                    return array('long'=>$_REQUEST['latitude'],'lat'=>$_REQUEST['longitude'],'dateline'=>$_SERVER['REQUEST_TIME']);
                }
            }

            if(isset($_COOKIE['userLocationLong']) && isset($_COOKIE['userLocationLat'])){
                //代表是百度地图地位
                if((isset($_COOKIE['userLocationName']) && $_COOKIE['userLocationName']) || (isset($_COOKIE['userLocationLbsType'] ) && $_COOKIE['userLocationLbsType'] == 'baidu')){
                    return array('long'=>$_COOKIE['userLocationLong'],'lat'=>$_COOKIE['userLocationLat'],'dateline'=>$_SERVER['REQUEST_TIME']);
                }
                $longlat_class = new longlat();
                $location2 = $longlat_class->gpsToBaidu($_COOKIE['userLocationLat'], $_COOKIE['userLocationLong']);
                if(empty($location2['lng'])){
                    $this->removeCookieLocation();
                    return array();
                }
                return array('long'=>$location2['lng'],'lat'=>$location2['lat'],'dateline'=>$_SERVER['REQUEST_TIME']);
            }
        }
        return array();
    }

    
    public function removeCookieLocation(){
        if($_COOKIE['userLocationLong']){
            setcookie('userLocationLong','',$_SERVER['REQUEST_TIME'] - 100,'/');
        }
        if($_COOKIE['userLocationLat']){
            setcookie('userLocationLat','',$_SERVER['REQUEST_TIME'] - 100,'/');
        }
    }


    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->userLongLatModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
    }

    /**
     *批量插入数据
     * @param $data array
     * @return array
     */
    public function addAll($data){
        if(empty($data)){
            return false;
        }

        try {
            // insertAll方法添加数据成功返回添加成功的条数
            $result = $this->userLongLatModel->insertAll($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * 更新数据
     * @param $data array
     * @return array
     */
    public function updateThis($where, $data){
        if(empty($data) || empty($where)){
            return false;
        }

        try {
            $result = $this->userLongLatModel->where($where)->update($data);
        } catch (\Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     *获取一条数据
     * @param $where array
     * @return array
     */
    public function getOne($where, $order = []){
        if(empty($where)){
            return false;
        }

        $result = $this->userLongLatModel->getOne($where, $order);
        if(empty($result)){
            return [];
        }

        return $result->toArray();
    }

    /**
     *获取多条数据
     * @param $where array
     * @return array
     */
    public function getSome($where, $field = true,$order=true,$page=0,$limit=0){
        try {
            $result = $this->userLongLatModel->getSome($where,$field ,$order,$page,$limit);
//            var_dump($this->userLongLatModel->getLastSql());
        } catch (\Exception $e) {
            return [];
        }

        return $result->toArray();
    }
}