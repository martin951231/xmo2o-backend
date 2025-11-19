<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>

<form id="myform" method="post" action="" enctype="multipart/form-data">
    <input type="hidden" name="id" value="{pigcms{$_GET.id}"/>
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="80">编号</th>
            <td>
                {pigcms{$employee_list.id}
            </td>
        </tr>
        <tr>
            <th width="80">昵称</th>
            <td>
                 <input type="text" class="input fl" name="nickname" id="nickname" value="{pigcms{$employee_list.nickname}" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/>
            </td>
        </tr>
        <tr>
            <th width="80">从业者姓名</th>
            <td>
                 <input type="text" class="input fl" name="true_name" id="true_name" value="{pigcms{$employee_list.true_name}" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/>
            </td>
        </tr>
        <tr>
            <th width="80">业务联系电话</th>
            <td><input type="text" class="input fl" name="business_phone" id="business_phone" value="{pigcms{$employee_list.business_phone}" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
        </tr>
        <tr>
            <th width="80">注册手机号</th>
            <td><input type="text" class="input fl" name="phone" id="sort" value="{pigcms{$employee_list.phone}" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
        </tr>
        <tr>
            <th width="80">分类信息</th>
            <td>
                {pigcms{$employee_list.cat_pfcid_name}-{pigcms{$employee_list.cat_fid_name}-{pigcms{$employee_list.cat_id_name}
            </td>
        </tr>
        <tr>
            <th width="80">主页点击数</th>
            <td>
                {pigcms{$employee_list.click_num}
            </td>
        </tr>
        <tr>
            <th width="80">粉丝数</th>
            <td>
                {pigcms{$employee_list.fans_num}
            </td>
        </tr>
        <tr>
            <th width="80">收藏数</th>
            <td>
                {pigcms{$employee_list.collect_num}
            </td>
        </tr>
        <tr>
            <th width="80">点赞数</th>
            <td>
                {pigcms{$employee_list.thumb_num}
            </td>
        </tr>
        <tr>
            <th width="80">平均评分</th>
            <td>
                <if condition="$employee_list.score_average gt 0">{pigcms{$employee_list.score_average}<else/>暂无评分</if>
            </td>
        </tr>
        <tr>
            <th width="80">账户余额</th>
            <td>
                {pigcms{$employee_list.now_money}
            </td>
        </tr>


        <tr>
            <th width="80">是否关联店铺</th>
            <td>
                <if condition="$employee_list['is_relate_store'] eq 0">
                否
                <else />
                是
                </if>
            </td>
        </tr>
        <tr>
            <th width="80">是否启用</th>
            <td>
                <span class="cb-enable"><label class="cb-enable <if condition="$employee_list['is_open'] eq 1">selected</if>"><span>开启</span><input name="is_open" type="radio" value="1" <if condition="$employee_list['is_open'] eq 1">checked="checked"</if> /></label></span>
                <span class="cb-disable"><label class="cb-disable <if condition="$employee_list['is_open'] eq 0">selected</if>"><span>关闭</span><input name="is_open" type="radio" value="0" <if condition="$employee_list['is_open'] eq 0">checked="checked"</if> /></label></span>
            </td>
        </tr>
<!--         <tr>-->
<!--            <th width="80">是否开启悬浮广告</th>-->
<!--            <td>-->
<!--                <span class="cb-enable"><label class="cb-enable <if condition="$employee_list['is_open_adver'] eq 1">selected</if>"><span>开启</span><input name="is_open_adver" type="radio" value="1" <if condition="$employee_list['is_open_adver'] eq 1">checked="checked"</if> /></label></span>-->
<!--                <span class="cb-disable"><label class="cb-disable <if condition="$employee_list['is_open_adver'] eq 0">selected</if>"><span>关闭</span><input name="is_open_adver" type="radio" value="0" <if condition="$employee_list['is_open_adver'] eq 0">checked="checked"</if> /></label></span>-->
<!--            </td>-->
<!--        </tr>-->
<!--        <tr>-->
<!--            <th width="80">悬浮框背景色</th>-->
<!--            <td>-->
<!--                <input type="text" class="input fl" name="adver_color" id="choose_color1" style="width:120px;background: {pigcms{$employee_list['adver_color']}" placeholder="可不填写"  value="{pigcms{$employee_list['adver_color']}" tips="请点击右侧按钮选择颜色，用途为个人详情页悬浮广告背景色使用"/>&nbsp;&nbsp;<a href="javascript:void(0);" id="choose_color_box1" style="line-height:28px;">点击选择颜色</a>-->
<!--            </td>-->
<!--        </tr>-->
<!--        <tr>-->
<!--            <th width="80">悬浮框文字</th>-->
<!--            <td><input type="text" class="input fl" name="adver_info" id="adver_info" maxlength="20" value="{pigcms{$employee_list.adver_info}" size="25" placeholder=""  tips="填写悬浮内容，如不填写不显示。长度限制为20字"/></td>-->
<!--        </tr>-->
<!--        <tr>-->
<!--            <th width="80">悬浮框链接</th>-->
<!--            <td>-->
<!--                <input type="text" class="input fl" name="adver_url" id="url" value="{pigcms{$employee_list.adver_url}" size="25" placeholder=""  tips="点击悬浮广告跳转链接，如不填写前端只展示点击无反应"/>-->
<!--                <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url',0)" data-toggle="modal">从功能库选择</a>-->
<!--            </td>-->
<!--        </tr>-->
        <tr>
            <th width="80">排序</th>
            <td><input type="text" class="input fl" name="sort" id="sort" value="{pigcms{$employee_list.sort}" size="25" placeholder=""  tips=""/></td>
        </tr>


        <if condition="is_array($employee_list['write_field'])">
            <tr>
                <th width="80" style="color: #0cc2c6;">从业者信息</th>
            </tr>
            <volist name="employee_list['write_field']" id="item">
                <tr>
                    <th width="80">{pigcms{$item.name}</th>
                    <if condition="$item.type eq 2">
                        <td>
                            {pigcms{$item.address}
                            <if condition="$item.long && $item.lat">
                                (经纬度：{pigcms{$item.long},{pigcms{$item.lat})
                            </if>
                        </td>
                        <elseif condition="$item.type eq 10"/>
                        <td>
                            <volist name="item.value" id="vv">
                                <img src="{pigcms{:file_domain()}{pigcms{$vv}" style="width: 80px;height: 80px;margin-right:5px;"  class="view_msg">
                            </volist>
                        </td>

                        <elseif condition="$item.type eq 8"/>
                        <td>
                            {pigcms{$item.date_value}   {pigcms{$item.time_value}
                        </td>
                        <else/>
                        <td>
                            {pigcms{$item.value}
                        </td>
                    </if>
                </tr>
            </volist>
        </if>


        <tr>
            <th width="80">添加时间</th>
            <td>
                {pigcms{$employee_list.add_time|date='Y-m-d H:i:s',###}
            </td>
        </tr>

        <if condition="$employee_list['refuse_info'] && $employee_list['examine_status'] eq 2">
            <tr>
                <th width="80">拒绝理由</th>
                <td>
                    {pigcms{$employee_list.refuse_info}
                </td>
            </tr>
        </if>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
    $('#choose_color_box1').click(function(){
        window.o2oTop.showTopColorPanel('Openedit',0,$(this).height(),$(this).offset().left,'choose_color1')
    });
    function addLink(domid,iskeyword){
        art.dialog.data('domid', domid);
        art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
    }
    // var a = $("input[name='is_open_adver']:checked").val();
</script>
<include file="Public:footer"/>