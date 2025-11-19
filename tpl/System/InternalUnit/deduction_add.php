<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('InternalUnit/deductionAdd')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr>
                <th width="30%">归属单位：</th>
                <td width="70%">
                    <select name="unit_id" style="width:310px; height: 30px">
                        <option value="">--请选择归属单位--</option>
                        <volist name="unit_list" id="vo">
                            <option class="unit_opt" value="{pigcms{$vo.id}" <if condition="$unit_id==$vo['id']">selected</if>>{pigcms{$vo.name}</option>
                        </volist>
                    </select>
                    <input type="text" class="input" id="search" onkeyup="filter_units()" placeholder="关键词搜索"/><br/>
                </td>
            </tr>
            <tr>
                <th width="30%">支出时间：</th>
                <td width="70%">
                    <input type="text" class="input" name="use_time" style="width:300px;" id="d4311"  value="" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                </td>
            </tr>
            <tr>
                <th width="30%">支出金额：</th>
                <td width="70%">
                    <input type="text" class="input" name="money" style="width:300px;"  value="" maxlength="11"/>
                </td>
            </tr>
            <tr>
                <th width="30%">备注：</th>
                <td width="70%">
                    <textarea style="width:300px;" maxlength="150" name="remark" rows="10"></textarea>
                </td>
            </tr>
		</table>
		<div class="btn hidden">
            <input type="hidden" name="id" value="{pigcms{$info['id'] ?? ''}">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
    
    <script type='text/javascript'>
        function filter_units(){
            var s = $('#search').val();
            $('.unit_opt').each(function(){
                var unit_name = $(this).html().trim();
                if(unit_name.indexOf(s) === -1){
                    $(this).hide();
                }else{
                    $(this).show();
                }
            })
        }
    </script>
<include file="Public:footer"/>
