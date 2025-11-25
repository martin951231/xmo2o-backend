<?php
/**
 * 阿里云实名认证
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/8/5 10:52
 */

class CheckIdCardAction extends BaseAction
{
    public $uid;

    protected function _initialize(){
        parent::_initialize();
        $ticket = I('ticket', false);
        $device_id    =   I('Device-Id',false);
        if($ticket) {
            $info = ticket::get($ticket, $device_id, true);
            if ($info) {
                $this->uid = $info['uid'];
            }
        } else {
            $this->returnCode('20140003');
        }
    }

    //上传图片   格式为 JPG(JPEG)，BMP，PNG，GIF，TIFF（小写也可以），宽和⾼大于 8px，⼩于等于4000px，大小不能超过1MB
    public function upload_img(){
        $uid = $this->uid;
        $errors = [
            UPLOAD_ERR_INI_SIZE => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
            UPLOAD_ERR_FORM_SIZE => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败'
        ];

        if ($_FILES['imageFile']['error'] === UPLOAD_ERR_OK) {
            if($_FILES['imageFile']['size'] > 1024*1024 ){
//                $this->returnCode(1001,[], '图片大小不能超过1Mb！');
            }
            $filename = pathinfo($_FILES['imageFile']['name']);
            if(empty($filename['extension'])){
                switch ($_FILES['imageFile']['type']) {
                    case 'image/png':
                        $_FILES['imageFile']['name'] .= '.png';
                        break;
                    case 'image/jpg':
                        $_FILES['imageFile']['name'] .= '.jpg';
                        break;
                    case 'image/gif':
                        $_FILES['imageFile']['name'] .= '.gif';
                        break;
                }
            }
            $image = D('Image')->handle($uid, 'live', 2, array('size' => 10), true);
            if ($image['error']) {
                $this->returnCode(1001, [], $image['message']);
            } else {
                $imageUrl = replace_file_domain($image['url']['imageFile']);
                $this->returnCode(0, ['url' => $imageUrl, 'path' => $image['url']['imageFile']]);
            }
        } else {
            $this->returnCode(1001, [], isset($errors[$_FILES['imageFile']['error']]) ? $errors[$_FILES['imageFile']['error']] : '上传失败');
        }
    }


