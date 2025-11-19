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
                    
                    <form id="myform" method="post" action="__SELF__" enctype="multipart/form-data" onsubmit="return chk_submit()">
						<table cellpadding="0" cellspacing="0" class="table table-striped table-bordered table-hover" width="100%">
							<tr>
								<th width="80">房间示例表格</th>
<!--								<td><a target="_blank" href="{pigcms{$static_public}file/village_import_sample.xls" class="button" style="margin-left:0px;">点击下载</a></td>-->
                                <td><a onclick="location.href='{pigcms{:U('Unit/village_import_sample',$_GET)}'"  style="cursor:pointer;" class="button">点击下载</a></td>
                            </tr>
							<tr>
								<th width="80">Excel导入</th>
								<td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传excel表格" validate="required:true"/></td>
							</tr>
                            <tr>
                                <th width="80">注意事项</th>
                                <td>
                                    <div >1.楼栋名称和楼栋编号、单元名称和单元编号、楼层名称和楼层编号、房间号和房间编号这些都不能同时为空；</div>
                                    <div >2.如果对应楼栋当前不存在需要自动创建的对应编号必须填写，单元和楼层也是同理；</div>
                                    <div >3.楼栋编号为1-{pigcms{$max_num}的数字；</div>
                                    <div>4.单元编号为1-99的数字；</div>
                                    <div >5.楼层编号为1-99的数字；</div>
                                    <div >6.房间编号为1-9999的数字；</div>
                                    <div class="fixline" >(1)注意如果填写3位以上（大于99）的编号请注意保持同一个楼栋下唯一</div>
                                    <div class="fixline">(2)如果填写了3位数但是实际小于100的编号会自动保留2位拼接楼层编号，比如楼层编号33和房屋编号001会让自动将房屋编号变更为3301</div>
                                    <div>7.<font color="red">EXCEL表格中填写好对应房间的物业服务时间后再将时间列的单元格格式设置为年月日的日期格式（例如：2020年12月31日）;</font>;</div>
                                    <div >8.导入的物业(开始/结束)服务时间范围遵循规则：</div>
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
</script>
<include file="Public:footer"/>
