<?php
/**
 * 智能垃圾回收对外接口
 */

namespace app\community\controller\platform\device;

use app\community\model\service\Device\GarbageCollectionMachineService;
use app\BaseController;
class GarbageCollectionApiController extends BaseController
{
    public $key = 'h1vb41243knkjc9sta7tgr42b34jh2g4';

    public function apiOutPut($status = 0, $data = [], $msg = '')
    {
        if(!$data){
            $data = [];
        }
        //增加敏感词过滤
        if (cfg('open_filter_word') == 1 && request()->param('system_type') != 'platform') {
            $encodeData = json_encode($data, JSON_UNESCAPED_UNICODE);
            $encodeData = (new \app\common\model\db\FilterWord())->filter($encodeData);
            $decodeData = json_decode($encodeData, true);
            is_array($decodeData) && $data = $decodeData;
        }

        $output = [
            'status' => $status  == '0' ? 1000 : $status,
            'msg' 	 => $msg,
            'data' 	 => $data,
            'code' 	 => $status  == '0' ? 'success' : 'error',
        ];
        $msgAry = explode('_returnUrl_',$msg);
        if($status = 1003 && count($msgAry) == 2){
            $output['msg'] = $msgAry[0];
            $output['url'] = $msgAry[1];
        }
        if(cfg('staff_ticket')){
            // 店员端每次要返回新的ticket,防止过期
            $output['staff_ticket'] = cfg('staff_ticket');
        }
        $output['refresh_ticket'] = strval(request()->refresh_ticket);
        return json($output)->code(200);
    }
    
    /**
     * 获取token
     */
    public function getToken()
    {
        return $this->apiOutPut(0, md5($this->key),'success');
    }
    /**
     * 检核token
     */
    public function checkToken($token)
    {
        if(md5($this->key)!=$token){
            return false;
        }
        return true;
    }

    /**
     * 用户验证接口
     */
    public function getUser()
    {
        $token = $_SERVER['HTTP_TOKEN'];
        $param['device_no'] = $this->request->param("device_no", "", "trim");
        $param['account_type'] = $this->request->param("account_type", 0, "trim");//1:手机号，2:卡号，3:二维码
        $param['account'] = $this->request->param("account", "", "trim");
        try {
            if(!$this->checkToken($token)){
                throw_exception('token异常');
            }
            $data = (new GarbageCollectionMachineService())->getUser($param);
        } catch (\Exception $e) {
            return $this->apiOutPut(-1, $e->getMessage());
        }
        return $this->apiOutPut(0, $data,'success');
    }
    
    /**
     * 接收通知
     */
    public function addNotice()
    {fdump_api($this->request->param(),'garbage_collection/addNotice',1);
//        $token = $_SERVER['HTTP_TOKEN'];
        $param['action'] = $this->request->param("action", "", "trim");//THROW 投递，WARN 预警
        $param['deviceNo'] = $this->request->param("deviceNo", "", "trim");//设备编号
        $param['machineName'] = $this->request->param("machineName", "", "trim");//设备名称
        $param['accountType'] = $this->request->param("accountType", 5, "trim");//账号类型
        $param['account'] = $this->request->param("account", '', "trim");//账号
        $param['phone'] = $this->request->param("phone", "", "trim");//用户手机
        $param['time'] = $this->request->param("time", "", "trim");//时间
        $param['garbage'] = $this->request->param("garbage", [], "trim");//json(type:垃圾类型 1:可回收垃圾 4:有害垃圾 5:厨余垃圾 6:其他垃圾,garbageCategory垃圾类型类目,weight重量（g）,points积分，limit 0：未达上限，1：已达上限)
        $param['address'] = $this->request->param("address", "", "trim");//设备地址
        $param['dateWarn'] = $this->request->param("dateWarn", "", "trim");//预警时间
        $param['bucketNo'] = $this->request->param("bucketNo", "", "trim");//桶编号
        $param['type'] = $this->request->param("type", 0, "trim");//预警类型
        $param['timestamp'] = $this->request->param("timestamp", "", "trim");//当前时间戳，整数（单位秒）
        $param['sign'] = $this->request->param("sign", "", "trim");//参数签名
        try {
//            if(!$this->checkToken($token)){
//                throw_exception('token异常');
//            }
            $data = (new GarbageCollectionMachineService())->addNotice($param);
        } catch (\Exception $e) {
            return $this->apiOutPut(-1, $e->getMessage());
        }
        return json(['code'=>'success'])->code(200);
    }
}