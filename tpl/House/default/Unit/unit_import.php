<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs">
        <ul class="breadcrumb">
	        <li>
		        <i class="ace-icon fa fa-user"></i>
		        <a href="{pigcms{:U('Unit/single_list')}"><?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>管理</a>
	        </li>
            <li class="active">导入<?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元') ?></li>
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
            <div style="padding: 10px;margin-bottom: 10px;">
                提示：建议直接前往<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间') ?>列表导入<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间') ?>，会自动生成<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>/<?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元') ?>/楼层。 <a style="color: #0a8ddf" href="/shequ.php?g=House&c=Unit&a=import_village_add">前往导入<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间') ?></a>
            </div>
            <div class="row">
                <ul id="myTab" class="nav nav-tabs">
                    <li>
                        <a href="#basicinfo" onclick="importSingle()" data-toggle="tab">导入<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?></a>
                    </li>
                    <li class="active">
                        <a href="#txtstore" onclick="importUnit()" data-toggle="tab">导入<?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元') ?></a>
                    </li>
                    <li>
                        <a href="#txtstore" onclick="importLayer()" data-toggle="tab">导入楼层</a>
                    </li>


                </ul>
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
						<table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
							<tr>
								<th width="120"><?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元') ?>示例表格</th>
<!--								<td><a target="_blank" href="{pigcms{$static_public}file/unit_import_sample.xls" class="button" style="margin-left:0px;">点击下载</a></td>-->
                                <td><a onclick="location.href='{pigcms{:U('Unit/unit_download',$_GET)}'"  style="cursor:pointer;" class="button">点击下载</a></td>
                            </tr>
                            <form id="myform" method="post" action="__SELF__" enctype="multipart/form-data" onsubmit="return chk_submit()">
                            <tr>
								<th width="120">Excel导入</th>
								<td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传excel表格" validate="required:true"/></td>
							</tr>
                            <tr>
                                <th width="80">注意事项</th>
                                <td>
                                    <div style="color: green;">1.单元编号必填，单元名称非必填；</div>
                                    <div style="color: green;">2.{pigcms{$tip}；</div>
                                    <div style="color: green;">3.没有填写的单元名称会自动赋值单元编号；</div>
                                </td>
                            </tr>
						</table>
						<div class="clearfix form-actions">
							<div class="col-md-offset-3 col-md-9">
								<button class="btn btn-info unitFormPost" type="submit">
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

    function importUnit(){
        window.location.href = "{pigcms{:U('unit_import')}";
    }
    function importSingle(){
        window.location.href = "{pigcms{:U('single_import')}";
    }
    function importLayer(){
        window.location.href = "{pigcms{:U('layer_import')}";
    }


    var loadExport = false;
    function chk_submit() {
        if (loadExport) {
            layer.msg('当前导入中，请稍后再试!',{icon: 2});
            return false
        }
        $('.unitFormPost').html('导入中...');
        $('.unitFormPost').removeClass('btn-info');
        setTimeout(function(){
            $('.unitFormPost').html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
            loadExport = false;
            $('.unitFormPost').addClass('btn-info');
        },2000);
        loadExport = true;
    }

</script>
<include file="Public:footer"/>
