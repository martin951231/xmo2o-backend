<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Face_door/door_list')}">硬件设备</a>
            </li>
            <li class="active">人脸识别门禁</li>
        </ul>
    </div>
    <div class="tips">
        <p> 1. 使用二维码开门功能时，需先完善<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>、<?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元') ?>、楼层、<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间') ?>的编号信息;若缺少相关编号信息则无法使用二维码开门功能;二维码开门功能仅限于D6（狄耐克门禁设备）</p>
        <p> 2. 在添加海康或大华设备时，请先检查该设备是否已在海康或大华平台中存在（若已存在，请先删除对应设备）。完成这一操作后，您可以在智慧社区平台中添加设备信息，并同步到海康和大华系统。</p>
    </div>
	<div style="margin:10px;padding:15px;">
		今天开门次数：{pigcms{$today_count} 次</br>
		昨天开门次数：{pigcms{$yesterday_count} 次</br>
	</div>
    <div style="margin:10px;padding:15px;">
        <span><button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('door_add',array('device_type'=>$device_type))}'" <if condition="!in_array(497,$house_session['menus'])">disabled="disabled"</if>>添加人脸识别门禁</button></span>
        <span><button class="btn btn-success getDoorSearch" type="button" <if condition="!$config['hik_info_vision_i_community_app_key']">style="display:none;"</if>>获取门禁信息</button></span>

        <if condition="$have_lilin_third_dev eq 1">
            <span><button class="btn btn-success" type="button" style="margin-left: 20px;" onclick="lilinPfOnlyUserSyc()">同步人员信息到立林门禁平台</button></span>

        </if>

        <if condition="$lilin_third_dev_api2 eq 1">
            <span><button class="btn btn-success" type="button" style="margin-left: 20px;" onclick="getLilinPfDevData()">拉取更新立林门禁设备</button></span>
        </if>

        <span>
            <if condition="$deviceVisitorAuth && $deviceVisitorAuth['title']">
                <button class="btn btn-success"  type="button" title="{pigcms{$deviceVisitorAuth['title']}" onclick="window.top.artiframe('{pigcms{$deviceVisitorAuth.toPage}','{pigcms{$deviceVisitorAuth.title}   ',1010,595,true,false,false,false,'set',false);">
                {pigcms{$deviceVisitorAuth['title']}
                </button>
            </if>
        </span>
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
                                	<th width="2%">
										<a href="{pigcms{:U('door_list',array('sort'=>'door_id','device_type'=>$device_type))}" style="color:blue;">No. ↑</a>
									</th>
                                	<th width="10%">设备ID</th>
                                    <th width="10%">设备名</th>
                                   	<th width="5%">状态</th>
                                   	<th width="8%">对应位置</th>
                                    <th width="3%">标识</th>
                                    <th width="5%">设备类型</th>
                                    <if condition="hasThirdChnNum">
                                        <th width="3%">房号匹配</th>
                                    </if>
                                    <th width="10%">添加时间</th>
                                    <th width="8%">最后上报时间</th>
                                    <th width="8%">
                                        <a href="{pigcms{:U('door_list',array('sort'=>'last_open_time','device_type'=>$device_type))}" style="color:blue;">最后开门时间 ↓</a>
                                    </th>
                                    <th width="10%">
                                        相关详细信息
                                    </th>
                                    <th class="button-column" width="20%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$door_list">
                                    <volist name="door_list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        	<td><div class="tagDiv">{pigcms{$vo.device_id}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.device_sn}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.device_name}</div></td>

											<if condition="$vo.device_type eq 4">
<!--												<td><div class="tagDiv a3_device_status_btn">A4未知</div></td>-->
                                                <td><div class="tagDiv a3_device_status_btn" data-device_sn="{pigcms{$vo.device_sn}" style="color:green;cursor:pointer;text-decoration:underline;">查询</div></td>
											<elseif condition="$vo.device_type eq 7"/>
												<td><div class="tagDiv a7_device_status_btn">参照上报时间</div></td>
                                            <elseif condition="$vo.device_type eq 3 || $vo.device_type eq 4 || $vo.device_type eq 24 || $vo.device_type eq 88"/>
												<td><div class="tagDiv a3_device_status_btn" data-device_sn="{pigcms{$vo.device_sn}" style="color:green;cursor:pointer;text-decoration:underline;">查询</div></td>
											<else/>
												<if condition="$vo.device_status eq 1">
													<td><div class="tagDiv" style="color:green;">正常</div></td>
													<elseif condition="$vo.device_status eq 2" />
													<td><div class="tagDiv" style="color:red;">停止/待机</div></td>
												</if>
                                            </if>

                                            <td>
                                                <div class="tagDiv" style="color:green;">
                                                    <if condition="$vo['floor_about_info'] && $vo['floor_about_info'][0]">
                                                        <volist name="vo['floor_about_info']" id="item">
                                                            <div>{pigcms{$item['single_name']}-{pigcms{$item['floor_name']}</div>
                                                        </volist>
                                                    <elseif condition="$vo['floor_layer'] || $vo['floor_name']" />
                                                        {pigcms{$vo.floor_layer}<if condition="$vo.floor_name neq ''">-{pigcms{$vo.floor_name}</if>
                                                    <elseif condition="$vo['thirdAddress']" />
                                                        <div>{pigcms{$vo['thirdAddress']}</div>
                                                    </if>
                                                </div>
                                            </td>

                                            <if condition="$vo.device_type eq 88">
                                                <td><div class="tagDiv" style="color:green;">进&出</div></td>
                                                <elseif condition="$vo.device_direction eq 1" />
                                                <td><div class="tagDiv" style="color:green;">出</div></td>
                                                <elseif condition="$vo.device_direction eq 2" />
                                                <td><div class="tagDiv" style="color:green;">进&出</div></td>
                                                <elseif condition="$vo.device_direction eq 0" />
                                                <td><div class="tagDiv" style="color:green;">进</div></td>
                                            </if>
                                            <td><div class="tagDiv">{pigcms{$vo.device_type_txt}</div></td>
                                            <if condition="hasThirdChnNum">
                                                <td><div class="tagDiv">{pigcms{$vo.thirdChnNum}</div></td>
                                            </if>
                                            <td><div class="shopNameDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                                            <td>
                                                <if condition="$vo['notify_time']">
                                                    {pigcms{$vo.notify_time|date='Y年n月d日 H:i:s',###}
                                                    <else/>
                                                    -
                                                </if>
                                            </td>
                                            <td>
                                                <if condition="$vo['last_open_time']">
                                                    {pigcms{$vo.last_open_time|date='Y年n月d日 H:i:s',###}
                                                    <else/>
                                                    -
                                                </if>
                                            </td>
                                            <td>
                                                <if condition="$vo['login_time'] && $vo['device_type'] eq 1">
                                                    最新启动时间：{pigcms{$vo.login_time|date='Y年n月d日 H:i:s',###}<br/>
                                                </if>
                                                <if condition="$vo['login_time']">
                                                    同步设备最新时间：{pigcms{$vo.login_time|date='Y年n月d日 H:i:s',###}<br/>
                                                </if>
                                                <if condition="$vo['cpu_usage']">
                                                    CPU 使用率：{pigcms{$vo.cpu_usage}%<br/>
                                                </if>
                                                <if condition="$vo['memory_size']">
                                                    内存大小：{pigcms{$vo.memory_size}<br/>
                                                </if>
                                                <if condition="$vo['memory_usage']">
                                                    内存使用率：{pigcms{$vo.memory_usage}%<br/>
                                                </if>
                                                <if condition="$vo['disk_size']">
                                                    磁盘大小：{pigcms{$vo.disk_size}<br/>
                                                </if>
                                                <if condition="$vo['disk_size']">
                                                    磁盘使用率：{pigcms{$vo.disk_usage}%<br/>
                                                </if>
                                                <if condition="$vo['disk_free_size']">
                                                    磁盘可用空间：{pigcms{$vo.disk_free_size}<br/>
                                                </if>
                                                <if condition="$vo['sdk_version']">
                                                    算法版本：{pigcms{$vo.sdk_version}<br/>
                                                </if>
                                            </td>

                                            <td class="button-column">
                                               <php> if($vo['third_dev_source'] == 'lilin_pf'){</php>
                                                     <if condition="in_array(498,$house_session['menus'])">
                                                        <a style="width:6rem;height:26px;line-height:20px;" class="label label-sm label-info" title="修改设备" href="{pigcms{:U('door_edit',array('device_id'=>$vo['device_id']))}">修改设备</a>&nbsp;
                                                    </if>
                                                    <if condition="in_array(499,$house_session['menus'])">
                                                        <a style="width:8rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info" title="查看开门记录" href="{pigcms{:U('door_open_list',array('device_id'=>$vo['device_id']))}">查看开门记录</a>&nbsp;
                                                    </if>
                                               
                                                     <if condition="$vo['device_no'] AND $lilin_third_dev_api2 lt 1 AND in_array(501,$house_session['menus'])">
                                                         <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info" onclick="lilinPfUserSyc('{pigcms{$vo[\'device_id\']}','{pigcms{$vo[\'device_no']}')">一键同步</a>&nbsp;
                                                     </if>
                                                        <if condition="$lilin_third_dev_api2 lt 1">
                                                            <a style="width:6rem;height:26px;line-height:20px;margin: 5px;background-color: red !important;" class="label label-sm label-info del_device" title="删除设备" data-device_source="lilin_pf" data-device_id="{pigcms{$vo['device_id']}">删除设备</a>
                                                        </if>
                                                  <php> }else if($vo['thirdProtocol'] == 15){</php>

                                                    <if condition="in_array(499,$house_session['menus'])">
                                                        <a style="width:8rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info" title="查看开门记录" href="{pigcms{:U('door_open_list',array('device_id'=>$vo['device_id']))}">查看开门记录</a>&nbsp;
                                                    </if>
                                                
                                                    <a style="width:8rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info" title="获取设备状态" href="javascript:;" onclick="lookStart('{pigcms{$vo[\'device_id\']}','{pigcms{$vo[\'device_sn\']}','{pigcms{$vo[\'device_type\']}')">获取状态</a>&nbsp;

                                                     <if condition="in_array(501,$house_session['menus'])">
                                                         <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info" onclick="hikCommunityUserSyc('{pigcms{$vo[\'device_id\']}','{pigcms{$vo[\'device_sn\']}','{pigcms{$vo[\'device_type\']}')">一键同步</a>&nbsp;
                                                     </if>
                                                <php>}else{</php>
                                                    <if condition="in_array(498,$house_session['menus']) && $vo['thirdProtocol'] != 15">
                                                        <a style="width:6rem;height:26px;line-height:20px;" class="label label-sm label-info" title="修改设备" href="{pigcms{:U('door_edit',array('device_id'=>$vo['device_id']))}">修改设备</a>&nbsp;
                                                    </if>
                                                
                                                    <if condition="in_array(499,$house_session['menus'])">
                                                        <a style="width:8rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info" title="查看开门记录" href="{pigcms{:U('door_open_list',array('device_id'=>$vo['device_id']))}">查看开门记录</a>&nbsp;
                                                    </if>

                                                    <php>if (1==$vo['device_type'] || 7==$vo['device_type'] || 23==$vo['device_type'] || 24==$vo['device_type'] || 61==$vo['device_type'] || (21==$vo['device_type'] && $vo['ai_device_sn'])  || 30==$vo['device_type']) {</php>
                                                        <if condition="in_array(502,$house_session['menus']) && $vo['thirdProtocol'] != 15">
                                                            <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info device_restart" title="重启设备" data-device_id="{pigcms{$vo['device_id']}">重启设备</a>&nbsp;
                                                        </if>
                                                    <php>} else if (2==$vo['device_type'] || (in_array($vo['device_type'],[4,21]) && !$vo['ai_device_sn']  && $vo['thirdProtocol'] != 15)) {</php>
                                                        <if condition="in_array(500,$house_session['menus'])">
                                                            <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info sync_device_restart" title="同步设备" data-device_id="{pigcms{$vo['device_id']}">同步设备</a>&nbsp;
                                                        </if>
                                                    <php>} else if (3==$vo['device_type'] && (!$vo['a3_device_id'] || 0==$vo['is_a3_group'])) {</php>
                                                        <if condition="in_array(500,$house_session['menus'])">
                                                            <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info sync_device_a3_restart" title="同步A3设备" data-device_id="{pigcms{$vo['device_id']}">同步设备</a>&nbsp;
                                                        </if>
                                                    <php>} else if (3==$vo['device_type'] && $vo['a3_device_id'] && $vo['thirdProtocol'] != 15) {</php>
                                                        <if condition="in_array(502,$house_session['menus'])">
                                                            <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info device_a3_restart" title="重启A3设备" data-device_id="{pigcms{$vo['device_id']}">重启设备</a>&nbsp;
                                                        </if>
                                                    <php>} else if (5==$vo['device_type'] || 6==$vo['device_type']) {</php>
                                                        <php>if(!$vo['cloud_device_id']  && $vo['thirdProtocol'] != 15){</php>
                                                            <if condition="in_array(502,$house_session['menus'])">
                                                                <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info sync_device_restart" title="同步A5/A6设备" data-device_id="{pigcms{$vo['device_id']}">同步设备</a>&nbsp;
                                                            </if>
                                                        <php>}else{</php>
                                                            <a style="width:8rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info" title="获取设备状态" href="javascript:;" onclick="lookStart('{pigcms{$vo[\'device_id\']}','{pigcms{$vo[\'device_sn\']}','{pigcms{$vo[\'device_type\']}')">获取状态</a>&nbsp;
    
                                                            <if condition="in_array(501,$house_session['menus'])">
                                                                <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info" title="同步A5/A6人脸" onclick="keySyn('{pigcms{$vo[\'device_id\']}','{pigcms{$vo[\'device_sn\']}','{pigcms{$vo[\'device_type\']}')">一键同步</a>&nbsp;
                                                            </if>
                                                        <php>}</php>

                                                    <php>} else if (31==$vo['device_type']) {</php>
                                                        <if condition="in_array(502,$house_session['menus'])">
                                                            <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info" onclick="xiHanUserSyc('{pigcms{$vo[\'device_id\']}','{pigcms{$vo[\'device_sn\']}')" title="同步设备人脸数据" data-device_id="{pigcms{$vo['device_id']}">同步设备</a>&nbsp;
                                                        </if>
                                                    <php>}</php>

                                                    <php>if ($vo['ai_device_sn'] && $vo['thirdProtocol'] != 15) {</php>
                                                        <if condition="in_array(501,$house_session['menus'])">
                                                            <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info get_device_status" title="同步设备" data-device_id="{pigcms{$vo['device_id']}">同步设备</a>&nbsp;
                                                        </if>
                                                    <php>}</php>
                                                
                                                    <php>if (3==$vo['device_type'] && ($vo['a3_device_id'] && $vo['is_a3_group'])) {</php>
                                                        <if condition="in_array(501,$house_session['menus'])">
                                                            <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info sync_device_a3_restart" title="同步A3人脸" data-device_id="{pigcms{$vo['device_id']}">一键同步</a>&nbsp;
                                                        </if>
                                                    <php>}</php>

                                                    <php>if (29==$vo['device_type'] || 7==$vo['device_type'] || 4==$vo['device_type'] || 23==$vo['device_type'] || 24==$vo['device_type'] || 61==$vo['device_type'] || (21==$vo['device_type'] && $vo['ai_device_sn']) || 26==$vo['device_type']  || 30==$vo['device_type'] ) {</php>
    
                                                        <if condition="in_array(501,$house_session['menus'])">
                                                            <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info sync_device_a3_restart" title="同步人脸" data-device_id="{pigcms{$vo['device_id']}">一键同步</a>&nbsp;
                                                        </if>
                                                    <php>}</php>
                                                
                                                    <php>if (22==$vo['device_type']) {</php>
                                                        <if condition="in_array(501,$house_session['menus'])">
                                                            <a style="width:8rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info" title="获取设备状态" href="javascript:;" onclick="lookStartd('{pigcms{$vo[\'device_id\']}')">获取状态</a>&nbsp;
                                                        </if>
                                                    <php>} else if (23==$vo['device_type'] || 24==$vo['device_type']) {</php>
                                                        <if condition="in_array(501,$house_session['menus'])">
                                                            <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info get_device_status" title="获取设备状态" data-device_id="{pigcms{$vo['device_id']}">获取状态</a>&nbsp;
                                                        </if>
                                                    <php>}</php>

                                                    <php>if (61 == $vo['device_type']) {</php>
                                                        <if condition="in_array(501,$house_session['menus'])">
                                                            <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-success upgrade_device" title="升级设备" data-device_id="{pigcms{$vo['device_id']}">升级设备</a>&nbsp;
                                                        </if>
                                                    <php>}</php>
                                                
                                                    <php>if (88 == $vo['device_type'] && $vo['cloud_device_id']) {</php>
                                                        <if condition="in_array(501,$house_session['menus'])">
                                                            <a style="width:6rem;height:26px;line-height:20px;" class="label label-sm label-success upgrade_dahua_device" title="升级设备" data-device_id="{pigcms{$vo['device_id']}">一键同步</a>&nbsp;
                                                        </if>
                                                    <php>}</php>

                                                    <if condition="in_array(501,$house_session['menus']) && $vo['device_type']==88">
                                                        <a style="width:6rem;height:26px;line-height:20px;" class="label label-sm label-success bind_device_auth" title="开门权限" data-device_id="{pigcms{$vo['device_id']}">开门权限</a>&nbsp;
                                                    </if>
                                                    <if condition="in_array(503,$house_session['menus']) && $vo['device_type']==30">
                                                        <a style="width:6rem;height:26px;line-height:20px;margin: 5px;background-color: red !important;" class="label label-sm label-info recover" title="恢复出厂设置" data-device_id="{pigcms{$vo['device_id']}">恢复出厂</a>
                                                    </if>

                                                    <if condition="in_array(503,$house_session['menus']) && in_array($vo['device_type'],[5,6]) && in_array($vo['thirdProtocol'],[16,17])">
                                                        <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info device_restart" title="重启设备" data-device_id="{pigcms{$vo['device_id']}">重启设备</a>&nbsp;
                                                    </if>
                                                
                                                    <if condition="in_array(502,$house_session['menus']) && $vo['device_type'] == 31">
                                                        <a style="width:6rem;height:26px;line-height:20px;margin: 5px;" class="label label-sm label-info device_restart" title="重启设备" data-device_id="{pigcms{$vo['device_id']}">重启设备</a>&nbsp;
                                                    </if>

                                                    <if condition="in_array(503,$house_session['menus']) && $vo['device_type']!=26 && $vo['thirdProtocol'] != 15">
                                                        <a style="width:6rem;height:26px;line-height:20px;margin: 5px;background-color: red !important;" class="label label-sm label-info del_device" title="删除设备" data-device_id="{pigcms{$vo['device_id']}" data-device_source="" >删除设备</a>
                                                    </if>
                                                <php>}</php>
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
<style>
    .tips {
        margin-left: 10px;
        position: relative;
        padding: 10px 10px 10px 15px;
        color: rgba(0, 0, 0, 0.65);
        line-height: 1.5;
        border-radius: 2px;
        background-color: #e6f7ff;
        border: 1px solid #91d5ff;
    }
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    var device_restart = false;
    $('.device_restart').click(function () {
        if (device_restart) {
            return false;
        }
        device_restart = true;
        var  device_id = $(this).data('device_id');
        $.ajax({
            url:"{pigcms{:U('device_restart')}",
            type:"post",
            data:{"device_id":device_id},
            dataType: "Json",
            success:function(d){
                device_restart = false;
                if (d.errorCode>0) {
                    alert(d.errorMsg);
                } else {
                    alert('重启命令已发送至设备，等待设备执行。执行成功后，页面中的最新启动时间将会变更');
                    window.location.reload();
                }
            }
        });
    });

    //获取状态
    var get_device_status = false;
    $('.get_device_status').click(function () {
        if (get_device_status) {
            return false;
        }
        get_device_status = true;
        var  device_id = $(this).data('device_id');
        $.ajax({
            url:"{pigcms{:U('d3GetStatus')}",
            type:"post",
            data:{"device_id":device_id},
            dataType: "Json",
            success:function(d){
                get_device_status = false;
                if (d.errorCode>0) {
                    alert(d.errorMsg);
                } else {
                    alert('获取状态命令已发送至设备，等待设备执行。');
                    window.location.reload();
                }
            }
        });
    });

    // 开门权限
    $('.bind_device_auth').click(function () {
        var  device_id = $(this).data('device_id');
        console.log('device_id', device_id)
        var url = parent.location.href.split("#")[0]+'#/village/village.device/daHuaYuiRuiBindAuth?device_id='+device_id
        var index = layer.open({
            type: 2,
            area: ['92%', '750px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '开门权限',
            content: url
        });
        layer.style(index, {
            top: '50px',
        });
    })

    var recover = false;
    $('.recover').click(function () {
        if (recover) {
            layer.open({
                content: '恢复出厂设置已经下发，请稍后!',
                offset: {'top': '80px'},
                shadeClose: false,
                closeBtn: 0,
                btn: ['确定']
            });
            return false;
        }
        recover = true;
        var  device_id = $(this).data('device_id');
        layer.open({
            content: '是否确定恢复出厂数据，执行后设备上数据会轻控股，需要重新触发下发！',
            offset: {'top': '80px'},
            shadeClose: false,
            closeBtn: 1
            ,btn: ['确定','取消']
            ,yes:function(index){
                console.log('删除设备id',device_id);
                layer.closeAll();
                layer.load(0, {shade: [0.6,'#fff']});
                $.ajax({
                    url:"{pigcms{:U('recover')}",
                    type:"post",
                    data:{"device_id":device_id},
                    dataType: "Json",
                    success:function(d){
                        console.log('删除结果返回',d);
                        recover = false;
                        layer.closeAll();
                        if (d.errorCode>0) {
                            layer.open({
                                content: d.errorMsg,
                                offset: {'top': '80px'},
                                shadeClose: false,
                                closeBtn: 0
                                ,btn: ['确定']
                            });
                        } else {
                            layer.open({
                                content: '已下发指令请稍后!',
                                offset: {'top': '80px'},
                                shadeClose: false,
                                closeBtn: 0
                                ,btn: ['确定']
                            });
                            window.location.reload();
                        }
                    }
                });
            },
            btn2:function () {
                console.log('取消恢复出厂设置',device_id);
                recover = false;
                layer.closeAll();
            }
        })
    })
    
    
    // 删除设备
    var del_device = false;
    $('.del_device').click(function () {
        var device_source= $(this).data('device_source');
        if (del_device && device_source!='lilin_pf') {
            layer.open({
                content: '删除命令已经下发，请稍后!',
                offset: {'top': '80px'},
                shadeClose: false,
                closeBtn: 0,
                btn: ['确定']
            });
            return false;
        }
        del_device = true;
        var  device_id = $(this).data('device_id');
        var del_msg='是否确定删除，删除数据后无法恢复！';
        if(device_source=='lilin_pf'){
            del_msg='是否确定删除？只能删除本系统的数据，删除后如果在立林那边操作这台设备的编辑还会推送数据过来的，建议从立林那边删除。';
        }
        layer.open({
            content: del_msg,
            offset: {'top': '80px'},
            shadeClose: false,
            closeBtn: 1
            ,btn: ['确定','取消']
            ,yes:function(index){
                console.log('删除设备id',device_id);
                layer.closeAll();
                layer.load(0, {shade: [0.6,'#fff']});
                $.ajax({
                    url:"{pigcms{:U('del_device')}",
                    type:"post",
                    data:{"device_id":device_id},
                    dataType: "Json",
                    success:function(d){
                        console.log('删除结果返回',d);
                        del_device = false;
                        layer.closeAll();
                        if (d.errorCode>0) {
                            layer.open({
                                content: d.errorMsg,
                                offset: {'top': '80px'},
                                shadeClose: false,
                                closeBtn: 0
                                ,btn: ['确定']
                            });
                        } else {
                            layer.open({
                                content: '设备已经成功删除!',
                                offset: {'top': '80px'},
                                shadeClose: false,
                                closeBtn: 0
                                ,btn: ['确定']
                            });
                            window.location.reload();
                        }
                    }
                });
            },
            btn2:function () {
                console.log('取消删除',device_id);
                del_device = false;
                layer.closeAll();
            }
        })
    });
    //d2获取设备状态
    function lookStartd(device_id) {
        $.ajax({
            url:"{pigcms{:U('getD2DeviceStatus')}",
            type:"post",
            data:{"device_id":device_id},
            dataType: "Json",
            success:function(d){
                console.log(d);
                if (d.errorCode>0) {
                    alert(d.errorMsg);
                } else {
                    alert('获取成功');
                    window.location.reload();
                }
                device_restart = false;
            }
        });
    }
    //获取状态 a5
    function lookStart(device_id,device_sn,device_type) {
        $.ajax({
            url:"{pigcms{:U('getEquipmentStatus')}",
            type:"post",
            data:{"device_id":device_id,"device_sn":device_sn,'device_type':device_type},
            dataType: "Json",
            success:function(d){
                console.log(d);
                if (d.errorCode>0) {
                    alert(d.errorMsg);
                } else {
                    alert('获取成功');
                    window.location.reload();
                }
                device_restart = false;
            }
        });
    }
    //一键同步
    function keySyn(device_id,device_sn,device_type) {
        $.ajax({
            url:"{pigcms{:U('a5KeySyn')}",
            type:"post",
            data:{"device_id":device_id,"device_sn":device_sn,'device_type':device_type},
            dataType: "Json",
            success:function(d){
                console.log(d);
                if (d.errorCode>0) {
                    alert(d.errorMsg);
                } else {
                    alert('同步命令已发送至设备，等待设备执行。执行成功后，页面中的最新同步时间将会变更');
                    // window.location.reload();
                }
                device_restart = false;
            }
        });
    }
    //删除设备 a5
    function delDevice(device_id,device_sn,device_type) {
        $.ajax({
            url:"{pigcms{:U('delEquipment')}",
            type:"post",
            data:{"device_id":device_id,"device_sn":device_sn,'device_type':device_type},
            dataType: "Json",
            success:function(d){
                console.log(d);
                if (d.errorCode>0) {
                    alert(d.errorMsg);
                } else {
                    window.location.reload();
                }
                device_restart = false;
            }
        });
    }
    var sync_device_restart = false;
    $('.sync_device_restart').click(function () {
        if (sync_device_restart) {
            return false;
        }
        sync_device_restart = true;
        var  device_id = $(this).data('device_id');
        $.ajax({
            url:"{pigcms{:U('sync_device_restart')}",
            type:"post",
            data:{"device_id":device_id},
            dataType: "Json",
            success:function(d){
                if (d.errorCode>0) {
                    alert(d.errorMsg);
                } else {
                    alert('同步命令已发送至设备，等待设备执行。执行成功后，页面中的最新同步时间将会变更');
                    window.location.reload();
                }
                sync_device_restart = false;
                console.log(d,d.errorCode);
            }
        });
    });

    // A3 重启设备
    var restart = false;
    $('.device_a3_restart').click(function () {
        if (restart) {
            return false;
        }
        restart = true;
        var  device_id = $(this).data('device_id');

        $.ajax({
            url:"{pigcms{:U('device_restart')}",
            type:"post",
            data:{"device_id":device_id},
            dataType: "Json",
            success:function(d){
                if (d.errorCode>0) {
                    alert(d.errorMsg);
                } else {
                    alert('重启命令已发送至设备，等待设备执行。执行成功后，页面中的最新同步时间将会变更');
                    window.location.reload();
                }
                restart = false;
                console.log(d,d.errorCode);
            }
        });
    });
    // A3 同步添加
    var a3_restart = false;
    $('.sync_device_a3_restart').click(function () {
        if (a3_restart) {
			alert('该设备刚同步过。若需同步，请刷新页面后重试');
            return false;
        }
        a3_restart = true;
        var  device_id = $(this).data('device_id');
		var _this = $(this);
		_this.removeClass('label-info');
        $.ajax({
            url:"{pigcms{:U('device_all_sync_per')}",
            type:"post",
            data:{"device_id":device_id},
            dataType: "Json",
            success:function(d){
                if (d.errorCode>0) {
					_this.addClass('label-info');
					a3_restart = false;
                    alert(d.errorMsg);
                } else {
					_this.html('同步中...');
                    alert('同步命令已发送至设备，等待设备执行。执行成功后，页面中的最新同步时间将会变更');
                    // window.location.reload();
                }
                console.log(d,d.errorCode);
            }
        });
    });
	
	// A3查询设备状态
	$('.a3_device_status_btn').click(function () {
		var _this = $(this);
		var device_sn = $(this).data('device_sn');
		$.ajax({
            url:"/index.php?g=Index&c=Update&a=get_device_info&is_ajax=1&device_sn=" + device_sn,
            type:"get",
			dataType: "Json",
            success:function(d){
				_this.html(d.status_txt);
				// alert(d.msg_status_txt);
				if(d.status == 1){
					_this.attr('style', 'color:green;');
				}else{
					_this.attr('style', 'color:red;');
				}
            }
        });
	});
	$.each($('.a3_device_status_btn'), function(i, item){
		$(item).trigger('click');
	});

    var getDoor = false;
    $('.getDoorSearch').click(function () {
        if (getDoor) {
            alert('获取设备已经触发，如需再次触发，请刷新页面后重试');
            return false;
        }
        var _this = $(this);
        _this.html('获取中...');
        $.ajax({
            url:"{pigcms{:U('getDoorSearch')}",
            type:"post",
            data:{},
            dataType: "Json",
            success:function(d){
                if (d.errorCode>0) {
                    getDoor = false;
                    alert(d.errorMsg);
                } else {
                    alert('已经获取设备信息，请刷新查看');
                }
                _this.html('获取门禁信息');
                console.log(d,d.errorCode);
            }
        });
    })

	//todo a185升级
    $('.upgrade_device').click(function () {
        var that=$(this),device_id=that.data('device_id');
        if(device_id){
            layer.confirm('确定要升级设备吗?',{
                    btn : [ '确定', '取消' ], //按钮
                    offset: {'top': '80px'},
                    shade : false
                    //不显示遮罩
                },
                function(index) {
                   var loading = layer.msg('正在加载', {
                        icon: 16,
                        shade: [0.1, '#000'],
                        time: false  // 取消自动关闭
                    });
                    $.ajax({
                        url:"{pigcms{:U('a185_upgrade')}",
                        type:"post",
                        data:{"device_id":device_id},
                        dataType: "Json",
                        success:function(data){
                            if(data.status == 1){
                                layer.msg(data.info, {icon: 6});
                            } else {
                                layer.msg(data.info, {icon: 2});
                            }
                            layer.close(loading);
                            layer.close(index);
                        }
                    });

                }, function(index) {
                    layer.close(index);

                });
        }
    })

    //todo 大华一键同步
    $('.upgrade_dahua_device').click(function () {
        var that=$(this),device_id=that.data('device_id');
        if(device_id){
            layer.confirm('确定要一键同步设备吗?',{
                    btn : [ '确定', '取消' ], //按钮
                    offset: {'top': '80px'},
                    shade : false
                    //不显示遮罩
                },
                function(index) {
                    var loading = layer.msg('正在加载', {
                        icon: 16,
                        shade: [0.1, '#000'],
                        time: false  // 取消自动关闭
                    });
                    $.ajax({
                        url:"{pigcms{:U('dahua_upgrade')}",
                        type:"post",
                        data:{"device_id":device_id},
                        dataType: "Json",
                        success:function(data){
                            if(data.status == 1){
                                layer.msg(data.info, {icon: 6});
                            } else {
                                layer.msg(data.info, {icon: 2});
                            }
                            layer.close(loading);
                            layer.close(index);
                        }
                    });

                }, function(index) {
                    layer.close(index);

                });
        }
    });

 function hikCommunityUserSyc(device_id,device_sn,device_type) {
        $.ajax({
            url:"{pigcms{:U('hikCommunityUserSyc')}",
            type:"post",
            data:{"device_id":device_id,"device_sn":device_sn,'device_type':device_type},
            dataType: "Json",
            success:function(d){
                console.log(d);
                if (d.errorCode>0) {
                    alert(d.errorMsg);
                } else {
                    alert('同步命令已发送至设备，等待设备执行。执行成功后，页面中的最新同步时间将会变更');
                    // window.location.reload();
                }
                device_restart = false;
            }
        });
    }

     function lilinPfUserSyc(device_id,device_no) {
        $.ajax({
            url:"{pigcms{:U('lilinPfUserSyc')}",
            type:"post",
            data:{"device_id":device_id,"device_no":device_no},
            dataType: "Json",
            success:function(d){
                console.log(d);
                if (d.errorCode>0) {
                    alert(d.errorMsg);
                } else {
                    layer.alert('同步命令已发送至设备，等待设备执行。执行成功后，页面中的最新同步时间将会变更', {icon: 1,time: 20000});
                    // window.location.reload();
                }
                device_restart = false;
            }
        });
    }

    function lilinPfOnlyUserSyc(){
            $.ajax({
            url:"{pigcms{:U('lilinPfOnlyUserSyc')}",
            type:"post",
            data:{},
            dataType: "Json",
            success:function(d){
                console.log(d);
                if (d.errorCode>0) {
                    alert(d.errorMsg);
                } else {
                    
                    layer.alert('同步人员信息到立林门禁平台命令已经压入队列执行，请不要在短时间内重复操作！', {icon: 1,time: 20000});
                    // window.location.reload();
                }
                device_restart = false;
            }
        });
    }
    function getLilinPfDevData(){
            $.ajax({
            url:"{pigcms{:U('getLilinPfDevData')}",
            type:"post",
            data:{},
            dataType: "Json",
            success:function(d){
                if (d.errorCode>0) {
                    alert(d.errorMsg);
                } else {
                    layer.alert('拉取更新立林门禁设备命令已经压入队列执行，请不要在短时间内重复操作！', {icon: 1,time: 20000});
                    // window.location.reload();
                }
                device_restart = false;
            }
        });
    }

    function xiHanUserSyc(device_id,device_sn){
            if(device_id){
                layer.confirm('确定要一键同步小区人脸数据到设备吗?',{
                        btn : [ '确定', '取消' ], //按钮
                        offset: {'top': '80px'},
                        shade : false
                        //不显示遮罩
                    },
                    function(index) {
                        var loading = layer.msg('正在加载', {
                            icon: 16,
                            shade: [0.1, '#000'],
                            time: false  // 取消自动关闭
                        });
                        $.ajax({
                            url:"{pigcms{:U('xiHanUserSyc')}",
                            type:"post",
                            data:{"device_id":device_id,'device_sn':device_sn},
                            dataType: "Json",
                            success:function(data){
                                if(data.errorCode == 1){
                                    layer.msg(data.errorMsg, {icon: 2,time:5000});
                                } else {
                                    layer.msg(data.errorMsg, {icon: 6,time:5000});
                                }
                                layer.close(loading);
                                layer.close(index);
                            }
                        });

                    }, function(index) {
                        layer.close(index);

                    });
            }
    }
</script>

<include file="Public:footer"/>
