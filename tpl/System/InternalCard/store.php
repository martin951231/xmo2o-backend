<include file="Public:header"/>
<div class="mainbox">
	<table class="search_table" width="100%">
		<tr>
			<td style="width:60%;">
				<form action="{pigcms{:U('InternalCard/store', array('id' => $did))}" method="get">
					<input type="hidden" name="c" value="InternalCard"/>
					<input type="hidden" name="a" value="store"/>
                    <input type="hidden" name="name_id" value="{pigcms{$Think.get.name_id|default=0}"/>
					指定状态：
					<select name="status">
						<option value="0" <if condition="$_GET['status'] eq 0">selected="selected"</if>>全部</option>
						<option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>已指定</option>
						<option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>未指定</option>
					</select>&nbsp;&nbsp;&nbsp;&nbsp;

					区域：
					<div id="choose_pca" street_id="-1" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
					筛选：
					<select name="searchtype">
						<option value="store_name" <if condition="$_GET['searchtype'] eq 'store_name'">selected="selected"</if>>店铺名称</option>
						<option value="store_id" <if condition="$_GET['searchtype'] eq 'store_id'">selected="selected"</if>>店铺编号</option>
						<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>商家名称</option>
						<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>联系电话</option>
						<option value="mer_id" <if condition="$_GET['searchtype'] eq 'mer_id'">selected="selected"</if>>商家编号</option>
					</select>&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
					<input type="submit" value="查询" class="button"/>
				</form>
			</td>
		</tr>
	</table>
	<form name="myform" id="myform" action="" method="post">
		<div class="table-list">
			<table width="100%" cellspacing="0">
				<thead>
					<tr>
						<th><input type="checkbox" id="all"/></th>
						<th>编号</th>
						<th>商家名称</th>
						<th>店铺名称</th>
						<th>联系电话</th>
						<th>操作</th>
					</tr>
				</thead>
				<if>
					<if condition="is_array($storeList)">
						<volist name="storeList" id="vo">
							<tr>
								<td store_id="{pigcms{$vo.store_id}"><input type="checkbox" class="store_ids" value="{pigcms{$vo.store_id}" <if condition="!empty($vo['is_bind'])">checked</if>></td>
								<td>{pigcms{$vo.store_id}</td>
								<td>{pigcms{$vo.merchant_name}</td>
								<td>{pigcms{$vo.name}</td>
								<td>{pigcms{$vo.phone}</td>
								<td class="textcenter" data-id="{pigcms{$vo.mer_id}" data-store_id="{pigcms{$vo.store_id}">
                                    <if condition="$canEdit">
									<span class="cb-enable"><label class="cb-enable <if condition="!empty($vo['is_bind'])">selected</if>"><span>指定</span><input type="radio" name="selectMerchant{pigcms{$vo['store_id']}" value="1" <if condition="!empty($vo['is_bind'])">checked="checked"</if> /></label></span>
									<span class="cb-disable"><label class="cb-disable <if condition="empty($vo['is_bind'])">selected</if>"><span>解除指定</span><input type="radio" name="selectMerchant{pigcms{$vo['store_id']}" value="0" <if condition="empty($vo['is_bind'])">checked="checked"</if>/></label></span>
                                    </if>
                                </td>
							</tr>
						</volist>
                        <tr>
							<td class="textcenter pagebar" colspan="12">
								{pigcms{$pagebar}
							</td>
						</tr>
                        <if condition="$canEdit">
						<tr>
							<td class="textcenter pagebar" colspan="12">
								<input type="button" value="批量指定" id="online" class="button" style="float:left;margin-right:20px;margin-top:2px;"/>
								<input type="button" value="批量解除指定" id="offline" class="button" style="float:left;margin-top:2px;"/>
							</td>
						</tr>
                        </if>
					<else/>
						<tr><td class="textcenter red" colspan="12">列表为空！</td></tr>
					</if>
				</tbody>
			</table>
		</div>
	</form>
</div>
<script type="text/javascript">
$(document).ready(function(){
    var name_id = '{pigcms{$Think.get.name_id|default=0}';

    $('#all').click(function(){
        if ($(this).is(':checked')) {
            $('input[type="checkbox"]').attr('checked', true);
        } else {
            $('input[type="checkbox"]').attr('checked', false);
        }
    });

	
   
    $('input[type="radio"]').click(function(){
        var storeIds = [];
        storeIds.push($(this).parents('td').data('store_id'));
        var type = $(this).val();
		
        $.post('{pigcms{:U("InternalCard/store")}', {'name_id':name_id, 'type':type ==1?'bind':'unbind','store_ids':storeIds}, function(res){
            if (res.status == 1) {
				layer.msg(res.info,{offset:'10px',shift:6,time:2000});
                if (type == 1) {
                    obj.attr('checked', true);
                } else {
                    obj.attr('checked', false);
                }
            } else {
                alert(res.info);
            }
        }, 'json');
    });

    $('#online').click(function(){
		var storeIds = [];
        $("input[class=store_ids]:checked").each(function(){
            storeIds.push($(this).val());
        });

        $.post('{pigcms{:U("InternalCard/store")}', {'name_id':name_id, 'type':'bind','store_ids':storeIds}, function(res){
            if (res.status == 1) {
                location.reload();
            } else {
                alert(res.info);
            }
        }, 'json');
    });

    $('#offline').click(function(){
        var storeIds = [];
        $("input[class=store_ids]:checked").each(function(){
            storeIds.push($(this).val());
        });
        $.post('{pigcms{:U("InternalCard/store")}', {'name_id':name_id, 'type':'unbind','store_ids':storeIds}, function(res){
            if (res.status == 1) {
                location.reload();
            } else {
                alert(res.info);
            }
        }, 'json');
    });
	
});

</script>
<include file="Public:footer"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>