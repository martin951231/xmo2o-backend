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

    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div class="tabbable">
                        <div class="tabbable">
                            <ul id="myTab" class="nav nav-tabs">
                                <li class="active">
                                    <a href="javascript:void(0);">智能电瓶车</a>
                                </li>
                                <li>
                                    <a href="{pigcms{:U('Park/park_statistics')}&type=electriccar_statistics">电瓶车进出统计</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="tab-content">
                        <div style="padding:5px 15px 15px 0;">
                            今天开门次数：{pigcms{$today_count|intval=###} 次</br>
                            昨天开门次数：{pigcms{$yesterday_count|intval=###} 次</br>
                        </div>
                        <div style="padding:0 15px 5px 0;">
                            <span><button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('add')}'" <if condition="!in_array(497,$house_session['menus'])">disabled="disabled"</if>>添加设备</button></span>
                        </div>
                        <!-- 内容头部 -->
                        <div id="shopList" class="grid-view">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th width="5%">
                                        <a href="{pigcms{:U('index',array('sort'=>'id'))}" style="color:blue;">No. ↑</a>
                                    </th>
                                    <th width="10%">设备ID</th>
                                    <th width="10%">设备名</th>
                                    <th width="15%">添加时间</th>
                                    <th width="15%">最后通信时间</th>
                                    <th width="15%">备注</th>
                                    <th width="15%">
                                        <a href="{pigcms{:U('index',array('sort'=>'last_open_time'))}" style="color:blue;">最后开门时间 ↓</a>
                                    </th>
                                    <th class="button-column" width="20%">操作</th>
                                </tr>
                                </thead>
                                <tbody>
                                <if condition="$device_list">
                                    <volist name="device_list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                                <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.device_id}</div></td>
                                <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo.device_name}</div></td>
                                <td><div class="shopNameDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                                <td>
                                    <if condition="$vo['notify_time']">
                                        {pigcms{$vo.notify_time|date='Y年n月d日 H:i:s',###}
                                        <else/>
                                        -
                                    </if>
                                </td>
                                <td style="word-break:break-all"><div class="tagDiv">{pigcms{$vo['remark']}</div></td>
                                <td>
                                    <if condition="$vo['last_open_time']">
                                        {pigcms{$vo.last_open_time|date='Y年n月d日 H:i:s',###}
                                        <else/>
                                        -
                                    </if>
                                </td>
                                <td class="button-column">
                                    <if condition="in_array(498,$house_session['menus'])">
                                        <a style="width:6rem;height:26px;line-height:20px;" class="label label-sm label-info" title="修改设备" href="{pigcms{:U('edit',array('id'=>$vo['id']))}">修改设备</a>&nbsp;
                                    </if>
                                    <!--
                                    <if condition="in_array(499,$house_session['menus'])">
                                        <a style="width:8rem;height:26px;line-height:20px;" class="label label-sm label-info" title="查看开门记录" href="{pigcms{:U('door_open_list',array('device_id'=>$vo['device_id']))}">查看开门记录</a>&nbsp;
                                    </if>
                                    -->
                                    <if condition="in_array(500,$house_session['menus'])">
                                        <a style="width:6rem;height:26px;line-height:20px;" class="label label-sm label-info quick_open_door" title="一键开门" data-device_id="{pigcms{$vo['device_id']}">一键开门</a>&nbsp;
                                    </if>
                                    <if condition="in_array(503,$house_session['menus'])">
                                        <a style="width:6rem;height:26px;line-height:20px;background-color: red !important;" class="label label-sm label-info del_device" title="删除设备" data-device_id="{pigcms{$vo['id']}">删除设备</a>
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
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    // 删除设备
    var del_device = false;
    $('.del_device').click(function () {
        if (del_device) {
            layer.open({
                content: '删除命令已经下发，请稍后!',
                shadeClose: false,
                closeBtn: 0
                ,btn: ['确定']
            });
            return false;
        }
        del_device = true;
        var  device_id = $(this).data('device_id');
        layer.open({
            content: '是否确定删除，删除数据后无法恢复！',
            shadeClose: false,
            closeBtn: 1
            ,btn: ['确定','取消']
            ,yes:function(index){
                console.log('删除设备id',device_id);
                layer.closeAll();
                layer.load(0, {shade: [0.6,'#fff']});
                $.ajax({
                    url:"{pigcms{:U('del')}",
                    type:"post",
                    data:{"id":device_id},
                    dataType: "Json",
                    success:function(d){
                        console.log('删除结果返回',d);
                        del_device = false;
                        layer.closeAll();
                        if (d.errorCode>0) {
                            layer.open({
                                content: d.errorMsg,
                                shadeClose: false,
                                closeBtn: 0
                                ,btn: ['确定']
                            });
                        } else {
                            layer.open({
                                content: '设备已经成功删除!',
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
	
	// 一键开门
    var quick_open_door = false;
    $('.quick_open_door').click(function () {
        if (quick_open_door) {
			alert('该设备刚开门过。若需开门，请刷新页面后重试');
            return false;
        }
        quick_open_door = true;
        var  device_id = $(this).data('device_id');
		var _this = $(this);
		_this.removeClass('label-info');
        $.ajax({
            url:"{pigcms{:U('quick_open_door')}",
            type:"post",
            data:{"device_id":device_id},
            dataType: "Json",
            success:function(d){
                if (d.errorCode>0) {
					_this.addClass('label-info');
					quick_open_door = false;
                    alert(d.errorMsg);
                } else {
					_this.html('开门中...');
                    alert('开门命令已发送至设备，等待设备执行。');
                    window.location.reload();
                }
                console.log(d,d.errorCode);
            }
        });
    });
</script>

<include file="Public:footer"/>
