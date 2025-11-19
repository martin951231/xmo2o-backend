<include file="Public:header"/>
<style type="text/css">
    .required{
        color: #FF0000;
    }
</style>
<form id="myform" method="post" action="{pigcms{:U('MerchantSpread/edit')}"  enctype="multipart/form-data">
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="15%"><span class="required">*</span>推荐商家获得佣金比例</th>
            <td width="35%"><input type="text" class="input fl" name="commission" size="50" validate="min:0.01,required:true" value="{pigcms{$user.commission}" placeholder="请填写大于0的数字，最大保留2位小数"  tips=""/></td>
        </tr>
        <input type="hidden" name="uid" value="{pigcms{$user.uid}"/>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<include file="Public:footer"/>