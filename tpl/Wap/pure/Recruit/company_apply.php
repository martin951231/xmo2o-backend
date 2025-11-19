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
    <title>企业认证</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}/classifynew/css/weui.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}/recruit/css/workIcon.css">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="{pigcms{$static_path}/classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <script src="{pigcms{$static_public}activity/assets/js/common.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/simplite/simplite.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/jquery-form/jquery-plugin-form.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}/recruit/js/validator.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.cookie.js"></script>
    <style>
        body {
            font-size: 14px;
        }
        .rel{position:relative}
        .weui-uploader__file a {
            position: absolute;
            top: 0;
            right: 0;
            width: 20px;
            height: 20px;
        }
        a ,a:visited,a:hover{color:#999}
        .weui-cells_form{margin-top:0px}
        .weui-cells {
            font-size: 16px;
        }
        .flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; align-items: center;  }
        .flex_one{  flex:1;  -webkit-box-flex: 1;  -webkit-flex: 1;  }
        .mg0{margin:0px}
        .pd0{padding:0px}
        .pd_15{    padding: 10px 15px;}
        .color_9{color:#999}
        .border-l-n{border-left:none !important;}
        .font14{font-size:14px}
        .bg_d{background-color: #ddd;}
        .demos-content-padded{position: fixed;
            bottom: 0px;
            padding:15px;
            left: 0px;
            right: 0px;
            background:#fff;
        }
        .weui-btn_primary {
            background-color: #ff4d00;
        }
        .main_color, .toolbar .picker-button, .weui-cells_radio .weui-check:checked + .weui-icon-checked:before {
            color: #ff4d00;
        }
        .test_box {
            width: 100%;
            min-height: 20px;
            max-height: 250px;
            margin-left: auto;
            margin-right: auto;
            outline: 0;
            line-height: 23px;
            word-wrap: break-word;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .goUrl{position:absolute;width:100%;height:100%;z-index:10}
        .cursor{cursor:pointer}
        .weui-btn_primary:not(.weui-btn_disabled):visited {color: #FFFFFF !important;}
        .weui-btn_primary:not(.weui-btn_disabled):active {color: #FFFFFF !important;background-color: rgba(255, 77, 0, 0.4)}
        #full .weui-cells{overflow:scroll}
        .weui-vcode-btn:active {
            color: #999;
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
        .cursor {
            cursor: pointer !important;
        }
        #full .weui-cell:active{background:#dedede}
        .allPosition .weui-cells{margin-top:0px}
        .allPosition .right .weui-cell{    background: rgba(238,238,238,0.4);}
        .allPosition .flex_one{overflow-y: scroll;height:100%}
        .demos-content-padded{z-index: 99;}

        .fl{
            float:left
        }
        .fr{
            float:right
        }
        .clear{
            clear:both
        }
        .layui-m-layercont{
            max-height:360px;
            padding:20px; 
            overflow-y: scroll;
            text-align:left;
            
        }
    </style>
</head>
<body>
<div class="frist">
    <form id="submitForm" action="{pigcms{:U('company_apply')}" method="post">
        <div style="padding-bottom:80px">
            <div class="weui-cells weui-cells_form" >
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">企业名称</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" type="text" name="company_name"   placeholder="请输入企业名称" data-rules='{"required": true}' data-messages='{"required": "请输入企业名称"}' value="{pigcms{$now_company.company_name}">
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd">
                        <label class="weui-label">法人名称</label>
                    </div>
                    <div class="weui-cell__bd">
                         <input class="weui-input" type="text" name="legal_person_name"   placeholder="请输入法人名称" data-rules='{"required": true}' data-messages='{"required": "请输入法人名称"}' value="{pigcms{$now_company.legal_person_name}">
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd">
                        <label class="weui-label">手机号</label>
                    </div>
                    <div class="weui-cell__bd">
                         <input class="weui-input" type="text" name="phone"   placeholder="请输入手机号" data-rules='{"required": true}' data-messages='{"required": "请输入手机号"}' value="{pigcms{$now_company.phone}">
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd">
                        <label class="weui-label">身份证号</label>
                    </div>
                    <div class="weui-cell__bd">
                         <input class="weui-input" type="text" name="ID_number"   placeholder="请输入身份证号" data-rules='{"required": true}' data-messages='{"required": "请输入身份证号"}' value="{pigcms{$now_company.ID_number}">
                    </div>
                </div>
                 <div class="weui-cell">
                    <div class="weui-cell__hd">
                        <label class="weui-label">公司地址</label>
                    </div>
                    <div class="weui-cell__bd">
                        <a href="{pigcms{:U('adres_map',array('type'=>'company'))}" target="_self" class="goUrl"></a>
	                    <div class="test_box" contenteditable="true"><?php if(!empty($now_company['adress'])): ?><?php echo $now_company['adress']; ?><?php else : ?>请选择工作地点<?php endif; ?><br /></div>
	                    <input type="hidden" name="address" value="{pigcms{$now_company.address}">
                        <input type="hidden" name="province" value="{pigcms{$now_company.province}">
	                    <input type="hidden" name="city" value="{pigcms{$now_company.city}">
	                    <input type="hidden" name="area" value="{pigcms{$now_company.area}">
	                    <input type="hidden" name="lat" value="{pigcms{$now_company.lat}">
	                    <input type="hidden" name="lng" value="{pigcms{$now_company.lng}">
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd" id="color-gray">
                        <label class="weui-label">详细地址</label>
                    </div>
                    <div class="weui-cell__bd">
                         <input class="weui-input" type="text" name="detail"   placeholder="请输入详细地址" data-rules='{"required": true}' data-messages='{"required": "请输入地址"}' value="{pigcms{$now_company.detail}">
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd">
                        <label class="weui-label">店铺面积</label>
                    </div>
                    <div class="weui-cell__bd">
                         <input class="weui-input" type="text" name="acreage"   placeholder="请输入店铺面积" data-rules='{"required": true}' data-messages='{"required": "请输入店铺面积"}' value="{pigcms{$now_company.acreage}">
                    </div>
                </div>
                 
                 
                <div class="weui-cell">
                    <div class="weui-cell__bd">
                        <div class="weui-uploader">
                            <div class="weui-uploader__hd">
                                <p class="weui-uploader__title">营业执照上传</p>
                                  <div class="weui-uploader__info">最多上传2张</div>
                            </div>
                            <div class="weui-uploader__bd">
                                <ul class="weui-uploader__files" id="uploaderFiles">

                                </ul>
                                <div class="weui-uploader__input-box">
                                    <input id="uploaderInput" class="weui-uploader__input" type="file" name="license_pic[]" accept="image/*" multiple="" >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                 <div class="weui-cell">
                    <div class="weui-cell__bd">
                        <div class="weui-uploader">
                            <div class="weui-uploader__hd">
                                <p class="weui-uploader__title">店铺环境上传</p>
                                  <div class="weui-uploader__info">最多上传6张</div>
                            </div>
                            <div class="weui-uploader__bd">
                                <ul class="weui-uploader__files" id="uploaderFiles1"> 
                                </ul>
                                <div class="weui-uploader__input-box">
                                    <input id="uploaderInput1" class="weui-uploader__input" type="file" name="company_pic[]" accept="image/*" multiple="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>

            <div class>
                <label for="weuiAgree" class="weui-agree fl" style="padding-right:0">
                    <input id="weuiAgree" type="checkbox" class="weui-agree__checkbox" data-rules='{"required": true}' name="xieyi" data-messages='{"required": "请同意服务协议"}'> 
                    <span class="weui-agree__text">
                        阅读并同意
                    </span>
                </label>  
                <a href="javascript:void(0);" class="fl" id="agree" style="font-size:12px; line-height:32px; color:#ff4d00">《服务协议》</a>
                <div class="clear"></div>
            </div>

        </div>
    </form> 

    <div class="coupon_mask cursor" ></div>
    <div class="demos-content-padded">
        <a href="javascript:;" class="weui-btn weui-btn_primary submit">提交</a>
    </div>
     <div id="agreeContent" style="display: none;">
        <div class="agreeContent"> 
            <p> {pigcms{:replace_file_domain_content(htmlspecialchars_decode($config["recruit_agreement"],ENT_QUOTES))}</p> 
        </div>
     </div>
</div>
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

    //选择完地址替换地址
    setInterval(function(){
        var info = $.cookie('user_address_company');
        if (info!=null) {
            info = JSON.parse(info);
        }
        if(!info){
            info = {};
        }
        if (info.lng) {
            $('input[name="address"]').val(info.address);
            $('input[name="province"]').val(info.province);
            $('input[name="area"]').val(info.area);
            $('input[name="city"]').val(info.city);
            $('input[name="lng"]').val(info.lng);
            $('input[name="lat"]').val(info.lat);
            $('.test_box').html(info.address);
        }
    },500);
</script>
<!-- 上传预览模板 -->
<script type="text/html" id="uploadImageTemplate">
    <& for(var i = 0; i < _this.length; i++){ &>
    <& var obj = _this[i]; &>
    <li class="weui-uploader__file  rel" style="background-image:url(<&= obj.src &>)"><a href="javascript:void(0);" class="upload_delete item" title="删除"><i class="weui-icon-warns iconfont">&#xe612;</i></a></li>
    <& } &>
</script> 
<script>
    var View={
        init:function(){ 
            this.uploaderFile();
            this.uploaderFile1();
            this.submit();
        },
        settings:{
            img:[],
            memory_info:[],
            window_w:375 
        }, 
        uploaderFile:function(){
            var self=this;
            //  上传图片
            var $removeContainer = $('#uploaderFiles');  
            APP.Uploader({
                element: '#uploaderInput',
                removeContainer: $removeContainer,
                onValidate: function() {
                    return {
                        invalid: false,
                        message: ''
                    }
                   
                },
                onSelectValid: function(item, list) {
                    console.log(list)
                    console.log(list.length)
                    if (list.length > 2) { 
                    	$.toast("不能超过2张", 1000);
			            return false;
			        }else{
                        APP.render({
                            element: $removeContainer,
                            templateId: 'uploadImageTemplate',
                            replaceAll: false,
                            data: [{
                                src: item.src,
                                info: item.value
                            }]
                        });
                        console.log(item)
                        $removeContainer.find('.weui-uploader__file').last().append(item.file);
                        self.settings.img=list;
			        } 
                   
                },
                onDetail:function(list){ 
                    self.settings.img=list
                }
            }); 
        },
        uploaderFile1:function(){
            var self=this;
            //  上传图片
            var $removeContainer = $('#uploaderFiles1');  
            APP.Uploader({
                element: '#uploaderInput1',
                removeContainer: $removeContainer,
                onValidate: function() {
                    return {
                        invalid: false,
                        message: ''
                    }
                   
                },
                onSelectValid: function(item, list) { 
                	if (list.length > 6) { 
                		$.toast("不能超过6张", 1000);
			            return false;
			        }else{
			         APP.render({
                        element: $removeContainer,
                        templateId: 'uploadImageTemplate',
                        replaceAll: false,
                        data: [{
                            src: item.src,
                            info: item.value
                        }]
                    });

			          $removeContainer.find('.weui-uploader__file').last().append(item.file);
                      self.settings.img=list	
			        }  
                },
                onDetail:function(list){ 
                    self.settings.img=list
                }
            }); 
        },
        submit:function(){
            var _this=this;
            $(".submit").on('click',function(){
                $("#submitForm").ajaxSubmit({
                    beforeSubmit: function(data, elem, options){
                        if(!Validator.getData(false,'#submitForm')) return false;

                        if ($('input[name=area]').val()=='' || $('input[name=area]').val()==0) {
                            $.alert('请选择公司地址');
                            return false;
                        }
                        layer.open({
                            type: 2,
                            shadeClose:false,
                            content: '提交中...'
                        });
                    },
                    success: function(res) {
                        res = JSON.parse(res);
                        if (res.code) {
                            layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:res.msg,btn:['好的'],shadeClose:false,yes:function(){
                                console.log(typeof(res.url));return false;
                                if(res.url!='' && typeof(res.url) != 'undefined'){
                                    window.location.href= res.url;
                                }
                                layer.closeAll();
                            }});
                        }else{
                            layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:res.msg,btn:['好的'],shadeClose:false,yes:function(){
                                if(res.url!='' && typeof(res.url) != 'undefined'){
                                    window.location.href= res.url;
                                }
                                layer.closeAll();
                            }});
                        }
                    },
                    error: function() {

                    }
                });

            })
        },
    }
    window.View=View;
    View.init(); 
</script>
</body>
</html>