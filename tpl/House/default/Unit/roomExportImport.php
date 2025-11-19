<include file="Public:header"/>

<style>
    .fixline {
        padding-left: 20px;
    }
</style>

<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" >
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
	            <a href="{pigcms{:U('Unit/import_village')}">房间列表</a>
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
                导出后，如果【房屋面积】需要修改可在原位置进行变更后导入即可更新 注意：不支持变更面积为0，也不支持现有服务时间清空，可以修改为其他时间
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                    <form id="myform" method="post" action="__SELF__" enctype="multipart/form-data" onsubmit="return chk_submit()">
						<table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
							<tr>
								<th width="80">Excel导入</th>
								<td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传excel表格" validate="required:true"/></td>
							</tr>
                            <tr>
                                <th width="80">注意事项</th>
                                <td>
                                    <div >1.EXCEL表格中所有对应日期请右击设置单元格格式为年月日（例如：2020年12月31日）</div>
                                    <div >2.注意列表处导出的列表对应列不要进行删除，可以保留需要修改的行数据；</div>
                                    <div >3.导入的物业(开始/结束)服务时间范围遵循规则：</div>
                                    <div class="fixline" >  <font color="#00bfff">小区合同开始时间</font><=<font color="#189db7">楼栋合同开始时间</font><=<font color="#487296">房间合同开始时间</font><=<font color="#9e6f04">物业服务开始时间</font>；</div>
                                    <div class="fixline" >   <font color="#9e6f04">物业服务结束时间</font><=<font color="#487296">房间合同结束时间</font><=<font color="#189db7">楼栋合同结束时间</font><=<font color="#00bfff">小区合同结束时间</font>。</div>
                                    <div class="fixline" > <font color="red"> 导入房间物业服务时间前需先设置小区合同起始时间，</font>   <a href="/v20/public/platform/#/village/village.iframe/house_index_config" target="_blank"><font color="#00bfff">去设置小区合同时间</font></a>;<br/>例：设置小区的合同时间为2020-01-01至2050-12-30时，楼栋的合同时间为2021-01-01至2050-12-30（楼栋合同时间，只能设置在小区的合同时间范围内）；房屋合同时间为2022-01-01至2050-12-30（房屋合同时间，只能设置在楼栋的合同时间范围内）；业主的物业服务时间范围只能在房屋合同时间范围内进行设置。</div>

                                </td>
                            </tr>
						</table>
						<div class="clearfix form-actions">
							<div class="col-md-offset-3 col-md-9">
								<button class="btn btn-info formRoomPost" type="submit">
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
        $('.formRoomPost').html('导入中...');
        $('.formRoomPost').removeClass('btn-info');
        setTimeout(function(){
            $('.formRoomPost').html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
            loadExport = false;
            $('.formRoomPost').addClass('btn-info');
        },2000);
        loadExport = true;
    }
    var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Unit/roomExportImport')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>
