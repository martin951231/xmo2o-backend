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
    <title>申请成为分销人员</title>
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
        .certificationTop{
            padding: 0 15px;
            line-height: 45px;
            background-color: #aaa;
            color: #fff;
        }
        .titleImg {
            width: 22px;
            height: 22px;
            margin-right: 10px;
        }
        .weui-cell__ft {
            text-align: right;
            color: #999;
            padding-right: 13px;
            position: relative;
        }
        .weui-cell:after {
            content: " ";
            position: absolute;
            left: 0;
            right: 0;
            height: 1px;
            color: #e5e5e5;
            z-index: 2;
            bottom: 0;
            border-top: 1px solid #e5e5e5;
            -webkit-transform-origin: 0 100%;
            transform-origin: 0 100%;
            -webkit-transform: scaleY(.5);
            transform: scaleY(.5);
        }
        .weui-cell__ft:after {
            content: " ";
            display: inline-block;
            height: 6px;
            width: 6px;
            border-width: 2px 2px 0 0;
            border-color: #c8c8cd;
            border-style: solid;
            -webkit-transform: matrix(.71,.71,-.71,.71,0,0);
            transform: matrix(.71,.71,-.71,.71,0,0);
            position: absolute;
            top: 50%;
            margin-top: 0px;
            right: 0px;
        }
    </style>
