<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					
				
					<a href="{pigcms{:U('Village_group/activity')}" class="on">活动商品列表</a> 
					
				
				</ul>
			</div>
			<table class="search_table" width="100%">
				<tr>
					<td>
						<input type="button" value="添加商品" class="button" onclick="window.top.artiframe('{pigcms{:U('add_activity_goods',['activity_id'=>$activity_id])}','添加商品','80%','80%',true,false,false,addbtn,'add',true);"/>
						<input type="button" value="全选" onclick="checkAll();" class="button" />
						<input type="button" value="删除选中" onclick="delCheck();" class="button" />
					</td>
			
					<td>
						<form action="{pigcms{:U('Village_group/activity_goods')}" method="get">
							<input type="hidden" name="c" value="Village_group"/>
							<input type="hidden" name="a" value="activity_goods"/>
							<input type="hidden" name="activity_id" value="{pigcms{$activity_id}"/>
							筛选：<input type="text" name="keyword" class="input-text" placeholder="输入商品名称" value="{pigcms{$keyword}"/>
							<input type="submit" value="查询" class="button"/>
						</form>
					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<style>
					.table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
					</style>
					<table width="100%" cellspacing="0">
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
								<th>每人限购</th>
								<if condition="$activity_info['type'] eq 2">
								<th>抢购价</th>
								<th>活动开始前<em class="notice_tips" tips="设为等待开始则用户无法购买此商品"></em></th>
								<th>活动结束后</th>
								</if>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($data)">
								<volist name="data" id="vo">
									<tr>
										<td><input type="checkbox" name="goods_check[]" value="{pigcms{$vo.id}" /></td>
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
										<td><input type="text" class="input-text" size="5" name="buy_limit" data-id="{pigcms{$vo.id}" value="{pigcms{$vo.buy_limit}" /><em class="notice_tips" tips="设置为0时表示不限购"></em></td>
										<if condition="$activity_info['type'] eq 2">
										<td><input type="text" name="buy_price" data-id="{pigcms{$vo.id}" value="{pigcms{$vo.buy_price}" /></td>
										<td><label><input type="radio" name="before_activity[{pigcms{$vo.id}]" checked value="1" />等待开始</label></td>
										<td data-id="{pigcms{$vo.id}"><label class="checkThis"><input type="radio" value="1" name="after_activity[{pigcms{$vo.id}]" <if condition="$vo['after_activity'] eq '1'">checked</if> />原价购买</label> <label class="checkThis"><input type="radio" value="2" name="after_activity[{pigcms{$vo.id}]" <if condition="$vo['after_activity'] eq '2'">checked</if> />限时价购买</label></td>
										</if>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="<if condition="$activity_info['type'] eq 2">14<else />11</if>">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="<if condition="$activity_info['type'] eq 2">14<else />11</if>">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
		<script type="text/javascript" src="{pigcms{$static_public}layer/layer.js"></script>
		<script type="text/javascript">
			function checkAll(){
				$('[name="goods_check[]"]').each(function(){
					this.checked = true;
				});
			}

			function delCheck(){
				var ids = [];
				$('[name="goods_check[]"]').each(function(){
					if(this.checked){
						ids.push($(this).val());
					}
				});
				if(ids.length == 0){
					layer.msg("您没有选中任何商品！",{offset:['400px','45%']});
					return false;
				}
				layer.confirm('您确定要删除选中项吗？',{offset:['400px','45%']}, function(index){
				  	$.post("{pigcms{:U('del_activity_goods')}",{ids:ids.join(',')},function(data){
				  		if(data.status == 1){
				  			layer.msg(data.info,{offset:['400px','45%'],time:1000},function(){
				  				window.location.href = window.location.href;	
				  			});				  			
				  		}
				  		else{
				  			layer.msg(data.info);
				  		}
				  	},'json');
				  	layer.close(index);
				}); 
			}

			$(function(){
				$('[name="buy_limit"]').bind("change",function(){
					var _this = $(this);
					var id = $(this).data("id");
					var _val = $.trim($(this).val());
					$.post("{pigcms{:U('update_activity_goods_info')}",{id:id,val:_val,field:'buy_limit'},function(data){
						layer.tips(data.info,_this);
						return ;
					},'json');
				})
				$('[name="buy_price"]').bind("change",function(){
					var _this = $(this);
					var id = $(this).data("id");
					var _val = $.trim($(this).val());
					$.post("{pigcms{:U('update_activity_goods_info')}",{id:id,val:_val,field:'buy_price'},function(data){
						layer.tips(data.info,_this);
						return ;
					},'json');
				})
				$(".checkThis").find('[type="radio"]').bind("click",function(){
					var _this = $(this);
					var id = $(this).parents("td").data("id");
					var _val = $(this).val();
					$.post("{pigcms{:U('update_activity_goods_info')}",{id:id,val:_val,field:'after_activity'},function(data){
						layer.tips(data.info,_this);
						return ;
					},'json');
				})
			})
		</script>
<include file="Public:footer"/>