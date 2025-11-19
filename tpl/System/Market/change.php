<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Market/change')}" frame="true" refresh="true">
		<input type="hidden" name="goods_id" value="{pigcms{$goods['goods_id']}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr>
                <th width="80">商品状态</th>
                <td>
                    <select name="status" class="valid">
                        <option value="0" <if condition="$goods['status'] eq 0">selected="selected"</if>>审核中</option>
                        <option value="1" <if condition="$goods['status'] eq 1">selected="selected"</if>>正常</option>
                        <option value="2" <if condition="$goods['status'] eq 2">selected="selected"</if>>不通过</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th width="80">排序值</th>
                <td>
                    <input type="number" class="input-text" name="sort" value="{pigcms{$goods['sort']}" placeholder="批发排序" validate="maxlength:6,number:true" tips="默认添加时间排序！手动排序数值越大，排序越前。">
                </td>
            </tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>