<include file="Public:header"/>
<style>
    img{
        width:100px;
        height:50px;
    }
    .color_red{
        color: red
    }
    .titleh4style {
        color: white;margin-left: 5px;font-size: 15px
    }
    .number_title {
        font-size: 28px;float: left;text-align: center;font-weight: bolder;
    }

</style>
<div class="mainbox">
    <div>
        <div style="height: 100px;width: 240px;border: 1px #3399cc solid;background-color: #3399cc;border-radius: 5px;margin-top: 20px;float: left">
            <h4 class="titleh4style">短信购买数量</h4>
            <div style="text-align: center;color: white;height: 30px;line-height: 30px;margin-left: 25%">
                <div class="number_title">{pigcms{$all_sms_sum}</div>
                <div style="float:left;">（条）</div>
            </div>
        </div>
        <div style="margin-left:30px;height: 100px;width: 230px;border: 1px #1890ff solid;background-color: #1890ff;border-radius: 5px;margin-top: 20px;float: left">
            <h4 class="titleh4style">短信发送数量</h4>
            <div style="text-align: center;color: white;height: 30px;line-height: 30px;margin-left: 25%">
                <div  class="number_title">{pigcms{$all_sms_send}</div>
                <div style="float:left;">（条）</div>
            </div>
        </div>
        <div style="margin-left:30px;height: 100px;width: 230px;border: 1px #975fe4 solid;background-color: #975fe4;border-radius: 5px;margin-top: 20px;float: left">
            <h4 class="titleh4style">剩余短信数量</h4>
            <div style="text-align: center;color: white;height: 30px;line-height: 30px;margin-left: 25%">
                <div  class="number_title">{pigcms{$all_sms_remain}</div>
                <div style="float:left;">（条）</div>
            </div>
        </div>
    </div>
    <table style="padding-top: 20px" class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('System/House/sms_operate')}" method="get">
                    <input type="hidden" name="g" value="System"/>
                    <input type="hidden" name="c" value="House"/>
                    <input type="hidden" name="a" value="sms_operate"/>
                    物业名称:&nbsp;&nbsp;
                    <input type="text" name="property_name" class="input-text" value="{pigcms{$_GET['property_name']}"/>
                    小区名称:&nbsp;&nbsp;
                    <input type="text" name="village_name" class="input-text" value="{pigcms{$_GET['village_name']}"/>
                    <input type="submit" value="查询" class="button"/>

                    <span class="btn-success button" onclick="location.href='{pigcms{:U('System/House/sms_operate')}'">重置</span>
                </form>


            </td>
        </tr>
    </table>
    <br/>
    <span>短信购买总数：{pigcms{$sms_sum}</span>&nbsp;&nbsp;&nbsp;&nbsp;
    <span>短信发送数量：{pigcms{$sms_send}</span>&nbsp;&nbsp;&nbsp;&nbsp;
    <span>剩余短信数量：{pigcms{$sms_remain}</span><br/><br/><br/>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>物业名称</th>
                    <th>小区名称</th>
                    <th>短信购买总数</th>
                    <th>短信发送数量</th>
                    <th>剩余短信数量</th>
                    <th class="textcenter" style="width: 160px">操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="!empty($village_list)">
                    <volist name="village_list" id="vo">
                        <tr>
                            <td>{pigcms{$vo.property_name}</td>
                            <td>{pigcms{$vo.village_name}</td>
                            <td>{pigcms{$vo.sms_sum}</td>
                            <td>{pigcms{$vo.sms_send}</td>
                            <td>{pigcms{$vo.sms_remain}</td>
                            <td class="textcenter">
                                <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('House/sms_note',array('id'=>$vo['village_id']))}','查看详情',1200,800,true,false,false,'','add',true);">查看详情</a>
                            </td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="10">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="10">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script type="text/javascript">

</script>
<include file="Public:footer"/>