<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('ThirdSichuan/index')}">四川人口信息</a>
            </li>
            <li class="active">配置</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="tabbable">
                    <ul id="myTab" class="nav nav-tabs">
                        <li>
                            <a href="{pigcms{:U('ThirdSichuan/index')}">基本设置</a>
                        </li>
                        <li>
                            <a href="{pigcms{:U('ThirdSichuan/upload_file')}">文件上传</a>
                        </li>
                        <li class="active">
                            <a href="#basicinfo">上传记录</a>
                        </li>
                        <li>
                            <a href="{pigcms{:U('ThirdSichuan/moveConfig')}">迁移配置</a>
                        </li>
                        <li>
                            <a href="{pigcms{:U('ThirdSichuan/moveUploadFile')}">迁移文件上传</a>
                        </li>
                    </ul>
                </div>
                <div class="col-xs-12">
                    <div id="list" class="grid-view">
                        <form method="get" action="{pigcms{:U('ThirdSichuan/handle_third_user_list')}" id="find-form">
                            <input type="hidden" name="c" value="ThirdSichuan"/>
                            <input type="hidden" name="a" value="handle_third_user_list"/>
                            状态:
                            <select class=""  name="status" id="status" style="height:32px;">
                                <option value="">请选择状态</option>
                                <option value="-1" <if condition="$_GET['status'] eq -1">selected="selected"</if>>未处理</option>
                                <option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>同步成功</option>
                                <option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>无同步对象</option>
                                <option value="3" <if condition="$_GET['status'] eq 3">selected="selected"</if>>同步失败</option>
                            </select>
                            来源:
                            <select class=""  name="from" id="from" style="height:32px;">
                                <option value="">请选择来源</option>
                                <option value="-1" <if condition="$_GET['from'] eq -1">selected="selected"</if>>文件导入</option>
                                <option value="1" <if condition="$_GET['from'] eq 1">selected="selected"</if>>用户人脸上传</option>
                            </select>
                            审核状态:
                            <select class=""  name="check_status" id="from" style="height:32px;">
                                <option value="">请选择状态</option>
                                <option value="0" <if condition="$_GET['check_status'] eq 0">selected="selected"</if>>全部</option>
                                <option value="-1" <if condition="$_GET['check_status'] eq -1">selected="selected"</if>>待审核</option>
                                <option value="1" <if condition="$_GET['check_status'] eq 1">selected="selected"</if>>审核通过</option>
                                <option value="2" <if condition="$_GET['check_status'] eq 2">selected="selected"</if>>审核拒绝</option>
                            </select>
                            &nbsp;&nbsp;
                            <input class="btn btn-success" type="submit" id="find_submit" value="查找" />&nbsp;
                            <a class="btn btn-success" onclick="location.href='{pigcms{:U('ThirdSichuan/upload_file')}'">重置</a>
                            <a class="btn btn-success" id="handle_third_user_info_resynchronize">重新同步</a>
                        </form>
                    </div>
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                        <tr>
                            <th width="5%">AI能力层认定的uuid</th>
                            <th width="10%">小区业务系统已登记的人员uuid</th>
                            <th width="10%">状态</th>
                            <th width="5%">审核状态</th>
                            <th width="6%">姓名</th>
                            <th width="3%">性别</th>
                            <th width="12%">身份证</th>
                            <th width="10%">地址信息</th>
                            <th width="5%">图片</th>
                            <th width="10%">手机号</th>
                            <th width="5%">人员类型</th>
                            <th width="5%">来源</th>
                            <th width="9%">操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        <if condition="$list">
                            <volist name="list" id="vo">
                                <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                        <td><div class="tagDiv">{pigcms{$vo.third_ryid|default='-'}</div></td>
                        <td><div class="tagDiv">{pigcms{$vo.third_xqryid|default='-'}</div></td>
                        <td><div class="tagDiv">{pigcms{$vo.status_txt}</div></td>
                        <td>
                            <if condition="$vo['ownerId'] eq ''">
                                <div class="tagDiv">{pigcms{$vo.check_status_txt|default='暂无'}</div>
                                <else/>
                                <div class="tagDiv">审核通过</div>
                            </if>
                        </td>
                        <td><div class="tagDiv">{pigcms{$vo.third_xm}</div></td>
                        <td><div class="tagDiv">{pigcms{$vo.sex_txt}</div></td>
                        <td><div class="tagDiv">{pigcms{$vo.third_zjhm}</div></td>
                        <td><div class="tagDiv">{pigcms{$vo.third_jzd_dzxz}</div></td>
                        <td>
                            <if condition="$vo['img_url_txt']">
                                <img src="{pigcms{$vo['img_url_txt']}" οnerrοr="javascript:this.src='.{pigcms{$vo['img_url']};'" style="width:80px;height:80px;" class="view_msg"/>
                                <else/>
                                暂无
                            </if>
                        </td>
                        <td><div class="tagDiv">{pigcms{$vo.phone|default='暂无'}</div></td>
                        <td><div class="tagDiv">{pigcms{$vo.type_txt|default='暂无'}</div></td>
                        <td><div class="tagDiv">{pigcms{$vo.from_txt|default='暂无'}</div></td>
                        <td>
                            <if condition="$vo['check_status'] neq 1 && $vo['ownerId'] eq ''">
                                <div class="tagDiv">
                                    <a class="btn btn-success" id="chinapopinCheckSingle" data-id1="{pigcms{$vo.third_ryid}" data-id2="{pigcms{$vo.third_xqryid}">点击审核通过</a>
                                </div>
                            </if>
                        </td>
                        </tr>
                        </volist>
                        <else/>
                        <tr class="odd"><td class="button-column" colspan="10" >暂无任何信息。</td></tr>
                        </if>
                        </tbody>
                    </table>
                    {pigcms{$pagebar}
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<style>
    .form-group>label{font-size:12px;line-height:24px;}
</style>

<script type="text/javascript">
    var lock = 0;
    $('#handle_third_user_info_resynchronize').click(function () {
        console.log('lock', lock);
        if (lock==1) {
            return;
        }
        lock = 1;
        $.post("{pigcms{:U('ThirdSichuan/handle_third_user_info_resynchronize')}",{},function(result){
            lock = 0;
            console.log('result', result);
            if(result.status == 0){
                alert(result.info);
            }else{
                alert(result.info);
                window.location.reload();
            }
        })
    });

    var lockCheck = 0;
    $('#chinapopinCheckSingle').click(function () {
        var id1 = $(this).data('id1');
        var id2 = $(this).data('id2');
        console.log('lockCheck', id1, id2);
        if (lockCheck == 1) {
            return;
        }
        var id = id2;
        if (id1) {
            id = id1
        }
        lockCheck = 1;
        $.post("{pigcms{:U('ThirdSichuan/chinapopinCheckSingle')}",{'id': id},function(result){
            lockCheck = 0;
            console.log('result', result);
            if(result.status == 0){
                alert(result.info);
            }else{
                alert(result.info);
                window.location.reload();
            }
        })
    });

</script>

<include file="Public:footer"/>