<include file="Public:header"/>
<style>
    .btn-success{
        background-color: #87b87f!important;
        border-color: #87b87f;
        color: #FFF!important;
    }
</style>
	<form id="myform" method="post" action="{pigcms{:U('House/advert_check')}"  enctype="multipart/form-data">
		<input type="hidden" name="id" value="{pigcms{$now_adver.id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">广告名称</th>
				<td>
                    {pigcms{$now_adver.name}
                </td>
			</tr>

			<tr>
				<th width="80">广告图片</th>
				<td><img src="{pigcms{$now_adver.pic}" style="width:260px;height:80px;" class="view_msg"/></td>
			</tr>
            
            <tr>
                <th width="80">链接地址</th>
                <td>
                    {pigcms{$now_adver.url}
                </td>
            </tr>
            <tr class="wxapp_open_wxapp">
                <th width="80">打开其他小程序</th>
                <td>
                    {pigcms{$wxappName}
                </td>
            </tr>
          <tr  class="wxapp_open_wxapp wxapp_page"  <if condition="empty($now_adver['wxapp_id'])">style="display: none;"</if> >
				<th width="80">小程序页面</th>
				<td>{pigcms{$now_adver.wxapp_page}</td>
			</tr>
            <if condition="$_GET['source'] neq 1">
                <tr>
                    <th width="15%">发布范围（小区）</th>
                    <td>
                        {pigcms{$now_adver.villageNames}
                    </td>
                </tr>
            <else/>
                <input type="hidden" name="source" value="1">
                <input type="hidden" name="is_platform" value="{pigcms{$_GET['is_platform']}">
            </if>

            <if condition="($config['house_visualize_switch'] eq 0 && $config['house_advert_targeted_delivery'] && $config['house_advert_targeted_delivery'] eq 1)">
                <tr>
                    <th width="80">广告有效期：</th>
                    <td colspan="2">
                        {pigcms{$now_adver.effective_start_time|date='Y-m-d H:i',###}
                        至&nbsp;
                        {pigcms{$now_adver.effective_end_time|date='Y-m-d H:i',###}
                    </td>
                </tr>
                <tr>
                    <th width="80">播放时间段：</th>
                    <td colspan="2">
                        {pigcms{$now_adver['play_start_time_txt']}
                        至&nbsp;
                        {pigcms{$now_adver['play_end_time_txt']}
                    </td>
                </tr>
                <tr>
                    <th width="80">投放年龄范围：</th>
                    <td colspan="2">
                        {pigcms{$now_adver.lm_age_min}
                        至&nbsp;
                        {pigcms{$now_adver.lm_age_max}
                    </td>
                </tr>

                <tr>
                    <th width="80">投放性别</th>
                    <td colspan="2">
                        <if condition="$now_adver['lm_sex'] eq 0">
                            不限
                            <elseif condition="$now_adver['lm_sex'] eq 1"/>
                            男
                            <else/>
                            女
                        </if>
                    </td>
                </tr>
                <tr>
                    <th width="80">虚拟浏览量：</th>
                    <td colspan="2">
                        {pigcms{$now_adver.virtual_look_num}
                    </td>
                </tr>
                <tr>
                    <th width="80">虚拟点击量：</th>
                    <td colspan="2">
                        {pigcms{$now_adver.virtual_click_num}
                    </td>
                </tr>
                <tr>
                    <th width="80">每日浏览量统计上限：</th>
                    <td colspan="2">
                        {pigcms{$now_adver.lm_day_look_max}
                    </td>
                </tr>
                <tr>
                    <th width="80">每日点击量统计上限：</th>
                    <td colspan="2">
                        {pigcms{$now_adver.lm_day_click_max}
                    </td>
                </tr>
            </if>

			<tr>
				<th width="80">广告排序</th>
				<td>
                    {pigcms{$now_adver.sort|default=0}
                </td>
			</tr>
			<tr>
				<th width="80">广告状态</th>
				<td>
                    <span style="color: {pigcms{$now_adver['status_text']}">{pigcms{$now_adver.status_text|default=未知}</span>
                </td>
			</tr>

            <if condition="$checkLog">
                <tr>
                    <th width="80">审核流程</th>
                </tr>
                <volist name="checkLog" id="item">
                    <tr>
                        <th width="80">{pigcms{$item['title']}</th>
                        <td>
                            审批人：{pigcms{$item['name']}<br>
                            审核状态：<span style="color: {pigcms{$item['statusColor']}">{pigcms{$item['statusText']}</span><br>
                            审核时间：{pigcms{$item['apply_time']}<br>
                            审核时间：{pigcms{$item['bak']|default=无}
                        </td>
                    </tr>
                </volist>
            </if>

            <if condition="$_GET['look'] neq 1">
                <tr>
                    <th width="80">审核状态</th>
                    <td>
                        <span class="cb-enable"><label class="cb-enable selected"><span>通过</span><input type="radio" name="check_status" value="2" checked="checked" /></label></span>
                        <span class="cb-disable"><label class="cb-disable"><span>拒绝</span><input type="radio" name="check_status" value="3" /></label></span>
                    </td>
                </tr>
                <tr>
                    <th width="80">审核原因</th>
                    <td>
                        <textarea id="check_reason" name="check_reason" tips="请填写审核理由（审核拒绝必须填写）"></textarea>
                    </td>
                </tr>
            </if>
		</table>
        <if condition="$_GET['look'] neq 1">
            <div class="btn hidden">
                <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
                <input type="reset" value="取消" class="button" />
            </div>
        </if>
	</form>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street_village.js?t=04"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
function addLink(domid, iskeyword, type){
	art.dialog.data('domid', domid);
<if condition="$_GET['source'] neq 1">
	if (type == 1) {
		art.dialog.open('?g=Admin&c=LinkPC&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
	} else {
		art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
	}
<else/>

    if (type == 1) {
        art.dialog.open('?g=Admin&c=LinkPC&a=visualize&iskeyword=' + iskeyword, {
            lock: true,
            title: '插入链接或关键词',
            width: 800,
            height: 500,
            yesText: '关闭',
            background: '#000',
            opacity: 0.45
        });
    } else {
        art.dialog.open('?g=Admin&c=Link&a=visualize&iskeyword=' + iskeyword, {
            lock: true,
            title: '插入链接或关键词',
            width: 800,
            height: 500,
            yesText: '关闭',
            background: '#000',
            opacity: 0.45
        });
    }
    </if>
}

function chooseFloor() {
    var url = "{pigcms{:U('House/house_nav_village_list')}";
    // var href = url + '&selecteds='+selecteds;
    var village_id= $('#village_arr').val();
    var href = url+'&type=1';
    var index= layer.open({
        type: 2,
        title:'发布小区',
        content: href+'&village_id=,'+village_id,
        area: ['95%', '95%'],
        maxmin: false,
        cancel: function (index, layero) {
            var body = layer.getChildFrame('body', index); //得到iframe页的body内容
            var BDDM = body.find("#village_id").val();
            if(!BDDM){
                $("#village_arr").attr("value", "");
            }else{
                $("#village_arr").attr("value", BDDM);
                //   $('#village_arr').val(BDDM);
            }

            console.log(BDDM);
            layer.close(index);
        }

    });
}
</script>
<script>

</script>
<script>
    $(".ant-modal-root",parent.document).css("pointer-events","none")
</script>
<include file="Public:footer"/>