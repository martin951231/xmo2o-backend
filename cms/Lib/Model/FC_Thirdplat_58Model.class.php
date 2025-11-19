<?php
/**
* 发布信息到58同城
*/
class FC_Thirdplat_58Model extends Model{
	// 模拟登录58同城
    public function dologin_58($username,$pwd){
        $microtime = microtime(true);
        $microtime = str_replace('.', '', $microtime);
        $microtime = substr($microtime, 0,strlen($microtime)-1);

        $url = 'https://passport.58.com/login/dologin';
        $post['isweak'] = 0;
        $post['source'] = 'pc-login';
        $post['originsource'] = 'passport';
        $post['path'] = 'http://my.58.com/?pts='.$microtime;
        $post['password'] = $pwd;
        $post['timesign'] = time();
        $post['isremember'] = false;
        $post['callback'] = 'successFun';
        $post['yzmstate'] = '';
        $post['fingerprint'] = '_000';
        $post['tokenId'] = 3668129886;
        $post['username'] = $username;
        $post['btnSubmit'] = L_('登录中...');

        $res = httpRequestWithCookie($url,'POST',$post);
        $cookies = $res[3];
        $PPU = '';
        foreach($cookies as $cookie){
            $arr = explode('=', $cookie,2);
            cookie($arr[0],$arr[1],0);
            if(trim($arr[0]) == 'PPU'){
                $PPU = trim($arr[1]);
            }
        }
        $res = httpRequestWithCookie('http://my.58.com/index?r=0.9086476394201645','GET');

        $num = 0;
        while($res[0] == 302){
            // 重定向，最多允许9次重定向
            $res = httpRequestWithCookie($res[2]['url'],'GET');
            $num++;
            if($num >9){
                break;
            }
        }

        $uid = 0;
        $ppus = explode('&',$PPU);
        $uidstr = $ppus[0];
        $uids = explode('=',$uidstr);
        $uid = $uids[1];
        return $uid;
    }

    // 上传房源图片
    public function upload_img_58($house_id){
        $imgList = D('Fc_sell_house_img')->where(array('sell_house_id'=>$house_id))->select();
        if(!$imgList){
            return;
        }
        $url = 'http://upload.58cdn.com.cn/json';
        $headers = array("Content-type: application/x-www-form-urlencoded; charset=UTF-8","Accept: */*","Cache-Control: no-cache","Pragma: no-cache");

        $img_58_names = array();
        $fc_path = str_replace('core/', '', THINK_PATH);
        foreach ($imgList as $value) {
            if(!file_exists($fc_path.$value['url'])){
                continue;
            }
            $base64_img = $this->base64EncodeImage($fc_path.$value['url']);
            $data = '{"Pic-Size":"0*0","Pic-Encoding":"base64","Pic-Path":"/p1/big/","Pic-Data":"'.$base64_img.'"}';
            $res = httpRequestWithCookie($url,'POST',$data,$headers);
            $img_58_names[] = '/p1/big/'.$res[1];
        }
        return $img_58_names;
    }

    // 图片base64转码
    private function base64EncodeImage ($image_file) {
      $base64_image = '';
      $image_info = getimagesize($image_file);
      $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
      $base64_image = base64_encode($image_data);
      return $base64_image;
    }

    // 同步房源信息
    // post_data同步的数据，flag:1 同步二手房，flag:2 租房
    public function do_publish_58($post_data,$flag = 1){
        $url = 'http://post.58.com/837/12/s5/submit?ClickID=2';
        if($flag == 2){
        	$url = 'http://post.58.com/837/8/s5/submit?ClickID=2';
        }
        $headers = array("Content-type: application/x-www-form-urlencoded;charset=utf-8","Accept: application/json, text/javascript, */*;","Cache-Control: no-cache","Pragma: no-cache");
        $res = httpRequestWithCookie($url,'POST',$post_data,$headers);
        return $res;
    }
}