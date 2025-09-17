<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
				<a href="{pigcms{:U('index')}">业主列表</a>
            </li>
            <li class="active">导入业主</li>
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

						<form id="myform" method="post" action="{pigcms{:U('User/user_import')}" enctype="multipart/form-data" onsubmit="return chk_submit()">
						<table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
							<tr>
								<th width="80">示例表格</th>
<!--								<td><a target="_blank" href="{pigcms{$static_public}file/user_sample.xls" class="button" style="margin-left:0px;">点击下载</a></td>-->

                                <td><a onclick="location.href='{pigcms{:U('Cashier/user_download',$_GET)}'"  style="cursor:pointer;" class="button">点击下载</a></td>
							</tr>
                            
                            <tr>
                                <th width="80">Excel导入</th>
                                <td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传excel表格"/></td>
                            </tr>
							<tr>
								<th width="80">注意事项</th>
								<td>
                                    <div style="color: #ee1651;">1.EXCEL表格中对应日期请右击设置单元格格式为年月日（例如：2020年12月31日）</div>
                                    <div style="color: green;">2.对应<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>、<?php echo (D('House_village')->getHouseAlias('floor_name')?:'单元') ?>、楼层和<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间') ?>号对应已经存在的名称进行填写</div>
                                    <div style="color: green;">3.手机号一栏只允许填写一个手机号，如需多个手机号请在备注一栏中填写其他备用手机号</div>
                                    <div style="color: green;">4.身份证号、IC卡号、车辆、车库和车位都是非必填项</div>
                                    <div style="color: green;">5.身份证号、IC卡号、车辆和车位填写错误会返回错误但是其他业主数据会照常录入进去，所以一旦错误请单独修改</div>
                                    <div style="color: green;">6.车辆和车库+车位 支持填写多个（注意支持的是同一个车库下多个车位绑定） 请使用英文逗号“,”进行拼接填写，例如“京A12345,皖A12345”和“A车库”+“001,002”</div>
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