    //姓名 + 身份证号 + 身份证正反面
    public function index() {

        $uid = $this->uid;
        $user_truename = $_POST['user_truename']; //姓名
        $user_id_number = $_POST['user_id_number']; //身份证号
        $authentication_img = $_POST['authentication_img']; //身份证正面
        $authentication_back_img = $_POST['authentication_back_img']; //身份证反面
        fdump($_POST['authentication_img'], 'cx_0808',1);
        fdump($_POST['authentication_back_img'], 'cx_0808',1);

        //先去查用户是否已实名认证
        $user_res = M('User_authentication')->field('authentication_status')->where(['uid'=>$uid, 'user_truename'=>$user_truename, 'user_id_number'=>$user_id_number])->find();
        if($user_res['authentication_status'] == 1) {
            $this->returnCode(0); //成功
        }


        /*----------------------   使用 姓名 + 身份证号 + 身份证正反面 审核  start  -------------------------*/

//        备注：
//        1、info节点中上传身份证正面照片时返回字段name,sex,nation,year,mouth,day,address,number,上传身份证背面照⽚时返回字段authority和timelimit
//        2、validity节点中上传身份证正面照片时返回字段name,sex,birthday,address,number,上传身份证背⾯照片时返回字段authority和timelimit
//        3、当上传的图⽚是⾮身份证图⽚片时，系统不会报错，但这种情况下validity中所有字段都会为false,请参考validity字段进行判断

//        if(empty($uid) || empty($user_truename) || empty($user_id_number) || empty($authentication_img) || empty($authentication_back_img)) {
//            $this->returnCode(1001, [],'提交信息不齐全！');
//        }

        $res_img_ok = false;
        $res_back_img_ok = false;
        $user_auth_notneed_img = C("config.user_auth_notneed_img");

        /*-----------   身份证正面  start  ------------*/

        if ((!is_null($user_auth_notneed_img) && $user_auth_notneed_img != '1') || (is_null($user_auth_notneed_img) && !$this->config['open_yanglao_and_sande'])) {
            $res_img = $this->img_check($authentication_img);  //识别正面照
    ////        //测试使用 start
    //        $res_img_json = '{"msg":"成功","success":true,"code":200,"data":{"order_no":"963278216431653648","result":0,"side":"front","info":{"number":"430124196203220016","address":"湖南省宁乡县玉潭镇楚沩西路38-49号","month":"3","nation":"汉","year":"1962","sex":"男","name":"李建良","day":"22"},"validity":{"birthday":true,"number":true,"address":true,"sex":true,"name":true},"image_url":"http://shumai-picture.oss-cn-hangzhou.aliyuncs.com/%E9%98%BF%E9%87%8C%E4%BA%91API/1596786974162image.jpg?Expires=1596790574&OSSAccessKeyId=LTAIWE9Yj6jLeaiY&Signature=m8oTOj55oEMI4%2BW8%2Br%2F%2F6bx3B9Q%3D"}}';
    //        $res_img = json_decode($res_img_json,true);
    //        //测试用用 end

            if($res_img['code'] == 200 && $res_img['success'] == true) {
                if($res_img['data']['validity'] && $res_img['data']['result'] == 0 && $res_img['data']['validity']['birthday'] == true && $res_img['data']['validity']['number'] == true && $res_img['data']['validity']['address'] == true && $res_img['data']['validity']['sex'] == true && $res_img['data']['validity']['name'] == true ) {
                    $res_img_ok = true; //记录正面审核成功
                }
            }

    //        {"msg":"成功","success":true,"code":200,"data":{"order_no":"963278216431653648","result":0,"side":"front","info":{"number":"430124196203220016","address":"湖南省宁乡县玉潭镇楚沩西路38-49号","month":"3","nation":"汉","year":"1962","sex":"男","name":"李建良","day":"22"},"validity":{"birthday":true,"number":true,"address":true,"sex":true,"name":true},"image_url":"http://shumai-picture.oss-cn-hangzhou.aliyuncs.com/%E9%98%BF%E9%87%8C%E4%BA%91API/1596786974162image.jpg?Expires=1596790574&OSSAccessKeyId=LTAIWE9Yj6jLeaiY&Signature=m8oTOj55oEMI4%2BW8%2Br%2F%2F6bx3B9Q%3D"}}
            /*-----------   身份证正面  end  ------------*/



            /*-----------   身份证反面  start  ------------*/
            $res_back_img = $this->img_check($authentication_back_img);  //识别反面照

    //        //测试使用 start
    //        $res_back_img_json = '{"msg": "","success": true,"code": 200,"data":{"order_no": "634416423342575616","result": 0,"side": "back","info":{"authority": "南昌市公安局昌北分局","timelimit": "20180727-20380727"},"validity":{"authority": true,"timelimit": true}}}';
    //        $res_back_img = json_decode($res_back_img_json,true);
    //        //测试用用 end

            if($res_back_img['code'] == 200 && $res_back_img['success'] == true) {
                if($res_back_img['data']['validity'] && $res_back_img['data']['result'] == 0 && $res_back_img['data']['validity']['authority'] == true && $res_back_img['data']['validity']['timelimit'] == true) {
                    $res_back_img_ok = true; //记录反面审核成功
                }
            }
        }
        else{
            $res_img_ok = true;
            $res_back_img_ok = true;
            $oldage = true;
        }

        /*-----------   身份证反面  end  ------------*/

        //验证扫描信息 和 提交信息 是否一致
        fdump($res_img_ok.'_'.$res_back_img_ok, 'cx_0808',1);
        if($res_img_ok && $res_back_img_ok) {

            // 写入实名认证表
            $insert_data['uid'] = $uid;
            $insert_data['user_truename'] = $user_truename;
            $insert_data['user_id_number'] = $user_id_number;
            $insert_data['authentication_img'] = $authentication_img;
            $insert_data['authentication_back_img'] = $authentication_back_img;
            $insert_data['authentication_time'] = time(); //认证时间
            $insert_data['examine_time'] = time(); //审核时间

            if(($user_truename == $res_img['data']['info']['name'] && $user_id_number == $res_img['data']['info']['number']) || $oldage) {
                $time_limit = explode('-', $res_back_img['data']['info']['timelimit']);
                if($time_limit[1] < date('Ymd', time()) && !$oldage) {

                    $insert_data['examine_remarks'] = 3;  //审核失败备注 默认0  1照片模糊 2照片和名字不符 3身份证过期
                    $insert_data['authentication_status'] = 2; //审核状态 0未审核 1审核通过 2审核不通过
                    M('User_authentication')->data($insert_data)->add();

                    $this->returnCode(1001, [],'身份证已过期！');
                } else {
                    //审核成功 写入实名认证表
                    $insert_data['examine_remarks'] = 0;
                    $insert_data['authentication_status'] = 1;

                    $res_auth = M('User_authentication')->field('authentication_id')->where(['uid'=>$uid])->find();
                    if($res_auth) {
                        $res = M('User_authentication')->where(['authentication_id'=>$res_auth['authentication_id']])->data($insert_data)->save();
                    } else {
                        $res = M('User_authentication')->data($insert_data)->add();
                    }

                    if($res) {
                        $this->returnCode(0);
                    } else {
                        $this->returnCode(1001, [],'数据写入失败！');
                    }
                }
            } else {
                $insert_data['examine_remarks'] = 2;  //审核失败备注 默认0  1照片模糊 2照片和名字不符 3身份证过期
                $insert_data['authentication_status'] = 2; //审核状态 0未审核 1审核通过 2审核不通过
                $this->returnCode(1001, [],'姓名或者身份证号与身份证上信息不一致！');
            }
        } else {
            $insert_data['examine_remarks'] = 1;  //审核失败备注 默认0  1照片模糊 2照片和名字不符 3身份证过期
            $insert_data['authentication_status'] = 2; //审核状态 0未审核 1审核通过 2审核不通过
            $this->returnCode(1001, [],'身份证照片无法识别,请重新上传！');
        }

        /*----------------------   使用 姓名 + 身份证号 + 身份证正反面 审核  end -------------------------*/
    }

