<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('MerchantSpread/cueField',array('type'=>$_GET['type']))}" class="on">填写项列表</a>|
            <a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('MerchantSpread/cueFieldAdd',array('type'=>$_GET['type']))}','添加填写项',560,360,true,false,false,addbtn,'add',true);">添加填写项</a>
        </ul>
    </div>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <colgroup>
                    <col/>
                    <col/>
                    <col/>
                    <col width="180" align="center"/>
                </colgroup>
                <thead>
                <tr>
                    <th>排序</th>
                    <th>名称</th>
                    <th>类型</th>
                    <th>必填</th>
                    <th class="textcenter">操作</th>
                </tr>
                </thead>
                <tbody>

                <if condition="is_array($setting['cue_field']) and $setting['cue_field']">
                    <volist name="setting['cue_field']" id="vo">
                        <tr>
                            <td>{pigcms{$vo.sort}</td>
                            <td>{pigcms{$vo.name}</td>
                            <td>
                                <php>echo $cueFieldTypes[$vo['type']]??'--';</php>
                            </td>
                            <td>
                                <if condition="$vo['iswrite'] eq 1">是<else/>否</if>
                            </td>
                            <td class="textcenter"><a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('MerchantSpread/cueFieldDel')}">删除</a></td>
                        </tr>
                    </volist>
                    <else/>
                    <tr><td class="textcenter red" colspan="5">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<script type="text/javascript">
    function dialogConfirm() {
        history.go(0);
    }
</script>
<include file="Public:footer"/>