<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('door_list')}">门禁设置</a>
            </li>
			<li class="active">查看门禁开门记录</li>
        </ul>
    </div>
	<div style="margin:10px;padding:15px;">当前门禁：<if condition="$nowDoor['floor_info']">{pigcms{$nowDoor.floor_info.floor_name}<else/>小区</if>（<if condition="$nowDoor['floor_info']">{pigcms{$nowDoor.floor_info.floor_layer}<else/>大门</if>）&nbsp;&nbsp;&nbsp;&nbsp;【设备名：{pigcms{$nowDoor['device_name']}】&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	</div>
<!--	<div class="alert alert-info" style="margin:10px;">-->
<!--		<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>搜索到蓝牙数，仅在搜索失败时可能为正数。可以结合“手机型号+手机版本+搜索蓝牙数”，来查看是否是安卓手机需要开启定位才能使用。<br/>-->
<!--	</div>-->
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                	<th width="12%">操作时间</th>
                                    <th width="12%">操作地点</th>
                                    <th width="12%">操作用户</th>
                                    <th width="12%">操作方式</th>
                                    <th width="12%">操作详细信息</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$door_list">
                                    <volist name="door_list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.eventTime}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.communityName} {pigcms{$vo.buildName}</div></td>
                                            <td style="word-break:break-all">
                                                <php>if ($vo['userName']) {</php>
                                                <div class="tagDiv">姓名：{pigcms{$vo['userName']}</div>
                                                <php>}</php>
                                                <php>if ($vo['eventUser']) {</php>
                                                <div class="tagDiv">电话：{pigcms{$vo['eventUser']}</div>
                                                <php>}</php>
                                            </td>
											<td>
												<if condition="$vo.eventType eq 1">
													<div class="tagDiv" style="color:green;">人脸开门</div>
												<elseif condition="$vo.eventType eq 2"/>
													<div class="tagDiv" style="color:green;">钥匙开门 </div>
												<elseif condition="$vo.eventType eq 3"/>
													<div class="tagDiv" style="color:green;">密码开门</div>
												<elseif condition="$vo.eventType eq 4"/>
													<div class="tagDiv" style="color:green;">远程开门</div>
                                                <elseif condition="$vo.eventType eq 5"/>
                                                    <div class="tagDiv" style="color:green;">扫码开门</div>
                                                <elseif condition="$vo.eventType eq 6"/>
                                                    <div class="tagDiv" style="color:green;">门卡开门</div>
                                                <elseif condition="$vo.eventType eq 7"/>
                                                    <div class="tagDiv" style="color:green;">临时密码开门</div>
												</if>
											</td>
                                            <td>
                                                <div class="tagDiv">
                                                    人脸照片：<img src="{pigcms{$vo['imgUrl']}" style="width:50px;height:50px;" class="view_msg"/>
                                                </div>
                                            </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="11" >暂无数据。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
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
<include file="Public:footer"/>
