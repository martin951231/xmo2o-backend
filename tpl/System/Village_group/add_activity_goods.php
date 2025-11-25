<include file="Public:header"/>
		<table class="search_table" width="100%">
			<tr>		
				<td>
					<form action="{pigcms{:U('add_activity_goods')}" method="get" name="searchForm">
						<input type="hidden" name="c" value="Village_group"/>
						<input type="hidden" name="a" value="add_activity_goods"/>
						<input type="hidden" name="page" value="{pigcms{$_GET['page']}" />
						<input type="hidden" name="goods_id" value="{pigcms{$goods_id}" id="search_goods" />
						<input type="hidden" name="activity_id" value="{pigcms{$activity_id}" />
						筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
						团购状态: <select name="searchstatus">
							<option value="-1" <if condition="$_GET['searchstatus'] eq -1">selected="selected"</if>>全部</option>
							<option value="1" <if condition="$_GET['searchstatus'] eq '1'">selected="selected"</if>>进行中</option>
							<option value="3" <if condition="$_GET['searchstatus'] eq '3'">selected="selected"</if>>未开始</option>
							<option value="2" <if condition="$_GET['searchstatus'] eq '2'">selected="selected"</if>>已售罄</option>
							<option value="4" <if condition="$_GET['searchstatus'] eq '4'">selected="selected"</if>>已过期</option>
						</select>
						<input type="submit" value="查询" class="button"/>
					</form>
				</td>
			</tr>
		</table>
		<div class="table-list">
			<style>
			.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
			</style>
			<table width="100%" cellspacing="0">
				<colgroup>
					<col/>
					<col/>
					<col/>
					<col/>
					<col/>
					<col/>
					<col/>
					<col/>
					<col/>
					<col/>
				</colgroup>
				<thead>
					<tr>
						<th>选择</th>
						<th>商家ID</th>
						<th>商品名称</th>
						<th>开始结束时间</th>
						<th>商品图片</th>
						<th>原始库存</th>
						<th>进价</th>
						<th>售价</th>
						<th>销量</th>
						<th>状态</th>
					</tr>
				</thead>
				<tbody>
					<if condition="is_array($data)">
						<volist name="data" id="vo">
							<tr>
								<td><input type="checkbox" name="goods_check[]" <if condition="$vo['checked'] eq '1'">checked="checked"</if> value="{pigcms{$vo.goods_id}" /></td>
								<td>{pigcms{$vo.mer_id}</td>
								<td>{pigcms{$vo.name}</td>
								<td>{pigcms{$vo.start_time|date='Y-m-d',###}<if condition="$vo['end_time'] gt 0">至{pigcms{$vo.end_time|date='Y-m-d',###}</if></td>
								<td><img src="{pigcms{$vo.image}" style="width:120px;height:80px;"></td>
								<td>{pigcms{$vo.stock_num}</td>
								<td>{pigcms{$vo.purchase_price}</td>
								<td>{pigcms{$vo.price}</td>
								<td>{pigcms{$vo.sell_count|floatval}</td>
								<td>
									<if condition="$vo['status'] eq 1 AND ($vo['start_time']) GT time() ">
											
										<font color="red">还未开始</font>
									
									<elseif condition="$vo['status'] eq 2 OR $vo['sell_count'] eq $vo['stock_num']"/>
										<font color="red">已售罄</font>
									<elseif condition="$vo['status'] eq 1" />
										进行中
									</if>
								
									<if condition="$vo['status'] eq 3">
										<font color="red">已过期</font>
									<elseif condition="$vo['status'] eq 5"/>
										<font color="green">已结束</font>
									
									</if>
								</td>
							</tr>
						</volist>
						<tr><td class="textcenter pagebar" colspan="10">{pigcms{$pagebar}</td></tr>
					<else/>
						<tr><td class="textcenter red" colspan="10">列表为空！</td></tr>
					</if>
				</tbody>
			</table>
		</div>
	<form id="myform" method="post" action="{pigcms{:U('add_activity_goods_submit')}" frame="true" refresh="true" enctype="multipart/form-data">
		<div class="btn hidden">
			<input type="hidden" name="goods_id" value="{pigcms{$goods_id}" id="submit_goods" />
			<input type="hidden" name="activity_id" value="{pigcms{$activity_id}" />
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
	<script type="text/javascript">
		$(function(){
			$('[name="goods_check[]"]').bind("click", function(){
				var goods = $.trim($('#submit_goods').val());
				var goods_arr = [];
				if(goods){
					goods_arr = goods.split(",");
				}
				if(this.checked){
					goods_arr.push($(this).val());
					$('#submit_goods').val(goods_arr.join(','));
					$('#search_goods').val(goods_arr.join(','));
				}
				else{
					var new_goods = [];
					for(var i = 0 ;i < goods_arr.length; i++){
						if(goods_arr[i] != $(this).val()){
							new_goods.push(goods_arr[i]);
						}
					}
					$('#submit_goods').val(new_goods.join(','));	
					$('#search_goods').val(new_goods.join(','));
				}
				searchForm.submit();
			})
		})
	</script>
<include file="Public:footer"/>