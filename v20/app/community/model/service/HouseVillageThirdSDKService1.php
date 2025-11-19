<?php


namespace app\community\model\service;
use tools;

class HouseVillageThirdSDKService1
{

    public $url='http://36.155.108.145:30337/ccp-api/commonapi-server/';
    public $key='HjPt2022';

    public function __construct()
    {
        $this->Des = new tools\Des();
    }

    /**
     * 获取token
     * @author:zhubaodi
     * @date_time: 2021/11/24 16:44
     */
    public function getToken($data){
        $url=$this->url.'out/auth/token?clientId='.$data['clientId'];
        $res=http_request($url);
        return  $res;
    }

    /**
     * 获取组织架构
     * @author:zhubaodi
     * @date_time: 2021/11/24 16:44
     */
    public function getOrg($data){
        $url=$this->url.'out/v1/org/getOrg';
        $headers = [
            "token:".$data['token'],
        ];
        $res = http_request($url, 'POST', [], $headers);
        return  $res;
    }

    /**
     * 获取地区编码
     * @author:zhubaodi
     * @date_time: 2021/11/24 16:44
     */
    public function getAllProvinceCityCounty($data){
        $url=$this->url.'out/v1/community/getAllProvinceCityCounty';
        $headers = [
            "token:".$data['token'],
        ];
        $res = http_request($url, 'GET', [], $headers);
        return  $res;
    }

    /**
     * 创建/编辑社区
     * @author:zhubaodi
     * @date_time: 2021/11/24 16:44
     */
    public function insertOrUpdateCommunity($data){
        $url=$this->url.'out/v1/community/insertOrUpdateCommunity';
        $headers = [
            "token:".$data['token'],
            "Content-Type:application/json",
        ];
        $data_add=[];
        if (isset($data['Id'])){
            $data_add['Id']=$data['Id'];
        }
       // $this->Des->encrypt($data['organizationId'],$this->key);
        $data_add['organizationId']=$data['organizationId'];//组织id
        $data_add['communityAddress']=$data['communityAddress'];//社区地址
        $data_add['communityName']=$data['communityName'];//社区名称
        $data_add['provinceId']=$data['provinceId'];//省ID(编号)
        $data_add['cityId']=$data['cityId'];//市ID(编号)
        $data_add['regionId']=$data['regionId'];//区ID(编号)
        $data_add['communityManagerAccountBindForm']=[
            'mobile'=>$data['communityManagerAccountBindForm']['mobile'],//管理员联系方式
            'nickname'=>$data['communityManagerAccountBindForm']['username'],
            'username'=>$data['communityManagerAccountBindForm']['username'],//管理员账号，英文字母+数字组合，最长30个字符
        ];
        $res = http_request($url, 'POST', json_encode($data_add), $headers);
        return  $res;
    }

    /**
     * 查询社区列表
     * @author:zhubaodi
     * @date_time: 2021/11/25 11:33
     */
    public function findCommunityList($data){
        $url=$this->url.'out/v1/community/findCommunityList';
        $headers = [
            "token:".$data['token'],
        ];
        $data_add=[];
        if (isset($data['communityName'])){
            $data_add['communityName']=$data['communityName'];
        }
        $res = http_request($url, 'POST', http_build_query($data_add), $headers);
        return  $res;
    }

    /**
     * 添加或者编辑房屋
     * @author:zhubaodi
     * @date_time: 2021/11/25 11:36
     */
    public function addOrUpdateBuilding($data){
        $url=$this->url.'out/v1/house/addOrUpdateBuilding';
        $headers = [
            "token:".$data['token'],
            "Content-Type:application/json",
        ];
        $data_add=[];
        if (isset($data['id'])){
            $data_add['id']=$data['id'];//房屋ID
        }
        $data_add['communityId']=$data['communityId'];//添加小区后返回的小区ID
        $data_add['buildNum']=$data['buildNum'];//楼栋号，数字格式
        $data_add['unitNum']=$data['unitNum'];//单元号，数字格式
        $data_add['roomNum']=$data['roomNum'];//房间号，数字格式
        $data_add['houseFloor']=$data['houseFloor'];//楼层号，数字格式
        $data_add['roomArea']=$data['roomArea'];//房屋面积，数字格式

        $res = http_request($url, 'POST', json_encode($data_add), $headers);
        return  $res;
    }


