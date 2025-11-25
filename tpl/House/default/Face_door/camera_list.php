<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Face_door/camera_list')}">硬件设备</a>
            </li>
            <li class="active">人脸识别摄像机</li>
        </ul>
    </div>
    <div style="margin:10px;padding:15px;">
    <span><button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('camera_add')}'" <if condition="!in_array(285,$house_session['menus'])">disabled="disabled"</if>>添加人脸识别摄像机</button></span>
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
                                    <th width="2%">ID</th>
                                	<th width="11%">设备ID</th>
                                    <th width="10%">设备名</th>
                                   	<th width="4%">状态</th>
                                   	<th width="10%">所在位置</th>
                                    <th width="10%">添加时间</th>
                                    <th width="8%">最近修改时间</th>
                                    <th class="button-column" width="10%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        	<td><div class="tagDiv">{pigcms{$vo.camera_id}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.camera_sn}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.camera_name}</div></td>

                                            <if condition="$vo.camera_status eq 0">
                                                <td><div class="tagDiv" style="color:green;">正常</div></td>
                                                <else/>
                                                <td><div class="tagDiv" style="color:red;">停止/待机</div></td>
                                            </if>

                                            <td>
                                                <div class="tagDiv" style="color:green;">
                                                    {pigcms{$vo.floor_layer}-{pigcms{$vo.floor_name}
                                                </div>
                                            </td>

                                            <td><div class="shopNameDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                                            <td>
                                                <if condition="$vo['last_time']">
                                                    {pigcms{$vo.last_time|date='Y年n月d日 H:i:s',###}
                                                    <else/>
                                                    -
                                                </if>
                                            </td>
                                            <td class="button-column">
                                            	<a style="width:6rem;height:26px;line-height:20px;" class="label label-sm label-info" title="修改设备" href="{pigcms{:U('camera_edit',array('camera_id'=>$vo['camera_id']))}">修改设备</a>&nbsp;
                                                <if condition="$vo['device_brand'] eq 'brand_haikang'">
                                                    <a style="width:8rem;height:26px;line-height:20px;" class="label label-sm label-info" title="查看监控通道" href="{pigcms{:U('device_channel_list',array('camera_id'=>$vo['camera_id']))}">查看监控通道</a>&nbsp;
                                                </if>
                                                <if condition="in_array(286,$house_session['menus'])">
                                                    <a style="width:6rem;height:26px;line-height:20px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认要删除吗？')){location.href='{pigcms{:U('camera_del',array('camera_id'=>$vo['camera_id']))}'}">删除</a>
                                                </if>
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
    $('.device_restart').click(function () {
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
                    alert('重启命令已发送至设备，等待设备执行。执行成功后，页面中的最后启动时间将会变更');
                    window.location.reload();
                }
                console.log(d,d.errorCode);
            }
        });
    });

    $('.sync_device_restart').click(function () {
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
                    alert('同步命令已发送至设备，等待设备执行。执行成功后，页面中的最后同步时间将会变更');
                    window.location.reload();
                }
                console.log(d,d.errorCode);
            }
        });
    });

</script>

<include file="Public:footer"/>
