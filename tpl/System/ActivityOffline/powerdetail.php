<include file="Public:header"/>
        <div class="mainbox">
            <div id="nav" class="mainnav_title">
                <ul>
                    <a href="" class="on">权限管理</a>
                </ul>
            </div>
            <form name="myform" action="{pigcms{:U('ActivityOffline/powerdetail')}" method="post">
                <div class="table-list">
                    <table width="100%" cellspacing="0">
                        <colgroup>
                            <col/>
                            <col/>
                            <col/>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>分类
                                    <!-- <label><input type="checkbox" id="all">&nbsp;&nbsp;全选</label> -->
                                </th>
                                <th>编号</th>
                            </tr>
                        </thead>
                        <tbody>
                            <if condition="is_array($area_list)">
                                <volist name="area_list" id="vo">
                                    <tr>
                                        <td><label>
                                            <!-- <input type="checkbox" name="areaIds[]" id="check_{pigcms{$vo['area_id']}" data-id="{pigcms{$vo['area_id']}" <if condition="$vo['select']">checked</if> value="{pigcms{$vo['area_id']}"> -->
                                            &nbsp;&nbsp;{pigcms{$vo.area_name}
                                        </label>
                                    </td>
                                        <td>{pigcms{$vo.area_id}</td>
                                    </tr>
                                    <php> if ($vo['son_list']) {</php>
                                    <tbody class="son_{pigcms{$vo.area_id}">
                                    <volist name="vo['son_list']" id="son">
                                    <tr style="background: #f7f6f1">
                                        <td>
                                            <label>
                                                <!-- <input type="checkbox" name="areaIds[]" id="check_{pigcms{$son['area_id']}" data-id="{pigcms{$son['area_id']}" <if condition="$son['select']">checked</if> value="{pigcms{$vo['area_id']}-{pigcms{$son['area_id']}"> -->
                                            &nbsp;&nbsp;|--------{pigcms{$son.area_name}
                                        </label>
                                            </td>
                                        <td>{pigcms{$son.area_id}</td>
                                    </tr>
                                    <php> if ($ActivityOffline_cate_list) {</php>
	                                        <volist name="ActivityOffline_cate_list" id="last">
                                        <tbody class="son_{pigcms{$vo.area_id} son_{pigcms{$son.area_id} son_{pigcms{$last.id}">
	                                            <if condition="$last.city_id eq $son.area_id && ($last.fid eq 0)">
		                                            <tr>
		                                                <td>
		                                                	<label>
		                                                		<!-- <input type="checkbox" name="id[]" id="check_{pigcms{$last['province_id']}" data-id="{pigcms{$last['city_id']}" <if condition="in_array($last['id'],$ids)">checked</if> value="{pigcms{$last['id']}"> -->
                                                                &nbsp;&nbsp;|--------------------{pigcms{$last.cate_name}
		                                                	</label>
		                                                </td>
		                                                <td>{pigcms{$last.city_id}</td>
		                                            </tr>
		                                            	<if condition="is_array($two_category)">
			                                            <foreach name="two_category" item="two">
                                                        <tbody class="son_{pigcms{$vo.area_id} son_{pigcms{$son.area_id} ">

			                                            <if condition="$two['fid'] eq $last['id']">
			                                            <tr style="background: #e8e8e3">
			                                                <td>
			                                                	<label>
			                                                		<input type="checkbox" name="cate_id[]" id="check_{pigcms{$two['province_id']}" data-id="{pigcms{$two['city_id']}" <if condition="in_array($two['id'],$ids)">checked</if> value="{pigcms{$two['id']}">&nbsp;&nbsp;|---------------------------------{pigcms{$two.cate_name}
			                                                	</label>
			                                                </td>
			                                                <td>{pigcms{$last.city_id}</td>
			                                            </tr>
			                                            </if>
                                                        </tbody>
			                                        	</foreach>
			                                        	</if>
	                                            </if>
                                        </tbody>

	                                        </volist>
                                    <php> } </php>
                                    </volist>
                                    </tbody>
                                    <php> } </php>
                                </volist>
                                <input type="hidden" name="uid" value="{pigcms{$Think.get.uid}">
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
<script type="text/javascript">
// $(document).ready(function(){
//     $('#all').click(function(){
//         if ($(this).is(':checked')) {
//             $('input[type="checkbox"]').attr('checked', true);
//         } else {
//             $('input[type="checkbox"]').attr('checked', false);
//         }
//     });
//     $('input[type="checkbox"]').click(function(){
//         var id = $(this).data('id');
//         var idStr = $(this).val();
//         var idArr = idStr.split('-');
//         console.log(idArr)
//         if ($(this).is(':checked')) {
//             if (idArr.length > 1) {
//                 for (var i = 0; i < idArr.length - 1; i++) {
//                     $('#check_' + idArr[i]).attr('checked', true);
//                 }
//             }
//             $('.son_' + id).find('input[type="checkbox"]').attr('checked', true);
//         } else {
//             $('.son_' + id).find('input[type="checkbox"]').attr('checked', false);
//         }
//     });
// });
</script>
<include file="Public:footer"/>