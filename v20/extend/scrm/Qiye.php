<?php

namespace scrm;

use app\common\model\service\ConfigService;
use net\Http;

/**
 * 对接SCRM系统企业微信相关接口
 * @package scrm
 */
class Qiye
{
    public $gateway = 'https://tapi.fastwhale.com.cn';

    const REGISTER = '/api/login/register';

    const GET_LOGIN_URL = '/api/login/get-login-url';

    const GET_ACCESS_TOKEN = '/api/login/get-access-token';

    const AGENT_ADD = '/api/work-agent/add';

    const AGENT_LIST = '/api/work-agent/list';

    const AGENT_GET = '/api/work-agent/get';

    const CROP_LIST = '/api/work-corp/list';

    const GET_ALL_DEPARTMENT_USER = '/api/work-party/get-all-department-user';

    const WORK_CONTACT_WAY_ADD = '/api/work-contact-way/add';

    const WORK_CONTACT_WAY_DEL = '/api/work-contact-way/delete';

    const REFRESH_PARTY_LIST = '/api/work-party/refresh-party-list';

    const GET_ALL_USER = '/api/work-user/get-all-user';

    const GET_QRCODE = '/api/work-contact-way/get-qrcode';

    const ACCTACHMENT_ADD = '/api/attachment/add';

    const UPLOAD_MEDIA = '/api/work-material/upload-media';

    public $header = [];

    public function __construct()
    {
        $scrmDomain = (new ConfigService())->getOneField('crm_domain');
        $scrmDomain && $this->gateway = $scrmDomain;
    }

    public function setHeader($token)
    {
        $this->header = ['Authorization:Bearer ' . $token];
        return $this;
    }


    public function register($param = [])
    {
        $rs = Http::curlPost($this->gateway . self::REGISTER, $param);
        return $rs;
    }

    public function getAccessToken($param)
    {
        $rs = Http::curlPost($this->gateway . self::GET_ACCESS_TOKEN, $param);
        return $rs;
    }

    public function getLoginUrl($param)
    {
        $rs = QiyeHttp::get($this->gateway . self::GET_LOGIN_URL . '?' . http_build_query($param));
        return is_array($rs) ? $rs : json_decode($rs, true);
    }

    public function cropList($param)
    {
        $rs = QiyeHttp::post($this->gateway . self::CROP_LIST, $param, $this->header);
        return $rs;
    }

    public function agentAdd($param)
    {
        $rs = QiyeHttp::post($this->gateway . self::AGENT_ADD, $param, $this->header);
        return $rs;
    }

    public function agentList($param)
    {
        $rs = QiyeHttp::post($this->gateway . self::AGENT_LIST, $param, $this->header);
        return $rs;
    }

    public function agentGet($param)
    {
        $rs = QiyeHttp::post($this->gateway . self::AGENT_GET, $param, $this->header);
        return $rs;
    }

    public function getAllDepartmentUser($param)
    {
        $rs = QiyeHttp::post($this->gateway . self::GET_ALL_DEPARTMENT_USER, $param, $this->header);
        return $rs;
    }

    public function workContactWayAdd($param)
    {
        $rs = QiyeHttp::post($this->gateway . self::WORK_CONTACT_WAY_ADD, $param, $this->header);
        return $rs;
    }

    public function workContactWayDel($param)
    {
        $rs = QiyeHttp::post($this->gateway . self::WORK_CONTACT_WAY_DEL, $param, $this->header);
        return $rs;
    }

    public function refreshPartyList($param)
    {
        $rs = QiyeHttp::post($this->gateway . self::REFRESH_PARTY_LIST, $param, $this->header);
        return $rs;
    }

    public function getAllUser($param)
    {
        $rs = QiyeHttp::post($this->gateway . self::GET_ALL_USER, $param, $this->header);
        return $rs;
    }

    public function getQrcode($param)
    {
        $rs = QiyeHttp::post($this->gateway . self::GET_QRCODE, $param, $this->header);
        return $rs;
    }

    public function attachmentAdd($param, $file)
    {
        $rs = QiyeHttp::uploadFile($this->gateway . self::ACCTACHMENT_ADD, $file, $param, $this->header);
        return $rs;
    }

    public function uploadMedia($param)
    {
        $rs = QiyeHttp::post($this->gateway . self::UPLOAD_MEDIA, $param, $this->header);
        return $rs;
    }


}

class QiyeHttp
{
    public static function get($url)
    {
        $rs = Http::curlGet($url);
        return is_array($rs) ? $rs : json_decode($rs, true);
    }

    public static function post($url, $params, $header)
    {
        $rs = Http::curlPostOwnWithHeader($url, $params, $header);
        return is_array($rs) ? $rs : json_decode($rs, true);
    }

    public static function uploadFile($url, $file, $data, $header)
    {
        $rs = Http::curlUploadFile($url, $file, true, $header, $data);
        return is_array($rs) ? $rs : json_decode($rs, true);
    }
}