    /**
     * 查询房屋列表
     * @author:zhubaodi
     * @date_time: 2021/11/25 13:15
     */
    public function buildingInfos($data){
        $url=$this->url.'out/v1/house/buildingInfos';
        $headers = [
            "token:".$data['token'],
            "Content-Type:application/json",
        ];
        $data_add=[];
        if (isset($data['buildNum'])){
            $data_add['buildNum']=$data['buildNum'];
        }
        if (isset($data['unitNum'])){
            $data_add['unitNum']=$data['unitNum'];
        }
        if (isset($data['houseFloor'])){
            $data_add['houseFloor']=$data['houseFloor'];
        }
        if (isset($data['roomNum'])){
            $data_add['roomNum']=$data['roomNum'];
        }

        $data_add['communityId']=$data['communityId'];
        $res = http_request($url, 'POST', json_encode($data_add), $headers);
        return  $res;
    }


    /**
     * 添加住户
     * @author:zhubaodi
     * @date_time: 2021/11/25 11:36
     */
    public function addPersonInfo($data){
        $url=$this->url.'out/v1/person/addPersonInfo';
        $headers = [
            "token:".$data['token'],
            "Content-Type:application/json",
        ];
        $data_add=[];
        $data_add['identityCard']=$data['identityCard'];//身份证号唯一
        $data_add['personName']=$data['personName'];//住户姓名
        $data_add['sex']=$data['sex'];//性别，0女，1男
        $data_add['imgBase64']=$data['imgBase64'];//头像(大于50kb小于3M)，传base64图片流
        $data_add['mobile']=$data['mobile'];//手机号
        $data_add['buildingId']=$data['buildingId'];//房屋ID(添加房屋后返回的id)

        $res = http_request($url, 'POST', json_encode($data_add), $headers);
        return  $res;
    }


    /**
     * 编辑住户
     * @author:zhubaodi
     * @date_time: 2021/11/25 11:36
     */
    public function updatePersonInfo($data){
        $url=$this->url.'out/v1/person/updatePersonInfo';
        $headers = [
            "token:".$data['token'],
            "Content-Type:application/json",
        ];
        $data_add=[];
        $data_add['identityCard']=$data['identityCard'];//身份证号唯一
        $data_add['personName']=$data['personName'];//住户姓名
        $data_add['sex']=$data['sex'];//性别，0女，1男
        $data_add['imgBase64']=$data['imgBase64'];//头像(大于50kb小于3M)，传base64图片流
        $data_add['mobile']=$data['mobile'];//手机号
        $data_add['buildingId']=$data['buildingId'];//房屋ID(添加房屋后返回的id)
        $data_add['bindId']=$data['bindId'];//房屋绑定关系ID
        $data_add['personInfoId']=$data['personInfoId'];//房屋绑定关系ID

        $res = http_request($url, 'POST', json_encode($data_add), $headers);
        return  $res;
    }

    /**
     * 查询住户列表
     * @author:zhubaodi
     * @date_time: 2021/11/25 13:15
     */
    public function householderInfoPage($data){
        $url=$this->url.'out/v1/person/householderInfoPage';
        $headers = [
            "token:".$data['token'],
        ];
        $data_add=[];
        if (isset($data['personName'])){
            $data_add['personName']=$data['personName'];//住户姓名
        }
        if (isset($data['idNumber'])){
            $data_add['idNumber']=$data['idNumber'];//身份证号
        }

        $data_add['communityId']=$data['communityId'];//小区ID（返回的小区id）
        $data_add['pageNum']=$data['pageNum'];
        $data_add['pageSize']=$data['pageSize'];
        $res = http_request($url, 'POST', http_build_query($data_add), $headers);
        return  $res;
    }


    /**
     * 查询社区详情
     * @author:zhubaodi
     * @date_time: 2021/11/25 13:21
     */
    public function findCommunityInfoByForm($data){
        $url=$this->url.'out/v1/community/findCommunityInfoByForm';
        $headers = [
            "token:".$data['token'],
        ];
        $data_add=[];
        $data_add['communityId']=$data['communityId'];
        $res = http_request($url, 'POST', http_build_query($data_add), $headers);
        return  $res;
    }

    /**
     * 住户ID查询住户信息
     * @author:zhubaodi
     * @date_time: 2021/11/25 13:24
     */
    public function getPersonInfo($data){
        $url=$this->url.'out/v1/person/getPersonInfo';
        $headers = [
            "token:".$data['token'],
        ];
        $data_add=[];
        $data_add['communityId']=$data['communityId'];
        $data_add['identity']=$data['identity'];
        $res = http_request($url, 'POST', http_build_query($data_add), $headers);
        return  $res;
    }


}