</head>
<body>
<div class="frist"> 
    <if condition="$now_distributor">
    <dl style="padding:0 10px 10px;background-color:#fff;border-radius: 10px">
       <div class="my_order">
            <a href="{pigcms{:U('My/distributor_spread_list')}">
            <div style="padding-bottom:10px;padding-top: 0;" class="weui-cell rel frist">
                <img class="titleImg" style="margin-top:10px;float:left;" src="{pigcms{$static_path}images/new_my/spread_money.png" />
                <div style="padding-top:13px;width:60%;">我的佣金记录</div>
                <div class="weui-cell__ft" style="width: 40%;"></div>
            </div>
            </a>
        </div>
    </dl>
    <div class="certificationTop">
        <span class="fl">{pigcms{$now_distributor['add_time']|date='Y-m-d H:i:s',###}</span>
        <if condition='$now_distributor["status"] eq 0'>
            <a href="#" class="fr certificationStatic" style="color: #fff;">审核中</a>
        <elseif condition='$now_distributor["status"] eq 1'/>
            <a href="#" class="fr certificationStatic green" style="color: #fff;">已通过</a>
        <else/>
            <a href="#" class="fr certificationStatic red" style="color: #fff;">未通过</a>
        </if>
        <div class="clear"></div>
    </div>
    </if>
    <form id="submitForm" action="{pigcms{:U('distributor_apply')}" method="post">
        <input class="weui-input" type="hidden" name="pigcms_id" value="{pigcms{$now_distributor.pigcms_id}">
        <div style="padding-bottom:80px">
            <div class="weui-cells weui-cells_form" >
                <div class="weui-cell">
                    <div class="weui-cell__hd"><label class="weui-label">姓名</label></div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" type="text" name="name"   placeholder="请输入姓名" data-rules='{"required": true}' data-messages='{"required": "请输入姓名"}' value="{pigcms{$now_distributor.name}">
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd">
                        <label class="weui-label">手机号</label>
                    </div>
                    <div class="weui-cell__bd">
                         <input class="weui-input" type="text" name="phone"   placeholder="请输入手机号" data-rules='{"required": true}' data-messages='{"required": "请输入手机号"}' value="{pigcms{$now_distributor.phone}">
                    </div>
                </div>
                <div class="weui-cell">
                    <div class="weui-cell__hd">
                        <label class="weui-label">岗位</label>
                    </div>
                    <div class="weui-cell__bd">
                         <input class="weui-input" type="text" name="position"   placeholder="请输入岗位" data-rules='{"required": true}' data-messages='{"required": "请输入岗位"}' value="{pigcms{$now_distributor.position}">
                    </div>
                </div>
                <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                    <div class="weui-cell__hd">
                        <label class="weui-label">省份</label>
                    </div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" type="text" name="select2" id="province"  placeholder="请选择省份" value="{pigcms{$now_province.area_name}">
                        <input class="weui-input" type="hidden" name="province_id" value="{pigcms{$now_province.area_id}" data-rules='{"required": true}' data-messages='{"required": "请选择省份"}'>
                    </div>
                </div>
                <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                    <div class="weui-cell__hd">
                        <label class="weui-label">城市</label>
                    </div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" type="text" name="select2" id="city"  placeholder="请选择城市" value="{pigcms{$now_city.area_name}">
                        <input class="weui-input" type="hidden" name="city_id"  value="{pigcms{$now_city.area_name}" data-rules='{"required": true}' data-messages='{"required": "请选择城市"}'>
                    </div>
                </div>
                <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                    <div class="weui-cell__hd">
                        <label class="weui-label">项目城市</label>
                    </div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" type="text" name="select2" id="project_city"  placeholder="请选择项目城市" value="{pigcms{$now_project_city.id}">
                        <input class="weui-input" type="hidden" name="project_city_id" value="{pigcms{$now_project.city_name}" data-rules='{"required": true}' data-messages='{"required": "请选择项目城市"}'>
                    </div>
                </div>
                <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                    <div class="weui-cell__hd">
                        <label class="weui-label">项目</label>
                    </div>
                    <div class="weui-cell__bd">
                        <input class="weui-input" type="text" name="select2" id="project"  placeholder="请选择项目" value="{pigcms{$now_project.project_name}">
                        <input class="weui-input" type="hidden" name="project_id"  value="{pigcms{$now_project.id}" data-rules='{"required": true}' data-messages='{"required": "请选择项目"}'>
                    </div>
                </div>
            </div>
        </div>
    </form> 

    <div class="coupon_mask cursor" ></div>
    <div class="demos-content-padded">
        <a href="javascript:;" class="weui-btn weui-btn_primary submit"><if condition="$now_distributor">重新</if>提交</a>
    </div>
     <div id="agreeContent" style="display: none;">
        <div class="agreeContent"> 
            <p> {pigcms{:replace_file_domain_content(htmlspecialchars_decode($config["recruit_agreement"],ENT_QUOTES))}</p> 
        </div>
     </div>
</div>
<script>
var province_arr = JSON.parse('{pigcms{$province_arr}');
var project_city_arr = JSON.parse('{pigcms{$project_city_arr}');
var city_arr = JSON.parse('{pigcms{$city_arr}');
var project_arr = JSON.parse('{pigcms{$project_arr}');
</script>
<script>
    var View={
        init:function(){ 
            this.initClick();
            this.submit();
        },
        settings:{
            img:[],
            memory_info:[],
            window_w:375 
        },
        initClick:function(){
            $(".closePopup").on("click",function(){
                $.closePopup();
                $("#searchInput").val("");
                $("#full .weui-cells").html('')
            })


            $(".coupon_mask").click(function(){
                $(".coupon_mask").hide();
                $("#province").select("close");
                $("#city").select("close");
                $("#project_city").select("close");
                $("#project").select("close");
            });

            $("#province").select({
                title: "请选择省份",
                items: province_arr,
                onChange: function(d) {
                    $("input[name=province_id]").val(d.values);
                    $.post('{pigcms{:U("ajax_get_area")}',{area_id:d.values},function (data) {
                        city_arr = data;
                        console.log(data)
                        $("#city").select("update", {items:data})

                    },'json')

                },
                onClose: function() {
                    $(".coupon_mask").hide()
                },
                onOpen: function() {
                    $(".coupon_mask").show()
                },
            });
            $("#city").select({
                title: "请选择城市",
                items: city_arr,
                onChange: function(d) {
                    $("input[name=city_id]").val(d.values);
                },
                onClose: function() {
                    $(".coupon_mask").hide()
                },
                onOpen: function() {
                    $(".coupon_mask").show()
                },
            });
            $("#project_city").select({
                title: "请选择项目城市",
                items: project_city_arr,
                onChange: function(d) {
                    $("input[name=project_city_id]").val(d.values);
                    $.post('{pigcms{:U("ajax_get_project")}',{city_name:d.titles},function (data) {
                        $("#project").select("update", {items:data});
                    },'json')
                },
                onClose: function() {
                    $(".coupon_mask").hide()
                },
                onOpen: function() {
                    $(".coupon_mask").show()
                },
            });
            $("#project").select({
                title: "请选择项目",
                items: project_arr,
                onChange: function(d) {
                    $("input[name=project_id]").val(d.values)
                },
                onClose: function() {
                    $(".coupon_mask").hide()
                },
                onOpen: function() {
                    $(".coupon_mask").show()
                },
            });
        },
        submit:function(){
            var _this=this;
            $(".submit").on('click',function(){
                $("#submitForm").ajaxSubmit({
                    beforeSubmit: function(data, elem, options){
                        if(!Validator.getData(false,'#submitForm')) return false;

                        
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