<!doctype html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>{pigcms{$config.employee_alias}入驻</title>

    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?210">
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/weui.css"/>
    <link rel="stylesheet" href="{pigcms{$static_path}employee/css/common/custom.css"/>
    <link href="{pigcms{$static_path}employee/css/index.css?t={pigcms{:time()}" rel="stylesheet"/>
    <style>
        .weui-dialog__btn, .weui-navbar__item.weui_bar__item_on, .color-strong, .chip-row .toutiao, .weui-cells_checkbox .weui-check:checked+.weui-icon-checked:before, .weui-vcode-btn, .weui-dialog__btn_primary, .weui-cells_radio .weui-check:checked+.weui-icon-checked:before, .weui-icon-checked:before, .weui-agree__checkbox:checked:before, .weui-icon-success-circle, .weui-icon-success-no-circle, .weui-search-bar__cancel-btn, .weui-tabbar__item.weui-bar__item_on .weui-tabbar__icon, .weui-tabbar__item.weui-bar__item_on .weui-tabbar__icon>i, .weui-tabbar__item.weui-bar__item_on .weui-tabbar__label,.main_color,.weui-tabbar__item.weui-bar__item--on .weui-tabbar__label,.picker-button,.weui-form-preview__btn_primary {color:#ff4d00!important}
        .weui_bar__item_on span:after,.weui-btn_primary, .weui-btn_primary:not(.weui-btn_disabled):active,.weui-btn_mini,.x_header, .main_bg,.position li.current, .position1 li.current,.post-tags .tag-on.weui-btn_default,.is-green, .weui-slider__track{background-color:#ff4d00!important}


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
        .choose_category {float:left;width: 30%;}
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
        .mapBox{
            height: 250px;
            position: relative;
        }
        .mapaddress{
            overflow: hidden;
            position: relative;
            overflow-y: scroll;
        }
        .mapBox #allmap {

            height: 250px;
        }
        .pad-bot-comm {

            padding-bottom: 0px;
        }
        .overhidden{    overflow: hidden;}
        .selectInput input {
            height: 40px;
            line-height: 40px;
        }
        .weui-select{
            -webkit-appearance: menulist;
        }
        .no_before{content:none !important;}
    </style>
    <script>
        var IN_WECHAT = '', AVATAR = "{pigcms{$user_session.avatar|default='./static/images/user_avatar.jpg'}", UID = '{pigcms{$user_session.uid|intval=###}', PLZINPUT = '请输入文字', BODA = '拨打电话', DELCONFIRM = '确定删除?', SUIBIANSHUO = '随便说点什么', HUIFU1 = '回复：', ERROR_TIP = '数据异常，请刷新页面再试。';
        var loading = false, page = 1, _APPNAME = '{pigcms{$config.site_url}/wap.php?c=Classifynew', _APPTITLE = '{pigcms{$config.classify_title}', scrollto =0, plzinput_mobile = '请输入手机号码';
        var cookiepre = 'classify_', cookiedomain = '', cookiepath = '/', IN_APP='', LISTINCR = '1', _URLEXT = '', GSITE='{pigcms{$config.site_url}/', MAXTAG = '5', MAXTAGTIP = "最多选5个标签", FASIXIN = '发私信',XL=1, LXFS ='查看联系方式',CKXFF = '查看联系方式需支付<strong class="amount">', QRZF ='</strong>{pigcms{$config['Currency_txt']}<br>确认支付？';
        var employee_category="{pigcms{:U('Employee/employee_category')}";
        var category="{pigcms{:U('Employee/index')}";
        var ajaxImgUpload = "{pigcms{:U('ajaxImgUpload')}";
        var ajax_url_root = "{pigcms{$config.site_url}/wap.php?c=Employee&a=";
        var examine_status_info = "{pigcms{$info.examine_status}";
        var static_path = "{pigcms{$static_path}";
    </script>
    <script src="{pigcms{$static_path}employee/js/common/jquery-2.1.4.js" type="text/javascript"></script>
    <script src="{pigcms{$static_path}employee/js/common/jquery-weui.js" type="text/javascript" charset="UTF-8"></script>
    <!--    <script src="{pigcms{$static_path}employee/js/app.js" type="text/javascript" charset="UTF-8"></script>-->
    <script type="text/javascript" src="{pigcms{$static_path}employee/js/common/common.js?t={pigcms{:time()}"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444"></script>
</head>
<body>
<div id = 'hidden_map' class="display:none"></div>
<link rel="stylesheet" href="{pigcms{$static_path}employee/css/cropper.css"/>
<style>.x_header{z-index:10}.weui-cells_form .weui-cell__ft{font-size:17px}</style>
<if condition="!$is_weixin_browser && !$is_app_browser">
    <div class="weui-pull-to-refresh__layer">
        <div class='weui-pull-to-refresh__arrow'></div>
        <div class='weui-pull-to-refresh__preloader'></div>
        <div class="down">下拉刷新</div>
        <div class="up">释放刷新</div>
        <div class="refresh">正在刷新</div>
    </div>
    <header class="x_header bgcolor_11 cl f15">
        <a class="z f14" href="javascript:window.history.go(-1);"><i class="iconfont icon-fanhuijiantou w15"></i>返回</a>
        <div class="navtitle">{pigcms{$config.employee_alias}入驻</div>
    </header>
    <!--		<div class="x_header_fix" ></div>-->
</if>
<div class="page__bd">
    <div style=" padding: 2px 15px 4px 15px; color: #4C93DA;">简单入驻，用户即可发现您！</div>
    <form action="{pigcms{:U('Employee/employee_info_add')}" method="post" id="form" enctype="multipart/form-data">
        <!--			<div class="weui-cells__title" style="margin-bottom:1em;">-->
        <!--				【提醒】请认真全面填写信息，以便大家查阅。-->
        <!--				<br/>【声明】填写的所有信息，平台只负责发布、展示，与平台本身无关，不承担任何责任。-->
        <!--				<if condition="$config['examine_employee']"><br/>【审核】信息填写后需要平台进行审核后，才会展示出来提供浏览。</if>-->
        <!--			</div>-->

        <input name="store_id" class="employee_store_id"  value=""  type="hidden" />

        <div class="weui-cells weui-cells_form">
            <div style=" color: #000; padding: 10px 0px 10px 20px;">请选择入驻类别:</div>
            <div class="weui-cell weui-cell_select weui-cell_select-after no_before">
                <div class="cell__bd" id="choose_category" cat_id="{pigcms{$_GET.cat_id}" cat_fid="{pigcms{$_GET.cat_fid}" cat_pfcid="{pigcms{$_GET.cat_pfcid}" style="display:inline;width: 100%;"></div>
            </div>
            <div class="write_box">
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

        <div class="fix-bottom" style="position: relative">
            <input type="submit" class="weui-btn weui-btn_primary" name="dosubmit" id="dosubmit" value="确认入驻">
        </div>
        <label class="weui-agree mt10" onclick="$(&quot;#agree__text&quot;).popup();">
            <input id="weuiAgree" type="checkbox" checked="checked" disabled="" readonly="" class="weui-agree__checkbox"/>
            <span class="weui-agree__text">
        					阅读并同意<a href="javascript:void(0);">《用户协议条款》</a>
        				</span>
        </label>
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
<section id="service-position" class="show_adress" style="display:none;">
    <div class="yxc-pay-main yxc-payment-bg pad-bot-comm show_adress" >
        <header class="yxc-brand">
            <a class="arrow-wrapper" data-role="cancel">
                <i class="bt-brand-back"></i>
            </a>
            <span>选择位置</span>
        </header>
        <div class="selectInput">
            <form target="frameFile" action="" onsubmit="conAdress_before()">
                <input type="search" placeholder="直接输入定位您的地址" id="se-input-wd" autocomplete="off"/>
                <iframe name="frameFile" style="display:none"></iframe>
            </form>
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
                <div style="height:10px;    background: #ddd;"></div>
                <dl></dl>
                <dd class="noMore" style=" background: #fff;">暂无更多数据!</dd>
                <!--<div class="no-deals hide">暂无信息</div>-->
            </section>
            <div id="pullUp">
                <span class="pullUpIcon"></span><span class="pullUpLabel">上拉加载更多</span>
            </div>
        </div>
    </div>
</div>
<div id="pageLoadTipShade" class="pageLoadTipBg">
    <div id="pageLoadTipBox" class="pageLoadTipBox">
        <div class="pageLoadTipLoader">
            <div style="background-image:url({pigcms{$config.shop_load_bg});"><!--img src="{pigcms{$static_path}shop/images/pageTipImg.png"/--></div>
        </div>
    </div>
</div>
<div class="masker" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);display:none;z-index:1000" onclick='$("#choose_sh").select("close")'></div>
<div class="cl footer_fix"></div>
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
<script type="text/javascript">
    window.shareData = {
        "moduleName":"Employee",
        "moduleID":"0",
        "imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
        "sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Employee/index')}",
        "tTitle": "{pigcms{$config.employee_alias}入驻申请",
        "tContent": "{pigcms{$config.site_name}"
    };
    var first_cid=0;
</script>
{pigcms{$shareScript}
<script type="text/javascript" src="{pigcms{$static_path}employee/js/choose_category.js?t={pigcms{:time()}"></script>
</body>
</html>