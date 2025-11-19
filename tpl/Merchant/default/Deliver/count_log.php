<include file="Public:header"/>
<div class="main-content">
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-desktop"></i>
                <a href="{pigcms{:U('Deliver/user')}">{pigcms{:L_("配送管理")}</a>
            </li>
            <li class="active">【{pigcms{$user['name']}{pigcms{:L_("】的每日配送量记录")}</li>
        </ul>
    </div>
    <!-- {pigcms{:L_("内容头部")} -->
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">{pigcms{:L_("日期")}</th>
                                    <th width="5%">{pigcms{:L_("配送量")}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="is_array($count_list)">
                                    <volist name="count_list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.today}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.num}</div></td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="2" >{pigcms{:L_("该配送员还没有统计记录")}</td></tr>
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
<include file="Public:footer"/>