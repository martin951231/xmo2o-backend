<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div >
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-tablet"></i>
				<a href="{pigcms{:U('Unit/single_list')}">楼栋管理</a>
			</li>
			<li class="active">编辑楼栋</li>
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
					<div class="tabbable">
						<ul id="myTab" class="nav nav-tabs">
							<li class="active">
								<a href="#basicinfo" data-toggle="tab">基本信息</a>
							</li>
							<li>
								<a href="#txtstore" data-toggle="tab">楼栋资料</a>
							</li>
                            <li>
                                <a href="#services_housekeeper" data-toggle="tab">楼栋管家</a>
                            </li>

						</ul>
					</div>
					<form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
						<input type="hidden" value="{pigcms{$single['id']}" name="id">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-1"><label for="single_name">楼栋名称</label></label>
									<input class="col-sm-2" size="20" name="single_name" id="single_name" type="text" value="{pigcms{$single.single_name}"/>
									&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="single_number">楼栋编号</label></label>
                                    <input class="col-sm-2" size="20" name="single_number" id="single_number" type="number" value="{pigcms{$single.single_number}"/>
                                    &nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项（仅限1-99不重复的数字）</span>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="long_lat">楼栋地址</label></label>
									<input class="col-sm-2" size="10" name="long_lat" id="long_lat" type="text" readonly value="<if condition="$single['long']">{pigcms{$single.long},{pigcms{$single.lat}</if>"/>
									&nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">点击选取经纬度</a>
								</div>

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="contract_time">合同时间</label></label>
                                    <input type="text" name="contract_time_start" class="input-text" value="{pigcms{$single.contract_time_start}"  style="height:30px;" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="合同开始时间"/>
                                    &nbsp;—&nbsp;
                                    <input type="text" name="contract_time_end" disabled class="input-text" value="{pigcms{$single.contract_time_end}"  style="height:30px;" onfocus="WdatePicker({isShowClear:true,readOnly:false,dateFmt:'yyyy-MM-dd'})" placeholder="合同结束时间"/>&nbsp;&nbsp;&nbsp;&nbsp;
                                    <span class="form_tips">设置楼栋时间范围，设置后物业只能收取合同时间内收费项目，未到合同开始时间或结束时间不能收费；未设置物业服务小区时间范围，则不影响。</span>
                                </div>
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="floor_name">楼栋管家名称</label></label>-->
<!--									<input class="col-sm-2" size="20" name="single_keeper_name" id="single_keeper_name" type="text" value="{pigcms{$single.single_keeper_name}"/>-->
<!--								</div>-->
<!---->
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="floor_name">联系方式</label></label>-->
<!--									<input class="col-sm-2" size="20" name="single_keeper_phone" id="single_keeper_phone" type="text" value="{pigcms{$single.single_keeper_phone}"/>-->
<!--								</div>-->
<!---->
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1">管家头像</label>-->
<!--									<input class="btn btn-primary" type="button" id="image3" value="选择图片" />-->
<!--									<input type="hidden" name="single_keeper_head" value="{pigcms{$single.single_keeper_head}" id="single_keeper_head" />-->
<!---->
<!--									<img src="{pigcms{:replace_file_domain($single['single_keeper_head'])}" id="single_keeper_head_view" style="padding: 15px; width: 80px;" <if condition="!$single['single_keeper_head']">display: none;</if>>-->
<!--								</div>-->
								<div class="form-group">
									<label class="col-sm-1"><label for="floor_layer">排序</label></label>
									<input class="col-sm-2" size="20" name="sort" id="sort" type="text" value="{pigcms{$single.sort}"/>
									&nbsp;&nbsp;&nbsp;&nbsp;<span class="">数字越大越靠前</span>
								</div>
								<div class="form-group">
										<label class="col-sm-1">状态</label>
										<label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition='$single["status"] eq 1'>checked="checked"</if> class="ace" value="1" name="status"><span style="z-index: 1" class="lbl">开启</span></label>
										<label style="padding-left:0px;"><input type="radio" class="ace" value="0" <if condition='$single["status"] eq 0'>checked="checked"</if> name="status"><span style="z-index: 1" class="lbl">关闭</span></label>
							   </div>
							</div>
							<div id="txtstore" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1"><label for="floor_layer">楼栋面积(m²)</label></label>
									<input class="col-sm-2" size="20" name="measure_area" id="measure_area" type="text" value="{pigcms{$single.measure_area}"/>

								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="floor_layer">所含单元数</label></label>
									<input class="col-sm-2" size="20" name="floor_num" id="floor_num" type="text" value="{pigcms{$single.floor_num}"/>

								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="floor_layer">所含房屋数</label></label>
									<input class="col-sm-2" size="20" name="vacancy_num" id="vacancy_num" type="text" value="{pigcms{$single.vacancy_num}"/>

								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="floor_layer">地面建筑层数</label></label>
									<input class="col-sm-2" size="20" name="upper_layer_num" id="upper_layer_num" type="text" value="{pigcms{$single.upper_layer_num}"/>

								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="floor_layer">地下建筑层数</label></label>
									<input class="col-sm-2" size="20" name="lower_layer_num" id="lower_layer_num" type="text" value="{pigcms{$single.lower_layer_num}"/>

								</div>
						    </div>



                            <div id="services_housekeeper" class="tab-pane">

                                <input type="hidden" name="housekeeper_id" value="{pigcms{$detail.housekeeper_id}" id="housekeeper_id" />
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="work_arr_choose">绑定员工：</label></label>
                                    <select class="col-sm-2"  name="work_id" id="work_arr_choose" >
                                        <option style="height: 35px;" selected value="">请选择对应要绑定的员工</option>
                                        <volist name="worker_list" id="worker">
                                            <option style="height: 35px;<if condition="in_array($worker['wid'],$work_arr)">display: none;</if>"  value="{pigcms{$worker['wid']}" class="choose_{pigcms{$worker['wid']}" data-name="{pigcms{$worker['name']}">{pigcms{$worker['name']}</option>
                                        </volist>
                                    </select>
                                    <div style="color: #dd5a43; margin-top: 5px;">&nbsp;&nbsp;&nbsp;&nbsp;多选，不限制数量，绑定员工后，业主加好友时随机从其中选择</div>
                                </div>
                                <div class="form-group multiple_worker_box" <if condition="empty($work_arr_info)">style="display: none;"</if>>
                                    <label class="col-sm-1"><label for="work_arr">&nbsp;</label></label>
                                    <div class="workers_box col-sm-3">
                                        <if condition="!empty($work_arr_info)">
                                            <volist name="work_arr_info" id="item">
                                                <div class="worker_info" style="margin-right:5px;margin-bottom: 5px;background-color: #BEC1C3;color:white;padding:0 5px 3px;border-radius:5px; display:inline-block !important; display:inline; ">{pigcms{$item['name']}
                                                    <span class="del" data-wid="{pigcms{$item['wid']}" style="color: red;font-size:18px;cursor:pointer">×</span>
                                                    <input type="hidden" name="work_arr[]" value="{pigcms{$item['wid']}">
                                                </div>
                                            </volist>
                                        </if>
                                    </div>
                                 </div>
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="work_arr_choose">绑定楼栋：</label></label>
                                        <label class="col-sm-1" style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition='$detail["is_kefu"] egt 1'>checked="checked"</if> class="ace" value="1" name="is_kefu"><span style="z-index: 1" class="lbl">是</span></label>
                                        <label class="col-sm-1" style="padding-left:0px;"><input type="radio" class="ace" value="0" <if condition='$detail["is_kefu"] eq 0'>checked="checked"</if> name="is_kefu"><span style="z-index: 1" class="lbl">否</span></label>
                                        <div style="color: #dd5a43; margin-top: 5px;">&nbsp;&nbsp;&nbsp;&nbsp;选择“是”选项，添加客服时只能选择楼栋下的业主；选择“否”选项，添加客服时能选择楼栋所有的业主</div>
                                    </div>
                                <div class="form-group enterprise_wx_hide">
                                    <label class="col-sm-1"><label for="floor_name">欢迎语：</label></label>
                                    <textarea class="col-sm-2" rows="3" name="welcome_tip" id="welcome_tip">{pigcms{$detail.welcome_tip}</textarea>
                                    <div style="color: #dd5a43; margin-top: 5px;">&nbsp;&nbsp;&nbsp;&nbsp;变量填写规则（可对应复制到填写内容）： {姓名} {手机号}</div>
                                </div>

                                <div class="form-group enterprise_wx_hide">
                                    <label class="col-sm-2"><label for="qy_qrcode">企业微信服务群二维码：</label></label>
                                    <input class="btn btn-primary col-sm-1" type="button" id="qy_qrcode_image" value="选择图片" />
                                    <input type="hidden" name="qy_qrcode" value="{pigcms{$detail.qy_qrcode}" id="qy_qrcode" />
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <span>
                                         <a href="{pigcms{$qyhelp_url}"  target="_blank" class="buttonBox" style="color: #1686ef;line-height: 34px;">
                                        点击可查看使用帮助
                                         </a>
                                    </span>
                                </div>
                                <div class="form-group qy_qrcode_view_box enterprise_wx_hide" style="<if condition="!$detail['qy_qrcode']">display: none;</if>"">
                                    <label class="col-sm-1"><label for="qy_qrcode_view"></label></label>
                                    <img src="{pigcms{:replace_file_domain($detail['qy_qrcode'])}" id="qy_qrcode_view" style="width: 200px;">
                                </div>
                                <div class="form-group enterprise_wx_hide">
                                    <label class="col-sm-1"><label for="floor_layer">装修：</label></label>
                                    <select class="col-sm-2"  name="template_type" id="template_type" >
                                        <option style="display: none;height: 35px;" value="">请选择装修模板</option>
                                        <option style="height: 35px;" value="0" <if condition="$detail['template_type'] eq 0">selected="selected"</if>">无需装修</option>
                                        <option style="height: 35px;" value="1" <if condition="$detail['template_type'] eq 1">selected="selected"</if>">系统默认模板</option>
                                        <option style="height: 35px;" value="2" <if condition="$detail['template_type'] eq 2">selected="selected"</if>">上传模板</option>
                                    </select>
                                    <div class="col-sm-9" style="color: #dd5a43;">
                                        <div>1）无需装修：将直接使用二维码自带的模板样式</div>
                                        <div>2）系统默认模板：将直接使用我们提供的模板样式，可选择查看样式</div>
                                        <div>3）上传模板：需要自行设计好模板后上传，尺寸：750*1334；注意：模板左下角需距离页面边缘间距20像素预留100*100像素的空白位置，用于放置群二维码</div>
                                    </div>
                                </div>
                                <div class="template_type_2_box enterprise_wx_hide" style="<if condition="$detail['template_type'] neq 2">display: none;</if>"">
                                    <div class="form-group">
                                        <label class="col-sm-2"><label for="template_url">上传模板：</label></label>
                                        <input class="btn btn-primary col-sm-1" type="button" id="template_url_image" value="选择图片" />
                                        <input type="hidden" name="template_url" value="{pigcms{$detail.template_url}" id="template_url" />
                                        <div class="col-sm-9" style="color: #dd5a43; margin-top: 5px;">尺寸：750*1334；注意：模板左下角需距离页面边缘间距20像素预留100*100像素的空白位置，用于放置群二维码</div>
                                    </div>
                                    <div class="form-group template_url_view_box" style="<if condition="!$detail['template_url']">display: none;</if>"">
                                        <label class="col-sm-1"><label for="template_url_view"></label></label>
                                        <img src="{pigcms{:replace_file_domain($detail['template_url'])}" id="template_url_view" style="width: 200px;">
                                    </div>
                                </div>
                                <div class="form-group effect_img_box enterprise_wx_hide" style="<if condition="!$detail['effect_img']">display: none;</if>"">
                                    <label class="col-sm-1"><label for="effect_img_view">效果预览：</label></label>
                                    <img src="{pigcms{:replace_file_domain($detail['effect_img'])}" id="effect_img_view" style="width: 200px;">
                                    <input type="hidden" name="effect_img" value="{pigcms{$detail.effect_img}" id="effect_img" />
                                </div>
                            </div>
						    <div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">

									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保存
									</button>

								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
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
					<div style="width:100%;height:600px;min-height:600px;" id="cmmap"></div>
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
	<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=01"></script>
	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    var enterprise_wx_corpid = "{pigcms{$enterprise_wx_corpid}";
    if (!enterprise_wx_corpid) {
        $('.enterprise_wx_hide').hide();
    }

	KindEditor.ready(function(K) {
		var editor = K.editor({
			allowFileManager : true
		});
		K('#image3').click(function() {
			editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
			editor.loadPlugin('image', function() {
				editor.plugin.imageDialog({
					showRemote : false,
					imageUrl : K('#url3').val(),
					clickFn : function(url, title, width, height, border, align) {
						var img = K('#single_keeper_head_view');
						img.attr("src",url);
						img.show();
						K("#single_keeper_head").val(url);
						editor.hideDialog();
					}
				});
			});
		});
        K('#qy_qrcode_image').click(function() {
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function(datas) {
                editor.plugin.imageDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#qy_qrcode_view');
                        img.attr("src",title);
                        $('.qy_qrcode_view_box').show();
                        K("#qy_qrcode").val(url);
                        editor.hideDialog();
                        var template_type = $('#template_type').val();
                        var qy_qrcode = url;
                        var template_url = $('#template_url').val();
                        if (1==template_type && qy_qrcode) {
                            services_look_img();
                        } else if (2==template_type && qy_qrcode && template_url){
                            services_look_img();
                        }
                    }
                });
            });
        });
        K('#template_url_image').click(function() {
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function(datas) {
                editor.plugin.imageDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#template_url_view');
                        img.attr("src",title);
                        $('.template_url_view_box').show();
                        K("#template_url").val(url);
                        editor.hideDialog();
                        var template_type = $('#template_type').val();
                        var qy_qrcode = $('#qy_qrcode').val();
                        var template_url = url;
                        if (1==template_type && qy_qrcode) {
                            services_look_img();
                        } else if (2==template_type && qy_qrcode && template_url){
                            services_look_img();
                        }
                    }
                });
            });
        });

	});


    $('#work_arr_choose').change(function(){
        var val = $(this).find('option:selected').attr('value');
        var name = $(this).find('option:selected').attr('data-name');
        if (val>0) {
            $('.multiple_worker_box').show();
            var html = '<div class="worker_info" style="margin-right:5px;margin-bottom: 5px;background-color: #BEC1C3;color:white;padding:0 5px 3px;border-radius:5px; display:inline-block !important; display:inline; ">'+name+'&nbsp;<span class="del" data-wid="'+val+'" style="color: red;font-size:18px;cursor:pointer">×</span><input type="hidden" name="work_arr[]" value="'+val+'"></div>';
            $('.workers_box').append(html);
            $(this).find('option:selected').hide();
            $('.del').click(function () {
                var wid = $(this).data('wid');
                $(this).closest('.worker_info').remove();
                $('#work_arr_choose').find('.choose_'+wid).show();
            })
            $('#work_arr_choose').val('');
        }
    });

    $('.del').click(function () {
        var wid = $(this).data('wid');
        $(this).closest('.worker_info').remove();
        $('#work_arr_choose').find('.choose_'+wid).show();
    });

    $('#template_type').change(function () {
        var val = $(this).val();
        if (2==val) {
            $('.template_type_2_box').show();
        } else {
            $('.template_type_2_box').hide();
        }
        var qy_qrcode = $('#qy_qrcode').val();
        var template_url = $('#template_url').val();
        if (1==val && qy_qrcode) {
            services_look_img();
        } else if (2==val && qy_qrcode && template_url){
            services_look_img();
        } else {
            $('.effect_img_box').hide();
        }
    });

    function services_look_img() {
        var template_type = $('#template_type').val();
        if (0==template_type || !template_type) {
            return false;
        }
        var qy_qrcode = $('#qy_qrcode').val();
        if (!qy_qrcode) {
            layer.open({content: '请上传企业微信群二维码链接'});
            return false;
        }
        var template_url = $('#template_url').val();
        if (2==template_type && !template_url) {
            layer.open({content: '请上传对应模板链接'});
            return false;
        } else if(!template_url) {
            template_url = '';
        }

        var url = "{pigcms{:U('qyWeixin/services_look_img')}";
        var param = {};
        param['template_type'] = template_type;
        param['template_url'] = template_url;
        param['qy_qrcode'] = qy_qrcode;
        layer.load(0, {shade: [0.6,'#fff']});
        $.post(url,param,function(data){
            console.log('services_look_img',data);
            if (data.data) {
                var file_path = data.data.file_path;
                var file = data.data.file;
                $('#effect_img_view').attr("src",file_path);
                $('.effect_img_box').show();
                $("#effect_img").val(file);
                layer.closeAll();
            }
        },'json');
    }

    function check_submit(){
	if($('#single_name').val() == ''){
		alert('楼栋名称不能为空！');
		return false;
	}

	// if($('#long_lat').val() == ''){
        //	// 	alert('楼栋地址不能为空！');
        //	// 	return false;
        //	// }

	if($('#measure_area').val() == ''){
		alert('楼栋面积不能为空！');
		return false;
	}

	if($('#floor_num').val() == ''){
		alert('单元数量不能为空！');
		return false;
	}

	if($('#vacancy_num').val() == ''){
		alert('房屋数量不能为空！');
		return false;
	}

	if($('#upper_layer_num').val() == ''){
		alert('地面建筑层数不能为空！');
		return false;
	}

	if($('#lower_layer_num').val() == ''){
		alert('地下建筑层数不能为空！');
		return false;
	}
}


</script>

<include file="Public:footer"/>