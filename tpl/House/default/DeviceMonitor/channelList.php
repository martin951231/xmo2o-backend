<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="tabbable">
                    <ul id="myTab" class="nav nav-tabs">
                        <li>
                            <a href="{pigcms{:U('DeviceMonitor/list')}">硬盘录像机</a>
                        </li>
                        <li  class="active">
                            <a href="javascript:;" data-toggle="tab">摄像机列表</a>
                        </li>
                    </ul>
                </div>
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="2%">ID</th>
                                	<th>通道编号</th>
                                    <th>通道名称</th>
                                    <th>转换后视频流</th>
                                    <th>通道ip</th>
                                    <th>添加时间</th>
                                    <th>最近修改时间</th>
                                    <th class="button-column" width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        	<td><div class="tagDiv">{pigcms{$vo.camera_id}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.channelNum}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.channelName}</div></td>
                                            <td style="word-break:break-all">
                                                <a target="_blank" href="{pigcms{:U('lookVideo',array('camera_id'=>$vo['camera_id']))}">查看视频</a>
                                            </td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.channelIp}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.addTimeTxt|default='-'}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.updateTimeTxt|default='-'}</div></td>
                                            <td class="button-column">
                                            	<a style="width:6rem;height:26px;line-height:20px;" class="label label-sm label-info" title="修改" href="{pigcms{:U('videoEdit',array('camera_id'=>$vo['camera_id'],'logId'=> $_GET['logId']))}">修改</a>
                                            </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="12" >暂无数据。</td></tr>
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
