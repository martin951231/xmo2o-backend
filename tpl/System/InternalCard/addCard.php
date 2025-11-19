<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('InternalCard/addCard')}" frame="true" refresh="true">
		<input type="hidden" name="lid" value="{pigcms{$leveldata['id']}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <tr>
                <td width="80">一卡通卡号：</td>
                <td>
                    <input type="text" class="input fl" name="card_id" value="" validate="required:true">
                </td>
            </tr>
            <tr>
                <td width="80">一卡通名称：</td>
                <td>
                    <select class="fl" name="card_name" id="" style="width:145px; margin-right: 15px;"  validate="required:true">
                        <option value="">--请选择一卡通名称--</option>
                        <volist name="card_names" id="card">
                            <option class="name_opt" value="{pigcms{$card['card_name']}">{pigcms{$card['card_name']}</option>
                        </volist>
                    </select>
                    <input type="text" class="input" id="name_search" onkeyup="filter_name()" placeholder="关键词搜索"/>
                </td>
            </tr>
            <tr>
                <td width="80">归属单位：</td>
                <td>
                    <select class="fl" name="unit_id" id="" style="width:145px; margin-right: 15px;"  validate="required:true">
                        <option value="">--请选择归属单位--</option>
                        <volist name="internal_unit" id="unit">
                            <option class="unit_opt" value="{pigcms{$unit['id']}">{pigcms{$unit['name']}</option>
                        </volist>
                    </select>
                    <input type="text" class="input" id="search" onkeyup="filter_units()" placeholder="关键词搜索"/>
                </td>
            </tr>
			<tr>
                <td width="80">用户姓名：</td>
                <td>
                    <input type="text" class="input fl" name="user_name" value="" validate="required:true">
                </td>
            </tr>
            <tr>
                <td width="80">手机号：</td>
                <td>
                    <input type="text" class="input fl" name="phone" value="" autocomplete="off" validate="required:true">
                </td>
            </tr>
			<tr>
                <td width="80">卡默认余额：</td>
                <td>
                    <input type="number" class="input fl" name="balance_money" value="0" autocomplete="off" validate="required:true">
                </td>
            </tr>
            <tr>
                <td width="80">一卡通密码：</td>
                <td>
                    <input type="password" class="input fl" name="password" value="" tips="请输入6位数字密码" autocomplete="off" maxlength="6" validate="required:true">
                </td>
            </tr>
		</table>
		<div class="btn hidden">
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
        function filter_name(){
            var s = $('#name_search').val();
            $('.name_opt').each(function(){
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

