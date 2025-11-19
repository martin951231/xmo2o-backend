<include file="Public:header"/>
<div class="mainbox">
	<!-- 内容头部 -->
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Lotteryturn/index')}" class="on">幸运大转盘</a>|
            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Lotteryturn/add')}','创建活动',800,600,true,false,false,addbtn,'add',true);">创建活动</a>
        </ul>
    </div>
	<!-- 内容头部 -->
    <div class="table-list">
        <table width="100%" cellspacing="0">
            <colgroup>
                <col/>
                <col/>
                <col/>
                <col/>
                <col/>
                <col width="180" align="center"/>
            </colgroup>
            <thead>
            <tr>
                <th>编号</th>
                <th>活动名称</th>
                <th>关键字</th>
                <th>有效参与人数</th>
                <th>开始时间/结束时间</th>
                <th>状态</th>
                <th class="textcenter">操作</th>
            </tr>
            </thead>
            <tbody>
            <if condition="is_array($list)">
                <volist name="list" id="vo">
                    <tr>
                        <td>{pigcms{$vo.id}</td>
                        <td>{pigcms{$vo.title}</td>
                        <td>{pigcms{$vo.keyword}</td>
                        <td>{pigcms{$vo.joinnum}</td>
                        <td>{pigcms{$vo.statdate|date='Y-m-d',###} / {pigcms{$vo.enddate|date='Y-m-d',###}</td>
                        <td><if condition="$vo['status'] eq 0"><span class="red">未开始</span><elseif condition="$vo['status'] eq 2"/><span class="red">已经结束</span><else/><span style="color: green">已经开始</span></if></td>
                        <td class="textcenter" >
                        <a href="javascript:void(0)" onclick="window.o2oTop.artiframe('{pigcms{:U('Lotteryturn/sn',array('id'=>$vo['id']))}','中奖管理',1000,800,true,false,false,false,'add',true);">中奖管理</a>　
                        <if condition="$activeType neq 'Coupon' AND 0">
                            <a href="{pigcms{:U($activeType .'/cheat',array('id'=>$vo['id']))}">作弊</a>
                        </if>
                        <a href="<if condition="$vo['status'] eq 1">javascript:drop_confirm('你确定要结束活动吗?', '{pigcms{:U($activeType .'/endLottery',array('id'=>$vo['id']))}');<else/>{pigcms{:U($activeType .'/startLottery',array('id'=>$vo['id']))}</if>">
                            <if condition="$vo['status'] eq 0">开始活动<else/>结束活动</if>
                        </a>　
                        <a title="修改"  href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Lotteryturn/edit',array('id'=>$vo['id']))}','编辑活动',800,600,true,false,false,editbtn,'add',true);" >修改</a> |
                        <a href="javascript:void(0);" class="delete_row" url="{pigcms{:U('Lotteryturn/del',array('id'=>$vo['id']))}">删除</a>
                        </td>
                </tr>
                </volist>
                <tr><td class="textcenter pagebar" colspan="14">{pigcms{$pagebar}</td></tr>
                <else/>
                <tr><td class="textcenter red" colspan="14">列表为空！</td></tr>
            </if>
            </tbody>
        </table>
    </div>
</div>
<script type="text/javascript">
$(function(){
	jQuery(document).on('click','#shopList a.red',function(){
		if(!confirm('确定要删除这条数据吗?不可恢复。')) return false;
	});
});
function CreateShop(){
	window.location.href = "{pigcms{:U($activeType . '/add')}";
}
function drop_confirm(msg, url)
{
	if (confirm(msg)) {
		window.location.href = url;
	}
}
</script>
<include file="Public:footer"/>
