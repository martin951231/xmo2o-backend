<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name='apple-touch-fullscreen' content='yes'/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="format-detection" content="address=no"/>
    <title>我的简历</title> 
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}/recruit/css/workIcon.css"> 
    <script src="{pigcms{$static_path}villagegroup/js/weui.min.js"></script> 
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/jquery-form/jquery-plugin-form.js"></script>
    <script src="{pigcms{$static_public}activity/assets/js/common.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/simplite/simplite.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/validator.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}recruit/js/recruit.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js"></script>
    
    <script src="{pigcms{$static_path}js/iscroll-zoom.js"></script>
	<script src="{pigcms{$static_path}js/hammer.js"></script>
    <script src="{pigcms{$static_path}js/lrz.all.bundle.js"></script> 
	<script src="{pigcms{$static_path}js/jquery.photoClip.js"></script>
	<!--[if IE]>
	<script src="{pigcms{$static_path}/js/html5shiv.min.js"></script>
	<![endif]--> 
    <style>
        body{ background: #f4f4f4;} 
        .pd0{padding:0px}
        .pd-r10{padding-right:10px}
        .mgt10{margin-top:10px}
        .font13{font-size:13px}
        .color_999{color:#999}
        .mgt0{margin-top:0px}
        .after:after,.before:before{content:none !important;}
        .pd0-5{padding:0px 5px}
        .align_center{text-align:center}
        .qiuzhi .weui-cell{    height: 80px;padding: 20px 15px;box-sizing: border-box;}
        .qiuzhi .weui-cell input{position:absolute;width:100%;height:100%;z-index:10;opacity: 0}
        .demos-content-padded{position: fixed;
            bottom: 0px;
            padding:15px;
            left: 0px;
            right: 0px;
            background:#fff;
            z-index: 999;
        }
        .weui-btn_primary {
            background-color: #ff4d00;
        }
        .coupon_mask {position: fixed;top: 0px;left: 0px;background: rgba(0,0,0,0.7);z-index: 888;width: 100%;height: 100%;}
        .rel{position:relative}
        .weui-btn_primary:not(.weui-btn_disabled):active {color: #FFFFFF !important;background-color: rgba(255, 77, 0, 0.4)}
        .my_star{height:20%}
        .my_photo{background: url(https://hf.pigcms.com/upload/adver/000/000/030/590c4b6ad74da520.png) no-repeat center;height: 70px;
            background-size: 100% 100%; position:relative }
        .imgBox{
            width: 75px;
            -webkit-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
            -webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            position: absolute;
            left: 50%;
            top: 90%;
            height: 75px;
            border-radius: 50%;
            border: 1px solid #3C7BC2;
            background:#fff
        }
        .imgBox input {width: 100%;
            height: 75px;
            opacity: 0;}
        .iconfont{
            -webkit-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
            -webkit-transform-origin: 0 0;
            transform-origin: 0 0;
            position: absolute;
            left: 50%;
            top: 50%;
            border-radius: 50%;
            font-size: 45px;
            color:#3C7BC2;
            z-index: -1;
        }
        .img_content{
            color: #3C7BC2;
            text-align: center;
            padding-top: 30px;
        }
        .flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; align-items: center;  }
        .flex_one{  flex:1;  -webkit-box-flex: 1;  -webkit-flex: 1;  }
        .weui-cells_radio .weui-check + .weui-icon-checked:before {font-family: Muiicons;display: block;content:url('{pigcms{$static_path}recruit/images/checkBtn.png');font-size: 16px;}
        .weui-cells_radio .weui-check:checked + .weui-icon-checked:before { font-family: Muiicons;display: block;content:url('{pigcms{$static_path}recruit/images/checkBtnHover.png');color: #09BB07;font-size: 16px;}
        .weui-cells_radio .weui-check + .weui-icon-checked:before{ color:#999}
        .pd_15 {
           padding: 10px 15px;
        }
        .cursor {
            cursor: pointer !important;
        } 
        .coupon_mask {
            position: fixed;
            top: 0px;
            left: 0px;
            background: rgba(0,0,0,0.7);
            z-index: 888;
            width: 100%;
            height: 100%;
            display: none;
        }
        .bgImg{height:100%;    overflow: hidden;}
        .bgImg img{
            height: 100%;
            width: 100%;
            border-radius: 50%;
            /*display:none*/
        }
        .demos-content-padded {
            position: fixed;
            bottom: 0px;
            padding: 15px;
            left: 0px;
            right: 0px;
            background: #fff;
            z-index: 99;
        }
        .weui-btn_primary {
            background-color: #ff4d00;
        }
        .weui-vcode-btn{
            background:#ff4d00;
            color:#fff;
            border:none;
            border-radius:2px;
            font-size:14px;
            height:30px;
            line-height:30px;
        } 
        
        #clipArea { height: 90%;} 
        .htmleaf-container{
		    margin: 0 auto;
		    text-align: center;
		    overflow: hidden;
		    width: 100%;
		    height: 100%;
		    position: fixed;
		    top: 0;
		    background: #000;
		    display: none;
		    z-index: 9999999;
		}
		.foot-use{
			text-align: right;
			padding: 0 30px;
		}
		#clipBtn {  
		    background: #18b4ed; 
		    outline: none;
		    color: #fff;
		    border: none;
		    font-size: 15px; 
		    padding: 10px 15px;
		}
		.bgImg{
			position: relative;
		}
		#logox{
			width: 75px;
			height: 75px; 
			display: block; 
		}
		#logox img{
			width: 75px;
			height: 75px; 
		}
		#setImagePreview{
			width: 75px;
			height: 75px; 
			display: block; 
			position: absolute;
			left: 0;
			top:0;
			z-index: 999;
			background: transparent;
		}
		.bgImg{
			background:#3C7BC2;
			border-radius:100%;
		}
    </style>
    
    <link href="https://cdn.bootcss.com/cropper/3.1.3/cropper.min.css" rel="stylesheet">
    <!-- <link href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"> -->
    <style> 
        #photo {
            max-width: 100%;
        } 
        #result {
            width: 150px;
            height: 150px;
        }
       .select_img{
            position: fixed;
            left:0;
            width:100%;
            height:100%;
            bottom:0; 
            display: none; 
            z-index: 999;
       }
       .crop_btn{
            position: fixed; 
            bottom:10px;
            right: 10px;
            background: #3C7BC2;
            color: #fff;
            padding: 5px 10px;
       }
       .AndroidShow_img{
            width: 75px;
            height: 75px; 
            border-radius: 50%; 
            background: #3C7BC2;
            position: relative;
            margin: 0 auto;
       }
       .AndroidShow{
           height: 75px;
           text-align: center;
       }
       .btn-danger{
           background: transparent; 
           border: none;
           position: absolute;
           left: 0;
           top:10px;
           z-index: 999;
           height: 75px;
           width: 75px;
           opacity: 0; 
       }
       .AndroidShow_img img{
           width: 75px;
           height: 75px;
           border-radius: 100%;
           position: absolute;
           top:0;
           left: 0;
           border: 1px solid #3C7BC2; 
       }
       .AndroidShowPhoto{ 
       } 
       
    </style> 
    <!-- <script src="{pigcms{$static_path}js/jquery.min.js_3_2.js"></script> --> 
    <script src="{pigcms{$static_path}recruit/js/cropper.min.js"></script> 
    <!-- <script src="{pigcms{$static_path}recruit/js/bootstrap.min.js"></script>  -->
