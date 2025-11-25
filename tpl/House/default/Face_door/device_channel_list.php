<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('Face_door/device_channel_list')}">人脸识别摄像机通道</a>
            </li>
            <li class="active">人脸识别摄像机通道</li>
        </ul>
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
                                	<th width="11%">通道id</th>
                                    <th width="10%">通道名称</th>
                                   	<th width="4%">状态</th>
                                   	<th width="10%">通道号</th>
                                    <th width="10%">标准流预览地址</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        	<td><div class="tagDiv">{pigcms{$vo.channel_id}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.channelId}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.channelName}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.channelStatusTxt}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.channelNo}</div></td>
                                            <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.lookUrl}</div></td>

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
