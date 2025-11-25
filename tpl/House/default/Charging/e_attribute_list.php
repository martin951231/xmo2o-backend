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
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="20%">功能属性名称</th>
                                <th width="20%">功能属性描述</th>
                                <th class="button-column" width="20%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$info">
                                <volist name="info" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.attribute_name}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.standard_des}</div></td>
                            <td class="am-text-middle">
                                <div class="tpl-table-black-operation">
                                    <!--<a title="查看" href="{pigcms{:U('equipment_info',array('id'=>$vo['id']))}">查看</a>-->
                                    <a title="删除" style="cursor:pointer" onclick="del('{pigcms{$vo.id}')">删除</a>
                                </div>
                            </td>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="12" >没有功能属性。</td></tr>
                            </if>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function del(id) {
        var url = "{pigcms{:U('Charging/e_attribute_del')}";
        $.get(url,{'equipment_id':id},function(data){
            if(data['status']){
                location.reload();
            }else{
                alert(data['msg']);
            }
        },'json')
    }
</script>
<include file="Public:footer"/>
