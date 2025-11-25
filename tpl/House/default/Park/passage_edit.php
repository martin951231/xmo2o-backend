<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
	.page-content .btn-info { border: none;}
	.page-content .clearfix.form-actions {text-align: -webkit-center;}
</style>
<if condition="$village_park_config['park_sys_type'] eq 'D5'">
    <style>
        .col-sm-1{
            width: 20% !important;
        }
        .col-w-237{
            width: 237px !important;
        }
    </style>
</if>
<div class="page-content" style="background-color: white; width: 755px;">
	<div class="row" style="background-color: white;">
		<div class="col-xs-12">
			<form id="add_info" class="form-horizontal" method="post" action="__SELF__">
				<div class="tab-content">
					<input type="hidden" value="{pigcms{$passage['id']}" name="id">
                    <!--d5停车设备-->
                    <if condition="$village_park_config['park_sys_type'] eq 'D5'">
                        <div class="form-group">
                            <label class="col-sm-1"><label for="">通道品牌：</label></label>
                            <select class="col-sm-4 col-md-2" name="park_brand" id="park_brand">
                                <volist name="d5_park_brand" id="vo">
                                    <option value="{pigcms{$vo.key}"  data-id="{pigcms{$vo.id}" <if condition="$vo['key'] eq $passage['park_brand']">selected="selected"</if>  >{pigcms{$vo['title']}</option>
                                </volist>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-1"><label for="">通道类型：</label></label>
                            <select class="col-sm-4 col-md-2" name="park_type" id="park_type">
                                <volist name="d5_park_type" id="vo">
                                    <option value="{pigcms{$vo.key}" <if condition="$vo['key'] eq $passage['park_type']">selected="selected"</if> >{pigcms{$vo['value']}</option>
                                </volist>
                            </select>
                        </div>
                    </if>

					<div class="form-group">
						<label class="col-sm-1"><label for="">通道名称：</label></label>
						<input class="col-sm-2 col-w-237" size="20" name="passage_name" id="passage_name" placeholder="通道名称" type="text" value="{pigcms{$passage['passage_name']}"/>

					</div>
					<div class="form-group">
						<label class="col-sm-1"><label for="">通道号：</label></label>
						<input class="col-sm-2 col-w-237" size="20" name="channel_number" id="channel_number" placeholder="通道号" type="text" value="{pigcms{$passage['channel_number']}"/>
						<span>填写的通道号需与对应停车系统保持一致</span>
					</div>
                    <if condition="$village_park_config['park_versions'] eq 2 && $village_park_config['park_sys_type'] eq 'D3'">
                        <div class="form-group">
                            <label class="col-sm-1"><label for="">设备编号：</label></label>
                            <input class="col-sm-2" size="20" name="device_number" id="device_number" placeholder="设备编号" type="text" value="{pigcms{$passage['device_number']}"/>
                            <span>填写的设备编号需与对应停车设备保持一致</span>
                        </div>
                    </if>
                    <if condition="$village_park_config['park_sys_type'] neq 'D5'">
					<div class="form-group">
						<label class="col-sm-1">通道进出方向:</label>

						<label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition="$passage['passage_direction'] eq 1">checked="checked"</if> class="ace" value="1" name="passage_direction"><span style="z-index: 1" class="lbl">入口</span></label>
						<label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition="$passage['passage_direction'] eq 0">checked="checked"</if> class="ace" value="0" name="passage_direction"><span style="z-index: 1" class="lbl">出口</span></label>
						<label style="padding-left:0px;"><input type="radio" <if condition="$passage['passage_direction'] eq 2">checked="checked"</if> class="ace" value="2" name="passage_direction"><span style="z-index: 1" class="lbl">出入口</span></label>
					</div>
                    </if>
					<div class="form-group">
						<label class="col-sm-1"><label for="">通道坐标:</label></label>
						<input class="col-sm-1 col-w-237" size="10" name="long_lat" id="long_lat" type="text" readonly value="<if condition="$passage['long']">{pigcms{$passage.long},{pigcms{$passage.lat}</if>"/>
						&nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">点击选取经纬度</a>
					</div>

                    <!--d5停车设备-->
                    <if condition="$village_park_config['park_sys_type'] eq 'D5'">
                        <div class="form-group">
                            <label class="col-sm-1"><label for="">通道相关参数：</label></label>
                            <textarea rows="5" name="park_param" class="f-textarea col-w-237">{pigcms{$passage['park_param']}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-1"><label for="">备注：</label></label>
                            <textarea rows="5" name="park_remarks" class="f-textarea col-w-237">{pigcms{$passage['park_remarks']}</textarea>
                        </div>
                    </if>
                    <if condition="$village_park_config['park_sys_type'] neq 'D5'">
                    <div class="form-group">
						<label class="col-sm-1">通道状态:</label>

						<label style="padding-left:0px;padding-right:20px;"><input type="radio" checked="checked" <if condition="$passage.status eq 1">checked</if> class="ace" value="1" name="status"><span style="z-index: 1" class="lbl">开启</span></label>
						<label style="padding-left:0px;"><input type="radio" class="ace" value="0" name="status" <if condition="$passage.status eq 0">checked</if>><span style="z-index: 1" class="lbl">关闭</span></label>
					</div>
                    </if>
                    <if condition="$village_park_config['park_sys_type'] eq 'D3'">
                        <div class="form-group">
                            <label class="col-sm-1">设备类型:</label>
                            <label style="padding-left:0px;padding-right:20px;"><input type="radio" checked="checked" class="ace" value="1" name="device_type" <if condition="$passage.device_type eq 2">checked</if>><span style="z-index: 1" class="lbl">横屏</span></label>
                            <label style="padding-left:0px;"><input type="radio" class="ace" value="2" name="device_type" <if condition="$passage.device_type eq 2">checked</if>><span style="z-index: 1" class="lbl">竖屏</span></label>
                        </div>
                    </if>
				</div>
				<div class="space"></div>
				<div class="clearfix form-actions">
					<div class="col-md-offset-3 col-md-9">
						<button class="btn btn-info" type="submit">
							<i class="ace-icon fa fa-check bigger-110"></i>
							添加
						</button>
					</div>
				</div>
		</div>
		</form>
	</div>
</div>
<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=01"></script>
<div id="modal-table" class="modal fade" tabindex="-1" style="display:block;">
	<div class="modal-dialog" style="width:80%;">
		<div class="modal-content" style="width:100%;">
			<div class="modal-header no-padding" style="width:100%;">
				<div class="table-header">
					<button id="close_button" type="button" class="close" data-dismiss="modal" aria-hidden="true">
						<span class="white">&times;</span>
					</button>
					(用鼠标滚轮可以缩放地图)    拖动红色图标，经纬度框内将自动填充经纬度。
				</div>
			</div>
			<div class="modal-body no-padding" style="width:100%;">
				<form id="map-search" style="margin:10px;">
					<input id="map-keyword" type="textbox" style="width:500px;" placeholder="尽量填写城市、区域、街道名"/>
					<input type="submit" value="搜索"/>
				</form>
				<div style="width:100%;height:300px;min-height:300px;" id="cmmap"></div>
			</div>
			<div class="modal-footer no-margin-top">
				<button class="btn btn-sm btn-success pull-right" data-dismiss="modal">
					<i class="ace-icon fa fa-times"></i>
					关闭
				</button>
			</div>
		</div>
	</div>
</div>
<style>
	input.ke-input-text {
		background-color: #FFFFFF;
		background-color: #FFFFFF!important;
		font-family: "sans serif",tahoma,verdana,helvetica;
		font-size: 12px;
		line-height: 24px;
		height: 24px;
		padding: 2px 4px;
		border-color: #848484 #E0E0E0 #E0E0E0 #848484;
		border-style: solid;
		border-width: 1px;
		display: -moz-inline-stack;
		display: inline-block;
		vertical-align: middle;
		zoom: 1;
	}
	.form-group>label{font-size:12px;line-height:24px;}
	#upload_ico_pic_box,#upload_hello_pic_box{margin-top:20px;height:100px;}
	#upload_ico_pic_box .upload_pic_li,#upload_hello_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
	#upload_ico_pic_box img,#upload_hello_pic_box img{width:100px;height:70px;}
</style>

<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/date/WdatePicker.js"></script>
<script type="text/javascript">

    $('#park_brand').on('change', function () {
        var id = $(this).find("option:selected").attr('data-id'), str='<option value="" >请选择</option>',status='{pigcms{$passage[\'park_type\']}',is_selected='';
        $("#park_type").html('');
        if(id > 0){
            $.post("{pigcms{:U('Park/getHardwareBrandType')}",{brand_id:id},function(result){
                if(result.status == 1){
                    $.each(result.data, function(i, item){
                        if(item.key == status){
                            is_selected='selected="selected"'
                        }else{
                            is_selected='';
                        }
                        str+='<option value="'+item.key+'"  '+is_selected+'>'+item.title+'</option>';
                    });
                    $("#park_type").append(str);
                }else{
                    layer.open({content: '操作失败'});
                }
            })
        }
    });

    $('#park_brand').change();


	$('#add_info').submit(function(){
	    console.log($('#add_info').attr('action'));
		$.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
			console.log('result', result);

			if (result && result.status==1) {
				layer.open({
					content: '修改成功',
					shadeClose: false,
					closeBtn: 0
					,btn: ['确定']
					,yes:function(index){
						window.parent.close_all_page(result);
					}
				})


			} else if (result.info) {
				layer.open({
					content: result.info,
					shadeClose: false,
					closeBtn: 0
					,btn: ['知道了']
				})
			} else {
				layer.open({content: '添加失败'});
			}
		});
		return false;
	});


</script>
<include file="Publics:footer"/>
