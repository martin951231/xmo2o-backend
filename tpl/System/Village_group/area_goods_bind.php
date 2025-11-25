<include file="Public:header"/>
	<style type="text/css">
		.tags,.goods_tags{
			margin-top: 10px;
		}
		.tags .tag, .goods_tags .goods_tag{
		    display: inline-block;
		    padding: 2px 2px;
		    margin: 0px 5px 5px 0;
		    border: 1px solid #ccc;
		}
		.tags .tag .close, .goods_tags .goods_tag .goods_close {
		    display: inline-block;
		    padding: 0 4px;
		    color: #277fc1;
		    font-weight: bold;
		    cursor: pointer;
		}
	</style>
	<form id="myform" method="post" action="{pigcms{:U('area_goods_bind')}" frame="true" refresh="true">
		<input type="hidden" name="city_id" value="{pigcms{$_GET['city_id']}"/>
		<input type="hidden" name="pigcms_id" value="{pigcms{$_GET['pigcms_id']}"/>

		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th  width="120">选择商品</th>
				<td>
					<if condition="!empty($goods_detail['goods_id'])">
						{pigcms{$goods_detail.name}
					<else/>
						<button onclick="addGoods()" class="a_choose">添加商品</button>
						<div class="goods_tags" id="goods_tags" tabindex="1"> 
							<div class="goods_content"></div>
							<input type="hidden" class="px" id="goods_id" value="{pigcms{$goods_detail.goods_id}" name="goods_id" style="width:300px" >
						</div>
					</if>
				</td>
			</tr>

			<tr>
				<th width="160">适用社区</th>
				<td>
					<select name="buy_user_type" class="buy_user_type" style="width:150px;" >
						<option value="0" <if condition="$goods_detail['buy_user_type'] eq 0"> selected="selected"</if>>所有社区</option>
						<option value="1" <if condition="$goods_detail['buy_user_type'] eq 1"> selected="selected"</if>>部分社区</option>
						<option value="2" <if condition="$goods_detail['buy_user_type'] eq 2"> selected="selected"</if>>部分社区不参与</option>
					</select>
				</td>
			</tr>
			
			<tr class="address_list" <if condition="$goods_detail['buy_user_type'] eq 0">style="display:none;"</if>>
				<th  width="120">社区选择：</th>
				<td>
					<div style="float: left; border: 1px solid #ccc; padding: 2px 3px; cursor: pointer;" onclick="addFans()" class="a_choose class_1">添加社区</div>&nbsp;&nbsp;<a href="###" onclick="clearUsers()" class="a_clear class_1">清空重选</a>
					<div class="tags class_1" id="tags" tabindex="1"> 
						<if condition="!empty($village_list)">
							<volist name="village_list" id="vo">
								<span class='tag'>{pigcms{$vo.name}<a class='close' data-uid="{pigcms{$vo.id}">X</a></span><br>
							</volist>
						</if>
						<input id="form-field-tags" type="text" placeholder="请输入标签 ..." value="Tag Input Control" name="tags" style="display: none;"/>

						<!-- <input type="text"  class="tags_enter" autocomplete="off"> -->
						
						<input type="hidden" class="px" id="uids" value="{pigcms{$goods_detail.address_list}" name="address_list" style="width:300px" >
					</div>				
				</td>
			</tr>
			<tr>
				<th width="80">售价</th>
				<td><input type="text" class="input fl" name="price" value="{pigcms{$goods_detail.area_price}" validate="required:true" tips=""/></td>
			</tr>
			<tr>
				<th width="80">团长是否可以加价</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$goods_detail['is_add_price'] eq 1">selected</if>"><span>是</span><input type="radio" name="is_add_price" value="1" <if condition="$goods_detail['is_add_price'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable  <if condition="$goods_detail['is_add_price'] eq 0">selected</if>"><span>否</span><input type="radio" name="is_add_price" value="0"  <if condition="$goods_detail['is_add_price'] eq 0">checked="checked"</if>/></label></span>
					<p style="display:block;clear:both;padding-top:10px;"></p>
				</td>
			</tr>
			<tr class="price_interval"  <if condition="$goods_detail['is_add_price'] eq 0">style="display: none;"</if>>
				<th width="80">加价区间</th>
				<td>
					<input type="text" class="input" size="10" name="min_price" value="{pigcms{$goods_detail.min_price}" /> - 
					<input type="text" class="input" size="10" name="max_price" value="{pigcms{$goods_detail.max_price}" tips="团长可以在此区间内给商品加价出售"/>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
	<script type="text/javascript">
		function addFans(){
			art.dialog.data('titledom', 'titledom');
			art.dialog.data('imgids', 'imgids');
			art.dialog.data('multinews', 'multinews');
			art.dialog.data('singlenews', 'singlenews');
			art.dialog.data('js_appmsg_preview', 'js_appmsg_preview');
			art.dialog.data('multione', 'multione');
			art.dialog.open('?g=System&c=Village_group&a=select_village&area_id={pigcms{$area_info['area_id']}',{lock:true,title:'选择社区',width:600,height:400,yesText:'关闭',background: '#000',opacity: 0.45,top:'70%'});
		}
		function addGoods(){
			art.dialog.data('titledom', 'titledom');
			art.dialog.data('imgids', 'imgids');
			art.dialog.data('multinews', 'multinews');
			art.dialog.data('singlenews', 'singlenews');
			art.dialog.data('js_appmsg_preview', 'js_appmsg_preview');
			art.dialog.data('multione', 'multione');
			art.dialog.open('?g=System&c=Village_group&a=select_goods',{lock:true,title:'选择商品',width:600,height:400,yesText:'关闭',background: '#000',opacity: 0.45});
		}
		function clearUsers(){
			$('.tag').remove();
			$('#uids').val('');
		}

		$(".close").live("click", function() {
			$(this).parent(".tag").remove();
			var delete_name = $(this).data('uid');
			var userlist = $('#uids').val();
			userlistArr = userlist.split(',');
			for (var i = 0; i < userlistArr.length; i++) {
				if(userlistArr[i] == delete_name){
					userlistArr.splice(i, 1);
				}
			}
			userlist = userlistArr.join(',')
			$('#uids').val(userlist.replace(delete_name,''))
			
		});

		$('input[name=is_add_price]').click(function(){
			console.log($('input[name=is_add_price]:checked').val())
			if($('input[name=is_add_price]:checked').val()=='1'){
				$('.price_interval').show();
			}else{
				$('.price_interval').hide();
			}
		});	

		$('.buy_user_type').click(function(){
			var sub = $(this);
			
			if(sub.val()==0){
				$('.address_list').hide();
			}else{
				$('.address_list').show();
			}
		});
			
		$('.buy_user_type').trigger('click');
		
		$(".tags_enter").blur(function() { //焦点失去触发 
			var txtvalue=$(this).val().trim();
			if(txtvalue!=''){
				addTag($(this));
				$(this).parents(".tags").css({"border-color": "#d5d5d5"})
			}
		}).keydown(function(event) {
			var key_code = event.keyCode;
			var txtvalue=$(this).val().trim(); 
			if (key_code == 13&& txtvalue != '') { //enter
				addTag($(this));
			}
			if (key_code == 32 && txtvalue!='') { //space
				addTag($(this));
			}
		});

		$(".goods_close").live("click", function() {
			$(this).parent(".goods_tag").remove();
			$('#goods_id').val('');
		});
	</script>
<include file="Public:footer"/>