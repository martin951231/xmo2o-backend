<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('Unit/parking_management')}">车位管理</a>
            </li>
            <li class="active">车位信息修改</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<div class="form-group garage_select">
									<label class="col-sm-1"><label for="garage_id">车库</label></label>
									<select name="garage_id" id="garage_id">
										<volist name="garage_list" id="vol">
											<option value='{pigcms{$vol.garage_id}' <if condition="$vol[garage_id] eq $position_list[garage_id]">selected</if>>{pigcms{$vol.garage_num}-{pigcms{$vol.garage_position}</option>
										</volist>
									</select>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="position_num">车位号</label></label>
									<input class="col-sm-2" size="20" name="position_num" id="position_num" type="text"  value="{pigcms{$position_list.position_num}" />
									<span style="color: red;margin-left: 10px;">*必填,不能使用中文</span>
								</div>
								
						<!-- 		<div class="form-group">
									<label class="col-sm-1"><label for="position_type">车位类型</label></label>
									<select name="position_type" id="position_type">
										<option value='1' <if condition="$position_list.position_type eq '1'">selected</if>>产权车位</option>
										<option value='2' <if condition="$position_list.position_type eq '2'">selected</if>>租赁车位</option>
										<option value='3' <if condition="$position_list.position_type eq '3'">selected</if>>临停车位</option>
									</select>
								</div> -->
                                <if condition="$house_village_park_config.park_versions eq 2 && $house_village_park_config.is_park_month_type eq 1">
                                    <if condition="$new_charge_time eq 0">
                                    <div class="form-group">
                                        <label class="col-sm-1">车位模式</label>
                                        <label><input name="position_pattern" class="position_pattern" id="position_pattern" <if condition="$position_list.position_pattern eq 1">checked="checked" </if> type="radio" value="1"/>&nbsp;&nbsp;真实车位</label>
                                        &nbsp;&nbsp;&nbsp;
                                        <label><input name="position_pattern" class="position_pattern" id="position_pattern" <if condition="$position_list.position_pattern eq 2">checked="checked" </if>  type="radio" value="2"/>&nbsp;&nbsp;虚拟车位</label>
                                        &nbsp;&nbsp;&nbsp;
                                        <span style="margin-left: 10px;">真实车位表示小区内已规划的车位，业主有固定的车位可停车；而虚拟车位即表示小区内没有规划的车位但可进入小区随意停车</span>
                                    </div>
                                    </if>
								</if>

                                <if condition="$user_bind">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="position_num">当前绑定业主</label></label>
                                        【姓名】{pigcms{$user_bind['name']}；【手机号】{pigcms{$user_bind['phone']}
                                    </div>
                                </if>

								<div class="form-group" id="form_group_uid">
									<label class="col-sm-1"><label for="position_num">绑定业主</label></label>
									<input  name="find_value" id="find_value" type="text" autocomplete="off" value="" style="width:100px;margin-right:10px;"/>
									<input  name="user_id" id="user_id" type="hidden"  value=""/>
									<div id="searchBox" class="dropdown-menus"></div>
									<span style="margin-left: 10px;">不填写则不修改,请输入手机号进行查询绑定</span>
								</div>
								<if condition="$house_village_park_config.is_park_month_type eq 1 AND $house_village_park_config.park_versions eq 2">
									<div class="form-group status_select">
										<label class="col-sm-1"><label for="position_status">月租车收费类型</label></label>
										<select name="month_charge_type" id="month_charge_type">
											<volist name="dataList" id="vo">
												<option value="{pigcms{$vo.id}" <if condition="$position_list.month_charge_type eq $vo.id">selected="selected" </if>>{pigcms{$vo.month_name}</option>
											</volist>
										</select>
									</div>
<!--									<div class="form-group" id="park_num">-->
<!--										<label class="col-sm-1"><label for="position_num">可绑定车辆数量</label></label>-->
<!--										<input  name="park_num" id="park_num" type="text"  value="1" style="width:100px;margin-right:10px;"/>-->
<!--										<span style="margin-left: 10px;">默认可添加一辆车</span>-->
<!--									</div>-->
									<div class="form-group" id="area">
										<label class="col-sm-1"><label for="position_num">停车场到期时间</label></label>
										<label class="col-sm-1"><label for="position_num">无</label></label>
									</div>
								</if>

								<div class="form-group">
									<label class="col-sm-1"><label for="position_area">车位面积</label></label>
									<input class="col-sm-2" size="20" name="position_area" id="position_area" type="text"  value="{pigcms{$position_list.position_area}" />
									<label><span class="green">单位：平方米</span></label>
								</div>
								
								<div class="form-group" >
									<label class="col-sm-1"><label>备注</label></label>
									<label><textarea name="position_note" id="position_note" maxlength="255" style="width:286px;height:90px;resize:none" placeholder="最多输入255个字">{pigcms{$position_list.position_note}</textarea></label>
								</div>

								<div class="form-group">
									<label class="col-sm-1"><label for="position_status">租售状态</label></label>
										<if condition="$position_list.position_status eq '1'"><label>未售出</label></if>
										<if condition="$position_list.position_status eq '2'"><label>已售出</label></if>
								</div>
								<if condition="$house_village_park_config.is_park_month_type neq 1 AND $house_village_park_config.park_versions neq 2 AND $new_charge_time eq 0">
								<div class="form-group">
									<label class="col-sm-1" for="park_flag">收费项</label>
									<if condition="in_array(261,$house_session['menus'])">
									<a class="bind_info btn label-info" href="{pigcms{:U('payment_add',array('position_id'=>$position_list['position_id']))}">添加收费项</a>
									<else/>
									<button class="btn" disabled="disabled">添加收费项</button>
									</if>
									<div class="grid-view" style="width:80%;margin-left: 8%">
										<table class="table table-striped table-bordered table-hover">
										    <thead>
										        <tr>
										            <th>收费项目</th>
										            <th>收费模式</th>
										            <th>计量方式</th>
										            <th>计量数量</th>
										            <th>收费金额</th>
										            <th>收费周期</th>
										            <th>收费周期上限</th>
										            <th>开始时间</th>
										            <th>结束时间</th>
										            <th>签订周期</th>
										            <th>已缴费周期</th>
										            <!-- <th>备注</th> -->
										            <th>操作</th>
										        </tr>
										    </thead>
										    <tbody class="class-checkbox">
										    	<if condition="is_array($payment_list)">
										    		<volist name="payment_list" id="vo">
											            <tr>
											                <td>{pigcms{$vo.payment_name}</td>
											                <td><div class="tagDiv"><if condition="$vo.pay_type eq 1">固定费用<else/>按金额*计量方式</if></div></td>
											                <td>{pigcms{$vo.metering_mode}</td>
											                <td>{pigcms{$vo.metering_mode_val}</td>
											                <td>{pigcms{$vo.pay_money}</td>
											                <td>{pigcms{$vo.pay_cycle} ({pigcms{$cycle_type[$vo['cycle_type']]})&nbsp;/&nbsp;周期</td>
											                <td>{pigcms{$vo.max_cycle} &nbsp;周期</td>
											                <td>{pigcms{$vo.start_time|date="Y-m-d",###}</td>
											                <td>{pigcms{$vo.end_time|date="Y-m-d",###}</td>
											                <td>{pigcms{$vo.cycle_sum}</td>
											                <td>{pigcms{$vo.paid_cycle}</td>
											                <!-- <td>{pigcms{$vo.remarks}</td> -->
											                <td>
															<if condition="in_array(262,$house_session['menus'])">
																<a class="label label-sm label-info" href="javascript:void(0);" onclick="payment_del({pigcms{$vo.bind_id})">删除</a>
															<else/>
																无
															</if>
															</td>
											            </tr>
											        </volist>
											        <else/>
											        <tr><td colspan="11" style="text-align: center; color: red;">暂无收费项</td></tr>
											        
										    	</if>
										        
										    </tbody>
										</table>
									</div>
								</div>
                                </if>
								
								<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
								<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
								<script>
									$('.bind_info').click(function(){
										art.dialog.open($(this).attr('href'),{
											init: function(){
												var iframe = this.iframe.contentWindow;
												window.top.art.dialog.data('iframe_handle',iframe);
											},
                                            close: function () {
                                                window.location.reload();
                                            },
											id: 'handle',
											title:'添加收费项',
											padding: 0,
											width: 1000,
											height: 603,
											lock: true,
											resize: false,
											background:'black',
											button: null,
											fixed: false,
											left: '50%',
											top: '38.2%',
											opacity:'0.4'
										});
										return false;
									});
								</script>
								<script>
									function payment_del(bind_id) {
										var payment_del_url = "{pigcms{:U('User/payment_del')}";
										var position_id = "{pigcms{$_GET['position_id']}";
										layer.confirm('您确定要删除这条收费项吗？', {
											btn: ['确定','取消'] //按钮
										}, function(){
											$.post(payment_del_url,{bind_id:bind_id,position_id:position_id},function(data){
												if(data.error == 1){
													alert(data.msg);
													location.href = location.href;
												}else{
													alert(data.msg);
												}
											},'json');
										});
									}
								</script>
							</div>
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info submit_info" type="button" <if condition="!in_array(47,$house_session['menus'])">disabled="disabled"</if>>
										<i class="ace-icon fa fa-check bigger-110"></i>
										修改
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
<style type="text/css">
	#searchBox{
		font-family: Monospaced Number,Chinese Quote,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,PingFang SC,Hiragino Sans GB,Microsoft YaHei,Helvetica Neue,Helvetica,Arial,sans-serif;
		line-height: 1.5;
		color: rgba(0,0,0,.65);
		margin: 0;
		padding: 0;
		list-style: none;
		-webkit-box-shadow: 0 2px 8px rgba(0,0,0,.15) !important;
		box-shadow: 0 2px 8px rgba(0,0,0,.15) !important;
		border-radius: 4px;
		-webkit-box-sizing: border-box;
		box-sizing: border-box;
		outline: none;
		font-size: 14px;
		min-width: auto;right:0px;left:0px;
		/*top:33px;*/
		position:absolute;
		z-index: 10;
		display: block;
		background: #fff;
		width: 12%;
		margin-left: 9%;
	}
	#searchBox .ant-select-dropdown-menu-item-active {
		background-color: #e6f7ff;
	}
	#searchBox .ant-select-dropdown-menu-item {
		position: relative;
		display: block;
		padding: 5px 12px;
		line-height: 22px;
		font-weight: 400;
		color: rgba(0,0,0,.65);
		white-space: nowrap;
		cursor: pointer;
		overflow: hidden;
		text-overflow: ellipsis;
		-webkit-transition: background .3s ease;
		transition: background .3s ease;
	}
	#searchBox .ant-select-dropdown-menu-item{line-height:35px;list-style: none;border:none !important;}
	#searchBox .ant-select-dropdown-menu-item:hover {
		background-color: #e6f7ff !important;
	}

	#searchBox .ant-select-dropdown-menu-item:first-child {
		border-radius: 4px 4px 0 0;
	}

	#searchBox .ant-select-dropdown-menu-item:last-child {
		border-radius: 0 0 4px 4px
	}

	#bind-user{
		overflow: hidden;
	}

</style>
<script type="text/javascript">
	var is_park_month_type = "{pigcms{$house_village_park_config.is_park_month_type}";
	var park_versions = "{pigcms{$house_village_park_config.park_versions}";
$('.submit_info').click(function(){
	var garage_id = $('#garage_id').val(); //区域ID
	if(!garage_id){
		layer.msg('请选择位置!',{icon:2});
		return false;
	}	
	var position_num = $('#position_num').val();
	if(!position_num){
		layer.msg('车位号不能为空!',{icon:2});
		return false;
	}

	var position_area = $('#position_area').val();//区域面积
//	if(!position_area){
//		layer.msg('车位面积不能为空!',{icon:2});
//		return false;
//	}
	 var position_status = $('#position_status').val();//车位状态
	// if(!position_status){
	// 	layer.msg('车位状态不能为空!',{icon:2});
	// 	return false;
	// }

	 var position_type = $('#position_type').val();//车位状态
	// if(!position_type){
	// 	layer.msg('车位类型不能为空!',{icon:2});
	// 	return false;
	// }
	if(is_park_month_type == 1 && park_versions == 2){
		var month_charge_type = $('#month_charge_type').val();//区域面积
		if(!month_charge_type){
			layer.msg('月租车收费类型不能为空!',{icon:2});
			return false;
		}
	}
	var position_note = $('#position_note').val();//车主备注
	$.post("{pigcms{:U('parking_edit')}",{'position_id':{pigcms{$position_list.position_id},'position_num':position_num,'garage_id':garage_id,'position_area':position_area,'position_note':position_note,'month_charge_type':month_charge_type,'park_num':$('#park_num').val(),'user_id':$('#user_id').val(),'position_pattern':$('input[name="position_pattern"]:checked').val()},function(data){
        if(data.code == 1){
            layer.msg(data.msg,{icon: 1},function(){
	           	location.href="{pigcms{:U('parking_management',['page'=>$_GET['page']])}";
	        });
        }
        if(data.code == 2){
            layer.msg(data.msg,{icon: 2});
        }
        
    },'json');
})


	$(function(){
		arr = [];
		$('#find_value').live('keyup',function(){
			var find_type  = '2';//请求类型2 手机号
			var find_value= $.trim(this.value);
			if(find_value!=""){//检测键盘输入的内容是否为空，为空就不发出请求,type:'true'
				$.post("{pigcms{:U('Cashier/ajax_user_list')}",{'find_type':find_type,'find_value':find_value},function(data){
					if (data.user_list && data.user_list!=null && data.user_list.length> 0) {//检测返回的结果是否为空
						var str = '<div><ul class="findtype-menu"  style="    overflow-y: auto;max-height: 255px; ">';
						for(var i in data['user_list']){
							var id=data["user_list"][i]["pigcms_id"];
							var final= $.inArray(parseInt(id),arr);
							str += '<li class="ant-select-dropdown-menu-item " style="line-height: 20px;" value="'+data["user_list"][i]["pigcms_id"]+'" phone="'+data["user_list"][i]["phone"]+'" uid="'+data["user_list"][i]["uid"]+'">'+data["user_list"][i]["name"];
							if(final >= 0){
								str+='<span style="float:right;">已选</span></li>';
							}else{
								str+='</li>';
							}
						};

						str+="</ul></div>";

						$("#searchBox").html(str).show();//将搜索到的结果展示出来

					} else {
						$("#searchBox").html("").hide();
					}
				},'json');
			}else{
				$("#searchBox").html("").hide();  //没有查询结果就隐藏搜索框
			}
		});

		$('#find_value').live('focus',function(){
			var find_type  = '2';//请求类型2 手机号
			var find_value= $.trim(this.value);
			if(find_value!=""){//检测键盘输入的内容是否为空，为空就不发出请求,type:'true'
				$.post("{pigcms{:U('Cashier/ajax_user_list')}",{'find_type':find_type,'find_value':find_value},function(data){
					if (data.user_list && data.user_list!=null && data.user_list.length> 0) {//检测返回的结果是否为空
						var str = '<div><ul class="findtype-menu"  style="    overflow-y: auto;max-height: 255px; ">';
						for(var i in data['user_list']){
							var id=data["user_list"][i]["pigcms_id"];
							var final= $.inArray(parseInt(id),arr);
							str += '<li class="ant-select-dropdown-menu-item " value="'+data["user_list"][i]["pigcms_id"]+'" phone="'+data["user_list"][i]["phone"]+'" uid="'+data["user_list"][i]["uid"]+'">'+data["user_list"][i]["name"];
							if(final >= 0){
								str+='<span style="float:right;">已选</span></li>';
							}else{
								str+='</li>';
							}
						};

						str+="</ul></div>";

						$("#searchBox").html(str).show();//将搜索到的结果展示出来

					} else {
						$("#searchBox").html("").hide();
					}
				},'json');
			}else{
				$("#searchBox").html("").hide();  //没有查询结果就隐藏搜索框
			}
		});

		$(document).on("click",'.ant-select-dropdown-menu-item',function(e){
			var pigcms_id = $(this).val();
			var name = $(this).text();
			var phone = $(this).attr('phone');
			var uid = $(this).attr('uid');
			var result = $.inArray(pigcms_id,arr);
			if(result<'0'){
				arr.push(pigcms_id);
				res = JSON.stringify(arr);
//				$('#float_right_div').append('<div style="margin-bottom: 3px;">'+phone+'[<font style="color:#2b7dbc">'+name+'</font>]<span style="padding-left:20px" onclick="_remove(this,'+pigcms_id+')"><a href="javascript:void(0)" style="color:red;">删除</a></span><br/></div>');
				$("#searchBox").html("").hide(); //输入框失去焦点的时候就隐藏搜索框
				$(".selected").show();

				$("#find_value").val(name);
				$("#user_id").val(pigcms_id);

			}


		})
		$(document).bind("click",function(e){
			var target=$(e.target);
			if(target.closest("#dropdown-menu").length==0){
				$("#searchBox").html("").hide();
			}
		})


	});

    var position_pattern = "{pigcms{$position_list.position_pattern}";
	if(position_pattern == 2){
		$("#form_group_uid").hide();
		$("#park_num").hide();
		$("#area").hide();
	}

	$(".position_pattern").click(function(){


		console.log($("input[name='position_pattern']:checked").val());
		if($("input[name='position_pattern']:checked").val() == 1){

			$("#form_group_uid").show();
			$("#park_num").show();
			$("#area").show();
		}else if($("input[name='position_pattern']:checked").val() == 2) {

			$("#form_group_uid").hide();
			$("#park_num").hide();
			$("#area").hide();
		}
	})
</script>

<include file="Public:footer"/>