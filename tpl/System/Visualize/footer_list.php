<include file="Publics:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-rtl.min.css">
<script src="./tpl/House/default/static/layer/layer.js"></script>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div class="tabbable">
                        <ul id="myTab" class="nav nav-tabs">
                            <li>
                                <a href="#basicinfo" onclick="viewpage_list()" data-toggle="tab">首页</a>
                            </li>
                            <li class="active">
                                <a href="#txtstore" onclick="footer_list()" data-toggle="tab">底部导航</a>
                            </li>
													<if condition="$yingshan OR $jsdhwy_new_wap_index OR $yuanJino2oCustomized">
														<li>
															<a href="#topstore" onclick="top_list()" data-toggle="tab">顶部导航</a>
														</li>
													</if>
                        </ul>
                    </div>

                    <div class="tab-content" style="height: 400px;">

                        <div class="table-list">
                            <div class="fitment_div">
                                <div class="form-group">
                                    <div class="group-title">
                                        <span class="name">导航列表</span>
                                        <div class="div_right">
                                            <button type="button" class="btn fitment" style="height: 28px;" data-url="{pigcms{:U('Visualize/foot_menu_list',array('cat_key'=>'house_app_index_top'))}">装修</button>
                                            <button type="button" class="btn  visualize_assign" style="height: 28px;" data-id="7">分配权限</button>
                                        </div>
                                    </div>
                                    <div class="group-content">自定义底部导航列表，若不设置则展示系统默认的</div>
                                </div>
                                <div class="form-group">
                                    <div class="group-title">
                                        <span class="name">文本默认颜色</span>
                                        <div class="right_div">
                                            <input type="text" class="input-text" id="config_txtcolor" value="{pigcms{$now_village.visualize_app_footer_txtcolor}" readonly="readonly" style="text-indent:1000px;width:35px;cursor:default;background-color:{pigcms{$now_village.visualize_app_footer_txtcolor} !important"/>
                                            <a href="javascript:void(0);" id="choose_color_box_txtcolor" style="line-height:28px;">点击选择颜色</a>
                                        </div>
                                    </div>
                                    <div class="group-content">导航文字的默认颜色，即非选中色</div>
                                </div>
                                <div class="form-group">
                                    <div class="group-title">
                                        <span class="name">文本选中颜色</span>
                                        <div class="right_div">
                                            <input type="text" class="input-text" id="config_txtactivecolor" value="{pigcms{$now_village.visualize_app_footer_txtactivecolor}" readonly="readonly" style="text-indent:1000px;width:35px;cursor:default;background-color:{pigcms{$now_village.visualize_app_footer_txtactivecolor} !important;"/>
                                            <a href="javascript:void(0);" id="choose_color_box_txtactivecolor" style="line-height:28px;">点击选择颜色</a>
                                        </div>
                                    </div>
                                    <div class="group-content">导航文字的选中颜色，即当前页面指向的部分。</div>
                                </div>
                                <div class="form-group">
                                    <div class="group-title">
                                        <span class="name">背景颜色</span>
                                        <div class="right_div">
                                            <input type="text" class="input-text" id="config_bgcolor" value="{pigcms{$now_village.visualize_app_footer_bgcolor}" readonly="readonly" style="text-indent:1000px;width:35px;cursor:default;background-color:{pigcms{$now_village.visualize_app_footer_bgcolor} !important;"/>
                                            <a href="javascript:void(0);" id="choose_color_box_bgcolor" style="line-height:28px;">点击选择颜色</a>
                                        </div>
                                    </div>
                                    <div class="group-content">导航条整个背景颜色。方便配合页面做一些节假日的样式调整。例如过年就红色等。</div>
                                </div>

															<if condition="$yingshan OR $jsdhwy_new_wap_index">
																<div style="margin-bottom: 10px;float: left;margin-left: 630px;margin-top: -323px;width: 450px;">
																	<button type="button" class="btn fitment fitment_template" onclick="selectTemplate()">选择模板</button>
																	<span style="display: inline-block;margin-left: 10px">当前模板：{pigcms{$footer_template}</span>
																	<p>选择底部导航模板时，模板只会覆盖未分配的小区</p>
																</div>
															</if>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    /**flex**/
    .rel{position: relative;}
    .flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; display: box; align-items: center;  }
    .flex_justify{-webkit-box-pack: justify;-webkit-justify-content: space-between;-ms-flex-pack: justify;justify-content: space-between;box-pack: center;}
    .flex-align{-webkit-box-align: center;-moz-align-items: center;-webkit-align-items: center; align-items: center;box-align: center;}
    .flex_one{flex: 1;-webkit-box-flex: 1;-webkit-flex: 1;}
    .flex-wrap{-webkit-flex-wrap: wrap;-moz-flex-wrap: wrap;-ms-flex-wrap: wrap;-o-flex-wrap: wrap;flex-wrap:wrap;}

    @media screen and (max-width: 1050px) {
        .page-style-wrapper{
            position: static;
            margin: 20px;
        }
    }

    .page-style-wrapper{position: absolute;left: 650px;top: 2px;}
    .page-style-wrapper .btn-box{margin: 20px 0;color: #999;font-size: 14px;}
    .page-style-wrapper .btn-box button {background-color: #438eb9;border-radius: 3px;border: none;padding: 6px 12px;color: white; cursor: pointer; outline: none;display: inline-block;margin-right: 10px;}
    .page-style-wrapper .btn-box button:active{background: #05679f;}

    .btn-box button {background-color: #438eb9;border-radius: 3px;border: none;padding: 6px 12px;color: white; cursor: pointer; outline: none;display: inline-block;margin-right: 10px;}
    .btn-box button:active{background: #05679f;}
    .page-style{width: 350px;height: 1227px;background: #fff;overflow:hidden;box-shadow: 0 0 10px 2px #ddd;position:relative;}
    .page-style .now_view .item { width: 200 !important }


    .table-list{
        position: relative;
    }

    .fitment_div {
        width: 520px;
        margin-left: 20px;
    }
    .form-group {
        margin-bottom: 10px;
        border-bottom: 1px solid #EEE;
        padding: 10px 0;
    }
    .form-group:last-child{
        border-bottom:none;
    }
    .group-title {
        position: relative;
        font-size: 16px;
        color: black;
        margin-bottom: 10px;
    }
    .group-content {
        width: 380px;
        line-height: 1.3;
        letter-spacing: 0.5px;
        font-size: 12px;
        color: #999;
    }
    .group-title .switch-off,.group-title .switch-on{
        position: absolute!important;
        right: 0px;
        top: 0px;
    }
    .group-title .right_change{
        position: absolute!important;
        right: 0px;
        top: 0px;
    }
    #colorpanel{
        font-size: 12px;
    }
    #colorpanel input{
        height:20px;
        width: 40px;
        font-size: 12px;
    }
    .ant-btn-primary {
        color: #fff;
        background-color: #1890ff;
        border-color: #1890ff;
        text-shadow: 0 -1px 0 rgb(0 0 0 / 12%);
        -webkit-box-shadow: 0 2px 0 rgb(0 0 0 / 5%);
        box-shadow: 0 2px 0 rgb(0 0 0 / 5%);
    }
    .ant-btn {
        line-height: 1.499;
        position: relative;
        display: inline-block;
        font-weight: 400;
        white-space: nowrap;
        text-align: center;
        background-image: none;
        border: 1px solid transparent;
        -webkit-box-shadow: 0 2px 0 rgb(0 0 0 / 2%);
        box-shadow: 0 2px 0 rgb(0 0 0 / 2%);
        cursor: pointer;
        -webkit-transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        -ms-touch-action: manipulation;
        touch-action: manipulation;
        height: 32px;
        padding: 0 15px;
        font-size: 14px;
        border-radius: 2px;

    }
    .fitment_template {
        background-color: #438eb9 !important;
        border: none;
        padding: 5px 10px;
        cursor: pointer
    }
    .fitment_template :hover{
        background-color: #438eb9 !important;
    }
		.btn{
        border-radius: 5px
    }
</style>
<!-- swiper -->

<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css" />
<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>
<!-- honeySwitch -->
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}js/honeySwitch/honeySwitch.css" />
<script type="text/javascript" src="{pigcms{$static_public}js/honeySwitch/honeySwitch.js" charset="utf-8"></script>

<script type="text/javascript" src="{pigcms{$static_public}js/jquery.colorpicker.js"></script>

<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>

<script type="text/javascript" charset="utf-8">

    function viewpage_list(){
        window.location.href = "{pigcms{:U('viewpage_list')}";
    }
    function footer_list(){
        window.location.href = "{pigcms{:U('footer_list')}";
    }
    function top_list(){
      window.location.href = "{pigcms{:U('top_list')}";
    }

    $(function(){

        var swiper1 = new Swiper('.swiper-container.default-header', {
            pagination: '.default-header .swiper-pagination',
            paginationClickable: true,
            observer:true,
            observeParents:true
        });
        var swiper2 = new Swiper('.swiper-container.slider-small', {
            pagination: '.slider-small .swiper-pagination',
            paginationClickable: true,
            observer:true,
            observeParents:true
        });

        $('.page-style-wrapper .btn-box button').on('click', function(){
            window.location.href = $(this).data('url');
        })

        switchEvent("#plat_news",function(){
            console.log('开了');
            change_plat_news(1);
        },function(){
            console.log('关了');
            change_plat_news(0);
        });
        function change_plat_news(value){
            $.post("{pigcms{:U('change_plat_news_show')}",{value:value},function(result){
                if(result.status != 1){
                    layer.alert('切换失败',{
                        offset: ($('body').height() - 150) / 2
                    });
                }
            });
        }


    });

</script>
<script src="/static/js/cart/jscolor.js" type="text/javascript"></script>
<style type="text/css">
    .plug-menu {
        width:36px;
        height:36px;
        border-radius:36px;
        -moz-box-shadow:0 0 0 4px #FFFFFF, 0 2px 5px 4px rgba(0, 0, 0, 0.25);
        -webkit-box-shadow:0 0 0 4px #FFFFFF, 0 2px 5px 4px rgba(0, 0, 0, 0.25);
        box-shadow:0 0 0 4px #FFFFFF, 0 2px 5px 4px rgba(0, 0, 0, 0.25);
        background-color: FF0000;
        position:relative
    }
    .plug-menu span {
        display: block;
        width:28px;
        height:28px;
        background: url(/static/images/photo/plugmenu.png) no-repeat;
        background-size: 28px 28px;
        text-indent: -999px;
        position:absolute;
        top:4px;
        left:4px;
        overflow: hidden;
    }
</style>
<style>
    .flex{display:flex !important;display: -webkit-box; display: -moz-box; display: -ms-flexbox; display: -webkit-flex; display: box; align-items: center;  }
    .flex_one{flex: 1;-webkit-box-flex: 1;-webkit-flex: 1;}

    .page-style-wrapper{
        float: left;
        margin: -210px auto auto 650px;
    }

    @media screen and (max-width: 1050px) {
        .page-style-wrapper{
            float: none;
            margin: 20px;
        }
    }

    .page-style .item{
        text-align: center;
        box-sizing: border-box;
        cursor: pointer;
    }

    .page-style .img{
        width: 28px;
        height: 28px;
        background: #fff;
        margin: 4px auto 0;
        box-sizing: border-box;
    }
    .page-style .img img{
        width: 100%;
        height: 100%;
    }

    .page-style-wrapper .btn-box{
        margin: 20px 0;
        color: #999;
        font-size: 14px;
    }

    .page-style-wrapper .btn-box button {
        background-color: #438eb9;
        border-radius: 3px;
        border: none;
        padding: 6px 12px;
        color: white;
        cursor: pointer;
        outline: none;
        display: inline-block;
        margin-right: 10px;
    }

    .page-style-wrapper .btn-box button:active{
        background: #05679f;
    }

    .fitment_div {
        width: 520px;
        margin-left: 20px;
    }
    .form-group {
        margin-bottom: 10px;
        border-bottom: 1px solid #EEE;
        padding: 10px 0;
    }
    .form-group:last-child{
        border-bottom:none;
    }
    .group-title {
        position: relative;
        font-size: 16px;
        color: black;
        margin-bottom: 10px;
    }
    .group-content {
        width: 380px;
        line-height: 1.3;
        letter-spacing: 0.5px;
        font-size: 12px;
        color: #999;
    }
    .group-title .btn.fitment ,.visualize_assign{
        background-color: #438eb9!important;
        border-radius: 3px;
        border: none;
        padding:0 12px;
        color:white;
        cursor:pointer;
        outline:0;
        -webkit-user-select: none;
        -webkit-touch-callout: none;
        margin-left:10px;
    }
    .div_right{
        float: right;
    }
    .group-title .switch-off,.group-title .switch-on{
        position: absolute!important;
        right: 0px;
        top: 0px;
    }
    .group-title .right_change{
        position: absolute!important;
        right: 0px;
        top: 0px;
    }
    .group-title .right_div{
        position: absolute!important;
        right: 0px;
        top: 0px;
        font-size:12px;
    }
</style>
<script type="text/javascript">
    if(typeof parent.showHelpText == 'function'){
        parent.showHelpText(parentShowHelpParam);
    }
    if(typeof parent.showHelpType == 'function'){
        parent.showHelpType(parentShowIndex,'{pigcms{:GROUP_NAME}','{pigcms{:MODULE_NAME}','{pigcms{:ACTION_NAME}');
    }
    $(function(){
        parent.iframeRealHeight = $('body').height() + 40;

        if(typeof parent.setMainHeight == 'function'){
            parent.setMainHeight({iframeHeight:true});
        }
        /* alert($(window.parent).scrollTop()); */
        /* parent.scrollTo(0,0); */
        // alert(parent.iframeRealHeight);
    });
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/jquery.colorpicker.js"></script>

<link rel="stylesheet" href="{pigcms{$static_public}layui2/css/layui.css" media="all"/>
<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>

<script type="text/javascript" charset="utf-8">
    $(function(){
        $("#choose_color_box_bgcolor").click(function(){
            change_bgcolor();
        });

        $("#choose_color_box_txtactivecolor").click(function(){
            change_txtactivecolor();
        });

        $("#choose_color_box_txtcolor").click(function(){

            change_txtcolor();
//            window.top.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'config_txtcolor')

        });


        $('.fitment').click(function(){
            var _this = $(this);
            console.log(_this.data('url'));
            if(_this.data('url')){
              window.top.artiframe(_this.data('url')+'&frame_show=true&from_page=viewpage',_this.siblings('.name').text(),1000,600,true,false,false,false,'viewpage',true);
            }
        });

        $('.page-style .item').click(function(){
            $('.page-style .item').css('color', '').removeClass('selected');
            $(this).css('color', $('#config_txtactivecolor').val()).addClass('selected');
        })



        $('.page-style-wrapper .btn-box button').on('click', function(){
            window.location.href = $(this).data('url');
        })
    });

    function change_bgcolor(){
        window.showTopColorPanel("leftmenu_Viewpage_index",$("#choose_color_box_txtactivecolor").offset().top,$("#choose_color_box_txtactivecolor").height(),$("#choose_color_box_txtactivecolor").offset().left,"config_bgcolor",function(){
            var bgcolor = $('#config_bgcolor').val();
            $('.page-style').css('background-color', bgcolor);
            save_change_color('app_footer_bgcolor',bgcolor);
        });
    }

    function change_txtactivecolor(){
        window.showTopColorPanel("leftmenu_Viewpage_index",$("#choose_color_box_txtactivecolor").offset().top,$("#choose_color_box_txtactivecolor").height(),$("#choose_color_box_txtactivecolor").offset().left,"config_txtactivecolor",function(){
            var txtactivecolor = $('#config_txtactivecolor').val();
            $('.page-style .item.selected').css('color', txtactivecolor);
            save_change_color('app_footer_txtactivecolor',txtactivecolor);
        });
    }

    function change_txtcolor(){
        window.showTopColorPanel("leftmenu_Viewpage_index",$("#choose_color_box_txtcolor").offset().top,$("#choose_color_box_txtcolor").height(),$("#choose_color_box_txtcolor").offset().left,"config_txtcolor",function(){
            var txtcolor = $('#config_txtcolor').val();
            $('.page-style').css('color', txtcolor);
            save_change_color('app_footer_txtcolor',txtcolor);
        });
    }

    function save_change_color(name,color){
        $.post("{pigcms{:U('Visualize/change_footer_color')}",{name:name,color:color},function(result){
            if(result.status != 1){
                layer.alert('切换失败,请重试~ ',{
//                    offset: ($('body').height() - 60) / 2
                });
            }else{
                layer.alert('切换成功',{
//                    offset: ($('body').height() - 60) / 2

                });
                window.location.href="{pigcms{:U('footer_list')}"
            }
        },'json');
    }

    function showTopColorPanel(frameName,ttop,thei,tleft,txt,callFunc){
        //txtobj = $('#'+txt,window.frames[frameName].document);

        $('#HexColor').val($("#"+txt+"").val());
        $('#DisColor').css('background-color',$("#"+txt+"").val() +' !important;');

        $("#colorpanel").css({

            top:ttop + thei + 5,
            left:tleft,
            'z-index':'19999',
        });
        $("#colorpanel").show();
        $("#CT tr td").unbind("click").unbind("mouseover").mouseover(function(){
            var aaa=$(this).css("background-color");
            $("#DisColor").css("background-color",aaa);
            $("#HexColor").val(aaa);
        }).click(function(){
            var aaa=$(this).css("background-color");
            $("#"+txt+"").val(aaa);
            $("#"+txt+"").css("background-color",aaa);
            $("#colorpanel").hide();
            console.log(typeof callFunc);
            if(typeof callFunc == 'function'){
                callFunc();
            }
        });
        $("#_clean").unbind("click").click(function(){
            $("#colorpanel").hide();
            $("#"+frameName+"").val('');
            $("#"+txt+"").val('');
            if(typeof callFunc == 'function'){
                callFunc();
            }
        });
        $("#_cclose").unbind("click").click(function(){$("#colorpanel").hide();}).css({"font-size":"12px","padding-left":"10px"});
        $('#HexColor').unbind("keypress").keypress(function(e){
            if(e.keyCode==13){
                $("#colorpanel").hide();
                $("#"+txt+"").val($(this).val());
                $("#"+txt+"").css("background-color",$(this).val()+' !important;');
                if(typeof callFunc == 'function'){
                    callFunc();
                }
            }
        }).css({'width':'90px'});
    }

    function selectTemplate (){
      layui.use(['layer'], function(){
        var layer = layui.layer;

        layer.open({
          type: 2,
					title: '选择模板',
          offset: '20px',
          area: ['435px', '400px'],
          content: '{pigcms{:U('foot_menu_template')}' //这里content是一个URL，如果你不想让iframe出现滚动条，你还可以content: ['http://sentsin.com', 'no']
        })
      })
    }

</script>
<script type="text/javascript">
    var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}",long="{pigcms{$village_info.long}",lat="{pigcms{$village_info.lat}";
</script>

<style>
    .BMap_cpyCtrl{display:none;}
    input.ke-input-text {
        background-color: #FFFFFF;
        background-color: #FFFFFF!important;
        font-family: "sans serif",tahoma,verdana,helvetica;
        font-size: 12px;
        line-height: 24px;
        height: 24px;
        padding: 2px 4px;
        border-color: #848484 #E0E0E0 #E0E0E0 #848484;
        border-style: solid;
        border-width: 1px;
        display: -moz-inline-stack;
        display: inline-block;
        vertical-align: middle;
        zoom: 1;
    }
    .form-group>label{font-size:12px;line-height:24px;}
    #upload_pic_box{margin-top:20px;height:150px;}
    #upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
    #upload_pic_box img{width:100px;height:70px;border:1px solid #ccc;}
</style>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

<script type="text/javascript">
    //art弹框组件
    function artiframe(url, title, width, height, lock, resize, background, button, id, fixeds, closefun, left, top, padding){
        if(url.indexOf("?") != -1){
            url = url+'&frame=1';
        }else{
            url = url+'?frame=1';
        }
        if (!width) width = 'auto';
        if (!height){
            height = 'auto';
        }else if(height > $(window).height() * 0.8){
            height = $(window).height() * 0.8;
        }
        if (!lock) lock = false;
        if (!resize) resize = false;
        if (!background) background = 'black';
        if (!closefun) closefun = null;
        if (!button) button = null;
        if (!left) left = '50%';
        if (!top) top = '38.2%';
        if (!id) id = null;
        if (!fixeds) fixeds = false;
        if (!padding) padding = 0;
        art.dialog.open(url, {
            init: function(){
                var iframe = this.iframe.contentWindow;
                window.top.art.dialog.data('iframe' + id, iframe);
            },
            id: id,
            title: title,
            padding: padding,
            width: width,
            height: height,
            lock: lock,
            resize: resize,
            background: background,
            button: button,
            fixed: fixeds,
            close: closefun,
            left: left,
            top: top,
            opacity:'0.4'
        });
    }

    function chooseFloor() {
        var url = "{pigcms{:U('Index/house_village_list')}";
        // var href = url + '&selecteds='+selecteds;
        var href = url+'&type=1';
        var index= layer.open({
            type: 2,
            title:'选择小区',
            content: href,
            offset:['0px','150px'],
            area: ['1000px', '700px'],
            btn: ['确定','关闭'],
            maxmin: false,
            yes: function (index,layero) {
                var body = layer.getChildFrame('body', index); //得到iframe页的body内容
                var BDDM = body.find("#village_id").val();
                var index1=index;
                console.log('BDDM',BDDM);
                layer.msg('是否将该小区用户端样式复制到本小区？', {
                    time: 0 //不自动关闭
                    ,btn: ['确定', '取消']
                    ,icon: 3
                    ,offset:['550px','750px']
                    ,skin:'demo'
                    ,yes: function(index){
                        if (BDDM==''){
                            layer.alert('请选择一个小区', {icon: 2,offset:['350px','750px']});
                            layer.close(index);
                        }else{
                            console.log(BDDM);
                            layer.close(index1);
                            layer.close(index);
                            copy_village(BDDM);
                        }

                    }
                });

            },
        });
    }
    function copy_village(village_id) {
        $.post("{pigcms{:U('copy_village_info')}",{village_id:village_id},function(result){
            if(result.status != 1){
                layer.alert('复制失败,请重试~ ',{
                });
            }else{
                layer.alert('复制成功',{
                });
                window.location.href="{pigcms{:U('viewpage_list')}"
            }
        },'json');
    }
    //art信息提示
    function msg(status,msg,lock,time){
        var list = window.art.dialog.list;
        for (var i in list){
            if(i == 'form_submit_tips'){
                list[i].close();
            }
        };
        if(lock){
            lock = true;
        }else{
            lock = false;
        }
        if (!time){
            time = 2;
        }
        if(status == 2){
            ico = 'face-smile';
        }else if(status){
            ico = 'succeed';
        }else{
            ico = 'error';
        }
        if(status == 2){
            id = 'form_submit_tips';
        }else{
            id = 'msg' + Math.random();
        }
        art.dialog({
            icon: ico,
            time: time,
            /*background: '#FFF',*/
            title: '提示信息',
            id: id,
            opacity:'0.4',
            lock: lock,
            fixed: true,
            resize: false,
            content: msg
        });
    }

    $('.visualize_assign').click(function () {
        var that=$(this),type=that.attr('data-id');
        layer.open({
            type: 2,
            title: '分配权限',
            shadeClose: false,
            maxmin: true, //开启最大化最小化按钮
            area: ['80%', '80%'],
            content: "{pigcms{:U('Visualize/visualize_assign')}&source_type="+type
        });

    })
</script>

<include file="Publics:footer"/>