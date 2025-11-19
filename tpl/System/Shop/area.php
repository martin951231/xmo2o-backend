<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Shop/discount',array('shop_type'=>$_GET['shop_type']))}">外卖优惠列表</a>|
					<a href="{pigcms{:U('Shop/area',array('shop_type'=>$_GET['shop_type'],'id'=>$_GET['id']))}" class="on">选择区域</a>
				</ul>
			</div>
			<form name="myform" action="{pigcms{:U('Shop/area', array('id' => $_GET['id']))}" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<colgroup>
							<col/>
						</colgroup>
						<thead>
							<tr>
                                <th><label><input type="checkbox" id="all">&nbsp;&nbsp;全选</label><input type="submit"  name="dosubmit" value="提交" class="button" /></th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($area_list)">
								<volist name="area_list" id="vo">
									<tr>
                                        <td><label><input type="checkbox" name="areaIds[]" id="check_{pigcms{$vo['area_id']}" data-id="{pigcms{$vo['area_id']}" <if condition="$vo['select']">checked</if> value="{pigcms{$vo['area_id']}">&nbsp;&nbsp;{pigcms{$vo.area_name}</label><notempty name="vo['son_list']">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="return sqzk({pigcms{$vo['area_id']},this);" href="#" tit="zhankai">展开 ↓</a></notempty></td>
									</tr>
                                    <php> if ($vo['son_list']) {</php>
                                    <tr class="son_{pigcms{$vo.area_id}" style="display:none;">
                                        <td>
                                        <table width="100%" cellspacing="0">
                                            <volist name="vo['son_list']" id="son">
                                            <tr style="background: #f7f6f1">
                                                <td><label><input type="checkbox" name="areaIds[]" id="check_{pigcms{$son['area_id']}" data-id="{pigcms{$son['area_id']}" <if condition="$son['select']">checked</if> value="{pigcms{$vo['area_id']}-{pigcms{$son['area_id']}">&nbsp;&nbsp;|--------{pigcms{$son.area_name}</label><notempty name="son['son_list']">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="return sqzk({pigcms{$son['area_id']},this);" href="#" tit="zhankai">展开 ↓<i class="fa fa-fw fa-angle-right text"></i></a></notempty></td>
                                            </tr>
                                            <php> if ($son['son_list']) {</php>
                                            <tr class="son_{pigcms{$son.area_id}" style="display:none;">
                                                <td>
                                                <table width="100%" cellspacing="0">
                                                    <volist name="son['son_list']" id="last">
                                                    <tr style="background: #e8e8e3">
                                                        <td><label><input type="checkbox" name="areaIds[]" id="check_{pigcms{$last['area_id']}" data-id="{pigcms{$last['area_id']}" <if condition="$last['select']">checked</if> value="{pigcms{$vo['area_id']}-{pigcms{$son['area_id']}-{pigcms{$last['area_id']}">&nbsp;&nbsp;|--------------------{pigcms{$last.area_name}</label><notempty name="last['son_list']">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="return sqzk({pigcms{$last['area_id']},this);" href="#" tit="zhankai">展开 ↓<i class="fa fa-fw fa-angle-right text"></i></a></notempty></td>
                                                        <td></td>
                                                    </tr>

                                                    <if condition="$last['son_list']">
                                                        <tr class="son_{pigcms{$last.area_id}" style="display:none;">
                                                            <td>
                                                                <table width="100%" cellspacing="0">
                                                                    <foreach name="last.son_list" item="street">
                                                                        <tr style="background: #e8e8e3">
                                                                            <td><label><input type="checkbox" name="areaIds[]" id="check_{pigcms{$street['area_id']}" data-id="{pigcms{$street['area_id']}" <if condition="$street['select']">checked</if> value="{pigcms{$vo['area_id']}-{pigcms{$son['area_id']}-{pigcms{$last['area_id']}-{pigcms{$street['area_id']}">&nbsp;&nbsp;|----------------------------------------{pigcms{$street.area_name}</label></td>
                                                                            <td></td>
                                                                        </tr>
                                                                    </foreach>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </if>





                                                    </volist>
                                                </table>
                                                </td>
                                            </tr>
                                            <php> } </php>
                                            </volist>
                                        </table>
                                        </td>
                                    </tr>
                                    <php> } </php>   
								</volist>
							<else/>
								<tr><td class="textcenter red" colspan="8">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
                <div class="btn">
                    <input type="submit"  name="dosubmit" value="提交" class="button" />
                </div>
				</div>
			</form>
		</div>
        <!-- <div style="width: 100%; height: 1000px;"></div> -->
<script type="text/javascript">
$(document).ready(function(){
    $('#all').click(function(){
        if ($(this).is(':checked')) {
            $('input[type="checkbox"]').attr('checked', true);
        } else {
            $('input[type="checkbox"]').attr('checked', false);
        }
    });
    $(".mainbox").height(10000);
    $('input[type="checkbox"]').click(function(){
        var id = $(this).data('id');
        var idStr = $(this).val();
        var idArr = idStr.split('-');
        
        if ($(this).is(':checked')) {
            if (idArr.length > 1) {
                for (var i = 0; i < idArr.length - 1; i++) {
                    $('#check_' + idArr[i]).attr('checked', true);
                }
            }
            $('.son_' + id).find('input[type="checkbox"]').attr('checked', true);
            // $('.son_' + id).show();
        } else {
            $('.son_' + id).find('input[type="checkbox"]').attr('checked', false);
            // $('.son_' + id).hide();
        }
    });



});
function sqzk(aid,_this){
    if($(_this).attr('tit') == 'shouqi'){
        $('.son_' + aid).hide();
        $(_this).text('展开 ↓');
        $(_this).attr('tit','zhankai');
    }
    else{
        $('.son_' + aid).show();   
        $(_this).text('收起 ↑');
        $(_this).attr('tit','shouqi');
    }
    return false;
}
</script>
<include file="Public:footer"/>