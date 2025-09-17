<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-gear gear-icon"></i>
                <a href="{pigcms{:U('ThirdSichuan/index')}">第三方接入</a>
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
                            <a href="{pigcms{:U('ThirdZatao/index')}">配置项</a>
                        </li>
                        <li class="active">
                            <a href="#basicinfo">同步记录</a>
                        </li>
                    </ul>
                </div>
                <div class="col-xs-12">
                    <div id="list" class="grid-view">
                        <form method="get" action="{pigcms{:U('ThirdZatao/handle_third_user_list')}" id="find-form">
                            <input type="hidden" name="c" value="ThirdZatao"/>
                            <input type="hidden" name="a" value="handle_third_user_list"/>
                            搜索: <input type="text" name="keyword" id="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder="请输入关键字" />
                            <select name="searchtype" id="searchtype">
                                <option value="content" <if condition="$_GET['searchtype'] eq 'content'">selected="selected"</if>>操作内容</option>
                                <option value="admin" <if condition="$_GET['searchtype'] eq 'admin'">selected="selected"</if>>操作人员</option>
                                <option value="dataid" <if condition="$_GET['searchtype'] eq 'dataid'">selected="selected"</if>>数据ID</option>
                            </select>
                            <font color="#000">日期筛选：</font>
                            <input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                            <input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>

                            状态:
                            <select class=""  name="status" id="status" style="height:32px;">
                                <option value="">请选择状态</option>
                                <option value="-1" <if condition="$_GET['status'] eq -1">selected="selected"</if>>同步中</option>
                                <option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>同步成功</option>
                                <option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>同步失败</option>
                            </select>
                            <input class="btn btn-success" type="submit" id="find_submit" value="查找" />&nbsp;
                        </form>
                    </div>
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="10%">编号</th>
                                <th width="10%">同步时间</th>
                                <th width="10%">归属业务</th>
                                <th width="10%">数据ID</th>
                                <th width="5%">同步状态</th>
                                <th width="15%">操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$list">
                                <volist name="list" id="vo">
                                    <tr class="<if condition='$i%2 eq 0'>odd<else/>even</if>">
                                        <td><div class="tagDiv">{pigcms{$vo.id|default='-'}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.addtime|date='Y-m-d H:i:s',###}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.business}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.data_id}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.status}</div></td>
                                        <td class="button-column">
                                            <a style="width: 60px;" class="label label-sm label-info handle_btn" title="详情" href="{pigcms{:U('detail',array('id'=>$vo['id']))}">详情</a>
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
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
    $('.handle_btn').live('click',function(){
        art.dialog.open($(this).attr('href'),{
            init: function(){
                var iframe = this.iframe.contentWindow;
                window.top.art.dialog.data('iframe_handle',iframe);
            },
            id: 'handle',
            title:'同步详情',
            padding: 0,
            width: 720,
            height: 520,
            lock: true,
            resize: false,
            background:'black',
            button: null,
            fixed: false,
            close: null,
            left: '50%',
            top: '38.2%',
            opacity:'0.4'
        });
        return false;
    });
</script>

<include file="Public:footer"/>