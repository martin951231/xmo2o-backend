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

                    <div style="margin-top:10px; cursor:pointer" class="alert alert-danger">
                        该功能用于需要临时出入小区的人员管理，可为该类型人员办理一张临时出入小区的通行证
                    </div>
                    <button class="btn btn-success" type="button" onclick="pass_add()">添加出入证码</button>&nbsp;&nbsp;
                    <button class="btn btn-success" type="button" onclick="pass_member()" >人员管理</button>&nbsp;&nbsp;
                    <button id="search" class="btn btn-success" onclick="paper()">纸质出入证下载</button>&nbsp;&nbsp;
<!--                    <button class="btn btn-success" onclick="location.reload()">刷新</button>-->

                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="10%">序号</th>
                                <th width="20%">添加时间</th>
                                <th width="10%">出入证二维码</th>
                                <th width="10%">使用状态</th>
                                <th width="10%">状态</th>
                                <th class="button-column" width="10%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$list['list']">
                            <volist name="list['list']" id="vo">
                                <tr>
                                    <td><div class="tagDiv">{pigcms{$vo.code_num}</div></td>
                                    <td><div class="tagDiv">{pigcms{$vo.add_time}</div></td>
                                    <td><div class="tagDiv"><a href="javascript:;" onclick="look_img('{pigcms{$vo.pass_url}','{pigcms{$vo.code_num}')">查看</a></div></td>
                                    <td><div class="tagDiv">
                                            <if condition='$vo["use_status"] eq 0'>
                                                <span class="green">未使用</span>
                                                <elseif condition="$vo['use_status'] eq 1"/>
                                                <span class="red">使用中</span><br/>
                                                <span>{pigcms{$vo.text}</span>
                                                <elseif condition="$vo['use_status'] eq 2"/>
                                                <span class="red">已过期</span><br/>
                                            </if>
                                        </div></td>
                                    <td><div class="tagDiv">
                                            <if condition='$vo["status"] eq 0'><span class="green">正常</span>
                                                <elseif condition='$vo["status"] eq 1'/><span style="color: red">禁用</span>
                                                <elseif condition='$vo["status"] eq 2'/><span class="" style="color: #0099FF">过期</span></if>
                                        </div>
                                    </td>
                                    <td class="button-column">
                                        <if condition="$vo['status'] eq 0">
                                            <a style="width: 60px;" class="label label-sm label-info" onclick="pass_edit('{pigcms{$vo.pass_id}')">编辑</a>
                                        </if>
                                        <a style="width: 60px;" class="label label-sm label-info" onclick="pass_record('{pigcms{$vo.pass_id}')">出入记录</a>
                                        <if condition="$vo['use_status'] eq 0 && $vo['status'] eq 0">
                                            <a style="width: 60px;" class="label label-sm label-info" onclick="pass_bind('{pigcms{$vo.pass_id}')">绑定人员</a>
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
    //添加
    function pass_add(){
        var url = "{pigcms{:U('pass_add')}";
        layer.open({
            type: 2,
            title:'添加',
            content: url,
            area: ['650px', '255px'],
            maxmin: false
        });
    }
    //编辑
    function pass_edit(id){
        var url = "{pigcms{:U('pass_edit')}&pass_id="+id;
        layer.open({
            type: 2,
            title:'编辑',
            content: url,
            area: ['650px', '355px'],
            maxmin: false
        });
    }
    //查看二维码
    function look_img(url,code_num){
        //页面层-图片
        layer.open({
            type: 1,
            title: false,
            area: ['310px', '380px'],
            closeBtn: 0,
            // skin: 'layui-layer-nobg', //没有背景色
            shadeClose: true,
            content: '<p style="text-align: center;margin-top: 10px;">点击保存发给人员填写</p><img style="margin-left: 5px;width: 296px" src='+ url +' /><br><a class="btn btn-success" href="'+url+'" download="'+code_num+'.png" style="margin-left: 40%">点击下载</a>'//
        });
    }
    //绑定成员
    function pass_bind(id) {
        var url = "{pigcms{:U('pass_bind_member')}&pass_id="+id;
        layer.open({
            type: 2,
            title:'绑定人员',
            content: url,
            area: ['900px', '700px'],
            maxmin: false
        });
    }
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
    //人员列表
    function pass_member() {
        window.location.href="{pigcms{:U('pass_member')}";
    }
    function paper() {
        var url = "{pigcms{:U('paper_download')}";
        layer.open({
            type: 2,
            title:'纸质出入证下载',
            content: url,
            area: ['900px', '700px'],
            maxmin: false
        });
    }
</script>

<include file="Public:footer"/>