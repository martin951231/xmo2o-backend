<include file="Public:header"/>
<form id="myform" method="post" action="{pigcms{:U('mobileAmend')}" frame="true" refresh="true" autocomplete="off">
    <input type="hidden" name="pack_id" value="{pigcms{$_GET['pack_id']}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">APP名称</th>
            <td><input type="text" class="input fl" name="name" size="40" value="{pigcms{$info['name']}" validate="required:true" tips="请填写APP名称"/></td>
        </tr>
        <tr>
            <th width="80">APP类型</th>
            <td>
                <select name="type">
                    <option value="1" <if condition="$info['type'] eq 1">selected="selected"</if>>平台APP</option>
                    <option value="2" <if condition="$info['type'] eq 2">selected="selected"</if>>社区APP</option>
                    <option value="3" <if condition="$info['type'] eq 3">selected="selected"</if>>跑腿APP</option>
                </select>
            </td>
        </tr>
        <tr>
            <th width="80">Android包名</th>
            <td><input type="text" class="input fl" name="package_name" size="40" value="{pigcms{$info['package_name']}" tips="请填写Android包名"/></td>
        </tr>
        <tr>
            <th width="80">IOS包名</th>
            <td><input type="text" class="input fl" name="package_name2" size="40" value="{pigcms{$info['package_name2']}" tips="请填写IOS包名"/></td>
        </tr>
        <tr>
            <th width="80">极光推送AppKey</th>
            <td><input type="text" class="input fl" name="jpush_appkey" size="40" value="{pigcms{$info['jpush_appkey']}" tips="请填写极光推送AppKey"/></td>
        </tr>
        <tr>
            <th width="80">极光推送Secret</th>
            <td><input type="text" class="input fl" name="jpush_secret" size="40" value="{pigcms{$info['jpush_secret']}" tips="请填写极光推送Secret"/></td>
        </tr>
        <tr>
            <th width="80">微信Appid</th>
            <td><input type="text" class="input fl" name="weixinapp_appid" size="40" value="{pigcms{$info['weixinapp_appid']}" tips="请填写微信Appid"/></td>
        </tr>
        <tr>
            <th width="80">微信Appsecret</th>
            <td><input type="text" class="input fl" name="weixinapp_appsecret" size="40" value="{pigcms{$info['weixinapp_appsecret']}" tips="请填写微信Appid"/></td>
        </tr>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<include file="Public:footer"/>