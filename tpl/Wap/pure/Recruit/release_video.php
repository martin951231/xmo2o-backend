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
    <title>发布视频</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <link rel="stylesheet" type="text/css" href="css/workIcon.css">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <script src="{pigcms{$static_public}activity/assets/js/common.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/simplite/simplite.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/jquery-form/jquery-plugin-form.js"></script>
    <!-- <script type="text/javascript" src="js/validator.js"></script>  --> 
    <script type="text/javascript" src="{pigcms{$static_public}js/ajaxfileupload.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
    <link href="{pigcms{$static_path}layer/need/layer.css" type="text/css" rel="styleSheet" id="layermcss">
    <link href="{pigcms{$static_path}recruit/css/video.css" type="text/css" rel="styleSheet" id="layermcss">
    <link href="{pigcms{$static_path}css/common.css?210" rel="styleSheet"> 
    <script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
     
    <script src="{pigcms{$static_path}js/iscroll-zoom.js"></script>
	<script src="{pigcms{$static_path}js/hammer.js"></script>
	<script src="{pigcms{$static_path}js/jquery.photoClip.js"></script>
	<!--[if IE]>
	<script src="{pigcms{$static_path}/js/html5shiv.min.js"></script>
	<![endif]--> 
    <style>
        .preview{
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 10000;
            background: #F1F1F1;
            display: none;
        }
        .span_2{
            padding:20px 0;
            font-size:20px;
        }
        .video_block{
            display:block;
        }
        .img_block{
            display:none;
        }
        .closeImg{
            position: absolute;
            right:0;
            top:0;
            z-index:1
        }
        .layui-m-layercont{
            max-height:360px;
            padding:20px; 
            overflow-y: scroll;
            text-align:left; 
        } 
        .loadImg{
        	background: transparent;
        	opacity: 0;
        	position: absolute;
        	left: 0;
        	top:0;
        }
        .upimgFileBtnNode1{
            position: relative;
        }
        .upimgFileBtnNode1 .closeImg{
        	display: none;
        }
        .clipArea { 
        	height: 90%;
        	display: none;
        } 
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
		.clipBtn {  
		    background: #18b4ed; 
		    outline: none;
		    color: #fff;
		    border: none;
		    font-size: 15px; 
		    padding: 10px 15px;
		    display: none;
		}
		.layui-m-layer2 .layui-m-layercont p{
			line-height: 30px;
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
                 top:0; 
                 display: none; 
                 z-index: 9999999; 
                 background: #fff;
            }
            .crop_btn{
                 position: fixed; 
                 bottom:10px;
                 right: 10px;
                 background: #3C7BC2;
                 color: #fff;
                 padding: 5px 10px;
            }
            .AndroidShow_img,.result_ul li{
                 width: 72px;
                 height: 78px;  
                 position: relative;  
                 float: left; 
                  /* border: 1px dashed #ddd; */
            }
            .AndroidShow_add,.result_ul li{
               border: 1px dashed #ddd;
                width: 72px;
                height: 78px; 
                overflow: hidden;
            }
            .result_ul li{  
                float: left;
                margin-right: 8px;
                position: relative;
                margin-bottom: 8px;
            }
            .result_ul li img.closePic{
                position: absolute;
                right:0px;
                top:0px;
                z-index: 999;
            } 
            .AndroidShow{
                height: 78px;
                text-align: center;
            }
            .btn-danger{
                background: transparent; 
                border: none;
                position: absolute;
                left: 0;
                top:10px;
                z-index: 999;
                height: 72px;
                width: 78px;
                opacity: 0; 
            }
            .AndroidShow_img img{
                width: 70px;
                height: 76px; 
                position: absolute;
                top:1px;
                left: 1px; 
            }
            .closeImg_Android{ 
                position: absolute;
                right: 0px;
                top:0px;
                z-index: 9999999;
                height: 20px;
                width: 20px;
                display: none;
            }  
         </style>  
      <script src="{pigcms{$static_path}recruit/js/cropper.min.js"></script>   
