<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
    .page-content {
        background-color: #fff;
        position: relative;
        margin: 0;
        padding: 0px 20px;
    }
</style>
<div class="page-content">
    <div class="page-content-area">
        <style>
            .ace-file-input a {display:none;}
        </style>
        <div class="row">
            <div class="col-xs-12">
                <form  class="form-horizontal" method="post" action="__SELF__" onsubmit="return check_submit()">
                    <table class="table table-border" width="100%">
                        <input type="hidden" name="admin_id" value="{pigcms{$admin_id}"/>
                        <input type="hidden" name="village_id" value="{pigcms{$village_id}"/>
                        <tr class="menus"><td colspan="2"><label><input type="checkbox" id="all"/> 全选</label></td></tr>
                        <volist name="menus" id="rowset">
                            <tr class="menus">
                                <th width="160px" style="border-right: 1px solid #ddd">
                                    <label style="font-size: 18px;color:#999"><input type="checkbox" class="menu_{pigcms{$rowset['id']} father_menu" value="{pigcms{$rowset['id']}" name="menus[]" <if condition="in_array($rowset['id'],$role['menus']) || $is_village_auth">checked</if>>　{pigcms{$rowset['name']}</label>
                                </th>
                                <td>
                                    <volist name="rowset['child']" id="row" key="k">
                                        <ul>
                                            <if condition="$k eq $rowset['count']">
                                                <li>
                                                    <else/>
                                                <li style="border-bottom: 1px solid #ddd">
                                            </if>
                                            <label style="margin-bottom: 4px;padding-top: 5px;font-size: 16px;color:#2b7dbc">
                                                <input type="checkbox" class="child_menu_{pigcms{$row['fid']} cf_menu_{pigcms{$row['id']} child_menu" value="{pigcms{$row['id']}"  name="menus[]" data-fid="{pigcms{$row['fid']}" data-id="{pigcms{$row['id']}" <if condition="in_array($row['id'],$role['menus']) || $is_village_auth">checked</if> >
                                                　{pigcms{$row['name']}
                                            </label>　
                                            <if condition="$row['child']">
                                                <div class="" style="padding-left: 30px;">
                                                    <volist name="row['child']" id="level_3" key="k3">
                                                        <ul>
                                                            <php>if ($k3==$row['count']) {</php>
                                                            <li>
                                                                <php>} else {</php>
                                                            <li style="border-bottom: 1px solid #ddd;">
                                                                <php>}</php>
                                                                <label style="margin-bottom: 4px;padding-top: 5px;font-size: 16px;color:#33cc7b;">
                                                                    <input type="checkbox" class="child_menu2_{pigcms{$level_3['fid']} cf_menu_{pigcms{$level_3['id']} child_menu2" value="{pigcms{$level_3['id']}"  name="menus[]" data-fid="{pigcms{$level_3['fid']}"  data-id="{pigcms{$level_3['id']}" <if condition="in_array($level_3['id'],$role['menus']) || $is_village_auth">checked</if> />　{pigcms{$level_3['name']}
                                                                </label>
                                                                <php>if ($level_3['child']) {</php>
                                                                <div style="padding-left: 30px;">
                                                                    <php>
                                                                        foreach($level_3['child'] as $level_4) {
                                                                    </php>
                                                                    <label>
                                                                        <input type="checkbox" class="child_menu3_{pigcms{$level_3['fid']} child_menu3_{pigcms{$level_4['fid']} child_menu3" value="{pigcms{$level_4['id']}"  name="menus[]" data-fid="{pigcms{$level_4['fid']}" <if condition="in_array($level_4['id'],$role['menus']) || $is_village_auth">checked</if> />　{pigcms{$level_4['name']}
                                                                    </label>　
                                                                    <php>
                                                                        }
                                                                    </php>
                                                                </div>
                                                                <php>}</php>
                                                            </li>
                                                        </ul>
                                                    </volist>
                                                </div>
                                            </if>
                                            </li>
                                        </ul>
                                    </volist>
                                </td>
                            </tr>
                        </volist>
                    </table>
                    <div class="clearfix form-actions">
                        <div class="col-md-offset-3 col-md-9">
                            <button class="btn btn-info" type="submit">
                                <i class="ace-icon fa fa-check bigger-110"></i>
                                保存
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function check_submit(){
        var account = $('#account').val();
        var pwd = $('#pwd').val();
        var repwd = $('#repwd').val();
        if (account=='') {
            layer.alert('请填写账号');
            return false;
        }
    }
    $(document).ready(function(){
        $('#all').click(function(){
            if ($(this).attr('checked')) {
                $('.father_menu, .child_menu, .child_menu2, .child_menu3').attr('checked', true);
            } else {
                $('.father_menu, .child_menu, .child_menu2, .child_menu3').attr('checked', false);
            }
        });
        $('.father_menu').click(function(){
            var fid = $(this).val();
            if ($(this).attr('checked')) {
                $('.child_menu_' + fid).attr('checked', true);
                $('.child_menu_' + fid).each(function(){
                    var id = $(this).val()
                    $('.child_menu2_' + id).attr('checked', true);
                    $('.child_menu3_' + id).attr('checked', true);
                });
            } else {
                $('.child_menu_' + fid).attr('checked', false);
                $('.child_menu_' + fid).each(function(){
                    var id = $(this).val()
                    $('.child_menu2_' + id).attr('checked', false);
                    $('.child_menu3_' + id).attr('checked', false);
                });
            }
        });
        $('.child_menu').click(function(){
            var fid = $(this).attr('data-fid');
            var id = $(this).attr('data-id');
            if ($(this).attr('checked')) {
                $('.menu_' + fid).attr('checked', true);
                $('.child_menu2_' + id).attr('checked', true);
                $('.child_menu3_' + id).attr('checked', true);
            } else {
                var flag = false;
                $('.child_menu_' + fid).each(function(){
                    if ($(this).attr('checked')) {
                        flag = true;
                    }
                });
                $('.menu_' + fid).attr('checked', flag);
                $('.child_menu2_' + id).attr('checked', false);
                $('.child_menu3_' + id).attr('checked', false);
            }
        });
        $('.child_menu2').click(function(){
            var fid = $(this).attr('data-fid');
            var id = $(this).attr('data-id');
            if ($(this).attr('checked')) {
                $('.cf_menu_' + fid).attr('checked', true);
                var ffid = $('.cf_menu_' + fid).attr('data-fid');
                $('.menu_' + ffid).attr('checked', true);
                $('.child_menu3_' + id).attr('checked', true);
            } else {
                var flag = false;
                $('.child_menu2_' + fid).each(function(){
                    if ($(this).attr('checked')) {
                        flag = true;
                    }
                });
                $('.cf_menu_' + fid).attr('checked', flag);

                var flag = false;
                var ffid = $('.cf_menu_' + fid).attr('data-fid');
                $('.child_menu_' + ffid).each(function(){
                    if ($(this).attr('checked')) {
                        flag = true;
                    }
                });
                $('.menu_' + ffid).attr('checked', flag);
                $('.child_menu3_' + id).attr('checked', false);
            }
        });
        $('.child_menu3').click(function(){
            var fid = $(this).attr('data-fid');
            console.log('fid3', fid);
            if ($(this).attr('checked')) {
                $('.cf_menu_' + fid).attr('checked', true);
                var ffid = $('.cf_menu_' + fid).attr('data-fid');
                $('.cf_menu_' + ffid).attr('checked', true);
                var fffid = $('.cf_menu_' + ffid).attr('data-fid');
                $('.menu_' + fffid).attr('checked', true);
            } else {
                var flag = false;
                $('.child_menu3_' + fid).each(function(){
                    if ($(this).attr('checked')) {
                        flag = true;
                    }
                });
                console.log('child_menu3',flag,fid);
                $('.cf_menu_' + fid).attr('checked', flag);
                var ffid = $('.cf_menu_' + fid).attr('data-fid');
                flag = false;
                $('.child_menu2_' + ffid).each(function(){
                    if ($(this).attr('checked')) {
                        flag = true;
                    }
                });
                $('.cf_menu_' + ffid).attr('checked', flag);

                flag = false;
                var fffid = $('.cf_menu_' + ffid).attr('data-fid');
                $('.child_menu_' + fffid).each(function(){
                    if ($(this).attr('checked')) {
                        flag = true;
                    }
                });
                $('.menu_' + fffid).attr('checked', flag);
            }
        });
    });
</script>

<include file="Public:footer"/>