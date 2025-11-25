<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('add_village')}" frame="true" refresh="true" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">社区名称</th>
				<td><input type="text" class="input fl" name="name" id="name" size="25" value="{pigcms{$village.name}" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
			
				
			<tr>
				<th width="80">社区经纬度</th>
				<td id="choose_map"  default_long_lat="{pigcms{$village.long|default=''}<if condition="$village['long']">,</if>{pigcms{$village.lat|default=''}"></td>
			</tr>
			
			<tr >
				<th width="15%">所在区域</th>
				<td id="choose_cityarea" province_id="{pigcms{$village.province_id}" city_id="{pigcms{$village.city_id}" area_id="{pigcms{$village.area_id}" circle_id="-1" circle="-1"></td>
			</tr>
			
			<tr>
				<th width="80">详细地址</th>
				<td><input type="text" class="input fl" name="pick_addr" id="pick_addr" size="25" value="{pigcms{$village.pick_addr}" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
			</tr>
		
			
			<tr>
				<th width="80">绑定团长</th>
				<td>
					<input type="text" class="input fl" name="header_uid" id="header_uid" size="25" value="" placeholder="" validate="maxlength:20" readonly="" />
					<a href="javascript:void(0)" class="button" onclick="select_header()">选择团长</a>
					<!-- <a href="javascript:void(0)" class="button" id="search">搜索</a> 请输入团长ID,在团长列表中查看 -->
					
					<input type="hidden" name="header_uid" id="header_id" value="{pigcms{$village.header_uid}">
					<input type="hidden" name="id" id="id" value="{pigcms{$_GET.id}">
				</td>
			</tr>
		
			<tr id="product_detail" <if condition="empty($village['header'])">style="display:none;"</if>>
				<th width="80">团长详情</th>
				<td id="product_detail_td">
					<if condition="$village.header">
						团长名称：{pigcms{$village.header.name}<br>
						团长电话：{pigcms{$village.header.phone}
					</if>
				
				</td>
			</tr>
			
	
			
            <tr>
				<th width="80">社区状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$village.status eq 1 OR empty($village)">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$village.status eq 1 OR empty($village)">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$village['status'] eq 0 AND isset($village['status'])">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$village['status'] eq 0 AND isset($village['status'])">checked="checked"</if>/></label></span>
                </td>
			</tr>

            <if condition="$config['village_group_delivery_switch'] eq 1">
                <tr>
                    <th colspan="2" style="color: red;">配送相关配置信息</th>
                </tr>
                <tr>
                    <th width="80">服务范围</th>
                    <td><input type="text" class="input fl" name="delivery_radius" value="{pigcms{$village.delivery_radius|floatval}" id="reduce_money" size="10" tips="以社区为起点的半径距离(单位：㎞)"/></td>
                </tr>
                <tr>
                    <th width="90">配送时长</th>
                    <td><input type="text" class="input fl" name="s_send_time" value="{pigcms{$village.s_send_time|intval}" size="10" tips="配送员从团长取货至送达用户所需要的时间(单位：分钟)"/></td>
                </tr>

<!--                <tr>-->
<!--                    <th width="90">起送价</th>-->
<!--                    <td><input type="text" class="input fl" name="s_basic_price" value="{pigcms{$village.s_basic_price|floatval}" size="10" tips="每单达到这个价格才给予配送，设置为0和0以上生效，设置-1寻找上级设置"/></td>-->
<!--                </tr>-->
<!---->
<!--                <tr>-->
<!--                    <th width="90">加价送</th>-->
<!--                    <td><input type="text" class="input fl" name="s_extra_price" value="{pigcms{$village.s_extra_price|floatval}" size="10" tips="当订单的价格没有达到起送价的时候加价也可以配送"/></td>-->
<!--                </tr>-->

            </if>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	<script type="text/javascript">
		KindEditor.ready(function(K){
				var site_url = "{pigcms{$config.site_url}";
				var editor = K.editor({
					allowFileManager : true
				});
				$('.J_selectImage').click(function(){
					var upload_file_btn = $(this);
					editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
					editor.loadPlugin('image', function(){
						editor.plugin.imageDialog({
							showRemote : false,
							clickFn : function(url, title, width, height, border, align) {
								upload_file_btn.siblings('.input-image').val(site_url+url);
								editor.hideDialog();
							}
						});
					});
				});

			});
	</script>
	
	<script>
		$('#search').click(function(){
			if($('#header_uid').val()!=''){
					
				$.post("{pigcms{:U('ajax_get_header')}",{w:$('#header_uid').val()},function(data){
					if(data.status == 0){
						parent.msg(0,data.info);
					}else{
						var html = '团长名称：'+data.info.name;
							html+= '<br/>团长电话：'+data.info.phone;
							
						$('#product_detail_td').html(html);
						$('#product_detail').show();
						$('#header_id').val(data.info.id);
					}
				});
			}else{
				$('#product_detail_td').html('');
				$('#product_detail').hide();
				$('#header_uid').val('');
			}
		});		
	</script>
	<script type="text/javascript">
		function select_header(){
			art.dialog.data('titledom', 'titledom');
			art.dialog.data('imgids', 'imgids');
			art.dialog.data('multinews', 'multinews');
			art.dialog.data('singlenews', 'singlenews');
			art.dialog.data('js_appmsg_preview', 'js_appmsg_preview');
			art.dialog.data('multione', 'multione');
			art.dialog.open('?g=System&c=Village_group&a=select_header_list',{lock:true,title:'选择团长',width:600,height:400,yesText:'关闭',background: '#000',opacity: 0.45});
		}
	</script>
<include file="Public:footer"/>