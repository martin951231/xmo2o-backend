<?php
/*
 * 腾讯云直播
 *
 * 	文档地址 https://cloud.tencent.com/document/product/267/30148
 */
include_once 'tencent_sdk/TCloudAutoLoader.php';
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Live\V20180801\LiveClient;
use TencentCloud\Live\V20180801\Models\CreateLiveRecordRequest;
use TencentCloud\Live\V20180801\Models\StopLiveRecordRequest;
use TencentCloud\Live\V20180801\Models\DescribeLiveStreamStateRequest;
use TencentCloud\Live\V20180801\Models\DescribeLiveRecordTemplatesRequest;
use TencentCloud\Live\V20180801\Models\DescribeLiveRecordRulesRequest;
class TencentLive{
	private $domain = null;
	private $secretid = null;
	private $secretkey = null;
	public function __construct(){
		$this->secretid = C("config.live_secret_id");
		$this->secretkey = C("config.live_secret_key");
		$this->domain = 'live.tencentcloudapi.com';
	}

	/**
	 * 创建录制任务
	 * @param  [type] $StreamName 直播流名称  对应本系统中的直播房间号
	 * @param  [type] $type       0=定时录制（可支持很长时间） 1=实时录制（最长只支持30分钟，一般用于商品演说录制）
	 * @param  [type] $start      [日期时间格式  2017-01-01 10:10:01]
	 * @param  [type] $end        [日期时间格式  2017-01-01 10:10:01]
	 * @param  [type] $StreamParam        云直播录制回调的时候，通过这个参数判断是哪一段视频
	 * @return [type]             [description]
	 */
	public function create_live_record($StreamName, $type = 1, $start = '', $end = '', $StreamParam = ''){
		if(empty($StreamName)) return ['errcode' => 1, 'errmsg' => '必填参数未填'];
		if($type == '0' && (empty($start) || empty($end))){
			return ['errcode' => 1, 'errmsg' => '录制定时任务，则时间段必填'];
		}
		try {
			$cred = new Credential($this->secretid, $this->secretkey);
		    $httpProfile = new HttpProfile();
		    $httpProfile->setEndpoint($this->domain);
		      
		    $clientProfile = new ClientProfile();
		    $clientProfile->setHttpProfile($httpProfile);
		    $client = new LiveClient($cred, "", $clientProfile);

		    $req = new CreateLiveRecordRequest();
		    
		    if($type == '0'){
			    $params = '{"StreamName":"'.$StreamName.'","StartTime":"'.urlencode($start).'","EndTime":"'.urlencode($end).'","Highlight":0,"StreamParam":"'.urlencode($StreamParam).'","FileFormat":"mp4"}';
			}
			else{
				$params = '{"StreamName":"'.$StreamName.'","Highlight":1,"StreamParam":"'.urlencode($StreamParam).'","FileFormat":"mp4"}';
			}
		    $req->fromJsonString($params);


		    $resp = $client->CreateLiveRecord($req);

		    $result = json_decode($resp->toJsonString(), true);
		    if (isset($result['Response']['Error']) && $result['Response']['Error']) {
                return ['errcode' => 1, 'errmsg' => $result['response']['Error']['Message']];
            }
            return ['errcode' => 0, 'errmsg' => '创建录制任务成功', 'retdata' => $result['TaskId']];
		}
		catch(TencentCloudSDKException $e) {
		    return ['errcode' => 1, 'errmsg' => '异常信息：' . $e->getMessage()];
		}
	}

	//结束录制
	public function stop_live_record($StreamName, $taskid){
		if(empty($taskid) || empty($StreamName)) return ['errcode' => 1, 'errmsg' => '必填参数未填'];
		try {
			$cred = new Credential($this->secretid, $this->secretkey);
		    $httpProfile = new HttpProfile();
		    $httpProfile->setEndpoint($this->domain);
		      
		    $clientProfile = new ClientProfile();
		    $clientProfile->setHttpProfile($httpProfile);
		    $client = new LiveClient($cred, "", $clientProfile);

		    $req = new StopLiveRecordRequest();
		    
		    $params = '{"StreamName":"'.$StreamName.'","TaskId":'.$taskid.'}';
    		$req->fromJsonString($params);


		    $resp = $client->StopLiveRecord($req);

		    $result = json_decode($resp->toJsonString(), true);
		    if (isset($result['Response']['Error']) && $result['Response']['Error']) {
                return ['errcode' => 1, 'errmsg' => $result['response']['Error']['Message']];
            }
            return ['errcode' => 0, 'errmsg' => '结束录制任务成功'];
		}
		catch(TencentCloudSDKException $e) {
		    return ['errcode' => 1, 'errmsg' => '异常信息：' . $e->getMessage()];
		}
	}

	//查询直播流状态
	public function get_live_state($StreamName){
		if(empty($StreamName)) return false;
		try {

		    $cred = new Credential($this->secretid, $this->secretkey);
		    $httpProfile = new HttpProfile();
		    $httpProfile->setEndpoint($this->domain);
		      
		    $clientProfile = new ClientProfile();
		    $clientProfile->setHttpProfile($httpProfile);
		    $client = new LiveClient($cred, "", $clientProfile);

		    $req = new DescribeLiveStreamStateRequest();
		    
		    $params = "{\"AppName\":\"live\",\"DomainName\":\"".C("config.live_push_domain")."\",\"StreamName\":\"".$StreamName."\"}";
		    $req->fromJsonString($params);


		    $resp = $client->DescribeLiveStreamState($req);
		    fdump("房间号：".$StreamName, "lumin0624",1);
		    $result = json_decode($resp->toJsonString(), true);
		    fdump($result, "lumin0624",1);
		    if (isset($result['Response']['Error']) && $result['Response']['Error']) {
                return false;
            }
            if($result['StreamState'] == 'active'){
            	return true;
            }
            return false;            
		}
		catch(TencentCloudSDKException $e) {
			fdump($e->getMessage(), "lumin0624",1);
		    return false;
		}
	}

	//获取录制模板
	public function get_record_template(){
		try {

		    $cred = new Credential($this->secretid, $this->secretkey);
		    $httpProfile = new HttpProfile();
		    $httpProfile->setEndpoint($this->domain);
		      
		    $clientProfile = new ClientProfile();
		    $clientProfile->setHttpProfile($httpProfile);
		    $client = new LiveClient($cred, "", $clientProfile);

		    $req = new DescribeLiveRecordTemplatesRequest();
		    
		    $params = "{}";
		    $req->fromJsonString($params);


		    $resp = $client->DescribeLiveRecordTemplates($req);

		    print_r($resp->toJsonString());
		}
		catch(TencentCloudSDKException $e) {
		    echo $e;
		}
		exit;
	}

	//获取录制规则
	public function get_record_rule(){
		try {

		    $cred = new Credential($this->secretid, $this->secretkey);
		    $httpProfile = new HttpProfile();
		    $httpProfile->setEndpoint($this->domain);
		      
		    $clientProfile = new ClientProfile();
		    $clientProfile->setHttpProfile($httpProfile);
		    $client = new LiveClient($cred, "", $clientProfile);

		    $req = new DescribeLiveRecordRulesRequest();
		    
		    $params = "{}";
		    $req->fromJsonString($params);


		    $resp = $client->DescribeLiveRecordRules($req);

		    print_r($resp->toJsonString());
		}
		catch(TencentCloudSDKException $e) {
		    echo $e;
		}
		exit;
	}
}