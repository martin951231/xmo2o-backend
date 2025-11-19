<?php

/**
 * 友电物联电瓶车充电桩（好快充）智能充电桩云平台
 * http://out.haokuaichong.cn/quick50/login 管理后台
 * 文档地址 http://xiaoyaoji.cn/project/1iyLstx4yDw/1iyLtkS1Elk?st=1jIiDub1o6y&sid=1jIiDub1o6y
 * 接口默认执行QPS（10个/每秒）限流策略，部分特殊接口只允许一段时间内访问（如：获取token、申请运营商 ，这2个接口只允许10s内调用一次。
 * 如没有特殊说明的，一般都为执行10次/每秒 的默认限流策略）
 */
class PileHaoKuaiChong
{

    public $apiServiceUrl = 'http://out.haokuaichong.cn/api'; //正式地址
    public $haoKuaiUserNo = ''; //账号
    public $haoKuaiPassword = ''; //密码
    public $haoKuaiToken = ''; //token
    public $haoKuaiApplyPhone = ''; //申请运营商账户申请人手机号
    public $haoKuaiApplyName = ''; //申请运营商账户申请人名称
    public $haoKuaiApplyAddress = ''; //申请运营商账户申请人联系地址
    public $openId = ''; //运营商唯一身份标识
    public $userId = ''; //运营商的用户id，全局唯一
    public $projectId = ''; //小区id
    public $village_id = 0;   //系统小区id
    public $retInfoArr = array('err_code' => 0, 'err_msg' => '', 'err_func' => '', 'err_type' => '', 'data' => array());
    public $stopReason = array(
        '00' => '未收到设备上传的结算指令，系统结算',
        '01' => '充满自停',
        '02' => '达到最大充电时长',
        '03' => '达到预设时间',
        '04' => '达到预设电量',
        '05' => '用户拔出',
        '06' => '负载过大',
        '07' => '服务器控制停止',
        '08' => '动态过载，功率起伏过大',
        '09' => '功率过小，充电器未连接好',
        '0A' => '环境温度过高',
        '0B' => '端口温度过高',
        '0C' => '过流',
        '0D' => '用户拔出-1',
        '0E' => '无功率',
        '0F' => '预检-继电器坏或保险丝断',
        '10' => '水浸断电',
        '11' => '灭火结算',
        '12' => '灭火结算-1【非本端口】',
        '13' => '密码开柜',
        '14' => '未关好柜门',
        '15' => '外部操作打开柜门',
        '16' => '刷卡操作打开柜门',
        '17' => '服务器强制停止',
    );

    /**
     * 页面编码
     * Demo constructor.
     * Date: 2019-04-16
     * Time: 下午 5:34
     */
    public function __construct($village_id = 0)
    {
        $this->village_id = $village_id;
        $this->retInfoArr = array('err_code' => 0, 'err_msg' => '', 'err_func' => '', 'err_type' => '', 'data' => array());
        $this->haoKuaiUserNo = C('config.hao_kuai_user_no');
        $this->haoKuaiPassword = C('config.hao_kuai_password');
        $this->haoKuaiApplyPhone = C('config.hao_kuai_apply_phone');
        $this->haoKuaiApplyName = C('config.hao_kuai_apply_name');

        $this->checkToken(); //检查token过期
        $this->checkUserId(); //检查是否成功申请运营商账户
    }

    public function checkToken()
    {
        $where = array();
        $where['name'] = 'hao_kuai_token';
        $nowtime = time();
        $expire_check_time = $nowtime + 1200;  //提前20分钟
        $tmpConfig = M('Config')->where($where)->find();
        if (empty($tmpConfig)) {
            $this->retInfoArr['err_func'] = 'checkToken';
            $this->retInfoArr['err_type'] = 'get_token';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '对接token未配置';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken,), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        //token 有效期为2小时
        $get_token_time = $tmpConfig['value_traditional'];
        $get_token_time = $get_token_time ? $get_token_time * 1 : 0;
        $expire_time = $get_token_time + 2 * 3600;
        if ($tmpConfig && empty($tmpConfig['value'])) {
            $this->getToken();
        } elseif ($get_token_time > 0 && $expire_time < $expire_check_time) {
            $this->getToken($tmpConfig['value']);
        } else {
            $this->haoKuaiToken = trim($tmpConfig['value']);
        }
        return $this->haoKuaiToken;
    }

