<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('User/addlevel')}" frame="true" refresh="true">
		<input type="hidden" name="lid" value="{pigcms{$leveldata['id']}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td width="80">等级名称：</td>
				<td>
				<input type="text" class="input fl" name="lname" value="{pigcms{$leveldata['lname']}" placeholder="请填写一个等级名" tips="如vip1，vip2等">
				&nbsp;&nbsp;&nbsp;<span class="red">例如填写1代表第一等级。</span>
				</td>
			</tr>
			<tr>
				<td width="80">等级级别：</td>
				<td>
				<span class="input fl" style="width: 140px;">{pigcms{$leveldata['level']}</span>
				&nbsp;&nbsp;&nbsp;<span class="red">例如填写1代表第一等级。</span>
				</td>
			</tr>
			<tr>
				<td width="80">等级{pigcms{$config['score_name']}：</td>
				<td>
				<input type="text" class="input fl" name="integral" value="{pigcms{$leveldata['integral']}" placeholder="请填写一个对应数字" onkeyup="value=value.replace(/[^1234567890]+/g,'')" tips="成为该等级会员所需要的{pigcms{$config['score_name']}数">
				&nbsp;&nbsp;&nbsp;<span class="red">客户想成为该等级会员所需要消耗的{pigcms{$config['score_name']}数</span>
				</td>
			</tr>
			<tr>
				<td width="80">等级余额：</td>
				<td>
				<input type="text" class="input fl" name="use_money" value="{pigcms{$leveldata['use_money']}" placeholder="请填写一个对应数字" onkeyup="value=value.replace(/[^1234567890]+/g,'')" tips="成为该等级会员所需要的余额">
				&nbsp;&nbsp;&nbsp;<span class="red">客户想成为该等级会员所需要消耗的余额，0为不使用</span>
				</td>
			</tr>
			<if condition="$config['level_balance']">
			<tr>
				<td width="80">等级有效期：</td>
				<td>
				<input type="text" class="input fl" name="validity" value="{pigcms{$leveldata['validity']}" placeholder="请填写一个对应天数" onkeyup="value=value.replace(/[^1234567890]+/g,'')" tips="等级持续有效期，单位/天">
				&nbsp;&nbsp;&nbsp;<span class="red">0 表示不限制 30 表示30天</span>
				</td>
			</tr>
			</if>
			
			<if condition="$config['spread_user_give_type'] eq 0 OR $config['spread_user_give_type'] eq 2">
			<tr>
				<td width="80">推荐用户奖励金额：</td>
				<td>
				<input type="text" class="input fl" name="spread_user_give_moeny" value="{pigcms{$leveldata['spread_user_give_moeny']}" placeholder="请填写金额数" onkeyup="value=value.replace(/[^1234567890]+/g,'')">
				&nbsp;&nbsp;&nbsp;<span class="red">0 表示不奖励 30 表示奖励30{pigcms{$config['Currency_txt']}余额</span>
				</td>
			</tr>
			</if>
			<if condition="$config['spread_user_give_type'] eq 1 OR $config['spread_user_give_type'] eq 2">
			<tr>
				<td width="80">推荐用户奖励{pigcms{$config['score_name']}：</td>
				<td>
				<input type="text" class="input fl" name="spread_user_give_score" value="{pigcms{$leveldata['spread_user_give_score']}" placeholder="请填写{pigcms{$config['score_name']}数" onkeyup="value=value.replace(/[^1234567890]+/g,'')">
				&nbsp;&nbsp;&nbsp;<span class="red">0 表示不奖励 30 表示奖励30{pigcms{$config['score_name']}</span>
				</td>
			</tr>
			</if>


            <tr>
                <td width="80">开通赠送{pigcms{$config['score_name']}：</td>
                <td>
                    <input type="text" class="input fl" name="open_give_score" value="{pigcms{$leveldata['open_give_score']}" placeholder="请填写{pigcms{$config['score_name']}数" onkeyup="value=value.replace(/[^1234567890]+/g,'')">
                    &nbsp;&nbsp;&nbsp;<span class="red">0 表示不奖励 30 表示奖励30{pigcms{$config['score_name']}</span>
                </td>
            </tr>
			
			<if condition="$config['score_clean_time'] neq ''">
			<tr>
				<td width="80">{pigcms{$config['score_name']}清理时间</td>
				<td>
				<input type="text" class="input fl" name="score_clean_time" value="{pigcms{$leveldata['score_clean_time']}"  onfocus="WdatePicker({readOnly:true,dateFmt:'MM-dd'})">
				&nbsp;&nbsp;&nbsp;<span class="red">设置后会在这一天清零积分，清零前一天会有微信模板消息提醒用户</span>
				</td>
			</tr>
			<tr>
				<td width="80">{pigcms{$config['score_name']}清理比例</td>
				<td>
				<input type="text" class="input fl" name="score_clean_percent" value="{pigcms{$leveldata['score_clean_percent']}" placeholder="请填写0-100的数字" onkeyup="value=value.replace(/[^1234567890]+/g,'')">
				&nbsp;&nbsp;&nbsp;<span class="red">0 表示不清理 100表示全部清理 30 清理30%</span>
				</td>
			</tr>
			</if>
			<tr>
				<td width="80">外卖提交页引导开通会员的一句话描述：</td>
				<td>
				<input type="text" class="input fl" name="sub_description" value="{pigcms{$leveldata['sub_description']}" placeholder="请填写描述" tips="">
				&nbsp;&nbsp;&nbsp;<span class="red">用于外卖提交页引导用户开通平台会员等级的描述。</span>
				</td>
			</tr>
            <tr>
                <td width="80">是否成为该等级后才可推广佣金</td>
                <td class="radio_box">
                    <span class="cb-enable"><label class="cb-enable <if condition="$leveldata['can_spread'] eq 1">selected</if>"><span>开启</span><input type="radio" name="can_spread" value="1"  <if condition="$leveldata['can_spread'] eq 1">checked="checked"</if>/></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$leveldata['can_spread'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="can_spread" value="0"  <if condition="$leveldata['can_spread'] eq 0">checked="checked"</if>/></label></span>
                </td>
            </tr>
			<tr>
				<td width="80">等级图标：</td>
				<td>
				    <input type="hidden" name="icon" value="{pigcms{$leveldata['icon']}"/>
					<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage">上传图片</a>
				    <img src="{pigcms{$leveldata['icon']|replace_file_domain=###}" width="50px" <if condition="!empty($leveldata['icon'])"> style="margin-left: 30px;"<else/>style="margin-left: 30px;display:none;"</if> />
				</td>
			</tr>
		   <tr>
				<td width="80">等级福利：</td>
				<td>优惠&nbsp;
				<select name="fltype">
				<option value="0">无</option>
				<option value="1" <if condition="$leveldata['type'] eq 1">selected="selected"</if>>百分比（%）</option>
				<option value="2" <if condition="$leveldata['type'] eq 2">selected="selected"</if>>立减</option>
				</select>
				&nbsp;&nbsp;&nbsp;
				 <input type="text" class="input" name="boon" value="{pigcms{$leveldata['boon']}" placeholder="请填写一个优惠值数字" onkeyup="value=value.replace(/[^1234567890]+/g,'')" tips="填写数字即可，无需%符号，填写50代表5折，0与100代表没有折扣。">
				</td>
			</tr>
			<tr>
				<td width="80">等级介绍：</td>
				<td><textarea name="description" rows="10" cols="40"  placeholder="写上一些等级介绍说明文字">{pigcms{$leveldata['description']|htmlspecialchars_decode=ENT_QUOTES|strip_tags=###}</textarea></td>
			</tr>
		   <tr>
            <tr>
                <th width="80" rowspan="3">佣金推广比例设置</th>
                <td style="width:240px;">
                    直接推广用户分享佣金比例：&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="text" class="input" name="first_share_money" id="first_share_money" value="{pigcms{$leveldata.first_share_money|default=0}" tips="直接推广用户用户开通会员等级获得佣金比例（百分比 0-100）"/>
                </td>
            </tr>
            <tr>
                <td style="width:240px;">
                    直接推广用户的上级分享佣金比例：&nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="text" class="input" name="second_share_money" id="second_share_money" value="{pigcms{$leveldata.second_share_money|default=0}" tips="直接推广用户的上级开通会员等级获得佣金比例（百分比 0-100）"/>
                </td>
            </tr>
            <tr>
                <td style="width:240px;">
                    直接推广用户的上上级分享佣金比例：&nbsp;
                    <input type="text" class="input" name="thild_share_money" id="thild_share_money" value="{pigcms{$leveldata.thild_share_money|default=0}" tips="直接推广用户的上上级开通会员等级获得佣金比例（百分比 0-100）"/>
                </td>
            </tr>
            <tr>


			<tr class="coupon">
				<th width="160">绑定优惠券</th>

				<td>
					<input type="button" value="添加优惠券" class="button" onclick="select_coupon('_again')" style="margin-left:0px;margin-right:10px;margin-bottom:10px;">
					<a>绑定优惠券后，用户可在开通会员等级后或者下单外卖提交页购买会员等级时获得相应优惠券。</a>
					<table style="width:800px;" class="table_form" id='goods_spec_again'>
						<thead>
						<tr >
							<th style="text-align: left;">ID</th>
							<th style="text-align: left;">满减条件</th>
							<th style="text-align: left;">有效时间</th>
							<th style="text-align: left;">使用平台</th>
							<th style="text-align: left;">使用类别</th>
							<th style="text-align: left;">使用分类</th>
							<th style="text-align: left;">操作</th>
						</tr>
						</thead>
						<tbody>
						<volist name="coupon_list_again" id="vo">
							<tr>
								<input type='hidden' name="coupon_ids_again[]" value="{pigcms{$vo.coupon_id}" />
								<td>{pigcms{$vo.coupon_id}</td>
								<td><php>if($vo['is_discount']==1){</php> 满 {pigcms{$vo.order_money|getFormatNumber=###} 打 {pigcms{$vo.discount_value|floatval}折  <php>}else{</php>满 {pigcms{$vo.order_money|getFormatNumber=###} 减 {pigcms{$vo.discount|getFormatNumber=###}<php>}</php></td>
								<td>{pigcms{$vo.start_time|date='Y-m-d',###} 到 {pigcms{$vo.end_time|date='Y-m-d',###}</td>
								<td><volist name="vo.platform" id="vv">{pigcms{$platform[$vv]}&nbsp;&nbsp;</volist></td>
								<td><if condition="$vo.cate_name eq 'all'">全部类别<else />{pigcms{$category[$vo['cate_name']]}</if></td>
								<td><if condition="$vo.cate_id eq '0'">全部分类<else />{pigcms{$vo['cate_id']}</if></td>
								<td>
									<button type="button" onClick="delThis(this,'_again')">移除</button>
								</td>
							</tr>
						</volist>
						</tbody>
					</table>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>


<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>


<script type="text/javascript">

KindEditor.ready(function(K){
	var editor = K.editor({
		allowFileManager : true
	});
	 //var islock=false;
	K('.J_selectImage').click(function(){
		var obj=$(this);
		editor.uploadJson = "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=system/image";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				imageUrl : K('#course_pic').val(),
				clickFn : function(url, title, width, height, border, align) {
					obj.siblings('input').val(url);
					editor.hideDialog();
					obj.siblings('img').attr('src',url).show();
					//window.location.reload();
				}
			});
		});
	   
	});

	kind_editor = K.create("#description",{
		width:'480px',
		height:'380px',
		minWidth:'480px',
		resizeType : 1,
		allowPreviewEmoticons:false,
		allowImageUpload : true,
		filterMode: true,
		items : [
			'source', '|', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
			'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
			'insertunorderedlist', '|', 'emoticons', 'image', 'link'
		],
		emoticonsPath : './static/emoticons/',
		uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=system/image"
	});
});

var _selected_coupon_ids = '{pigcms{$leveldata["coupon_ids"]}';
var _selected_coupon_ids_again = '{pigcms{$leveldata["coupon_ids_again"]}';
function select_coupon(type=''){
	var url = '';
	if (type) {
		url = '{pigcms{:U("select_coupon")}'+'&selected='+_selected_coupon_ids_again;
	}else{
		url = '{pigcms{:U("select_coupon")}'+'&selected='+_selected_coupon_ids;
	}
	layer.open({
		type: 2,
		area: ['90%', '550px'],
		fixed: false,
		offset:'10px',
		maxmin: true,
		content: url,
		btn: ['确定', '取消'],
		yes: function(index, layero){
			var frameId = $(layero).find("iframe").attr('id');
			var selected = $.trim($(window.frames[frameId].document).find('[name="selected"]').val());
			if (type) {
				_selected_coupon_ids_again = selected;
			}else{
				_selected_coupon_ids = selected;
			}
			$("#goods_spec"+type+" tbody").empty();
			console.log(selected)
			$.post("{pigcms{:U('Home/ajaxGetCoupon')}",{selected:selected},function(data){
				if(data.length > 0){
					var html = '';
					console.log(data)
					for(var i = 0; i< data.length; i++){
						console.log(i)
						html += '<tr>'+
							'<input type="hidden" name="coupon_ids'+type+'[]" value="'+data[i].coupon_id+'" />'+
							'<td>'+data[i].coupon_id+'</td>';

						if (data[i].is_discount == '1') {
							html += '<td>满'+data[i].order_money+'打'+data[i].discount_value+'折</td>';
						}else{
							html += '<td>满'+data[i].order_money+'减'+data[i].discount+'</td>';
						}
                        html += '<td>' + data[i].start_time + '至' + data[i].end_time + '</td>';
						html += 	'<td>'+data[i].platform_str+'</td>'+
							'<td>'+data[i].cate_name+'</td>'+
							'<td>'+data[i].cate_id+'</td>'+
							'<td>'+
							'<button type="button" onClick="delThis(this)">移除</button>'+
							'</td>'+
							'</tr>'
					}
					$("#goods_spec"+type+" tbody").append(html);
				}
			},'json');
			layer.close(index);
		},
		btn2: function(index){
			layer.close(index);
		}
	});
}

function delThis(t,type=''){
	if(confirm('{pigcms{:L_("你确定要移除此优惠券吗？")}')){
		var coupon_id = $(t).parents("tr").find('[name="coupon_ids'+type+'[]"]').val();
		var _selected_coupon_ids_arr = (_selected_coupon_ids+type).split(',');
		var _selected_coupon_ids_arr_new = [];
		for(var i = 0;i< _selected_coupon_ids_arr.length;i++){
			if(_selected_coupon_ids_arr[i] != coupon_id){
				_selected_coupon_ids_arr_new.push(_selected_coupon_ids_arr[i]);
			}
		}
		if (type) {
			_selected_coupon_ids_again = _selected_coupon_ids_arr_new.join(",");
		}else{
			_selected_coupon_ids = _selected_coupon_ids_arr_new.join(",");
		}
		$(t).parents("tr").eq(0).remove();
	}
}

$('select[name="fltype"]').change(function(e){
	if(e.target.value == 0){
		$('input[name="boon"]').val('0')
		$('input[name="boon"]').attr("disabled",true)
	}else{
		$('input[name="boon"]').attr("disabled",false)
	}
})
</script>
