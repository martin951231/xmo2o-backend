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
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('door_list')}">门禁设置</a>
            </li>
        </ul>
    </div>
	<div style="margin:10px;padding:15px;">
        <a class="btn btn-info" href="{pigcms{:U('door_add')}">添加门禁</a>
	</div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                	<th width="4%">
										<a href="{pigcms{:U('door_list',array('sort'=>'door_id'))}" style="color:blue;">No. ↑</a>
									</th>
                                	<th width="6%">设备ID</th>
                                    <th width="12%">设备名</th>
                                   	<th width="5%">状态</th>
                                   	<th width="10%">增加时间</th>
                                   	<th width="10%">楼名</th>
                                   	<th width="8%">楼号</th>
                                   	<th width="8%">坐标</th>
                                    <th class="button-column" width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$door_list['list']">
                                    <volist name="door_list['list']" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        	<td><div class="tagDiv">{pigcms{$vo.door_id}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.door_device_id}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.door_name}</div></td>
                                            <!-- <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.door_psword}</div></td> -->
                                            <if condition="$vo.status eq 1">
                                            	<td><div class="tagDiv" style="color:green;">启用</div></td>
                                            <elseif condition="$vo.status eq 0" />
                                            	<td><div class="tagDiv" style="color:red;">未启用</div></td>
                                            </if>
                                            <td><div class="shopNameDiv">{pigcms{$vo.add_time|date='Y-m-d',###}</div></td>
                                            <if condition="$vo.type eq 2">
												<td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.floor_name}</div></td>
												<td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.floor_layer}</div></td>
											<else/>
												<td style="word-break:break-all"><div class="tagDiv">小区</div></td>
												<td style="word-break:break-all"><div class="tagDiv">大门</div></td>
											</if>
											<td>
												<if condition="$vo['lng']">已记录<else/>未记录</if>
											</td>
                                            <td class="button-column">
                                            	<a style="width:80px;height:26px;line-height:20px;" class="label label-sm label-info" title="修改设备" href="{pigcms{:U('door_edit',array('door_id'=>$vo['door_id']))}">修改设备</a>&nbsp;
                                                <if condition="!$vo['doorKeeperId']">
                                                <a style="width:80px;height:26px;line-height:20px;" class="label label-sm label-info" title="对接门禁" onclick="door_docking({pigcms{$vo.door_id})">同步设备</a>
                                                <else/>
                                                 <a style="width:80px;height:26px;line-height:20px;" class="label label-sm" href="javascript:void(0);">已同步</a>
                                                </if>
                                                <a style="width:80px;height:26px;line-height:20px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该条信息？')){location.href='{pigcms{:U('door_del',array('door_id'=>$vo['door_id']))}'}">删除</a>
                                            </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="11" >暂无数据。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$door_list.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    function door_docking(door_id) {
        $.ajax({
            url:'{pigcms{:U("door_docking")}',
            type:'post',
            data:{door_id:door_id},
            dataType:'json',
            success:function(data){  
                if(data.error){
                    layer.alert(data.msg)
                }else{
                    layer.alert(data.msg,function(){
                        window.location.reload();
                    });
                }
            }
        })
    }
</script>
</body>
</html>
