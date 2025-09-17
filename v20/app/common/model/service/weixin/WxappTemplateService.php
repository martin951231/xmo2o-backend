<?php

/**
 * 微信模板消息
 */

namespace app\common\model\service\weixin;
use app\common\model\db\WxappTemplate;
use app\common\model\service\UserService;
use net\Http;
class WxappTemplateService
{

    public $wxappTemplateModel = null;
    public function __construct()
    {
        $this->wxappTemplateModel = new WxappTemplate();
    }

    /**
     * 发送小程序订阅消息
     * @param $tempKey 模板ID
     * @param array $dataArr 参数
     * @return bool
     * @author 衡婷妹
     * @date 2020/10/27
     */
    public function sendTempMsg($tempKey, $dataArr = [])
    {
        $where = [
            'template_key' => $tempKey,
            'status' => 1
        ];
        $template = $this->getOne($where);

        if(empty($template)){
            return false;
        }

        // 请求地址
        $requestUrl = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=';

        // 请求参数
        $data = $this->getData($dataArr);
        $sendData = '{"touser":"' . $dataArr["wecha_id"]  . '","template_id":"' . $tempKey . '","page":"' . $dataArr["page"] . '",';

        $sendData.= '"data":' . $data . '}';

        // 获取 access_token
        $access_token_array = (new AccessTokenWxappExpiresService())->getAccessToken();
        if ($access_token_array['errcode']) {
            return false;
        }
        $access_token = $access_token_array['access_token'];

        // 拼接 access_token
        $requestUrl .=  $access_token;

        $resultArr = $this->curlPost($requestUrl,$sendData,'', 'json');
        if ($resultArr['errcode'] > 0){
            fdump_sql([$sendData, $resultArr], 'sendWeixinTempMsgFail');
           return false;
        }

        return true;
    }


    // Get Data.data
    public function getData($dataArr)
    {

        unset($dataArr['wecha_id'], $dataArr['page']);
        $jsonData = '';
        foreach ($dataArr as $k => $v) {
            $jsonData .= '"' . $k . '":{"value":"' . $v . '"},';
        }

        $jsonData = rtrim($jsonData, ',');

        return "{" . $jsonData . "}";
    }

    /**
     * 获得某个业务的可用的模板信息
     * @param $param array
     * @return array
     */
    public function getNormalWxappTemplate($param){
        $where = [];
        if($param['template_id']){
            if(!is_array($param['template_id'])){
                $temids = explode(',', $param['template_id']);
            } else {
                $temids = $param['template_id'];
            }
            $where[] = ['template_id', 'in', $temids];
        }
        $result = $this->getSome($where);

        $list = [];
        foreach ($result as &$value){
            if(!$value['template_key']){
                continue;
            }
            $list[] = $value;
        }
        $returnArr = [
            'list' => $list,
        ];
        return $returnArr;
    }


    /**
     * 获得微信小程序订阅消息模板列表
     * @param $where array
     * @return array
     */
    public function getWxappTemplateList($param){
        $page = $param['page'] ?? 0;
        $page = $page ? $page - 1 : 0;
        $limit = 0;

        $result = $this->getSome([], '', '', $page, $limit);
        $count = $this->wxappTemplateModel->getCount();
        foreach ($result as &$value){
            $value['image'] = cfg('site_url').$value['image'];
        }


        $returnArr = [
            'list' => $result,
            'total' => $count
        ];
        return $returnArr;
    }

    /**
     * 获得微信小程序订阅消息模板列表
     * @param $where array
     * @return array
     */
    public function editWxappTemplate($param){
        $id = $param['id'] ?? 0;
        $nowData = $this->getOne(['id'=>$id]);
        if(empty($nowData)){
            throw new \think\Exception(L_("模板不存在"), 1003);
        }

        $data = [];
        $status = $param['status'] ?? 0;
        $data['status'] = $status;
        $where = [
            'id' => $id
        ];
        $res = $this->updateThis($where, $data);
        if($res === false){
            throw new \think\Exception(L_("修改失败，请重试"), 1003);
        }

        $returnArr['msg'] = '修改成功';;
        return $returnArr;
    }

    /**
     * 获取模板id
     * @param $where array
     * @return array
     */
    public function addTemplate($param){
        $id = $param['id'] ?? 0;
        $nowData = $this->getOne(['id'=>$id]);
        if(empty($nowData)){
            throw new \think\Exception(L_("模板不存在"), 1003);
        }

        $accessTokenArray = (new AccessTokenWxappExpiresService())->getAccessToken();
        if ($accessTokenArray['errcode']) {
            throw new \think\Exception('获取access_token发生错误：错误代码' . $accessTokenArray['errcode'] .',微信返回错误信息：' . $accessTokenArray['errmsg']);
        }
        $accessToken = $accessTokenArray['access_token'];

        $httpUrl  = 'https://api.weixin.qq.com/wxaapi/newtmpl/addtemplate?access_token='.$accessToken;

        // 请求参数
        // 模板标题 id，可通过接口获取，也可登录小程序后台查看获取
        $postData['tid'] = $nowData['template_id'];

        // 开发者自行组合好的模板关键词列表，关键词顺序可以自由搭配（例如 [3,5,4] 或 [4,5,3]），最多支持5个，最少2个关键词组合
        $postData['kidList'] = explode(',',$nowData['keywords']);
        $postData['kidList'] = array_map('intval', $postData['kidList']);

        // 服务场景描述，15个字以内
        $postData['sceneDesc'] = '用于取餐通知';

        $resultArr = $this->curlPost($httpUrl,json_encode($postData),'','json');
        if (!$resultArr['errcode']){
            $condition['id'] = $id;
            $data['template_key'] = $resultArr['priTmplId'];
            if(!$this->updateThis($condition,$data)){
                throw new \think\Exception(L_('获取失败,请重试。'));
            }
        }else{
            throw new \think\Exception(L_('发生错误：错误代码 ').$resultArr['errcode'].L_('，微信返回错误信息：').$resultArr['errmsg']);
        }
        $returnArr['msg'] = '获取成功';
        return $returnArr;
    }


    static public function curlPost($url,$data,$timeout=15, $type = null){
        $ch = curl_init();
        $headers[] = "Accept-Charset: utf-8";//"Content-Type: multipart/form-data; boundary=" .  uniqid('------------------');
        if($type == 'json'){
            $headers[] = "Content-type: application/json;charset=utf-8";
        }
//      $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.80 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch ,CURLOPT_TIMEOUT ,$timeout);
        $result = curl_exec($ch);
        
        //关闭curl
        curl_close($ch);
        // echo $result;exit;
        $result = json_decode($result, true);
        if (empty($result)) {
            return array('errcode' => 1, 'errmsg' => '请求失败');
        } else {
            return $result;
        }
    }

    /**
     * 添加一条记录
     * @param $data array 数据
     * @return array
     */
    public function add($data){
        $id = $this->wxappTemplateModel->insertGetId($data);
        if(!$id) {
            return false;
        }

        return $id;
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
            $result = $this->wxappTemplateModel->updateThis($where, $data);
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

        $result = $this->wxappTemplateModel->getOne($where, $order);
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
    public function getSome($where = [], $field = true,$order=true,$page=0,$limit=0){
        $result = $this->wxappTemplateModel->getSome($where, $field, $order, $page, $limit);
//        var_dump($this->wxappTemplateModel->getLastSql());
        return $result->toArray();
    }
}
