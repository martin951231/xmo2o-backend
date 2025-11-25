<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
				<a href="{pigcms{:U('index')}">抄表列表</a>
            </li>
            <li class="active">导入抄表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                    
                    <form id="myform" method="post" action="{pigcms{:U('Cashier/meter_reading_import')}" enctype="multipart/form-data" onsubmit="return chk_submit()">

						<table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
							<tr>
								<th width="80">时间选择:</th>
								<td><input type="text" name="time_start" class="input-text" value="{pigcms{$property_endtime_start}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM'})" placeholder=""/></td>
							</tr>
                            <tr>
                                <th width="80">示例表格</th>
                                <td><a target="_blank" href="{pigcms{$static_public}file/meter_reading.xls" class="button" style="margin-left:0px;">点击下载</a></td>
                            </tr>
                            <tr>
                                <th width="80">Excel导入</th>
                                <td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传excel表格" validate="required:true"/></td>
                            </tr>
                            <tr>
                                <th width="80">注意事项</th>
                                <td>
                                    <div style="color: green;">1.导入反应可能不及时 点击后不要立即重复点击，重复点击强制拦截最少15秒；</div>
                                    <div style="color: green;">2.导入列【月份(可不填)】这个格式固定为年-月 即 2020-01 这样的格式不可以采用其他格式，excel可以对应设置该格式；</div>
                                    <div style="color: green;">3.如果导入的为同一个月份可以都不填写然后对应【时间选择】上选择对应月份，即所有录入均是所选时间；</div>
                                </td>
                            </tr>

						</table>
						<div class="clearfix form-actions">
							<div class="col-md-offset-3 col-md-9">
								<button class="btn btn-info meterFormPost" type="submit">
									<i class="ace-icon fa fa-check bigger-110"></i>
									保存
								</button>
							</div>
						</div>
					</form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
$('#user_add').click(function(){
	location.href="{pigcms{:U('user_add')}";
});
var loadExport = false;
function chk_submit() {
    if (loadExport) {
        layer.msg('当前导入中，请稍后再试!',{icon: 5});
        return false
    }
    $('.meterFormPost').html('导入中...');
    $('.meterFormPost').removeClass('btn-info');
    setTimeout(function(){
        $('.meterFormPost').html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
        loadExport = false;
        $('.meterFormPost').addClass('btn-info');
    },15000);
    loadExport = true;
}
</script>
<include file="Public:footer"/>