    //身份证照片认证
    public function img_check($img) {

        $host = "https://idcardocrc.shumaidata.com";
        $path = "/getidcardocrc";
        $method = "POST";
        $appcode = C('config.appcode');
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
		
		 
		$file_handle = new file_handle();
        $full_url = replace_file_domain($img);
		$imgPath = $file_handle->get_path($img);
        $imgSize = getimagesize($img);
        if($imgSize[0] > $imgSize[1]){
            $width = 520;
            $height = 358;
        }else{
            $width = 358;
            $height = 520;
        }
	    $models = '\app\common\model\service\image\ImageService/encodeImgToDataUrl';
	    $param = [
            'params'     => [
                'imgPath'      => substr($full_url, 0, 4) == 'http' ? $full_url : rtrim(WEB_PATH, '/') . $imgPath,
                'width' => $width,
                'heiht' => $height,
            ]
	    ];
	    $getResult = invoke_v20_service($models,$param);
		if (!empty($getResult)){
			$dataUrl = $getResult['data']['retval'];
			if (strpos($dataUrl,"data:image/jpeg;base64") === 0){
                $image = urlencode(explode('data:image/jpeg;base64,',$dataUrl)[1]);
                $bodys = "image=data:image/jpeg;base64,".$image;
//				$bodys = "url=".$getResult['data']['retval'];
			}else if(strpos($dataUrl,"data:image/png;base64") === 0){
                $image = urlencode(explode('data:image/png;base64,',$dataUrl)[1]);
                $bodys = "image=data:image/png;base64,".$image;
//				$bodys = "url=".$getResult['data']['retval'];
			}else{
				$this->returnCode(1001, [], '图片压缩失败！'.$dataUrl);
			}
		 
		}else{
			$this->returnCode(1001, [], '图片压缩失败，请检查');
		}
	   
        $querys = "";
        //image 和 url 二选一 ，选择其中一个时，请注释另外一个参数,默认使用image
//        $bodys = "url=".urlencode($img);  //  正面  authentication_img    反面  authentication_back_img

//        $image = urlencode("base64字符串");
//        $bodys = "image=data:image/jpg;base64,".$image;
        $url = $host . $path;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //设定返回信息中是否包含响应信息头，启用时会将头文件的信息作为数据流输出，true 表示输出信息头, false表示不输出信息头
        //如果需要将字符串转成json，请将 CURLOPT_HEADER 设置成 false
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);

