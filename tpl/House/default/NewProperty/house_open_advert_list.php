<include file="NewPublic:header"/>
<style>
    #nav.mainnav_title {
        border-bottom: 1px solid #e5e5e5;
        margin-bottom: 10px;
        color: white;
    }
    #nav.mainnav_title a.on {
        border-bottom: 2px solid #0099FC;
        text-decoration: none;
        color: #3283FA;
    }
    #nav.mainnav_title a {
        height: 48px;
        padding: 0 20px;
        min-width: 0;
        border: none;
        background: transparent;
        font-size: 14px;
        color: #666666;
        line-height: 46px;
        display: inline-block;
    }
    .page-content {
        padding: 10px 12px;
    }
</style>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="">物业管理</a>
            </li>
            <li class="active">物业中心</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div id="nav" class="mainnav_title">
            <ul>
                <a href="{pigcms{:U('NewProperty/house_advert_list',['iframe'=> $_GET['iframe']])}">轮播图管理</a>
                <a href="{pigcms{:U('NewProperty/house_middle_advert_list',['iframe'=> $_GET['iframe']])}">中间栏轮播广告</a>
                <a href="{pigcms{:U('NewProperty/house_open_advert_list',['iframe'=> $_GET['iframe']])}" class="on">一键开门广告管理</a>
                <a href="{pigcms{:U('NewProperty/app_fullscreen_adver',['iframe'=> $_GET['iframe']])}">App全屏广告</a>
            </ul>
        </div>
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover" style="position:relative;">
                            <thead>
                            <tr>
                                <th>编号</th>
                                <th>名称</th>
                                <th>链接地址</th>
                                <th>图片(以下为强制小图，点击图片查看大图)</th>
                                <th class="textcenter">最后操作时间</th>
                                <th>状态</th>
                                <th>点击量</th>
                                <th>浏览量</th>
                            </tr>
                            </thead>
                            <tbody class="class-checkbox">
                            <if condition="$adver_list">
                                <volist name="adver_list" id="vo">
                                    <tr>
                                        <td>{pigcms{$vo.id}</td>
                                        <td>{pigcms{$vo.name}</td>


                                        <td><a href="{pigcms{$vo.url}" target="_blank">访问链接</a></td>
                                        <td>
                                            <img src="{pigcms{$vo.pic}" style="width:300px;height:80px;" class="view_msg"/>
                                        </td>
                                        <td class="textcenter">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>
                                        <td>
                                            <if condition="$vo['status'] eq 1">
                                                <font color="green">正常</font>
                                                <elseif condition="$vo['status'] eq 0"/>
                                                <font color="red">关闭</font>
                                            </if>
                                        </td>
                                        <td>
                                            {pigcms{$vo['clickNum']+$vo['virtual_click_num']}
                                        </td>
                                        <td>
                                            {pigcms{$vo['lookNum']+$vo['virtual_look_num']}
                                        </td>
                                    </tr>
                                </volist>
                                <else/>
                                <tr class="odd"><td class="button-column" colspan="8" >暂无任何数据。</td></tr>
                            </if>
                            </tbody>

                        </table>
                        <if condition="$adver_list">
                            {pigcms{$pagebar}
                        </if>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    //预览大图
    $('.view_msg').click(function () {
        window.top.art.preview({
            padding: 0,
            title: '大图',
            url: $(this).attr('src'),
            width: 600,
            height: 400,
            click: true,
            lock: true
        });
    });
</script>

<include file="NewPublic:footer"/>
