<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/storestaffBase.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
	</head>
	<body>
		<div class="headerBox">
			<div class="txt">{pigcms{$config.group_alias_name}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_("返回首页")}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_("刷新页面")}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink" data-url="{pigcms{:U('group_list')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("订单列表")}</div>
					</li>
					<li class="urlLink cur" data-url="{pigcms{:U('group_coupon')}">
						<div class="icon coupon"></div>
						<div class="text">{pigcms{:L_("团购券管理")}</div>
					</li>
					<li class="urlLink" data-url="{pigcms{:U('group_find')}">
						<div class="icon search"></div>
						<div class="text">{pigcms{:L_("查找订单")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<div class="form-group clearfix">
					<form action="{pigcms{:U('Stroe/group_coupon')}" method="get">
						<input type="hidden" name="c" value="Store"/>
						<input type="hidden" name="a" value="group_coupon"/>
						<input type="hidden" name="appoint_id" value="{pigcms{$_GET.appoint_id}"/>
						
						<div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
                            <font color="#000">{pigcms{:L_("核销人：")}</font>
							<input type="text" name="staff_name" class="input-text" value="{pigcms{$_GET['staff_name']}" style="width:200px;"/>
						</div>
						<div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
							<font color="#000">{pigcms{:L_("核销时间：")}</font>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;-&nbsp;			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						</div>

                        <div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
                            <font color="#000">{pigcms{:L_("核销方式")}</font>
                            <select name="verify_type">
                                <option value="0" <if condition="$_GET['verify_type'] eq 0">selected="selected"</if>>{pigcms{:L_("全部")}</option>
                                <option value="1" <if condition="$_GET['verify_type'] eq 1">selected="selected"</if>>{pigcms{:L_("移动端核销")}</option>
                                <option value="2" <if condition="$_GET['verify_type'] eq 2">selected="selected"</if>>{pigcms{:L_("pc端核销")}</option>
                            </select>
                        </div>
                        <div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
                            <font color="#000">{pigcms{:L_("状态")}</font>
                            <select name="status">
                                <option value="0" <if condition="$_GET['status'] eq 0">selected="selected"</if>>{pigcms{:L_("全部")}</option>
                                <option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>{pigcms{:L_("待核销")}</option>
                                <option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>{pigcms{:L_("已核销")}</option>
                                <option value="3" <if condition="$_GET['status'] eq 3">selected="selected"</if>>{pigcms{:L_("已过期")}</option>
                                <option value="4" <if condition="$_GET['status'] eq 4">selected="selected"</if>>{pigcms{:L_("已退款")}</option>
                            </select>
                        </div>

                        <div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
                            <select name="searchtype">
                                <option value="group_pass" <if condition="$_GET['searchtype'] eq 'group_pass'">selected="selected"</if>>{pigcms{:L_("卡券序列号")}</option>
                                <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("手机号")}</option>
                                <option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>{pigcms{:L_("用户呢称")}</option>
                                <option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>{pigcms{:L_("卡券订单号")}</option>
                            </select>
                            <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" style="width:200px;"/>
                        </div>
						<div style="float:left;margin-right:20px;margin-bottom:20px;height:32px;">
<!--                            <button class="btn btn-success" type="button" onclick="location.href='{pigcms{:U('Stroe/group_coupon')}'">重置</button>-->
                            <a class="btn btn-success" onclick="location.href='{pigcms{:U('group_coupon')}'" style="padding:2px 14px;">重置</a>
							<input type="submit" value="{pigcms{:L_('查询')}" class="btn btn-success" style="padding:2px 14px;"/>　
<!--							<a href="javascript:void(0)" onclick="exports('{pigcms{:L_('加载中,请耐心等待,数量越多时间越长。')}','{pigcms{:L_('若长时间未成功导出，建议调整筛选条件减少导出数，然后分多次导出')}')" class="down_excel" style="float:right;padding:8px 14px;border:1px solid #629b58;color:#629b58;">{pigcms{:L_("导出订单")}</a>-->
                            <a href="{pigcms{:U('group_coupon',$_GET)}&export=1" class="button" style="float:right;padding:8px 14px;border:1px solid #629b58;color:#629b58;">导出订单</a>
						</div>
					</form>
				</div>
				<div class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("券名称")}</th>
									<th>{pigcms{:L_("券序列码")}</th>
                                    <th>{pigcms{:L_("状态")}</th>
									<th>{pigcms{:L_("卡券订单号")}</th>
									<th>{pigcms{:L_("券类型")}</th>
									<th>{pigcms{:L_("用户昵称")}</th>
									<th>{pigcms{:L_("手机号")}</th>
									<th>{pigcms{:L_("核销时间")}</th>
									<th>{pigcms{:L_("核销方式")}</th>
									<th>{pigcms{:L_("核销人")}</th>
									<th class="button-column">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<volist name="order_list" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
										<td width="200">{pigcms{$vo.name}</td>
										<td width="100">{pigcms{$vo.group_pass}</td>
                                        <td width="50"><font style="color:{pigcms{$vo['status_color']}">{pigcms{$vo.status}</font></td>
<!--                                        <td width="50"><font color="$vo['status_color']">{pigcms{$vo.status}</font></td>-->
                                        <td width="100">{pigcms{$vo.real_orderid}</td>
                                        <td width="50">{pigcms{$config.group_alias_name}券</td>
                                        <td width="100">{pigcms{$vo.nickname}</td>
                                        <td width="50">{pigcms{$vo.phone}</td>
										<td width="100">
                                            <if condition="$vo['verify_time']">
                                                {pigcms{$vo.verify_time}<else/><div style="text-align: center">--</div>
                                            </if>
                                        </td>
                                        <td width="50">
                                            <if condition="$vo['verify_type']">
                                                {pigcms{$vo.verify_type}<else/><div style="text-align: center">--</div>
                                            </if>
                                        </td>
                                        <td width="150"><div style="text-align: center">
                                            <if condition="$vo['staff_name']">
                                                {pigcms{$vo.staff_name}<else/>--
                                            </if></div>
                                        </td>
										<td class="button-column" width="150">
                                            <if condition="$vo['can_verify']">
                                                <a title="{pigcms{:L_("查看券码详情")}"  data-title="{pigcms{:L_("券码详情")}" data-box_width="800px" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('Store/group_coupon_edit',array('order_id'=>$vo['order_id'],'id'=>$vo['id']))}">
                                                <i class="shortBtn">{pigcms{:L_("核销")}</i>
                                                </a><else/><div style="text-align: center">--</div>
                                            </if>
										</td>
									</tr>
								</volist>
							</tbody>
						</table>
					{pigcms{$pagebar}
					</div>
				</div>
			</div>
	</body>
	<script>
		function change_note(obj,order_id){
			var note_info = $(obj).siblings('input[name="note_info"]').val();
			$.post('{pigcms{:U("Store/change_group_order_note")}',{order_id:order_id,note_info:note_info},function(date){
				alert(date.msg)
				window.location.reload();
			},'json');
		}
	 var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('group_export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
</html>