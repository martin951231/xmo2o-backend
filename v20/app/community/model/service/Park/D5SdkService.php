<?php
/**
 * @author : liukezhu
 * @date : 2022/1/7
 */
namespace app\community\model\service\Park;

use app\community\model\db\AccessTokenCommonExpires;
use net\Http as Http;

class D5SdkService{

    protected $config=[];

    public function __construct($config=[]){
        $this->config=$config;
    }

    //todo token失效，重新调用方法
    private function reacquire($func,$result,$data=[]){
        if(isset($result['code']) && $result['code'] == 3){
            $this->getToken(1);
            $result1=$result;
            $result=call_user_func_array(array(new $this($this->config),$func),$data);
            fdump_api(['方法名--'.$func.',line:'.__LINE__,$result1,$result,$data],'d5_park/reacquire',1);
        }
        return $result;
    }

    //todo 统一返回错误提示
    public function getErrMsg($result){
        $msg='';
        if(isset($result['errMsg'])){
            $msg=$result['errMsg'];
        }elseif (isset($result['msg'])){
            $msg=$result['msg'];
        }
        return $msg;
    }

    /**
     * 获取token
     * @author: liukezhu
     * @date : 2022/1/14
     * @param int $is_again
     * @return array
     */
    public function getToken($is_again=0){
        $AccessTokenCommonExpires = new AccessTokenCommonExpires();
        $where = [
            'type' =>'d5_park_token',
            'access_id' =>$this->config['userName'].'_'.$this->config['passWord'],
        ];
        $token_info = $AccessTokenCommonExpires->getOne($where);
        $time = time();
        if ($token_info && intval($token_info['access_token_expire']) > $time && $is_again == 0) {
            return ['success' => true,'errMsg'=>'ok','data' =>$token_info['access_token']];
        }
        else{
            $passWord=urlencode(strtoupper(md5($this->config['passWord'])));
            $url=$this->config['base_url'].'/api/Login/GetToken?userName='.urlencode($this->config['userName']).'&passWord='.$passWord;
            $result = json_decode(Http::curlGet($url),true);
            fdump_api(['获取token,line:'.__LINE__,$url,$result],'d5_park/getToken',1);
            if(!is_array($result) || empty($result)){
                return ['success' => false,'errMsg'=>'token解析错误','data'=>[]];
            }
            if(isset($result['success']) && !$result['success']){
                return ['success' => false,'errMsg'=>'token获取失败【'.(self::getErrMsg($result)).'】','data'=>[]];
            }
            $set = array(
                'access_token' => $result['data']['accessToken'],
                'access_token_expire' => $time + 1200,  //有效期半小时 计算为20分钟
            );
            if ($token_info) {
                $AccessTokenCommonExpires->saveOne($where,$set);
            }
            else {
                $set['type'] = $where['type'];
                $set['access_id'] = $where['access_id'];
                $AccessTokenCommonExpires->addOne($set);
            }
            return ['success' => true,'errMsg'=>'ok','data' =>$set['access_token']];
        }
    }


    /**
     * 获取道闸信息
     * @author: liukezhu
     * @date : 2022/1/10
     * @return array|mixed
     */
    public function getChannel(){
        $url=$this->config['base_url'].'/api/device/sluice/channel';
        $token=$this->getToken();
        if(!$token['success']){
            return ['success'=>false,'errMsg'=>$token['errMsg'],'data'=>[]];
        }
        $result = json_decode(Http::curlGet($url,15,['staffid'=>$token['data']]),true);
        $result=$this->reacquire('getChannel',$result);
        fdump_api(['获取道闸信息,line:'.__LINE__,$url,$result],'d5_park/getChannel',1);
        return ['success' => true,'errMsg'=>'ok','data' =>$result['data']];
    }


    /**
     * 月租车查询
     * @author: liukezhu
     * @date : 2022/1/10
     * @param $carNum
     * @return array
     */
    public function monthCarQuery($carNum){
        $url=$this->config['base_url'].'/api/MonthlyCar/Get';
        $token=$this->getToken();
        if(!$token['success']){
            return ['success'=>false,'errMsg'=>$token['errMsg'],'data'=>[]];
        }
        $result = Http::curlPostToken($url,json_encode(['carNum'=>$carNum],JSON_UNESCAPED_UNICODE),15,['staffid'=>$token['data']]);
        $result=$this->reacquire('monthCarQuery',$result,['carNum'=>$carNum]);
        fdump_api(['获取token,line:'.__LINE__,$url,$carNum,$result],'d5_park/monthCarQuery',1);
        $msg=self::getErrMsg($result);
        if(isset($result['success']) && !$result['success']){
            return ['success' => false,'errMsg'=>'月租车查询失败【'.$msg.'】','data'=>$result['data']];
        }
        return ['success' => true,'errMsg'=>$msg,'data' =>$result['data']];
    }

