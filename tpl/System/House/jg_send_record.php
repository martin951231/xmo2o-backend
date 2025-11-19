<include file="Public:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css?t=01">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-rtl.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/global.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui-timepicker-addon.css">
<link rel="stylesheet" href="{pigcms{$static_public}layui/css/layui.css"  media="all">
<script type="text/javascript" src="{pigcms{$static_path}js/bootstrap.min.js"></script>
<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<div class="mainbox">

    <!-- 内容头部 -->
    <div class="page-content">

        <div class="tabbable">

        </div>
        <div class="page-content-area">
            <div class="row">
                <div class="form-group" style="padding:10px;">
                   
                </div>
                
                    <div class="table-list">
                        <table  width="100%">
                            <thead>
                                <tr>
                                    <th width="10%">房间地址</th>
                                    <th width="10%">业主名称</th>
                                    <th width="10%">业主手机号</th>
                                    <th width="20%">发送状态</th>
                                    <th width="10%">发送时间</th>
                                </tr>
                            </thead>
                            <tbody>
						<if condition="is_array($list) && !empty($list)">
							<volist name="list" id="vo">
                            <tr>
									<td>{pigcms{$vo.room_address}</td>
									<td>{pigcms{$vo.name}</td>
                                    <td>{pigcms{$vo.phone}</td>
                                    <td>{pigcms{$vo.send_status_str}</td>
                                    <td>{pigcms{$vo.send_time_str}</td>
                             </tr>
                            </volist>
                            <tr><td class="textcenter pagebar" colspan="9">{pigcms{$pagebar}</td></tr>
						<else/>
							<tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
						</if>
                            </tbody>
                        </table>
                    </div>
                
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" language="javascript">


</script>
<include file="Public:footer"/>
