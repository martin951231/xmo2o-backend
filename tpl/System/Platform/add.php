<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pcap.js?13"></script>
<form id="myform" method="post" action="" enctype="multipart/form-data">
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <if condition="!isset($_GET['fid'])">
        <tr>
            <th width="160">区域</th>
            <td>
                <if condition="$admin_area neq 3">
                <div id="choose_pca" province_idss="{pigcms{$_GET.province_id}" city_idss="{pigcms{$_GET.city_id}"  style="display:inline">
                </div>
                 </if>
            </td>
        </tr>
        <else />
            <input type="hidden" name="province_idss" value="{pigcms{$_GET.province_id}"/>
            <input type="hidden" name="city_idss" value="{pigcms{$_GET.city_id}"/>
            <input type="hidden" name="fid" value="{pigcms{$_GET.fid}"/>
        </if>
        <tr>
            <th width="80">分类名称</th>
            <td><input type="text" class="input fl" name="cate_name" id="cate_name" value="" size="25" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
        </tr>
         <tr>
            <th width="80">排序</th>
            <td><input type="text" class="input fl" name="sort" id="sort" value="" size="25" placeholder="" validate="maxlength:20" tips=""/></td>
        </tr>
        <tr>
            <th width="80">状态</th>
            <td>
                <span class="cb-enable"><label class="cb-enable selected"><span>开启</span><input name="status" type="radio" value="0" checked="checked" /></label></span>
                <span class="cb-disable"><label class="cb-disable"><span>关闭</span><input name="status" type="radio" value="1"/></label></span>
            </td>
        </tr>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
</form>
<include file="Public:footer"/>