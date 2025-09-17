<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                权限管理
            </li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">

        <?php if(isset($house_session['village_name'])){
            echo '<h1 style="width: 300px;margin: auto;font-size: 18px;color: red">这是【'.$house_session['village_name'].'】小区老版管理员列表</h1>';
        } ?>
        <div class="page-content-area">
            <if condition="false">
            <if condition="in_array(9,$house_session['menus'])">
            <button class="btn btn-success" onclick="Add()">添加管理员</button>
            <else/>
            <button class="btn btn-success disabled" disabled="disabled">添加管理员</button>
            </if>
            &nbsp;
            <if condition="in_array(271,$house_session['menus'])">
            <a class="btn btn-success" href="{pigcms{:U('group_list')}">权限分组列表</a>
            <else/>
            <button class="btn btn-success disabled" disabled="disabled">权限分组列表</button>
            </if>
            &nbsp;
            <if condition="in_array(270,$house_session['menus'])">
            <a class="btn btn-success" href="{pigcms{:U('role_export')}">EXCEL导出</a>
            <else/>
            <button class="btn btn-success disabled" disabled="disabled">EXCEL导出</button>
            </if>
            </if>
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="list" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">编号</th>
                                    <th width="10%">登录账号</th>
                                    <th width="20%">姓名</th>
                                    <th width="20%">手机号</th>
                                    <th width="20%">备注</th>
                                    <th width="10%">权限分组</th>
                                    <th class="button-column" width="20%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$list['list']">
                                    <volist name="list['list']" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.account}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.realname}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.remarks}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                                            <td style="text-align: center;">
                                                <if condition="false">
                                                <if condition="in_array(10,$house_session['menus'])">
                                                <a style="width: 60px;" class="label label-sm label-info" title="编辑" href="{pigcms{:U('role_edit',array('id'=>$vo['id']))}">编辑</a> &nbsp;
                                                </if>
                                                </if>
                                                <if condition="in_array(11,$house_session['menus'])">
                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认要删除吗？')){location.href='{pigcms{:U('role_del',array('id'=>$vo['id']))}'}">删除</a>
                                                </if>
                                           </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="7" >暂无管理员。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$list.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>
function Add(){
    window.location.href = "{pigcms{:U('role_add')}";
}
</script>
<include file="Public:footer"/>
