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
    <title>发布全职职位</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}classifynew/css/weui.css">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}/recruit/css/workIcon.css">
    <script type="text/javascript" src="https://apps.bdimg.com/libs/jquery/1.9.0/jquery.min.js"></script>
    <script src="{pigcms{$static_path}classifynew/js/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <script src="{pigcms{$static_public}activity/assets/js/common.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/simplite/simplite.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}activity/assets/js/components/jquery-form/jquery-plugin-form.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}/recruit/js/validator.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
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
            max-height: 100px;
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
	.weui-cells_radio .weui-check + .weui-icon-checked:before {font-family: Muiicons;display: block;content:url('http://www.group.com/tpl/Wap/pure/static/recruit/images/checkBtn.png');font-size: 16px;}
        .weui-cells_radio .weui-check:checked + .weui-icon-checked:before { font-family: Muiicons;display: block;content:url('http://www.group.com/tpl/Wap/pure/static/recruit/images/checkBtnHover.png');color: #09BB07;font-size: 16px;}
        .weui-cells_radio .weui-check + .weui-icon-checked:before{ color:#999}

    </style>
</head>
<body>
<form id="submitForm" action="{pigcms{:U('position_edit')}" method="post">
    <input type="hidden" name="position_id" value="{pigcms{$now_position.position_id}">
    <div style="padding-bottom:80px">
        <div class="weui-cells weui-cells_form" >
            <div class="weui-cell">
                <div class="weui-cell__hd"><label class="weui-label">我要招聘</label></div>
                <div class="weui-cell__bd">
                    <input class="weui-input select_position" type="text" id="position_cat" name="zhiwei" readonly data-rules='{"required": true}' data-messages='{"required": "请输入职位"}'>
                    <input class="weui-input" type="hidden" name="position_cat_id" >
                </div>
                <div class="weui-cell__ft cursor search">
                    <a class="select_position"> 
                        <i class="weui-icon-warns iconfont">&#xe7b7;</i>
                    </a>
                </div>
            </div>
            <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                <div class="weui-cell__hd">
                    <label class="weui-label">月薪</label>
                </div>
                <div class="weui-cell__bd">
                    <input class="weui-input" type="text" name="select2" id="salary"  placeholder="请选择月薪">
                    <input class="weui-input" type="hidden" name="salary" >
                </div>
            </div>
           
            <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                <div class="weui-cell__hd">
                    <label for="" class="weui-label">学历要求</label>
                </div>
                <div class="weui-cell__bd">
                    <input class="weui-input" type="text" name="select3" id="qualifications"  placeholder="请选择学历要求">
                    <input class="weui-input" type="hidden" name="qualifications" >
                </div>
            </div>
            <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                <div class="weui-cell__hd">
                    <label for="" class="weui-label">工作年限</label>
                </div>
                <div class="weui-cell__bd">
                    <input class="weui-input" type="text" name="select4" id="work_years"  placeholder="请选择工作年限">
                    <input class="weui-input" type="hidden" name="work_years" >
                </div>
            </div>
            <div class="weui-cell weui-cell_warns">
                <div class="weui-cell__hd"><label for="" class="weui-label">工作地点</label></div>
                <div class="weui-cell__bd rel" style="overflow:hidden">
                    <a href="javascript:void(0);" target="_self" class="goUrl"></a>
                    <div class="test_box" contenteditable="true"><?php if(!empty($now_position['adress'])): ?><?php echo $now_position['adress']; ?><?php else : ?>请选择工作地点<?php endif; ?><br /></div>
                    <input type="hidden" name="address" value="{pigcms{$now_position.address}">
                    <input type="hidden" name="province" value="{pigcms{$now_position.province}">
                    <input type="hidden" name="city" value="{pigcms{$now_position.city}">
                    <input type="hidden" name="area" value="{pigcms{$now_position.area}">
                    <input type="hidden" name="lat" value="{pigcms{$now_position.lat}">
                    <input type="hidden" name="lng" value="{pigcms{$now_position.lng}">
                </div>
                <div class="weui-cell__ft cursor">
                    <a class="goUrl" target="_self"><i class="weui-icon-warns iconfont">&#xe61c;</i></a>
                </div>
            </div>

            <div class="weui-cell weui-cell_select weui-cell_select-after pd_15">
                <div class="weui-cell__hd">
                    <label for="" class="weui-label">福利待遇</label>
                </div>
                <div class="weui-cell__bd"> 
                    <textarea class="weui-textarea"  name="select2" id="welfare" style="padding-right:10px; "  placeholder="请选择福利待遇"></textarea> 
                    <input class="weui-input" type="hidden" name="welfare" >
                </div>
            </div>
            
            <div class="weui-cell weui-cell_warns">
                <div class="weui-cell__hd"><label for="" class="weui-label">职位描述</label></div>
                <div class="weui-cell__bd weui-text">
                    <textarea class="weui-textarea" placeholder="请输入文本" name="desc" rows="5">{pigcms{$now_position.desc}</textarea>
                    <!-- <div class="weui-textarea-counter font14">已输入<span class="length color_r">0</span><span>个字</span></div> -->
                </div>
            </div>
           <!--  <div class="weui-cell">
                <div class="weui-cell__bd">
                    <div class="weui-uploader">
                        <div class="weui-uploader__hd">
                            <p class="weui-uploader__title">图片上传</p>
                        </div>
                        <div class="weui-uploader__bd">
                            <ul class="weui-uploader__files" id="uploaderFiles">

                            </ul>
                            <div class="weui-uploader__input-box">
                                <input id="uploaderInput" class="weui-uploader__input" type="file" name="file[]" accept="image/*" multiple="">
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
</form>
<div class="coupon_mask cursor" ></div>
<div class="demos-content-padded">
    <a href="javascript:;" class="weui-btn weui-btn_primary submit">发布</a>
</div>
<div id="full" class="weui-popup__container ">
    <div class="weui-popup__modal rel">
        <div style="padding-bottom:80px">
            <div class="weui-search-bar" id="searchBar">
                <form class="weui-search-bar__form">
                    <div class="weui-search-bar__box">
                        <i class="weui-icon-search"></i>
                        <input type="text" class="weui-search-bar__input" id="searchInput" placeholder="搜索" required="">
                        <a href="javascript:" class="weui-icon-clear" id="searchClear"></a>
                    </div>
                    <label class="weui-search-bar__label" id="searchText">
                        <i class="weui-icon-search"></i>
                        <span>搜索</span>
                    </label>
                </form>
                <a href="javascript:" class="weui-search-bar__cancel-btn" id="searchCancel">取消</a>
            </div>
            <div class="weui-cells">

            </div>
        </div>
        <div class="demos-content-padded close">
            <a href="javascript:;" class="weui-btn weui-btn_primary closePopup ">关闭</a>
        </div>
    </div>
</div>
<!-- 上传预览模板 -->
<script type="text/html" id="uploadImageTemplate">
    <& for(var i = 0; i < _this.length; i++){ &>
    <& var obj = _this[i]; &>
    <li class="weui-uploader__file  rel" style="background-image:url(<&= obj.src &>)"><a href="javascript:void(0);" class="upload_delete item" title="删除"><i class="weui-icon-warns iconfont">&#xe612;</i></a></li>
    <& } &>
</script>
<!-- 职位搜索 -->
<script type="text/html" id="zhiwei">
    <div class="weui-cell">
        <div class="weui-cell__bd">
            <p><&= obj.content &></p>
        </div>
        <div class="weui-cell__ft">销售</div>
    </div>
</script>
    <script src="{pigcms{$static_path}js/jquery.cookie.js" type="text/javascript" charset="UTF-8"></script>
<script>
    var position_cat_id = {pigcms{$now_position['position_cat_id']|intval};
    if (position_cat_id>0) {
        var position = {
            id:position_cat_id,
            name:"{pigcms{$now_position['position_name']}"
        };
        sessionStorage.setItem("selectPosition",JSON.stringify(position));
    }
    $(":input").blur(function(){
        window.scroll(0, 0);
    });

    $(".goUrl").click(function(){
        before_leave();
        location.href = "{pigcms{:U('adres_map', array('type'=>'position_edit'))}";
    });
    $(".select_position").click(function(){
        before_leave();
        location.href = "{pigcms{:U('position_select',array('type'=>'position_edit'))}";
    });
    function before_leave(){
        var detail = new Object();
        detail.name = $('input[name="name"]').val();
        detail.province = $('input[name="province"]').val();
        detail.area = $('input[name="area"]').val();
        detail.city = $('input[name="city"]').val();
        detail.lng = $('input[name="lng"]').val();
        detail.lat = $('input[name="lat"]').val();
        detail.address = $('input[name="address"]').val();
                    
        detail.work_years = $('input[name="work_years"]').val();
        detail.qualifications = $('input[name="qualifications"]').val();
        detail.salary = $('input[name="salary"]').val();
        detail.welfare = $('input[name="welfare"]').val();

        detail.work_years_txt = $('#work_years').val();
        detail.qualifications_txt = $('#qualifications').val();
        detail.salary_txt = $('#salary').val();
        detail.welfare_txt = $('#welfare').val();

        $.cookie("user_address_position_edit", JSON.stringify(detail));
    }

    //选择完地址替换地址
    setInterval(function(){ 
        // 选中的职位
        var selectPosition = JSON.parse(sessionStorage.getItem("selectPosition"));
        if (selectPosition.id) {
            $('#position_cat').val(selectPosition.name);
            $('input[name=position_cat_id]').val(selectPosition.id);
        }

        var info = $.cookie('user_address_position_edit');
        if (typeof(info)=='undefined' || info==0) {
            info = {};
        }else{
            info = JSON.parse(info);
            if(!info){
                info = {};
            }
        }

        if ($('input[name="work_years"]').val() == '') {
            $('input[name="work_years"]').val(info.work_years);
            $('#work_years').val(info.work_years_txt);
        }
        
        if ($('input[name="salary"]').val() == '') {
            $('input[name="salary"]').val(info.salary);
            $('#salary').val(info.salary_txt);
        } 
        if ($('input[name="qualifications"]').val() == '') {
            $('input[name="qualifications"]').val(info.qualifications);
            $('#qualifications').val(info.qualifications_txt);
        } 
        if ($('input[name="welfare"]').val() == '') {
            $('input[name="welfare"]').val(info.welfare);
            $('#welfare').val(info.welfare_txt);
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
    },200);


    // 工作年限
    var work_years_arr = JSON.parse('{pigcms{$work_years_arr}');
    // 学历
    var qualifications_arr = JSON.parse('{pigcms{$qualifications_arr}');
    // 月薪
    var salary_arr = JSON.parse('{pigcms{$salary_arr}');
    // 福利
    var welfare_arr = JSON.parse('{pigcms{$welfare_arr}');

    var View={
        init:function(){
            this.initFresh();
            this.uploaderFile();
            this.submit();
            this.initInput();
            this.initClick()
        },
        settings:{
            img:[],
            memory_info:[]
        },
        initFresh:function(){
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
                    APP.render({
                        element: $removeContainer,
                        templateId: 'uploadImageTemplate',
                        replaceAll: false,
                        data: [{
                            src: item.src,
                            info: item.value
                        }]
                    });
                    //    $(".loadedNum").html(list.length)
                    //  $(".leftNum").html((8-(list.length)));
                    $removeContainer.find('.weui-uploader__file').last().append(item.file);
                    self.settings.img=list
                },
                onDetail:function(list){
                    $(".loadedNum").html(list.length);
                    $(".leftNum").html(8-(list.length));
                    self.settings.img=list
                }
            });

        },
        submit:function(){
            var _this=this;
            
            var validate=function(res,is_global){
                var result=res.replace(/(^\s+)|(\s+$)/g,"");
                if(is_global.toLowerCase()=="g") {
                    if(result==''){
                        return false
                    }else{
                        return true
                    }
                }
            }
            $(".submit").on('click',function(){

                $("#submitForm").ajaxSubmit({
                    beforeSubmit: function(data, elem, options){
                        var show_mask = false
                        for (var i=0; i<data.length; i++) {
                            if ((data[i].name == "zhiwei" || data[i].name == "salary" || data[i].name == "welfare" || data[i].name == "work_years" || data[i].name == "address" || data[i].name == "desc") && data[i].value == "") {
                                layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:'信息未填写完整',btn:['好的'],shadeClose:false,yes:function(){
                                    layer.closeAll();
                                }});
                                show_mask = true;
                                break;
                            }
                        }
                        if (show_mask == true) {
                            return false
                        }
                        if(!Validator.getData(false,'#submitForm')) return false
                    },
                    success: function(res) {
                        res = JSON.parse(res);
                        if (res.code) {
                            layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:res.msg,btn:['好的'],shadeClose:false,yes:function(){
                                if(res.url!=''){
                                    window.location.href= res.url;
                                }
                                layer.closeAll();
                            }});
                        }else{
                            layer.open({title:['提示：','background-color:#8DCE16;color:#fff;'],content:res.msg,btn:['好的'],shadeClose:false,yes:function(){
                                if(res.url!=''){
                                    window.location.href= res.url;
                                }else{
                                    history.back()
                                }
                                layer.closeAll();
                            }});
                        }
                        console.log(res)

                    },
                    error: function() {

                    }
                });

            })
        },
        initInput:function(){
            var bind_name = 'input';
            if (navigator.userAgent.indexOf("MSIE") != -1){ bind_name = 'propertychange' }
            $('#searchBar').bind(bind_name,'#searchInput' ,function(){
                var content=$.trim($('#searchInput').val());
                if(content!=''){
                    var cat_data = Simplite.getTemplate('zhiwei');
                    var cat_all = Simplite.compile(cat_data)({'content':content} );
                    $('#full .weui-cells').append(cat_all);
                }
            })
        },
        initClick:function(){
            $(".closePopup").on("click",function(){
                $.closePopup();
                $("#searchInput").val("");
                $("#full .weui-cells").html('')
            })


            $(".coupon_mask").click(function(){
                $(".coupon_mask").hide();
                $("input[name=salary]").select("close");
                $("#salary").select("close");
                $("#qualifications").select("close");
                $("#work_years").select("close");
                $("#welfare").select("close");
            });

            $("#salary").select({
                title: "请选择月薪",
                items: salary_arr,
                onChange: function(d) {
                    $("input[name=salary]").val(d.values)

                },
                onClose: function() {
                    $(".coupon_mask").hide()
                },
                onOpen: function() {
                    $(".coupon_mask").show()
                },
            });
            $("#qualifications").select({
                title: "请选择学历要求",
                items: qualifications_arr,
                onChange: function(d) {
                    $("input[name=qualifications]").val(d.values)

                },
                onClose: function() {
                    $(".coupon_mask").hide()
                },
                onOpen: function() {
                    $(".coupon_mask").show()
                },
            });
            $("#work_years").select({
                title: "请选择工作年限",
                items: work_years_arr,
                onChange: function(d) {
                    $("input[name=work_years]").val(d.values)

                },
                onClose: function() {
                    $(".coupon_mask").hide()
                },
                onOpen: function() {
                    $(".coupon_mask").show()
                },
            });
            $("#welfare").select({
                title: "请选择福利待遇",
                multi: true,
                items: welfare_arr,
                onChange: function(d) {
                    $("input[name=welfare]").val(d.values)

                },
                onClose: function() {
                    $(".coupon_mask").hide()
                },
                onOpen: function() {
                    $(".coupon_mask").show()
                },
            });
            $(".face").on("click",function(){
                $(".face i").toggleClass("main_color")
            })
            // $("input[name=zhiwei]").on("click",function(){
            //     // $("#full").popup();
            //     window.location.href='{pigcms{:U('position_select')}';
            //     console.log($("#searchBar").height(),)
            // })
            $(document).on("click","#full .weui-cell",function(){
                $("input[name=zhiwei]").val($(this).find(".weui-cell__bd p").html())
                $(".closePopup").trigger("click")
            })

        }
    }
    window.View=View;
    View.init();
</script>
</body>
</html>