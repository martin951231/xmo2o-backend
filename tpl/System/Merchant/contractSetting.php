<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Merchant/contract')}">已签署合同商家</a>|
            <a href="{pigcms{:U('Merchant/notContract')}">未签署合同商家</a>|
            <a href="{pigcms{:U('Merchant/contractSetting')}"  class="on">合同配置</a>|
        </ul>
    </div>
    <form method="post" action="{pigcms{:U('Merchant/contractSetting')}"  enctype="multipart/form-data">
        <table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="" >
            <tbody>
            <tr>
                <th width="160">签署合同开关：</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable  <if condition="$configs['contract_must_sign'] eq 1">selected</if>"><span>需签署</span><input name="contract_must_sign" value="1" <if condition="$configs['contract_must_sign'] eq 1">checked="checked"</if> type="radio"></label></span>
                    <span class="cb-disable" ><label class="cb-disable <if condition="$configs['contract_must_sign'] eq 0">selected</if>"><span>无需签署</span><input name="contract_must_sign" value="0" <if condition="$configs['contract_must_sign'] eq 0">checked="checked"</if> type="radio"></label></span>
                </td>
            </tr>

            <tr>
                <th width="160">乙方名称：</th>
                <td>
                    <input type="text" class="input-text" name="contract_second_company" id="ios_version" value="{pigcms{$configs.contract_second_company}" size="30" validate="required:true" tips="请填写当前IOS版本名称"/>
                </td>
            </tr>
            <tr>
                <th width="160">代表名称：</th>
                <td>
                    <input type="text" class="input-text" name="contract_second_name" id="ios_version" value="{pigcms{$configs.contract_second_name}" size="30" validate="required:true" tips="请填写当前IOS版本名称"/>
                </td>
            </tr>
            <tr>
                <th width="160">乙方电话：</th>
                <td>
                    <input type="text" class="input-text" name="contract_second_tel" id="ios_version" value="{pigcms{$configs.contract_second_tel}" size="30" validate="required:true"/>
                </td>
            </tr>
            <tr>
                <th width="160">乙方地址：</th>
                <td>
                    <input type="text" class="input-text" name="contract_second_address" value="{pigcms{$configs.contract_second_address}" size="30" validate="required:true"/>
                </td>
            </tr>
            <tr>
                <th width="160">合同有效期：</th>
                <td>
                    <input type="number" class="input-text" name="contract_effective_time" value="{pigcms{$configs.contract_effective_time}" size="30" validate="required:true"/>
                    <select name="contract_effective_type" class="valid" style="width: 50px;">
                        <option value="year" <if condition="$configs.contract_effective_type eq 'year'">selected</if>>年</option>
                        <option value="month" <if condition="$configs.contract_effective_type eq 'month'">selected</if>>月</option>
                    </select>&nbsp;&nbsp;选择时间类型
                </td>
            </tr>
            <tr>
                <th width="160">合同是否开启续签：</th>
                <td>
                    <span class="cb-enable" id="can_renewed">
						<label class="cb-enable <php>if($configs['contract_can_renewed']==1){</php>selected<php>}</php>"><span>开启</span>
						<input type="radio" name="contract_can_renewed" value="1" <php>if($configs['contract_can_renewed']==1){</php>checked="checked"<php>}</php>/>
						</label>
					</span>
                    <span class="cb-disable" id="can_not_renewed">
						<label class="cb-disable <php>if($configs['contract_can_renewed']==0){</php>selected<php>}</php>"><span>关闭</span>
						<input type="radio" name="contract_can_renewed" value="0" <php>if($configs['contract_can_renewed']==0){</php>checked="checked"<php>}</php>/>
						</label>
					</span>
                </td>
            </tr>
            <tr id="contract_few_days_notice" <if condition="$configs.contract_can_renewed neq 1">style="display:none"</if>>
                <th width="160">提前几天提醒用户续签合同：</th>
                <td>
                    <input type="number" class="input-text" name="contract_few_days_notice" value="{pigcms{$configs.contract_few_days_notice}" validate="number:true" size="30"/>
                </td>
            </tr>
            <tr>
                <th width="160">公章：</th>
                <td>
                    <input type="file" class="input fl" name="contract_official_seal"  style="width:200px;" placeholder="请上传图片" validate="required:true"/>
                    <span style="color: red;margin-left: 20px;">请上传透明背景公章图片，防止合同内容被遮挡显示不全</span>
                    <input type="hidden" name="contract_official_seal" value="{pigcms{$configs.contract_official_seal}"/>
                    <if condition="$configs['contract_official_seal']">
                        <br/><br/><br/>
                        <img src="{pigcms{$configs.contract_official_seal|replace_file_domain=###}" style="border:1px solid #ddd;padding:8px;width: 120px;height: 120px"/>
                    </if>
                </td>
            </tr>
            <tr>
                <th width="160">合同范本：</th>
                <td><textarea name="contract_content" style="width:480px;height:300px;" id="contract_content">{pigcms{:htmlspecialchars_decode($configs['contract_content'],ENT_QUOTES)}</textarea></td>
            </tr>
            </tbody>
        </table>
        <div class="btn" style="margin-top:20px;">
            <input type="submit" value="提交" class="button" />
            <input type="reset" value="取消" class="button" />
        </div>
    </form>
</div>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">

<style>
    .table_form{border:1px solid #ddd;}
    .tab_ul{margin-top:10px;border-color:#C5D0DC;margin-bottom:0!important;margin-left:0;position:relative;top:1px;border-bottom:1px solid #ddd;padding-left:0;list-style:none;}
    .tab_ul>li{position:relative;display:block;float:left;margin-bottom:-1px;}
    .tab_ul>li>a {
        position: relative;
        display: block;
        padding: 10px 15px;
        margin-right: 2px;
        line-height: 1.42857143;
        border: 1px solid transparent;
        border-radius: 4px 4px 0 0;
        padding: 7px 12px 8px;
        min-width: 100px;
        text-align: center;
    }
    .tab_ul>li>a, .tab_ul>li>a:focus {
        border-radius: 0!important;
        border-color: #c5d0dc;
        background-color: #F9F9F9;
        color: #999;
        margin-right: -1px;
        line-height: 18px;
        position: relative;
    }
    .tab_ul>li>a:focus, .tab_ul>li>a:hover {
        text-decoration: none;
        background-color: #eee;
    }
    .tab_ul>li>a:hover {
        border-color: #eee #eee #ddd;
    }
    .tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
        color: #555;
        background-color: #fff;
        border: 1px solid #ddd;
        border-bottom-color: transparent;
        cursor: default;
    }
    .tab_ul>li>a:hover {
        background-color: #FFF;
        color: #4c8fbd;
        border-color: #c5d0dc;
    }
    .tab_ul>li:first-child>a {
        margin-left: 0;
    }
    .tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
        color: #576373;
        border-color: #c5d0dc #c5d0dc transparent;
        border-top: 2px solid #4c8fbd;
        background-color: #FFF;
        z-index: 1;
        line-height: 18px;
        margin-top: -1px;
        box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
    }
    .tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
        color: #555;
        background-color: #fff;
        border: 1px solid #ddd;
        border-bottom-color: transparent;
        cursor: default;
    }
    .tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
        color: #576373;
        border-color: #c5d0dc #c5d0dc transparent;
        border-top: 2px solid #4c8fbd;
        background-color: #FFF;
        z-index: 1;
        line-height: 18px;
        margin-top: -1px;
        box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
    }
    .tab_ul:before,.tab_ul:after{
        content: " ";
        display: table;
    }
    .tab_ul:after{
        clear: both;
    }
    .ke-dialog-default{
        top:153px;
    }
    input[readonly]{
        background:#EFEFEF;
    }
</style>

<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript">
    KindEditor.ready(function(K){
        window.editor = K.create('#contract_content',{pasteType : 1,uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=merchant/contract"});
    });
    $('#can_renewed').click(function (){
        $('#contract_few_days_notice').css('display','');
    })
    $('#can_not_renewed').click(function (){
        $('#contract_few_days_notice').css('display','none');
    })
</script>
<include file="Public:footer"/>