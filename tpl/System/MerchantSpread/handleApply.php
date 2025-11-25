<include file="Public:header"/>
<style>
    .sub_mch{
        display:none
    }
    .view_msg_express,.img_prev{
        max-height: 65px;
        padding: 2px;
        margin: 2px;
        border: 1px solid #ddd;
    }
</style>
<form id="myform" method="post" action="{pigcms{:U('MerchantSpread/handleApply')}" frame="true" refresh="true">
    <input type="hidden" name="id" value="{pigcms{$apply.id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th colspan="2"><b>基本信息</b></th>
        </tr>
        <tr>
            <th width="160">姓名</th>
            <td>{pigcms{$apply.nickname}</td>
        </tr>
        <tr>
            <th width="160">推广商类型</th>
            <td><php>echo $apply['type']==1?'vip推广商':'城市推广商';</php></td>
        </tr>
        <tr>
            <th width="160">缴纳金额</th>
            <td>{pigcms{$apply.price|getFormatNumber}</td>
        </tr>
        <notempty name="apply.form_data">
            <tr>
                <th colspan="2"><b>填写项内容</b></th>
            </tr>
            <volist name="apply.form_data" id="vo">
                <tr>
                    <if condition="$key neq 'upgrade_type'">
                        <th width="160">{pigcms{$key}</th>
                        <td>
                            <if condition="is_array($vo) && ($vo['front'] neq '')">
                             <img class="view_msg_express" max-width="800" src="{pigcms{$vo.front}"/>
                            </if>
                            <if condition="is_array($vo) && ($vo['back'] neq '')">
                             <img class="view_msg_express" max-width="800" src="{pigcms{$vo.back}"/>
                            </if>
                            <if condition="is_string($vo)">
                            {pigcms{$vo}
                            </if>
                            <if condition="is_array($vo) and !empty($vo[0])">
                                <volist name="vo" id="i">
                                    <img class="view_msg_express" max-width="800" src="{pigcms{$i.title|replace_file_domain=###}"/>
                                </volist>
                            </if>
                        </td>
                    </if>
                <!-- <if condition="($vo['type'] eq 5) OR ($vo['type'] eq 9)">
                    <td>
                        <volist name="vo.images" id="img">
                            <img class="img_prev" src="{pigcms{$img}"/>
                        </volist>
                    </td>
                <else/>
                    <td>{pigcms{$vo.txt}</td>
                </if> -->
                </tr>
            </volist>
        </notempty>

        <tr>
            <th colspan="2"><b>审核</b></th>
        </tr>
        <tr>
            <th width="160">审核</th>
            <td>
                <select name="handle_status">
                    <option value="1" selected>通过</option>
                    <option value="2">驳回</option>
                </select>
            </td>
        </tr>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<script>

</script>

<include file="Public:footer"/>