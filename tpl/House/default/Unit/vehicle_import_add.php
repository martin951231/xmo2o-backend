<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('vehicle_management')}">车辆管理</a>
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
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                    
                    <form id="myform" method="post" action="__SELF__" enctype="multipart/form-data"  onsubmit="return chk_submit()">
						<table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
                            <if condition="$house_village_park_config.park_versions eq 2">
                                <tr>
                                    <th width="80">示例表格</th>
                                    <td><a onclick="location.href='{pigcms{:U('Unit/vehicle_download_add',$_GET)}'"  style="cursor:pointer;" class="button">点击下载</a></td>
                                </tr>
                            <else/>
                                <tr>
                                    <th width="80">示例表格</th>
                                    <td><a target="_blank" href="{pigcms{$static_public}file/village_import_vehicle_add.xls" class="button" style="margin-left:0px;">点击下载</a></td>
                                </tr>
                            </if>
							<tr>
								<th width="80">Excel导入</th>
								<td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传excel表格" validate="required:true"/></td>
							</tr>
                            <tr>
                                <th width="80">注意事项</th>
                                <td>
                                    <div style="color: #ee1651;">1.EXCEL表格中对应日期请右击设置单元格格式为年月日（例如：2020年12月31日）</div>
                                    <div style="color: green;">2.对应车库名称是当前小区已经存在的对应车库名称（选填）；</div>
                                    <div style="color: green;">3.对应车位号是当前小区对应车库下已经存在的对应车位号（选填，和车库名称同时存在对应关联）；</div>
                                    <div style="color: green;">4.停车卡号需要是当前不存在的停车卡号，不确定可以在车辆管理列表中进行搜索确认；</div>
                                    <div style="color: green;">5.车主姓名和车主手机号只是对应记录，不实际关联小区业主身份，关联请编辑对应车辆中【绑定业主】进行关联；</div>
                                    <div style="color: green;">6.开启了【智慧停车功能】，填写了停车到期时间会对应同步到设备上；</div>
                                    <div style="color: green;">7.开启了【智慧停车功能】，填写了停车卡类会自动同步到对应设备上，注意储值车类型车辆不会进行同步，用户实际储值时才会同步下发；</div>
                                </td>
                            </tr>
						</table>
						<div class="clearfix form-actions">
							<div class="col-md-offset-3 col-md-9">
								<button class="btn btn-info formParkPost" type="submit">
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
<script type="text/javascript">
    var loadExport = false;
    function chk_submit() {
        if (loadExport) {
            layer.msg('当前导入中，请稍后再试!',{icon: 2});
            return false
        }
        $('.formParkPost').html('导入中...');
        $('.formParkPost').removeClass('btn-info');
        setTimeout(function(){
            $('.formParkPost').html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
            loadExport = false;
            $('.formParkPost').addClass('btn-info');
        },2000);
        loadExport = true;
    }
</script>
<include file="Public:footer"/>
