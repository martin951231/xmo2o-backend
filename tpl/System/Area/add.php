<include file="Public:header"/>
<style type="text/css">
    .inputwidth{ width: 50%; }
</style>
	<form id="myform" method="post" action="{pigcms{:U('Area/modify')}" frame="true" refresh="true">
		<input type="hidden" name="area_type" value="{pigcms{$_GET['type']}" >
		<input type="hidden" name="area_pid" value="{pigcms{$_GET['pid']}" >
		<input type="hidden" name="change_location" value="0" >
        <input type="hidden" id="area_type_v" value="{pigcms{$_GET['type']}" >
        <input type="hidden" readonly="readonly" name="area_map_name" value="" id="area_map_name" >
       
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%" >
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl inputwidth" name="area_name" id="area_name" size="20" placeholder="请输入名称" validate="maxlength:30,required:true"/></td>
			</tr>
			<if condition="$_GET['type'] eq 2 && (!$config['country_code'] || $config['country_code'] neq 'cn')">
				<tr>
					<th width="80">所属时区</th>
					<td>
						<select name="continent">
							<volist name="continent" id="c">
								<option value="{pigcms{$c.continent}" <if condition="$current_continent eq $c['continent']">selected="selected"</if>>{pigcms{$c.continent}</option>
							</volist>
						</select>
						<select name="timezone">
							<option value="" >默认时区</option>
							<volist name="timezone" id="t">
								<option value="{pigcms{$t.timezone}">{pigcms{$t.timezone}</option>
							</volist>
						</select>
					</td>
				</tr>
			</if>
			<if condition="$_GET['type'] eq 2">
				<tr>
					<th width="80">城市客服电话</th>
					<td><input type="text" class="input fl inputwidth" name="service_phone" id="service_phone" size="20" placeholder="城市客服电话" value="{pigcms{$now_area.service_phone}" tips="城市的客服电话"/></td>
				</tr>
			</if>
			<if condition="$_GET['type'] eq 3">
				<tr>
					<th width="80">邮编</th>
					<td><input type="text" class="input fl inputwidth" name="zip_code" id="zip_code" size="20" placeholder="请输入邮编"/></td>
				</tr>
			</if>
			<if condition="$_GET['type'] eq 4">		
				<tr>
					<th width="80">商圈分类</th>
					<td>
					<select name="circle_category_id">
					<volist name="cat_list" id="vo">
							<option value="{pigcms{$vo.id}" >{pigcms{$vo.name}</option>
					</volist>
					</select>
					</td>
				</tr>
				<tr>
					<th width="80">商圈经纬度</th>
					<td id="choose_map" default_long_lat=""></td>
					<font color="red">添加商圈请重新定位</font>					
				</tr>
			</if>
			<if condition="$_GET['type'] eq 2 || $_GET['type'] eq 3 || $_GET['type'] eq 4">
				<tr>
					<th width="80">首字母</th>
					<td><input type="text" class="input fl inputwidth" name="first_pinyin" id="first_pinyin" size="20" placeholder="" validate="maxlength:20,required:true" tips="名称第一个字符的小写首字母！输入名称后，若此字段为空，会自动填写（仅作为示例）"/></td>
				</tr>
			</if>
			<if condition="$_GET['type'] gt 1 && 0">
				<tr>
					<th width="80">网址标识</th>
					<td><input type="text" class="input fl inputwidth" name="area_url" id="area_url" size="20" placeholder="" validate="maxlength:20,required:true" tips="一般为地区名称的小写首字母！输入名称后，若此字段为空，会自动填写（仅作为示例）"/></td>
				</tr>
			</if>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl inputwidth" name="area_sort" size="10" value="0" validate="required:true,number:true,maxlength:6" tips="数值越大，排序越前"/></td>
			</tr>
			<tr>
                <th width="80">代理商佣金比例</th>
                <td><input type="text" class="input fl inputwidth" name="commission_rate" value="" size="10" value="0" validate="number:true,maxlength:6,min:0,max:100" tips="请填写一个百分比数字，不需要增加%"/></td>
            </tr>
            <if condition="in_array($_GET['type'],[1,2,3])">
                <tr>
                    <th width="100">国家区域编码</th>
                    <td><input type="text" class="input fl inputwidth" name="adcode" value="" validate="required:true,number:true" tips="请填写国家区域编码"/></td>
                </tr>
            </if>
            <if condition="in_array($_GET['type'],[2,3])">
                <tr>
                    <th width="80">佣金结算关系</th>
                    <td>
                        <php>
                            if($_GET['type'] == 2 ){
                                $selectRelation = 1;
                            }else{
                                $selectRelation = 2;
                            }
                        </php>
                        <select name="commission_relation">
                            <option value="0" <if condition="$selectRelation eq 0">selected="selected"</if>>不选</option>
                            <option value="1" <if condition="$selectRelation eq 1">selected="selected"</if>>省级</option>
                            <if condition="$_GET['type'] eq 3">
                                <option value="2" <if condition="$selectRelation eq 2">selected="selected"</if>>市级</option>
                            </if>
                            <!--<option value="3">区级</option>-->
                        </select>
                        <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="不选代表上级所有管理员均不获得佣金">
                    </td>
                </tr>
            </if>
			<if condition="$_GET['type'] gt 1">
				<tr>
					<th width="80">热门</th>
					<td>
						<span class="cb-enable"><label class="cb-enable"><span>是</span><input type="radio" name="is_hot" value="1" /></label></span>
						<span class="cb-disable"><label class="cb-disable selected"><span>否</span><input type="radio" name="is_hot" value="0" checked="checked"/></label></span>
					</td>
				</tr>
			</if>
			<if condition="$_GET['type'] eq 2 || $_GET['type'] eq 3">
				<tr>
					<th width="80">切换城市显示</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$now_area['is_show_change'] eq 1">selected</if>"><span>是</span><input type="radio" name="is_show_change" value="1" <if condition="$now_area['is_show_change'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$now_area['is_show_change'] eq 0">selected</if>"><span>否</span><input type="radio" name="is_show_change" value="0" <if condition="$now_area['is_show_change'] eq 0">checked="checked"</if>/></label></span>
						<em class="notice_tips" tips="展示在城市切换的页面中，特别建议县/县级市开启，对于用户选择地区更加有意义。只在小程序/H5/APP页面中生效，PC端无效。"></em>
					</td>
				</tr>
			</if>
			<tr>
				<th width="80">状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="is_open" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="is_open" value="0" /></label></span>
				</td>
			</tr>
            
            <if condition="($_GET['type'] eq 2 || $_GET['type'] eq 3) ">
               
            <tr>
                <if condition="$_GET['type'] eq 2">
                    <th width="100">城市政府办公地址</th>
               <else/>
                    <th width="100">区县政府办公地址</th>
                </if>
                <td><input type="text" class="input fl inputwidth" name="detail_address" id="detail_address" size="20" value="" placeholder="请输入地址" validate="maxlength:60,required:true"/></td>
            </tr>
              <tr>
					<th width="100"> 联系电话</th>
					<td><input type="text" class="input fl inputwidth" name="lx_phone" id="lx_phone" placeholder="填写联系电话" value="" tips="请填写联系电话"/></td>
				</tr>
            <tr style="display: none;">
                <th width="100">具体地区</th>
                <td><input type="text" class="input fl inputwidth" readonly="readonly" name="address" value="" id="adress" /></td> </tr>
            <tr>
                <th width="100">所处经纬度</th>
                <td>
                    <input class="input fl inputwidth" size="70" name="long_lat" id="long_lat" value="" type="text" validate="required:true"/>
                    <span class="sure" style="border: solid 1px #ddd;padding: 5px;margin-left: 5px;cursor: pointer;">确定使用</span>
                </td>
            </tr>
           </if>
        
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>

   
     <if condition="$_GET['type'] eq 2 || $_GET['type'] eq 3 ">
      <div class="modal-content" style="width:100%;">
        <div class="modal-header no-padding" style="width:100%;">
            <div class="table-header" style="padding-left:10px;">拖动红色图标，经纬度框内将自动填充经纬度。</div>
        </div>
        <div class="modal-body no-padding" style="width:100%;">
            <form id="map-search" style="margin:10px;">
                <input id="map-keyword" value="" type="textbox" style="width:300px;border:1px solid #ccc;height:24px;line-height:24px;padding-left:6px;" placeholder="尽量填写城市、区域、街道名"/>
                <input type="submit" value="搜索" class="button" style="margin-left:0px;"/>
            </form>
            <div style="width:750px;height:400px;margin-left:10px;" id="cmmap"></div>
        </div>
    </div>
    </if>


	<script type="text/javascript">
    <if condition="$_GET['type'] eq 4">
		get_first_word('area_name','area_url','first_pinyin');
      </if>

		$(function(){
			$('[name="continent"]').bind("change",function(){
				var _v = $(this).val();
				$.post("{pigcms{:U('Area/get_timezone')}",{continent:_v},function(data){
					$('[name="timezone"]').empty();
					$('[name="timezone"]').append('<option value="" >默认时区</option>');
					if(data.error == 0){
						for(var i = 0 ;i < data.data.length ;i++){
							$('[name="timezone"]').append('<option value="'+data.data[i].timezone+'" >'+data.data[i].timezone+'</option>');
						}
					}
				},'json');
			})
		})
	</script>

     <if condition="$_GET['type'] eq 2 || $_GET['type'] eq 3 ">
   <script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>
	<script type="text/javascript">
        var default_lng = 117.2285309;
        var default_lat = 31.8291397;
        var disable_edit_map = 1;
	</script>

	<if condition="$config['tianditu_web_key']">
        <script src="https://api.tianditu.gov.cn/api?v=4.0&tk={pigcms{$config.tianditu_web_key}" type="text/javascript"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/map_world.js"></script>
    <else />
        <if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
            <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places,drawing&key={pigcms{$config.google_map_ak}&language=en"></script>
            <script type="text/javascript" src="{pigcms{$static_path}js/map_google.js"></script>
            <else />
            <script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>
            <script type="text/javascript" src="https://api.map.baidu.com/library/DrawingManager/1.4/src/DrawingManager_min.js"></script>
            <script type="text/javascript">var polygonMap = 1;</script>
            <script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=3"></script>
        </if>
    </if>
	</if>
   
<include file="Public:footer"/>