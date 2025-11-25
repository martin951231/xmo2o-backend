<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>个人详情</title>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/weui.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/custom.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}css/common.css"/>
    <link href="{pigcms{$static_path}employee/css/index.css?001" rel="stylesheet"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/cropper.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/layer.css"/>
    <style type="text/css">

        .x_header{background-color:#04CB86!important}
        .avatar_img{border-radius: 50%;width:80px !important;padding:5px;}
        .reply_avatar_img{border-radius: 50%;width:60px !important;padding:20px;}
        .top_div{float: left;}
        .mid{padding-bottom: 10px;}
        .address_div{font-size: 15px;padding: 10px;margin-top: 10px;color:#8B8A8A;}
        .address_div div{padding:5px;}
        .page_bd_backgroud{
            background: #FFFFFF;
        }
        .div_mid div span{margin-left:10px;}
        .person_img img{width:23%;}
        .memory div{
            border:1px solid #FFBC8F;
            display: inline-block;
            margin-left: 15px;
            margin-top: 10px;
            border-radius: 5px;
            color:#FFBC8F;
            padding: 1px;
            text-align: center;
        }
        .address_div.page_bd_backgroud.div_bottom{
            bottom:0;
            position:fixed;
            height: 43px;
            width: 100%;
            padding: 0px;
        }
        .address_div.page_bd_backgroud.div_bottom div{
            float: left;
            width:17.39%;
            text-align: center;
            height: 43px;
            line-height: 43px;
        }
        .right_fbox {
            color: white;
            position: fixed;
            top: 65%;
            right: 15px;
            width: 50px;
            font-size: 14px;
            background: #FF7F27;
            border-radius: 50%;
            z-index: 999;
        }
        .adver{
            text-align: center;
            padding:10px;

        }
        .weui-pull-to-refresh__layer {
                height: 24px;
        }
        .border-bottom{
            border-bottom: 1px solid #E4E4E4;
        }
        a.go_ping{
            display: block;
            background: #ff9000;
            padding: 3px 7px;
            color: #fff;}
        }


        .all_info {
            display: none;
            padding: 10px 15px;
        }
        .all_info .tip {
            border-bottom: 1px solid #e5e5e5;
            padding: 8px 0 15px 5px;
        }
        .all_content {
            padding: 8px 0 15px 15px;
        }
        .all_info.active{
            display: block;
        }
    </style>
    <script src="{pigcms{$static_path}employee/js/common/jquery-2.1.4.js" type="text/javascript"></script>
    <script src="{pigcms{$static_path}employee/js/common/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
</head>
<body class="pb0">
    <if condition="!$is_weixin_browser && !$is_app_browser">
        <div class="weui-pull-to-refresh__layer">
        </div>
        <header class="x_header bgcolor_11 cl f15" >
            <a class="z f14" href="javascript:window.history.go(-1);"><i class="iconfont icon-fanhuijiantou w15"></i>返回</a>
            <div class="navtitle">个人详情</div>
        </header>
    </if>
    <div class="page__bd" style="padding-bottom:60px">
        <form action="" method="post" id="form" enctype="multipart/form-data">
            <div class="weui-panel weui-panel_access before">
                <div class="weui-panel__bd link-url" data-url="{pigcms{:U('Employee/employee_info',array('employee_id'=>$list['id']))}">
                        <div  class="weui-media-box weui-media-box_appmsg" style='padding:10px'>
                            <div class="weui-media-box__hd" style="    width: 80px; height: 80px;">
                                <img class="weui-media-box__thumb" src="{pigcms{$list.avatar}" alt="" style="border-radius:50%"/>
                            </div>
                            <div class="weui-media-box__bd">
                                <h4 class="weui-media-box__title color_9 font_b font_size15 flex">
                                     <div class="flex_one" style="font-size: 18px;font-weight:bold;color:#000">{pigcms{$list['person_info']['nickname']['value']}</div>
                                    <div class="flex_one flex"><img width="20px;" src="{pigcms{$static_path}employee/images/collect.png"><span class="pl5">{pigcms{$list.collect_num}</span></div>
                                    <div class="flex_one flex"><img width="20px;" src="{pigcms{$static_path}employee/images/zan.png"><span class="pl5">{pigcms{$list.thumb_num}</span>  </div>


                                    <if condition="$is_worker_appoint">
                                        <div class="flex_one flex"><img width="20px;" src="{pigcms{$static_path}employee/images/yuyue.png"><span class="pl5">预约</span></div>
                                    </if>
                                </h4>
                                <div class="weui-media-box__desc mg_b5 mg_t5 flex">
                                    <if condition="$list['sex'] eq 1">
                                        <div class="flex_one" style="line-height: 24px;color: #FA9843;">男</div>
                                        <elseif condition="$list['sex'] eq 2"/>
                                        <div class="flex_one" style="line-height: 24px;color: #FA9843;">女</div>
                                    </if>
                                    <div class="flex_one" style="line-height: 24px;">{pigcms{$list['cat_name']} | 从业{pigcms{$list['person_info']['working_years']['value']}年</div>
                                </div>
                                <p class="weui-media-box__desc">{pigcms{$list['person_info']['personality_signature']['name']} : {pigcms{$list['person_info']['personality_signature']['value']}</p>
                            </div>
                        </div>
                </div>
            </div>

            <div class="weui-cells font_size15">
                <if condition="$list['merchant_store_name'] && $list['store_id']">
                    <div class="weui-cell link-url" data-url="{pigcms{$config['site_url']}/wap.php?g=Wap&c=Employee&a=shop&store_id={pigcms{$list['store_id']}">
                        <div class="color_8A">
                            <p>所属商家&nbsp;:&nbsp;</p>
                        </div>
                        <div class="weui-cell__bd color_8A">{pigcms{$list.merchant_store_name}</div>
                    </div>
                    <else/>
                    <div class="weui-cell ">
                        <div class="color_8A" id="bind_store">
                            <div class="weui-btn weui-btn_default" style="font-size: 14px;">去绑定商家</div>
                        </div>
                    </div>
                </if>
<!--                c6b08bc4dfbee3a2a5179ab151ad2528-->
                <div class="weui-cell ">
                    <div class="color_8A navi" data-lng="{pigcms{$list['person_info']['unit_address']['long']}" data-lat="{pigcms{$list['person_info']['unit_address']['lat']}" data-name="{pigcms{$list['person_info']['unit_address']['value']}" data-address="{pigcms{$list['person_info']['unit_address']['address']}">
                        <p>{pigcms{$list['person_info']['unit_address']['name']}&nbsp;:&nbsp;</p>
                    </div>
                    <div class="weui-cell__bd color_8A navi" data-lng="{pigcms{$list['person_info']['unit_address']['long']}" data-lat="{pigcms{$list['person_info']['unit_address']['lat']}" data-name="{pigcms{$list['person_info']['unit_address']['value']}" data-address="{pigcms{$list['person_info']['unit_address']['address']}"> {pigcms{$list['person_info']['unit_address']['address']}</div>
                </div>
            </div>
            <div class="weui-cells font_size15">
                <div class="weui-cell ">
                    <div class="color_8A">
                        <p>{pigcms{$list['person_info']['work_unit']['name']}&nbsp;</p>
                    </div>
                    <div class="weui-cell__bd color_8A">{pigcms{$list['person_info']['work_unit']['value']}</div>
                </div>
                <div class="weui-cell ">
                    <div class="color_8A">
                        <p>{pigcms{$list['person_info']['unit_phone']['name']}&nbsp;:&nbsp;</p>
                    </div>
                    <div class="weui-cell__bd color_8A">{pigcms{$list['person_info']['unit_phone']['value']}</div>
                </div>
<!--                <volist name="list['person_info']" id="vol" key='key'>-->
<!--                    <if condition="is_numeric($key)">-->
<!--                        <div class="weui-cell ">-->
<!--                            <div class="color_8A">-->
<!--                                <p>{pigcms{$vol['name']}&nbsp;:&nbsp;</p>-->
<!--                            </div>-->
<!--                            <div class="weui-cell__bd color_8A">{pigcms{$vol['value']}</div>-->
<!--                        </div>-->
<!--                    </if>-->
<!--                </volist>-->
            </div>


            <if condition="$group">
                <div class="weui-cells font_size15">
                    <div class="weui-cell">
                        <volist name="group" id="item">
                            <p style="margin:0 5px;" class="info-title <if condition="$item['gid'] eq $first_id">active</if>" data-group_id="{pigcms{$item['gid']}">{pigcms{$item['gname']}&nbsp;</p>
                            <p style="margin-right: 5px;border-right:1px solid #ccc;">&nbsp;</p>
                        </volist>
                    </div>
                <volist name="group" id="item">
                    <if condition="$item['gid'] eq 4">
                        <div class="weui-media-box__bd all_content info-content_{pigcms{$item['gid']}  <if condition="$item['gid'] eq $first_id">active</if>">
                    <p class="weui-media-box__desc imgs pics"  style="padding: 10px;-webkit-line-clamp: unset;">
                        <volist name="item['info']" id="vo">
                            <volist name="vo['value']" id="voo">
                                <img class="weui-media-box__thumb" data-img="{pigcms{$voo}" data-index="{pigcms{$key}" src="{pigcms{$voo}" alt=""  style="width:30%;margin-right:3%;"/>
                            </volist>
                        </volist>
                    </p>
                    </div>
                    <else/>
                    <div class="weui-media-box__bd all_info all_content info-content_{pigcms{$item['gid']}  <if condition="$item['gid'] eq $first_id">active</if>">
                    <volist name="item['info']" id="vo">
                        <div class="tip">
                            <div class="color_8A">
                                <p style="float: left;">{pigcms{$vo['name']}&nbsp;:&nbsp;</p>
                            </div>
                            <div class="weui-cell__bd color_8A">
                                <if condition="$vo['type'] eq 12 || $vo['type'] eq 10">
                                    <volist name="vo['value']" id="voo">
                                        <img class="weui-media-box__thumb" data-img="{pigcms{$voo}" data-index="{pigcms{$key}" src="{pigcms{$voo}" alt=""  style="width:30%;margin-right:3%;"/>
                                    </volist>
                                    <else/>
                                    {pigcms{$vo['value']}
                                </if>
                            </div>
                        </div>
                    </volist>
                    </div>
                    </if>
                </volist>
            </div>
            <elseif condition="$list['person_info']['person_mien']"/>
                <div class="weui-cells font_size15">
                    <div class="weui-cell ">
                        <p>{pigcms{$list['person_info']['person_mien']['name']}&nbsp;:&nbsp;</p>
                    </div>
                    <div class="weui-media-box__bd">
                        <p class="weui-media-box__desc imgs pics"  style="padding: 10px;">
                            <volist name="list['person_info']['person_mien']['value']" id="voo">
                                <img class="weui-media-box__thumb" data-img="{pigcms{$voo}" data-index="{pigcms{$key}" src="{pigcms{$voo}" alt=""  style="width:30%;margin-right:3%;"/>
                            </volist>
                        </p>
                    </div>
                </div>
            </if>

            <div class=" mg_t10 bg_f">
                    <div >
                        <div class="weui-cell flex">
                              <div>评论</div>
                              <div class="rateInfo flex">
                                <if condition="$score neq 0">

                                    <div class="starIconBg" style="margin-left: 5px;margin-right: 10px;">
                                        <div class="starIcon" style="width:{pigcms{$start}%;"><!--控制星级显示-->
                                        </div>
                                    </div>
                                    <div >{pigcms{$score*2}分</div>
                                    <else/>
                                    <div style="margin-left: 10px;">
                                        暂无评分
                                    </div>
                                </if>
                            </div>
                        </div>
                        <div class="memory">
                            <volist name="employee_memory_info" id="vo">
                                <div>{pigcms{$vo['memory_name']}({pigcms{$vo['num']})</div>
                            </volist>
                        </div>
                    </div>
                    <div class="weui-panel weui-panel_access before">
                        <div class="weui-panel__ft weui-panel__hd pd0">
                            <a href="{pigcms{:U('reply_more',array('employee_id'=>$list['id']))}" class="weui-cell weui-cell_access weui-cell_link">
                                <div class="weui-cell__bd color_9">评论({pigcms{$employee_reply_info['count']})</div>
                                <span class="weui-cell__ft pinglnu_more" style="color: #FFBC8F">查看全部</span>
                            </a>
                        </div>
                    <div class="weui-panel__bd">
                        <volist name="employee_reply_info['list']" id="vo">
                            <a href="javascript:void(0);" class="weui-media-box weui-media-box_appmsg">
                                <div class="weui-media-box__hd">
                                    <if condition="$vo.avatar neq ''">
                                        <img class="weui-media-box__thumb" src="{pigcms{$vo.avatar}" alt="" />
                                        <else/>
                                        <img class="weui-media-box__thumb" src="{pigcms{$config['site_url']}/tpl/Wap/pure/static/images/new_my/pic-default.png" alt="" />
                                    </if>
                                </div>
                                <div class="weui-media-box__bd">
                                    <h4 class="weui-media-box__title color_9 font_b font_size15">{pigcms{$vo.nickname}</h4>
                                    <div class="reply_info weui-media-box__desc">
                                        <div class="rateInfo">
                                            <div class="starIconBg">
                                                <div class="starIcon" style="width:{pigcms{$vo.start}%;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="weui-media-box__desc">{pigcms{$vo['add_time']|date="Y-m-d H:i:s",###}</p>
                                    <p class="weui-media-box__desc">{pigcms{$vo.reply_content}</p>
                                </div>
                            </a>
                        </volist>
                    </div>
                </div>

            </div>
                <!--悬浮广告框-->
<!--            <if condition="$list['is_open_adver']">    -->
<!--                <div class="right_fbox" style="background: {pigcms{$list.adver_color}"><div class="adver">{pigcms{$list.adver_info}</div></div>-->
<!--            </if>-->
        </form>
        <!--<a href="{pigcms{:U('Employee/employee_info')}">
            <div class="right_fbox" style="background: {pigcms{$list.adver_color}"><div class="adver">编辑</div></div>
        </a>-->

        <div style="position:fixed;z-index:50;padding:5px 0;" class="weui-tabbar" id='is_bottom'>
            <footer class="footerMenu wap">
                <ul class="home_li">
                    <li>
                        <a class="" href="{pigcms{:U('employee_spread_hb',array('share_type'=>'normal'))}" target="self"><em class="home"></em><p>邀客户关注</p></a>
                    </li>
                    <li>
                        <a href="{pigcms{:U('employee_spread_hb',array('share_type'=>'friend'))}" target="self"><em class="store"></em><p>约同伴入驻</p></a>
                    </li>
                    <li>
                        <a href="{pigcms{:U('employee_spread_hb',array('share_type'=>'unit_manager'))}"><em class="group"></em><p>邀单位入驻</p></a>
                    </li>
                    <li>
                        <a href="{pigcms{:U('Employee/employee_info')}" target="self"><em class="my"></em><p>修改信息</p></a>
                    </li>
                </ul>
            </footer>
            <!--<button class="weui-btn weui-btn_primary signUp " style="width:60%;background-color: #42bd56;">推广海报</button>-->
        </div>



        </div>
<!--    <div class="weui-tabbar">-->
<!--        <a href="javascript:void(0);" class="weui-tabbar__item weui-bar__item--on">-->
<!--            <div class="weui-tabbar__icon">-->
<!--                <img src="http://www.group.com/tpl/Wap/pure/static/images/new_my/pic-default.png" alt="" />-->
<!--            </div>-->
<!--            <p class="weui-tabbar__label">首  页</p>-->
<!--        </a>-->
<!--        <a href="javascript:void(0);" class="weui-tabbar__item">-->
<!--            <div class="weui-tabbar__icon">-->
<!--                <img src="http://www.group.com/tpl/Wap/pure/static/images/new_my/pic-default.png" alt="" />-->
<!--            </div>-->
<!--            <p class="weui-tabbar__label">分  享</p>-->
<!--        </a>-->
<!--        <a href="javascript:void(0);" class="weui-tabbar__item" id="collect">-->
<!--            <div class="weui-tabbar__icon">-->
<!--                <img src="http://www.group.com/tpl/Wap/pure/static/images/new_my/pic-default.png" alt="" />-->
<!--            </div>-->
<!--            <p class="weui-tabbar__label">收  藏</p>-->
<!--        </a>-->
<!--        <a href="javascript:void(0);" class="weui-tabbar__item" id="zan">-->
<!--            <div class="weui-tabbar__icon">-->
<!--                <img src="http://www.group.com/tpl/Wap/pure/static/images/new_my/pic-default.png" alt="" />-->
<!--            </div>-->
<!--            <p class="weui-tabbar__label">点  赞</p>-->
<!--        </a>-->
<!--        <a href="javascript:void(0);" class="weui-tabbar__item">-->
<!--            <div class="weui-tabbar__icon">-->
<!--                <img src="http://www.group.com/tpl/Wap/pure/static/images/new_my/pic-default.png" alt="" />-->
<!--            </div>-->
<!--            <p class="weui-tabbar__label">预  约</p>-->
<!--        </a>-->
<!--    </div>-->
    <div id="production"></div>
</body>

<script type="text/html" id="sing">
    <div class="weui-mask weui-mask--visible hide_info" style="z-index:99"></div>
    <div class="weui-dialog weui-dialog--visible yui-auth-form  auth-animated">
        <div class="weui-panel__ft weui-panel__hd pd0">
            <a href="{pigcms{:U('employee_spread_hb',array('share_type'=>'normal'))}" class="weui-cell weui-cell_access weui-cell_link">
                <div class="weui-cell__bd share_type" style="font-size: 16px;">我要分享给普通用户</div>
            </a>
        </div>
        <div class="weui-panel__ft weui-panel__hd pd0 ">
            <a href="{pigcms{:U('employee_spread_hb',array('share_type'=>'friend'))}" class="weui-cell weui-cell_access weui-cell_link">
                <div class="weui-cell__bd share_type" style="font-size: 16px;">我要分享给同伴</div>
            </a>
        </div>
        <div class="weui-panel__ft weui-panel__hd pd0 ">
            <a href="{pigcms{:U('employee_spread_hb',array('share_type'=>'unit_manager'))}" class="weui-cell weui-cell_access weui-cell_link">
                <div class="weui-cell__bd share_type" style="font-size: 16px;">我要分享给单位管理人员</div>
            </a>
        </div>
    </div>
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
<script src="{pigcms{$static_path}employee/js/common/simplite.js" type="text/javascript" charset="UTF-8"></script>
<script src="{pigcms{$static_path}employee/js/common/layer.js" type="text/javascript"></script>
<script type="text/javascript">
    //点赞
    var View={
        init:function(){
             this.initClick()
        },
        initClick:function(){
            $('.info-title').on('click',function(){
                console.log('info-title');
                $('.info-title').removeClass('active');
                $('.info-title').css({'border':'none','color':'#333'});
                $(this).addClass('active');
                $('.all_content').hide();
                var gid = $(this).data('group_id');
                $('.info-content_'+gid).show();
                $(this).css({'border-bottom':'2px solid #04CB86', 'color': '#04CB86'});
            });
            setTimeout(function () {
                $(".imgs img").each(function(){
                    $(this).width();
                    console.log('width', $(this).width());
                    $(this).css("height", $(this).width());
                });
                $('.all_content').hide();
                $('.all_content.active').show();
                $('.info-title.active').css({'border-bottom':'2px solid #04CB86', 'color': '#04CB86'});
            },80);
            $('#zan').on('click',function(){
                var id = "{pigcms{$list['id']}";
                $.ajax({
                    url: "{pigcms{:U('ajax_zan')}",
                    type: "POST",
                    dataType: "json",
                    data: {'thumb_id' : id},
                    success: function (data) {
                        console.log(data);
                        if(data.status == 1){
                            var str = '<img width="20px;" src="{pigcms{$static_path}employee/images/zan.png">     ';
                            str = str+data.count
                            $(".show_zan").html(str);
                        }
                        //提示
                        layer.open({
                            content: data.msg
                            ,skin: 'msg'
                            ,time: 2 //2秒后自动关闭
                        });
                    },
                    error: function () {
                        layer.open({
                            content: '请求出错'
                            ,skin: 'msg'
                            ,time: 2 //2秒后自动关闭
                        });
                    }
                });
            });
            //收藏
            $('#collect').on('click',function(){
                var id = "{pigcms{$list['id']}";
                $.ajax({
                    url: "{pigcms{:U('ajax_collect')}",
                    type: "POST",
                    dataType: "json",
                    data: {'collect_id' : id},
                    success: function (data) {
                        console.log(data);
                        if(data.status == 1){
                            var str = '<img width="20px;" src="{pigcms{$static_path}employee/images/collect.png">     ';
                            str = str+data.count
                            $(".show_collect").html(str);
                        }
                        //提示
                        layer.open({
                            content: data.msg
                            ,skin: 'msg'
                            ,time: 2 //2秒后自动关闭
                        });
                    },
                    error: function () {
                        layer.open({
                            content: '请求出错'
                            ,skin: 'msg'
                            ,time: 2 //2秒后自动关闭
                        });
                    }
                });
            });
            //绑定店铺
            $('#bind_store').on('click',function () {
                var url = "{pigcms{:U('employee_info')}" ;
                window.location.href=url;
            });

            // 点击定位
            $('.navi').click(function(){
                var _this = $(this);
                if(!_this.data('lat') || !_this.data('lng') || !_this.data('name') || !_this.data('address')){
                    return false;
                }
                if(typeof wx !=='undefined'){
                    var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)'});
                    $.getJSON("/wap.php?g=Wap&c=Userlonglat&a=baiduToGcj02&baidu_lat="+_this.data('lat')+"&baidu_lng="+_this.data('lng'),function(result){
                        layer.close(layerLoadTip);
                        if(result['status'] == 1){
                            wx.openLocation({
                                latitude: result['info']['lat'],
                                longitude: result['info']['lng'],
                                name: _this.data('name'), // 位置名
                                address: _this.data('address'), // 地址详情说明
                                scale: 18, // 地图缩放级别,整形值,范围从1~28。默认为最大
                                infoUrl: window.location.href // 在查看位置界面底部显示的超链接,可点击跳转
                            });
                        }else{
                            layer.open({
                                content: result.msg
                                ,btn: '好的'
                            });
                        }
                    });
                }else{
                    var url = "http://api.map.baidu.com/direction?origin=latlng:"+_this.data('lat')+","+_this.data('lng')+"|name:"+encodeURIComponent('当前位置')+"&destination=latlng:"+_this.data('lat')+","+_this.data('lng')+"|name:"+encodeURIComponent(_this.data('address'))+"&mode=driving&region=100010000&src=baidu|jsapi&output=html";
                    // console.log(url);
                    location.href = url;
                }
                return false;
            });

            /*页面点击事件*/
            $(document).on('click','.link-url',function(){
                window.location.href = $(this).data('url');
            });
            // 点击推广
            $(document).on("click",'.signUp',function(){
                var tpl = Simplite.getTemplate('sing');
                var compiledTpl = Simplite.compile(tpl)([]);
                $('#production').html(compiledTpl);
                setTimeout(function () {
                    $(" #production .yui-auth-form").removeClass("auth-animated")

                },1000);
            });
            // 点击隐藏
            $(document).on("click",'.hide_info',function(){
                $(".yui-auth-form").addClass("close-animated");
                $(".yui-auth-layer").addClass("close-animated");
                setTimeout(function () {
                    $("#production").html("");
                },20);
            });

        }
    }
    window.View=View;
    View.init()

</script>

<script type="text/javascript">
    window.shareData = {
        "moduleName":"Employee",
        "moduleID":"0",
        "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Employee/employee_detail', array('employee_id' => $list['id']))}",
        "tTitle": "{pigcms{$config['employee_alias']}详情",
        "tContent": "{pigcms{$config.site_name}"
    };
</script>
{pigcms{$shareScript}
<if condition="$is_app_browser">
    <script type="text/javascript">
        
        if("{pigcms{$app_browser_type}" == 'android'){
                window.lifepasslogin.shareLifePass("{pigcms{$config['employee_alias']}详情","","<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>","{pigcms{$config.site_url}{pigcms{:U('Employee/employee_detail', array('employee_id' => $list['id']))}");
            } else {
                iosCommon.iosFunction('shareLifePass', {name:'shareLifePass', imgUrl: window.shareData.imgUrl, sendFriendLink:window.shareData.sendFriendLink, tTitle:window.shareData.tTitle, tContent:window.shareData.tContent });
            }
    </script>
</if>
</html>