        $data = json_decode(curl_exec($curl), true);
        fdump($data, 'cx_0808',1);
        if(isset($data['code']) && $data['code'] != 200){
            $this->returnCode(1001, [], $data['msg']);
        }
        return $data;
    }


        // 正面返回
//        {
//            "msg": "",
//    "success": true,
//    "code": 200,
//    "data": {
//            "order_no": "634416423342575616",//订单号
//        "result": 0,//扫描状态 0-成功 1-失败
//        "side": "front",//正反面 front-人像面 back-国徽面
//        "info": {//身份证信息
//                "number": "320382198912014010",//身份证号
//            "address": "江苏省苏州市岔河镇东沙庄村黄庄150号",//地址
//            "month": "12",//月份
//            "nation": "汉",//民族
//            "year": "1989",//年份
//            "sex": "男",//性别
//            "name": "赵宁宁",//姓名
//            "day": "1"//日期
//        },
//        "validity": {//扫描成功结果，详情见下方备注
//                "birthday": true,
//            "number": true,
//            "address": true,
//            "sex": true,
//            "name": true
//        },
//        "image_url": "http://shumai-picture.oss-cn-hangzhou.aliyuncs.com/%E5%95%86%E7%9B%9F/20.png"//身份证头像url，有效期一个小时
//    }
//}
//
//// 反面返回
//        {
//            "msg": "",
//    "success": true,
//    "code": 200,
//    "data":{
//            "order_no": "634416423342575616",
//        "result": 0,
//        "side": "back",
//        "info":{
//                "authority": "南昌市公安局昌北分局",//签发机关
//            "timelimit": "20180727-20380727"//身份证有效期
//        },
//        "validity":{
//                "authority": true,
//            "timelimit": true
//        }
//    }
//}

//{
//"msg": "入参错误,
//    "success": false,
//    "code": 400,
//    "data": { }
//}



    //查询返回结果
    public function search_result() {
        $uid = $this->uid;
        $result = M('User_authentication')->field(true)->where(['uid'=> $uid])->find();
        if($result) {
            $this->returnCode(0, $result);
        } else {
            $this->returnCode(1002, [], '查询结果失败！');
        }
    }


    //查询用户是否已经实名认证
    public function search_authentication() {
        $uid = $this->uid;
        $res = M('User_authentication')->field('authentication_status')->where(['uid'=> $uid])->find();
        if($res) {
            if($res['authentication_status'] == 1) {
                $result = ["is_check" => 1]; // 用户是否已经实名通过   0 未实名  1 实名通过  2实名未通过
            } else {
                $result = ["is_check" => 2]; // 用户是否已经实名通过   0 未实名  1 实名通过  2实名未通过
            }
            $this->returnCode(0, $result);
        } else {
            $result = ["is_check" => 0]; // 用户是否已经实名通过   0 未实名  1 实名通过  2实名未通过
            $this->returnCode(0, $result);
        }
    }



}