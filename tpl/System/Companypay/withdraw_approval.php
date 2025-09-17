<include file="Public:header"/>
<style>
    .link{
        font-size: 14px;
        cursor: pointer;
    }
</style>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <if condition="$config['company_pay_open'] eq 1 or isset($config['company_pay_open'])">
            <a href="{pigcms{:U('Companypay/index')}">提款列表</a>|
            <a href="{pigcms{:U('Config/index',array('galias'=>'companyPay','header'=>'Companypay/header'))}">企业付款配置</a>
            </if>
            <a href="{pigcms{:U('Companypay/withdraw_by_hand')}">手动提款</a>
            <a href="{pigcms{:U('Companypay/withdraw_approval') }"  class="on">审核在线提现</a>
        </ul>
    </div>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Companypay/withdraw_approval')}" method="get">
                    <input type="hidden" name="c" value="Companypay"/>
                    <input type="hidden" name="a" value="withdraw_approval"/>
                    筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    <select name="searchtype">
                        <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>手机号</option>
                        <option value="ID" <if condition="$_GET['searchtype'] eq 'ID'">selected="selected"</if>>身份证号</option>
                    </select>
                    <font color="#000">日期筛选：</font>
                    <input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
                    <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>

                    <button class="button" id="forms">查询</button>
                </form>
            </td>
            <td>
                <button class="button" onclick="bathAgree();">
                    <if condition="$withdraw_apply eq 1">
                        批量申请提现
                        <else/>
                        批量付款
                    </if>
                </button>
            </td>
        </tr>
        <tr>
            <td style="font-size: 16px;font-weight: 530;">
               当前待付款总额：￥{pigcms{$total_money}<span> &nbsp;&nbsp;&nbsp;当前所选中待付款总额：￥<label id="selectedMoney">0.00</label></span>
            </td>
        </tr>
    </table>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <colgroup><col> <col> <col> <col><col><col><col><col><col width="240" align="center"> </colgroup>
                <thead>
                    <tr>
                        <th> <input type="checkbox" class="check_all"></th>
                        <th>姓名</th>
                        <th width="200px">手机号</th>
                        <th>身份证号</th>
                        <if condition="$config['open_user_distributor']">
                            <th>所属城市</th>
                            <th>所属项目</th>
                        </if>
                        <th>待付款金额</th>
                        <th>服务费</th>
                        <th width="8%">提现账户</th>
                        <th>身份</th>
                        <th>申请提现时间</th>
                        <if condition="$withdraw_apply eq 1">
                        <th>状态</th>
                        </if>
                        <th width="100px">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <volist name="pay_list" id="vo">
                        <tr>
                            <td>
                                <input type="checkbox" value="{pigcms{$vo.pigcms_id}" data-money="{pigcms{$vo.money}" onclick="Dcheckbox(this)"
                                <if condition="$vo.status eq 10">
                                    disabled="disabled"
                                    <else />
                                    name="pigcms_id[]"
                                </if>
                                >
                            </td>
                            <td>{pigcms{$vo.nickname}</td>
                            <td>
                                {pigcms{$vo.phone}&nbsp;
                                <a class="btn" style=" border: 1px solid #ddd;background: #fff;padding: 4px" href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/money_list',array('uid'=>$vo['uid']))}','余额记录列表',680,560,true,false,false,null,'money_list',true);">查看余额记录</a>
                            </td>
                            <td>{pigcms{$vo.user_id_number|default='--'}</td>
                            <if condition="$config['open_user_distributor']">
                                <td>{pigcms{$vo.city_name|default='--'}</td>
                                <td>{pigcms{$vo.project_name|default='--'}</td>
                            </if>
                            <td><font color="green">￥{pigcms{$vo['money']}</font></td>
                            <td>￥{pigcms{$vo['service_charges']/100}</td>
                            <td>微信</td>
                            <td>{pigcms{$vo['user_type']}</td>
                            <td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
                            <if condition="$withdraw_apply eq 1">
                                <td>
                                    <span style="color: {pigcms{$vo['status_color']}" >{pigcms{$vo['status_txt']}</span>
                                    <if condition="$vo.status eq 20">
                                        | 失败原因<img src="./tpl/System/Static/images/help.gif" class="tips_img" title="{pigcms{$vo['fail_reason']}" style="margin-left:0;">
                                    </if>
                                </td>
                            </if>
                            <td>
                                <if condition="$vo.status != 10">
                                <font color="blue" class="link agree" data-id="{pigcms{$vo.pigcms_id}">
                                    <if condition="$withdraw_apply eq 1">
                                    申请提现
                                    <else/>
                                    付款
                                    </if>
                                </font>
                                <font color="grey" class="link refuse"  onclick="window.top.artiframe('/admin.php?g=System&c=Companypay&a=edit_reason&id={pigcms{$vo.pigcms_id}','编辑',400,200,true,false,false,editbtn,'edit',true);">拒绝</font>
                                <elseif condition="$withdraw_apply eq 1 && $vo.can_get_apply_status eq 1"/>
                                <font color="blue" class="link refuse" id="update_apply_atatus" data-id="{pigcms{$vo['pigcms_id']}">更新提现状态</font>
                                <else />
                                --
                                </if>
                            </td>


                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="16">{pigcms{$pagebar}</td></tr>
                </tbody>
            </table>
        </div>
    </form>
</div>
<div id="refuse" style="display: none">
    <table width="100%" cellspacing="0">
            <tr>
                <td>拒绝理由：</td>
                <td<textarea></textarea></td>
            </tr>
    </table>
</div>

<script>
	$('.agree').click(function (argument) {
		// 同意 去付款
        param = {
            pigcms_id : $(this).data('id')
        }
        if(confirm('确认付款？')){
            agree_withdraw_approval(param)
        }
    })

   function bathAgree() {
        // 批量同意 去付款
        var pigcms_id_arr = [];
        $("input[name='pigcms_id[]']").each(function(){
            if ($(this).is(':checked')) {
                pigcms_id_arr.push($(this).val());
            }
        })
        if (pigcms_id_arr.length <=0) {
            alert("{pigcms{:L_("请选择记录")}");
            return false;
        }
        var param = {
            pigcms_id : pigcms_id_arr
        }

       if(confirm('确认付款？')){
           agree_withdraw_approval(param)
       }
       return false;
   }

   function agree_withdraw_approval(param){
	    //加载层
       var index = layer.load(1,
           {
               offset: '400px',
               shade: [0.2,'gray'] ,
               content: '正在付款中...',
               success: function (layero) {
                   layero.find('.layui-layer-content').css({
                       'padding-top': '39px',
                       'width': '100px',
                        'color': '#197508',
                   });
               }

           });

        $.post('{pigcms{:U("agree_withdraw_approval")}', param, function (data) {

            layer.close(index);
            layer.open({
                content: data.info
                ,offset: '100px'
                ,area:['500px','400px']
                ,btn: ['确定']
                ,yes: function(index, layero){
                    //按钮【按钮一】的回调
                    layer.closeAll()
                    window.location.reload();
                }
                ,cancel: function(){
                    //右上角关闭回调

                    window.location.reload();
                    //return false 开启该代码可禁止点击该按钮关闭
                }
            });
        })
   }

    $(".check_all").on('click',function(){
        if($(this).is(':checked')){
            $("input[name='pigcms_id[]']").prop("checked",true);
        }else{
            $("input[name='pigcms_id[]']").prop("checked",false);
        }

        var total_money = 0;
        $("input[name='pigcms_id[]']").each(function(e){

            var money = 0;
            if($(this).is(':checked')){
                money = $(this).data('money');
            }
            total_money = total_money+money
        })
        total_money = total_money.toFixed(2)
        $('#selectedMoney').html(total_money)
        console.log(total_money)

    });

    function Dcheckbox(e){
        var n=0;
        var len = $(e).parents('table').find("input[name='pigcms_id[]']").length;
        for(var i=0;i<len;i++){
            if($(e).parents('table').find("input[name='pigcms_id[]']")[i].checked) n++;
        }
        if(n==len){
            $(e).parents('table').find(".check_all").prop("checked",true);
        }else{
            $(e).parents('table').find(".check_all").prop("checked",false);
        }

        var total_money = 0;
        $("input[name='pigcms_id[]']").each(function(e){
            var money = 0;
            if($(this).is(':checked')){
                money = $(this).data('money');
            }
            total_money = total_money+money
        })
        total_money = total_money.toFixed(2)
        console.log(total_money)
        $('#selectedMoney').html(total_money)

    }

    $('#update_apply_atatus').click(function(){
        $.post('{pigcms{:U("get_apply_status")}',{id:$(this).data('id')},function(res){console.log(res);
            if(res.error){
                alert(res.msg)
            }else{
                window.location.href="{pigcms{:U('Companypay/withdraw_approval',array('page'=>$_GET['page']))}";
            }
        })
    });
</script>
<include file="Public:footer"/>