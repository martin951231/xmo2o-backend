<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>意见反馈</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?215"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/weui.min.css"/>   
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery-1.7.2.js" charset="utf-8"></script>   
        <script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
        <link href="{pigcms{$static_path}layer/need/layer.css" type="text/css" rel="styleSheet" id="layermcss">
        <script type="text/javascript" src="{pigcms{$static_public}js/ajaxfileupload.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script> 
        <script type="text/javascript" src="{pigcms{$static_path}js/weui.min.js" charset="utf-8"></script>
        <style>
         .weui-cells_checkbox .weui-check:checked+.weui-icon-checked:before{
             color:#088fe9;
         }
         .fl{
             float:left;
         }
         .fr{
             float:right;
         }
         .clear{
             clear:both;
         }
         .submit_p{
             margin:20px 15px; 
         }
         .weui-btn_primary,.weui-btn_primary:not(.weui-btn_disabled):active,.weui-btn_primary:not(.weui-btn_disabled):visited{
             background:#088fe9;
             color:#fff;
         }  
        </style>   
    </head>
    <body> 
        <div class="page__bd">
            <div class="weui-cells__title">(必选)请选择你想反馈的问题点</div>
            <div class="weui-cells weui-cells_checkbox">
                <label class="weui-cell weui-check__label" for="s11">
                    <div class="weui-cell__hd">
                        <input type="checkbox" class="weui-check" name="checkbox1" id="s11" checked="checked">
                        <i class="weui-icon-checked"></i>
                    </div>
                    <div class="weui-cell__bd">
                        <p>功能异常:功能异常或不可用</p>
                    </div>
                </label>
                <label class="weui-cell weui-check__label" for="s12">
                    <div class="weui-cell__hd">
                        <input type="checkbox" name="checkbox1" class="weui-check" id="s12">
                        <i class="weui-icon-checked"></i>
                    </div>
                    <div class="weui-cell__bd">
                        <p>产品建议:用的不爽，我有意见</p>
                    </div>
                </label>
                <label class="weui-cell weui-check__label" for="s13">
                    <div class="weui-cell__hd">
                        <input type="checkbox" name="checkbox1" class="weui-check" id="s13">
                        <i class="weui-icon-checked"></i>
                    </div>
                    <div class="weui-cell__bd">
                        <p>安全问题:密码，隐私，欺诈等</p>
                    </div>
                </label> 
                <label class="weui-cell weui-check__label" for="s14">
                    <div class="weui-cell__hd">
                        <input type="checkbox" name="checkbox1" class="weui-check" id="s14">
                        <i class="weui-icon-checked"></i>
                    </div>
                    <div class="weui-cell__bd">
                        <p>用户终端异常:无法支付，支付慢等</p>
                    </div>
                </label>
                <label class="weui-cell weui-check__label" for="s15">
                    <div class="weui-cell__hd">
                        <input type="checkbox" name="checkbox1" class="weui-check" id="s15">
                        <i class="weui-icon-checked"></i>
                    </div>
                    <div class="weui-cell__bd">
                        <p>其他问题</p>
                    </div>
                </label>  
            </div> 
        </div> 

        <div class="page__bd" style="padding-bottom:0.7rem">
            <div class="weui-cells__title">请补充详细问题和意见</div>
            <div class="weui-cells weui-cells_form">
                <div class="weui-cell">
                    <div class="weui-cell__bd">
                        <textarea class="weui-textarea" placeholder="请输入不少于10个字的描述" id="orgAuditDesc" rows="3"></textarea>
                        <div class="weui-textarea-counter"><span id='text-count'>0</span>/200</div>
                    </div>
                </div>
            </div> 
        </div> 

        <div class="page__bd" style="padding:10px 0; background:#fff">   
            <div class="weui-cell">
                <div class="weui-cell__bd">
                    <div class="weui-uploader">
                        <div class="weui-uploader__hd">
                            <p class="weui-uploader__title">请提供相关问题的截图或照片</p>
                            <div class="weui-uploader__info">0/4</div>
                        </div>
                        <div class="weui-uploader__bd">
                            <ul class="weui-uploader__files" id="uploaderFiles">    
                            </ul>
                            <div class="weui-uploader__input-box">
                                <input  onchange="imgUpload(4)" class="weui-uploader__input" type="file" accept="image/*" multiple="">
                            </div>
                        </div>
                    </div>
                </div>
            </div> 
             
        </div>  
        
        <div class="submit_p"><a href="javascript:;" class="weui-btn weui-btn_block weui-btn_primary">提交</a></div>  
    </body> 
    <script>
        function imgUpload(limit){   
            var len=$('#uploaderFiles li').length;
            if(len<limit){  
                $.ajaxFileUpload({
                    url:"{pigcms{:U('feedback/ajax_upload_file')}",
                    secureuri:false,
                    fileElementId:name,
                    dataType: 'json',
                    success: function (data) { 
                        console.log(data.url)
                        if(data.error == 2){  
                            $("#uploaderFiles").append(' <li class="weui-uploader__file" style="background-image:url('+data.url+')"><div class="closeImg"><img src="{pigcms{$static_path}recruit/images/close.png" style="width:20px"  /></div></li>');
                           
                        }else{
                            alert(data.msg);
                        }
                    }
                }); 
            }else{
                alert('最多只能上传'+limit+'张图片');
            } 
        }  
        /*字数限制*/
        $("#orgAuditDesc").on("input propertychange", function () {
            var $this = $(this),
                    _val = $this.val(),
                    count = "";
            if (_val.length > 200) {
                $this.val(_val.substring(0, 200));
            }
            count = 200 - $this.val().length;
            $("#text-count").text(count);
        }); 
        $('.submit_p a').on('click',function(){
            var len=$('#orgAuditDesc').val().length; 
            if(len < 10){
                layer.open({
                    content: '补充详细问题和意见不少于10个字的描述'
                    ,skin: 'msg'
                    ,time: 2 //2秒后自动关闭
                });
                return false
            }

           var orgAuditDesc=$('#orgAuditDesc').val(); 
            
            // .weui-cells_checkbox .weui-check:checked+.weui-icon-checked:before
        }) 
    </script>
</html>