<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Deliver/user')}">配送员管理</a>|
            <a href="{pigcms{:U('Deliver/scan_report')}" class="on">配送员扫码收单统计</a>|
            <a href="{pigcms{:U('Deliver/reply')}">配送员评论列表</a>|
            <a href="{pigcms{:U('Deliver/cancel')}">配送员扔回订单列表</a>|
            <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('user_add')}','添加配送员',800,560,true,false,false,editbtn,'edit',true);">添加配送员</a>
            <!-- <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('field')}','配送员注册自定义字段',580,360,true,false,false,false,'detail',true);">配送员注册自定义字段</a> -->
            <if condition="$show_merchant_user eq 1">
                <a href="{pigcms{:U('Deliver/merchant_user')}">商家配送员管理</a>
            </if>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Deliver/scan_report')}" method="get">
                    <input type="hidden" name="c" value="Deliver"/>
                    <input type="hidden" name="a" value="scan_report"/>
                    筛选:
                    <div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>

                    <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    <select name="searchtype">
                        <option value="uid" <if condition="$_GET['searchtype'] eq 'uid'">selected="selected"</if>>用户ID</option>
                        <option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>昵称</option>
                        <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>手机号</option>
                    </select>
                    &nbsp;&nbsp;&nbsp;
                    日期筛选：
                    <select name="searchTimeType">
                        <option value="create_time" <if condition="$_GET['searchTimeType'] eq 'accept'">selected="selected"</if>>下单时间</option>
                    </select>
                    <input type="text" class="input-text" name="begin_time" style="width:80px;" id="begin_time"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text" name="end_time" style="width:80px;" id="end_time" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="submit" value="查询" class="button"/>
                    <input type="button" value="导出" class="button" onclick="export_user()"/>
                </form>
            </td>
        </tr>
    </table>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>排序</th>
                    <th>昵称</th>
                    <th>手机号</th>
                    <th>常驻地址</th>
                    <th>当前状态</th>
                    <th class="textcenter">接单</th>
                    <th class="textcenter">取餐</th>
                    <th class="textcenter">配送</th>
                    <th class="textcenter">送达</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($user_list)">
                    <volist name="user_list" id="vo">
                        <tr>
                            <td>{pigcms{$vo.uid}</td>
                            <td>{pigcms{$vo.sort|intval}</td>
                            <td>{pigcms{$vo.name}</td>
                            <td>{pigcms{$vo.phone|phone_demo_show}</td>
                            <td>{pigcms{$vo.site}</td>
                            <td>
                                <if condition="$vo['last_time'] eq 0"><font color="red">账号退出</font><else/>在线（<if condition="$vo['is_notice'] eq 0">接收新订单<else/><font color="red">不接收新订单</font></if>）</if>
                            </td>
                            <td class="textcenter">{pigcms{$vo.grap_count}</td>
                            <td class="textcenter">{pigcms{$vo.pick_count}</td>
                            <td class="textcenter">{pigcms{$vo.send_count}</td>
                            <td class="textcenter">{pigcms{$vo.complete_count}</td>
                        </tr>
                    </volist>
                    <else/>
                    <tr><td class="textcenter red" colspan="17">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<include file="Public:footer"/>
<script type="text/javascript">
    function export_user() {
        var url = "{pigcms{:U('Deliver/exportUser')}";
        $.each($('form').serializeArray(), function () {
            if (this.name != 'a' && this.name != 'c') {
                url += '&'
                url += this.name;
                url += '=';
                url += this.value;
            }
        })
        window.open(url);
    }
</script>