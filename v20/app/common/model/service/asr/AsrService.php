<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 *             语音识别
	 * @Desc      使用之前请熟悉文档： @see  https://cloud.tencent.com/document/product/1093/35637
	 *            
	 */
	namespace app\common\model\service\asr;
	/*
	use TencentCloud\Aai\V20180522\Models\SentenceRecognitionRequest;
	use TencentCloud\Asr\V20190614\AsrClient;
	use TencentCloud\Asr\V20190614\Models\CloseAsyncRecognitionTaskRequest;
	use TencentCloud\Asr\V20190614\Models\CreateAsrVocabRequest;
	use TencentCloud\Asr\V20190614\Models\CreateAsyncRecognitionTaskRequest;
	use TencentCloud\Asr\V20190614\Models\CreateCustomizationRequest;
	use TencentCloud\Asr\V20190614\Models\DeleteAsrVocabRequest;
	use TencentCloud\Asr\V20190614\Models\DeleteCustomizationRequest;
	use TencentCloud\Asr\V20190614\Models\DescribeAsyncRecognitionTasksRequest;
	use TencentCloud\Asr\V20190614\Models\DownloadAsrVocabRequest;
	use TencentCloud\Asr\V20190614\Models\DownloadCustomizationRequest;
	use TencentCloud\Asr\V20190614\Models\GetAsrVocabListRequest;
	use TencentCloud\Asr\V20190614\Models\GetAsrVocabRequest;
	use TencentCloud\Asr\V20190614\Models\GetCustomizationListRequest;
	use TencentCloud\Asr\V20190614\Models\ModifyCustomizationRequest;
	use TencentCloud\Asr\V20190614\Models\ModifyCustomizationStateRequest;
	use TencentCloud\Asr\V20190614\Models\SetVocabStateRequest;
	use TencentCloud\Asr\V20190614\Models\UpdateAsrVocabRequest;
	use TencentCloud\Common\Credential;
	use TencentCloud\Common\Exception\TencentCloudSDKException;
	use TencentCloud\Common\Profile\ClientProfile;
	use TencentCloud\Common\Profile\HttpProfile;
	 暂时用不到 屏蔽掉 */
	use think\Exception;

	class AsrService
	{
        private $is_open=0;
	    private $secretId;
		private $secretKey;
		private $appid;
		public  $client;

		public function __construct ()
		{
			$this->secretId  = cfg('tencent_voice_asr_secretid');
            $this->secretId=trim($this->secretId);
			$this->secretKey = cfg('tencent_voice_asr_secretkey');
            $this->secretKey=trim($this->secretKey);
			$this->appid     = cfg('tencent_voice_asr_appid');
            $this->appid=trim($this->appid);
            $this->is_open    = cfg('tencent_voice_asr_open');
            $this->is_open=intval($this->is_open);
            if ($this->is_open<1){
                throw new Exception('链接失败，未开启语音识别功能！');
            }
			if (empty($this->secretId) || empty($this->secretKey) || empty($this->appid)){
				throw new Exception('链接失败，语音识别配置参数有误！');
			}
			/*
			try {
				// 实例化一个认证对象，入参需要传入腾讯云账户secretId，secretKey,此处还需注意密钥对的保密
				// 密钥可前往 https://console.cloud.tencent.com/cam/capi 网站进行获取
				$cred         = new Credential($this->secretId,$this->secretKey);
				$httpProfile  = new HttpProfile();
				$httpProfile->setEndpoint('asr.tencentcloudapi.com');
				$clientProfile= new ClientProfile();
				$clientProfile->setHttpProfile($httpProfile);
				
				$this->client = new AsrClient($cred, '', $clientProfile);
				
			}catch (TencentCloudSDKException $e){
                throw new Exception($e->getMessage()); 
                //return $e->getMessage();
			}
			 */
		}

		//方式一、signature 签名生成(只返回签名本身)
 		public function signature( array $params)
		{
			$params['secretid'] = $this->secretId;
			ksort($params);
			$sourcStr = http_build_query($params);
			$mapSourcStr = 'asr.cloud.tencent.com/asr/v2/'.$this->appid.'?'.$sourcStr;
			return $this->signatureStr($mapSourcStr);
		}
		
		
		private function signatureStr($mapSourcStr)
		{
			return urlencode(base64_encode(hash_hmac('sha1',$mapSourcStr,$this->secretKey,true)));
		}
 
		//方式二 、signature 签名生成 建议使用 （直接返回完整的 wss 整个URI 串）
		public function signatureWss(array $params)
		{
			$params['secretid'] = $this->secretId;
			ksort($params);
			$sourcStr = http_build_query($params);
			$mapSourcStr = 'asr.cloud.tencent.com/asr/v2/'.$this->appid.'?'.$sourcStr;
			return 'wss://'.$mapSourcStr.'&signature='.$this->signatureStr($mapSourcStr);
		}
		
		/**
		 * 一句话识别 
		 * @param array $params
		 *
		 * @return false|string
		 */
		public function aWrodRecognition(array $params)
		{
			try {
				// 实例化一个请求对象,每个接口都会对应一个request对象
				$req = new SentenceRecognitionRequest();
				$req->fromJsonString(\json_encode($params));

				// 返回的resp是一个SentenceRecognitionResponse的实例，与请求对象对应
				$resp = $this->client->SentenceRecognition($req);
				return $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 语音流异步识别任务创建
		 * @param array $params
		 *
		 * @return false|string
		 */
		public function createAsynceRecogintionTask(array $params)
		{
			try {
				$req = new CreateAsyncRecognitionTaskRequest();
				$req->fromJsonString(\json_encode($params));
				
				$resp = $this->client->CreateAsyncRecognitionTask($req);
				
				return $resp->toJsonString();
				
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 语音流异步识别任务列表
		 * @return false|string
		 */
		public function getAsyncRecognitionTasks()
		{
			try {
				$req = new DescribeAsyncRecognitionTasksRequest();
				$params = [];
				$req->fromJsonString(\json_encode($params));
				
				$resp = $this->client->DescribeAsyncRecognitionTasks($req);
				return $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 语音流异步识别任务关闭
		 * @param int $TaskId 语音流异步识别任务的唯一标识，在创建任务时会返回
		 *
		 * @return false|string
		 */
		public function closeAsyncRecognitionTask(int $TaskId)
		{
			try {
				$req = new CloseAsyncRecognitionTaskRequest();
				$params = [
					'TaskId' => $TaskId
				];
				$req->fromJsonString(\json_encode($params));
				
				$resp  = $this->client->CloseAsyncRecognitionTask($req);
				return  $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 创建热词表
		 * @param array $params
		 *
		 * @return false|string
		 */
		public function createAsrVocab(array $params)
		{
			try {
				$req = new CreateAsrVocabRequest();
				$req->fromJsonString(\json_encode($params));

				$resp = $this->client->CreateAsrVocab($req);
				return $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return  $e->getMessage();
			}
		}

		/**
		 * 更新热词表
		 * @param array $params
		 *
		 * @return false|string
		 */
		public function updateAsrVocab(array $params)
		{
			try {
				$req = new UpdateAsrVocabRequest();
				$req->fromJsonString(\json_encode($params));
				
				$resp = $this->client->UpdateAsrVocab($req);
				return  $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 设置热词表状态
		 * @param array $params
		 *
		 * @return false|string
		 */
		public function setVocabState(array $params)
		{
			try {
				$req = new SetVocabStateRequest();
				$req->fromJsonString(\json_encode($params));
				
				$resp = $this->client->SetVocabState($req);
				return  $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 列举热词表 
		 * @param array $params
		 *
		 * @return false|string
		 */
		public function getAsrVocabList(array $params = [])
		{
			try {
				$req = new GetAsrVocabListRequest();
				$req->fromJsonString(\json_encode($params));
				
				$resp = $this->client->GetAsrVocabList($req);
				return  $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 获取热词表
		 * @param $vocabId
		 *
		 * @return false|string
		 */
		public function getAsrVocab($vocabId)
		{
			try {
				$req = new GetAsrVocabRequest();
				$req->fromJsonString(\json_encode(['VocabId'=>$vocabId]));
				
				$resp = $this->client->GetAsrVocab($req);
				return $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 下载热词表
		 * @param $vocabId
		 *
		 * @return false|string
		 */
		public function downloadAsrVocab($vocabId)
		{
			try {
				$req = new DownloadAsrVocabRequest();
				$req->fromJsonString(\json_encode(['VocabId'=>$vocabId]));
				
				$resp = $this->client->DownloadAsrVocab($req);
				return  $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 删除热词表
		 * @param $vocabId
		 *
		 * @return false|string
		 */
		public function deleteAsrVocab($vocabId)
		{
			try {
				$req = new DeleteAsrVocabRequest();
				$req->fromJsonString(\json_encode(['VocabId'=>$vocabId]));

				$resp = $this->client->DeleteAsrVocab($req);
				return  $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 创建自学习模型
		 * @param array $params
		 *
		 * @return false|string
		 */
		public function createCustomization(array $params)
		{
			try {
				$req = new CreateCustomizationRequest();
				$req->fromJsonString(\json_encode($params));

				$resp = $this->client->CreateCustomization($req);
				return  $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 查询自学习模型列表
		 * @param array $params
		 *
		 * @return false|string
		 */
		public function getCustomizationList(array $params = [])
		{
			try {
				$req = new GetCustomizationListRequest();
				$req->fromJsonString(\json_encode($params));

				$resp = $this->client->GetCustomizationList($req);
				return  $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 更新自学习模型
		 * @param array $params
		 *
		 * @return false|string
		 */
		public function modifyCustomization(array $params)
		{
			try {
				$req = new ModifyCustomizationRequest();
				$req->fromJsonString(\json_encode($params));

				$resp = $this->client->ModifyCustomization($req);
				return  $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 修改自学习模型状态
		 * @param array $params
		 *
		 * @return false|string
		 */
		public function modifyCustomizationState(array $params)
		{
			try {
				$req = new ModifyCustomizationStateRequest();
				$req->fromJsonString(\json_encode($params));

				$resp = $this->client->ModifyCustomizationState($req);
				return  $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 删除自学习模型
		 * @param $modelId
		 *
		 * @return false|string
		 */
		public function deleteCustomization($modelId)
		{
			try {
				$req = new DeleteCustomizationRequest();
				$req->fromJsonString(\json_encode(['ModelId'=>$modelId]));

				$resp = $this->client->DeleteCustomization($req);
				return  $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}

		/**
		 * 下载自学习模型语料
		 * @param $modelId
		 *
		 * @return false|string
		 */
		public function downloadCustomization($modelId)
		{
			try {
				$req = new DownloadCustomizationRequest();
				$req->fromJsonString(\json_encode(['ModelId'=>$modelId]));

				$resp = $this->client->DownloadCustomization($req);
				return  $resp->toJsonString();
			}catch (TencentCloudSDKException $e){
				return $e->getMessage();
			}
		}
		
		
	}