<include file="Publics:header"/>
<div class="mainbox">
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Index/house_village_list')}" method="get">
                    <input type="hidden" name="c" value="Index"/>
                    <input type="hidden" name="a" value="house_village_list"/>
                    <input type="hidden" name="type" value="{pigcms{$_GET.type}"/>
                    <input type="hidden" name="adver_id" value="{pigcms{$_GET.adver_id}"/>
                    <input type="hidden" name="village_id" id="village_id" value="{pigcms{$_GET.village_id}"/>
                    筛选:
                        <div id="choose_street" province_idss="{pigcms{$_GET.province_idss}"
                             city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}"
                             street_idss="{pigcms{$_GET.street_idss}" community_idss="{pigcms{$_GET.community_idss}"
                             style="display:inline"></div>
                    <input style="height: 30px;" type="text" name="village_name" class="input-text" placeholder="请输入<?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区') ?>名称"
                           value="{pigcms{$_GET['village_name']}"/>
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
                    <th width="5%"><?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区') ?>名称</th>
                    <th width="10%"><?php echo (D('House_village')->getHouseAlias('house_name',$village_id)?:'小区') ?>地址</th>
                    <th width="5%">操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($village_list)">
                    <volist name="village_list" id="vo">
                        <tr>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.village_name}</div>
                            </td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.village_address}</div>
                            </td>
                            <td>
                                <div class="tagDiv" style="color: #2681f3;cursor:pointer;"  >
                                   <input type="button" class="js-choose" id="js_choose_{pigcms{$vo['village_id']}" data-id="{pigcms{$vo['village_id']}" value="选中" />
                                    <input type="button" id="close_choose_{pigcms{$vo['village_id']}" data-id="{pigcms{$vo['village_id']}" style="display: none" value="取消选中"/>

                                </div>
                            </td>
                        </tr>
                    </volist>
                    <tr>
                    </tr>
                    <tr>
                        <td class="textcenter pagebar" colspan="9">{pigcms{$pagebar}</td>
                    </tr>
                    <else/>
                    <tr>
                        <td class="textcenter red" colspan="9">列表为空！</td>
                    </tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>
<script>
    $(function () {
        var village_id = $('#village_id').val();
        if (village_id!=''){
            var village_arr= new Array(); //定义一数组
            village_arr=village_id.split(","); //字符分割
            for (i=0;i<village_arr.length ;i++ )
            {
                var v_id=village_arr[i];
                if (v_id!=''){
                    v_id.trim();
                    $(".check_" + v_id).prop("checked", true);
                    $("#close_choose_"+v_id).show();
                    $("#js_choose_"+v_id).hide();
                }

            }
        }
    });
    function getQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]); return null;
    }
    var page = getQueryString("page");
    if (page==''|| page==null){
        page=1;
    }
    $("#select_all").click(function () {
        $("input[name='is_check']").each(function () {
            this.checked = !this.checked;
        });
    });


    $('.js-choose').click(function () {
        var id = $(this).data('id');
        var village_id = $('#village_id').val();
        $('#village_id').val(id);
        console.log(id);
        $(".check_" + id).prop("checked", true);
        $("#js_choose_"+village_id).show();
        $("#close_choose_"+village_id).hide();
        $("#close_choose_"+id).show();
        $("#js_choose_"+id).hide();
       /* window.location.href="{pigcms{:U('Index/house_village_list')}"+ '&village_id='+id+'&page='+page;*/
    });

   /* $('.close-choose').click(function () {
        var id = $(this).data('id');
        var village_id = $('#village_id').val();
        $('#village_id').val(id);
        $(".check_" + id).prop('checked', false);
        $("#close_choose_"+id).hide();
        $("#js_choose_"+id).show();
        window.location.href="{pigcms{:U('House/house_nav_village_list')}"+ '&village_id='+village+'&page='+page;

    });*/


</script>
<include file="Public:footer"/>