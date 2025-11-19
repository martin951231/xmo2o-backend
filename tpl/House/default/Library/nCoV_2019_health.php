<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-tasks"></i>
                <a href="{pigcms{:U('nCoV_2019_health')}">新冠防护登记</a>
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
				<form method="get" action="{pigcms{:U('nCoV_2019_health')}">
					<input type="hidden" value="Library" name="c"/>
					<input type="hidden" value="nCoV_2019_health" name="a"/>
				
					<select style="margin-right:10px;height:36px;width:120px" class="col-sm-1" id="find_type" name="find_type">
						<option value="0" <if condition="$_GET['find_type'] eq 0">selected="selected"</if>>请选择</option>
						<option value="1" <if condition="$_GET['find_type'] eq 1">selected="selected"</if>>业主编号</option>
						<option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>手机号</option>
					</select>
					<input type="text" style="margin-right:10px;font-size:14px;height:36px;width:160px;" id="find_value" name="find_value" class="col-sm-2" value="{pigcms{$_GET['find_value']}"/>
					
					<input type="text" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="开始时间" name="begin_time" class="col-sm-2" style="margin-right:10px;font-size:14px;height:36px;width:120px;" value="{pigcms{$_GET['begin_time']}" />
					<input type="text" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="结束时间" name="end_time" class="col-sm-2" style="margin-right:10px;font-size:14px;height:36px;width:120px;" value="{pigcms{$_GET['end_time']}" />
					
					<input type="submit" value="查找" id="find_submit" class="btn btn-success"/>&nbsp;&nbsp;
					<a onclick="location.href='{pigcms{:U('nCoV_2019_health_export', $_GET)}'" class="btn btn-success fr">EXCEL导出</a>
					<a onclick="showQrcode()" class="btn btn-success fr" style="margin-right:10px;">登记二维码下载</a>
				</form>
			</div>
			
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="8%">姓名</th>
									<th width="10%">房屋</th>
									<th width="7%">手机号</th>
									<th width="8%">身份证号</th>
									<th width="10%">健康</th>
									<th width="8%">路过</th>
									<th width="8%">外出</th>
									<th width="8%">接触</th>
                                    <th width="5%">体温</th>
									<th width="15%">记录时间</th>
                                    <th width="25%">备注</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list">
                                    <volist name="list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        	<td><div class="tagDiv">{pigcms{$vo.name}</div></td>
											<td><div class="tagDiv">{pigcms{$vo.address}</div></td>
											<td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.id_card}</div></td>
                                            <td><div class="tagDiv">{pigcms{$nCov_2019_health_type[$vo['health_info']]}</div></td>
                                            <td><div class="tagDiv"><if condition="$vo['go_hubei']">是<else/>否</if></div></td>
                                            <td><div class="tagDiv"><if condition="$vo['out_province']">是<else/>否</if></div></td>
                                            <td><div class="tagDiv"><if condition="$vo['near_hubei_people']">是<else/>否</if></div></td>
                                            <td>
												<div class="tagDiv">
												<if condition='$vo["temperature"] eq -1 || ($vo["temperature"] gt 0 && $vo["temperature"] lt 37.3)'>
													<span class="green">< 37.3</span>
												<elseif condition='$vo["temperature"] eq -2 || ($vo["temperature"] gt 0 && $vo["temperature"] egt 37.3)'/>
													<span class="red">>= 37.3</span>
												<else/>
													<span class="red">{pigcms{$vo.temperature}</span>
												</if>
												</div>
											</td>
                                            <td><div class="shopNameDiv">{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.check_mark}</div></td>
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
