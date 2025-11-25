<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-tasks"></i>
                <a href="{pigcms{:U('nCoV_2019')}">新冠防护登记</a>
            </li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
			
			<div style="border:1px solid #c5d0dc;padding:10px;" class="form-group">
				<form method="get" action="{pigcms{:U('nCoV_2019_white')}">
					<input type="hidden" value="Library" name="c"/>
					<input type="hidden" value="nCoV_2019_white" name="a"/>
				
					<select style="margin-right:10px;height:36px;width:120px" class="col-sm-1" id="find_type" name="find_type">
						<option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>手机号</option>
					</select>
					<input type="text" style="margin-right:10px;font-size:14px;height:36px;width:160px;" id="find_value" name="find_value" class="col-sm-2" value="{pigcms{$_GET['find_value']}"/>
					
					<input type="submit" value="查找" id="find_submit" class="btn btn-success"/>&nbsp;&nbsp;&nbsp;&nbsp;
					<a onclick="location.href='{pigcms{:U('nCoV_2019_white')}'" class="btn btn-success">重置</a>
					<a onclick="location.href='{pigcms{:U('nCoV_2019_white_add')}'" class="btn btn-success fr">添加白名单人员</a>
				</form>
			</div>
			
			

            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="8%">手机号</th>
                                    <th width="8%">是否放行</th>
                                    <th width="8%">类别</th>
                                    <th width="15%">添加时间</th>
                                    <th>备注</th>
                                    <th class="button-column" width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                                            <td><div class="tagDiv"><if condition="$vo['status'] eq 1">允许外出<else/>禁止外出</if></div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.type|default='无'}</div></td>
                                            <td><div class="shopNameDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.remark}</div></td>
											<td class="button-column">
												<a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onclick="if(confirm('确认删除该条信息？')){location.href='{pigcms{:U('nCoV_2019_white_del', ['id'=>$vo['id']])}'}">删除</a>
											</td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="6" >暂无数据。</td></tr>
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
<script>
function showQrcode(){
	art.dialog.open("{pigcms{:U('nCoV_2019_qrcode')}",{
		init: function(){
			var iframe = this.iframe.contentWindow;
			window.top.art.dialog.data('iframe_handle',iframe);
		},
		id: 'handle',
		title:'登记二维码下载',
		padding: 0,
		width: 320,
		height: 300,
		lock: true,
		resize: false,
		background:'black',
		button: null,
		fixed: false,
		close: null,
		opacity:'0.4'
	});
	return false;
}
</script>
<include file="Public:footer"/>
