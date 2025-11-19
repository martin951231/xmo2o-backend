<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
				<a href="{pigcms{:U('bind_audit_list')}">家属列表</a>
            </li>
            <li class="active">导入亲属</li>
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

						<form id="myform" method="post" action="{pigcms{:U('User/user_import_excel_file',array('xtype'=>'user_relatives'))}" enctype="multipart/form-data" onsubmit="return chk_submit()">
						<table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
							<tr>
								<th width="80">示例表格</th>

                                <td><a onclick="location.href='{pigcms{:U('User/user_import_excel_demo',array('xtype'=>'user_relatives'))}'"  style="cursor:pointer;" class="button">点击下载</a></td>
							</tr>
                            
                            <tr>
                                <th width="80">Excel导入</th>
                                <td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传excel表格"/></td>
                            </tr>
							<tr>
								<th width="80">注意事项</th>
								<td>
                                    <div style="color: #ee1651;">1.EXCEL表格中所有单元格填写的内容<strong style="color: #000;">必须是文本格式（日期，数字等请填写成文本格式）</strong>，程序对文本内读取容兼性最好。</div>
                                   <div style="color: green;">2、请下载示例表格，然后用示例表格填写数据后导入</div>
                                </td>
							</tr>
						</table>
						<div class="clearfix form-actions">
							<div class="col-md-offset-3 col-md-9">
								<button class="btn btn-info formPost" type="submit">
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
    var loadExport = false;
    function chk_submit() {
        if (loadExport) {
            layer.msg('当前导入中，请稍后再试!',{icon: 2});
            return false
        }
        $('.formPost').html('导入中...');
        $('.formPost').removeClass('btn-info');
        setTimeout(function(){
            $('.formPost').html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
            loadExport = false;
            $('.formPost').addClass('btn-info');
        },3000);
        loadExport = true;
        return true;
    }


</script>
<include file="Public:footer"/>