    //-------------------------todo 简洁版 start-------------
    /**
     *月租车下发  carNum：车牌号，name：姓名，beginDate：起始日期，endDate：截止日期，voucherType：凭证类型，mobileNo：手机号
     * @author: liukezhu
     * @date : 2022/1/10
     * @param $data
     * @return array
     */
    public function carAdd($data){
        $url=$this->config['base_url'].'/api/MonthlyCar/Add';
        $token=$this->getToken();
        if(!$token['success']){
            return ['success'=>false,'errMsg'=>$token['errMsg'],'data'=>[]];
        }
        $result = Http::curlPostToken($url,json_encode($data,JSON_UNESCAPED_UNICODE),15,['staffid'=>$token['data']]);
        $result=$this->reacquire('carAdd',$result,['data'=>$data]);
        fdump_api(['获取token,line:'.__LINE__,$url,$data,$result],'d5_park/carAdd',1);
        $msg=self::getErrMsg($result);
        if(isset($result['success']) && !$result['success']){
            return ['success' => false,'errMsg'=>'月租车下发失败【'.$msg.'】','data'=>[]];
        }
        return ['success' => true,'errMsg'=>$msg, 'data' =>[]];
    }

    /**
     * 月租车延期 carNum：车牌号，payFare：缴费额(int)，monthCount：延期月份数(注:当缴费额不足于支付延期月份数时,月租车延期操作失败)(int)
     * @author: liukezhu
     * @date : 2022/1/10
     * @param $data
     * @return array
     */
    public function carDelay($data){
        $url=$this->config['base_url'].'/api/MonthlyCar/Delay';
        $token=$this->getToken();
        if(!$token['success']){
            return ['success'=>false,'errMsg'=>$token['errMsg'],'data'=>[]];
        }
        $result = Http::curlPostToken($url,json_encode($data,JSON_UNESCAPED_UNICODE),15,['staffid'=>$token['data']]);
        $result=$this->reacquire('carDelay',$result,['data'=>$data]);
        fdump_api(['获取token,line:'.__LINE__,$url,$data,$result],'d5_park/carDelay',1);
        $msg=self::getErrMsg($result);
        if(isset($result['success']) && !$result['success']){
            return ['success' => false,'errMsg'=>'月租车延期失败【'.$msg.'】','data'=>[]];
        }
        return ['success' => true,'errMsg'=>$msg, 'data' =>[]];
    }
    //-------------------------简洁版 end-------------

    //-------------------------todo 完成版 start-------------
    /**
     * 月租车新增或修改
     *
     *  $data=[
        'parkingNo'=>'cs0110131', //车位编号
        'beginTime'=>1641657600000, //起始日间(13 位时间戳)
        'endTime'=>1642089600000,//截止日间(13 位时间戳)
        'id'=>'22011002',       //记录 ID[作唯一标识用]，(修改删除时需要传入一样的 ID)
        'cars'=>array(
            array(
                'plateNo'=>'吉ABC131', //车牌号
                'owner'=>'测试车主31', //车主姓名
                'userId'=>'userid31', //用户ID
                'deviceIds'=>[
                    '33F8C16D-1E43-4D9F-9678-D2747B0E664E',
                    '61870405-8fe7-4336-bfcc-2fd71f035093',
                    '93fbca63-2986-41f8-a0a3-cb89f4b26799',
                    'd5ce946d-ccf6-4004-92b8-4b89a354052b'
                    ] //设备集合(要办理哪些车闸权限,从 2.3.1 方法获取道闸信息)
                )
            )
        ];
     *
     * @author: liukezhu
     * @date : 2022/1/10
     * @param $data
     * @return array
     */
    public function monthCarAdd($data){
        $url=$this->config['base_url'].'/api/Th/AddMonthCar';
        $token=$this->getToken();
        if(!$token['success']){
            return ['success'=>false,'errMsg'=>$token['errMsg'],'data'=>[]];
        }
        $result = Http::curlPostToken($url,json_encode($data,JSON_UNESCAPED_UNICODE),15,['staffid'=>$token['data']]);
        $result=$this->reacquire('monthCarAdd',$result,['data'=>$data]);
        fdump_api(['获取token,line:'.__LINE__,$url,$data,$result],'d5_park/monthCarAdd',1);
        $msg=self::getErrMsg($result);
        if(isset($result['success']) && !$result['success']){
            return ['success' => false,'errMsg'=>'月租车新增或修改失败【'.$msg.'】','data'=>[]];
        }
        return ['success' => true,'errMsg'=>$msg,'data' =>[]];
    }

