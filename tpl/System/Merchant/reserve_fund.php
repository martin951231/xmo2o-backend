<include file="Public:header"/>

<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Merchant/reserve_fund')}" class="on">平台采购备用金</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Merchant/reserve_fund')}" method="get">
                    <input type="hidden" name="c" value="Merchant"/>
                    <input type="hidden" name="a" value="reserve_fund"/>
                    筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    <select name="searchtype">
                        <option value="mer_id" <if condition="$_GET['searchtype'] eq 'mer_id'">selected="selected"</if>>商家ID</option>
                        <option value="mer_name" <if condition="$_GET['searchtype'] eq 'mer_name'">selected="selected"</if>>商家名称</option>
                    </select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <font color="#000">日期筛选：</font>
                    <input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    类型:
                    <select name="type">
                        <option value="">请选择类型</option>
                        <option value="1" <if condition="$_GET['type'] eq '1'">selected="selected"</if>>增加</option>
                        <option value="2" <if condition="$_GET['type'] eq '2'">selected="selected"</if>>消耗</option>
                    </select>

                    <input type="submit" style="margin-right:20px;" value="查询" class="button"/>

                </form>
                <div>
                    获得总备用金：<span style="color: #38CDBE;">{pigcms{$config['Currency_symbol']}{pigcms{$fund_num['fund_get']}</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <if condition="$fund_num['fund_use']">
                    消耗总备用金：<span style="color: red;">{pigcms{$config['Currency_symbol']}{pigcms{$fund_num['fund_use']}</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    </if>
                </div>

            </td>

            <td>
                <a href="{pigcms{:U('Merchant/reserve_fund_export',$_GET)}" class="button" style="float:right;margin-right: 10px;">导出信息</a>
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
                    <col/>
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
                    <th>订单号</th>
                    <th>商家名称</th>
                    <th>类型</th>
                    <th>金额</th>
                    <th>获得时间</th>
                    <th>备注</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($list['list']) && $list['list']">
                    <volist name="list['list']" id="vo">
                        <tr>
                            <td>{pigcms{$vo.id}</td>
                            <td><if condition="$vo['order_id']">{pigcms{$vo.order_id}<else/>--</if></td>
                            <td>{pigcms{$vo.name}</td>
                            <td><if condition="$vo['type'] eq '1'">增加<else/>消耗</if></td>
                            <td><if condition="$vo['type'] eq '1'"><span style="color: #38CDBE;">+ <else/><span style="color: red;">- </if>{pigcms{$vo.money}</span></td>
                            <td>{pigcms{$vo.time|date="Y-m-d H:i:s",###}</td>
                            <td>{pigcms{$vo.desc}</td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="12">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="12">暂无数据！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>

<include file="Public:footer"/>