    public function checkUserId()
    {
        if ($this->retInfoArr['err_type'] == 'get_token' && $this->retInfoArr['err_code'] == 1) {
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'checkUserId';
            $this->retInfoArr['err_type'] = 'get_UserId';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $hao_kuai_apply_result = C('config.hao_kuai_apply_result');
        if ($hao_kuai_apply_result) {
            $this->userId = trim($hao_kuai_apply_result);
        } else {
            $this->operatorApply();
        }
        return $this->userId;
    }

    public function getkUserId()
    {
        return $this->userId;
    }

    public function getStopReason($code = '')
    {
        if ($code) {
            if (isset($this->stopReason[$code])) {
                return $this->stopReason[$code];
            } else {
                $code = '0' . $code;
                $tmpV = isset($this->stopReason[$code]) ? $this->stopReason[$code] : '';
                return $tmpV;
            }
        } else {
            return $this->stopReason;
        }
    }

    public function operatorApply()
    {
        if (empty($this->haoKuaiApplyPhone) || empty($this->haoKuaiApplyName)) {
            $this->retInfoArr['err_func'] = 'operatorApply';
            $this->retInfoArr['err_type'] = 'apply_account';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '申请人名称或申请人手机号未填写！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $randomStr = createRandomStr(10);
        $this->openId = 'VPile_Y' . date('Y') . 'M' . date('m') . 'D' . date('d') . 'Hi' . date('Hi') . $randomStr;
        $postData = [
            'name' => $this->haoKuaiApplyName,
            'openId' => $this->openId,
            'phone' => $this->haoKuaiApplyPhone,
            'address' => $this->haoKuaiApplyAddress,
        ];
        $this->retInfoArr['data'] = array();
        $result = $this->curl_request('/operator/apply', $postData, 'POST');
        if ($result && isset($result['code'])) {
            if (isset($result['data']) && !empty($result['data']) && isset($result['data']['userId'])) {
                $this->userId = $result['data']['userId'];
                $where = array();
                $where['name'] = 'hao_kuai_apply_result';
                $result['data']['openId'] = $this->openId;
                $value_english = json_encode($result['data'], JSON_UNESCAPED_UNICODE);
                $saveArr = array('value' => $this->userId, 'value_english' => $value_english);
                M('Config')->where($where)->data($saveArr)->save();
                $this->retInfoArr['err_func'] = 'operatorApply';
                $this->retInfoArr['err_type'] = 'apply_account';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '数据获取成功！';
                $this->retInfoArr['data'] = $result['data'];
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '申请运营商账户失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'operatorApply';
            $this->retInfoArr['err_type'] = 'apply_account';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'operatorApply';
            $this->retInfoArr['err_type'] = 'apply_account';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '申请运营商账户接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    /**
     * 获取token
     * @return mixed
     * Date: 2019-04-16
     * Time: 下午 4:59
     */
    public function getToken($old_token = '')
    {
        if (empty($this->haoKuaiUserNo) || empty($this->haoKuaiPassword)) {
            $this->retInfoArr['err_func'] = 'getToken';
            $this->retInfoArr['err_type'] = 'get_token';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '账号密码未配置完全！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $postData = [
            'userNo' => $this->haoKuaiUserNo,
            'password' => $this->haoKuaiPassword,
        ];
        $this->retInfoArr['data'] = array();
        $result = $this->curl_request('/login', $postData, 'POST', 'application/x-www-form-urlencoded', true);
        if ($result && isset($result['code'])) {
            if (isset($result['data']) && !empty($result['data']) && isset($result['data']['token'])) {
                $this->haoKuaiToken = trim($result['data']['token']);
                $where = array();
                $where['name'] = 'hao_kuai_token';
                $saveArr = array('value' => $this->haoKuaiToken, 'value_traditional' => time());
                if ($old_token) {
                    $saveArr['value_english'] = $old_token;
                }
                M('Config')->where($where)->data($saveArr)->save();
                $this->retInfoArr['err_func'] = 'getToken';
                $this->retInfoArr['err_type'] = 'get_token';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '数据获取成功！';
                $this->retInfoArr['data']['token'] = $this->haoKuaiToken;
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '获取token失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'getToken';
            $this->retInfoArr['err_type'] = 'get_token';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'getToken';
            $this->retInfoArr['err_type'] = 'get_token';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '获取token接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function createProjectVillage($postData = array(), $id = 0)
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'createProjectVillage';
            $this->retInfoArr['err_type'] = 'createProjectVillage';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        if (empty($this->userId)) {
            $this->retInfoArr['err_func'] = 'createProjectVillage';
            $this->retInfoArr['err_type'] = 'createProjectVillage';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '申请运营商账户失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $this->retInfoArr['data'] = array();
        $postData['userId'] = $this->userId;
        $result = $this->curl_request('/project', $postData, 'POST');
        if ($result && isset($result['code'])) {
            if (isset($result['data']) && !empty($result['data'])) {
                $this->projectId = trim($result['data']);
                if ($id > 0) {
                    $saveArr = array('project_id' => $this->projectId, 'user_id' => $this->userId);
                    M('House_village_haokuaichong')->where(array('id' => $id))->data($saveArr)->save();
                }
                $this->retInfoArr['err_func'] = 'createProjectVillage';
                $this->retInfoArr['err_type'] = 'createProjectVillage';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '创建小区成功！';
                $this->retInfoArr['data']['project_id'] = $this->projectId;
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '创建小区失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'createProjectVillage';
            $this->retInfoArr['err_type'] = 'createProjectVillage';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'createProjectVillage';
            $this->retInfoArr['err_type'] = 'createProjectVillage';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '创建小区接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function editProjectVillage($postData = array(), $project_id = 0)
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'editProjectVillage';
            $this->retInfoArr['err_type'] = 'editProjectVillage';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $this->projectId = $project_id;
        $this->retInfoArr['data'] = array();
        $result = $this->curl_request('/project', $postData, 'POST');
        if ($result && isset($result['code'])) {
            if ($result['code'] == 200 || $result['code'] == '200') {
                $this->retInfoArr['err_func'] = 'editProjectVillage';
                $this->retInfoArr['err_type'] = 'editProjectVillage';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '修改小区信息成功！';
                $this->retInfoArr['data']['project_id'] = $this->projectId;
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '修改小区信息失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'editProjectVillage';
            $this->retInfoArr['err_type'] = 'editProjectVillage';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'editProjectVillage';
            $this->retInfoArr['err_type'] = 'editProjectVillage';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '修改小区信息接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function getProjectInfo($project_id = 0, $id = 0)
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'getProjectInfo';
            $this->retInfoArr['err_type'] = 'getProjectInfo';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $this->projectId = $project_id;
        $this->retInfoArr['data'] = array();
        $result = $this->curl_request('/project/' . $project_id, '', 'GET');
        if ($result && isset($result['code'])) {
            if (isset($result['data']) && !empty($result['data'])) {
                $whereArr = array('project_id' => $project_id);
                if ($id > 0) {
                    $whereArr['id'] = $id;
                }
                if ($this->village_id) {
                    $whereArr['village_id'] = $this->village_id;
                }
                $village_haokuaichongDb = M('House_village_haokuaichong');
                $village_haokuaichong = $village_haokuaichongDb->where($whereArr)->order('id desc')->find();
                $saveArr = array();
                $saveArr['project_name'] = $result['data']['projectName'];
                $saveArr['phone_name'] = $result['data']['phoneName'];
                $saveArr['phone'] = $result['data']['phone'];
                $saveArr['abcode'] = $result['data']['code'];
                $saveArr['address'] = $result['data']['address'];
                $saveArr['project_status'] = $result['data']['status'];
                $saveArr['extra_data'] = json_encode($result['data'], JSON_UNESCAPED_UNICODE);
                $saveArr['update_time'] = time();
                if (isset($result['data']['userId']) && $result['data']['userId']) {
                    $saveArr['user_id'] = $result['data']['userId'];
                }
                if (isset($result['data']['appid']) && $result['data']['appid']) {
                    $saveArr['appid'] = $result['data']['appid'];
                }
                if ($village_haokuaichong) {
                    $village_haokuaichongDb->where($whereArr)->data($saveArr)->save();
                } else {
                    $saveArr['project_id'] = $project_id;
                    $saveArr['village_id'] = $this->village_id;
                    $village_haokuaichongDb->data($saveArr)->add();
                }
                $this->retInfoArr['err_func'] = 'getProjectInfo';
                $this->retInfoArr['err_type'] = 'getProjectInfo';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '获取小区详情成功！';
                $this->retInfoArr['data'] = $result['data'];
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '获取小区详情失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'getProjectInfo';
            $this->retInfoArr['err_type'] = 'getProjectInfo';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'getProjectInfo';
            $this->retInfoArr['err_type'] = 'getProjectInfo';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '获取小区详情接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function getWorkUnit($code = '')
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'getProjectInfo';
            $this->retInfoArr['err_type'] = 'getProjectInfo';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $uri = '/workUnit';
        if ($code) {
            $uri = '/workUnit?code=' . $code;
        }
        $result = $this->curl_request($uri, '', 'GET');
        $this->retInfoArr['data'] = array();
        if ($result && isset($result['code'])) {
            if (isset($result['data']) && !empty($result['data'])) {
                $this->retInfoArr['err_func'] = 'getWorkUnit';
                $this->retInfoArr['err_type'] = 'getWorkUnit';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '获取省市区Code成功！';
                $this->retInfoArr['data'] = $result['data'];
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '获取省市区Code失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'getWorkUnit';
            $this->retInfoArr['err_type'] = 'getWorkUnit';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'getWorkUnit';
            $this->retInfoArr['err_type'] = 'getWorkUnit';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '获取省市区Code接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function addProjectRate($postData = array(), $project_id = 0, $idd = 0)
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'addProjectRate';
            $this->retInfoArr['err_type'] = 'addProjectRate';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $this->projectId = $project_id;
        $this->retInfoArr['data'] = array();
        $postDataJson = json_encode($postData, JSON_UNESCAPED_UNICODE);
        $result = $this->curl_request('/rate/' . $this->projectId, $postDataJson, 'POST', 'application/json');
        if ($result && isset($result['code'])) {
            if (isset($result['data']) && !empty($result['data'])) {
                $rate_id = trim($result['data']);
                if ($idd > 0) {
                    $saveArr = array('rate_id' => $rate_id, 'project_id' => $this->projectId);
                    M('House_village_haokuaichong_rate')->where(array('id' => $idd))->data($saveArr)->save();
                }
                $this->retInfoArr['err_func'] = 'addProjectRate';
                $this->retInfoArr['err_type'] = 'addProjectRate';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '添加费率套餐成功！';
                $this->retInfoArr['data']['rate_id'] = $rate_id;
                $this->retInfoArr['data']['project_id'] = $this->projectId;
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '添加费率套餐失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'addProjectRate';
            $this->retInfoArr['err_type'] = 'addProjectRate';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'addProjectRate';
            $this->retInfoArr['err_type'] = 'addProjectRate';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '添加费率套餐接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function editProjectRate($postData = array(), $rate_id = '')
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'editProjectRate';
            $this->retInfoArr['err_type'] = 'editProjectRate';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $this->retInfoArr['data'] = array();
        $postDataJson = json_encode($postData, JSON_UNESCAPED_UNICODE);
        $result = $this->curl_request('/rate/' . $rate_id, $postDataJson, 'PUT', 'application/json');
        if ($result && isset($result['code'])) {
            if ($result['code'] == 200 || $result['code'] == '200') {
                $this->retInfoArr['err_func'] = 'editProjectRate';
                $this->retInfoArr['err_type'] = 'editProjectRate';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '修改费率信息成功！';
                $this->retInfoArr['data']['rate_id'] = $rate_id;
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '修改费率套餐失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'editProjectRate';
            $this->retInfoArr['err_type'] = 'editProjectRate';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'editProjectRate';
            $this->retInfoArr['err_type'] = 'editProjectRate';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '修改费率套餐接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function delProjectRate($rate_id = '', $userId = '', $idd = 0)
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'delProjectRate';
            $this->retInfoArr['err_type'] = 'delProjectRate';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        if (empty($userId)) {
            $userId = $this->userId;
        }
        $postData = array('userId' => $userId);
        $this->retInfoArr['data'] = array();
        $result = $this->curl_request('/rate/' . $rate_id, $postData, 'DELETE');
        if ($result && isset($result['code'])) {
            if ($result['code'] == 200 || $result['code'] == '200') {
                $this->retInfoArr['err_func'] = 'delProjectRate';
                $this->retInfoArr['err_type'] = 'delProjectRate';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '删除费率信息成功！';
                $this->retInfoArr['data']['rate_id'] = $rate_id;
                if ($idd > 0) {
                    $whereArr = array('id' => $idd, 'village_id' => $this->village_id);
                    $nowtime = time();
                    M('House_village_haokuaichong_rate')->where($whereArr)->data(array('del_time' => $nowtime))->save();
                }
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '删除费率套餐失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'delProjectRate';
            $this->retInfoArr['err_type'] = 'delProjectRate';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'delProjectRate';
            $this->retInfoArr['err_type'] = 'delProjectRate';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '删除费率套餐接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }


    public function addProjectArea($postData = array(), $project_id = 0, $idd = 0)
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'addProjectArea';
            $this->retInfoArr['err_type'] = 'addProjectArea';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $this->projectId = $project_id;
        $this->retInfoArr['data'] = array();
        $result = $this->curl_request('/area/' . $this->projectId, $postData, 'POST');
        if ($result && isset($result['code'])) {
            if (isset($result['data']) && !empty($result['data'])) {
                $area_id = trim($result['data']);
                if ($idd > 0) {
                    $saveArr = array('area_id' => $area_id, 'project_id' => $this->projectId);
                    M('House_village_haokuaichong_region')->where(array('id' => $idd))->data($saveArr)->save();
                }
                $this->retInfoArr['err_func'] = 'addProjectArea';
                $this->retInfoArr['err_type'] = 'addProjectArea';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '添加充电区成功！';
                $this->retInfoArr['data']['area_id'] = $area_id;
                $this->retInfoArr['data']['project_id'] = $this->projectId;
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '添加充电区失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'addProjectArea';
            $this->retInfoArr['err_type'] = 'addProjectArea';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'addProjectArea';
            $this->retInfoArr['err_type'] = 'addProjectArea';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '添加充电区接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function editProjectArea($postData = array(), $area_id = '', $rateArr = array())
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'editProjectArea';
            $this->retInfoArr['err_type'] = 'editProjectArea';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $this->retInfoArr['data'] = array();
        $result = $this->curl_request('/area/' . $area_id, $postData, 'PUT');
        if ($result && isset($result['code'])) {
            if (!empty($rateArr)) {
                $retInfoArr = $this->editProjectAreaRate($rateArr, $area_id);
                if ($retInfoArr['err_code'] > 0) {
                    return $retInfoArr;
                }
            }
            if ($result['code'] == 200 || $result['code'] == '200') {
                $this->retInfoArr['err_func'] = 'editProjectArea';
                $this->retInfoArr['err_type'] = 'editProjectArea';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '修改充电区信息成功！';
                $this->retInfoArr['data']['area_id'] = $area_id;
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '修改充电区信息失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'editProjectArea';
            $this->retInfoArr['err_type'] = 'editProjectArea';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'editProjectArea';
            $this->retInfoArr['err_type'] = 'editProjectArea';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '修改充电区接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function editProjectAreaRate($postData = array(), $area_id = '')
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'editProjectAreaRate';
            $this->retInfoArr['err_type'] = 'editProjectAreaRate';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $this->retInfoArr['data'] = array();
        $result = $this->curl_request('/area/rate/' . $area_id, $postData, 'PUT');
        if ($result && isset($result['code'])) {
            if ($result['code'] == 200 || $result['code'] == '200') {
                $this->retInfoArr['err_func'] = 'editProjectAreaRate';
                $this->retInfoArr['err_type'] = 'editProjectAreaRate';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '修改充电区费率信息成功！';
                $this->retInfoArr['data']['area_id'] = $area_id;
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '修改充电区费率信息失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'editProjectAreaRate';
            $this->retInfoArr['err_type'] = 'editProjectAreaRate';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'editProjectAreaRate';
            $this->retInfoArr['err_type'] = 'editProjectAreaRate';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '修改充电区费率信息接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function getProjectOneArea($area_id = '', $idd = 0)
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'getProjectArea';
            $this->retInfoArr['err_type'] = 'getProjectArea';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $this->retInfoArr['data'] = array();
        $result = $this->curl_request('/area/' . $area_id, '', 'GET');
        if ($result && isset($result['code'])) {
            if (isset($result['data']) && !empty($result['data'])) {
                $this->retInfoArr['err_func'] = 'getProjectArea';
                $this->retInfoArr['err_type'] = 'getProjectArea';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '充电区绑定的费率信息成功！';
                $this->retInfoArr['data'] = $result['data'];
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '充电区绑定的费率信息失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'getProjectArea';
            $this->retInfoArr['err_type'] = 'getProjectArea';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'getProjectArea';
            $this->retInfoArr['err_type'] = 'getProjectArea';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '充电区绑定的费率信息接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function getProjectAreaList($project_id = 0)
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'getProjectAreaList';
            $this->retInfoArr['err_type'] = 'getProjectAreaList';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $nowtime = time();
        $this->projectId = $project_id;
        $this->retInfoArr['data'] = array();
        $uri = '/area/list/' . $project_id . '?pageNum=1&pageSize=120';
        $result = $this->curl_request($uri, '', 'GET');
        if ($result && isset($result['code'])) {
            if (isset($result['rows']) && !empty($result['rows'])) {
                $village_haokuaichong_regionDb = M('House_village_haokuaichong_region');
                foreach ($result['rows'] as $vv) {
                    $saveData = array('village_id' => $this->village_id, 'area_name' => $vv['areaName'], 'area_type' => $vv['areaType'], 'area_marks' => $vv['marks']);
                    $saveData['project_id'] = $project_id;
                    //$saveData['common_id']=$common_id;
                    $add_time = 0;
                    if (isset($vv['createTime']) && $vv['createTime']) {
                        $add_time = strtotime($vv['createTime']);
                    }
                    if ($add_time < 10) {
                        $add_time = $nowtime;
                    }
                    $area_id = trim($vv['id']);
                    $oneAreaRate = $this->getProjectOneArea($area_id);
                    if ($oneAreaRate && $oneAreaRate['data'] && $oneAreaRate['data']['oftenList'] && $oneAreaRate['data']['oftenList']) {
                        $saveData['common_id'] = $oneAreaRate['data']['oftenList']['0']['packageId'];
                    }
                    $whereArr = array('village_id' => $this->village_id, 'area_id' => $area_id);
                    $haokuaichong_region = $village_haokuaichong_regionDb->where($whereArr)->find();
                    if ($haokuaichong_region && isset($haokuaichong_region['id'])) {
                        $saveData['update_time'] = $nowtime;
                        $village_haokuaichong_regionDb->where(array('id' => $haokuaichong_region['id']))->data($saveData)->save();
                    } else {
                        $saveData['area_id'] = $area_id;
                        $saveData['add_time'] = $add_time;
                        $village_haokuaichong_regionDb->data($saveData)->add();
                    }
                }
                $this->retInfoArr['err_func'] = 'getProjectAreaList';
                $this->retInfoArr['err_type'] = 'getProjectAreaList';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '获取小区的充电区列表成功！';
                $this->retInfoArr['data'] = $result['rows'];
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '获取小区的充电区列表失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'getProjectAreaList';
            $this->retInfoArr['err_type'] = 'getProjectAreaList';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'getProjectAreaList';
            $this->retInfoArr['err_type'] = 'getProjectAreaList';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '获取小区的充电区列表接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function addProjectEquipment($postData = array(), $equipment_num = '', $idd = 0)
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'addProjectEquipment';
            $this->retInfoArr['err_type'] = 'addProjectEquipment';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }

        $this->retInfoArr['data'] = array();
        $result = $this->curl_request('/equipment', $postData, 'POST');
        if ($result && isset($result['code'])) {
            if (isset($result['data']) && !empty($result['data'])) {
                $equipment_serial = trim($result['data']);
                if ($idd > 0) {
                    $whereArr['id'] = $idd;
                    if ($this->village_id) {
                        $whereArr['village_id'] = $this->village_id;
                    }
                    $saveData = array('equipment_serial' => $equipment_serial);
                    M('House_village_pile_equipment')->where($whereArr)->data($saveData)->save();
                    $this->getEquipmentDetail($equipment_num, $idd);
                }
                $this->retInfoArr['err_func'] = 'addProjectEquipment';
                $this->retInfoArr['err_type'] = 'addProjectEquipment';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '添加设备成功！';
                $this->retInfoArr['data']['equipment_serial'] = $equipment_serial;
                $this->retInfoArr['data']['deviceId'] = $equipment_num;
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '添加设备失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'addProjectEquipment';
            $this->retInfoArr['err_type'] = 'addProjectEquipment';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'addProjectEquipment';
            $this->retInfoArr['err_type'] = 'addProjectEquipment';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '添加设备接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function getEquipmentDetail($equipment_num = '', $idd = 0)
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'getEquipmentDetail';
            $this->retInfoArr['err_type'] = 'getEquipmentDetail';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $village_pile_equipmentDb = M('House_village_pile_equipment');
        $this->retInfoArr['data'] = array();
        $result = $this->curl_request('/equipment/portsState?deviceId=' . $equipment_num, '', 'GET');
        if ($result && isset($result['code'])) {
            if($idd>0){
                $whereArr=array();
                $whereArr['id'] = $idd;
                if ($this->village_id) {
                    $whereArr['village_id'] = $this->village_id;
                }
                $saveArr = array();
                $saveArr['dev_update_time']=time();
                $village_pile_equipmentDb->where($whereArr)->data($saveArr)->save();
            }
            if (isset($result['data']) && !empty($result['data'])) {
                $detail = $result['data']['0'];
                if ($idd > 0) {
                    $saveArr = array();
                    $saveArr['equipment_serial'] = $detail['id'];
                    $saveArr['equipment_sn'] = $detail['code'];
                    if ($detail['state'] == '联网') {
                        $saveArr['status'] = 1;
                    } else {
                        $saveArr['status'] = 2;
                    }
                    $saveArr['socket_num'] = 0;
                    $ports = $detail['ports'];
                    if ($ports) {
                        $ports_info = explode(',', $ports);
                        if ($ports_info) {
                            $saveArr['ports_info'] = json_encode($ports_info, JSON_UNESCAPED_UNICODE);
                            $saveArr['socket_num'] = count($ports_info);
                        }
                    }
                    $portState = $detail['portState'];
                    $saveArr['port_status'] = '';
                    if ($portState) {
                        $port_status = explode(',', $portState);
                        $saveArr['port_status'] = $port_status ? json_encode($port_status, JSON_UNESCAPED_UNICODE) : '';
                    }
                    
                    if ($detail['time']) {
                        $last_heart_time = strtotime($detail['time']);
                        if ($last_heart_time > 0) {
                            $saveArr['last_heart_time'] = $last_heart_time;
                        }
                    }
                    $saveArr['extra_data'] = json_encode($detail, JSON_UNESCAPED_UNICODE);
                    $village_pile_equipmentDb->where($whereArr)->data($saveArr)->save();
                }
                $this->retInfoArr['err_func'] = 'getEquipmentDetail';
                $this->retInfoArr['err_type'] = 'getEquipmentDetail';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '查询设备端口信息成功！';
                $this->retInfoArr['data'] = $detail;
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '查询设备端口信息失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'getEquipmentDetail';
            $this->retInfoArr['err_type'] = 'getEquipmentDetail';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'getEquipmentDetail';
            $this->retInfoArr['err_type'] = 'getEquipmentDetail';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '查询设备端口信息接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    public function delProjectEquipment($equipment_num = '', $idd = 0, $equipment_serial = '')
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'delProjectEquipment';
            $this->retInfoArr['err_type'] = 'delProjectEquipment';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $postData = array('deviceId' => $equipment_num);
        $this->retInfoArr['data'] = array();
        $result = $this->curl_request('/equipment/deviceId', $postData, 'DELETE');
        if ($result && isset($result['code'])) {
            if ($idd > 0) {
                $whereArr = array('id' => $idd, 'village_id' => $this->village_id);
                $deltime = date('YmdHis');
                M('House_village_pile_equipment')->where($whereArr)->save(['is_del' => 2, 'equipment_num' => $equipment_num . '_del-' . $deltime]);
            }
            if ($result['code'] == 200 || $result['code'] == '200') {
                $this->retInfoArr['err_func'] = 'delProjectEquipment';
                $this->retInfoArr['err_type'] = 'delProjectEquipment';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '删除设备成功！';
                $this->retInfoArr['data']['equipment_num'] = $equipment_num;
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '删除设备失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'delProjectEquipment';
            $this->retInfoArr['err_type'] = 'delProjectEquipment';
            $this->retInfoArr['err_code'] = 0;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'delProjectEquipment';
            $this->retInfoArr['err_type'] = 'delProjectEquipment';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '删除设备接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    /**开始充电接口
     * 接口描述
     * 该接口仅下发上电指令，返回成功不代表设备实际上电成功！确认设备上电是否成功以<接收上电结果>接口推送结果为准。
     * 由于物联网网络原因，如未收到<接收上电结果>，应自行处理上电超时订单业务
     ***/
    public function startToCharge($order_id = 0, $pileOrder = array())
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'startTocharge';
            $this->retInfoArr['err_type'] = 'startTocharge';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        if ($order_id > 0 && empty($pileOrder)) {
            $whereOrderArr = array('id' => $order_id, 'village_id' => $this->village_id);
            $pileOrder = M('House_village_pile_pay_order')->where($whereOrderArr)->field('*')->find();
        }
        $extra_data = '';
        if (isset($pileOrder['extra_data']) && !empty($pileOrder['extra_data'])) {
            $extra_data = is_array($pileOrder['extra_data']) ? $pileOrder['extra_data'] : json_decode($pileOrder['extra_data'], true);
        }
        $postData = array();
        $equipment_num = '';
        if ($extra_data && isset($extra_data['equipment_num'])) {
            $equipment_num = $extra_data['equipment_num'];
        }
        $whereArr = array('id' => $pileOrder['equipment_id'], 'village_id' => $pileOrder['village_id']);
        $pile_equipment = M('House_village_pile_equipment')->where($whereArr)->field('*')->find();
        if ($pile_equipment && $pile_equipment['equipment_num']) {
            $equipment_num = $pile_equipment['equipment_num'];
        }
        $postData['deviceId'] = strtoupper($equipment_num);
        $port = '';
        if ($extra_data && isset($extra_data['equipment_port'])) {
            $port = $extra_data['equipment_port'];
        } elseif ($pileOrder['socket_no']) {
            $port = $pileOrder['socket_no'] - 1;
            $port = $port < 0 ? 0 : $port;
            if ($port < 10) {
                $port = '0' . $port;
            }
        }
        $postData['port'] = $port;
        $chargeType = '';
        if ($extra_data && isset($extra_data['haokuaichong_rate'])) {
            $haokuaichong_rate = $extra_data['haokuaichong_rate'];
            if ($haokuaichong_rate && $haokuaichong_rate['rate_type']) {
                $chargeType = $haokuaichong_rate['rate_type'];
            }
        }
        if (empty($chargeType) && $pile_equipment['area_id']) {
            $whereTemp = array('area_id' => $pile_equipment['area_id'], 'village_id' => $pileOrder['village_id']);
            $haokuaichong_region = M('House_village_haokuaichong_region')->where($whereTemp)->field('*')->find();
            if ($haokuaichong_region && $haokuaichong_region['common_id']) {
                $whereTemp = array('rate_id' => $haokuaichong_region['common_id'], 'village_id' => $pileOrder['village_id']);
                $haokuaichong_rate = M('House_village_haokuaichong_rate')->where($whereTemp)->field('*')->find();
                if ($haokuaichong_rate && $haokuaichong_rate['rate_type']) {
                    $chargeType = $haokuaichong_rate['rate_type'];
                }
            }
        }
        if (empty($chargeType) && $pileOrder['buy_ele'] > 0) {
            $chargeType = 'B2';  //计量
        } else if (empty($chargeType) && $pileOrder['buy_time'] > 0) {
            $chargeType = 'B1'; //计时
        }
        $postData['chargeType'] = $chargeType;
        $postData['evMode'] = '0';
        if ($chargeType == 'B2') {
            $postData['evMode'] = '1';
            $postData['time'] = $pileOrder['buy_ele']*100;  //单位是0.01度 100表示1度
        } else {
            $postData['time'] = $pileOrder['buy_time'];
        }
        $postData['isCard'] = 2; //1刷卡2扫码
        $postData['orderNo'] = $pileOrder['order_no']; //订单号 固定为长度32位纯数字字符串
        $postData['moneyOrTime'] = $pileOrder['use_money'];
        $this->retInfoArr['data'] = array();
        $result = $this->curl_request('/charge/start', $postData, 'POST');
        if ($result && isset($result['code'])) {
            if ($result['code'] == 200 || $result['code'] == '200') {
                $this->retInfoArr['err_func'] = 'startTocharge';
                $this->retInfoArr['err_type'] = 'startTocharge';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '设备开电成功！';
                $this->retInfoArr['data'] = $postData;
                $whereArr = array('id' => $pileOrder['id']);
                $charge_info = array('start_charge' => $result);
                $charge_info['start_charge']['start_charge_time_str'] = date('Y-m-d H:i:s');
                $charge_info['start_charge']['start_charge_time'] = time();
                $charge_info_str = json_encode($charge_info, JSON_UNESCAPED_UNICODE);
                $saveArr = array('charge_info' => $charge_info_str);
                M('House_village_pile_pay_order')->where($whereArr)->data($saveArr)->save();
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '设备开电失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'startTocharge';
            $this->retInfoArr['err_type'] = 'startTocharge';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'startTocharge';
            $this->retInfoArr['err_type'] = 'startTocharge';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '设备开电接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    /**结束充电接口
     * 接口描述
     * 该接口仅下发停止充电指令，返回成功不代表设备实际停止电成功！
     ***/
    public function stopToCharge($order_id = 0, $orderNo = '')
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'stopTocharge';
            $this->retInfoArr['err_type'] = 'stopTocharge';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $house_village_pile_pay_orderDb = M('House_village_pile_pay_order');
        $whereArr = array('village_id' => $this->village_id);
        if ($order_id > 0) {
            $whereArr['id'] = $order_id;
        } else if (!empty($orderNo)) {
            $whereArr['order_no'] = $orderNo;
        }
        $pileOrder = $house_village_pile_pay_orderDb->where($whereArr)->field('*')->find();
        if (empty($orderNo) && !empty($pileOrder)) {
            $orderNo = $pileOrder['order_no'];
        }
        $this->retInfoArr['data'] = array();
        $postData = array('orderNo' => $orderNo);
        $result = $this->curl_request('/charge/stop', $postData, 'POST');
        if ($result && isset($result['code'])) {
            if ($result['code'] == 200 || $result['code'] == '200') {
                $this->retInfoArr['err_func'] = 'stopTocharge';
                $this->retInfoArr['err_type'] = 'stopTocharge';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '结束充电成功！';
                $this->retInfoArr['data'] = $postData;
                $charge_info = '';
                if ($pileOrder && isset($pileOrder['charge_info']) && $pileOrder['charge_info']) {
                    $charge_info = json_decode($pileOrder['charge_info'], true);
                }
                $charge_info = $charge_info ? $charge_info : array();
                $whereArr = array('id' => $pileOrder['id']);
                $charge_info['end_charge'] = $result;
                $charge_info['end_charge']['end_charge_time_str'] = date('Y-m-d H:i:s');
                $charge_info['end_charge']['end_charge_time'] = time();
                $charge_info_str = json_encode($charge_info, JSON_UNESCAPED_UNICODE);
                $saveArr = array('charge_info' => $charge_info_str);
                M('House_village_pile_pay_order')->where($whereArr)->data($saveArr)->save();
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '结束充电失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'stopTocharge';
            $this->retInfoArr['err_type'] = 'stopTocharge';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'stopTocharge';
            $this->retInfoArr['err_type'] = 'stopTocharge';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '结束充电接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }

    }

    /**查询设备方订单那信息
     *
     ***/
    public function queryChargeOrderInfo($order_id = 0, $orderNo = '')
    {
        if (empty($this->haoKuaiToken)) {
            $this->retInfoArr['err_func'] = 'queryChargeOrderInfo';
            $this->retInfoArr['err_type'] = 'queryChargeOrderInfo';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = 'token获取失败';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
        $house_village_pile_pay_orderDb = M('House_village_pile_pay_order');
        $whereArr = array('village_id' => $this->village_id);
        if ($order_id > 0) {
            $whereArr['id'] = $order_id;
        } else if (!empty($orderNo)) {
            $whereArr['order_no'] = $orderNo;
        }
        $pileOrder = $house_village_pile_pay_orderDb->where($whereArr)->field('*')->find();
        if (empty($orderNo) && !empty($pileOrder)) {
            $orderNo = $pileOrder['order_no'];
        }
        $this->retInfoArr['data'] = array();
        $uri = '/consumRecord/info?orderNum=' . $orderNo;
        $result = $this->curl_request($uri, '', 'GET');
        if ($result && isset($result['code'])) {
            $updateData=array('last_query_time'=>time());
            $house_village_pile_pay_orderDb->where($whereArr)->data($updateData)->save();
            if (isset($result['rows']) && !empty($result['rows'])) {
                $queryData = $result['rows']['0'];
                $this->retInfoArr['err_func'] = 'queryChargeOrderInfo';
                $this->retInfoArr['err_type'] = 'queryChargeOrderInfo';
                $this->retInfoArr['err_code'] = 0;
                $this->retInfoArr['err_msg'] = '查询充电订单成功！';
                $this->retInfoArr['data'] = $queryData;
                $charge_info = '';
                if ($pileOrder && isset($pileOrder['charge_info']) && $pileOrder['charge_info']) {
                    $charge_info = json_decode($pileOrder['charge_info'], true);
                }
                $charge_info = $charge_info ? $charge_info : array();
                $whereArr = array('id' => $pileOrder['id']);
                $charge_info['order_info'] = $result;
                $charge_info_str = json_encode($charge_info, JSON_UNESCAPED_UNICODE);
                $saveArr = array('charge_info' => $charge_info_str);
                if(isset($queryData['startTime']) && !empty($queryData['startTime']) && $pileOrder['start_time']<100){
                    $saveArr['start_time']=strtotime($queryData['startTime']);
                }
                if(isset($queryData['endTime']) && !empty($queryData['endTime']) && $pileOrder['end_time']<100){
                    $saveArr['end_time']=strtotime($queryData['endTime']);
                }
                if(isset($queryData['timeScond']) && $queryData['timeScond']>0 && $pileOrder['continued_time']<100){
                    $saveArr['continued_time']=$queryData['timeScond'];
                }
                if(isset($queryData['chargeKwh']) && $queryData['chargeKwh']>0 && $pileOrder['use_ele']<=0){
                    $saveArr['use_ele']=$queryData['chargeKwh'];
                }
                if(isset($queryData['stopReason']) && !empty($queryData['stopReason']) && empty($pileOrder['end_result'])){
                    $saveArr['end_result']=$this->getStopReason($queryData['stopReason']);
                }
                if(isset($queryData['settlePower']) && $queryData['settlePower']>0 && ($pileOrder['end_power']<=0)){
                    $saveArr['end_power']= $queryData['settlePower'];
                }
                $realMoney=0;
                if(isset($queryData['realMoney']) && $queryData['realMoney']>0){
                    $realMoney= $queryData['realMoney'];
                }
                if(isset($queryData['isCharge']) && $queryData['isCharge']==1 && $pileOrder['status']<1){
                    $saveArr['status']=1;
                }
                M('House_village_pile_pay_order')->where($whereArr)->data($saveArr)->save();
                if(isset($queryData['isCharge']) && $queryData['isCharge']==1 && $pileOrder['status']<1){
                    //结算
                    $data=array('comefrom'=>'queryChargeOrder','money'=>$realMoney);
                    $data['stopReason']=$queryData['stopReason'];
                    $data['powerConsumption']=$queryData['chargeKwh'] ? $queryData['chargeKwh']:0;
                    $this->outSettlementRefund($order_id,$data);
                }
                fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
                return $this->retInfoArr;
            }
            $err_msg = '查询充电订单失败！错误码：' . $result['code'];
            if (isset($result['msg']) && $result['msg']) {
                $err_msg = $result['msg'] . '！错误码：' . $result['code'];
            }
            $this->retInfoArr['err_func'] = 'queryChargeOrderInfo';
            $this->retInfoArr['err_type'] = 'queryChargeOrderInfo';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = $err_msg;
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        } else {
            $this->retInfoArr['err_func'] = 'queryChargeOrderInfo';
            $this->retInfoArr['err_type'] = 'queryChargeOrderInfo';
            $this->retInfoArr['err_code'] = 1;
            $this->retInfoArr['err_msg'] = '查询充电订单接口返回错误！';
            fdump_api(array('retInfoArr' => $this->retInfoArr, 'haoKuaiToken' => $this->haoKuaiToken), 'haokuaichong/00PileHaoKuaiChong', 1);
            return $this->retInfoArr;
        }
    }

    /**
     **充电结束通知 退款处理
     *****/
    public function outSettlementRefund($order_id = 0, $data = array())
    {
        fdump_api(['order_id'=>$order_id,'data'=>$data],'haokuaichong/outSettlementRefund',1);
        $returnArr =array('error'=>1,'msg'=>'操作失败了！');
        $comefrom = !empty($data) && isset($data['comefrom']) ? $data['comefrom'] : '';
        $house_village_pile_pay_orderDb = M('House_village_pile_pay_order');
        $used_money = $data['money'] > 0 ? $data['money'] : 0; //充电设备算的用掉的钱
        $refund_money=isset($data['refund_money']) ? $data['refund_money']:0;  //手动操作退的钱
        $refund_money_opt=$refund_money;
        import('@.ORG.templateNews');
        $templateNewsService = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $tempKey = 'OPENTM400166399';
        $pileOrder = $house_village_pile_pay_orderDb->where(array('id' => $order_id))->field('*')->find();
        $extra_data = '';
        if (isset($pileOrder['extra_data']) && !empty($pileOrder['extra_data'])) {
            $extra_data = is_array($pileOrder['extra_data']) ? $pileOrder['extra_data'] : json_decode($pileOrder['extra_data'], true);
        }
        $chargeType = ''; // B2 计量 B1计时
        if ($extra_data && isset($extra_data['haokuaichong_rate'])) {
            $haokuaichong_rate = $extra_data['haokuaichong_rate'];
            if ($haokuaichong_rate && $haokuaichong_rate['rate_type']) {
                $chargeType = $haokuaichong_rate['rate_type'];
            }
        }
        fdump_api(['order_id'=>$order_id,'pileOrder'=>$pileOrder],'haokuaichong/outSettlementRefund',1);
        if(!empty($pileOrder) && $pileOrder['pay_type']==3 ){
            //月卡支付的
            $whereArr=array('uid'=>$pileOrder['uid'],'village_id'=>$pileOrder['village_id']);
            $month_card_user=M('House_village_pile_month_card_user')->where($whereArr)->order('id desc')->find();
            if($month_card_user && in_array($comefrom,array('opt_refund','notice_charge_fail','charge_fail'))){
                $surplus_num=$month_card_user['surplus_num']+1;
                M('House_village_pile_month_card_user')->where($whereArr)->data(array('surplus_num'=>$surplus_num))->save();
            }
            $res = $house_village_pile_pay_orderDb->where(array('id' => $pileOrder['id']))->data(array('status' => 1, 'is_refund' => 3))->save();
            $refundRet['error']=0;
            $refundRet['msg']='退单操作成功！';
            return $refundRet;
        }else if(!empty($pileOrder) && $comefrom=='opt_refund'){
            //手动退操作
            $userInfo = M('User')->where(array('uid' => $pileOrder['uid']))->field('*')->find();
            $href = get_base_url('pages/village/smartCharge/chargeDetails?order_id=' . $pileOrder['id'] . '&status=1');
            if($refund_money>0){
                $config_info= M('House_village_pile_config')->where(array('village_id' => $pileOrder['village_id']))->field('*')->find();
                $use_ele=$pileOrder['use_ele']>0 ? $pileOrder['use_ele']:0;
                $order_buy_ele =  $pileOrder['buy_ele'] > 0 ?  $pileOrder['buy_ele'] : 0;

                $order_buy_time = $pileOrder['buy_time'] > 0 ?  $pileOrder['buy_time'] : 0;
                $continued_time=$pileOrder['continued_time']>0 ? $pileOrder['continued_time']:0;
                $continued_time_m = ceil($continued_time / 60);  //分钟
                /*
                 * low_consume_time 最低消费时长
                 * des_time_level 扣费时间间隔档位
                 * low_consume_power 最低消费电量
                 * des_power_level 扣费电量间隔档位
                 *   
                 */
                if (!empty($config_info)) {
                    if ($chargeType == 'B2') {
                        if ($pileOrder['end_time'] < 10 || $comefrom == 'notice_charge_fail' || (!empty($config_info['low_consume_power']) && $config_info['low_consume_power'] > $use_ele) || (!empty($config_info['des_power_level']) && $config_info['des_power_level'] > $use_ele)) {
                            $refund_money = $pileOrder['use_money'];
                        } elseif (!empty($config_info['des_power_level']) && $config_info['des_power_level'] < $use_ele) {
                            $level = ceil($use_ele / $config_info['des_power_level']);
                            $des_power_level = $config_info['des_power_level'] * $level;
                            if ($des_power_level >= $use_ele && $order_buy_ele > 0) {
                                $refund_money = $pileOrder['use_money'] - ($pileOrder['use_money'] / $order_buy_ele) * $des_power_level;
                                $refund_money = $refund_money > 0 ? $refund_money : 0;
                            }
                        }
                    } elseif ($chargeType == 'B1') {
                        $order_buy_time_m = ceil($order_buy_time / 60);  //分钟
                        $start_time = $pileOrder['start_time'] >= $pileOrder['pay_time'] ? $pileOrder['start_time'] : $pileOrder['pay_time'];
                        $used_time = $pileOrder['end_time'] - $start_time;
                        $used_time = $used_time > 0 ? $used_time : 0;
                        $used_time_m = ceil($used_time / 60);
                        if ($pileOrder['end_time'] < 10 || (!empty($config_info['low_consume_time']) && $config_info['low_consume_time'] > $used_time_m) || (!empty($config_info['des_time_level']) && $config_info['des_time_level'] > $used_time_m)) {
                            $refund_money = $pileOrder['use_money'];
                        } elseif (!empty($config_info['des_time_level']) && $config_info['des_time_level'] <= $used_time_m && $order_buy_time_m > 0 && $order_buy_time_m >= $used_time_m) {
                            $level = ceil($used_time_m / $config_info['des_time_level']);
                            $des_time_level = $config_info['des_time_level'] * $level;
                            /*** 由于功率问题 算不准 先用设备根据费率算出来的
                            if ($des_time_level >= $used_time_m && $des_time_level <= $order_buy_time_m) {
                                $refund_money = $pileOrder['use_money'] - ($pileOrder['use_money'] / $order_buy_time_m) * $des_time_level;
                            }
                            ***/
                        }
                    }
                }
                fdump_api(['refund_money'=>$refund_money,'use_money'=>$pileOrder['use_money'],'refund_money_opt'=>$refund_money_opt],'haokuaichong/outSettlementRefund',1);
                $param = [
                    'param' => array('business_type' => 'pile', 'business_id' => $pileOrder['order_id'], 'pile_pay_order_id' => $pileOrder['id']),
                    'operation_info' => '',
                    'refund_money' => $refund_money
                ];
                $refundRet = D('Plat_order')->pile_order_refund($param['param'], '', $refund_money);
                if (!$refundRet['error']) {
                    $res = $house_village_pile_pay_orderDb->where(array('id' => $pileOrder['id']))->data(array('status'=>1,'is_refund' => 3))->save();
                    if ($res) {
                        $refund_data = [
                            'order_id' => $pileOrder['id'],
                            'status' => 3,
                            'refund_money' => $refund_money,
                            'refund_reason' => '手动退款',
                            'refund_time' => time(),
                        ];
                        $house_village_pile_refund_orderDb= M('House_village_pile_refund_order');
                        $pile_refund_order=$house_village_pile_refund_orderDb->where(array('order_id'=>$pileOrder['id'],'status'=>2))->field('*')->find();
                        if($pile_refund_order){
                            $house_village_pile_refund_orderDb->where(array('refund_id'=>$pile_refund_order['refund_id'],'order_id'=>$pileOrder['id']))->data($refund_data)->save();
                        }else{
                            $refund_data['add_time']=time();
                            $house_village_pile_refund_orderDb->add($refund_data);
                        }
                        
                    }
                    if($userInfo && !empty($userInfo['openid'])) {
                        $dataArr = [
                            'href' => $href,
                            'wecha_id' => $userInfo['openid'],
                            'first' => '您的充电桩账单退款￥' . $refund_money . '成功',
                            'keyword1' => '充电桩充电提醒',
                            'keyword2' => '已发送',
                            'keyword3' => date('H:i'),
                            'remark' => '请点击查看详细信息！'
                        ];
                        $templateNewsService->sendTempMsg($tempKey, $dataArr, 0, 0, 0);
                    }
                }
                return $refundRet;
            }
            $returnArr['msg']='退款失败了！';
        }else if (!empty($pileOrder) && $comefrom!='opt_refund') {
            $house_village_pile_equipmentDb = D('House_village_pile_equipment');
            $equipment_info = $house_village_pile_equipmentDb->where(array('id' => $pileOrder['equipment_id']))->field('*')->find();
            $userInfo = M('User')->where(array('uid' => $pileOrder['uid']))->field('*')->find();
            if ($userInfo &&  !empty($equipment_info)) {
                $href = get_base_url('pages/village/smartCharge/chargeDetails?order_id=' . $pileOrder['id'] . '&status=1');
                if ($pileOrder['use_money'] <= $used_money && $comefrom!='notice_charge_fail') {
                    if(!empty($userInfo['openid'])){
                        $dataArr = [
                            'href' => $href,
                            'wecha_id' => $userInfo['openid'],
                            'first' => '您设备所在的' . $equipment_info['equipment_name'] . $pileOrder['socket_no'] . '号插座口充电完成，欢迎再次使用，祝您生活愉快',
                            'keyword1' => '充电桩充电提醒',
                            'keyword2' => '已发送',
                            'keyword3' => date('H:i'),
                            'remark' => '请点击查看详细信息！'
                        ];
                        $templateNewsService->sendTempMsg($tempKey, $dataArr, 0, 0, 0);
                    }
                } else {
                    /*
                     * low_consume_time 最低消费时长
                     * des_time_level 扣费时间间隔档位
                     * low_consume_power 最低消费电量
                     * des_power_level 扣费电量间隔档位
                     *   
                     */
                    $refund_money = $pileOrder['use_money'] - $used_money;
                    
                    $config_info= M('House_village_pile_config')->where(array('village_id' => $pileOrder['village_id']))->field('*')->find();
                    $use_ele=$pileOrder['use_ele']>0 ? $pileOrder['use_ele']:0;
                    $order_buy_ele =  $pileOrder['buy_ele'] > 0 ?  $pileOrder['buy_ele'] : 0;
                    
                    $order_buy_time = $pileOrder['buy_time'] > 0 ?  $pileOrder['buy_time'] : 0;
                    $continued_time=$pileOrder['continued_time']>0 ? $pileOrder['continued_time']:0;
                    $continued_time_m = ceil($continued_time / 60);  //分钟
                    if (!empty($config_info)) {
                        if ($chargeType=='B2') {
                            if ($pileOrder['end_time']<10 || $comefrom=='notice_charge_fail' || (!empty($config_info['low_consume_power']) && $config_info['low_consume_power'] > $use_ele) || (!empty($config_info['des_power_level']) && $config_info['des_power_level'] > $use_ele)) {
                                $refund_money = $pileOrder['use_money'];
                            } elseif (!empty($config_info['des_power_level']) && $config_info['des_power_level'] < $use_ele) {
                                $level = ceil($use_ele / $config_info['des_power_level']);
                                $des_power_level = $config_info['des_power_level'] * $level;
                                if ($des_power_level >= $use_ele && $order_buy_ele>0) {
                                    $refund_money = $pileOrder['use_money'] - ($pileOrder['use_money'] / $order_buy_ele) * $des_power_level;
                                    $refund_money=$refund_money>0 ? $refund_money:0;
                                }
                            }
                        } elseif ($chargeType=='B1') {
                            $order_buy_time_m= ceil($order_buy_time / 60);  //分钟
                            $start_time=$pileOrder['start_time']>=$pileOrder['pay_time'] ?$pileOrder['start_time']:$pileOrder['pay_time'];
                            $used_time =$pileOrder['end_time']- $start_time;
                            $used_time=$used_time>0 ? $used_time:0;
                            $used_time_m = ceil($used_time/ 60);
                            if ($pileOrder['end_time']<10 ||(!empty($config_info['low_consume_time']) && $config_info['low_consume_time'] > $used_time_m) || (!empty($config_info['des_time_level']) && $config_info['des_time_level'] > $used_time_m)) {
                                $refund_money = $pileOrder['use_money'];
                            } elseif (!empty($config_info['des_time_level']) && $config_info['des_time_level'] <= $used_time_m && $order_buy_time_m>0 && $order_buy_time_m >= $used_time_m) {
                                $level = ceil($used_time_m / $config_info['des_time_level']);
                                $des_time_level = $config_info['des_time_level'] * $level;
                                /*** 由于功率问题 算不准 先用设备根据费率算出来的
                                if ($des_time_level >= $used_time_m && $des_time_level <= $order_buy_time_m) {
                                    $refund_money = $pileOrder['use_money'] - ($pileOrder['use_money'] / $order_buy_time_m) * $des_time_level;
                                }
                                 ***/
                            }
                        }
                        fdump_api(['refund_money'=>$refund_money,'use_money'=>$pileOrder['use_money'],'used_money'=>$used_money],'haokuaichong/outSettlementRefund',1);
                        if ($refund_money >= 0.01) {
                            $refund_money = round($refund_money, 2);
                            $param = [
                                'param' => array('business_type' => 'pile', 'business_id' => $pileOrder['order_id'], 'pile_pay_order_id' => $pileOrder['id']),
                                'operation_info' => '',
                                'refund_money' => $refund_money
                            ];
                            $refund_reason = '已自动退款';
                            if (isset($data['fail_msg']) && $data['fail_msg']) {
                                $refund_reason = $data['fail_msg'] . '，已自动退款';
                            }
                            $refundRet = D('Plat_order')->pile_order_refund($param['param'], '', $refund_money);
                            $returnArr = $refundRet;
                            if (!$refundRet['error']) {
                                $res = $house_village_pile_pay_orderDb->where(array('id' => $pileOrder['id']))->data(array('status' => 1, 'is_refund' => 3))->save();
                                if ($res) {
                                    $refund_data = [
                                        'order_id' => $pileOrder['id'],
                                        'status' => 3,
                                        'refund_money' => $refund_money,
                                        'refund_reason' => $refund_reason,
                                        'refund_time' => time(),
                                        'add_time' => time(),
                                    ];

                                    M('House_village_pile_refund_order')->add($refund_data);
                                }
                                if (!empty($userInfo['openid'])) {
                                    $dataArr = [
                                        'href' => $href,
                                        'wecha_id' => $userInfo['openid'],
                                        'first' => '您的充电桩账单退款￥' . $refund_money . '成功',
                                        'keyword1' => '充电桩充电提醒',
                                        'keyword2' => '已发送',
                                        'keyword3' => date('H:i'),
                                        'remark' => '请点击查看详细信息！'
                                    ];
                                    $templateNewsService->sendTempMsg($tempKey, $dataArr, 0, 0, 0);
                                }
                            }
                        }
                    }
                    if (!empty($userInfo['openid']) && $comefrom!='notice_charge_fail' && !in_array($data['stopReason'], ['00', 0, '01', '02', '03', '04'])) {
                        $dataArr = [
                            'href' => $href,
                            'wecha_id' => $userInfo['openid'],
                            'first' => '您设备所在的' . $equipment_info['equipment_name'] . $pileOrder['socket_no'] . '号插座口由于设备异常情况，停止充电',
                            'keyword1' => '充电桩充电提醒',
                            'keyword2' => '已发送',
                            'keyword3' => date('H:i'),

                        ];
                        $templateNewsService->sendTempMsg($tempKey, $dataArr, 0, 0, 0);
                    }
                }
            }
            return $returnArr;
        }
        return $returnArr;
    }

    /**
     * CURL请求数据
     * @param $url
     * @param $param
     *
     * @return mixed
     * Date: 2019-04-16
     * Time: 下午 4:34
     */
    public function curl_request($url, $param = '', $methd = 'POST', $content_type = 'application/x-www-form-urlencoded', $no_need_token = false)
    {
        $url = $this->apiServiceUrl . $url;
        $headers = array(
            'Content-Type:' . $content_type,
            'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        );
        if (!$no_need_token) {
            $headers[] = 'Authorization:Bearer ' . $this->haoKuaiToken;
        }
        //是否开启curl扩展
        if (!extension_loaded('curl')) {
            echo '对不起，请开启curl功能模块!';
            exit();
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($param)) {
            //curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $methd);
            $paramStr = '';
            if (is_array($param)) {
                $paramStr = http_build_query($param);
            } else {
                $paramStr = $param;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paramStr);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $methd);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        $result = curl_exec($ch);
        curl_close($ch);
        $jsonRes = array();
        if ($result) {
            $jsonRes = json_decode($result, true);
        }
        $jsonRes = $jsonRes ? $jsonRes : array();
        fdump_api(array('url' => $url, 'param' => $param, 'methd' => $methd, 'Authorization' => $this->haoKuaiToken, 'result' => $result, 'jsonRes' => $jsonRes), 'haokuaichong/00curl_request', 1);
        return $jsonRes;
    }
}