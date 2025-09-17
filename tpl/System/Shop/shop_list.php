<include file="Public:header"/>
<style>
  .address {
      width: 100px;
      overflow: hidden;
      white-space: nowrap;
      text-overflow: ellipsis;
      display: block;
  }
</style>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<if condition="$_GET['parentid']">
						<a href="{pigcms{:U('Shop/index')}" <if condition="!$category">class="on"</if>>分类列表</a>|
						<if condition="$category">
							<a href="{pigcms{:U('Shop/cat_renovation',array('parentid'=>$category['cat_id']))}" class="on">{pigcms{$category.cat_name} - 页面装修 - 爆款推荐</a>|
						</if>
					<else/>
						<a href="{pigcms{:U('Shop/shop_list')}" class="on">{pigcms{$config['shop_alias_name']}店铺列表</a>
					</if>
				</ul>
			</div>
			<if condition="!$_GET['parentid']">
			<table class="search_table" width="100%">
				<tr>
					<td>
						<form action="{pigcms{:U('Shop/shop_list')}" method="get">
							<input type="hidden" name="c" value="Shop"/>
							<input type="hidden" name="a" value="shop_list"/>
							店铺名: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							&nbsp;&nbsp;&nbsp;&nbsp;店铺状态：<select name="status" id="store_status">
								<option value="0" <if condition="$_GET['status'] eq 0">selected="selected"</if>>正常</option>
								<option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>关闭</option>
							</select>
							&nbsp;&nbsp;&nbsp;&nbsp;筛选优选：<select name="preference_status" id="preference_status">
								<option value="0" <if condition="$_GET['preference_status'] eq 0">selected="selected"</if>>全部店铺</option>
								<option value="1" <if condition="$_GET['preference_status'] eq 1">selected="selected"</if>>优选店铺</option>
							</select>
							<input type="submit" value="查询" class="button"/>
                            <input type="button" value="导出" class="button" onclick="exports()"/>
						</form>
					</td>
				</tr>
			</table>
			</if>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th>商家名称</th>
								<th>店铺名称</th>
								<th>配送方式</th>
								<th>商家电话</th>
								<th>店铺电话</th>
								<th>营业时间</th>
                <th>店铺地址</th>
								<th>创建时间</th>
								<th>最后更新时间</th>
								<th>店铺排序</th>
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($order_list)">
								<volist name="order_list" id="vo">
									<tr>
										<td>{pigcms{$vo.merchant_name}</td>
										<td>{pigcms{$vo.store_name}</td>
										<td><if condition="$vo['deliver_type'] eq 0">{pigcms{$config['deliver_name']}<elseif condition="$vo['deliver_type'] eq 1" />商家配送<elseif condition="$vo['deliver_type'] eq 2" />自提<elseif condition="$vo['deliver_type'] eq 3" />{pigcms{$config['deliver_name']}或自提<elseif condition="$vo['deliver_type'] eq 4" />商家配送或自提</if></td>
										<td>{pigcms{$vo.merchant_phone}</td>
										<td>{pigcms{$vo.store_phone}</td>
										<td>
											<if condition="$vo['is_close']">
												<font color="red">{pigcms{$vo.time}</font>
											<else/>
												{pigcms{$vo.time}
											</if>
										</td>
                    <td title="{pigcms{$vo.store_address}"><span class="address">{pigcms{$vo.store_address}</span></td>
										<td><if condition="$vo.create_time gt 0" >{pigcms{$vo.create_time|date="Y-m-d H:i:s",###}</if></td>
										<td><if condition="$vo.last_time gt 0">{pigcms{$vo.last_time|date="Y-m-d H:i:s",###}<elseif condition="$vo.create_time gt 0" />{pigcms{$vo.create_time|date="Y-m-d H:i:s",###}</if></td>
										<td>{pigcms{$vo.sort}</td>
										<td class="textcenter"><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Shop/edit_sort',array('store_id' => $vo['store_id']))}','设置店铺',780,550,true,false,false,editbtn,'edit',true);">设置店铺</a></td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="14">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="14">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
		</div>
<script type="text/javascript">
    function exports() {
        var keyword = $('input[name=keyword]').val();
        var status = $('#store_status option:selected').val();
        var preference_status = $('#preference_status option:selected').val();
        var export_url = "{pigcms{:U('Shop/shop_list')}";
        export_url += '&keyword=' + keyword;
        export_url += '&status=' + status;
        export_url += '&preference_status=' + preference_status;
        export_url += '&download=1';
        window.open(export_url)
    }
</script>
<include file="Public:footer"/>
