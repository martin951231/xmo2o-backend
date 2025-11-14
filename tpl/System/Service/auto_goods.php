<include file="Public:header"/>
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('Service/auto_goodsname')}">分类列表</a>|
			<a href="{pigcms{:U('Service/auto_goods',array('cid'=>$cid))}" class="on">{pigcms{$now_category.name} - 商品列表</a>|

			<if condition="$now_category['type'] eq 0">
				<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Service/auto_goods_add', array('cid'=>$cid))}','添加商品',580,450,true,false,false,addbtn,'add',true);">添加商品</a>
			<else/>
				<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Service/auto_store_add', array('cid'=>$cid))}','添加店铺',700,550,true,false,false,null,'add',true,function (argument) {
					window.location.reload();
				});">添加店铺</a>
			</if>
		</ul>
	</div>
	<form name="myform" id="myform" action="" method="post">
		<div class="table-list">
			<table width="100%" cellspacing="0">
				<thead>
					<tr>
						<if condition="$now_category['type'] eq 0">
						<th width="5%">排序</th>
						</if>
						<th width="5%">编号</th>

						<if condition="$now_category['type'] eq 0">
							<th width="15%">商品名称</th>
						<else/>
							<th width="15%">店铺名称</th>
							<th width="15%">所属商家</th>
							<th width="20%">店铺营业时间</th>
							<th width="20%">起送费</th>
							<th width="10%">商家到期时间</th>
						</if>
						<th width="10%" class="textcenter">操作</th>
					</tr>
				</thead>
				<tbody>
					<if condition="!empty($goods_list)">
						<volist name="goods_list" id="vo">
							<tr>
								<if condition="$now_category['type'] eq 0">
									<td>{pigcms{$vo.sort}</td>
								</if>
								<td>{pigcms{$vo.id}</td>
								<td>{pigcms{$vo.name}</td>

								<if condition="$now_category['type'] eq 1">
									<td>{pigcms{$vo.merchant_name}</td>
									<td>
										<if condition="empty($vo['open_time_list'])">
                                            数据未导入
                                        <else/>
                                            <select>
                                            <volist name="vo.open_time_list" id="ti">
                                            <option disabled="disabled"<if condition="$ti.current eq 1"> selected="selected"</if>>{pigcms{$ti.week_show}&nbsp;&nbsp;{pigcms{$ti.time_show}</option>
                                            </volist>
                                            </select>
                                        </if>
									</td>
									<td class="frame_form"  data-id="{pigcms{$vo.id}"  data-fee="{pigcms{$vo.delivery_fee}"><input class="input fl" type="text" name="delivery_fee" value="{pigcms{$vo.delivery_fee}" id="delivery_fee" placeholder="请输入起送费"></td>
									<td>
										<if condition="$vo['merchant_end_time'] eq 0">
											无限期
										<else />
											<if condition="$vo['merchant_end_time'] gt time()" >
												<span style="color: green;">{pigcms{$vo.merchant_end_time|date="Y-m-d H:i:s",###}</span>
											<else />
												<span style="color: red;">于{pigcms{$vo.merchant_end_time|date="Y-m-d H:i:s",###}到期</span>
											</if>
										</if>
									</td>
								</if>

								<td class="textcenter">
									<if condition="$now_category['type'] eq 0">
										<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Service/auto_goods_add',array('id'=>$vo['id']))}','编辑分类信息',580,450,true,false,false,editbtn,'add',true);">编辑</a> | 
									</if>
									<a href="javascript:void(0);" class="delete_row" tip="操作后会删除该内容且不能恢复！" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Service/auto_goods_del')}">删除</a>
								</td>
							</tr>
						</volist>
					<else/>
						<tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
					</if>
				</tbody>
			</table>
		</div>
	</form>
</div>
<script type="text/javascript">
	$('input[name=delivery_fee]').blur(function(){
		var _that = $(this);
		var delivery_fee = $(this).val();
		var id = $(this).parent('td').attr("data-id");
		var fee = $(this).parent('td').attr("data-fee");
		if (delivery_fee != fee) {
			var url = "{pigcms{:U('auto_goods_delivery_fee_set')}";
			$.post(url,{id,delivery_fee},function(res){
				_that.parent('td').attr("data-fee",delivery_fee)
				alert(res.info)
			})
		}
	})
</script>
<include file="Public:footer"/>