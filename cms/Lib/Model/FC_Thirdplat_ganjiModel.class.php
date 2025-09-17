<?php
/**
* 发布信息到赶集网
*/
class FC_Thirdplat_ganjiModel extends Model{
	// 获取验证码
    public function getCheckCode(){
    	$img = httpRequestWithCookie('https://passport.ganji.com/ajax.php?dir=captcha&module=login_captcha');
    	$cookies = $img[3];
        foreach($cookies as $cookie){
            $arr = explode('=', trim($cookie),2);
            cookie($arr[0],urldecode($arr[1]),10000);
        }
        return $img[1];
  	}

  	// 模拟登录赶集
  	public function dologin_ganji($username,$pwd,$checkCode=''){
       	// 15011560052
        $url = 'https://passport.ganji.com/login.php?callback=jQuery18201&username='.$username.'&password='.$pwd.'&checkCode='.$checkCode.'&setcookie=14';
        cookie('ganji_uuid','8934312150383284881991',10000);
        $result = httpRequestWithCookie($url);//dump($result);
        // 登录是否成功
        $res = rtrim(str_ireplace('jQuery18201(', '',$result[1]),')');
        if($res){
        	$res = json_decode($res,true);
        	$cookies = $result[3];
	        foreach($cookies as $cookie){
	            $arr = explode('=', trim($cookie),2);
	            cookie($arr[0],$arr[1],10000);
	        }
        	return $res;
        }
    }

    // 发房源图片
    public function upload_img_ganji($house_id,$flag = 1){
    	$imgList = false;
    	if($flag == 1){
    		$imgList = D('Fc_sell_house_img')->where(array('sell_house_id'=>$house_id))->select();
    	}else{
    		$imgList = D('Fc_rent_house_img')->where(array('sell_house_id'=>$house_id))->select();
    	}
    	
        if(!$imgList){
            return;
        }
        $imgdata = array();

        $url = 'http://upload.ganji.com/upload.php';
        foreach($imgList as $img){
        	$res = $this->upload_img($img['url'],$url);
        	if($res['error'] > 0){
        		continue;
        	}
        	$imgdata[] = array('image'=>$res['info'][0]['url'],'thumb_image'=>$res['info'][0]['thumbUrl'],'width'=>$res['info'][0]['image_info'][0],'height'=>$res['info'][0]['image_info'][1],'is_new'=>'true');
        }
        return $imgdata;
    }

    public function publish_ganji($post_data,$flag = 1){
    	$url = 'http://www.ganji.com/pub/pub.php?cid=7&mcid=21&act=pub&method=submit';
    	if($flag == 2){
    		// 租房
    		$url = 'http://www.ganji.com/pub/pub.php?cid=7&mcid=20&act=pub&method=submit';
    	}

    	$headers = array();
		$headers[] = 'Content-type: application/x-www-form-urlencoded,text/html';
		$headers[] = 'Accept: application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
		$headers[] = 'Accept-Language: zh-CN,zh;q=0.8';
		$headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.75 Safari/537.36';
		$headers[] = 'Upgrade-Insecure-Requests: 1';
    	cookie('citydomain','bj',10000);
    	cookie('bizs','%5B%5D',10000);
    	cookie('lg','1',10000);
    	$res = httpRequestWithCookie($url,'POST',$post_data,$headers,false,false);
    	$responseMsg = $res[1];
    	$isok = mb_strstr($responseMsg,L_('恭喜您，发布成功啦！'));
    	if($isok != false){
    		return array('code'=>0,'msg'=>L_('恭喜您，发布成功啦！'));
    	}else{
    		preg_match_all('/displayError(.*)/',$responseMsg,$errMsg); //正则匹配
    		$msg = implode(PHP_EOL, $errMsg[1]);
    		$msg = str_replace('(', '', $msg);
    		$msg = str_replace(')', '', $msg);
    		$msg = str_replace(';', '<br/>', $msg);
    		return array('code'=>1,'msg'=>$msg);
    	}
    }

    // 执行图片上传
    private function upload_img($file,$post_url){
    	header('content-type:text/html;charset=utf8');
		$ch = curl_init();

		$data = array('Filedata'=>'@'. str_replace('/core/', '', THINK_PATH).$file);
		curl_setopt($ch,CURLOPT_URL,$post_url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
		$result = curl_exec($ch);
		curl_close($ch);
		$result = json_decode($result,true);
		return $result;
    }

    
}