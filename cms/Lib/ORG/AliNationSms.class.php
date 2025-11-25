<?php

/**
 * Created by PigCMS.
 * User: Kingson.
 * Date: 2019/9/9.
 * Time: 13:57.
 */
require __DIR__ . '/ali_nation_sms/autoload.php';
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class AliNationSms
{
    private $config = array(
        'accessKeyId' => '',
        'accessSecret' => '',
    );

    /**
     * AliNationSms constructor.
     */
    public function __construct()
    {
        $this->config['accessKeyId']    =  C('config.ali_national_sms_accesskey');
        $this->config['accessSecret']   =  C('config.ali_national_sms_access_secret');
    }

    /**
     * @desc 发送短信
     * @param  to:短信接收人手机号,指定国家码，如：61401345618
     * @param  content:短信内容
     * @return bool|string
     */
    public function send($data){
		$nationCode = $data['nationCode'] ? $data['nationCode'] : C('config.ali_national_sms_default_country');
		if($nationCode=='86'){
			return $this->send_to_china($data);
		}
        AlibabaCloud::accessKeyClient($this->config['accessKeyId'], $this->config['accessSecret'])
            ->regionId('ap-southeast-1')
            ->asDefaultClient();
        //$sms_content = '['.trim(C('config.sms_sign')).']'.$data['content'];//需要发送的短信内容
        $sms_content = $data['content'];
        $to_phone = $nationCode.$data['mobile'];
        $sms_sign=C('config.sms_sign');
        if (!empty($data['o2o_village_id'])){
            $arr = D('House_village')->checkVillageSms($data['o2o_village_id'], 1, true);
            if (! $arr) {
                AliNationSms::add_sms_record($data,-2);
                return false;
            }
            if (!empty($arr['sms_sign'])) {
                $sms_sign = $arr['sms_sign'];
            }
        }
        try {
            $result = AlibabaCloud::rpc()
                ->product('sms-intl')
                // ->scheme('https') // https | http
                ->version('2018-05-01')
                ->action('SendMessageToGlobe')
                ->method('POST')
                ->host('sms-intl.ap-southeast-1.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "ap-southeast-1",
                        'To' => $to_phone,//61401345619
                        'Message' => $sms_content,
						'From' => trim($sms_sign),
                    ],
                ])
                ->request();
            $r = $result->toArray();
            if($r['ResponseCode']=='OK'){
                $sms_status = 1;
            }else{
                $sms_status = 0;
            }
            $content = json_encode($r);
        } catch (ClientException $e) {
            $sms_status = 0;
            $content = $e->getErrorMessage();
        } catch (ServerException $e) {
            $sms_status = 0;
            $content = $e->getErrorMessage();
        }
        //增加到本地数据库
        AliNationSms::add_sms_record($data,$sms_status);
        fdump_sql($content,'ali_nation_sms_error_log');
        return $content;
    }
	
	//增加到本地数据库记录
	public function add_sms_record($data,$status){
		$row = array(
            'mer_id'     =>  isset($data['o2o_mer_id']) ? intval($data['o2o_mer_id']) : 0,
            'uid'        =>  isset($data['o2o_uid']) ? intval($data['o2o_uid']) : 0,
            'store_id'   =>  isset($data['o2o_store_id']) ? intval($data['o2o_store_id']) : 0,
            'time'       =>  time(),
            'phone'      =>  isset($data['mobile']) ? $data['mobile'] : '',
            'text'       =>  isset($data['content']) ? $data['content'] : '',
            'status'     =>  $status,
            'type'       =>  isset($data['o2o_type']) ? $data['o2o_type'] : '',
            'sendto'     =>  isset($data['o2o_sendto']) ? $data['o2o_sendto'] : '',
            'village_id' =>  isset($data['o2o_village_id']) ? $data['o2o_village_id'] : 0,
            'village_send_id' => isset($data['village_send_id']) ? intval($data['village_send_id']) : 0
        );
        $add = D('Sms_record')->add($row);
        if (! $add) {
            fdump_sql(['AliNationSms', $row],'sms_error_log');
        }
        // 如果是社区群发处理社区群发
        if (0 == $status && intval($row['village_send_id']) > 0 && $data['o2o_type'] == 'house_group_send') {
            // 正常发送添加成功发送数量
            M('House_sms_group_send')->where(array('send_id' => $data['village_send_id']))->setInc('success_send_num', 1);
        }
        $village_id = $row['o2o_village_id'];
        if (intval($village_id) > 0) {
            // 发送不论是否成功更正下社区拥有短信数量
            D('House_village')->correctVillageSmsNumber($data['village_send_id']);
        }
        return true;
	}
	
	//发送中国国内短信
	public function send_to_china($data){
		// Download：https://github.com/aliyun/openapi-sdk-php
		// Usage：https://github.com/aliyun/openapi-sdk-php/blob/master/README.md
		AlibabaCloud::accessKeyClient($this->config['accessKeyId'], $this->config['accessSecret'])
								->regionId('ap-southeast-1')
								->asDefaultClient();
		$to_phone = '86'.$data['mobile'];
		$template = $this->fetch_template($data['content']);

		$params["TemplateCode"] = $template['templateCode'];
		foreach ($template['param'] as $key=>$item) {
			$params['TemplateParam'][$key] = $item;
		}
		
		// *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
		if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
			$params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
		}
        $sms_sign=C('config.sms_sign');
        if (!empty($data['o2o_village_id'])){
            $arr = D('House_village')->checkVillageSms($data['o2o_village_id'], 1, true);
            if (! $arr) {
                AliNationSms::add_sms_record($data,-2);
                return false;
            }
            if (!empty($arr['sms_sign'])) {
                $sms_sign = $arr['sms_sign'];
            }
        }
		try {
			$result = AlibabaCloud::rpc()
								  ->product('sms-intl')
								  // ->scheme('https') // https | http
								  ->version('2018-05-01')
								  ->action('SendMessageWithTemplate')
								  ->method('POST')
								  ->host('sms-intl.ap-southeast-1.aliyuncs.com')
								  ->options([
												'query' => [
												  'RegionId' => "ap-southeast-1",
												  'To' => $to_phone,
												  'From' => trim($sms_sign),
												  'TemplateCode' => $params['TemplateCode'],
												  'TemplateParam' => $params['TemplateParam'],
												],
											])
								  ->request();
			//print_r($result->toArray());
			$r = $result->toArray();
            if($r['ResponseCode']=='OK'){
                $sms_status = 1;
            }else{
                $sms_status = 0;
            }
            $content = json_encode($r);
		} catch (ClientException $e) {
			//echo $e->getErrorMessage() . PHP_EOL;
			$sms_status = 0;
            $content = $e->getErrorMessage();
		} catch (ServerException $e) {
			//echo $e->getErrorMessage() . PHP_EOL;
			$sms_status = 0;
            $content = $e->getErrorMessage();
		}
		//增加到本地数据库
        AliNationSms::add_sms_record($data,$sms_status);
		
        fdump_sql($content,'ali_nation_sms_error_log');
        return $content;
		
	}
	
	//获取短信模板
	public function fetch_template($content){
		$template_list = M('Ali_sms_template')->where(array('status'=>1))->select();
		foreach ($template_list as $item) {
			preg_match_all('/\${\w+}/',$item['content'],$match);
			$param =  preg_replace("/(\\$|{|})/",'',$match[0]);
			$sms_str = preg_replace('/\${\w+}/','||',$item['content']);
			$sms_arr = explode('||',$sms_str);

			foreach ($sms_arr as &$it) {
				$it = "/".trim($it)."/";
			}

			$replace_str = preg_replace($sms_arr, '|', $content,-1,$count);
			$replace_str = trim($replace_str,'|');

			$replace_arr = explode('|',$replace_str);

			foreach ($replace_arr as $key=>$str) {
				$item['param'][$param[$key]] = $str;
			}

			if($count==count($sms_arr)){
				return $item;
			}
		}
		return false;
	}

}
