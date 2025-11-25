<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" >
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
	            <a href="{pigcms{:U('door_list')}">蓝牙门禁</a>
            </li>
			<li class="active">查看门禁开门记录</li>
        </ul>
    </div>
	<div style="margin:10px;padding:15px;">当前门禁：<if condition="$nowDoor['floor_info']">{pigcms{$nowDoor.floor_info.floor_name}<else/>小区</if>（<if condition="$nowDoor['floor_info']">{pigcms{$nowDoor.floor_info.floor_layer}<else/>大门</if>）&nbsp;&nbsp;&nbsp;&nbsp;【设备名：{pigcms{$nowDoor['door_name']}】&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<if condition="$nextDoor">
		<a style="width:80px;height:26px;line-height:20px;" class="label label-sm label-info" title="查看下一门禁" href="{pigcms{:U('door_open_list',array('door_id'=>$nextDoor['door_id']))}">查看下一门禁</a>
	</if>
	</div>
	<div class="alert alert-info" style="margin:10px;">
		搜索到蓝牙数，仅在搜索失败时可能为正数。可以结合“手机型号+手机版本+搜索蓝牙数”，来查看是否是安卓手机需要开启定位才能使用。<br/>
		扫描失败的门禁是当用户在搜索不到蓝牙设备时，系统自动根据用户开门权限以及权限对应所有门的开门失败情况来自动归属到该门禁。用户不在门旁边搜索蓝牙的搜索不到也会认定进来。
	</div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">

            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form action="{pigcms{:U('Door/door_open_list')}" method="get" >
                    <input type="hidden" name="c" value="Door"/>
                    <input type="hidden" name="a" value="door_open_list"/>
                    <input type="hidden" name="door_id" value="{pigcms{$_GET['door_id']}"/>
                    <select name="find_type" id="find_type" class="col-sm-1" style="margin-left: 10px;margin-right:10px;height:42px;">
                        <option value="name" <if condition="$_GET['find_type'] eq 'name'">selected="selected"</if>>姓名</option>
                        <option value="phone" <if condition="$_GET['find_type'] eq 'phone'">selected="selected"</if>>手机号</option>
                    </select>
                    <input value="{pigcms{$_GET['find_value']}" class="col-sm-2" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                    状态：
                    <select name="status" class="" style=" margin-right:10px;height:42px;">
                        <option value="" <if condition="!isset($_GET['status']) || $_GET['status'] heq ''">selected="selected"</if>>全部</option>
                        <option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>成功</option>
                        <option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>失败</option>
                    </select>
                    时间筛选：
                    <input type="text" name="start_time" class="input-text" value="{pigcms{$_GET['start_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始"/>
                    &nbsp;—&nbsp;
                    <input type="text" name="end_time" class="input-text" value="{pigcms{$_GET['end_time']}"  style="height:42px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束"/>&nbsp;&nbsp;&nbsp;&nbsp;

                    <input class="btn btn-success" type="submit" id="find_submit" value="查找" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('Door/door_open_list',array('door_id'=>$_GET['door_id']))}'">重置</a>

                </form>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                	<th width="12%">操作时间</th>
                                    <th width="12%">操作用户</th>
                                    <th width="12%">操作状态</th>
                                    <th width="12%">手机型号及系统版本</th>
                                   	<th width="12%">搜索到蓝牙数</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$door_list">
                                    <volist name="door_list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.nickname} {pigcms{$vo.phone}</div></td>
											<td>
												<if condition="$vo.open_status eq 0">
													<div class="tagDiv" style="color:green;">成功</div>
												<elseif condition="$vo.open_status eq 1"/>
													<div class="tagDiv" style="color:red;">扫描失败</div>
												<elseif condition="$vo.open_status eq 2"/>
													<div class="tagDiv" style="color:red;">连接失败</div>
												<elseif condition="$vo.open_status eq 3"/>
													<div class="tagDiv" style="color:red;">重连失败</div>
												<elseif condition="$vo.open_status eq 4"/>
													<div class="tagDiv" style="color:red;">获取不到蓝牙关键词</div>
												</if>
											</td>
                                            <td style="word-break:break-all">
												<div class="tagDiv">
													<if condition="$vo['phone_plat'] eq 2">
														安卓
													<else/>
														苹果
													</if>
													（{pigcms{$vo.phone_brand}）&nbsp;{pigcms{$vo.phone_version}
												</div>
											</td>
											<td style="word-break:break-all">
											{pigcms{$vo.searched_bluetooth}
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
<include file="Public:footer"/>
