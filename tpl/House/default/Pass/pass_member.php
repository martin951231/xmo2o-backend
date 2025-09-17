<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('pass_list')}">功能库</a>
            </li>
            <li class="active">出入证管理</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">

            <div class="row">
                <div class="col-xs-12">
                    <a href="{pigcms{:U('Pass/pass_list')}" style="color: #0A8DE4">< 返回列表页</a><br/>
                    <div style="border:1px solid #c5d0dc;padding:20px 10px;" class="form-group">
                        <form method="get" action="{pigcms{:U('Pass/pass_member')}">
                            <input type="hidden" value="Pass" name="c">
                            <input type="hidden" value="pass_member" name="a">
                            <select style="margin-right:10px;height:42px; width:120px" class="col-sm-1" id="find_type" name="find_type">
                                <option value="0" <if condition="$_GET['find_type'] eq 0">selected="selected"</if>>请选择</option>
                                <option value="1" <if condition="$_GET['find_type'] eq 1">selected="selected"</if>>姓名</option>
                                <option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>联系方式</option>
                            </select>
                            <input type="text" style="font-size:18px;height:42px;" id="find_value" name="find_value" class="col-sm-2" value="{pigcms{$_GET['find_value']}">
                            <span style="margin-left: 10px;">状态：</span>
                            <select style="margin-right:10px;height:42px; width:150px" id="status" name="status">
                                <option value="1" <if condition="!$_GET['status']">selected="selected"</if>>待审核</option>
                                <option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>正常</option>
                                <option value="3" <if condition="$_GET['status'] eq 3">selected="selected"</if>>已过期</option>
                                <option value="4" <if condition="$_GET['status'] eq 4">selected="selected"</if>>全部</option>
                            </select>
                            <input type="submit" value="查询" id="find_submit" class="btn btn-success"/>&nbsp;&nbsp;
                            <a style="margin:10px;"  class="btn btn-success" onclick="location.href='{pigcms{:U('Pass/pass_member')}'">重置</a>
                        </form>
                    </div><br/>
                    <button class="btn btn-success" type="button" onclick="member_add()">添加人员</button>&nbsp;&nbsp;
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="10%">出入证序号</th>
                                <th width="20%">人员姓名</th>
                                <th width="10%">联系方式</th>
                                <th width="10%">出入证到期时间</th>
                                <th width="10%">状态</th>
                                <th class="button-column" width="10%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$list['list']">
                                <volist name="list['list']" id="vo">
                                    <tr>
                                        <td><div class="tagDiv">{pigcms{$vo.code_num}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.come_name}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.come_phone}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.due_time}</div></td>
                                        <td><div class="tagDiv">
                                                <if condition='$vo["status"] eq 0'><span class="green">正常</span>
                                                    <elseif condition='$vo["status"] eq 1'/><span style="color: #0099FF">待审核</span>
                                                    <elseif condition='$vo["status"] eq 2'/><span style="color: red">已过期</span>
                                                    <elseif condition='$vo["status"] eq 3'/><span style="color: red">已拒绝</span>
                                                </if>
                                            </div>
                                        </td>
                                        <td class="button-column">
                                            <if condition="$vo['status'] eq 0 || $vo['status'] eq 1">
                                                <a style="width: 60px;" class="label label-sm label-info" onclick="member_edit('{pigcms{$vo.id}')">编辑</a>
                                            </if>
                                            <a style="width: 60px;" class="label label-sm label-info" onclick="del('{pigcms{$vo.id}')">删除</a>
                                            <if condition="$vo['status'] neq 1">
                                                <a style="width: 60px;" class="label label-sm label-info" onclick="pass_record('{pigcms{$vo.pass_id}')">出入记录</a>
                                            </if>
                                        </td>
                                    </tr>
                                </volist>
                                <else/>
                                <tr class="odd"><td class="button-column" colspan="9" >没有任何出入证码。</td></tr>
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
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    function resets() {
        $('#status').val(1);
        window.location.reload();
    }
    //添加
    function member_add(){
        var url = "{pigcms{:U('member_add')}";
        layer.open({
            type: 2,
            title:'添加',
            content: url,
            area: ['900px', '700px'],
            maxmin: false
        });
    }
    //编辑
    function member_edit(id){
        var url = "{pigcms{:U('member_edit')}&id="+id;
        layer.open({
            type: 2,
            title:'编辑',
            content: url,
            area: ['900px', '700px'],
            maxmin: false
        });
    }
    //删除
    function del(id) {
        var params = {
            'id':id,
        };
        $.post("{pigcms{:U('member_del')}", params, function(data) {
            //data = JSON.parse(data);
            console.log(data)
            if(data.status == 1){
                layer.open({
                    content: '操作成功！',
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        parent.location.reload();
                    }
                })
            }else{
                layer.alert(data.info);
            }
        });
    }
    //出行记录
    function pass_record(id) {
        var url = "{pigcms{:U('record_list')}&pass_id="+id;
        layer.open({
            type: 2,
            title:'出入记录',
            content: url,
            area: ['900px', '700px'],
            maxmin: false
        });
    }
</script>

<include file="Public:footer"/>