    /**
     * 月租车删除
     * @author: liukezhu
     * @date : 2022/1/10
     * @param $id
     * @return array
     */
    public function monthCarDel($id){
        $url=$this->config['base_url'].'/api/Th/DelMonthCar';
        $token=$this->getToken();
        if(!$token['success']){
            return ['success'=>false,'errMsg'=>$token['errMsg'],'data'=>[]];
        }
        try {
            $result = Http::curlPostToken($url,json_encode(['id'=>$id],JSON_UNESCAPED_UNICODE),15,['staffid'=>$token['data']]);
            $result=$this->reacquire('monthCarDel',$result,['id'=>$id]);
            fdump_api(['获取token,line:'.__LINE__,$url,$id,$result],'d5_park/monthCarDel',1);
            $msg=self::getErrMsg($result);
            if(!$result){
                return ['success' => false,'errMsg'=>'月租车删除失败','data'=>[]];
            }
            if(isset($result['success']) && !$result['success']){
                return ['success' => false,'errMsg'=>'月租车删除失败【'.$msg.'】','data'=>[]];
            }
            return ['success' => true,'errMsg'=>$msg,'data' =>[]];
        } catch (\Exception $e) {
            return ['success' => false,'errMsg'=>'月租车删除失败【请联系设备方】','data'=>[]];
        }

    }
    //-------------------------完成版 end-------------

    /**
     * 获取停车场人员信息
     * @author: liukezhu
     * @date : 2022/1/10
     * @param $data
     * @return array|bool|mixed|string
     */
    public function getPerson($data){
        $url=$this->config['base_url'].'/api/Th/GetPerson';
        $token=$this->getToken();
        if(!$token['success']){
            return ['success'=>false,'errMsg'=>$token['errMsg'],'data'=>[]];
        }
        $result = Http::curlPostToken($url,json_encode($data,JSON_UNESCAPED_UNICODE),15,['staffid'=>$token['data']]);
        $result=$this->reacquire('getPerson',$result,['data'=>$data]);
        fdump_api(['获取停车场人员信息,line:'.__LINE__,$url,$data,$result],'d5_park/getPerson',1);
        return ['success' => true,'errMsg'=>'ok','data' =>$result['data']];
    }

    /**
     * 车辆进场数据
     * @author: liukezhu
     * @date : 2022/1/10
     * @param $data
     * @return array|bool|mixed|string
     */
    public function getParkIn($data){
        $url=$this->config['base_url'].'/api/caraccess/find/In';
        $token=$this->getToken();
        if(!$token['success']){
            return ['success'=>false,'errMsg'=>$token['errMsg'],'data'=>[]];
        }
        $result = Http::curlPostToken($url,json_encode($data,JSON_UNESCAPED_UNICODE),15,['staffid'=>$token['data']]);
        $result=$this->reacquire('getParkIn',$result,['data'=>$data]);
        fdump_api(['车辆进场数据,line:'.__LINE__,$url,$data,$result],'d5_park/getParkIn',1);
        return ['success' => true,'errMsg'=>'ok','data' =>$result['data']];
    }

    /**
     * 车辆离场数据
     * @author: liukezhu
     * @date : 2022/1/10
     * @param $data
     * @return array|bool|mixed|string
     */
    public function getParkOut($data){
        $url=$this->config['base_url'].'/api/caraccess/find/Out';
        $token=$this->getToken();
        if(!$token['success']){
            return ['success'=>false,'errMsg'=>$token['errMsg'],'data'=>[]];
        }
        $result = Http::curlPostToken($url,json_encode($data,JSON_UNESCAPED_UNICODE),15,['staffid'=>$token['data']]);
        $result=$this->reacquire('getParkOut',$result,['data'=>$data]);
        fdump_api(['车辆离场数据,line:'.__LINE__,$url,$data,$result],'d5_park/getParkOut',1);
        return ['success' => true,'errMsg'=>'ok','data' =>$result['data']];
    }

    /**
     * 获取出入场图片
     * @author: liukezhu
     * @date : 2022/1/10
     * @param $code
     * @return array|bool|mixed|string
     */
    public function getParkImg($code){
        $url=$this->config['base_url'].'/api/caraccess/find/GetPic?InVoucherCode='.$code;
        $token=$this->getToken();
        if(!$token['success']){
            return ['success'=>false,'errMsg'=>$token['errMsg'],'data'=>[]];
        }
        $result = json_decode(Http::curlGet($url,15,['staffid'=>$token['data']]),true);
        $result=$this->reacquire('getParkImg',$result,['code'=>$code]);
        fdump_api(['获取出入场图片,line:'.__LINE__,$url,$code,$result],'d5_park/getParkImg',1);
        return ['success' => true,'errMsg'=>'ok','data' =>$result['data']];
    }

    /**
     * 无牌车出入场
     * @author: liukezhu
     * @date : 2022/1/11
     * @param $data
     * @return array
     */
    public function getVoucher($data){
        $url=$this->config['base_url'].'/api/payment/AddInVoucher';
        $token=$this->getToken();
        if(!$token['success']){
            return ['success'=>false,'errMsg'=>$token['errMsg'],'data'=>[]];
        }
        $result = Http::curlPostToken($url,json_encode($data,JSON_UNESCAPED_UNICODE),15,['staffid'=>$token['data']]);
        $result=$this->reacquire('getVoucher',$result,['data'=>$data]);
        fdump_api(['无牌车出入场,line:'.__LINE__,$url,$data,$result],'d5_park/getVoucher',1);
        return ['success' => true,'errMsg'=>'ok','data' =>$result['data']];
    }




}