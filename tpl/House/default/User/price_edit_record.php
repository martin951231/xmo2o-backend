<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/styles.css">
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/jquery.ba-bbq.min.js"></script>
    <if condition="$config['site_favicon']">
        <link rel="shortcut icon" href="{pigcms{$config.site_favicon}"/>
    </if>
    <title>{pigcms{$config.site_name} - 小区中心</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
    <link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css">
    <link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.css">
    <link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.min.css">
    <link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
    <link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
    <link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
    <link rel="stylesheet" href="{pigcms{$static_path}css/ace-rtl.min.css">
    <link rel="stylesheet" href="{pigcms{$static_path}css/global.css">
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
    <style type="text/css">
        .jqstooltip {
            position: absolute;
            left: 0px;
            top: 0px;
            visibility: hidden;
            background: rgb(0, 0, 0) transparent;
            background-color: rgba(0, 0, 0, 0.6);
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000,endColorstr=#99000000);
            -ms-filter:"progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000, endColorstr=#99000000)";
            color: white;
            font: 10px arial, san serif;
            text-align: left;
            white-space: nowrap;
            padding: 5px;
            border: 1px solid white;
            z-index: 10000;
        }

        .jqsfield {
            color: white;
            font: 10px arial, san serif;
            text-align: left;
        }

        .statusSwitch, .orderValidSwitch, .unitShowSwitch, .authTypeSwitch {
            display: none;
        }

        #shopList .shopNameInput, #shopList .tagInput, #shopList .orderPrefixInput
        {
            font-size: 12px;
            color: black;
            display: none;
            width: 100%;
        }
        .fl{ float:left;}
        .fr{ float:right;}
    </style>
    <script type="text/javascript">
        try{ace.settings.check('navbar' , 'fixed')}catch(e){}
        try{ace.settings.check('main-container' , 'fixed')}catch(e){}
        try{ace.settings.check('sidebar' , 'fixed')}catch(e){}
        try{ace.settings.check('breadcrumbs' , 'fixed')}catch(e){}
    </script>

</head>

<body class="no-skin">
<div class="main-content">
    <!-- 内容头部 -->
    <!--     <div class="breadcrumbs" id="breadcrumbs">
            <ul class="breadcrumb">
                <li>
                    <i class="ace-icon fa fa-user"></i>
                    <a href="{pigcms{:U('Unit/index')}">收费管理</a>
                </li>
                <li class="active">收银台</li>
            </ul>
        </div> -->
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
                            <div id="shopList" class="grid-view">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                    <tr>
                                        <th width="20%">操作类型</th>
                                        <th width="20%">操作时间</th>
                                        <th width="10%">操作人</th>
                                        <th width="10%">联系方式</th>
                                        <th width="20%">费用金额</th>
                                        <th width="10%">费用明细</th>
                                        <th width="10%">备注</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <if condition="$record_list">
                                        <volist name="record_list" id="vo">
                                            <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                    <td><div class="tagDiv">{pigcms{$vo.action} </div></td>
                                    <td><div class="tagDiv">{pigcms{$vo.add_time} </div></td>
                                    <td><div class="tagDiv">{pigcms{$vo.name} </div></td>
                                    <td><div class="tagDiv">{pigcms{$vo.phone} </div></td>
                                    <td><div class="tagDiv">{pigcms{$vo.money} </div></td>
                                    <td><div class="tagDiv">{pigcms{$vo.detail} </div></td>
                                    <td><div class="tagDiv">{pigcms{$vo.remark} </div></td>
                                    </tr>
                                    </volist>
                                    <tr><td class="textcenter pagebar" colspan="12">{pigcms{$order_list.pagebar}</td></tr>
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

