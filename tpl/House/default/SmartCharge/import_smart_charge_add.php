<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('import_village')}">物业管理</a>
            </li>
            <li class="active">导入房间</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div style="background-color: #dff0d8;padding: 10px;margin-bottom: 10px;">
                导入保存后，若自动下载了Excel表格即为反馈导入过程中出现的问题，未提示失败原因的则导入成功
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                    
                    <form id="myform" method="post" action="__SELF__" enctype="multipart/form-data">
						<table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
							<tr>
								<th width="80">打开方式</th>
                                <td>
                                    <select class="col-sm-2" name="open_type" id="open_type">
                                        <option value="0">链接</option>
                                        <option value="1">小程序</option>
                                    </select>
                                    <span style="color: red;padding: 0 10px;line-height:30px;">请选择对应打开方式后下载对应示例表格</span>
                                </td>
                            
                            </tr>
							<tr>
								<th width="80">示例表格</th>
                                <td><a onclick="down_file_info();"  style="cursor:pointer;" class="button">点击下载</a></td>
                            </tr>
							<tr>
								<th width="80">Excel导入</th>
								<td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传excel表格" validate="required:true"/></td>
							</tr>
						</table>
						<div class="clearfix form-actions">
							<div class="col-md-offset-3 col-md-9">
								<button class="btn btn-info" type="submit">
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
<script>
    var open_type = 0;
    function down_file_info() {
        var url = "{pigcms{:U('smart_charge_import_sample')}";
        url = url + '&open_type='+open_type;
        console.log('url',url);
        location.href = url;
    }
    $('#open_type').change(function(){
		var vl = $(this).val();
		console.log('vl',vl);
        open_type = vl;
	});
	$('#open_type').change();
</script>
<include file="Public:footer"/>
