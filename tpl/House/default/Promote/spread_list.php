<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('import_village')}">小区推广</a>
            </li>
            <li class="active">推广列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">

            <div class="form-group" style="border:1px solid #c5d0dc;padding:10px;">
                <form action="{pigcms{:U('Promote/spread_list')}" method="get" >
                    <input type="hidden" name="c" value="Promote"/>
                    <input type="hidden" name="a" value="spread_list"/>
                    <select name="find_type" id="find_type" class="col-sm-1" style="margin-left: 10px;margin-right:10px;height:42px;">
                        <option value="1" <if condition="$_GET['find_type'] eq 1">selected="selected"</if>>姓名</option>
                        <option value="2" <if condition="$_GET['find_type'] eq 2">selected="selected"</if>>手机号</option>
                    </select>
                    <input value="{pigcms{$_GET['find_value']}" class="col-sm-2" name="find_value" id="find_value" type="text" style="margin-right:10px;font-size:18px;height:42px;"/>
                    &nbsp;&nbsp;
                    <input class="btn btn-success" type="submit"  value="查找" />&nbsp;
                    <a class="btn btn-success" onclick="location.href='{pigcms{:U('Promote/spread_list')}'">重置</a>
                </form>
            </div>


            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="5%">姓名</th>
                                <th width="5%">联系方式</th>
                                <th width="8%">推广用户数量</th>
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="user_list">
                                <volist name="user_list['user_list']" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                            <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                            <td><div class="tagDiv">{pigcms{$vo.spread_count}</div></td>
                            </tr>
                            </volist>
                            <else/>
                            <tr class="odd"><td class="button-column" colspan="14" >没有任何信息。</td></tr>
                            </if>
                            </tbody>
                        </table>
                        {pigcms{$user_list['user_list'].pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var choose_single = "{pigcms{:U('Service/ajax_get_single')}";
    var choose_floor = "{pigcms{:U('Service/ajax_get_floor')}";
    var choose_vacancy = "{pigcms{:U('Service/ajax_get_vacancy')}";
</script>
<script type="text/javascript" src="{pigcms{$static_path}js/village_choose.js?t=001"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>
