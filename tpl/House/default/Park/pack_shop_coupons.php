<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta charset="utf-8">
<!--    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/styles.css">-->
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.ba-bbq.min.js"></script>
    <if condition="$config['site_favicon']">
        <link rel="shortcut icon" href="{pigcms{$config.site_favicon}"/>
    </if>
    <title>{pigcms{$config.site_name} - 小区中心</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
    <link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css">
<!--    <link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.css">-->
<!--    <link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.min.css">-->
<!--    <link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">-->
    <link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<!--    <link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">-->
<!--    <link rel="stylesheet" href="{pigcms{$static_path}css/ace-rtl.min.css">-->
<!--    <link rel="stylesheet" href="{pigcms{$static_path}css/global.css">-->
    <link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui-timepicker-addon.css">
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.ba-bbq.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/ace-extra.min.js"></script>


    <script type="text/javascript" src="{pigcms{$static_path}js/bootstrap.min.js"></script>

    <!-- page specific plugin scripts -->
    <script type="text/javascript" src="{pigcms{$static_path}js/bootbox.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui.custom.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.ui.touch-punch.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.easypiechart.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.sparkline.min.js"></script>

    <!-- ace scripts -->
    <script type="text/javascript" src="{pigcms{$static_path}js/ace-elements.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/ace.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.yiigridview.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui-i18n.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui-timepicker-addon.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/echarts.min.js"></script>
    <link rel="stylesheet" href="{pigcms{$static_path}layer/css/layui.css"  media="all">
    <script src="{pigcms{$static_path}layer/layer.js"></script>
</head>

<body class="no-skin">
<div class="main-content">
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>

            <div class="row">
                <div class="col-xs-12">
                    <div class="tab-content">
                        <div class="tab-pane active" id="basicinfo">
                            <a class="btn btn-success" href="javascript:;" style="margin-bottom: 10px;border-radius:6px;padding: 5px;" onclick="add_shop_coupons()">添加优惠券</a>
                            <div id="shopList" class="grid-view">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                    <tr>
                                        <th width="5%">优惠券名称</th>
                                        <th width="5%">购买总次数</th>
                                        <th width="5%">剩余使用次数</th>
                                        <th width="5%">优惠券二维码</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <if condition="$list_coupons['list']">
                                        <volist name="list_coupons['list']" id="vo">
                                            <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                                <td>{pigcms{$vo.c_title}</td>
                                                <td>{pigcms{$vo.num}</td>
                                                <td>{pigcms{$vo.residue_num}</td>
                                                <td>
                                                    <if condition="$vo.status eq 1">
                                                        <a href="javascript:;" onclick="look_img('{pigcms{$vo.qrcode_url}','{pigcms{$vo.id}')">查看</a>
                                                        <else/>
                                                        <span style="color: red">动态码无法查看</span>
                                                    </if>
                                                </td>
                                            </tr>
                                        </volist>
                                        <tr><td class="textcenter pagebar" colspan="12">{pigcms{$list_coupons.pagebar}</td></tr>
                                    </if>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div style="font-size: 30px; padding: 15px;"></div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>
<script>
    var mid = '{pigcms{$mid}';
    function add_shop_coupons() {
        var url = "{pigcms{:U('Park/add_pack_shop_coupons')}&mid="+mid;
        layer.open({
            type: 2,
            title:'添加优惠券',
            content: url,
            area: ['750px', '585px'],
            maxmin: false
        });
    }
    //查看二维码
    function look_img(url,mid){
        //页面层-图片
        layer.open({
            type: 1,
            title: '查看',
            area: ['310px', '380px'],
            closeBtn: 0,
            // skin: 'layui-layer-nobg', //没有背景色
            shadeClose: true,
            content: '<img style="margin-left: 5px;width: 95%;" src='+ url +' /><br><a class="btn btn-success" href="'+url+'" download="'+mid+'.png" style="margin-left: 35%;margin-top: -10px;">点击下载</a>'//
        });
    }
</script>
