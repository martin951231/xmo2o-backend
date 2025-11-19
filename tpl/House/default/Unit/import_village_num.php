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
            <if condition="$limit_info['room_limit_num'] gt 0">
                <div style="background-color: #dff0d8;padding: 10px 10px 0;color: red;">
                    剩余导入房间数量为{pigcms{$limit_info['surplus_room_num']}
                </div>
            </if>
            <div style="background-color: #dff0d8;padding: 10px;margin-bottom: 10px;">
                导入保存后，若自动下载了Excel表格即为反馈导入过程中出现的问题，未提示失败原因的则导入成功
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                    
                    <form id="myform" method="post" action="__SELF__" enctype="multipart/form-data">
						<table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
							<tr>
								<th width="80">三表示例表格</th>
<!--								<td><a target="_blank" href="{pigcms{$static_public}file/village_import_sample.xls" class="button" style="margin-left:0px;">点击下载</a></td>-->
                                <td><a onclick="location.href='{pigcms{:U('Unit/village_import_num',$_GET)}'"  style="cursor:pointer;" class="button">点击下载</a></td>
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
<include file="Public:footer"/>
