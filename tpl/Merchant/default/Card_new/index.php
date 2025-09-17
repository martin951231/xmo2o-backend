<include file="Public:header"/>
<style>
	.layer-iframe{
		white-space: nowrap;
	}
</style>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-credit-card"></i>
				<a href="{pigcms{:U('Card_new/index')}">{pigcms{:L_("会员卡")}</a>
			</li>
			<li class="active">{pigcms{:L_("会员卡用户列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">


					<div class="row" style="margin: 5px; 10px">


						<a class="btn btn-success" href="{pigcms{:U('Card_new/card_edit')}" >{pigcms{:L_("编辑会员卡")}</a>
						
						<button class="btn btn-success layer-iframe" layer-iframe-type="noTop" data-title="{pigcms{:L_("新增会员")}" href="{pigcms{:U('Card_new/add_user')}" >{pigcms{:L_("新增会员")}</button>

						<button class="btn btn-success layer-iframe" layer-iframe-type="noTop" data-title="{pigcms{:L_("批量新增")}" href="{pigcms{:U('Card_new/import_user')}" >{pigcms{:L_("批量新增")}</button>
						<a class="btn btn-success" href="{pigcms{:U('Card_new/card_new_coupon')}" >{pigcms{:L_("优惠券列表")}</a>

						<a class="btn btn-success" href="{pigcms{:U('Card_new/card_group')}" >{pigcms{:L_("会员卡分组")}</a>
						<a class="btn btn-success" href="{pigcms{:U('Card_new/recharge_list')}" >{pigcms{:L_("会员卡消费记录")}</a>

						<if condition="C('customization.open_merchant_wxapp_score_plugin') eq 1">
							<a class="btn btn-success" href="{pigcms{:U('Card_new/wx_circleintegral')}">{pigcms{:L_("微信商圈积分记录")}</a>
							<a class="btn btn-success" target="_parent" href="/v20/public/platform/#/merchant/card.merchant/pointGoods">{pigcms{:L_("积分商品")}</a>
						</if>
						<if condition="C('customization.cash_back') eq 1">
						   <a class="btn btn-success" target="_parent" href="/v20/public/platform/#/merchant/merchant.card/cashBackList">{pigcms{:L_("优惠买单返还记录")}</a>
                        </if>
						<if condition="C('customization.deposita') eq 1">
						   <a class="btn btn-success" target="_parent" href="/v20/public/platform/#/merchant/merchant.card/goodsList">{pigcms{:L_("会员卡商品库")}</a>
						</if>
						<button class="btn btn-success"  href="javascript:void(0)" id="change_group"  >{pigcms{:L_("批量修改分组")}</button>
					</div>


					<div class="row" style="margin-top: 20px;">


							<form action="{pigcms{:U('Card_new/index')}" method="get" style="float:left;margin-left:10px">
								<input type="hidden" name="c" value="Card_new"/>
								<input type="hidden" name="a" value="index"/>
							{pigcms{:L_("筛选")}: 
								<select name="searchtype">
									<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("手机号码")}</option>
									<option value="card_id" <if condition="$_GET['searchtype'] eq 'card_id'">selected="selected"</if>>{pigcms{:L_("会员卡号")}</option>
									<option value="physical_id" <if condition="$_GET['searchtype'] eq 'physical_id'">selected="selected"</if>>{pigcms{:L_("实体卡号")}</option>
									<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>{pigcms{:L_("微信昵称")}</option>
								</select>
								<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
								&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								{pigcms{:$diy_select}
							{pigcms{:L_("开卡时间")}:
							<input type="text" class="input fl" name="begin_time" id="d4311"  value="{pigcms{$_GET['begin_time']}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;
							<input type="text" class="input fl" name="end_time" id="d4312" value="{pigcms{$_GET['end_time']}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>&nbsp;&nbsp;&nbsp;

								<button class="btn" >{pigcms{:L_("查询")}</button>&nbsp;&nbsp;&nbsp;&nbsp;
								
							
							</form>
                            <button class="btn"  href="javascript:void(0)" onclick="exports()"  >{pigcms{:L_("导出会员")}</button>
                            

							


					</div>

					<div class="row" style="margin-top: 20px;">

					<div style="margin-left: 10px;">
								<label >用户商家会员卡可用余额总额：<span style="color: green">{pigcms{$total_money}</span></label>
								<label style="margin-left: 25px;" >用户商家会员卡禁用余额总额：<span style="color: red">{pigcms{$stop_total_money}</span></label>
							</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
                                    <th width="20"><input type="checkbox" id="checkAll"></th>
									<th width="120">{pigcms{:L_("会员ID")}</th>
									<th width="120">{pigcms{:L_("微信卡号")}</th>
									<th width="120">{pigcms{:L_("微信昵称")}</th>
									<th width="120">{pigcms{:L_("姓名")}</th>
									<th width="80">{pigcms{:L_("性别")}</th>
									<th width="120">{pigcms{:L_("用户生日")}</th>
									<th width="120">{pigcms{:L_("用户手机")}</th>
									<th width="240" class="am-text-middle">{pigcms{:L_("会员卡余额")}</th>
									<th width="120" class="am-text-middle">{pigcms{:L_("会员卡")}{pigcms{$config['score_name']}</th>
									<th width="120">{pigcms{:L_("实体卡号")}</th>
									<th width="120">{pigcms{:L_(用户分组)}</th>
									<th width="160" class="am-text-middle">{pigcms{:L_("领卡时间")}</th>
									<th width="120">{pigcms{:L_("会员卡状态")}</th>
									<th width="120">{pigcms{:L_("备注")}</th>
									<th width="80" class="am-text-middle">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$card_user_list">
									<volist name="card_user_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><input type="checkbox" name="ids[]" value="{pigcms{$vo.id}"></td>
											<td>{pigcms{$vo.id}</td>
											<td><if condition="$vo.wx_card_code neq ''">{pigcms{$vo.wx_card_code}<else /></if></td>
											<td><if condition="$vo['uid'] gt 0">{pigcms{$vo.nickname}<else /><a href="{pigcms{:U('Card_new/see_qrcode',array('id'=>$vo['id']))}" data-id="{pigcms{$vo.id}" class="see_qrcode">{pigcms{:L_("查看二维码(用户微信扫描后绑定")})</a></if></td>
											<td>{pigcms{$vo.name}</td>
											<td><if condition="$vo['sex'] eq 1">男<elseif condition="$vo['sex'] eq 2"/>女<else/>未知</if></td>
											<td><if condition="$vo['birthday'] neq '0000-00-00'">{pigcms{$vo.birthday}</if></td>
											<td>{pigcms{$vo.phone}</td>
											<td class="am-text-middle">
												<div>{pigcms{$vo['card_money']+$vo['card_money_give']}</div>
												<span style="color: red;">{pigcms{:L_("（非自充余额：")}{pigcms{$vo['card_money_give']}{pigcms{:L_("）")}</span>
												</td>
											<td class="am-text-middle">{pigcms{$vo.card_score}</td>
											<td>{pigcms{$vo.physical_id}</td>
											<td>{pigcms{$vo['group_name']}</td>
											<td class="am-text-middle"><if condition="$vo.add_time neq 0">{pigcms{$vo['card_add_time']|date="Y-m-d H:i:s",###}</if></td>
											<td><if condition="$vo.card_status eq '1'"><font color="green">{pigcms{:L_("正常")}</font><else /><font color="red">{pigcms{:L_("禁止")}</font></if></td>
											<td>{pigcms{$vo.remark}</td>
											<td class="am-text-middle">
												<div class="tpl-table-black-operation">
													<a class="layer-iframe" layer-iframe-type="noTop" href="{pigcms{:U('Card_new/card_detail',array('id'=>$vo['id'],'uid'=>$vo['uid']))}">{pigcms{:L_("查看详情")}</a>
												</div>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="11" >{pigcms{:L_("无内容")}</td></tr>
								</if>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>

					</div>
					
					
					
				</div>
			</div>
		</div>
	</div>
</div>
<script>
var cardid='';
var group_arr = {pigcms{$card_group_new};
console.log(group_arr);
function tip(msg,e){
    layer.alert(msg,{offset:'250px'},function(index){
        layer.close(index);
        typeof e=='function' && e();
    });
}
$(function(){
	$('.see_qrcode').click(function(){
		layer.open({
			title:'{pigcms{:L_("查看渠道二维码")}',
			area:['430px','483px'],
			type: 2,
			shade: 0.4,
			content:$(this).attr('href')
		});
		return false;
	});

	 var time = setInterval(function(){
		if(cardid!=''){
			$.post("{pigcms{:U('Card_new/ajax_get_bind_status')}", {cardid:cardid }, function(data, textStatus, xhr) {
				data = eval('('+data+')');
				if(data.error_code==0){
					cardid='';
					window.location.reload();
				}
			});

		}
	},1000);


	$('#group_id').change(function(){
		$('#frmselect').submit();
	});

    $("#checkAll").click(function(){
        $('[name="ids[]"]:checkbox').prop("checked", $(this).prop("checked"));
    });
    $("#change_group").click(function (){
        var ids=[];
        $('input[name="ids[]"]:checked').each(function(){
            ids.push($(this).val());
        });
        if(ids.length==0){
            tip('至少选择一个会员！');
            return false;
        }
        console.log(ids);
        if(group_arr == ''||group_arr.length<=0){
            tip('请先添加分组！');
            layer.close(index);
            return false;
        }
        var _html = '用户分组：<select id="card_group" style="padding-right:2rem">';
        for (var i=0;i<group_arr.length;i++){
            _html += '<option value="'+group_arr[i].id+'">'+group_arr[i].name+'</option>'
        }
        _html += '</select>';
        layer.open({
            title:'{pigcms{:L_("设置分组")}',
            content:_html,
            area:['300px', '180px'],
            btn:['确认','取消'],
            offset:'15%',
            yes:function(index){
                var group_id = $('#card_group').val();
                $.post("{pigcms{:U('Card_new/changeCradGroup')}",{ids:ids,group_id:group_id},function(res){
                    console.log(res);
                    if(res.error_code){
                        alert(res.msg);
                    }else {
                        alert(res.msg);
                        window.location.reload();
                    }
                });
            },
            no:function(index){
                typeof e2=='function' && e2();
            }
        });
    })
});
var url = "{pigcms{$config.site_url}"
var export_url = "{pigcms{:U('Card_new/export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>




<include file="Public:footer"/>
