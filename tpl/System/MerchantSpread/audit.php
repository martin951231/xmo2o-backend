<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('MerchantSpread/audit')}" class="on">已审核推广商</a>|
            <a href="{pigcms{:U('MerchantSpread/notAudit')}">待审核推广商</a>|
            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('MerchantSpread/setting')}','推广商奖励设置',550,300,true,false,false,editbtn,'edit',true);">推广商奖励设置</a>|
            <a href="{pigcms{:U('MerchantSpread/index')}">推广商申请设置</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('MerchantSpread/audit')}" method="get">
                    <input type="hidden" name="c" value="MerchantSpread"/>
                    <input type="hidden" name="a" value="audit"/>
                    推广商类型:
                    <select name="type">
                        <option value="-1" <if condition="$_GET['type'] eq 0">selected="selected"</if>>全部</option>
                        <option value="1" <if condition="$_GET['type'] eq 1">selected="selected"</if>>vip推广商</option>
                        <option value="2" <if condition="$_GET['type'] eq 2">selected="selected"</if>>城市推广商</option>
                    </select>
                    &nbsp;&nbsp;
                    搜索:
                    <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    <select name="searchtype">
                        <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>推广商手机号</option>
                        <option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>推广商姓名</option>
                    </select>
                    <input type="submit" value="查询" class="button"/>
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
                    <th>姓名</th>
                    <th>手机号</th>
                    <th>推广商类型</th>
                    <th>缴纳金额</th>
                    <if condition="C('config.open_city_merchant_spread') eq 1">
                    <th>推荐商家获得佣金比例</th>
                    </if>
                    <th>直推vip推广商数</th>
                    <th>直推城市推广商数</th>
                    <th>直推总奖励金额</th>
                    <th class="textcenter">操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="$lists">
                    <volist name="lists" id="vo">
                        <tr>
                            <td>{pigcms{$vo.id}</td>
                            <td>{pigcms{$vo.nickname}</td>
                            <td>{pigcms{$vo.phone|phone_show=###}</td>
                            <td><php>echo $vo['type']==1?'vip推广商':'城市推广商';</php></td>
                            <td>{pigcms{$vo.price}</td>
                            <if condition="C('config.open_city_merchant_spread') eq 1">
                            <td><php>echo $vo['type']==1?'--':getFormatNumber($vo['commission']).'%';</php></td>
                            </if>
                            <td>{pigcms{$vo.spread_vip_count}</td>
                            <td>{pigcms{$vo.spread_city_count}</td>
                            <td>{pigcms{$vo.spread_money}</td>
                            <td class="textcenter">
                                <if condition="$vo['type'] eq 1">
                                    <a href="javascript:void(0);" class="delete_row" parameter="uid={pigcms{$vo.uid}" url="{pigcms{:U('MerchantSpread/del',array('id'=>$vo['id']))}">删除</a>
                                <else/>
                                    <if condition="C('config.open_city_merchant_spread') eq 1">
                                    <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('MerchantSpread/edit',array('uid'=>$vo['uid']))}','推广商信息',860,100,true,false,false,editbtn,'edit',true);">编辑</a> |
                                    </if>
                                    <a href="javascript:void(0);" class="delete_row" parameter="uid={pigcms{$vo.uid}" url="{pigcms{:U('MerchantSpread/del',array('id'=>$vo['id']))}">删除</a>
                                </if>
                            </td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="13">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<include file="Public:footer"/>