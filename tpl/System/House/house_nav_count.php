<include file="Public:header"/>
<div class="mainbox">
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('House/house_nav_count')}" method="get">
                    <input type="hidden" name="c" value="House"/>
                    <input type="hidden" name="a" value="house_nav_count"/>
                    <input type="hidden" name="id" value="{pigcms{$_GET.id}"/>
                    筛选:
                    <if condition="$admin_area neq 3">
                        <div id="choose_street" province_idss="{pigcms{$_GET.province_idss}"
                             city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}"
                             street_idss="{pigcms{$_GET.street_idss}" community_idss="{pigcms{$_GET.community_idss}"
                             style="display:inline"></div>
                    </if>
                    <input type="text" name="village_name" class="input-text" placeholder="请输入小区名称" value="{pigcms{$_GET['village_name']}"/>
                    <input type="submit" value="查询" class="button"/>
                </form>
            </td>
        </tr>
    </table>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <colgroup>
                    <col/>
                    <col/>
                    <col/>
                    <col width="180" align="center"/>
                </colgroup>
                <thead>
                <tr>
                   <th width="5%">小区名称</th>
                    <th width="10%">点击量</th>
                    <th width="10%">浏览量</th>
                    <th width="10%">投放性别</th>
                    <th width="10%">投放年龄</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($adver_list)">
                    <volist name="adver_list" id="vo">
                        <tr>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.village_name}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.click_num}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.look_num}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.sex}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.age}</div>
                            </td>

                        </tr>
                    </volist>

                    <tr>
                        <td class="textcenter pagebar" colspan="9">{pigcms{$pagebar}</td>
                    </tr>
                    <else/>
                    <tr>
                        <td class="textcenter red" colspan="9">列表为空！</td>
                    </tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<style>
    body {
        background: #f3f3f3;
    }
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>
