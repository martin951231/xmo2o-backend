<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" >
        <ul class="breadcrumb">
	        <li>
		        <i class="ace-icon fa fa-user"></i>
		        <a href="{pigcms{:U('Unit/single_list')}">楼栋管理</a>
	        </li>
            <li class="active">导入楼层</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
<!--            <button class="btn btn-success" onclick="importSingle()">导入楼栋</button>-->
<!--            &nbsp;&nbsp;<button class="btn btn-success" onclick="importUnit()">导入单元</button>-->
            <style>
                .ace-file-input a {display:none;}
            </style>
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div style="padding: 10px;margin-bottom: 10px;">
                提示：建议直接前往房间列表导入房间，会自动生成楼栋/单元/楼层。 <a style="color: #0a8ddf" href="/shequ.php?g=House&c=Unit&a=import_village_add">前往导入房间</a>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div class="tabbable">
                        <ul id="myTab" class="nav nav-tabs">
                            <li >
                                <a href="#basicinfo" onclick="importSingle()" data-toggle="tab">导入楼栋</a>
                            </li>
                            <li>
                                <a href="#txtstore" onclick="importUnit()" data-toggle="tab">导入单元</a>
                            </li>
                            <li class="active">
                                <a href="#txtstore" onclick="importLayer()" data-toggle="tab">导入楼层</a>
                            </li>


                        </ul>
                    </div>
                    <div id="shopList" class="grid-view">
                    
                    <form id="myform" method="post" action="__SELF__" enctype="multipart/form-data" onsubmit="return chk_submit()">
						<table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
							<tr>
								<th width="120">楼层示例表格</th>
								<td><a target="_blank" href="{pigcms{$static_public}file/layer_import_sample.xls?t=01" class="button" style="margin-left:0px;">点击下载</a></td>
							</tr>
							<tr>
								<th width="120">Excel导入</th>
								<td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传excel表格" validate="required:true"/></td>
							</tr>
                            <tr>
                                <th width="80">注意事项</th>
                                <td>
                                    <div style="color: green;">1.楼层编号必填，楼层名称非必填；</div>
                                    <div style="color: green;">2.{pigcms{$tip}；</div>
                                    <div style="color: green;">3.没有填写的楼层名称会自动赋值楼层编号；</div>
                                </td>
                            </tr>
						</table>
						<div class="clearfix form-actions">
							<div class="col-md-offset-3 col-md-9">
								<button class="btn btn-info layerFormPost" type="submit">
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
    function importAdd(){
        window.location.href = "{pigcms{:U('single_add')}";
    }
    function importUnit(){
        window.location.href = "{pigcms{:U('unit_import')}";
    }
    function importSingle(){
        window.location.href = "{pigcms{:U('single_import')}";
    }

    var loadExport = false;
    function chk_submit() {
        if (loadExport) {
            layer.msg('当前导入中，请稍后再试!',{icon: 2});
            return false
        }
        $('.layerFormPost').html('导入中...');
        $('.layerFormPost').removeClass('btn-info');
        setTimeout(function(){
            $('.layerFormPost').html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
            loadExport = false;
            $('.layerFormPost').addClass('btn-info');
        },2000);
        loadExport = true;
    }

</script>
<include file="Public:footer"/>
