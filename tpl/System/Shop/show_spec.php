<include file="Public:header"/>
<style>
    .table-list td{line-height:22px;padding-top:5px;padding-bottom:5px;}
    .statist-wrap{
        width: 15%;
        margin-right: 2.3%;
        padding:0 15px;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-bottom:15px;
    }
    .sta-mon{

        font-size: 25px;
        color: #333;
        width: 100%;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1;
    }
    .sta-num{
        font-size: 18px;
        color: #999;
        width: 100%;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sta-total{
        background: #1c84c6;
        padding: 2px 7px;
        color: #fff;
    }
    .sta-trade{
        background: #23c6c8;
        padding: 2px 7px;
        color: #fff;
    }
    .sta-refund{
        background: #ed5565;
        padding: 2px 7px;
        color: #fff;
    }

    .time-choice-wrap {
        margin: 0 10px;
        border-radius: 3px;
        border: 1px solid #ccc
    }

    .time-choice {
        float: left;
        display: block;
        padding: 0 10px;
        line-height: 22px;
        font-size: 12px;
        color: #333;
        outline: 0;
        cursor: pointer
    }

    .time-choice.time-current {
        color: #fff;
        background: #1ab394
    }

    .bor-gray {
        border-left: 1px solid #ccc;
        border-right: 1px solid #ccc
    }
    .fl{
        line-height: 22px;
        margin-right:5px;
        margin-bottom: 20px;
    }
</style>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('Shop/goods_price_modify_log')}" class="on">价格修改记录</a>
        </ul>
    </div>

    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>规格</th>
                    <th>操作前价格</th>
                    <th>操作后价格</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($specs)">
                    <volist name="specs" id="vo">
                        <tr>
                            <td>{pigcms{$vo.str}</td>
                            <td>{pigcms{$vo.old_price|getFormatNumber}</td>
                            <td>{pigcms{$vo.new_price|getFormatNumber}</td>
                        </tr>
                    </volist>
                    <else/>
                    <tr><td class="textcenter red" colspan="9">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<include file="Public:footer"/>