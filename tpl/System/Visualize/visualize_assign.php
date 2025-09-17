<include file="Public:header"/>
<style>
    label{cursor: pointer}
    .msg_span{
        background: #FFF2DC;
        border: 1px solid #FFF2DC;
        padding: 10px 0 10px 5px;
        display: block;
        margin-top: 10px;
        color: #6f6767;
        font-size: 15px;
    }
</style>
<div class="mainbox">
  
    <if condition="$jsdhwy_new_wap_index">
        <span class="msg_span">
            根据运营方案给小区该功能的管理权限，分配后有管理权限的小区可以添加/编辑/删除本小区的广告。但小区的小区快送、小区生活服务、家庭助理、直播、邻里猜你喜欢、猜你喜欢商家、底部左侧二宫格、底部右侧二宫格这8个特殊的导航分配了权限的统一平台的数据，没分权限的可以单独去设置，其他导航不影响
        </span>
    <else />
         <span class="msg_span">根据运营方案给小区分类该广告位的管理权限，分配后，有管理权限的小区可以添加/编辑/删除本小区的广告，不影响其他小区</span>
    </if>
    <table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('Visualize/visualize_assign')}" method="get">
                    <input type="hidden" name="c" value="Visualize"/>
                    <input type="hidden" name="a" value="visualize_assign"/>
                    <input type="hidden" name="source_type" value="{pigcms{$source_type}"/>
                    筛选:
                    <if condition="$admin_area neq 3">
                        <div id="choose_street" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" street_idss="{pigcms{$_GET.street_idss}" community_idss="{pigcms{$_GET.community_idss}" style="display:inline"></div>
                    </if>
                    <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder="请输入小区名称"/>
                    <input type="submit" value="查询" class="button"/>
                </form>
            </td>
        </tr>
    </table>
    <div>
        <span class="button click_batch" data-id="1" style="margin: 0 0 5px 0 !important;color: #fff;background: #1890ff;border-color: #1890ff;">批量绑定</span>
        <span class="button click_batch"  data-id="4" style="margin: 0 0 5px 0 !important;color: #fff;background: #ffb818;border-color: #ffb818;">批量解绑</span>
    </div>
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <thead>
                <tr>
                    <th>
                        <input type="checkbox" name="select_all" id="select_all">
                        <label for="select_all">全选</label>
                    </th>
                    <th>小区名称</th>
                    <th>小区地址</th>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($village_list)">
                    <volist name="village_list" id="vo">
                        <tr>
                            <td>
                                <input type="checkbox" name="village_id[]" value="{pigcms{$vo.village_id}" class="single_select" >
<!--                                ID：{pigcms{$vo.village_id}-->
                            </td>
                            </td>
                            <td>{pigcms{$vo.village_name}</td>
                            <td>
                                <div class="tagDiv">{pigcms{$vo.village_address}</div>
                            </td>
                            <td class="textcenter" data-village_id="{pigcms{$vo.village_id}" >
                                <span class="cb-enable">
												<label class="cb-enable <php>if($vo['v_type']){</php>selected<php>}</php>"><span>已选中</span>
												<input type="radio" name="show_adver" value="1" <php>if($vo['v_type']){</php>checked="checked"<php>}</php>/>
												</label>
											</span>
                                <span class="cb-disable">
												<label class="cb-disable <php>if(!$vo['v_type']){</php>selected<php>}</php>"><span>未选中</span>
												<input type="radio" name="show_adver" value="4" <php>if(!$vo['v_type']){</php>checked="checked"<php>}</php>/>
												</label>
											</span>

                            </td>

                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="4">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="4">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>
<script>
    $('#select_all').click(function(){
        $('.single_select').each(function () {
            $(this).prop("checked",$('#select_all').is(":checked"));
        })
    })
    
    $('.click_batch').click(function () {
        var chk_value =[];
        var that=$(this),status=that.attr('data-id');
        $('input[name="village_id[]"]:checked').each(function(){
            chk_value.push($(this).val());
        });
        if(chk_value.length < 1){
            layer.msg('请选择小区', {icon: 2});
            return false;
        }
        ajax_confirm(status,chk_value);
    });

    $('input[name="show_adver"]:radio').click(function(){
        var status = $(this).val(),village_id = $(this).parents('.textcenter').data('village_id');
        ajax_confirm(status,village_id);
    })

    function ajax_confirm(status,village_id) {
        if(status == 4){
            layer.confirm('确定解绑？<br/><span style="color: red">平台取消了管理权限，则小区的广告还原为平台添加的广告，则只能查看,【操作不可逆】</span>', {
                btn: ['确定', '取消']
            }, function(index, layero){
                layer.close(index);
                ajax_submit(status,village_id);
            }, function(index){
                //按钮【按钮二】的回调
            });
        }else{
            ajax_submit(status,village_id);
        }
    }
    
    function ajax_submit(status,village_id) {
        var loading = layer.msg('正在加载', {
            icon: 16,
            shade: [0.1, '#000'],
            time: false  // 取消自动关闭
        });
        $.post('{pigcms{:U("visualize_assign_post")}',{village_id:village_id,status:status,source_type:'{pigcms{$_GET.source_type}'},function(d){
            layer.close(loading);
            layer.msg(d.info);
            if(d.status == 1){
                setTimeout(function(){window.location.reload();},800);
            }
        })
    }

</script>
<include file="Public:footer"/>