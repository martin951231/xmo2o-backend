<include file="Public:header"/>
<div class="mainbox">
    <include file="card_manage_header"/>

    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('InternalCard/transaction_count')}" method="get">
                    <input type="hidden" name="c" value="InternalCard"/>
                    <input type="hidden" name="a" value="transaction_count"/>
                    卡名称: <input type="text" name="card_name" class="input-text" value="{pigcms{$_GET['card_name']}"/>
                    店铺: <input type="text" name="store_name" class="input-text" value="{pigcms{$_GET['store_name']}"/>
                    <font color="#000">消费时间范围：</font>
                    <input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>

                    <input type="submit" style="margin-right:20px;" value="查询" class="button"/>
                </form>
                <p style="margin:5px 0;">
                    合计金额：<if condition="$total_money['total_money']">{pigcms{$total_money['total_money']}<else/>0</if>{pigcms{$config['Currency_txt']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <a href="javascript:void(0)" onclick="exportData()" class="button" style="float:right;margin-right: 10px;">导出Excel</a>

            </p>

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
                    <col  align="center"/>
                </colgroup>
                <thead>
                <tr>
                    <th>序号</th>
                    <th>一卡通名称</th>
                    <th>店铺名称</th>
                    <th>区域</th>
                    <th>消费总金额</th>
                    <th>详情</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($list)">
                    <volist name="list" id="vo" key="serial">
                        <tr>
                            <td>{pigcms{$serial}</td>
                            <td>{pigcms{$vo.card_name}</td>
                            <td>{pigcms{$vo.store_name}</td>
                            <td>{pigcms{$vo.region_str}</td>
                            <td>{pigcms{$vo.total_money}</td>
                            <td><a class="view-detail-excel" href="javascript:void(0);" data-card_name="{pigcms{$vo.card_name}" data-store_id="{pigcms{$vo.store_id}" data-card_id="{pigcms{$vo.card_id}">交易明细</a></td>
                            <td><a class="export-detail-excel" href="javascript:void(0);" data-card_name="{pigcms{$vo.card_name}" data-store_id="{pigcms{$vo.store_id}" data-card_id="{pigcms{$vo.card_id}">导出明细</a></td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="8">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="8">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>

    </form>

<include file="Public:footer"/>
    <script type="text/javascript">
        function exportData() {
            window.location.href = '/admin.php?export=excel&' + $('.search_table form').serialize();
        }

        $('.view-detail-excel').on('click', function() {
            var query = $('.search_table form').serialize();
            query = query.replace('transaction_count', 'transaction_records');
            var data = $(this).data();

            var url = '/admin.php?' + query + '&card_name=' + data.card_name + '&store_id=' + data.store_id + '&card_id=' + data.card_id;

            window.o2oTop.artiframe(url,'交易明细',1000,560,true,false,false,null,'money_list',true);
        });

        $('.export-detail-excel').on('click', function() {
            var query = $('.search_table form').serialize();
            query = query.replace('transaction_count', 'transaction_records');
            var data = $(this).data();

            window.location.href = '/admin.php?export=excel&' + query + '&card_name=' + data.card_name + '&store_id=' + data.store_id + '&card_id=' + data.card_id;
        })
    </script>