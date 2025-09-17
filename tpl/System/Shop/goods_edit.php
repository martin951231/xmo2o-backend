<include file="Public:header"/>
<form id="myform" method="post" action="{pigcms{:U('Shop/goods_amend')}" enctype="multipart/form-data">
		<input type="hidden" name="goods_id" value="{pigcms{$now_goods.goods_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">商品条形码</th>
				<td><input type="text" class="input fl" name="number" size="20" value="{pigcms{$now_goods.number}" placeholder="请输入商品条形码" validate="maxlength:20,required:true"/></td>
			</tr>
			<tr>
				<th width="80">商品名称</th>
				<td><input type="text" class="input fl" name="name" size="20" value="{pigcms{$now_goods.name}" placeholder="请输入商品名称" validate="maxlength:20,required:true"/></td>
			</tr>
			<tr>
				<th width="80">所属分类</th>
				<td>
					<select name="sort_id">
						<volist name="sort_list" id="sort">
						<option value="{pigcms{$sort['sort_id']}" <if condition="$now_goods['sort_id'] eq $sort['sort_id']">selected="selected"</if>>{pigcms{$sort['name']}</option>
						</volist>
					</select>
				</td>
			</tr>
			<tr>
				<th width="80">商品单位</th>
				<td><input type="text" class="input fl" name="unit" size="20" value="{pigcms{$now_goods.unit}" placeholder="请输入商品单位" validate="required:true"/></td>
			</tr>
			<tr>
				<th width="80">商品进价</th>
				<td><input type="text" class="input fl" name="cost_price" id="cost_price" value="{pigcms{$now_goods.cost_price}" style="width:120px;" placeholder="请输入商品的进价" tips=""/></td>
			</tr>
			<tr>
				<th width="80">零售价</th>
				<td><input type="text" class="input fl" name="price" id="price" value="{pigcms{$now_goods.price}" style="width:120px;" placeholder="请输入商品的零售价" tips=""/></td>
			</tr>

			<tr>
				<th width="80">商品现图</th>
				<td><img src="{pigcms{$now_goods.image}" style="width:80px;height:80px;" class="view_msg"/></td>
			</tr>
			
			<tr>
				<th width="80">商品图片</th>
				<td><input type="file" class="input fl" name="image" style="width:200px;" placeholder="请上传图片"/></td>
			</tr>

            <tr>
                <th width="80">供货价</th>
                <td><input type="text" class="input fl" name="supply_price" id="supply_price" value="{pigcms{$now_goods.supply_price}" style="width:120px;" placeholder="请输入供货价" tips=""/></td>

            </tr>

            <tr>
                <th width="80">商品编码</th>
                <td><input type="text" class="input fl" name="goods_code" id="goods_code" value="{pigcms{$now_goods.goods_code}" style="width:120px;" placeholder="请输入商品编码" tips=""/></td>
            </tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
    <if condition="$is_status eq 1">
    <div style="background-color: #fff;width: 100%;height: 40px;"></div>
    <form method="post" action="{pigcms{:U('Shop/goods_status')}">
        <input type="hidden" name="goods_id" value="{pigcms{$now_goods.goods_id}"/>
        <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr>
                <th width="80">审核状态*</th>
                <td><input type="radio" class="input" name="status" value="1" <if condition="$now_goods.status eq 1 or $now_goods.status eq 0">checked</if>/>&nbsp;成功&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" class="input" name="status" value="2" <if condition="$now_goods.status eq 2">checked</if> />&nbsp;失败</td>
            </tr>
            <tr>
                <th width="80">审核备注</th>
                <td><textarea class="input" name="status_content" style="min-height: 50px;min-width: 70%;">{pigcms{$now_goods.status_content}</textarea ></td>
            </tr>
        </table>
        <div class="btn">
            <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
            <input type="reset" value="取消" class="button" />
        </div>
    </form>
    </if>
<include file="Public:footer"/>