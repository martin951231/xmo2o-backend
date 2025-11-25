<include file="Public:header"/>

<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Merchant/minimum_profit')}" class="on">平台最低利润</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Merchant/minimum_profit')}" method="get">
                    <input type="hidden" name="c" value="Merchant"/>
                    <input type="hidden" name="a" value="minimum_profit"/>
                    筛选: 
                    <font color="#000">日期筛选：</font>
                    <input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="submit" style="margin-right:20px;" value="查询" class="button"/>

                </form>
                <div>
                    <!-- 抽成总金额：<span style="color: #38CDBE;">{pigcms{$sore_num['score_get']}积分</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; -->
                    平台最低利润总额：<span style="color: red;">{pigcms{$config['Currency_symbol']}{pigcms{$minimum_profit_count}</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </div>
            </td>
            <td>
                <a href="{pigcms{:U('Merchant/minimum_profit_export',$_GET)}" class="button" style="float:right;margin-right: 10px;">导出信息</a>
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
                    <th>日期</th>
                    <th>抽成金额</th>
                    <th>用户积分返现</th>
                    <th>商家积分返现</th>
                    <th>用户分佣</th>
                    <th>商家分佣</th>
                    <th>商家备用金</th>
                    <th>平台最低利润</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($list['list']) && $list['list']">
                    <volist name="list['list']" id="vo">
                        <tr>
                            <td>{pigcms{$vo.id}</td>
                            <td>{pigcms{$vo.take_date|date="Y-m-d",###}</td>
                            <td>{pigcms{$config['Currency_symbol']}{pigcms{$vo.money}</td>
                            <td>{pigcms{$config['Currency_symbol']}{pigcms{$vo.score_pay_back_money}<span style="color:#0099FC;">（{pigcms{$vo.score_pay_back}%）</span></td>
                            <td>{pigcms{$config['Currency_symbol']}{pigcms{$vo.score_pay_back_mer_money}<span style="color:#0099FC;">（{pigcms{$vo.score_pay_back_mer}%）</span></td>
                            <td>{pigcms{$config['Currency_symbol']}{pigcms{$vo.user_spread_money}<span style="color:#0099FC;">（{pigcms{$vo.user_spread}%）</span></td>
                            <td>{pigcms{$config['Currency_symbol']}{pigcms{$vo.mer_spread_money}<span style="color:#0099FC;">（{pigcms{$vo.mer_spread}%）</span></td>
                            <td>{pigcms{$config['Currency_symbol']}{pigcms{$vo.reserve_fund_money}<span style="color:#0099FC;">（{pigcms{$vo.reserve_fund}%）</span></td>
                            <td>{pigcms{$config['Currency_symbol']}{pigcms{$vo.minimum_profit_money}<span style="color:#0099FC;">（{pigcms{$vo.minimum_profit}%）</span></td>
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
