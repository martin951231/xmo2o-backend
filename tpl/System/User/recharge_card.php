<include file="Public:header"/>
		<div class="mainbox">
			<include file='ecard_header' />
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('User/recharge_card')}" method="get">
							<input type="hidden" name="c" value="User"/>
							<input type="hidden" name="a" value="recharge_card"/>
							筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="card_id" <if condition="$_GET['searchtype'] eq 'card_id'">selected="selected"</if>>卡ID</option>
								<option value="card_name" <if condition="$_GET['searchtype'] eq 'card_name'">selected="selected"</if>>卡名</option>
								<option value="card_price" <if condition="$_GET['searchtype'] eq 'card_price'">selected="selected"</if>>面值</option>
							</select>
							<input type="submit" value="查询" class="button"/>
							<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/recharge_card_edit')}','添加',550,400,true,false,false,addbtn,'add',true);" style="margin-left:20px;" class="button">添加{pigcms{$config['recharge_card_alias_name']|default='充值卡'}</a>
						</form>

					</td>
				</tr>
			</table>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col/>
							<col width="180" align="center"/>
						</colgroup>
						<thead>
							<tr>
								<th>ID</th>
								<th>充值卡名称</th>
								<th>封面图</th>
								<th>面值</th>
								<th>当前库存/原始库存</th>
								<!-- <th class="textcenter">卡片数量</th> -->
								<th>操作人</th>
								<th >状态</th>
								<th class="textcenter">编辑</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>
										<td>{pigcms{$vo.card_id}</td>
										<td>{pigcms{$vo.card_name}</td>
										<td>
											<notempty name='vo.card_bg'>
												<img src="{pigcms{$vo.card_bg}" style="max-height:80px;padding:5px 0;" />
											</notempty>
										</td>
										<td>
											{pigcms{$config.Currency_symbol}{pigcms{$vo.card_price}
										</td>
										<td>
											<span style='width:80px;display:inline-block'>
												<gt name="vo.make_num" value='0'>
													<egt name='vo.use_num' value='$vo["card_num"]'>
														<span style='color:red;'>卡片不足</span>
													<else/>
														{pigcms{$vo['card_sku']-$vo['use_num']}
													</egt>/{pigcms{$vo.card_sku}
												<else/>
													<span style='color:red;'>暂未制卡</span>
												</gt>	
											</span>
											<a href="{pigcms{:U('User/recharge_card_coupon_list',array('card_id'=>$vo['card_id'],'make_status'=>0))}" class="button">管理</a>
										</td>
										<!-- <td  class="textcenter">{pigcms{$vo.card_num}</td> -->
										<td>{pigcms{$vo.realname}</td>
										<td class="textcenter" data-card_id='{pigcms{$vo.card_id}'>
											<span class="cb-enable">
												<label class="cb-enable <eq name='vo.status' value='1'>selected</eq>">
													<span>开启</span>
													<input type="radio" name="status" value="1"  >
												</label>
											</span>
											<span class="cb-disable">
												<label class="cb-disable <eq name='vo.status' value='0'>selected</eq>">
													<span>关闭</span>
													<input type="radio" name="status" value="0" <eq name='vo.status' value='0'>checked="checked"</eq> >
												</label>
											</span>
										</td>
										<td class="textcenter">
											<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/recharge_card_edit',array('card_id'=>$vo['card_id']))}','编辑',580,320,true,false,false,editbtn,'edit',true);">编辑</a>|
											<a href="{pigcms{:U('User/recharge_card_del',array('card_id'=>$vo['card_id']))}" class="del">删除</a>
										</td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="20">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="20">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script type="text/javascript">
function tip(msg,e){
	layer.alert(msg,{offset:'250px'},function(index){
		layer.close(index);
		typeof e=='function' && e();
	});
}
</script>
<script type="text/javascript">
$(function(){
	/*更新卡券的状态*/
	var src='{pigcms{:U("save_recharge_card_status")}';
	$('input[name="status"]').click(function(){
		var val=$(this).val();
		var card_id=$(this).parents('td').data('card_id');
		$.post(src,{card_id:card_id,status:val},function(res){
			console.log(res);
		});
	});
	$('.del').click(function(){
		var href=$(this).attr('href');
		layer.open({
			content:'确认删除！',
			btn:['确定','取消'],
			offset:'250px',
			yes:function(index){
				layer.close(index);
				$.get(href,function(res){
					tip(res.info);
					if(res.status){
						window.location.reload();
					}
				});
			}
		});
		return false;
	});
});
</script>
<include file="Public:footer"/>