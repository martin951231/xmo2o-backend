<include file="Public:header"/>
<form id="myform" method="post" action="{pigcms{:U('MerchantSpread/cueFieldAdd')}" frame="true" refresh="true">
    <input type="hidden" name="spread_type" value="{pigcms{$Think.get.type}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">名称</th>
            <td><input type="text" class="input fl" name="name" id="name" size="10" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
        </tr>
        <tr>
            <th width="80">显示排序</th>
            <td><input type="text" class="input fl" name="sort" value="0" size="10" placeholder="显示排序" validate="maxlength:6,required:true,number:true" tips="默认添加时间排序！手动排序数值越大，排序越前。"/></td>
        </tr>
        <tr>
            <th width="80">是否必填</th>
            <td>
                <span class="cb-enable"><label class="cb-enable  selected"><span>是</span><input name="iswrite" value="1" checked="checked" type="radio"></label></span>
                <span class="cb-disable" ><label class="cb-disable "><span>否</span><input name="iswrite" value="0" type="radio"></label></span>
                <img title="客户发布信息时决定此字段用户是否必须填写" class="tips_img" src="./tpl/System/Static/images/help.gif">
            </td>
        </tr>
        <tr>
            <th width="80">字段类型</th>
            <td>
                <select name="type" id='js-selectInput'>
                    <volist name="cueFieldTypes" id="v">
                        <option value="{pigcms{$key}">{pigcms{$v} </option>
                    </volist>
                </select>
            </td>
        </tr>
        <tr id="js-showSelect" style='display:none'>
            <th width="80">单选框选项值</th>
            <td>
                <textarea tips="一行一个，将通过单选项展现出来。" name="use_field" style="width:175px;height:54px;" class="input fl"></textarea>
            </td>
        </tr>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<script>
    $('#js-selectInput').change(function(){
        $('#js-showSelect').hide();
        if($(this).val() == '3'){
            $('#js-showSelect').show();
        }
    })
</script>
<include file="Public:footer"/>