</head>
<body style="background:#eee">    
    <form method="post"  action="__SELF__" onSubmit="return check()"> 
        <div class="span_2" style="padding: 10px 0;border-bottom: 1px solid #f1f1f1; background:#fff;"> 
            <label style="margin: 0  5%"> 
                <input type="radio" name="type" checked="checked" value="0">
                <span>视频</span>
            </label>	
            <label >
                <input type="radio" name="type"  value="1"> 
                <span>图文</span>
            </label>		
        </div>

        <script>
            $('input:radio[name="type"]').click(function(){
                var val=$('input:radio[name="type"]:checked').val();
                if(val == 0){
                    $(".video_block").css('display','block');
                    $('.img_block').css('display','none'); 
                }else{
                    $(".video_block").css('display','none'); 
                    $('.img_block').css('display','block'); 
                } 
            })
        </script>  
        <input type="hidden" name="course_id" value="{pigcms{$_GET['course_id']}" />
        <div class="fill_content video_block">
            <div class="reply_tabs" id="reply_tabs">  
                <div class="tab-cont"   id="videoType">
                    <div class="imgcon" style="text-align: left; padding-left: 5px;">
                        <div class="my_video_node clearfix">
                            <div class="upimgFileBtnNode">
                                <img src="{pigcms{$static_path}recruit/images/upimg.png" id="upvideoFileBtn" class="upimgFileBtn imgview" alt="" style="height: 78px;">
                                <input type="file" accept="video/*" id="videoUploadFile" onchange="videoUpload()" style="display: none;" name="videoFile" value="选择文件上传"/>
                            </div>
                            <div class="my_prop_imgitem" style="<if condition="$now_course['videoUrl']">display: block;<else/>display: none;</if>"> 
                                <div class="imgviewNode" onclick="preview()" style="<if condition="$now_course['videoUrl']">background:url({pigcms{:file_domain().$now_course['video_img']})</if>"> 
                                    <img id="imgId" src="<if condition="$now_course['videoUrl']">{pigcms{$static_path}recruit/images/open.png</if>" class="imgview" style="height: 78px;" alt="">
                                <input type="hidden" name="videoUrl" id="videoUrl" value="{pigcms{$now_course['videoUrl']}"/>
                                </div>
                            </div>

                        </div>
                        <span style=" padding-left: 10px; color: red;">仅支持上传2G以内的视频文件</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="weui-cells weui-cells_form" >   
            <div class="weui-cell">
                <div class="weui-cell__hd"><label class="weui-label">标题</label></div>
                <div class="weui-cell__bd">
                    <input class="weui-input" type="text" name="title"   placeholder="请输入标题" data-rules='{"required": true}' data-messages='{"required": "请输入标题"}' value="{pigcms{$now_course['title']}">
                </div>
            </div> 
            
            <div class="weui-cell">
                <div class="weui-cell__hd"><label class="weui-label">分类</label></div>
                <div class="weui-cell__bd">
                    <select style="background-color: #fff;border: 0;height: 25px;" name="cat_id">
                        <volist name="category" id="vo">
                        <option value="{pigcms{$vo.id}" <if condition="$vo['id'] eq $now_course['cat_id']">selected</if>>{pigcms{$vo.name}</option>
                        </volist>
                    </select>
                    <!-- <input class="weui-input" type="text" name="title"   placeholder="请输入标题" data-rules='{"required": true}' data-messages='{"required": "请输入标题"}'> -->
                </div>
            </div>
            <div class="weui-cell">  
                <div class="weui-cell__bd">
                    <div class="weui-uploader">
                        <div class="weui-uploader__hd">
                            <p class="weui-uploader__title">详情描写</p> 
                        </div>
                    
                        <div class="fill_content">
                            <textarea  name="content" class="weui-textarea"  rows="3"  placeholder="多行文本框" data-rules='{"required": true}' data-messages='{"required": "内容必填"}'>{pigcms{$now_course['content']}</textarea>
                        </div>
                    </div>
                </div> 
            </div>   
            <div class="weui-cell">
                <div class="weui-cell__bd">
                    <div class="weui-uploader">
                        <div class="weui-uploader__hd">
                            <p class="weui-uploader__title">封面图</p>
                                <div class="weui-uploader__info">最多上传1张，建议尺寸8:5</div>
                        </div>  
                         
                        <div class="fill_content">
                            <div class="reply_tabs" id="reply_tabs">  
                                <div class="tab-cont" style="display:block;" id="imgType">
                                     <div class="imgcon IosShow">
                                        <div class="my_prop_imgitem_node1 clearfix">
                                            <div class="upimgFileBtnNode upimgFileBtnNode1">
                                            	<div class="closeImg"><img src="{pigcms{$static_path}recruit/images/close.png" style="width:20px"  /></div>
                                                <img src="<if condition="$now_course['cover_img']">{pigcms{:file_domain().$now_course['cover_img']}<else/>{pigcms{$static_path}recruit/images/upimg.png</if>" id="upimgFileBtnCover" class="upimgFileBtn imgview" alt="" style="height: 78px;">
                                                <input type="hidden" name="imgList1[]" value="{pigcms{$now_course['cover_img']}" />
                                                <input type="file" class="loadImg" id="imgUploadFileCover" onchange="javascript:loadImg(1);" name="imgFile"  accept="image/*" multiple/>
                                            </div>  
                                        </div>
                                    </div>
                                    
                                    <!-- 安卓 -->
                                     <div class="AndroidShow AndroidShow1" data-state="1">
                                         <div class="text-center AndroidShow_img">
                                            <div class="AndroidShow_add">
                                                <div class="closeImg closeImg_Android"><img src="{pigcms{$static_path}recruit/images/close.png" style="width:20px; height: 20px;"  /></div>
                                                           <label for="input" class="btn btn-danger" id=""> 
                                                               <input type="file" id="input" class="sr-only">
                                                           </label>
                                                           <img src="<if condition="$now_course['cover_img']">{pigcms{:file_domain().$now_course['cover_img']}<else/>{pigcms{$static_path}recruit/images/upimg.png</if>" alt="" id="result"> 
                                            </div> 
                                        </div> 
                                        <div class="select_img"> 
                                             <img src="" id="photo"> 
                                             <label class="btn btn-primary crop_btn" onclick="crop(1)">确定</label>  
                                        </div>
                                    </div>
                                    <!-- 安卓 end -->
                                </div>
                            </div>
                        </div>   
                        
                    </div>
                </div>
            </div>
            <div class="weui-cell">
                <div class="weui-cell__bd">
                    <div class="weui-uploader">
                        <div class="weui-uploader__hd">
                            <p class="weui-uploader__title">详情图片上传</p>
                            <div class="weui-uploader__info">最多上传6张</div>
                        </div> 
                        
                         <div class="fill_content IosShow">
                            <div class="reply_tabs" >  
                                <div class="tab-cont" style="display:block;" id="imgType">
                                    <div class="imgcon">
                                        <div class="my_prop_imgitem_node2 clearfix">
                                            <div class="upimgFileBtnNode">
                                                <img src="{pigcms{$static_path}recruit/images/upimg.png" id="upimgFileBtn" class="upimgFileBtn imgview" alt="" style="height: 78px;">
                                                <input type="file" id="imgUploadFile" class="file loadImg"  onchange="javascript:loadImg(2);"   name="imgFile" value=" "/>  
                                            </div>  
                                            <volist name="now_course['imgUrl']" id="vo">
                                                <div class="my_prop_imgitem"> <div class="imgviewNode"><div class="closeImg"><img src="{pigcms{$static_path}recruit/images/close.png" style="width:20px"  /></div><img src="{pigcms{:file_domain().$vo}" class="imgview" style="height: 78px;">
                                                    <input type="hidden" name="imgList2[]" value="{pigcms{$vo}" /></div></div>
                                            </volist>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                         
                          <!-- 安卓 -->
                          <div class="AndroidShow AndroidShow2" data-state="2">
                               <ul class="result_ul"></ul>
                              <div class="text-center AndroidShow_img">
                                 <div class="AndroidShow_add"> 
                                     <label for="input1" class="btn btn-danger" id=""> 
                                         <input type="file" id="input1" class="sr-only">
                                     </label>
                                 <img src="<if condition="$now_course['cover_img']">{pigcms{:file_domain().$now_course['cover_img']}<else/>{pigcms{$static_path}recruit/images/upimg.png</if>" alt=""> 
                             </div>  
                                 
                             <div class="select_img"> 
                                  <img src="" id="photo1"> 
                                  <label class="btn btn-primary crop_btn" onclick="crop(2)">确定</label>  
                             </div>
                         </div>
                         <!-- 安卓 end --> 
                       
                    </div>
                </div>
            </div>  
        </div> 
        
        <div class>
            <label for="weuiAgree" class="weui-agree fl" style="padding-right:0">
                    <input id="weuiAgree" type="checkbox" class="weui-agree__checkbox"> 
                    <span class="weui-agree__text">
                        阅读并同意
                    </span>
                </label>  
                <a href="javascript:void(0);" class="fl" id="agree" style="font-size:12px; line-height:32px; color:#586c94">《用户协议》</a>
                <div class="clear"></div>
            </div> 
            <div style="margin:10px 10px 20px;"><input class="comn-submit fabu_btn" type="submit" value="发 布"></div> 
            <div id="progress" class="progress" style="margin-bottom:15px;display:none;">
                <div class="label">0%</div>
        </div> 
     </form>
     <div id="agreeContent" style="display: none;">
        <div class="agreeContent"> 
            <p> {pigcms{:replace_file_domain_content(htmlspecialchars_decode($config["recruit_agreement"],ENT_QUOTES))}</p> 
        </div>
     </div> 
</body>  
 <!--裁剪图片-->
	<article class="htmleaf-container">
		<div class="clipArea" id="clipArea1"></div>
		<div class="clipArea" id="clipArea2"></div> 
		<div class="foot-use"> 
			<button class="clipBtn" id="clipBtn">确定</button>
			<button class="clipBtn" id="clipBtn2">确定</button>
		</div>
		<div id="view"></div>
	</article>
 <!--裁剪图片 end-->  
 <script> 
 if(motify.checkAndroid()){ 
        $('.IosShow').hide();
        $('.AndroidShow1').show();  
        $('.AndroidShow2').show();   
        var initCropper = function (img, input){  
            console.log(img,input,'input');
            var $image = img;
            var options = {
                aspectRatio: 1.6, // 纵横比
                viewMode: 2 
            };
            $image.cropper(options);
            var $inputImage = input;
            var uploadedImageURL;
            if (URL) {
                // 给input添加监听
                $inputImage.change(function () {   
                 if( img.selector == '#photo'){  
                     $('.AndroidShow1 .select_img').show();
                     console.log('1')
                 }else{ 
                     if($('.result_ul li').length >= 5){
                         $('.AndroidShow2 .AndroidShow_add').hide();
                     }else{
                         $('.AndroidShow2 .AndroidShow_add').show();
                     }
                     $('.AndroidShow2 .select_img').show();
                      console.log('2')
                 }  
                    var files = this.files;
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
       function crop(option){
           if(option == 1){  
               $('.AndroidShow1 .select_img').hide();
                $('.closeImg_Android').show();
                   var $image = $('#photo');
                   var $target = $('#result');
                    var cas = $image.cropper('getCroppedCanvas',{
                        width:160, // 裁剪后的长宽
                        height:100
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
                                $('input[name="imgList1[]"]').val(data.url);  
                            }
                        }
                    })   
            
       //        .toBlob(function(blob){
       //                 // 裁剪后将图片放到指定标签 
       //                 $target.attr('src', URL.createObjectURL(blob));
       //         });
           }else if(option == 2){ 
               $('.AndroidShow2 .select_img').hide(); 
                   var $image = $('#photo1');
                   var $target = $('#result1');
                   var cas = $image.cropper('getCroppedCanvas',{
                        width:160, // 裁剪后的长宽
                        height:100
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
                         }else{
                           $('.result_ul').append('<li><img src="'+data.url+'" style="width:75px; height:75px" /> <img src="{pigcms{$static_path}recruit/images/close.png" style="width:20px;" class="closePic"/> <input type="hidden" name="imgList2[]" value="'+data.url+'" /></li>');
                         }
                     }
                 }); 
                     
             } 
       }
  
        $(function(){    
            initCropper($('#photo'),$('#input')); 
            initCropper($('#photo1'),$('#input1'));  
            $('.closeImg_Android').click(function(){ 
                $('#result').attr('src','{pigcms{$static_path}recruit/images/upimg.png'); 
                $(this).hide();
            }) 
            
            $(".result_ul ").on("click","li",function(){    
                if($('.result_ul li').length <7 ){
                    $('.AndroidShow2 .AndroidShow_add').show()
                }
                 $(this).remove()
            })
    
            
        });
 }else{
     $('.IosShow').show();
     $('.AndroidShow1').hide();  
     $('.AndroidShow2').hide();   
   $("#clipArea1").photoClip({
       size:[320,200],
       width:320,
       height:200,
      	outputSize: [320, 200],
       outputQuality:1,
       lrzOption:{quality:1},
       file: "#imgUploadFileCover", 
       view: "#view",
       ok: "#clipBtn",
       loadStart: function() {},
       loadComplete: function() {},
       clipFinish: function(dataURL) { 
            $('.upimgFileBtnNode1 .closeImg').show();
       } 
   }); 
   
   $("#clipArea2").photoClip({
       width:320,
       height:200,
       size:[320,200],
       outputQuality:1,
       lrzOption:{quality:1},
   	   outputSize: [320, 200], 
       file: "#imgUploadFile", 
       view: "#view",
       ok: "#clipBtn2", 
       loadStart: function() {},
       loadComplete: function() {},
       clipFinish: function(dataURL) { 
            console.log(dataURL)
       } 
   }); 
   $("#clipBtn").click(function(){
       layer.open({
           type: 2,
           shadeClose:false,
           content: '上传中'
       }); 
       $.ajax({
           type:'post',
           url:"{pigcms{:U('ajax_upload_file_base64')}",
           data:{'file':imgsource},
           dataType:'json',
           success:function(data){
               setTimeout(function(){layer.closeAll();},1000);  
               if (data.error) { 
                   layer.open({
                       type: 1,
                       shadeClose:false,
                       content: data.msg
                   }); 
               }else{
                   $(".upimgFileBtnNode1 img.upimgFileBtn").empty(); 
                   $('.upimgFileBtnNode1 img.upimgFileBtn').attr('src','{pigcms{:file_domain()}'+data.url);  
                   $('input[name="imgList1[]"]').val(data.url);  
                   $(".htmleaf-container").hide(); 
               }
           }
       }) 
   })
   
   $("#clipBtn2").click(function(){
       var len=$('.my_prop_imgitem_node2>.my_prop_imgitem').length;
       if(len<6){ 
           layer.open({
               type: 2,
               shadeClose:false,
               content: '上传中'
           }); 
           
          $.ajax({
               type:'post',
               url:"{pigcms{:U('ajax_upload_file_base64')}",
               data:{'file':imgsource},
               dataType:'json',
               success:function(data){
                   setTimeout(function(){layer.closeAll();},1000);  
                   if (data.error) { 
                       layer.open({
                           type: 1,
                           shadeClose:false,
                           content: data.msg
                       }); 
                   }else{
                       $(".my_prop_imgitem_node2").append('<div class="my_prop_imgitem"><div class="closeImg"><img src="{pigcms{$static_path}recruit/images/close.png" style="width:20px"  /></div><div class="imgviewNode"><img src="{pigcms{:file_domain()}'+data.url+'" class="imgview" style="height: 78px;"><input type="hidden" name="imgList2[]" value="'+data.url+'" /></div></div>');
                       $(".htmleaf-container").hide(); 
                   }
               }
           }); 
       }else{
           layer.open({
               type: 1,
               shadeClose:false,
               content: '最多只能上传6张图片'
           });
       }
   
   })	
   	 	
   function loadImg(type){   
   	if(type ==1){
   		$('.htmleaf-container').show();
   		$('#clipArea1').show();
   		$("#clipBtn").show();
   		$('#clipArea2').hide();
   		$("#clipBtn2").hide();
   	} else if(type ==2){ 
   		var len=$('.my_prop_imgitem_node2 .my_prop_imgitem').length;
   		if(len <6){
   			$('.htmleaf-container').show();
   			$('#clipArea2').show();
   		    $("#clipBtn2").show();
   		    $('#clipArea1').hide();
   		    $("#clipBtn").hide();
   		}else{
   			layer.open({
   	            type: 2,
   	            shadeClose:false,
   	            content: '最多6张'  
   	       });  
   	       setTimeout(function(){layer.closeAll();},1000); 
   	        return false;
   		}
   	}
   }  
   $('.upimgFileBtnNode1 .closeImg').click(function(){
   	$('.upimgFileBtnNode1 img.upimgFileBtn').attr('src','{pigcms{$static_path}recruit/images/upimg.png');
   	$(this).hide();
   })	  
 }     
 </script>
<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
<script>
    $('#agree').on('click', function(){
        layer.open({  
            title: [
            '用户协议',
            'background-color: #ff4d00; color:#fff;'
            ],  
            btn: ['知道了']
            ,yes: function(index){
                location.reload();
                layer.close(index);
            },
            content: $('.agreeContent').html()
        });
    }); 
</script> 
<input type="hidden" id="previewUrl" value="{pigcms{$now_course['videoUrl']}"> 
<div class="preview">
    <script type="text/javascript" src="{pigcms{$static_public}ckplayer/ckplayer.js"></script>
    <div class="video" style="width: 100%;height: 400px; margin-top: 20%"></div>
    <div style="text-align: center; margin-top: 15px;" onclick="$('.preview').css('display','none')"><button class="comn-submit fabu_btn"> 关闭</button></div>
    <script type="text/javascript">
        function preview(){
            var url = $("#previewUrl").val();
            var videoObject = {
                container: '.video',//“#”代表容器的ID，“.”或“”代表容器的class
                variable: 'player',//该属性必需设置，值等于下面的new chplayer()的对象
                poster:'pic/wdm.jpg',//封面图片
                video:url
            };
            var player=new ckplayer(videoObject);
            $('.preview').css('display','block');
        }
    </script>
   </div>
 
 <script type='text/javascript'>
    $("#upvideoFileBtn").click(function(){
        $("#videoUploadFile").click();
    }) 
    
    function outputObj(obj) {  
        var description = "";  
        for (var i in obj) {  
            description += i + " = " + obj[i] + "\n";  
        }  
        alert(description);  
    } 

    function videoUpload(e){
        // alert(motify.checkAndroid())
        if(motify.checkAndroid()&&motify.checkApp()){
            // alert(1)
            // window.pigcmspackapp.closewebview(2);
            // return false;
        }  
        var file = $("#videoUploadFile")[0].files[0];
        console.log(file)
        layer.open({
            type: 2,
            shadeClose:false,
            content: '上传中(<text id="progress">0%</text>)'
        });
        var formData = new FormData();
        formData.append('file',  file);
        $.ajax({
            url: "{pigcms{:U('Recruit/ajax_upload_video')}" ,
            type: 'POST',
            dataType: 'json',
            data: formData,
            xhr: function(){ //获取ajaxSettings中的xhr对象，为它的upload属性绑定progress事件的处理函数
                myXhr = $.ajaxSettings.xhr();
                if(myXhr.upload){ //检查upload属性是否存在
                    //绑定progress事件的回调函数
                    myXhr.upload.addEventListener('progress',progressHandlingFunction, false);
                }
                return myXhr; //xhr对象返回给jQuery使用
             },
            contentType: false,
            processData: false, 
            success: function (data) {
                setTimeout(function(){layer.closeAll();},1000); 
                if(data.error == 2){
                    $("#imgId").attr('src','{pigcms{$static_path}recruit/images/open.png');
                    $(".imgviewNode").css('background','url({pigcms{:file_domain()}'+data.image+')');
                    $("#videoUrl").val(data.url);
                    $(".my_prop_imgitem").css('display','block');
                    $("#previewUrl").val('{pigcms{:file_domain()}'+data.url);
                }else{
                    alert(data.msg);
                } 
            },
            error: function (data) {
                console.log(data.errorMsg)
            }
        });
    }

    $("#upimgFileBtn").click(function(){
        $("#imgUploadFile").click(); 
    })  

    $("#upimgFileBtnCover").click(function(){
        $("#imgUploadFileCover").click();
    })
  //上传进度回调函数：
    function progressHandlingFunction(e) {
        if (e.lengthComputable) {
            // $('progress').attr({value : e.loaded, max : e.total}); //更新数据到进度条
            percent = e.loaded/e.total*100;
            $('#progress').html(percent.toFixed(2) + "%");
        }
    }
    function imgUpload(name,limit,type){   
        var len=$('.my_prop_imgitem_node'+type+'>.my_prop_imgitem').length;
        if(len<limit){ 
            layer.open({
                type: 2,
                shadeClose:false,
                content: '上传中'
            }); 
            
            $.ajaxFileUpload({
                url:"{pigcms{:U('Recruit/ajax_upload_file')}",
                secureuri:false,
                fileElementId:name,
                dataType: 'json',
                success: function (data) {
                    setTimeout(function(){layer.closeAll();},1000);   
                    console.log(data.url)
                    if(data.error == 2){  
                        $(".my_prop_imgitem_node"+type).append('<div class="my_prop_imgitem"><div class="closeImg"><img src="{pigcms{$static_path}recruit/images/close.png" style="width:20px"  /></div><div class="imgviewNode"><img src="'+data.url+'" class="imgview" style="height: 78px;"><input type="hidden" name="imgList'+type+'[]" value="'+data.url+'" /></div></div>');
                    }else{
                        alert(data.msg);
                    }
                }
            }); 
        }else{
            alert('最多只能上传'+limit+'张图片');
        }
     
    } 
    // 删除照片按钮点击
    $('.my_prop_imgitem_node1.clearfix').off('click','.closeImg').on('click','.closeImg',function(e){
        $(this).parents('.my_prop_imgitem').remove();
    });

    $('.my_prop_imgitem_node2.clearfix').off('click','.closeImg').on('click','.closeImg',function(e){
        $(this).parents('.my_prop_imgitem').remove();
    });  

    function delfile(obj){
        alert(obj);
    }

    function check(){
        var type=$('input:radio[name="type"]:checked').val();
        if (type==0) {
            if ($('input[name=videoUrl]').val() == '') {
                motify.log('请上传视频');
                return false;
            }
        }
        if ($('input[name=title]').val() == '') {
            motify.log('请输入标题');
            return false;
        }
        if ($('textarea[name=content]').val() == '') {
            motify.log('请输入内容');
            return false;
        }
        if ($('input[name=imgList1]').val() == '') {
            motify.log('请上传封面图');
            return false;
        }
        if (!$('#weuiAgree').is(':checked')) {
            motify.log('请同意用户协议');
            return false;
        }
        return true;
    }
</script>
</html>
 
 