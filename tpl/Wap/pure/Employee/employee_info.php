<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>{pigcms{$config.employee_alias}入驻信息</title>
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210">
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/weui.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/custom.css"/>
    <link href="{pigcms{$static_path}employee/css/index.css?001" rel="stylesheet"/>
    <style>
        .weui-dialog__btn, .weui-navbar__item.weui_bar__item_on, .color-strong, .chip-row .toutiao, .weui-cells_checkbox .weui-check:checked+.weui-icon-checked:before, .weui-vcode-btn, .weui-dialog__btn_primary, .weui-cells_radio .weui-check:checked+.weui-icon-checked:before, .weui-icon-checked:before, .weui-agree__checkbox:checked:before, .weui-icon-success-circle, .weui-icon-success-no-circle, .weui-search-bar__cancel-btn, .weui-tabbar__item.weui-bar__item_on .weui-tabbar__icon, .weui-tabbar__item.weui-bar__item_on .weui-tabbar__icon>i, .weui-tabbar__item.weui-bar__item_on .weui-tabbar__label,.main_color,.weui-tabbar__item.weui-bar__item--on .weui-tabbar__label,.picker-button,.weui-form-preview__btn_primary {color:#ff4d00!important}
        .weui_bar__item_on span:after,.weui-btn_primary, .weui-btn_primary:not(.weui-btn_disabled):active,.weui-btn_mini,.x_header, .main_bg,.position li.current, .position1 li.current,.post-tags .tag-on.weui-btn_default,.is-green, .weui-slider__track{background-color:#ff4d00!important}

        .border_bottom_info{
            border-bottom: 1px solid #e5e5e5;
        }
        .uploader__file_del{cursor: pointer}

        .pullup,.loading{
            text-align: center;
            padding:0 0 10px 0;
            font-size: 14px;
            color: #999;
        }
        .loading img{
            height: 14px;
            vertical-align: middle;
            margin-right: 2px;
        }
        .weui-select_info {
             border: 0;
             outline: 0;
             background-color: transparent;
             width: 100%;
             font-size: inherit;
             height: 45px;
             line-height: 45px;
             position: relative;
             z-index: 1;
             padding-left: 15px;
        }
        .choose_category {float:left;width: 31%;}
        #storeList {
            position: absolute;
            z-index: 1;
            top: 0;
            bottom: 1px;
            left: 0;
            width: 100%;
            overflow: hidden;
        }
        .noMore {
            text-align: center;
            line-height: 50px;
        }
        .loadMoreList {
            text-align: center;
            line-height: 50px;
        }
        .weui-cell_top {
            margin-top: 50px;
        }
    </style>
    <script>
        var IN_WECHAT = '', AVATAR = "{pigcms{$user_session.avatar|default='./static/images/user_avatar.jpg'}", UID = '{pigcms{$user_session.uid|intval=###}', PLZINPUT = '请输入文字', BODA = '拨打电话', DELCONFIRM = '确定删除?', SUIBIANSHUO = '随便说点什么', HUIFU1 = '回复：', ERROR_TIP = '数据异常，请刷新页面再试。';
        var loading = false, page = 1, _APPNAME = '{pigcms{$config.site_url}/wap.php?c=Classifynew', _APPTITLE = '{pigcms{$config.classify_title}', scrollto =0, plzinput_mobile = '请输入手机号码';
        var cookiepre = 'classify_', cookiedomain = '', cookiepath = '/', IN_APP='', LISTINCR = '1', _URLEXT = '', GSITE='{pigcms{$config.site_url}/', MAXTAG = '5', MAXTAGTIP = "最多选5个标签", FASIXIN = '发私信',XL=1, LXFS ='查看联系方式',CKXFF = '查看联系方式需支付<strong class="amount">', QRZF ='</strong>{pigcms{$config['Currency_txt']}<br>确认支付？';
        var employee_category="{pigcms{:U('Employee/employee_category')}";
        var category="{pigcms{:U('Employee/employee_info')}";
        var ajaxImgUpload = "{pigcms{:U('ajaxImgUpload')}";
        var ajax_url_root = "{pigcms{$config.site_url}/wap.php?c=Employee&a=";
        var examine_status_info = "{pigcms{$info['examine_status']}";
        var static_path = "{pigcms{$static_path}";
    </script>

    <script src="{pigcms{$static_path}employee/js/common/jquery-2.1.4.js" type="text/javascript"></script>
    <script src="{pigcms{$static_path}employee/js/common/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}employee/js/common/common.js?t={pigcms{:time()}"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444"></script>

</head>
<body>
	<link rel="stylesheet" href="{pigcms{$static_path}employee/css/cropper.css"/>
	<style>.x_header{z-index:10}.weui-cells_form .weui-cell__ft{font-size:17px}</style>
	<if condition="!$is_weixin_browser && !$is_app_browser">
		<header class="x_header bgcolor_11 cl f15">
			<a class="z f14" href="javascript:window.history.go(-1);"><i class="iconfont icon-fanhuijiantou w15"></i>返回</a>
			<div class="navtitle">入驻信息</div>
		</header>
<!--		<div class="x_header_fix" ></div>-->
	</if>
	<div class="page__bd">
		<form action="{pigcms{:U('Employee/employee_info_edit')}" method="post" id="form" enctype="multipart/form-data">
<!--			<div class="weui-cells__title" style="margin-bottom:1em;">-->
<!--				【提醒】请认真全面填写信息，以便大家查阅。-->
<!--				<br/>【声明】填写的所有信息，平台只负责发布、展示，与平台本身无关，不承担任何责任。-->
<!--				<if condition="$info['examine_status'] eq 0"><br/>您已提交入驻信息，审核通过后即拥有个人主页</if>-->
<!--			</div>-->
            <input name="store_id" class="employee_store_id"  value=""  type="hidden" />

            <if condition="$info['examine_status'] neq 1">

                <div class="weui-cells weui-cells_form <if condition="!$is_weixin_browser && !$is_app_browser">weui-cell_top</if>">
                    <div class="weui-cell">
                        <div class="weui-cell__hd"><label class="weui-label">审核状态：</label></div>

                        <div class="weui-cell__bd">
                            <if condition="$info['examine_status'] eq 0"><span>待审核</span><else/><span>审核拒绝</span></if>
                        </div>
                    </div>
                    <if condition="$info['examine_status'] eq 2">
                        <div class="weui-cell">
                            <div class="weui-cell__hd"><label class="weui-label">拒绝理由：</label></div>

                            <div class="weui-cell__bd">
                                {pigcms{$info['refuse_info']}
                            </div>
                        </div>

                    </if>


                </div>
                <else/>
                <div class="weui-cells weui-cells_form <if condition="!$is_weixin_browser && !$is_app_browser">weui-cell_top</if>">
                <div class="weui-cell">
                    <div class="weui-cell__bd">
                        您已经入驻成功！<if condition="$config['examine_employee']">如果修改内容将重新进行审核！</if>
                    </div>
                </div>
                </div>
            </if>
			<div class="weui-cells weui-cells_form">

                <div class="weui-cell weui-cell_select weui-cell_select-after border_bottom_info">
                    <div class="cell__bd" id="choose_category" cat_id="{pigcms{$cat_id}" cat_fid="{pigcms{$cat_fid}" cat_pfcid="{pigcms{$cat_pfcid}" style="display:inline;width: 100%;"></div>
                </div>
                <div class="write_box">
                    <php>
                        if ($write_group_info) {
                        $num = 1;
                        $key_val = 0;
                    </php>
                        <volist name="write_group_info" id="item">
                            <php>
                                $num = count($item['write_info']);
                            </php>
                            <if condition="$item['write_info']">
                                <php>if ($item['gname']) {</php>
                                    <div class="weui-cell" style="color: #F58444;font-weight: bold;">
                                        {pigcms{$item['gname']} :
                                        <php>if ($item['gid']==4) {</php>
                                        （能更好的展现自己)
                                        <php>}</php>
                                    </div>
                                <php>}</php>
                                <volist name="item['write_info']" id="vv" key="k">
                                    <php>$kk = intval($key_val) + intval($k);</php>
                                    <!--0 单行文本 1 多行文本 2 地图 3 下拉框选择 4 数字 5 邮件 6 日期 7 时间 8 日期时间 9 手机 10 多张图片-->
                                    <if condition="$vv['type'] eq 0">
                                        <div class="weui-cell">
                                            <div class="weui-cell__hd">
                                                <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
                                            </div>
                                            <div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
                                            <div class="weui-cell__bd">
                                                <input data-role="text" <if condition="$info['examine_status'] eq 0">readonly="readonly"</if>  name="write_field[{pigcms{$kk}][value]" class="weui-input value_{pigcms{$vv['alias_name']}" placeholder="{pigcms{$vv['des']}<if condition="!$vv['iswrite']">（选填）</if>" type="text" value="{pigcms{$vv['value']}" <if condition="$vv['iswrite']">data-required="required"</if>/>
                                                <php>if ($vv['alias_name'] == 'work_unit' && $info['examine_status'] != 0) { </php>
                                                <div id="searchBtn" class="listSearchBtn page-link" data-url="shopSearch"><div></div></div>
                                                <php>}</php>
                                                <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
                                            </div>
                                        </div>
                                    <elseif condition="$vv['type'] eq 1"/>
                                        <div class="weui-cell">
                                            <div class="weui-cell__hd">
                                                <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
                                            </div>
                                            <div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
                                            <div class="weui-cell__bd">
                                                <textarea data-role="textarea" <if condition="$info['examine_status'] eq 0">readonly="readonly"</if> class="weui-textarea value_{pigcms{$vv['alias_name']}" rows="2" name="write_field[{pigcms{$kk}][value]"  placeholder="{pigcms{$vv.des}<if condition="!$vv['iswrite']">（选填）</if>"   <if condition="$vv['iswrite']">data-required="required"</if>>{pigcms{$vv['value']}</textarea>

                                                <input name="write_field[{pigcms{$kk}][id]"  value="{pigcms{$vv['id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
                                            </div>
                                        </div>
                                    <elseif condition="$vv['type'] eq 2"/>
                                        <div class="weui-cell">
                                            <div class="weui-cell__hd">
                                                <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
                                            </div>
                                            <div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
                                            <div class="weui-cell__bd">
                                                <input  <if condition="$info['examine_status'] neq 0">data-role="position"</if> data-key="{pigcms{$kk}" class="value_{pigcms{$vv['alias_name']} weui-input position_info position_key_{pigcms{$kk}" type="text" name="write_field[{pigcms{$kk}][value]" value="{pigcms{$vv['value']}" placeholder="{pigcms{$vv.des}<if condition="!$vv['iswrite']">（可选）</if>" readonly="readonly" <if condition="$vv['iswrite']">data-required="required"</if>/>

                                                <input name="write_field[{pigcms{$kk}][id]"  value="{pigcms{$vv['id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][long]" value="{pigcms{$vv['long']}" class="long_{pigcms{$vv['alias_name']} long_{pigcms{$kk}" type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][lat]" value="{pigcms{$vv['lat']}" class="lat_{pigcms{$vv['alias_name']} lat_{pigcms{$kk}" type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][address]" value="{pigcms{$vv['address']}" class="address_{pigcms{$vv['alias_name']} address_{pigcms{$kk}" type="hidden" />
                                            </div>
                                        </div>

                                        <div class="weui-cell">
                                            <div class="weui-cell__hd">
                                                <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
                                            </div>
                                            <div class="weui-cell__hd"><label class="weui-label">补充地址</label></div>
                                            <div class="weui-cell__bd">
                                                <input data-role="position-desc" <if condition="$info['examine_status'] eq 0">readonly="readonly"</if> data-key="{pigcms{$kk}" class="value-desc_{pigcms{$vv['alias_name']} weui-input position_desc position_desc_key_{pigcms{$kk}" type="text" name="write_field[{pigcms{$kk}][value-desc]" value="{pigcms{$vv['value-desc']}" placeholder="选择地图后填写详细地址<if condition="!$vv['iswrite']">（选填）</if>" <if condition="$vv['iswrite']">data-required="required"</if>/>
                                            </div>
                                        </div>
                                    <elseif condition="$vv['type'] eq 3"/>
                                        <div class="weui-cell <if condition="$info['examine_status'] neq 0"> weui-cell_select weui-cell_select-after </if>">
                                        <div class="weui-cell__hd">
                                            <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
                                        </div>
                                        <div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
                                            <div class="weui-cell__bd">
                                                <php>if ($info['examine_status'] == 0) {</php>
                                                    <input data-role="select" name="write_field[{pigcms{$kk}][value]"  class="weui-input" type="text" value="{pigcms{$vv['value']}"  <if condition="$info['examine_status'] eq 0">readonly="readonly"</if>/>
                                                <php>} else {</php>
                                                <select class="weui-select" name="write_field[{pigcms{$kk}][value]" data-role="select">
                                                    <option value="" <if condition="$vv['value'] eq ''"> selected</if>>{pigcms{$vv['des']}</option>
                                                    <volist name="vv['use_field']" id="item">
                                                        <option value="{pigcms{$item}" <if condition="$vv['value'] eq $item OR $vv['value'] eq $key"> selected</if>>{pigcms{$item}</option>
                                                    </volist>
                                                </select>
                                                <php>}</php>
                                                <input name="write_field[{pigcms{$kk}][id]"  value="{pigcms{$vv['id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][use_field]"  value="{pigcms{$vv['use_field']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
                                            </div>
                                        </div>
                                    <elseif condition="$vv['type'] eq 4"/>
                                        <div class="weui-cell">
                                            <div class="weui-cell__hd">
                                                <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
                                            </div>
                                            <div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
                                            <div class="weui-cell__bd">
                                                <input data-role="number" <if condition="$info['examine_status'] eq 0">readonly="readonly"</if> name="write_field[{pigcms{$kk}][value]" class="value_{pigcms{$vv['alias_name']} weui-input" placeholder="{pigcms{$vv['des']}" type="number" value="{pigcms{$vv['value']}" onkeyup="value=clearNoNum(this.value)"/>

                                                <input name="write_field[{pigcms{$kk}][id]"  value="{pigcms{$vv['id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
                                            </div>
                                        </div>
                                    <elseif condition="$vv['type'] eq 5"/>
                                        <div class="weui-cell">
                                            <div class="weui-cell__hd">
                                                <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
                                            </div>
                                            <div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
                                            <div class="weui-cell__bd">
                                                <input class="value_{pigcms{$vv['alias_name']} weui-input" <if condition="$info['examine_status'] eq 0">readonly="readonly"</if> type="email" name="write_field[{pigcms{$kk}][value]" value="{pigcms{$vv['value']}" placeholder="{pigcms{$vv.des}<if condition="!$vv['iswrite']">（选填）</if>" data-role="number" <if condition="$vv['iswrite']">data-required="required"</if>/>

                                                <input name="write_field[{pigcms{$kk}][id]"  value="{pigcms{$vv['id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
                                            </div>
                                        </div>
                                    <elseif condition="$vv['type'] eq 6"/>
                                        <div class="weui-cell">
                                            <div class="weui-cell__hd">
                                                <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
                                            </div>
                                            <div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
                                            <div class="weui-cell__bd">

                                                <input class="value_{pigcms{$vv['alias_name']} weui-input" <if condition="$info['examine_status'] eq 0">readonly="readonly"</if> type="date" name="write_field[{pigcms{$kk}][value]" value="{pigcms{$vv['value']}" placeholder="{pigcms{$vo.des}<if condition="!$vv['iswrite']">（选填）</if>" data-role="date" <if condition="$vv['iswrite']">data-required="required"</if>/>

                                                <input name="write_field[{pigcms{$kk}][id]"  value="{pigcms{$vv['id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
                                            </div>
                                        </div>
                                    <elseif condition="$vv['type'] eq 7"/>
                                        <div class="weui-cell">
                                            <div class="weui-cell__hd">
                                                <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
                                            </div>
                                            <div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
                                            <div class="weui-cell__bd">

                                                <input class="value_{pigcms{$vv['alias_name']} weui-input" <if condition="$info['examine_status'] eq 0">readonly="readonly"</if> type="time" name="write_field[{pigcms{$kk}][value]" value="{pigcms{$vv['value']}" placeholder="{pigcms{$vv.des}<if condition="!$vv['iswrite']">（可选）</if>" data-role="time" <if condition="$vv['iswrite']">data-required="required"</if>/>

                                                <input name="write_field[{pigcms{$kk}][id]"  value="{pigcms{$vv['id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
                                            </div>
                                        </div>
                                    <elseif condition="$vv['type'] eq 8"/>
                                        <div class="weui-cell">
                                            <div class="weui-cell__hd">
                                                <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
                                            </div>
                                            <div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
                                            <div class="weui-cell__bd">

                                                <input <if condition="$info['examine_status'] eq 0">readonly="readonly"</if> style="float: left;width: 60%;" class="date_value_{pigcms{$vv['alias_name']} weui-input" type="date" name="write_field[{pigcms{$kk}][date_value]" value="{pigcms{$vv['date_value']}" placeholder="{pigcms{$vv.des}<if condition="!$vv['iswrite']">（可选）</if>" data-role="date" <if condition="$vv['iswrite']">data-required="required"</if>/>
                                                <input <if condition="$info['examine_status'] eq 0">readonly="readonly"</if> style="float: left;width: 40%;" class="time_value_{pigcms{$vv['alias_name']} weui-input" type="time" name="write_field[{pigcms{$kk}][time_value]" value="{pigcms{$vv['time_value']}" data-role="time" <if condition="$vv['iswrite']">data-required="required"</if>/>

                                                <input name="write_field[{pigcms{$kk}][id]"  value="{pigcms{$vv['id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
                                            </div>
                                        </div>
                                    <elseif condition="$vv['type'] eq 9"/>
                                        <div class="weui-cell">
                                            <div class="weui-cell__hd">
                                                <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
                                            </div>
                                            <div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
                                            <div class="weui-cell__bd">

                                                <input <if condition="$info['examine_status'] eq 0">readonly="readonly"</if> class="weui-input" type="tel" name="write_field[{pigcms{$kk}][value]"  value="{pigcms{$vv['value']}" placeholder="{pigcms{$vv.des}<if condition="!$vv['iswrite']">（可选）</if>" data-role="phone" <if condition="$vv['iswrite']">data-required="required"</if>/>

                                                <input name="write_field[{pigcms{$kk}][id]"  value="{pigcms{$vv['id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
                                            </div>
                                        </div>
                                    <elseif condition="$vv['type'] eq 10"/>
                                        <div class="weui-cell">
                                            <div class="weui-cell__bd">
                                                <div class="weui-uploader">
                                                    <div class="weui-uploader__hd">
                                                        <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
                                                        <p class="weui-uploader__title">{pigcms{$vv['name']}</p>
                                                        <div class="weui-uploader__info" style="font-size:12px;">{pigcms{$vv['des']} 最多上传8张照片<php>if($vv['iswrite']>0){echo '<strong style="color:red;">(必传)</strong>';}else{echo '（可不上传）';}</php></div>
                                                    </div>
                                                    <div class="weui-uploader__bd">
                                                        <ul class="weui-uploader__files" id="uploaderFiles_{pigcms{$kk}">
                                                            <if  condition="$vv['value'] && $info['examine_status'] neq 0">
                                                                <volist name="vv['value']" id="item">
                                                                    <li class="uploader__file_del weui-uploader__file weui-uploader__file_status" style="background-image:url({pigcms{$item})"><input type="hidden" name="write_field[{pigcms{$kk}][value][]" value="{pigcms{$item}"/><div class="weui-uploader__file-content"><i class="weui-icon-warn iconfont icon-shanchu"></i></div></li>
                                                                </volist>
                                                                <elseif condition="$vv['value'] && $info['examine_status'] eq 0"/>
                                                                <volist name="vv['value']" id="item">
                                                                    <li class="weui-uploader__file weui-uploader__file_status" style="background-image:url({pigcms{$item})"><input type="hidden" name="write_field[{pigcms{$kk}][value][]" value="{pigcms{$item}"/></li>
                                                                </volist>
                                                            </if>
                                                        </ul>
                                                        <if condition="$info['examine_status'] neq 0">
                                                            <div class="weui-uploader__input-box">
                                                                <input id="uploaderInput_{pigcms{$kk}" class="weui-uploader__input" type="file"/>

                                                                <input name="write_field[{pigcms{$kk}][id]"  value="{pigcms{$vv['id']}"  type="hidden" />
                                                                <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
                                                                <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
                                                                <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
                                                                <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
                                                                <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
                                                                <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
                                                            </div>
                                                        </if>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <elseif condition="$vv['type'] eq 11"/>
                                        <div class="weui-cell">
                                            <div class="weui-cell__hd">
                                                <php>if($vv['iswrite']>0){echo '<strong style="color:red;">*</strong>';}else{echo '&nbsp;';}</php>
                                            </div>
                                            <div class="weui-cell__hd"><label class="weui-label">{pigcms{$vv['name']}</label></div>
                                            <div class="weui-cell__bd weui-cells_radio" style="display:flex">
                                                <if condition="$info['examine_status'] eq 0 && $vv['value'] eq 1">
                                                    <div class="weui-cell__bd" style="    text-align: left;">
                                                        <label>
                                                            <span>男</span>
                                                        </label>
                                                    </div>
                                                    <elseif condition="$info['examine_status'] eq 0 && $vv['value'] eq 2"/>
                                                    <div class="weui-cell__bd" style="    text-align: left;">
                                                        <label>
                                                            <span>女</span>
                                                        </label>
                                                    </div>
                                                    <else/>
                                                    <div class="weui-cell__bd" style="    text-align: left;">
                                                        <label for="x11_{pigcms{$kk}"><span>男</span>
                                                            <input data-role="checked" type="radio" class="weui-check" name="write_field[{pigcms{$kk}][value]" id="x11_{pigcms{$kk}" value="1" <if condition="$vv['value'] eq 1">checked="checked"</if>><span class="weui-icon-checked" style="border: #ff4d00 1px solid;border-radius: 18%;"></span>
                                                        </label>
                                                    </div>
                                                    <div class="weui-cell__bd" style="    text-align: left;">
                                                        <label for="x12_{pigcms{$kk}"><span>女</span>
                                                            <input data-role="checked" type="radio" class="weui-check" name="write_field[{pigcms{$kk}][value]" id="x12_{pigcms{$kk}" value="2" <if condition="$vv['value'] eq 2">checked="checked"</if>><span class="weui-icon-checked" style="border: #ff4d00 1px solid;border-radius: 18%;"></span>
                                                        </label>
                                                    </div>
                                                </if>
                                                <input name="write_field[{pigcms{$kk}][id]"  value="{pigcms{$vv['id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
                                                <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
                                            </div>
                                        </div>
                                    <elseif condition="$vv['type'] eq 12"/>
                                        <div class="weui-cell">
                                            <div class="weui-cell__bd">
                                                <div class="weui-uploader">
                                                    <div class="weui-uploader__hd">
                                                        <p class="weui-uploader__title">{pigcms{$vv['name']}</p>
                                                        <div class="weui-uploader__info" style="font-size:12px;">{pigcms{$vv['des']} （可不上传）</div>
                                                    </div>
                                                    <div class="weui-uploader__bd">
                                                        <ul class="weui-uploader__files" id="uploaderAvatar_{pigcms{$kk}">
                                                            <if  condition="$vv['value'] && $info['examine_status'] neq 0">
                                                                <volist name="vv['value']" id="item">
                                                                    <li class="uploader__file_del weui-uploader__file weui-uploader__file_status" style="background-image:url({pigcms{$item})"><input type="hidden" name="write_field[{pigcms{$kk}][value][]" value="{pigcms{$item}"/><div class="weui-uploader__file-content"><i class="weui-icon-warn iconfont icon-shanchu"></i></div></li>
                                                                </volist>
                                                                <elseif condition="$vv['value'] && $info['examine_status'] eq 0"/>
                                                                <volist name="vv['value']" id="item">
                                                                    <li class="weui-uploader__file weui-uploader__file_status" style="background-image:url({pigcms{$item})"><input type="hidden" name="write_field[{pigcms{$kk}][value][]" value="{pigcms{$item}"/></li>
                                                                </volist>
                                                            </if>
                                                        </ul>
                                                        <if condition="$info['examine_status'] neq 0">
                                                            <div class="weui-uploader__input-box">
                                                                <input id="uploaderAvatarInput_{pigcms{$kk}" class="weui-uploader__input" type="file"/>

                                                                <input name="write_field[{pigcms{$kk}][id]"  value="{pigcms{$vv['id']}"  type="hidden" />
                                                                <input name="write_field[{pigcms{$kk}][name]"  value="{pigcms{$vv['name']}"  type="hidden" />
                                                                <input name="write_field[{pigcms{$kk}][type]"  value="{pigcms{$vv['type']}"  type="hidden" />
                                                                <input name="write_field[{pigcms{$kk}][sort]"  value="{pigcms{$vv['sort']}"  type="hidden" />
                                                                <input name="write_field[{pigcms{$kk}][iswrite]"  value="{pigcms{$vv['iswrite']}"  type="hidden" />
                                                                <input name="write_field[{pigcms{$kk}][cat_id]"  value="{pigcms{$vv['cat_id']}"  type="hidden" />
                                                                <input name="write_field[{pigcms{$kk}][alias_name]"  value="{pigcms{$vv['alias_name']}"  type="hidden" />
                                                            </div>
                                                        </if>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </if>
                                </volist>
                            </if>
                            <php>$key_val = intval($key_val) + intval($num);</php>

                        </volist>
                    <php>
                        }
                    </php>



                    <script>
                        var user_long = "{pigcms{$long_lat['long']}",user_lat  = "{pigcms{$long_lat['lat']}",user_city='{pigcms{$city_name}';
                    </script>

                    <if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
                        <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places,drawing&key={pigcms{$config.google_map_ak}"></script>
                        <else />
                        <script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
                        <script type="text/javascript" src="https://api.map.baidu.com/library/DrawingManager/1.4/src/DrawingManager_min.js"></script>
                    </if>
                    
                    <script src="{pigcms{$static_path}/js/common_wap.js"></script>
                    <script src="{pigcms{$static_path}layer/layer.m.js"></script>
                </div>
            </div>
			<label class="weui-agree mt10" onclick="$(&quot;#agree__text&quot;).popup();">
				<input id="weuiAgree" type="checkbox" checked="checked" disabled="" readonly="" class="weui-agree__checkbox"/>
				<span class="weui-agree__text">
					阅读并同意<a href="javascript:void(0);">《用户协议条款》</a>
				</span>
			</label>
			<div id="agree__text" class="weui-popup__container">
				<div class="weui-popup__overlay"></div>
				<div class="weui-popup__modal">
					<div class="fixpopuper">
					<article class="weui-article">
						<h1>用户协议条款</h1>
						<section>
                            <if condition="$employee_register_agreement eq ''">
                                <section>
                                    <h3>一、服务使用规范：</h3>
                                    <p>
                                    </p><p>通过本平台服务平台，您可以按照本平台的规则填写各种信息。但所填写的信息不得含有以下内容：</p>
                                    <p>1) 反对宪法所确定的基本原则，煽动抗拒、破坏宪法和法律、行政法规实施的；<br>
                                        2) 煽动危害国家安全、泄露国家秘密、颠覆国家政权，推翻社会主义制度的；<br>
                                        3) 煽动分裂国家、破坏国家统一、损害国家荣誉和民族利益的；<br>
                                        4) 煽动民族仇恨、民族歧视，破坏民族团结的；<br>
                                        5) 捏造或者歪曲事实，散布谣言，扰乱社会秩序的；<br>
                                        6) 进行政治宣传或破坏国家宗教政策、宣扬封建迷信、淫秽、色情、赌博、暴力、凶杀、恐怖、教唆犯罪的；<br>
                                        7) 公然侮辱他人或者捏造事实诽谤他人的，或者进行其他恶意攻击的；<br>
                                        8) 损害国家机关信誉的；<br>
                                        9) 其他违反宪法和法律法规的；</p>
                                    <p></p>
                                </section>
                                <else/>
                                <p style="text-align:center;">{pigcms{$employee_register_agreement|nl2br}</p>
                            </if>
						</section>
					</article>
						<div class="footer_fix"></div>
						<div class="bottom_fix"></div>
					</div>
					<div class="fix-bottom">
						<a class="weui-btn weui-btn_primary close-popup" href="javascript:;">我已阅读并同意此协议</a>
					</div>
				</div>
			</div>
            <if condition="$info['examine_status'] neq 0">
                <div class="fix-bottom" style="position: relative">
                    <input type="submit" class="weui-btn weui-btn_primary" name="dosubmit" id="dosubmit" value="确认修改">
                </div>
            </if>
		</form>
    </div>
	<div id="popctrl" class="weui-popup__container">
		<div class="weui-popup__overlay"></div>
		<div class="weui-popup__modal">
			<div style="height:100vh"><img id="photo"></div>
			<div class="pub_funcbar">
				<a class="weui-btn close-popup weui-btn_primary" data-method="confirm">确定</a>
				<a class="weui-btn close-popup weui-btn_default" data-method="destroy">取消</a>
			</div>
		</div>
	</div>
    <div id="popctrl_single" class="weui-popup__container">
        <div class="weui-popup__overlay"></div>
        <div class="weui-popup__modal">
            <div style="height:100vh"><img id="photo_single"></div>
            <div class="pub_funcbar">
                <a class="weui-btn close-popup weui-btn_primary" data-method="confirm">确定</a>
                <a class="weui-btn close-popup weui-btn_default" data-method="destroy">取消</a>
            </div>
        </div>
    </div>
    <section id="service-position" style="display:none;">
        <div class="yxc-pay-main yxc-payment-bg pad-bot-comm">
            <header class="yxc-brand">
                <a class="arrow-wrapper" data-role="cancel">
                    <i class="bt-brand-back"></i>
                </a>
                <span>选择位置</span>
            </header>
            <div class="selectInput">
                <input type="text" placeholder="直接输入定位您的地址" id="se-input-wd" autocomplete="off"/>
            </div>
            <div class="mapBox">
                <div id="allmap"></div>
                <div class="dot"></div>
            </div>
            <div class="mapaddress">
                <ul id="addressShow"></ul>
            </div>
        </div>
    </section>

    <div id="pageShopSearch" class="pageDiv">
        <div id="pageShopSearchHeader" class="searchHeader">
            <div id="pageShopSearchBackBtn" class="searhBackBtn"></div>
            <div id="pageShopSearchBox" class="searchBox">
                <div class="searchIco"></div>
                <input type="text" id="pageShopSearchTxt" class="searchTxt" placeholder="请输入店铺名称" autocomplete="off"/>
                <div class="delIco" id="pageShopSearchDel"><div></div></div>
            </div>
            <div id="pageShopSearchBtn" class="searchBtn">搜索</div>
        </div>
        <div id="storeList" style="display:none;">
            <div id="scroller">
                <div id="pullDown">
                    <span class="pullDownIcon"></span><span class="pullDownLabel">下拉可以刷新</span>
                </div>

                <script id="listShopTpl" type="text/html">
                    {{# for(var i = 0, len = d.length; i < len; i++){ }}
                    <dd class="choose_link" data-name="{{ d[i].name }}" data-province_id="{{ d[i].province_id }}" data-city_id="{{ d[i].city_id }}" data-area_id="{{ d[i].area_id }}" data-kefu_phone="{{ d[i].phone }}" data-adress="{{ d[i].adress }}" data-long="{{ d[i].long }}" data-lat="{{ d[i].lat }}" data-store_id="{{ d[i].store_id }}">
                        <div class="dealcard-img imgbox">
                            {{# if(d[i].isverify == 1){ }}
                            <img src="./static/images/kd_rec.png" style="    width: 41px;height: 15px;position: absolute;z-index: 15;margin: 2px 0 0 0;">
                            {{# } }}
                            <img src="{{ d[i].image }}" alt="{{ d[i].name }}" width="90" height="60">
                            {{# if(d[i].is_close){ }}<div class="closeTip">休息中</div>{{# } }}
                        </div>
                        <div class="dealcard-block-right">
                            <div class="brand">{{ d[i].name }}</div>
                        </div>
                    </dd>
                    {{# } }}
                </script>
                <section class="listBox">
                    <dl></dl>
                    <div class="no-deals hide">暂无信息</div>
                </section>
                <div id="pullUp">
                    <span class="pullUpIcon"></span><span class="pullUpLabel">上拉加载更多</span>
                </div>
            </div>
        </div>
    </div>


    <div class="masker" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);display:none;z-index:1000" onclick='$("#choose_sh").select("close")'></div>
	<div class="cl footer_fix"></div>


    <script id="listShopTpl" type="text/html">
        {{# for(var i = 0, len = d.length; i < len; i++){ }}
        <dd class="choose_link" data-name="{{ d[i].name }}" data-province_id="{{ d[i].province_id }}" data-city_id="{{ d[i].city_id }}" data-area_id="{{ d[i].area_id }}" data-kefu_phone="{{ d[i].kefu_phone }}" data-adress="{{ d[i].adress }}" data-long="{{ d[i].long }}" data-lat="{{ d[i].lat }}" data-store_id="{{ d[i].store_id }}">
            <div class="dealcard-img imgbox">
                {{# if(d[i].isverify == 1){ }}
                <img src="./static/images/kd_rec.png" style="    width: 41px;height: 15px;position: absolute;z-index: 15;margin: 2px 0 0 0;">
                {{# } }}
                <img src="{{ d[i].image }}" alt="{{ d[i].name }}" width="90" height="60">
                {{# if(d[i].is_close){ }}<div class="closeTip">休息中</div>{{# } }}
            </div>
            <div class="dealcard-block-right">
                <div class="brand">{{ d[i].name }}</div>
            </div>
        </dd>
        {{# } }}
    </script>



    <script>
		function clearNoNum(value){
			//清除"数字"和"."以外的字符

			value = value.replace(/[^\d.]/g,"");

			//验证第一个字符是数字而不是
			value = value.replace(/^\./g,"");

			//只保留第一个. 清除多余的
			value = value.replace(/\.{2,}/g,".");
			value = value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");

			//只能输入两个小数
			value = value.replace(/^(\-)*(\d+)\.(\d\d).*$/,'$1$2.$3');
			return value;
		}
		function setTypeidNew(dom){
			if($(dom).hasClass("tag-on")){
				$(dom).removeClass("tag-on");
				$(dom).next('input').attr('checked',false);
			}else{
				$(dom).addClass("tag-on");
				$(dom).next('input').attr('checked',true);
			}
		}


	</script>
    <script src="{pigcms{$static_path}employee/js/common/cropper.min.js" type="text/javascript"></script>
	<script>

        var upload_str = "{pigcms{$upload_str}";
        var avatar_str = "{pigcms{$avatar_str}";
        console.log('upload_str', upload_str);
        var upload_key = upload_str.split(",");
        var avatar_key = avatar_str.split(",");
        console.log('upload_key', upload_key);
        console.log('avatar_key', avatar_key);
        for (var m in upload_key) {
            if (upload_key[m]) {
                console.log('upload_key_'+m, upload_key[m]);
                img_info(upload_key[m]);
            }
        }
        for (var n in avatar_key) {
            if (avatar_key[n]) {
                console.log('avatar_key_'+n, avatar_key[n]);
                single_img_info(avatar_key[n]);
            }
        }

        function img_info(key) {
            var photoct = $('#photo');
            var imgpop = $('#popctrl');
            var uploadinput = $('#uploaderInput_'+key);
            var max_upload_num = 8;
            var boxer = $('#uploaderFiles_'+key);



            var URL = window.URL || window.webkitURL;
            var blobURL;
            var file;
            uploadinput.on("change", function () {
                photoct.cropper('destroy').cropper({
                    minContainerHeight: 320,
                    autoCropArea:1
                });
                var files = this.files;
                if (!photoct.data('cropper')) {
                    return;
                }
                if (files && files.length) {
                    file = files[0];
                    if (/^image\/\w+$/.test(file.type)) {
                        blobURL = URL.createObjectURL(file);
                        photoct.one('built.cropper', function () {
                            URL.revokeObjectURL(blobURL);
                        }).cropper('reset').cropper('replace', blobURL);
                        uploadinput.val('');
                        imgpop.popup();
                    } else {
                        $.toptip('Please choose an image file.', 'error');
                    }
                }
            });

            $('#popctrl .pub_funcbar a').each(function () {
                var btn = $(this);
                var mtd = btn.attr('data-method');
                btn.on('click', function () {
                    if (mtd == 'destroy') {
                    } else if (mtd == 'confirm') {
                        result = photoct.cropper('getCroppedCanvas');
                        photo = result.toDataURL('image/jpeg');
                        var img=photo.split(',')[1];
                        img=window.atob(img);
                        var ia = new Uint8Array(img.length);
                        for (var i = 0; i < img.length; i++) {
                            ia[i] = img.charCodeAt(i);
                        }
                        var bfile = new Blob([ia], {type:"image/jpeg"});


                        compress(bfile, function(cmp_photo){
                            var img = cmp_photo.split(',')[1];
                            img = window.atob(img);
                            var ia = new Uint8Array(img.length);
                            for (var i = 0; i < img.length; i++) {
                                ia[i] = img.charCodeAt(i);
                            }
                            var blob = new Blob([ia], {type:"image/jpeg"});
                            var formdata=new FormData();
                            formdata.append('filename','file');
                            formdata.append('file',blob);

                            $.ajax({
                                type: 'post',
                                url: ajaxImgUpload,
                                data :  formdata,
                                processData : false,
                                contentType : false,
                                success: function (data) {
                                    if(null==data){ tip_common('error|'+ERROR_TIP); return false;}
                                    if(data.indexOf('success')!==-1){
                                        var html = '<li class="uploader__file_del weui-uploader__file weui-uploader__file_status" style="background-image:url(' + cmp_photo + ')"><input type="hidden" name="write_field[' + key + '][value][]" value="' + data.split('|')[1] + '"/><div class="weui-uploader__file-content"><i class="weui-icon-warn iconfont icon-shanchu"></i></div></li>';
                                        $('#loadingimg').remove();
                                        boxer.append(html);
                                    } else {
                                        $('#loadingimg').remove();
                                        tip_common(data);
                                    }

                                    // 删除事件
                                    $(document).on('click', '.uploader__file_del', function () {
                                        var that = $(this);
                                        $.confirm(DELCONFIRM, function () {
                                            that.remove();
                                        }, function () {
                                        });
                                        return false;
                                    });
                                }
                            });
                        });

                        boxer.append('<li id="loadingimg" class="uploader__file_del weui-uploader__file weui-uploader__file_status"><div class="weui-uploader__file-content"><img src="{pigcms{$static_path}employee/images/loading.gif"/></div></li>');


                    } else {
                        var opt = btn.attr('data-option');
                        photoct.cropper(mtd, opt);
                    }
                });
            });
        }



        function single_img_info(key) {
            var photoct = $('#photo_single');
            var imgpop = $('#popctrl_single');
            var uploadinput = $('#uploaderAvatarInput_'+key);
            var boxer = $('#uploaderAvatar_'+key);

            console.log('uploadinput',uploadinput);
            console.log('boxer',boxer);


            var URL = window.URL || window.webkitURL;
            var blobURL;
            var file;
            $(document).on('change', '#uploaderAvatarInput_'+key, function () {
                console.log('uploaderAvatarInput_');
                photoct.cropper('destroy').cropper({
                    minContainerHeight: 320,
                    autoCropArea:1
                });
                var files = this.files;
                console.log('files1',files);
                if (!photoct.data('cropper')) {
                    return;
                }
                console.log('files',files);
                if (files && files.length) {
                    file = files[0];
                    if (/^image\/\w+$/.test(file.type)) {
                        blobURL = URL.createObjectURL(file);
                        photoct.one('built.cropper', function () {
                            URL.revokeObjectURL(blobURL);
                        }).cropper('reset').cropper('replace', blobURL);
                        uploadinput.val('');
                        imgpop.popup();
                    } else {
                        $.toptip('Please choose an image file.', 'error');
                    }
                }
            });

            $('#popctrl_single .pub_funcbar a').each(function () {
                var btn = $(this);
                var mtd = btn.attr('data-method');
                btn.on('click', function () {
                    if (mtd == 'destroy') {
                    } else if (mtd == 'confirm') {
                        result = photoct.cropper('getCroppedCanvas');
                        photo = result.toDataURL('image/jpeg');
                        var img=photo.split(',')[1];
                        img=window.atob(img);
                        var ia = new Uint8Array(img.length);
                        for (var i = 0; i < img.length; i++) {
                            ia[i] = img.charCodeAt(i);
                        }
                        var bfile = new Blob([ia], {type:"image/jpeg"});


                        compress(bfile, function(cmp_photo){
                            var img = cmp_photo.split(',')[1];
                            img = window.atob(img);
                            var ia = new Uint8Array(img.length);
                            for (var i = 0; i < img.length; i++) {
                                ia[i] = img.charCodeAt(i);
                            }
                            var blob = new Blob([ia], {type:"image/jpeg"});
                            var formdata=new FormData();
                            formdata.append('filename','file');
                            formdata.append('file',blob);

                            $.ajax({
                                type: 'post',
                                url: ajaxImgUpload,
                                data :  formdata,
                                processData : false,
                                contentType : false,
                                success: function (data) {
                                    if(null==data){ tip_common('error|'+ERROR_TIP); return false;}
                                    console.log('data',data);
                                    if(data.indexOf('success')!==-1){
                                        var html = '<li class="uploader__file_del weui-uploader__file weui-uploader__file_status" style="background-image:url(' + cmp_photo + ')"><input type="hidden" name="write_field[' + key + '][value][]" value="' + data.split('|')[1] + '"/><div class="weui-uploader__file-content"><i class="weui-icon-warn iconfont icon-shanchu"></i></div></li>';

                                        console.log('html',html);
                                        $('#loadingimg').remove();
                                        $('#uploaderAvatar_'+key).html(html);
                                    } else {
                                        $('#loadingimg').remove();
                                        tip_common(data);
                                    }
                                    // 删除事件
                                    $(document).on('click', '.uploader__file_del', function () {
                                        var that = $(this);
                                        $.confirm(DELCONFIRM, function () {
                                            that.remove();
                                        }, function () {
                                        });
                                        return false;
                                    });
                                }
                            });
                        });

                        // $('#uploaderAvatar_'+key).html('<li id="loadingimg" class="uploader__file_del weui-uploader__file weui-uploader__file_status"><div class="weui-uploader__file-content"><img src="{pigcms{$static_path}classifynew/images/loading.gif"/></div></li>');


                    } else {
                        var opt = btn.attr('data-option');
                        photoct.cropper(mtd, opt);
                    }
                });
            });
        }
        var first_cid="{pigcms{$cat_id}";
	</script>
    <script type="text/javascript" src="{pigcms{$static_path}employee/js/choose_category.js?t={pigcms{:time()}"></script>
</body>
</html>