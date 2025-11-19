<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Area/add_area_street')}" enctype="multipart/form-data">
        <input type="hidden" name="self_area_id" value="{pigcms{$_GET['area_id']}"/>
        <input type="hidden" name="area_type" value="{pigcms{$_GET['area_type']}"/>
        <input type="hidden" name="area_pid" value="{pigcms{$_GET['area_pid']}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" name="area_name" id="area_name" size="20" value="{pigcms{$area_street['area_name']}" placeholder="请输入名称" validate="maxlength:30,required:true"/></td>
			</tr>

            <tr >
                <th width="150">所在区域</th>
                <if condition="$_GET['from'] eq 'house' && !$_GET['area_id'] && !$_GET['area_pid']">
                    <td id="choose_street" province_idss="{pigcms{$area_street.province_id}" city_idss="{pigcms{$area_street.city_id}" area_id="{pigcms{$area_street.area_pid}" style="display:inline"></td>
                    <else/>
                    <td>{pigcms{$addressTxt}</td>
                </if>
            </tr>

            <tr>
                <th width="80">联系方式</th>
                <td>
                    <input type="text" class="input fl" name="phone" id="phone" size="20" value="{pigcms{$area_street['phone']}" placeholder="请输入联系方式" validate="maxlength:30,required:true"/>
                </td>
            </tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="area_sort" size="10" value="{pigcms{$area_street['area_sort']|default=0}" validate="required:true,number:true,maxlength:6" tips="数值越大，排序越前"/></td>
			</tr>
            <tr>
                <th width="80">代理商佣金比例</th>
                <td><input type="text" class="input fl" name="commission_rate" value="{pigcms{$area_street.commission_rate}" size="10" value="0" validate="number:true,maxlength:6,min:0,max:100" tips="请填写一个百分比数字，不需要增加%"/></td>
            </tr>
            <tr>
                <th width="80">佣金结算关系</th>
                <td>
                    <php>
                        $selectRelation = 3;
                        if($area_street){
                            if($area_street['commission_relation'] == -1){
                                $selectRelation = 3;
                            }else{
                                $selectRelation = $area_street['commission_relation'];
                            }
                        }
                    </php>
                    <select name="commission_relation">
                        <option value="0" <if condition="$selectRelation eq 0">selected="selected"</if>>不选</option>
                        <option value="1" <if condition="$selectRelation eq 1">selected="selected"</if>>省级</option>
                        <option value="2" <if condition="$selectRelation eq 2">selected="selected"</if>>市级</option>
                        <option value="3" <if condition="$selectRelation eq 3">selected="selected"</if>>区级</option>
                    </select>
                    <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="不选代表上级所有管理员均不获得佣金">
                </td>
            </tr>
             <if condition="$is_func_yougejianet_special gt 0 AND $_GET['area_id'] && $_GET['area_type'] eq 1">
            <tr>
				<th width="80">默认社区</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable <if condition="$area_street['is_default'] eq 1">selected</if>"><span>是</span><input type="radio" name="is_default" value="1" <if condition="$area_street['is_default'] eq 1">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable  <if condition="$area_street['is_default'] eq 0">selected</if>"><span>否</span><input type="radio" name="is_default" value="0" <if condition="$area_street['is_default'] eq 0">checked="checked"</if> /></label></span>
                  
                    <img src="./tpl/System/Static/images/help.gif" class="tips_img" title="前端页面默认情况下访问的街道，只能有一个默认街道">
                </td>
			</tr>
            </if>
			<tr>
				<th width="80">热门</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable <if condition="$area_street['is_hot'] eq 1">selected</if>"><span>是</span><input type="radio" name="is_hot" value="1" <if condition="$area_street['is_hot'] eq 1">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable  <if condition="$area_street['is_hot'] eq 0">selected</if>"><span>否</span><input type="radio" name="is_hot" value="0" <if condition="$area_street['is_hot'] eq 0">checked="checked"</if> /></label></span>
                </td>
			</tr>
            <tr>
                <th width="80">状态</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable <if condition="$area_street['is_open'] eq 1">selected</if>"><span>显示</span><input type="radio" name="is_open" value="1" <if condition="$area_street['is_open'] eq 1">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable  <if condition="$area_street['is_open'] eq 0">selected</if>"><span>隐藏</span><input type="radio" name="is_open" value="0" <if condition="$area_street['is_open'] eq 0">checked="checked"</if> /></label></span>
                </td>
            </tr>
            <if condition="$_GET['area_id'] && $_GET['area_id'] gt 0">
                <tr>
                    <th width="80">账号</th>
                    <td><input type="text" class="input fl" name="account" id="account" size="20" placeholder="请输入账号" validate="maxlength:100" value="{pigcms{$area_street['account']}"/></td>
                </tr>
                <tr>
                    <th width="80">密码</th>
                    <td><input type="password" class="input fl" name="pwd" id="pwd" size="20" placeholder=""  tips="添加时候必填，在修改时候不填写证明不修改密码" autocomplete="off"/></td>
                </tr>
            </if>
<!--            <tr>-->
<!--                <th width="100">登录凭依手机号</th>-->
<!--                <td>-->
<!--                    <input type="text" class="input fl" name="login_phone" value="{pigcms{$workWeiXinPhoneInfo.phone}" size="30" placeholder="请输入登录凭依手机号" tips="填写了登录凭依手机号，企业微信移动管理端登录后以此手机号为准进行账号匹配自动登录"/>-->
<!--                </td>-->
<!--            </tr>-->
            <tr>
                <if condition="$_GET['area_type'] eq 0">
                <th width="80">街道地址</th>
                    <else/>
                    <th width="80">社区地址</th>
                </if>
                <td><input type="text" class="input fl" name="detail_address" id="detail_address" size="20" value="{pigcms{$area_street['detail_address']}" placeholder="请输入地址" validate="required:true"/></td>
            </tr>
            <tr style="display: none;">
                <th width="80">具体地区</th>
                <td><input type="text" class="input fl" readonly="readonly" name="address" value="{pigcms{$area_street.address}" id="adress"/></td> </tr>
            <tr>
                <th width="80">所处经纬度</th>
                <td>
                    <input class="input fl" size="20" name="long_lat" id="long_lat" value="{pigcms{$area_street.long_lat}" type="text" validate="required:true"/>
                    <span class="sure" style="border: solid 1px #ddd;padding: 5px;margin-left: 5px;cursor: pointer;">确定使用</span>
                </td>
            </tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
    <div class="modal-content" style="width:100%;">
        <div class="modal-header no-padding" style="width:100%;">
            <div class="table-header" style="padding-left:10px;">拖动红色图标，经纬度框内将自动填充经纬度。</div>
        </div>
        <div class="modal-body no-padding" style="width:100%;">
            <form id="map-search" style="margin:10px;">
                <input id="map-keyword" value="{pigcms{$area_street.address}" type="textbox" style="width:300px;border:1px solid #ccc;height:24px;line-height:24px;padding-left:6px;" placeholder="尽量填写城市、区域、街道名"/>
                <input type="submit" value="搜索" class="button" style="margin-left:0px;"/>
            </form>
            <div style="width:750px;height:400px;margin-left:10px;" id="cmmap"></div>
        </div>
    </div>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>
	<script type="text/javascript">
//		get_first_word('area_name','area_url','first_pinyin');
        var default_lng = 117.2285309;
        var default_lat = 31.8291397;
        var disable_edit_map = 1;
	</script>
    <if condition="$area_street && $area_street['long'] && $area_street['lat']">
        <script type="text/javascript">
            default_lng = {pigcms{$area_street.long};
            default_lat = {pigcms{$area_street.lat};
        </script>
        <elseif condition="$default_city && $default_city['area_lng']"/>
        <script type="text/javascript">
            default_lng = {pigcms{$default_city.area_lng};
            default_lat = {pigcms{$default_city.area_lat};
            console.log('default_lng', default_lng);
            console.log('default_lat', default_lat);
        </script>
    </if>
    <if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
        <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places,drawing&key={pigcms{$config.google_map_ak}&language=en"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/map_google.js"></script>
        <else />
        <script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>
        <script type="text/javascript" src="https://api.map.baidu.com/library/DrawingManager/1.4/src/DrawingManager_min.js"></script>
        <script type="text/javascript">var polygonMap = 1;</script>
        <script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=3"></script>
    </if>
<include file="Public:footer"/>