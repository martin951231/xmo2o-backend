<include file="Public:header" />
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-credit-card"></i>
				<a href="{pigcms{:U('Card_new/index')}">{pigcms{:L_("会员卡")}</a>
			</li>
			<li>
				<a href="{pigcms{:U('Card_new/card_new_coupon')}">{pigcms{:L_("会员卡优惠券")}</a>
			</li>
			<li class="active">{pigcms{:L_("派发优惠券")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-sm-12">

					<div class="tabbable" style="margin-top:20px;">
						<ul class="nav nav-tabs" id="myTab">
							<li <if condition="$_GET['tab'] eq 1 OR !isset($_GET['tab'])">class="active"</if>>
								<a data-toggle="tab" href="#basicinfo" data-id="card_group">
									{pigcms{:L_("分组派发")}
								</a>
							</li>
							<li <if condition="$_GET['tab'] eq 3 ">class="active"</if>>
								<a data-toggle="tab" href="#diyinfo" data-id="diy">
									{pigcms{:L_("自定义会员标签派发")}
								</a>
							</li>
							<li <if condition="$_GET['tab'] eq 2 ">class="active"</if>>
								<a data-toggle="tab" href="#groupinfo" data-id="person">
									{pigcms{:L_("个人派发")}
								</a>
							</li>

							<li>
								<a href="{pigcms{:U('Card_new/send_all')}">
									{pigcms{:L_("全部派发")}
								</a>
							</li>
							<li>
								<a href="{pigcms{:U('Card_new/weixin_send')}">
									{pigcms{:L_("微信购买派发")}
								</a>
							</li>
							<li>
								<a href="{pigcms{:U('Card_new/send_history')}">
									{pigcms{:L_("派发记录")}
								</a>
							</li>
                            <li <if condition="$_GET['tab'] eq 6 ">class="active"</if>>
                            <a data-toggle="tab" href="#employeeinfo" data-id="employee">
                                {pigcms{:L_("员工派发")}
                            </a>
                            </li>
						</ul>
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane <if condition=" $_GET['tab'] eq 1 OR !isset($_GET['tab'])">active</if>">
								<div class="widget-box">
									<div class="widget-body" id="main" style="padding:20px;height:300px;width:100%;overflow:auto">
										<!--<if condition="$card_group">
										<volist name="card_group" id="vo">
											<div class="radio">
												<label>
													<input class="paycheck ace store-list" type="checkbox" name="card_group_id[]" value="{pigcms{$vo.id}" id="coupon{pigcms{$vo.id}" <if condition="$vo.user_count eq 0">disabled="disabled"</if>/>
													<span class="lbl"><label for="coupon{pigcms{$vo.id}">{pigcms{$vo.name}--{pigcms{$vo.des}</label></span>
												</label>
											</div>
										</volist>
										<else />
											{pigcms{:L_("没有分组")}
										</if>-->

										<table class="table table-striped table-bordered table-hover">
											<thead>
												<tr>
													<th>{pigcms{:L_("勾选分组")}</th>

													<th>{pigcms{:L_("分组名称")}</th>
													<th>{pigcms{:L_("分组描述")}</th>

												</tr>
											</thead>
											<tbody>
												<if condition="$card_group">
													<volist name="card_group" id="vo">


														<tr class="even">
															<td style="width: 120px"><input type="checkbox" name="card_group_id[]" value="{pigcms{$vo.id}" id="coupon{pigcms{$vo.id}" <if condition="$vo.user_count eq 0">disabled="disabled"
												</if>></td>
												<td style="width: 120px">{pigcms{$vo.name}</td>
												<td style="width: 120px">{pigcms{$vo.des}</td>
												</tr>
												</volist>
												<else />
												<tr>
													<td colspan="3">{pigcms{$vo.des}</td>
												</tr>
												</if>
											</tbody>
										</table>
									</div>
								</div>
								<div class="row">
									<div class="col-xs-12">
										<div class="grid-view">

										</div>
									</div>

								</div>
							</div>
							<div id="diyinfo" class="tab-pane <if condition=" $_GET['tab'] eq 3">active</if>">
								<div class="widget-box">
									<div class="widget-body" id="main" style="padding:20px;height:300px;width:100%;overflow:auto">

										<table class="table table-striped table-bordered table-hover">
											<thead>
												<tr>
													<th>{pigcms{:L_("勾选标签")}</th>

													<th>{pigcms{:L_("标签名称")}</th>
													<th>{pigcms{:L_("标签值")}</th>

												</tr>
											</thead>
											<tbody>
												<if condition="$diy_list">
													<volist name="diy_list" id="vo">
														<tr class="even">
															<td style="width: 120px"><input type="checkbox" name="diy_id[]" value="{pigcms{$vo.diy_value}" id="coupon{pigcms{$vo.id}"></td>
															<td style="width: 120px">{pigcms{$vo.diy_name}</td>
															<td style="width: 120px">{pigcms{$vo.show_value}</td>
														</tr>
													</volist>
													<else />
													<tr>
														<td colspan="3">请联系系统管理员添加自定义标签！</td>
													</tr>
												</if>
											</tbody>
										</table>
									</div>
								</div>
								<div class="row">
									<div class="col-xs-12">
										<div class="grid-view">

										</div>
									</div>

								</div>
							</div>
							<div id="groupinfo" class="tab-pane <if condition=" $_GET['tab'] eq 2 ">active</if>">
								<div class="widget-box">
									<div class="widget-body" id="group_main" style="padding:20px;height:100px;width:100%;">
										<select name="keyword">
											<option value="nickname">{pigcms{:L_("用户昵称")}</option>
											<option value="phone">{pigcms{:L_("用户手机")}</option>
										</select>
										<input type="text" name="search_val" style="padding: 3px 3px 4px;"><a style="padding: 0.8em 0.4em 1.7em;margin-left:10px" class="label label-sm label-info" href="javascript:void(0);" id="search_user">{pigcms{:L_("搜索用户")}</a>
									</div>
									<table class="table table-striped table-bordered table-hover" id="user_table" style="display:none">
										<thead>
											<tr>
												<th>{pigcms{:L_("勾选会员")}</th>
												<th>{pigcms{:L_("用户会员卡")}ID</th>
												<th>{pigcms{:L_("用户昵称")}</th>
												<th>{pigcms{:L_("用户手机")}</th>

											</tr>
										</thead>
										<tbody id="user_list">

										</tbody>
									</table>
								</div>
								<div class="row">
									<div class="col-xs-12">
										<div class="grid-view">

										</div>
									</div>

								</div>
							</div>
                <div id="employeeinfo" class="tab-pane <if condition="$_GET['tab'] eq 6 ">active</if>"   >
                <div class="widget-box">
                    <div class="widget-body" style="padding:20px;height:100px;width:100%;">
                        <ul>
                            <li class="on">选择员工身份标签</li>
                            <li>
                                <volist name="label_list" id="vo">
                                    <label style="margin-left:15px;">{pigcms{$vo['name']}
                                    <input type="checkbox" name="user_label[]" value="{pigcms{$vo.id}" id="user_label{pigcms{$vo.id}">
                                    </label>
                                </volist>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <div class="grid-view">

                        </div>
                    </div>

                </div>
            </div>
							<style type="text/css">
								.radio {
									min-height: 27px;
								}
							</style>
							<div class="tab-pane " style="display:block">
								<div class="widget-box">

									<div class="widget-body form-group" style="padding:20px;height:300px;width:100%;line-height: 1.5;overflow:auto">
										<!--<volist name="coupon_list" id="vo">
											<div class="radio">
												<label>
													<input class="paycheck ace store-list" type="checkbox" name="coupon_id[]" value="{pigcms{$vo.coupon_id}" id="coupon{pigcms{$vo.coupon_id}"/>
													<span class="lbl"><label for="coupon{pigcms{$vo.coupon_id}">{pigcms{$vo.name} - {pigcms{$vo.des}</label></span>
												</label>
											</div>
										</volist>-->
										<table class="table table-striped table-bordered table-hover">
											<thead>
												<tr>
													<th>{pigcms{:L_("勾选优惠券")}</th>

													<th>{pigcms{:L_("优惠券名称")}</th>
													<th>{pigcms{:L_("优惠券描述")}</th>
													<th>{pigcms{:L_("发放数量")}</th>
												</tr>
											</thead>
											<tbody id="coupon_list">
												<volist name="coupon_list" id="vo">


													<tr class="even">
														<td style="width: 120px">
															<input type="checkbox" name="coupon_id[]" value="{pigcms{$vo.coupon_id}" id="coupon{pigcms{$vo.id}">
														</td>
														<td style="width: 120px">{pigcms{$vo.name}</td>
														<td style="width: 120px">{pigcms{$vo.des}</td>
														<td style="width: 120px">
															<input type="number" name="send_nums[]" value="1" id="sendNum{pigcms{$vo.coupon_id}" />
														</td>
													</tr>
												</volist>
											</tbody>
										</table>
									</div>
								</div>
								<div class="row">
									<div class="col-xs-12">
										<div class="grid-view">
											<button class="btn btn-info" type="submit" id="save_btn" style="margin-left: 25%;" href="">
												<i class="ace-icon fa fa-check bigger-110"></i>
												{pigcms{:L_("派发优惠券")}
											</button>
										</div>
									</div>

								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
	var tab = $('#myTab .active a').attr('data-id');
	$(function() {
		$('#search_user').click(function() {
			var keyword = $('select[name="keyword"]').val();
			var search_val = $('input[name="search_val"]').val();
			$.post("{pigcms{:U('Card_new/ajax_get_user_merid')}", {
				keyword: keyword,
				search_val: search_val
			}, function(data, textStatus, xhr) {

				if (typeof data == 'null' || data == null || data == undefined || data == '') {
					$('#user_list').html('<tr class="odd"><td class="button-column" colspan="4" >{pigcms{:L_("没有查询到用户")}</td></tr>');
				} else {
					var str;
					$.each(data, function(index, val) {
						console.log(val)
						if (index % 2 == 0) {
							str += '<tr class="even">';
						} else {
							str += '<tr class="odd">';
						}
						str += '<td style="width: 120px" class="select_user"><input type="radio" name="uid" value="' + val.uid + '"></td>';
						if (val.is_card == 1) {
							str += '<td style="width: 120px">' + val.id + '</td>';
						} else {
							str += '<td style="width: 120px">无</td>';
						}
						str += '<td style="width: 120px">' + val.nickname + '</td>';
						str += '<td style="width: 120px">' + val.phone + '</td>';
						str += '</tr>';
					});

					$('#user_list').html(str);
				}
				$('#user_table').show();

			});
		});
		/*$('.select_user input').live('click',function(){
			$(this).prop('checked',!$(this).prop('checked'));
			return false;
		});
		$('.select_user').live('click',function(){
			$(this).find('input').prop('checked',!$(this).find('input').prop('checked'));
		});*/

		$('#save_btn').click(function() {
			var send_url = "{pigcms{:C('config.site_url')}{pigcms{:U('Card_new/send')}";


			if (tab == 'person' && $("input[name='uid']:checked").length == 0) {
				alert('{pigcms{:L_("没有勾选用户")}');
				return false;
			} else {
				uid = $("input[name='uid']:checked").val();
			}

			if (tab == 'card_group' && $("input[name='card_group_id[]']:checked").length == 0) {
				alert('{pigcms{:L_("没有勾选用户分组")}');
				return false;
			} else {
				var card_group_id = '';
				$('input[name="card_group_id[]"]').each(function() {
					if ($(this).is(":checked")) {
						card_group_id += $(this).val() + ',';
					}
				});
				card_group_id = card_group_id.substring(0, card_group_id.length - 1);
			}
			if (tab == 'diy' && $("input[name='diy_id[]']:checked").length == 0) {
				alert('{pigcms{:L_("没有勾选自定义标签")}');
				return false;
			} else {
				var diy_ids = '';
				$('input[name="diy_id[]"]').each(function() {
					if ($(this).is(":checked")) {
						diy_ids += $(this).val() + ',';
					}
				});
				diy_ids = diy_ids.substring(0, diy_ids.length - 1);
			}
            var userLabel='';
            if(tab=='employee'&&$("input[name='user_label[]']:checked").length==0){
                alert('{pigcms{:L_("请选择员工身份标签")}');return false;
            }else{
                $('input[name="user_label[]"]').each(function () {
                    if ($(this).is(":checked")) {
                        userLabel +=$(this).val()+',';
                    }
                });
            }

            var coupon_id='';
			if ($("input[name='coupon_id[]']:checked").length == 0) {
				alert('{pigcms{:L_("没有勾选优惠券")}');
				return false;
			}

			var coupon_send_num = '';
			var send_num_arr = [];
			$('input[name="coupon_id[]"]').each(function() {
				if ($(this).is(":checked")) {
					coupon_id += $(this).val() + ',';

					var send_num = parseInt($('#sendNum' + $(this).val()).val());
					coupon_send_num += send_num + ',';
					send_num_arr.push(send_num);
				}
			});

			//判断发放数量必须大于1且为数字
			for (let ci = 0; ci < send_num_arr.length; ci++) {
				const element = send_num_arr[ci];
				if (element < 1 || isNaN(element)) {
					alert('{pigcms{:L_("优惠券发放数量不能少于1张")}');
					return false;
				}
			}
			coupon_id = coupon_id.substring(0, coupon_id.length - 1);
			coupon_send_num = coupon_send_num.substring(0, coupon_send_num.length - 1);

			//alert(send_url);
			if (tab == 'person') {
				$(this).attr('href', send_url + '&uid=' + uid + '&coupon_id=' + coupon_id + '&coupon_send_num=' + coupon_send_num);
			} else {
				$(this).attr('href', send_url + '&card_group_id=' + card_group_id + '&coupon_id=' + coupon_id + '&coupon_send_num=' + coupon_send_num+'&userLabel='+userLabel);
			}
			if (tab != 'person') {
				$.post(send_url, {
					card_group_id: card_group_id,
					coupon_id: coupon_id,
					coupon_send_num:coupon_send_num,
					diy_ids: diy_ids,
					userLabel:userLabel
				}, function(data, textStatus, xhr) {
					artDialog({
						content: data.info,
						time: 3
					})
					if (data.status) {
						//window.location.reload();
					}
				}, 'json');
				return false;
			}
			art.dialog.open($(this).attr('href'), {
				init: function() {
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle', iframe);
				},
				id: 'handle',
				title: $(this).data('title'),
				padding: 0,
				width: 720,
				height: 520,
				lock: true,
				resize: false,
				background: 'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '20%',
				opacity: '0.4'
			});
			return false;

			console.log(coupon_id);
		});

		$('input[name="card_group_id[]"]').click(function() {
			var card_group_id = '';
			$('input[name="card_group_id[]"]').each(function() {
				if ($(this).is(":checked")) {
					card_group_id += $(this).val() + ',';
				}
			});
			card_group_id = card_group_id.substring(0, card_group_id.length - 1);
			$.post("{pigcms{:U('Card_new/ajax_get_send_coupon')}", {
				card_group_id: card_group_id
			}, function(data, textStatus, xhr) {
				console.log(data);
				if (data == null || data == undefined || data == '') {
					$('#coupon_list').html('{pigcms{:L_("没有可用的优惠券")}');
				} else {
					var str = '';
					$.each(data, function(coupon_id, val) {
						// str += '<div class="radio"><label>';
						// if(val.disable){
						// str+='<input class="paycheck ace store-list" disabled="disabled" type="checkbox" name="coupon_id[]" value="'+coupon_id+'" id="coupon'+coupon_id+'"/>';
						// }else{							
						// str+='<input class="paycheck ace store-list" type="checkbox" name="coupon_id[]" value="'+coupon_id+'" id="coupon'+coupon_id+'"/>';
						// }
						// str+='<span class="lbl"><label for="coupon'+coupon_id+'">'+val.name+' - '+val.des+'</label></span>';
						// str+='</label></div>';
						if (coupon_id % 2 == 0) {
							str += '<tr class="even">';
						} else {
							str += '<tr class="odd">';
						}
						str += '<td style="width: 120px">';
						if (val.disable) {
							str += '<input type="checkbox" name="coupon_id[]" value="' + coupon_id + '" disabled="disabled"  id="coupon' + coupon_id + '"></td>';
						} else {
							str += '<input type="checkbox" name="coupon_id[]" value="' + coupon_id + '" id="coupon' + coupon_id + '"></td>';
						}
						str += '<td style="width: 120px">' + val.name + '</td>';
						str += '<td style="width: 120px">' + val.des + '</td>';
						str += '<td style="width: 120px"><input type="number" value="1"  name="send_nums[]" id="sendNum' + coupon_id + '"></td>';
						str += '</tr>';
					});
					$('#coupon_list').html(str);
				}
			});
		});

		$('#myTab li').click(function(event) {
			$(":checkbox").attr("checked", false);
			tab = $(this).find('a').attr('data-id');
			$.post("{pigcms{:U('Card_new/ajax_get_send_coupon')}", '', function(data, textStatus, xhr) {
				if (data == null || data == undefined || data == '') {
					$('#coupon_list').html('{pigcms{:L_("没有可用的优惠券")}');
				} else {
					var str = '';
					$.each(data, function(coupon_id, val) {
						// str += '<div class="radio"><label>';
						// str+='<input class="paycheck ace store-list" type="checkbox" name="coupon_id[]" value="'+coupon_id+'" id="coupon'+coupon_id+'"/>';
						// str+='<span class="lbl"><label for="coupon'+coupon_id+'">'+val.name+' - '+val.des+'</label></span>';
						// str+='</label></div>';
						str += '<tr class="even">';
						str += '<td style="width: 120px">';
						str += '<input type="checkbox" name="coupon_id[]" value="' + coupon_id + '" id="coupon' + coupon_id + '"></td>';
						str += '<td style="width: 120px">' + val.name + '</td>';
						str += '<td style="width: 120px">' + val.des + '</td>';
						str += '<td style="width: 120px"><input type="number" value="1" name="send_nums[]" id="sendNum' + coupon_id + '"></td>';
						str += '</tr>';
					});
					$('#coupon_list').html(str);
				}
			});
		});
	});
</script>


<include file="Public:footer" />