</head>
<body>    
<!-- end --> 
   <div class="bodyDiv">
       <form id="submitForm" action="{pigcms{:U('my_resume_base')}" method="post" >
        <input type="hidden" name="resume_id" value="{pigcms{$now_resume.resume_id}">
           <div style="padding-bottom:80px">
               <div class="my_star IosShow">
                   <div class="my_photo">
                       <div class="imgBox"> 
                           <div class="bgImg"> 
                               <a class="logo" id="logox">
									<img id="bgl" src="<if condition="$now_resume['avatar']">{pigcms{:file_domain().$now_resume['avatar']}<else/>{pigcms{$static_path}images/logo_n.png</if>" /> 
                                </a> 
                                <input  type="hidden" class="setImagePreview" name="avatar" value="{pigcms{$now_resume['avatar']}"> 
                                <input id="setImagePreview" type="file" name="flieImg"  onchange="loadImg1()"   accept="image/*" />  
                           </div> 
                       </div>
                   </div> 
               </div>
               
               <!-- 安卓的时候 -->
               <div class="AndroidShow" > 
                   <div class="text-center AndroidShow_img">
                       <label for="input" class="btn btn-danger" id=""> 
                           <input type="file" id="input" class="sr-only">
                       </label>
                       <img src="<if condition="$now_resume['avatar']">{pigcms{:file_domain().$now_resume['avatar']}<else/>{pigcms{$static_path}images/logo_n.png</if>" alt="" id="result">
                   </div> 
                   <div class="select_img"> 
                        <img src="" id="photo"> 
                        <label class="btn btn-primary crop_btn" onclick="crop()">裁剪图片</label>  
                   </div>
               </div>
                <!-- 安卓的时候 end -->
                
                <p class="img_content">头像,一份好工作的开始</p>
               
               <div class="weui-cells weui-cells_form weui-cells_radio">
                   <div class="weui-cell">
                       <div class="weui-cell__hd"><label class="weui-label">简历名</label></div>
                       <div class="weui-cell__bd">
                           <input class="weui-input" type="text"  placeholder="请输入简历名" name="resume_name"  data-rules='{"required":true}' data-messages='{"required":"请输入简历名"}' value="{pigcms{$now_resume.resume_name}">
                       </div>
                   </div>
                   <div class="weui-cell">
                       <div class="weui-cell__hd"><label class="weui-label">姓名</label></div>
                       <div class="weui-cell__bd">
                           <input class="weui-input" type="text"  placeholder="请输入姓名" name="name"  data-rules='{"required":true}' data-messages='{"required":"请输入姓名"}' value="{pigcms{$now_resume.name}">
                       </div>
                   </div>
                   <div class="weui-cell weui-check__label sex" >
                       <div class=" weui-cell__hd">
                           <label class="weui-label">性别</label>

                       </div>
                       <div class="weui-cell__bd flex">
                           <label class=" " >
                               <div style="margin-right:20px">
                                   男
                                   <input type="radio" class="weui-check" name="sex" value="1" <if condition="$now_resume['sex'] neq 2">  checked="checked" </if> data-rules='{"required":true}' data-messages='{"required":"请选择性别"}'>
                                   <i class="weui-icon-checked"></i>
                               </div>
                           </label>
                           <label class=" " >
                               <div>
                                   女
                                   <input type="radio" class="weui-check" name="sex" value="2" data-rules='{"required":true}' data-messages='{"required":"请选择性别"}' <if condition="$now_resume['sex'] eq 2">  checked="checked" </if>>
                                   <i class="weui-icon-checked"></i>
                               </div>
                           </label>
                       </div>
                   </div> 
                   <div class="weui-cell">
                       <div class="weui-cell__hd">
                           <label class="weui-label">手机号</label>
                       </div>
                       <div class="weui-cell__bd">
                           <input class="weui-input" type="tel" placeholder="请输入手机号" name="phone" data-rules='{"required":true,"isMobile":true}' data-messages='{"required":"请输入手机号码","isMobile":"请输入有效的手机号"}' value="{pigcms{$now_resume.phone}">
                       </div>
                       <if condition="$config['sms_key'] != ''">
                       <div class="weui-cell__ft">
                           <input type="button" class="weui-vcode-btn" onclick="settime(this)" value='获取验证码' /> 
                       </div>
                        </if>
                   </div>
                    <if condition="$config['sms_key'] != ''">
                   <div class="weui-cell ">
                       <div class="weui-cell__hd"><label class="weui-label">验证码</label></div>
                       <div class="weui-cell__bd">
                           <input class="weui-input" type="number" placeholder="请输入验证码"   data-rules='{"required":true}' data-messages='{"required":"请输入短信验证码"}' name="sms_code">
                       </div>

                   </div>
                    </if>
                   <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                       <div class="weui-cell__hd">
                           <label for="" class="weui-label">出生日期</label>
                       </div>
                       <div class="weui-cell__bd">
                           <input class="weui-input" type="text"  id="birthday" readonly  placeholder="请选择出生日期"  data-rules='{"required":true}' data-messages='{"required":"请选择出生日期"}' value="{pigcms{$now_resume.birthday}">
                           <input class="weui-input" type="hidden" name="birthday"  value="{pigcms{$now_resume.birthday}">
                       </div>
                   </div>
                   <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                       <div class="weui-cell__hd">
                           <label for="" class="weui-label">最高学历</label>
                       </div>
                       <div class="weui-cell__bd">
                           <input class="weui-input" type="text"  id="education" readonly  placeholder="请选择学历要求" data-rules='{"required":true}' data-messages='{"required":"请选择学历要求"}' value="{pigcms{$now_resume.qualifications_txt}">
                           <input class="weui-input" type="hidden" name="qualifications" value="{pigcms{$now_resume.qualifications}">
                       </div>
                   </div>
                   <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                       <div class="weui-cell__hd">
                           <label for="" class="weui-label">工作时间</label>
                       </div>
                       <div class="weui-cell__bd">
                           <input class="weui-input" type="text" id="workHouse" readonly  placeholder="请选择工作时间"  data-rules='{"required":true}' data-messages='{"required":"请选择工作时间"}' value="{pigcms{$now_resume.work_years_txt}">
                           <input class="weui-input" type="hidden" name="work_years"  value="{pigcms{$now_resume.work_years}">
                       </div>
                   </div>
               </div>

            <div class="mgt10 qiuzhi">
                <div class="weui-cells">
                    <div class="weui-panel__hd after before">求职意向</div>
                    <div class="weui-cell before zhiwei">
                        <a class="rel position goUrl" data-url="{pigcms{:U('position_select',array('type'=>'resume'))}" style="display:block;width:100%">
                            <input type="hidden" name="position_cat_id"/>
                            <div class="weui-cell__bd color_999 align_center">
                                <p>期望职位</p>
                            </div>
                        </a>

                    </div>
                    <div class="weui-cell money">
                        <input type="text" name="salary"/>
                        <div class="weui-cell__bd color_999 align_center">
                            <p>期望薪资</p>
                        </div>
                    </div>
                    <div class="weui-cell area">
                        <div class="weui-cell__bd color_999 align_center goUrl" data-url="{pigcms{:U('adres_map',array('type'=>'resume'))}">
                            <p>求职区域</p>
                        </div> 
                        <input type="hidden" name="address" value="{pigcms{$now_resume.address}">
                        <input type="hidden" name="province" value="{pigcms{$now_resume.province}">
                        <input type="hidden" name="city" value="{pigcms{$now_resume.city}">
                        <input type="hidden" name="area" value="{pigcms{$now_resume.area}">
                        <input type="hidden" name="lat" value="{pigcms{$now_resume.lat}">
                        <input type="hidden" name="lng" value="{pigcms{$now_resume.lng}">
                    </div>
                </div>
            </div>
           </div>
       </form> 
       <div class="demos-content-padded">
           <a href="javascript:;" class="weui-btn weui-btn_primary submit">下一步</a>
       </div>
   </div>
   <div class="coupon_mask cursor" ></div>  
   <!-- 裁剪图片 -->
  <article class="htmleaf-container">
		<div id="clipArea"></div>
		<div class="foot-use"> 
			<button id="clipBtn">确定</button>
		</div>
		<div id="view"></div>
	</article>
   <!--裁剪图片 end-->   
  <script>   
  if(motify.checkAndroid()){  
      $('.IosShow').hide(); 
      $('.AndroidShow').show();  
        var initCropper = function (img, input){  
            var $image = img;
            var options = {
                aspectRatio: 1, // 纵横比
                viewMode: 2,
//              preview: '.img-preview' // 预览图的class名
            };
            $image.cropper(options);
            var $inputImage = input;
            var uploadedImageURL;
            if (URL) {
                // 给input添加监听
                $inputImage.change(function () {  
                    $('.select_img').show();
                    var files = this.files;
                    console.log(files,'files');
                    var file;
                    if (!$image.data('cropper')) {
                        return;
                    }
                    if (files && files.length) {
                        file = files[0];
                        // 判断是否是图像文件
                        if (/^image\/\w+$/.test(file.type)) {
                            // 如果URL已存在就先释放
                            if (uploadedImageURL) {
                                URL.revokeObjectURL(uploadedImageURL);
                            }
                            uploadedImageURL = URL.createObjectURL(file);
                            // 销毁cropper后更改src属性再重新创建cropper
                            $image.cropper('destroy').attr('src', uploadedImageURL).cropper(options);
                            $inputImage.val('');
                        } else {
                          window.alert('请选择一个图像文件！');
                      }
                  }
              });
            } else {
                $inputImage.prop('disabled', true).addClass('disabled');
            }
        }
        var crop = function(){ 
           $('.select_img').hide();
            var $image = $('#photo');
            var $target = $('#result');
            var cas = $image.cropper('getCroppedCanvas',{
                width:75, // 裁剪后的长宽
                height:75
            })
            var base64 = cas.toDataURL('image/jpeg'); // 转换为base64
            console.log(base64,'base64');
            $.ajax({
                type:'post',
                url:"{pigcms{:U('ajax_upload_file_base64')}",
                data:{'file':base64},
                dataType:'json',
                success:function(data){
                    if (data.error) {
                        alert(data.msg)
                    }else{ 
                       $target.attr('src', data.url); 
                       data_json.avatar = data.url;
                       $.cookie('user_address_resume', JSON.stringify(data_json));
                    }
                }
            })   
        } 
        $(function(){
            initCropper($('#photo'),$('#input'));
        }); 

  }else{  
     $('.IosShow').show();
     $('.AndroidShow').hide();  
     var obUrl = '' 
    $("#clipArea").photoClip({
      width: 199,
      height: 199, 
      size:[199,199],
      outputQuality:1,
      lrzOption:{quality:1},
      outputSize: [199, 199], 
      file: "#setImagePreview",
      view: "#view",
      ok: "#clipBtn",
      rotateFree:false,   
      loadStart: function() { 
      },
      loadComplete: function() { 
      },
      clipFinish: function(dataURL) { 
      }
    }); 
    $("#clipBtn").click(function(){   
      loadImg();
    }) 
    function loadImg(){  
        $.ajax({
            type:'post',
            url:"{pigcms{:U('ajax_upload_file_base64')}",
            data:{'file':imgsource},
            dataType:'json',
            success:function(data){
                if (data.error) {
                    alert(data.msg)
                }else{
                    $("#logox").empty();
                    $('#logox').append('<img src="{pigcms{:file_domain()}' + data.url + '" align="absmiddle" style="width: 100%;height: 100%; border-radius:100%">'); 
                    $(".htmleaf-container").hide();
                    $('.setImagePreview').val(data.url);
                    data_json.avatar = data.url;
                    $.cookie('user_address_resume', JSON.stringify(data_json));
                }
            }
        })
    }
    
    function loadImg1(){ 
        $(".htmleaf-container").show(); 
    } 
    
  } 
 </script> 
   <!-- 上传预览模板 -->
   <script type="text/html" id="uploadImageTemplate">
       <& for(var i = 0; i < _this.length; i++){ &>
       <& var obj = _this[i]; &>
       <li class="weui-uploader__file  rel" style="background-image:url(<&= obj.src &>)"><a href="javascript:void(0);" class="upload_delete item" title="删除"><i class="weui-icon-warns iconfont">&#xe612;</i></a></li>
       <& } &>
   </script>

    <script type="text/html" id="model">
        <p class="font13"><&= _this.content&></p>
        <p style="color:#000"><&= _this.value&></p>
    </script>

    <script src="{pigcms{$static_path}js/jquery.cookie.js" type="text/javascript" charset="UTF-8"></script>
    <script>
        // 工作年限
        var work_years_arr = JSON.parse('{pigcms{$work_years_arr}');
        // 学历
        var qualifications_arr = JSON.parse('{pigcms{$qualifications_arr}');
        // 月薪
        var salary_arr = JSON.parse('{pigcms{$salary_arr}');
        var data_json = {};
        var View={
           init:function(){
               this.refresh();
               this.initClick();
            //    this.uploaderFile();
               this.submit()
           },
           setting:{
               salary:'0',
               salary_txt:'',
               work_years:'0',
               work_years_txt:'',
               qualifications:'0',
               qualifications_txt:'',
               position_cat_id:'0',
               position_name:'',
               province :'0',
               area :'0',
               city :'0',
               lng :null,
               lat :null,
               birthday :'',
               address :'',
               age:[]
           },
           refresh:function(){
                var self=this;
                var year=new Date().getFullYear();
                // var info = JSON.parse($.cookie('user_address_resume'));

                // console.log('初次加载数据')
                //    console.log(info)
               for(var i=(year-60);i<(year-16);i++){
                   self.setting.age.push(i)
               }
               $("#education").select({
                   title: "请选择最高学历",
                   items: qualifications_arr,
                   onChange: function(d) {
                       $("#education").val(d.titles)
                       $("input[name=qualifications]").val(d.values)
                       data_json.qualifications = d.values;
                       data_json.qualifications_txt = d.titles;
                        $.cookie('user_address_resume', JSON.stringify(data_json));
                   },
                   onClose: function() {
                       $(".coupon_mask").hide()
                        $(".bodyDiv").css({'overflow':'','height':''});
                   },
                   onOpen: function() {
                       $(".coupon_mask").show()
                        $(".bodyDiv").css({'overflow':'hidden','height':$(window).height()});
                   },
               });
               $("#workHouse").select({
                   title: "请选择工作时间",
                   items: work_years_arr,
                   onChange: function(d) {
                       $("input[name=work_years]").val(d.values)
                        data_json.work_years = d.values;
                       data_json.work_years_txt = d.titles;
                        $.cookie('user_address_resume', JSON.stringify(data_json));

                   },
                   onClose: function() {
                       $(".coupon_mask").hide()
                        $(".bodyDiv").css({'overflow':'','height':''});
                   },
                   onOpen: function() {
                        $(".coupon_mask").show()
                        $(".bodyDiv").css({'overflow':'hidden','height':$(window).height()});
                   },
               });
                $("input[name=salary]").select({
                    title: "请选择您期望的薪资范围",
                    items: salary_arr,
                    onChange: function(d) {
                        if(d.values){
                            var cat_data = Simplite.getTemplate('model');
                            var cat_all = Simplite.compile(cat_data)({'content':'薪资期望',value:d.titles} );
                            $('.money .weui-cell__bd').html(cat_all);
                            $("input[name=salary]").val(d.values)

                            data_json.salary = d.values;
                            data_json.salary_txt = d.titles;
                            $.cookie('user_address_resume', JSON.stringify(data_json));
                        }
                     },
                     onClose: function() {
                        $(".coupon_mask").hide()
                        $(".bodyDiv").css({'overflow':'','height':''});
                     },
                     onOpen: function() {
                        $(".coupon_mask").show()
                        $(".bodyDiv").css({'overflow':'hidden','height':$(window).height()});
                     },
                 });  
           },
           initClick:function(){
                var detail = new Object();
                var self=this;
                $(".coupon_mask").click(function(){
                    $(".coupon_mask").hide();
                    $("input[name=salary]").select("close");
                    $("#workHouse").select("close");
                    $("#education").select("close");  
                    // $('.weui-animate-slide-up').hide();  
                    $('.dateTime').hide();
                     console.log('2222222', $('.weui-picker__hd>a:first'))
                     $('.weui-picker__hd>a:first').trigger('click')
                });

                $(".goUrl").click(function(){
                    var url = $(this).data('url');
                    data_json.birthday = $('input[name="birthday"]').val();
                    $.cookie("user_address_resume", JSON.stringify(data_json));
                    location.href = url;
                });

                $('#birthday').on('click', function(){
                    $(".coupon_mask").show();
                    $('.dateTime').show();
                    var today = new Date()
                    var defaultValue = [today.getFullYear(), today.getMonth() + 1, today.getDate()]
                    console.log(defaultValue)
                    weui.datePicker({
                         start: 1900, // 从今天开始
                         end: today.getFullYear(),
                         defaultValue: defaultValue,
                         className:'dateTime',
                         onChange: function(result){
                            console.log(result)
                         },
                         onConfirm: function(result){
                            $('#birthday').val(result[0] + '-' + result[1] + '-' + result[2])
                            $("input[name=birthday]").val(result[0] + '-' + result[1] + '-' + result[2]);
                            data_json.birthday = result[0] + '-' + result[1] + '-' + result[2];
                            $.cookie('user_address_resume', JSON.stringify(data_json));
                            $(".coupon_mask").hide()
                         },
                         onClose: function(result){  
                            $(".coupon_mask").hide()
                         },
                         id: 'birthday'
                     }); 
                 }) 
                 
           }, 
           submit:function(){
               
           }
       }
       window.View=View;
       window.addEventListener("pageshow", function(){
           View.init();
           // if(/iPhone|iPod/i.test(navigator.userAgent)) {
           //     //手机端
           //    $("input[name=file]").removeAttr("capture")
           // } else {
           //     //浏览器 
           // }

       });  
       
       $(".submit").on('click',function(){ 
            $("#submitForm").ajaxSubmit({
                beforeSubmit: function(data, elem, options){
                    console.log(data,'data')
                    if(!Validator.getData(false,'#submitForm')) return false;
                    if ($('input[name=position_cat_id]').val()=='' || $('input[name=position_cat_id]').val()==0) {
                        $.alert('请选择期望职位');
                        return false;
                    }

                    if ($('input[name=salary]').val()=='' || $('input[name=salary]').val()==0) {
                        $.alert('请选择期望薪资');
                        return false;
                    }

                    if ($('input[name=area]').val()=='' || $('input[name=area]').val()==0) {
                        $.alert('请选择求职区域');
                        return false;
                    }

                    layer.open({
                        type: 2,
                        shadeClose:false,
                        content: '提交中...'
                    });
                },
                success: function(res) { 
                    console.log(res,'res')
                    res = JSON.parse(res);
                    if (res.code) {
                        layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:res.msg,btn:['好的'],shadeClose:false,yes:function(){
                            if(res.url!='' && typeof(res.url) != 'undefined'){
                                window.location.href= res.url;
                            }
                            layer.closeAll();
                        }});
                    }else{
                        // layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:res.msg,btn:['好的'],shadeClose:false,yes:function(){
                            if(res.url!='' && typeof(res.url) != 'undefined'){
                                window.location.href= res.url;
                            }
                            //layer.closeAll();
                        // }});
                    }

                },
                error: function() { 
                }
            });

        })

        // 验证码倒计时 
        var countdown = 60;
        function settime(obj) {
            if($("input[name='phone']").val()==''){
                layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'手机号码不能为空！',btn:['好的'],shadeClose:false,yes:function(){ 
                    layer.closeAll();
                }});
            }else{
              if(countdown==60){
                $.ajax({
                    url: "{pigcms{:U('Generate')}",
                    type: 'POST',
                    dataType: 'json',
                    data: {phone: $("input[name='phone']").val()},
                    success:function(date){
                        if(date.error_code){
                            layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:date.msg,btn:['好的'],shadeClose:false,yes:function(){
                                layer.closeAll();
                            }});
                        }
                    }
                });
              }
            if (countdown == 0) {
                obj.removeAttribute("disabled");
                $('.weui-vcode-btn').css('background','#ff4d00')
                obj.value="获取验证码";
                countdown = 60;
                return;
            } else {
                obj.setAttribute("disabled", true);
                $('.weui-vcode-btn').css('background','#ddd');
                obj.value="发送(" + countdown + "s)";
                countdown--;
            }
            setTimeout(function() {
                settime(obj)
            },1000)
        }
    }

     //选择完地址替换地址
    setInterval(function(){
        var info = $.cookie('user_address_resume');
       if (typeof(info)!='undefined') { 
            info = JSON.parse(info);
        }else{
            info = {};
        }
        if(!info){
            info = {};
        }
        console.log(info.avatar)
        if (info.avatar) {
            $('#logox').html('<img src="{pigcms{:file_domain()}' + info.avatar + '" align="absmiddle" style="width: 100%;height: 100%; border-radius:100%">'); 
            $('.setImagePreview').val(info.avatar)
            data_json.avatar = info.avatar;
        }   
        if (info.birthday) {
            $('input[name="birthday"]').val(info.birthday);
            $('#birthday').val(info.birthday);
            data_json.birthday = info.birthday;
        }    

        if (info.work_years&&info.work_years_txt) {
            $('input[name="work_years"]').val(info.work_years);
            $('#workHouse').val(info.work_years_txt);
            data_json.work_years = info.work_years;
            data_json.work_years_txt = info.work_years_txt;
        }  

        if (info.qualifications&&info.qualifications_txt) {
            $('input[name="qualifications"]').val(info.qualifications);
            $('#education').val(info.qualifications_txt);
            data_json.qualifications = info.qualifications;
            data_json.qualifications_txt = info.qualifications_txt;
        }
        if (info.salary_txt&&info.salary) {
            var cat_data = Simplite.getTemplate('model');
            var cat_all = Simplite.compile(cat_data)({'content':'期望薪资',value:info.salary_txt} );
            $('.money .weui-cell__bd').html(cat_all);
            $("input[name=salary]").val(info.salary)
            data_json.salary = info.salary;
            data_json.salary_txt = info.salary_txt;
        }

        if (info.position_name) {
            var cat_data = Simplite.getTemplate('model');
            var cat_all = Simplite.compile(cat_data)({'content':'期望职位',value:info.position_name} );
            $('.position .weui-cell__bd').html(cat_all);
            $("input[name=position_cat_id]").val(info.position_cat_id)
            data_json.position_cat_id = info.position_cat_id;
            data_json.position_name = info.position_name;
        }

        if (info.lng) {
            var cat_data = Simplite.getTemplate('model');
            var cat_all = Simplite.compile(cat_data)({'content':'求职区域',value:info.address} );
            $('.area .weui-cell__bd').html(cat_all);
            $('input[name="address"]').val(info.address);
            $('input[name="province"]').val(info.province);
            $('input[name="area"]').val(info.area);
            $('input[name="city"]').val(info.city);
            $('input[name="lng"]').val(info.lng);
            $('input[name="lat"]').val(info.lat);
            data_json.address = info.address;
            data_json.province = info.province;
            data_json.area = info.area;
            data_json.city = info.city;
            data_json.lng = info.lng;
            $('.address').html(info.address);
        }
    },200);
  </script>
</body>
</html>