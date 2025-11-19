<include file="Public:header"/>
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
	<style>
        #qitianscreennav li{float: left;}
        form input:focus{outline: none;border: 1px solid #dcdcdc; box-shadow:  0 0 0 1px #d0d0d0;border-radius: 5px;}
        form .input-text{outline: none;width: 330px; height: 26px;line-height: 26px;border-radius: 5px;}
        form .input-min{outline: none;width: 180px; height: 26px;line-height: 26px;border-radius: 5px;}
        form .perfont {font-size: 22px;display: inline-block;vertical-align: bottom;margin-left: 5px;}
        form .additem {display: inline-block;border: 1px solid #ccc;padding: 2px 20px; border-radius: 5px;cursor: pointer; margin-left: 10px;}
        form .delitem {display: inline-block;border: 1px solid #ccc;padding: 2px 20px; border-radius: 5px;cursor: pointer; margin-left: 10px;}
        form .input-mmin {outline: none;width: 110px; height: 26px;line-height: 26px;border-radius: 5px;}
        form .xmonth{height: 26px;width: 120px;}
        .input-m120min{outline: none; height: 26px;line-height: 26px;border-radius: 5px;width: 120px;}
        .fieldname{font-size: 14px;font-weight: bold;}
        #qitianscreen4 .screen4div,#qitianscreen4 .xmonth4div{display: inline-block;margin-right: 10px;}
        form input{padding-left: 5px;}
        .imgclose{position: relative;top: -23px;font-size: 14px;width: 21px;
        display: inline-block;
        text-align: center; border: 1px solid #ccc;border-radius: 30px;cursor: pointer;}
        #choose_areass{ display:none }
        #choose_streetss{ display:none }
        #choose_provincess,#choose_cityss{width: 150px;height: 25px;line-height: 25px;}
	</style>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul id="qitianscreennav">
					<li class="nav1"><a href="javascript:;" class="on" data-navindex="1">基本设置</a></li>
					<li class="nav2"><a href="javascript:;" data-navindex="2">导航设置一</a></li>
					<li class="nav3"><a href="javascript:;" data-navindex="3">导航设置二</a></li>
					<li class="nav4"><a href="javascript:;" data-navindex="4">导航设置三</a></li>
                    <li class="nav5"><a href="javascript:;" data-navindex="5">导航设置四</a></li>
                    <li class="nav6"><a href="javascript:;" data-navindex="6">导航设置五</a></li>
                    <li class="nav7"><a href="javascript:;" data-navindex="7">导航设置六</a></li>
                    <li class="nav8"><a href="javascript:;" data-navindex="8">导航设置七</a></li>
                    <li class="nav9"><a href="javascript:;" data-navindex="9">导航设置八</a></li>
				
				</ul>
			</div>
            <form id="myform" refresh="true"> 
			 <div id="qitianscreen1" class="qitian_screen"> 
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%">
				<tbody>
				<tr>
				  <th width="160">可是化标题：</th>
				  <td><input type="text"  autocomplete="off" class="input-text" name="screentitle"  placeholder="请输入可是化标题（10个字符以内）" maxlength="10" value='<if condition="!empty($skycockpit) AND $skycockpit['baseinfo'] AND $skycockpit['baseinfo']['screentitle']"> {pigcms{$skycockpit["baseinfo"]["screentitle"]} <else />启天数据驾驶舱</if>' validate="required:true" tips="请输入可是化标题" required /></td>
				 </tr>
                  <tr>
				  <th width="160">选择一个城市：</th>
				  <td>
                    <div id="choose_pca" street_id="0"  province_idss="{pigcms{$province_id}" city_idss="{pigcms{$city_id}" area_id="0" style="display:inline"></div>
                    </td>
				 </tr>
             	 <tr>
				  <th width="160">驾驶舱底图：</th>
				  <td><input type="hidden" name="bg_img" value="<if condition="!empty($skycockpit) AND $skycockpit['baseinfo'] AND $skycockpit['baseinfo']['bg_img']"> {pigcms{$skycockpit["baseinfo"]["bg_img"]}</if>" id="bg_img" />
                       <input style="padding: 5px 15px;line-height: 20px;cursor: pointer;" class="btn btn-primary" type="button" id="bg_img_upload" value="上传图片" />
                       <img src="<if condition="!empty($skycockpit) AND $skycockpit['baseinfo'] AND $skycockpit['baseinfo']['bg_img']"> {pigcms{$skycockpit["baseinfo"]["bg_img"]}</if>" id="bg_img_src" <if condition="empty($skycockpit) || empty($skycockpit['baseinfo']) || empty($skycockpit['baseinfo']['bg_img'])"> class="hide" </if>  style="width: 60px;margin-left: 10px;" >
                       </td>
				 </tr>
				</tbody>
			   </table> 
                   <div class="btn" style="margin-top:20px;"> 
                        <a class="button" style="margin-left: 80px;" href="javascript:;" data-s_index="1" onclick="saveCurrentData(1)">保存数据</a>
                   </div> 
			</div> 

			 <div id="qitianscreen2" class="qitian_screen hide"> 
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%">
				<tbody>
				 <tr>
				    <th width="160">导航标题：</th>
				    <td><input type="text"  autocomplete="off" class="input-text" name="nav1title" placeholder="请输入导航标题（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navtitle']"> {pigcms{$skycockpit["info1data"]["navtitle"]} <else />社区概况</if>" maxlength="6" validate="required:true" tips="请输入导航标题（6个字符以内）" required /></td>
				 </tr>
             	 <tr>
				    <th width="160"></th>
				    <td class="subfield"><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav1f1name"  placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['0']['xname']"> {pigcms{$skycockpit['info1data']['navinfo']['0']['xname']} <else />人口总数</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="number"  autocomplete="off" class="input-min xv1" name="nav1f1v1"  min="0" max="99999999" oninput="limitmaxlength(8,this)" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['0']['xv1']"> {pigcms{$skycockpit['info1data']['navinfo']['0']['xv1']}</if>"  step="1" required /> &nbsp;&nbsp;&nbsp; 
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv2" name="nav1f1v2"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['0']['xv2']"> {pigcms{$skycockpit['info1data']['navinfo']['0']['xv2']}</if>" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span>
                   </td>
				 </tr>
                 <tr>
				    <th width="160"></th>
				    <td class="subfield"><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav1f2name" placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['1']['xname']"> {pigcms{$skycockpit['info1data']['navinfo']['1']['xname']} <else />法人数</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="number"  autocomplete="off" class="input-min xv1" name="nav1f2v1"  min="0"  oninput="limitmaxlength(8,this)"  max="99999999" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['1']['xv1']"> {pigcms{$skycockpit['info1data']['navinfo']['1']['xv1']}</if>"  step="1" required /> &nbsp;&nbsp;&nbsp; 
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)"  class="input-min xv2" name="nav1f2v2"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['1']['xv2']"> {pigcms{$skycockpit['info1data']['navinfo']['1']['xv2']}</if>" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span>
                   </td>
				 </tr>
                  <tr>
				    <th width="160"></th>
				    <td class="subfield"><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav1f3name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['2']['xname']"> {pigcms{$skycockpit['info1data']['navinfo']['2']['xname']} <else />房屋总数</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="number"  autocomplete="off" class="input-min xv1" name="nav1f3v1"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['2']['xv1']"> {pigcms{$skycockpit['info1data']['navinfo']['2']['xv1']}</if>"  step="1" required /> &nbsp;&nbsp;&nbsp; 
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv2" name="nav1f3v2"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['2']['xv2']"> {pigcms{$skycockpit['info1data']['navinfo']['2']['xv2']}</if>" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span>
                   </td>
				 </tr>
                   <tr>
				    <th width="160"></th>
				    <td class="subfield"><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav1f4name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['3']['xname']"> {pigcms{$skycockpit['info1data']['navinfo']['3']['xname']} <else />社工人员</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="number"  autocomplete="off" class="input-min xv1" name="nav1f4v1"  min="0" oninput="limitmaxlength(8,this)"  max="99999999" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['3']['xv1']"> {pigcms{$skycockpit['info1data']['navinfo']['3']['xv1']}</if>"  step="1" required /> &nbsp;&nbsp;&nbsp; 
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv2" name="nav1f4v2"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['3']['xv2']"> {pigcms{$skycockpit['info1data']['navinfo']['3']['xv2']}</if>" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span>
                   </td>
				 </tr>
                  <tr>
				    <th width="160"></th>
				    <td class="subfield"><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav1f5name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['4']['xname']"> {pigcms{$skycockpit['info1data']['navinfo']['4']['xname']} <else />物业人员</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="number"  autocomplete="off" class="input-min xv1" name="nav1f5v1"  min="0" max="99999999" oninput="limitmaxlength(8,this)"  placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['4']['xv1']"> {pigcms{$skycockpit['info1data']['navinfo']['4']['xv1']}</if>"  step="1" required /> &nbsp;&nbsp;&nbsp; 
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv2" name="nav1f5v2"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['4']['xv2']"> {pigcms{$skycockpit['info1data']['navinfo']['4']['xv2']}</if>" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span>
                   </td>
				 </tr>
                  <tr>
				    <th width="160"></th>
				    <td class="subfield"><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav1f6name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['5']['xname']"> {pigcms{$skycockpit['info1data']['navinfo']['5']['xname']} <else />楼栋长</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="number"  autocomplete="off" class="input-min xv1" name="nav1f6v1"  min="0" max="99999999" oninput="limitmaxlength(8,this)" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['5']['xv1']"> {pigcms{$skycockpit['info1data']['navinfo']['5']['xv1']}</if>"  step="1" required /> &nbsp;&nbsp;&nbsp; 
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv2" name="nav1f6v2"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['5']['xv2']"> {pigcms{$skycockpit['info1data']['navinfo']['5']['xv2']}</if>" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span>
                   </td>
				 </tr>
                 <tr>
				    <th width="160"></th>
				    <td class="subfield"><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav1f7name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['6']['xname']"> {pigcms{$skycockpit['info1data']['navinfo']['6']['xname']} </if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="number"  autocomplete="off" class="input-min xv1" name="nav1f7v1"  min="0" max="99999999" oninput="limitmaxlength(8,this)" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['6']['xv1']"> {pigcms{$skycockpit['info1data']['navinfo']['6']['xv1']}</if>"  step="1" required /> &nbsp;&nbsp;&nbsp; 
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv2" name="nav1f7v2"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['6']['xv2']"> {pigcms{$skycockpit['info1data']['navinfo']['6']['xv2']}</if>" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span>
                   </td>
				 </tr>
                <tr>
				    <th width="160"></th>
				    <td class="subfield"><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav1f8name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['7']['xname']"> {pigcms{$skycockpit['info1data']['navinfo']['7']['xname']} </if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="number"  autocomplete="off" class="input-min xv1" name="nav1f8v1"  min="0" max="99999999" oninput="limitmaxlength(8,this)" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['7']['xv1']"> {pigcms{$skycockpit['info1data']['navinfo']['7']['xv1']}</if>"  step="1" required /> &nbsp;&nbsp;&nbsp; 
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv2" name="nav1f8v2"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['7']['xv2']"> {pigcms{$skycockpit['info1data']['navinfo']['7']['xv2']}</if>" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span>
                   </td>
				 </tr> 
                 <tr>
				    <th width="160"></th>
				    <td class="subfield"><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav1f9name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['8']['xname']"> {pigcms{$skycockpit['info1data']['navinfo']['8']['xname']} </if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="number"  autocomplete="off" class="input-min xv1" name="nav1f9v1"  min="0" max="99999999" oninput="limitmaxlength(8,this)" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['8']['xv1']"> {pigcms{$skycockpit['info1data']['navinfo']['8']['xv1']}</if>"  step="1" required /> &nbsp;&nbsp;&nbsp; 
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv2" name="nav1f9v2"  value="<if condition="!empty($skycockpit) AND $skycockpit['info1data'] AND $skycockpit['info1data']['navinfo']['8']['xv2']"> {pigcms{$skycockpit['info1data']['navinfo']['8']['xv2']}</if>" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span>
                   </td>
				 </tr>
				</tbody>
			   </table> 
                   <div class="btn" style="margin-top:20px;"> 
                        <a class="button" style="margin-left: 80px;" href="javascript:;" data-s_index="2" onclick="saveCurrentData(2)">保存数据</a>
                   </div> 
			</div>

			 <div id="qitianscreen3" class="qitian_screen hide"> 
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%">
				<tbody>
				 <tr>
				    <th width="160">导航标题：</th>
				    <td><input type="text"  autocomplete="off" class="input-text" name="nav2title"  placeholder="请输入导航标题（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['navtitle']"> {pigcms{$skycockpit["info2data"]["navtitle"]} <else />社区安防</if>" maxlength="6" validate="required:true" tips="请输入导航标题" required /></td>
				 </tr>
                 <tr class="sub3field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav2f1name" placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['navinfo']['0']['xname']"> {pigcms{$skycockpit['info2data']['navinfo']['0']['xname']} </if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav2f1v1"  placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['navinfo']['0']['xv1']"> {pigcms{$skycockpit['info2data']['navinfo']['0']['xv1']} </if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                  <tr class="sub3field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav2f2name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['navinfo']['1']['xname']"> {pigcms{$skycockpit['info2data']['navinfo']['1']['xname']} </if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav2f2v1" placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['navinfo']['1']['xv1']"> {pigcms{$skycockpit['info2data']['navinfo']['1']['xv1']} </if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                   <tr class="sub3field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav2f3name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['navinfo']['2']['xname']"> {pigcms{$skycockpit['info2data']['navinfo']['2']['xname']} </if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav2f3v1" placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['navinfo']['2']['xv1']"> {pigcms{$skycockpit['info2data']['navinfo']['2']['xv1']} </if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                  <tr class="sub3field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav2f4name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['navinfo']['3']['xname']"> {pigcms{$skycockpit['info2data']['navinfo']['3']['xname']} </if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav2f4v1"  placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['navinfo']['3']['xv1']"> {pigcms{$skycockpit['info2data']['navinfo']['3']['xv1']} </if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                  <tr class="sub3field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav2f5name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['navinfo']['4']['xname']"> {pigcms{$skycockpit['info2data']['navinfo']['4']['xname']} </if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav2f5v1"  placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['navinfo']['4']['xv1']"> {pigcms{$skycockpit['info2data']['navinfo']['4']['xv1']} </if>" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>

				 <tr>
				    <th width="160">统计图标题：</th>
				    <td><input type="text"  autocomplete="off" class="input-text" name="tj2title" placeholder="请输入统计图标题（10个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['tjtitle']"> {pigcms{$skycockpit["info2data"]["tjtitle"]} <else />社区人口分布</if>" maxlength="10" validate="required:true" tips="请输入统计图标题" required /></td>
				 </tr>
             	 <tr class="subtj3field" data-xcount="1">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="tj2f1name"  placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['tjinfo']['0']['xname']"> {pigcms{$skycockpit['info2data']['tjinfo']['0']['xname']} </if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv1" name="tj2f1v1"  value="<if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['tjinfo']['0']['xv1']"> {pigcms{$skycockpit['info2data']['tjinfo']['0']['xv1']} </if>" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span> <span class="additem" onclick="additem(3)"> 添加 </span>
                   </td>
				 </tr>
                 <if condition="!empty($skycockpit) AND $skycockpit['info2data'] AND $skycockpit['info2data']['tjinfo']">
                 <volist name="skycockpit['info2data']['tjinfo']" id="vo">
                    <php>if($key==0){ continue; }else{
                        $xcount=$key+1;
                    }</php>
                    <tr class="subtj3field" data-xcount="{pigcms{$xcount}">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="tj2f2name" placeholder="请输入字段名称（6个字符以内）"  value="{pigcms{$vo.xname}" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv1" name="tj2f2v1"  value="{pigcms{$vo.xv1}" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span> <span class="delitem" onclick="delitem(this,3)"> 删除 </span>
                   </td>
				    </tr>
                </volist>
                </if>
				</tbody>
			   </table> 
                   <div class="btn" style="margin-top:20px;"> 
                        <a class="button" style="margin-left: 80px;" href="javascript:;" data-s_index="3" onclick="saveCurrentData(3)">保存数据</a>
                   </div> 
			</div>

             <div id="qitianscreen4" class="qitian_screen hide"> 
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%">
				<tbody>
				 <tr>
				    <th width="160">导航标题：</th>
				    <td><input type="text"  autocomplete="off" class="input-text" name="nav3title"   placeholder="请输入导航标题（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info3data'] AND $skycockpit['info3data']['navtitle']"> {pigcms{$skycockpit["info3data"]["navtitle"]} <else />消防社区 </if>" maxlength="6" validate="required:true" tips="请输入导航标题" required /></td>
				 </tr>
             	 <tr class="sub4field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav3f1name"  placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info3data'] AND $skycockpit['info3data']['navinfo']['0']['xname']"> {pigcms{$skycockpit['info2data']['navinfo']['0']['xname']} </if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav3f1v1" placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info3data'] AND $skycockpit['info3data']['navinfo']['0']['xv1']"> {pigcms{$skycockpit['info3data']['navinfo']['0']['xv1']} </if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                 <tr class="sub4field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav3f2name" placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info3data'] AND $skycockpit['info3data']['navinfo']['1']['xname']"> {pigcms{$skycockpit['info3data']['navinfo']['1']['xname']} </if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav3f2v1" placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info3data'] AND $skycockpit['info3data']['navinfo']['1']['xv1']"> {pigcms{$skycockpit['info3data']['navinfo']['1']['xv1']} </if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>

				 <tr>
				    <th width="160">统计图标题：</th>
				    <td><input type="text"  autocomplete="off" class="input-text" name="tj3title"  value="<if condition="!empty($skycockpit) AND $skycockpit['info3data'] AND $skycockpit['info3data']['tjtitle']"> {pigcms{$skycockpit["info3data"]["tjtitle"]} <else />某某某趋势 </if>" placeholder="请输入统计图标题（10个字符以内）" maxlength="10" validate="required:true" tips="请输入统计图标题" required /></td>
				 </tr>
                 <tr class="sub4month">
				    <th width="160"></th>
				    <td>
                    <span class="fieldname">月份设置：</span>
                    <php>
                        $xmonth='';
                        if(!empty($skycockpit) && $skycockpit['info3data'] && $skycockpit['info3data']['xmonth']){
                            $xmonth=$skycockpit['info3data']['xmonth'];
                        }
                    </php>
                    <div class="xmonth4div" style="margin-left: 210px;">
                     <select class="xmonth" name="tj3f1mv1" data-xmv="1">
                        <option value="1" <if condition="!empty($xmonth) AND $xmonth['0'] AND $xmonth['0']==1"> selected="selected" </if> >1月</option>
                        <option value="2" <if condition="!empty($xmonth) AND $xmonth['0'] AND $xmonth['0']==2"> selected="selected" </if> >2月</option>
                        <option value="3" <if condition="!empty($xmonth) AND $xmonth['0'] AND $xmonth['0']==3"> selected="selected" </if> >3月</option>
                        <option value="4" <if condition="!empty($xmonth) AND $xmonth['0'] AND $xmonth['0']==4"> selected="selected" </if> >4月</option>
                        <option value="5" <if condition="!empty($xmonth) AND $xmonth['0'] AND $xmonth['0']==5"> selected="selected" </if>>5月</option>
                        <option value="6" <if condition="!empty($xmonth) AND $xmonth['0'] AND $xmonth['0']==6"> selected="selected" </if> >6月</option>
                        <option value="7" <if condition="!empty($xmonth) AND $xmonth['0'] AND $xmonth['0']==7"> selected="selected" </if>>7月</option>
                        <option value="8" <if condition="!empty($xmonth) AND $xmonth['0'] AND $xmonth['0']==8"> selected="selected" </if> >8月</option>
                        <option value="9" <if condition="!empty($xmonth) AND $xmonth['0'] AND $xmonth['0']==9"> selected="selected" </if>>9月</option>
                        <option value="10" <if condition="!empty($xmonth) AND $xmonth['0'] AND $xmonth['0']==10"> selected="selected" </if> >10月</option>
                        <option value="11" <if condition="!empty($xmonth) AND $xmonth['0'] AND $xmonth['0']==11"> selected="selected" </if> >11月</option>
                        <option value="12" <if condition="!empty($xmonth) AND $xmonth['0'] AND $xmonth['0']==12"> selected="selected" </if> >12月</option>
                     </select>
                     </div>
                   <div class="xmonth4div">
                     <select  class="xmonth" name="tj3f1mv2" data-xmv="2">
                        <option value="1" <if condition="!empty($xmonth) AND $xmonth['1'] AND $xmonth['1']==1"> selected="selected" </if> >1月</option>
                        <option value="2" <if condition="!empty($xmonth) AND $xmonth['1'] AND $xmonth['1']==2"> selected="selected" </if> >2月</option>
                        <option value="3" <if condition="!empty($xmonth) AND $xmonth['1'] AND $xmonth['1']==3"> selected="selected" </if> >3月</option>
                        <option value="4" <if condition="!empty($xmonth) AND $xmonth['1'] AND $xmonth['1']==4"> selected="selected" </if> >4月</option>
                        <option value="5" <if condition="!empty($xmonth) AND $xmonth['1'] AND $xmonth['1']==5"> selected="selected" </if>>5月</option>
                        <option value="6" <if condition="!empty($xmonth) AND $xmonth['1'] AND $xmonth['1']==6"> selected="selected" </if> >6月</option>
                        <option value="7" <if condition="!empty($xmonth) AND $xmonth['1'] AND $xmonth['1']==7"> selected="selected" </if>>7月</option>
                        <option value="8" <if condition="!empty($xmonth) AND $xmonth['1'] AND $xmonth['1']==8"> selected="selected" </if> >8月</option>
                        <option value="9" <if condition="!empty($xmonth) AND $xmonth['1'] AND $xmonth['1']==9"> selected="selected" </if>>9月</option>
                        <option value="10" <if condition="!empty($xmonth) AND $xmonth['1'] AND $xmonth['1']==10"> selected="selected" </if> >10月</option>
                        <option value="11" <if condition="!empty($xmonth) AND $xmonth['1'] AND $xmonth['1']==11"> selected="selected" </if> >11月</option>
                        <option value="12" <if condition="!empty($xmonth) AND $xmonth['1'] AND $xmonth['1']==12"> selected="selected" </if> >12月</option>
                     </select>
                     </div>
                    <div class="xmonth4div">
                     <select  class="xmonth" name="tj3f1mv3" data-xmv="3">
                        <option value="1" <if condition="!empty($xmonth) AND $xmonth['2'] AND $xmonth['2']==1"> selected="selected" </if> >1月</option>
                        <option value="2" <if condition="!empty($xmonth) AND $xmonth['2'] AND $xmonth['2']==2"> selected="selected" </if> >2月</option>
                        <option value="3" <if condition="!empty($xmonth) AND $xmonth['2'] AND $xmonth['2']==3"> selected="selected" </if> >3月</option>
                        <option value="4" <if condition="!empty($xmonth) AND $xmonth['2'] AND $xmonth['2']==4"> selected="selected" </if> >4月</option>
                        <option value="5" <if condition="!empty($xmonth) AND $xmonth['2'] AND $xmonth['2']==5"> selected="selected" </if>>5月</option>
                        <option value="6" <if condition="!empty($xmonth) AND $xmonth['2'] AND $xmonth['2']==6"> selected="selected" </if> >6月</option>
                        <option value="7" <if condition="!empty($xmonth) AND $xmonth['2'] AND $xmonth['2']==7"> selected="selected" </if>>7月</option>
                        <option value="8" <if condition="!empty($xmonth) AND $xmonth['2'] AND $xmonth['2']==8"> selected="selected" </if> >8月</option>
                        <option value="9" <if condition="!empty($xmonth) AND $xmonth['2'] AND $xmonth['2']==9"> selected="selected" </if>>9月</option>
                        <option value="10" <if condition="!empty($xmonth) AND $xmonth['2'] AND $xmonth['2']==10"> selected="selected" </if> >10月</option>
                        <option value="11" <if condition="!empty($xmonth) AND $xmonth['2'] AND $xmonth['2']==11"> selected="selected" </if> >11月</option>
                        <option value="12" <if condition="!empty($xmonth) AND $xmonth['2'] AND $xmonth['2']==12"> selected="selected" </if> >12月</option>
                     </select>
                     </div>
                    <div class="xmonth4div">
                     <select  class="xmonth" name="tj3f1mv4" data-xmv="4">
                        <option value="1" <if condition="!empty($xmonth) AND $xmonth['3'] AND $xmonth['3']==1"> selected="selected" </if> >1月</option>
                        <option value="2" <if condition="!empty($xmonth) AND $xmonth['3'] AND $xmonth['3']==2"> selected="selected" </if> >2月</option>
                        <option value="3" <if condition="!empty($xmonth) AND $xmonth['3'] AND $xmonth['3']==3"> selected="selected" </if> >3月</option>
                        <option value="4" <if condition="!empty($xmonth) AND $xmonth['3'] AND $xmonth['3']==4"> selected="selected" </if> >4月</option>
                        <option value="5" <if condition="!empty($xmonth) AND $xmonth['3'] AND $xmonth['3']==5"> selected="selected" </if>>5月</option>
                        <option value="6" <if condition="!empty($xmonth) AND $xmonth['3'] AND $xmonth['3']==6"> selected="selected" </if> >6月</option>
                        <option value="7" <if condition="!empty($xmonth) AND $xmonth['3'] AND $xmonth['3']==7"> selected="selected" </if>>7月</option>
                        <option value="8" <if condition="!empty($xmonth) AND $xmonth['3'] AND $xmonth['3']==8"> selected="selected" </if> >8月</option>
                        <option value="9" <if condition="!empty($xmonth) AND $xmonth['3'] AND $xmonth['3']==9"> selected="selected" </if>>9月</option>
                        <option value="10" <if condition="!empty($xmonth) AND $xmonth['3'] AND $xmonth['3']==10"> selected="selected" </if> >10月</option>
                        <option value="11" <if condition="!empty($xmonth) AND $xmonth['3'] AND $xmonth['3']==11"> selected="selected" </if> >11月</option>
                        <option value="12" <if condition="!empty($xmonth) AND $xmonth['3'] AND $xmonth['3']==12"> selected="selected" </if> >12月</option>
                     </select>
                     </div>
                     <span style="color:red;"> &nbsp;&nbsp;&nbsp;最多可添加6项 </span>
                   </td>
				 </tr>

             	 <tr class="subtj4field" data-xcount="1">
				    <th width="160"></th>
				    <td>
                    <span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="tj3f1name"  placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info3data'] AND $skycockpit['info3data']['tjinfo']"> {pigcms{$skycockpit["info3data"]["tjinfo"]['0']['xname']} </if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                    <php>
                        $tjinfo0='';
                        $itemCount=0;
                        if(!empty($skycockpit) && $skycockpit['info3data'] && $skycockpit['info3data']['tjinfo'] && $skycockpit['info3data']['tjinfo']['0']){
                            $tjinfo0=$skycockpit['info3data']['tjinfo']['0'];
                            $itemCount=count($skycockpit['info3data']['tjinfo']);
                        }
                    </php>
                    <div class="screen4div">&nbsp;<span><input type="number" min="0" oninput="limitmaxlength(8,this)" max="99999999" class="input-mmin xv1" name="tj3f1v1[]"  value="<if condition="!empty($tjinfo0) AND $tjinfo0['xv'] AND $tjinfo0['xv']['0']['xv1']">{pigcms{$tjinfo0['xv']['0']['xv1']}</if>" placeholder="请输入数量值"   step="0.01" autocomplete="off" required/></span> 
                     </div>
                     <div class="screen4div">&nbsp;<span><input type="number" min="0" oninput="limitmaxlength(8,this)" max="99999999" class="input-mmin xv1" name="tj3f1v1[]"  value="<if condition="!empty($tjinfo0) AND $tjinfo0['xv'] AND $tjinfo0['xv']['1']['xv1']">{pigcms{$tjinfo0['xv']['1']['xv1']}</if>" placeholder="请输入数量值"   step="0.01" autocomplete="off" required/></span> 
                     </div>
                    <div class="screen4div">&nbsp;<span><input type="number" min="0" oninput="limitmaxlength(8,this)" max="99999999" class="input-mmin xv1" name="tj3f1v1[]"  value="<if condition="!empty($tjinfo0) AND $tjinfo0['xv'] AND $tjinfo0['xv']['2']['xv1']">{pigcms{$tjinfo0['xv']['2']['xv1']}</if>" placeholder="请输入数量值"   step="0.01" autocomplete="off" required/></span> 
                     </div>
                   <div class="screen4div">&nbsp;<span><input type="number" min="0" oninput="limitmaxlength(8,this)" max="99999999" class="input-mmin xv1" name="tj3f1v1[]"  value="<if condition="!empty($tjinfo0) AND $tjinfo0['xv'] AND $tjinfo0['xv']['3']['xv1']">{pigcms{$tjinfo0['xv']['3']['xv1']}</if>" placeholder="请输入数量值"   step="0.01" autocomplete="off" required/></span> 
                     </div>
                     <span class="additem add4item" onclick="additem(4)" <if condition="$itemCount egt 6"> style="display:none;" </if> > 添加 </span>
                   </td>
				 </tr>
                <if condition="!empty($skycockpit) && $skycockpit['info3data'] && $skycockpit['info3data']['tjinfo']">
                 <volist name="skycockpit['info3data']['tjinfo']" id="vo" >
                   <php>
                    if($key==0){ continue; }else{
                        $xcount=$key+1;
                        
                    }</php>
                 <tr class="subtj4field" data-xcount="{pigcms{$xcount}">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="tj3f2name" placeholder="请输入字段名称（6个字符以内）"  value="{pigcms{$vo.xname}" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                     <div class="screen4div">&nbsp;<span><input type="number" min="0" oninput="limitmaxlength(8,this)" max="99999999" class="input-mmin xv1" name="tj3f2v1[]"  value="<if condition="!empty($vo['xv']) AND $vo['xv']['0']">{pigcms{$vo['xv']['0']['xv1']}</if>" placeholder="请输入数量值"   step="0.01" autocomplete="off" required/></span> 
                     </div>
                     <div class="screen4div">&nbsp;<span><input type="number" min="0" oninput="limitmaxlength(8,this)" max="99999999" class="input-mmin xv1" name="tj3f2v1[]"  value="<if condition="!empty($vo['xv']) AND $vo['xv']['1']">{pigcms{$vo['xv']['1']['xv1']}</if>" placeholder="请输入数量值"   step="0.01" autocomplete="off" required/></span> 
                     </div>
                    <div class="screen4div">&nbsp;<span><input type="number" min="0" oninput="limitmaxlength(8,this)" max="99999999" class="input-mmin xv1" name="tj3f2v1[]"  value="<if condition="!empty($vo['xv']) AND $vo['xv']['2']">{pigcms{$vo['xv']['2']['xv1']}</if>" placeholder="请输入数量值"   step="0.01" autocomplete="off" required/></span> 
                     </div>
                   <div class="screen4div">&nbsp;<span><input type="number" min="0" oninput="limitmaxlength(8,this)" max="99999999" class="input-mmin xv1" name="tj3f2v1[]"  value="<if condition="!empty($vo['xv']) AND $vo['xv']['3']">{pigcms{$vo['xv']['3']['xv1']}</if>" placeholder="请输入数量值"   step="0.01" autocomplete="off" required/></span> 
                     </div>
                     <span class="delitem" onclick="delitem(this,4)"> 删除 </span>
                   </td>
				 </tr>
                  </volist>
                </if>
				</tbody>
			   </table> 
                   <div class="btn" style="margin-top:20px;"> 
                        <a class="button" style="margin-left: 80px;" href="javascript:;" data-s_index="4"  onclick="saveCurrentData(4)">保存数据</a>
                   </div> 
			</div>

            <div id="qitianscreen5" class="qitian_screen hide"> 
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%">
				<tbody>
				 <tr>
				    <th width="160">导航标题：</th>
				    <td><input type="text"  autocomplete="off" class="input-text" name="nav4title"  value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['navtitle']"> {pigcms{$skycockpit["info4data"]["navtitle"]} <else />健康养老</if>" placeholder="请输入导航标题（6个字符以内）"  maxlength="6" validate="required:true" tips="请输入导航标题" required /></td>
				 </tr>
                 <tr class="sub5field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav4f1name" placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['navinfo']['0']['xname']"> {pigcms{$skycockpit['info4data']['navinfo']['0']['xname']} <else />60岁以上</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav4f1v1"  placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['navinfo']['0']['xv1']"> {pigcms{$skycockpit['info4data']['navinfo']['0']['xv1']} </if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                  <tr class="sub5field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav4f2name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['navinfo']['1']['xname']"> {pigcms{$skycockpit['info4data']['navinfo']['1']['xname']} <else />孤寡老人</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav4f2v1" placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['navinfo']['1']['xv1']"> {pigcms{$skycockpit['info4data']['navinfo']['1']['xv1']} </if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                   <tr class="sub5field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav4f3name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['navinfo']['2']['xname']"> {pigcms{$skycockpit['info4data']['navinfo']['2']['xname']} <else />失联老人</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav4f3v1" placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['navinfo']['2']['xv1']"> {pigcms{$skycockpit['info4data']['navinfo']['2']['xv1']} </if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                  <tr class="sub5field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav4f4name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['navinfo']['3']['xname']"> {pigcms{$skycockpit['info4data']['navinfo']['3']['xname']} <else />津贴老人</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav4f4v1" placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['navinfo']['3']['xv1']"> {pigcms{$skycockpit['info4data']['navinfo']['3']['xv1']} </if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                  <tr class="sub5field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav4f5name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['navinfo']['4']['xname']"> {pigcms{$skycockpit['info4data']['navinfo']['4']['xname']} <else />独居老人</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav4f5v1"  placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['navinfo']['4']['xv1']"> {pigcms{$skycockpit['info4data']['navinfo']['4']['xv1']} </if>" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>

				 <tr>
				    <th width="160">统计图标题：</th>
				    <td><input type="text"  autocomplete="off" class="input-text" name="tj4title" placeholder="请输入统计图标题（10个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['tjtitle']"> {pigcms{$skycockpit["info4data"]["tjtitle"]} <else />社区老人服务优化比</if>" maxlength="10" validate="required:true" tips="请输入统计图标题" required /></td>
				 </tr>
                 <tr class="subtj5attr">
                    <th width="160"></th>
                    <td>
                    <span class="fieldname">字段名称：</span>
                    <div style="display: inline-block;margin-left: 136px;"></div>
                    <input type="text"  autocomplete="off" class="input-m120min attr1"  name="tj4attr1" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['tjattr'] AND $skycockpit['info4data']['tjattr']['0']['attr1']"> {pigcms{$skycockpit["info4data"]['tjattr']['0']['attr1']}</if>" maxlength="6" required />&nbsp;&nbsp;&nbsp;
                    <input type="text"  autocomplete="off" class="input-m120min attr2"  name="tj4attr2" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['tjattr'] AND $skycockpit['info4data']['tjattr']['1']['attr2']"> {pigcms{$skycockpit["info4data"]['tjattr']['1']['attr2']}</if>" maxlength="6" required />&nbsp;&nbsp;&nbsp;
                    <input type="text"  autocomplete="off" class="input-m120min attr3"  name="tj4attr3" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['tjattr'] AND $skycockpit['info4data']['tjattr']['2']['attr3']"> {pigcms{$skycockpit["info4data"]['tjattr']['2']['attr3']}</if>" maxlength="6" required />&nbsp;&nbsp;&nbsp;
                   <input type="text"  autocomplete="off" class="input-m120min attr4"   name="tj4attr4" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['tjattr'] AND $skycockpit['info4data']['tjattr']['3']['attr4']"> {pigcms{$skycockpit["info4data"]['tjattr']['3']['attr4']}</if>" maxlength="6" required />&nbsp;&nbsp;&nbsp;
                   <input type="text"  autocomplete="off" class="input-m120min attr5"   name="tj4attr5" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['tjattr'] AND $skycockpit['info4data']['tjattr']['4']['attr5']"> {pigcms{$skycockpit["info4data"]['tjattr']['4']['attr5']}</if>" maxlength="6" required />
                    <span style="color:red;"> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;最多可添加6项 </span>
                   </td>
             	 </tr>

             	 <tr class="subtj5field" data-xcount="1">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                     <php>
                        $itemCount=0;
                        if(!empty($skycockpit) && $skycockpit['info4data'] && $skycockpit['info4data']['tjinfo'] && $skycockpit['info4data']['tjinfo']['0']){
                           $itemCount=count($skycockpit['info4data']['tjinfo']);
                        }
                    </php>
                    <span>
                     <input type="text"  autocomplete="off" class="input-m120min xname"  name="tj4f1name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['tjinfo'] AND $skycockpit['info4data']['tjinfo']['0']"> {pigcms{$skycockpit['info4data']['tjinfo']['0']['xname']}</if> " maxlength="6" required />&nbsp;&nbsp;&nbsp;
                    <input type="number"  autocomplete="off" class="input-m120min xv1" name="tj4f1mv1"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['tjinfo'] AND $skycockpit['info4data']['tjinfo']['0']"> {pigcms{$skycockpit['info4data']['tjinfo']['0']['xv']['0']['xv1']}</if>"  step="1" required />&nbsp;&nbsp;&nbsp;
                    <input type="number"  autocomplete="off" class="input-m120min xv2" name="tj4f1mv2"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['tjinfo'] AND $skycockpit['info4data']['tjinfo']['0']"> {pigcms{$skycockpit['info4data']['tjinfo']['0']['xv']['1']['xv2']}</if>"  step="1" required />&nbsp;&nbsp;&nbsp;
                    <input type="number"  autocomplete="off" class="input-m120min xv3" name="tj4f1mv3"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['tjinfo'] AND $skycockpit['info4data']['tjinfo']['0']"> {pigcms{$skycockpit['info4data']['tjinfo']['0']['xv']['2']['xv3']}</if>"  step="1" required />&nbsp;&nbsp;&nbsp;
                    <input type="number"  autocomplete="off" class="input-m120min xv4" name="tj4f1mv4"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['tjinfo'] AND $skycockpit['info4data']['tjinfo']['0']"> {pigcms{$skycockpit['info4data']['tjinfo']['0']['xv']['3']['xv4']}</if>"  step="1" required />&nbsp;&nbsp;&nbsp;
                    <input type="number"  autocomplete="off" class="input-m120min xv5" name="tj4f1mv5"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['tjinfo'] AND $skycockpit['info4data']['tjinfo']['0']"> {pigcms{$skycockpit['info4data']['tjinfo']['0']['xv']['4']['xv5']}</if>"  step="1" required />
                    </span>
                    &nbsp;&nbsp;&nbsp;<span class="additem add5item" onclick="additem(5)" <if condition="$itemCount egt 6"> style="display:none;" </if> > 添加 </span>
                   </td>
				 </tr>
                <if condition="!empty($skycockpit) AND $skycockpit['info4data'] AND $skycockpit['info4data']['tjinfo']">
                 <volist name="skycockpit['info4data']['tjinfo']" id="vo" >
                    <php>
                    if($key==0){ continue; }else{
                        $xcount=$key+1;
                        
                    }</php>
             	 <tr class="subtj5field" data-xcount="{pigcms{$xcount}">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <span>
                     <input type="text"  autocomplete="off" class="input-m120min xname"  name="tj4f2name" placeholder="请输入字段名称（6个字符以内）" value="{pigcms{$vo.xname}" maxlength="6" required />&nbsp;&nbsp;&nbsp;
                    <input type="number"  autocomplete="off" class="input-m120min xv1" name="tj4f2mv1"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="{pigcms{$vo['xv']['0']['xv1']}"  step="1" required />&nbsp;&nbsp;&nbsp;
                    <input type="number"  autocomplete="off" class="input-m120min xv2" name="tj4f2mv2"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="{pigcms{$vo['xv']['1']['xv2']}"  step="1" required />&nbsp;&nbsp;&nbsp;
                    <input type="number"  autocomplete="off" class="input-m120min xv3" name="tj4f2mv3"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="{pigcms{$vo['xv']['2']['xv3']}"  step="1" required />&nbsp;&nbsp;&nbsp;
                    <input type="number"  autocomplete="off" class="input-m120min xv4" name="tj4f2mv4"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="{pigcms{$vo['xv']['3']['xv4']}"  step="1" required />&nbsp;&nbsp;&nbsp;
                    <input type="number"  autocomplete="off" class="input-m120min xv5" name="tj4f2mv5"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="{pigcms{$vo['xv']['4']['xv5']}"  step="1" required />
                    </span>
                    &nbsp;&nbsp;&nbsp;<span class="delitem" onclick="delitem(this,5)"> 删除 </span>
                   </td>
				 </tr>
                  </volist>
                </if>
				</tbody>
			   </table> 
                   <div class="btn" style="margin-top:20px;"> 
                        <a class="button" style="margin-left: 80px;" href="javascript:;" data-s_index="5" onclick="saveCurrentData(5)">保存数据</a>
                   </div>  
			</div>

             <div id="qitianscreen6" class="qitian_screen hide"> 
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%">
				<tbody>
                   <tr>
				    <th width="160">导航标题：</th>
				    <td><input type="text"  autocomplete="off" class="input-text" name="nav5title"  value="<if condition="!empty($skycockpit) AND $skycockpit['info5data'] AND $skycockpit['info5data']['navtitle']"> {pigcms{$skycockpit["info5data"]["navtitle"]} <else />社区环境</if>" maxlength="6" validate="required:true" tips="请输入导航标题" placeholder="请输入导航标题（6个字符以内）" required /></td>
				 </tr>
                 <tr class="sub6field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav5f1name" placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info5data'] AND $skycockpit['info5data']['navinfo']"> {pigcms{$skycockpit['info5data']['navinfo']['0']['xname']} <else />环境噪音</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav5f1v1" placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info5data'] AND $skycockpit['info5data']['navinfo']"> {pigcms{$skycockpit['info5data']['navinfo']['0']['xv1']}</if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                      <input type="text"  autocomplete="off" class="input-min xv2" name="nav5f1v2"  placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info5data'] AND $skycockpit['info5data']['navinfo']"> {pigcms{$skycockpit['info5data']['navinfo']['0']['xv2']}</if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>

				 <tr>
				    <th width="160">统计图标题：</th>
				    <td><input type="text"  autocomplete="off" class="input-text" name="tj5title"  value="<if condition="!empty($skycockpit) AND $skycockpit['info5data'] AND $skycockpit['info5data']['tjtitle']"> {pigcms{$skycockpit["info5data"]["tjtitle"]} <else />环境检测分类</if>" placeholder="请输入统计图标题（10个字符以内）"  maxlength="10" validate="required:true" tips="请输入统计图标题" required /></td>
				 </tr>
             	 <tr class="subtj6field" data-xcount="1">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="tj5f1name"  placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info5data'] AND $skycockpit['info5data']['tjinfo'] AND $skycockpit['info5data']['tjinfo']['0']"> {pigcms{$skycockpit['info5data']['tjinfo']['0']['xname']}</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv1" name="tj5f1mv1"  value="<if condition="!empty($skycockpit) AND $skycockpit['info5data'] AND $skycockpit['info5data']['tjinfo'] AND $skycockpit['info5data']['tjinfo']['0']"> {pigcms{$skycockpit['info5data']['tjinfo']['0']['xv1']}</if>" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span> <span class="additem" onclick="additem(6)"> 添加 </span>
                   </td>
				 </tr>
                 <if condition="!empty($skycockpit) AND $skycockpit['info5data'] AND $skycockpit['info5data']['tjinfo']">
                 <volist name="skycockpit['info5data']['tjinfo']" id="vo" >
                    <php>
                    if($key==0){ continue; }else{
                        $xcount=$key+1;
                        
                    }</php>
                 <tr class="subtj6field" data-xcount="{pigcms{$xcount}">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="tj5f2name" placeholder="请输入字段名称（6个字符以内）"  value="{pigcms{$vo.xname}" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv1" name="tj5f2mv1"  value="{pigcms{$vo.xv1}" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span> <span class="delitem" onclick="delitem(this,6)"> 删除 </span>
                   </td>
				 </tr>
                  </volist>
                </if>
             
				</tbody>
			   </table> 
                   <div class="btn" style="margin-top:20px;"> 
                        <a class="button" style="margin-left: 80px;" href="javascript:;" data-s_index="6"  onclick="saveCurrentData(6)">保存数据</a>
                   </div> 
			</div>

             <div id="qitianscreen7" class="qitian_screen hide"> 
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%">
				<tbody>
				 <tr>
				    <th width="160">导航标题：</th>
				    <td><input type="text"  autocomplete="off" class="input-text" name="nav6title"  value="<if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navtitle']"> {pigcms{$skycockpit["info6data"]["navtitle"]} <else />事件列表</if>" maxlength="6" validate="required:true" tips="请输入导航标题"  placeholder="请输入导航标题（6个字符以内）" required /></td>
				 </tr>

                 <tr class="sub7fieldleft">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="navleftname"  placeholder="请输入字段名称（6个字以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navleft']"> {pigcms{$skycockpit['info6data']['navleft']['0']['xname']} <else />智慧安防</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="navleftxv1"  placeholder="请输入（5个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navleft']"> {pigcms{$skycockpit['info6data']['navleft']['0']['xv1']}<else />23456</if>"  maxlength="5" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>

             	 <tr class="sub7field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav6f1name"  placeholder="请输入字段名称"  value="<if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navinfo']"> {pigcms{$skycockpit['info6data']['navinfo']['0']['xname']} <else />总事件数</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav6f1v1"  placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navinfo']"> {pigcms{$skycockpit['info6data']['navinfo']['0']['xv1']}</if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                 <tr class="sub7field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav6f2name" placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navinfo']"> {pigcms{$skycockpit['info6data']['navinfo']['1']['xname']} <else />事件数</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav6f2v1"   placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navinfo']"> {pigcms{$skycockpit['info6data']['navinfo']['1']['xv1']}</if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                  <tr class="sub7field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav6f3name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navinfo']"> {pigcms{$skycockpit['info6data']['navinfo']['2']['xname']} <else />处置中</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav6f3v1"  placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navinfo']"> {pigcms{$skycockpit['info6data']['navinfo']['2']['xv1']}</if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                   <tr class="sub7field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav6f4name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navinfo']"> {pigcms{$skycockpit['info6data']['navinfo']['3']['xname']} <else />已处置</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav6f4v1"  placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navinfo']"> {pigcms{$skycockpit['info6data']['navinfo']['3']['xv1']}</if>"  maxlength="6" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                  <tr class="sub7field" data-xcount="5">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav6f5name" placeholder="请输入字段名称（6个字符以内）" value="<if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navinfo']"> {pigcms{$skycockpit['info6data']['navinfo']['4']['xname']}</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav6f5v1"  placeholder="请输入"  value="<if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navinfo']"> {pigcms{$skycockpit['info6data']['navinfo']['4']['xv1']}</if>"  maxlength="6" required /><span class="additem" onclick="additem(7)"> 添加 </span>
                   </td>
				 </tr>
                <if condition="!empty($skycockpit) AND $skycockpit['info6data'] AND $skycockpit['info6data']['navinfo']">
                 <volist name="skycockpit['info6data']['navinfo']" id="vo" >
                    <php>
                    if($key<5){ continue; }else{
                        $xcount=$key+1;
                        
                    }</php>
                <tr class="sub7field" data-xcount="{pigcms{$xcount}">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav6f6name" placeholder="请输入字段名称（6个字符以内）" value="{pigcms{$vo.xname}" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="text"  autocomplete="off" class="input-min xv1" name="nav6f6v1"  placeholder="请输入"  value="{pigcms{$vo.xv1}"  maxlength="6" required /><span class="delitem" onclick="delitem(this,7)"> 删除 </span>
                   </td>
				 </tr>
                   </volist>
                </if>
				</tbody>
			   </table> 
                   <div class="btn" style="margin-top:20px;"> 
                        <a class="button" style="margin-left: 80px;" href="javascript:;" data-s_index="7"  onclick="saveCurrentData(7)">保存数据</a>
                   </div>  
			</div>

            <div id="qitianscreen8" class="qitian_screen hide"> 
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%">
				<tbody>
				 <tr>
				    <th width="160">导航标题：</th>
				    <td><input type="text"  autocomplete="off" class="input-text" name="nav7title"  value="<if condition="!empty($skycockpit) AND $skycockpit['info7data'] AND $skycockpit['info7data']['navtitle']"> {pigcms{$skycockpit["info7data"]["navtitle"]} <else />物联感知设备</if>" placeholder="请输入导航标题（6个字符以内）" maxlength="6" validate="required:true" tips="请输入导航标题" required /></td>
				 </tr>
             	 <tr class="sub8field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav7f1name"  placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info7data'] AND $skycockpit['info7data']['navinfo']"> {pigcms{$skycockpit['info7data']['navinfo']['0']['xname']} <else />设备总数</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="number"  autocomplete="off" class="input-min xv1" name="nav7f1v1"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info7data'] AND $skycockpit['info7data']['navinfo']"> {pigcms{$skycockpit['info7data']['navinfo']['0']['xv1']}</if>"  step="1" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                 <tr class="sub8field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav7f2name" placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info7data'] AND $skycockpit['info7data']['navinfo']"> {pigcms{$skycockpit['info7data']['navinfo']['1']['xname']} <else />报警数</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                      <input type="number"  autocomplete="off" class="input-min xv1" name="nav7f2v1"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info7data'] AND $skycockpit['info7data']['navinfo']"> {pigcms{$skycockpit['info7data']['navinfo']['1']['xv1']}</if>"  step="1" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>
                 <tr class="sub8field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav7f3name" placeholder="请输入字段名称（6个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info7data'] AND $skycockpit['info7data']['navinfo']"> {pigcms{$skycockpit['info7data']['navinfo']['2']['xname']} <else />故障数</if>" maxlength="6" required /> &nbsp;&nbsp;&nbsp;
                     <input type="number"  autocomplete="off" class="input-min xv1" name="nav7f3v1"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value="<if condition="!empty($skycockpit) AND $skycockpit['info7data'] AND $skycockpit['info7data']['navinfo']"> {pigcms{$skycockpit['info7data']['navinfo']['2']['xv1']}</if>"  step="1" required /> &nbsp;&nbsp;&nbsp; 
                   </td>
				 </tr>

				</tbody>
			   </table> 
                   <div class="btn" style="margin-top:20px;"> 
                        <a class="button" style="margin-left: 80px;" href="javascript:;" data-s_index="8"  onclick="saveCurrentData(8)">保存数据</a>
                   </div>  
			</div>

            <div id="qitianscreen9" class="qitian_screen hide"> 
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%">
				<tbody>
				 <tr>
				    <th width="160">导航标题：</th>
				    <td><input type="text"  autocomplete="off" class="input-text" name="nav8title"  value="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navtitle']"> {pigcms{$skycockpit["info8data"]["navtitle"]} <else />视频监控</if>" maxlength="6" validate="required:true" tips="请输入导航标题" placeholder="请输入导航标题（6个字符以内）" required /></td>
				 </tr>
             	 <tr class="sub9field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav8f1name"  placeholder="请输入字段名称8个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo']"> {pigcms{$skycockpit['info8data']['navinfo']['0']['xname']}<else />法姬娜大厦A座</if>" maxlength="8" required /> &nbsp;&nbsp;&nbsp;
                     <div style="display: inline-block;">
                     <!--
                        <input type="hidden" name="field0_img" value="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo'] AND $skycockpit['info8data']['navinfo']['0']['xv1']"> {pigcms{$skycockpit['info8data']['navinfo']['0']['xv1']}</if>" id="field0_img" class="img xv1" name="nav8f1v1" />
                        <input style="padding: 5px 15px;line-height: 20px;cursor: pointer;" class="btn btn-primary" type="button" id="field0_img_upload" value="上传图片" />
                        <img src="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo'] AND $skycockpit['info8data']['navinfo']['0']['xv1']"> {pigcms{$skycockpit['info8data']['navinfo']['0']['xv1']}</if>" id="field0_img_src" <if condition="empty($skycockpit) || empty($skycockpit['info8data']) || empty($skycockpit['info8data']['navinfo']) || empty($skycockpit['info8data']['navinfo']['0']['xv1'])"> class="hide" </if> style="width: 60px;margin-left: 10px;" >
                        -->
                        <span class="fieldname">设备名称：</span>
                        <input type="text" name="field0_img_name" id="field0_img_name" value="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo']"> {pigcms{$skycockpit['info8data']['navinfo']['0']['xv2']}</if>" class="input-text xv2 img" disabled="disabled" placeholder="请点击右侧选择视频" data-ismonitor="1" data-camera_sn="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo']"> {pigcms{$skycockpit['info8data']['navinfo']['0']['camera_sn']}</if>" data-look_url_type="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo']"> {pigcms{$skycockpit['info8data']['navinfo']['0']['look_url_type']}</if>" >&nbsp;&nbsp;

                        <input type="text" name="field0_img"  style="display: none;" disabled="disabled" class="input-text xv1 img" value="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo']"> {pigcms{$skycockpit['info8data']['navinfo']['0']['xv1']}</if>" id="field0_img" data-ismonitor="1" />&nbsp;&nbsp;<a href="JavaScript:;" onclick="monitorvideo('field0_img')" style="color: #0699e5;text-decoration: underline;">点击选择视频</a>
                     </div>
                   </td>
				 </tr>
                 <tr  class="sub9field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                        <input type="text"  autocomplete="off" class="input-min xname" name="nav8f2name"  placeholder="请输入字段名称（8个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo']"> {pigcms{$skycockpit['info8data']['navinfo']['1']['xname']}<else />法姬娜大厦B座</if>" maxlength="8" required /> &nbsp;&nbsp;&nbsp;
                         <div style="display: inline-block;">
                            <!--
                            <input type="hidden" name="field1_img" value="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo'] AND $skycockpit['info8data']['navinfo']['1']['xv1']"> {pigcms{$skycockpit['info8data']['navinfo']['1']['xv1']}</if>" id="field1_img" class="img xv1" name="nav8f2v1" />
                            <input style="padding: 5px 15px;line-height: 20px;cursor: pointer;" class="btn btn-primary" type="button" id="field1_img_upload" value="上传图片" />
                            <img src="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo'] AND $skycockpit['info8data']['navinfo']['1']['xv1']"> {pigcms{$skycockpit['info8data']['navinfo']['1']['xv1']}</if>" id="field1_img_src" <if condition="empty($skycockpit) || empty($skycockpit['info8data']) || empty($skycockpit['info8data']['navinfo']) || empty($skycockpit['info8data']['navinfo']['1']['xv1'])"> class="hide" </if> style="width: 60px;margin-left: 10px;" >
                            -->
                            <span class="fieldname">设备名称：</span>
                            <input type="text" name="field1_img_name" id="field1_img_name" value="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo']"> {pigcms{$skycockpit['info8data']['navinfo']['1']['xv2']}</if>" class="input-text xv2 img"  disabled="disabled" placeholder="请点击右侧选择视频" data-ismonitor="1" data-camera_sn="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo']"> {pigcms{$skycockpit['info8data']['navinfo']['1']['camera_sn']}</if>" data-look_url_type="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo']"> {pigcms{$skycockpit['info8data']['navinfo']['1']['look_url_type']}</if>" >&nbsp;&nbsp;
                            <input type="text" style="display: none;" disabled="disabled" name="field1_img" class="input-text xv1 img" value="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo']"> {pigcms{$skycockpit['info8data']['navinfo']['1']['xv1']}</if>" id="field1_img"  data-ismonitor="1" />&nbsp;&nbsp;<a href="JavaScript:;" onclick="monitorvideo('field1_img')" style="color: #0699e5;text-decoration: underline;">点击选择视频</a>
                         </div>
                   </td>
				 </tr>
                 <tr  class="sub9field">
				    <th width="160"></th>
				    <td><span class="fieldname">字段名称：</span>
                    <input type="text"  autocomplete="off" class="input-min xname" name="nav8f3name" placeholder="请输入字段名称（8个字符以内）"  value="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo']"> {pigcms{$skycockpit['info8data']['navinfo']['2']['xname']}<else />人脸识别率</if>" maxlength="8" required /> &nbsp;&nbsp;&nbsp;
                     <span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv1" name="nav8f3v1"  value="<if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['navinfo']"> {pigcms{$skycockpit['info8data']['navinfo']['2']['xv1']}</if>" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span>
                   </td>
				 </tr>
             	 <tr>
				  <th width="160">人脸图片：</th>
				  <td id="face_img_td" style="height: 100px;">
                       <input style="padding: 5px 15px;line-height: 20px;cursor: pointer;" class="btn btn-primary" type="button" id="face_img_upload" value="上传图片" />
                          <if condition="!empty($skycockpit) AND $skycockpit['info8data'] AND $skycockpit['info8data']['faceimg']">
                            <volist name="skycockpit['info8data']['faceimg']" id="vo" >
                                <span class="face_img_span"><img src="{pigcms{$vo}" data-xsrc="{pigcms{$vo}" class="face_img" style="width: 65px;margin-left: 15px;" ><span class="imgclose" onclick="delthatimg(this)">X</span></span>
                               </volist>
                            </if>
                       </td>
				 </tr>
				</tbody>
			   </table> 
                   <div class="btn" style="margin-top:20px;"> 
                        <a class="button" style="margin-left: 80px;" href="javascript:;" data-s_index="9"  onclick="saveCurrentData(9)">保存数据</a>
                   </div> 
			</div>
           </form>
		</div>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript">
        KindEditor.ready(function(K) {
        var editor = K.editor({
            allowFileManager : true
        });
        K('#bg_img_upload').click(function() {
            editor.uploadJson = "{pigcms{:U('Village/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#bg_img_src');
                        img.attr("src",url);
                        img.show();
                        img.removeClass('hide');
                        K("#bg_img").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });

       K('#field0_img_upload').click(function() {
            editor.uploadJson = "{pigcms{:U('Village/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#field0_img_src');
                        img.attr("src",url);
                        img.show();
                        img.removeClass('hide');
                        K("#field0_img").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });

       K('#field1_img_upload').click(function() {
            editor.uploadJson = "{pigcms{:U('Village/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#field1_img_src');
                        img.attr("src",url);
                        img.show();
                        img.removeClass('hide');
                        K("#field1_img").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });
       K('#face_img_upload').click(function() {
            var face_img_count=$('#face_img_td .face_img').size();
            if(face_img_count>=5){
                layer.alert('最多可上传5张图片!', {icon: 2,offset:['120px','40%']});
                 return false;
            }
            editor.uploadJson = "{pigcms{:U('Village/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#face_img_td');
                        img.append('<span class="face_img_span"><img src="'+url+'" data-xsrc="'+url+'" class="face_img" style="width: 65px;margin-left: 15px;" ><span class="imgclose" onclick="delthatimg(this)">X</span></span>')
                        editor.hideDialog();
                    }
                });
            });
        });

    });
       function limitmaxlength(len,obj){
          var xvalue=$(obj).val()||'';
          if(xvalue && xvalue.length>len){
              xvalue=xvalue.slice(0,len)
              $(obj).val(xvalue);
          }
       }
        $('#qitianscreennav a').click(function(){
            var navindex=$(this).data('navindex');
            $('#qitianscreennav a').removeClass('on');
            $(this).addClass('on');
            $('.qitian_screen').addClass('hide');
            $('#qitianscreen'+navindex).removeClass('hide');
        });

        function delthatimg(obj){
            $(obj).parent('.face_img_span').remove();
        }

        function delitem(obj,dindex){
            $(obj).parent('td').parent('tr').remove();
            if(dindex!=undefined && dindex==4){
                var subtj4count=$('#qitianscreen4 .subtj4field').size();
                console.log(subtj4count)
                if(subtj4count<6){
                    $('#qitianscreen4 .add4item').show();
                }
            }
            if(dindex!=undefined && dindex==5){
                var subtj5count=$('#qitianscreen5 .subtj5field').size();
                if(subtj5count<6){
                    $('#qitianscreen5 .add5item').show();
                }
            }
        }
        function additem(index){
            var xhtml='';
            if(index==3){
                var xcount=$('#qitianscreen3 .subtj3field:last').data('xcount')||0;
                    xcount=xcount+1;
                xhtml='<tr class="subtj3field" data-xcount="'+xcount+'">'+
				    '<th width="160"></th>'+
				    '<td><span class="fieldname">字段名称：</span>'+
                    '<input type="text"  autocomplete="off" class="input-min xname" name="tj2f'+xcount+'name" placeholder="请输入字段名称（6个字符以内）"  value="" maxlength="6" required /> &nbsp;&nbsp;&nbsp;&nbsp; '+
                     '<span><input type="number" min="0" oninput="limitmaxlength(6,this)" max="100" class="input-min xv1" name="tj2f'+xcount+'v1"  value="" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span> <span class="delitem" onclick="delitem(this,3)"> 删除 </span></td>'+
				 '</tr>';
            }else if(index==4){
                    var subtj4count=$('#qitianscreen4 .subtj4field').size();
                    if(subtj4count>=5){
                        $('#qitianscreen4 .add4item').hide();
                    }
                    if(subtj4count>=6){
                        layer.alert('最多可以添加加6个！');
                        return false;
                    }
			        var xcount=$('#qitianscreen4 .subtj4field:last').data('xcount')||0;
                    xcount=xcount+1;
                   xhtml='<tr class="subtj4field" data-xcount="'+xcount+'">'+
				    '<th width="160"></th>'+
				    '<td><span class="fieldname">字段名称：</span>'+
                    '<input type="text"  autocomplete="off" class="input-min xname" name="tj3f'+xcount+'name" placeholder="请输入字段名称（6个字符以内）"  value="" maxlength="6" required /> &nbsp;&nbsp;&nbsp;&nbsp; <div class="screen4div">'+
                     '&nbsp;&nbsp;<span><input type="number" min="0" oninput="limitmaxlength(8,this)" max="99999999" class="input-mmin xv1" name="tj3f'+xcount+'v1[]"  value="" placeholder="请输入数量值"   step="0.01" autocomplete="off" required/></span>'+
                     '</div>'+
                     '<div class="screen4div">'+
                     '&nbsp;&nbsp;<span><input type="number" min="0" oninput="limitmaxlength(8,this)" max="99999999" class="input-mmin xv1" name="tj3f'+xcount+'v1[]"  value="" placeholder="请输入数量值"   step="0.01" autocomplete="off" required/></span>'+ 
                     '</div>'+
                    '<div class="screen4div">'+
                     '&nbsp;&nbsp;<span><input type="number" min="0" oninput="limitmaxlength(8,this)" max="99999999" class="input-mmin xv1" name="tj3f'+xcount+'v1[]"  value="" placeholder="请输入数量值"   step="0.01" autocomplete="off" required/></span>'+ 
                     '</div>'+
                   '<div class="screen4div">'+
                     '&nbsp;&nbsp;<span><input type="number" min="0" oninput="limitmaxlength(8,this)" max="99999999" class="input-mmin xv1" name="tj3f'+xcount+'v1[]"  value="" placeholder="请输入数量值"   step="0.01" autocomplete="off" required/></span>'+
                     '</div>'+
                     '<span class="delitem" onclick="delitem(this,4)"> 删除 </span>'+
                   '</td></tr>';
            }else if(index==5){
                    var subtj4count=$('#qitianscreen5 .subtj5field').size();
                    if(subtj4count>=5){
                        $('#qitianscreen5 .add5item').hide();
                    }
                    if(subtj4count>=6){
                        layer.alert('最多可以添加加6个！');
                        return false;
                    }
					var xcount=$('#qitianscreen5 .subtj5field:last').data('xcount')||0;
                    xcount=xcount+1;
                   xhtml='<tr class="subtj5field" data-xcount="'+xcount+'">'+
				    '<th width="160"></th>'+
				    '<td><span class="fieldname">字段名称：</span>'+
                    '<span>'+
                     '<input type="text"  autocomplete="off" class="input-m120min xname"  name="tj4f'+xcount+'name" placeholder="请输入字段名称（6个字符以内）" value="" maxlength="6" required />&nbsp;&nbsp;&nbsp;&nbsp; '+
                    '<input type="number"  autocomplete="off" class="input-m120min xv1" name="tj4f'+xcount+'mv1"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value=""  step="1" required />&nbsp;&nbsp;&nbsp; '+
                    '<input type="number"  autocomplete="off" class="input-m120min xv2" name="tj4f'+xcount+'mv2"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value=""  step="1" required />&nbsp;&nbsp;&nbsp; '+
                    '<input type="number"  autocomplete="off" class="input-m120min xv3" name="tj4f'+xcount+'mv3"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value=""  step="1" required />&nbsp;&nbsp;&nbsp; '+
                    '<input type="number"  autocomplete="off" class="input-m120min xv4" name="tj4f'+xcount+'mv4"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value=""  step="1" required />&nbsp;&nbsp;&nbsp; '+
                    '<input type="number"  autocomplete="off" class="input-m120min xv5" name="tj4f'+xcount+'mv5"  min="0" oninput="limitmaxlength(8,this)" max="99999999" placeholder="请输入数量"  value=""  step="1" required />'+
                    '</span>'+
                    '&nbsp;&nbsp;&nbsp; <span class="delitem" onclick="delitem(this,5)"> 删除 </span>'+
                   '</td></tr>';
            }else if(index==6){
				var xcount=$('#qitianscreen6 .subtj6field:last').data('xcount')||0;
                    xcount=xcount+1;
                   xhtml='<tr class="subtj6field" data-xcount="'+xcount+'">'+
				    '<th width="160"></th>'+
				    '<td><span class="fieldname">字段名称：</span>'+
                    '<input type="text"  autocomplete="off" class="input-min xname" name="tj5f'+xcount+'name" placeholder="请输入字段名称（6个字符以内）"  value="" maxlength="6" required /> &nbsp;&nbsp;&nbsp; '+
                     '<span><input type="number" min="0" max="100" oninput="limitmaxlength(6,this)" class="input-min xv1" name="tj5f'+xcount+'mv1"  value="" placeholder="请输入百分"   step="0.01" autocomplete="off" required/><span class="perfont">%</span></span> <span class="delitem" onclick="delitem(this,6)"> 删除 </span>'+
                   '</td></tr>';
            }else if(index==7){
				var xcount=$('#qitianscreen7 .sub7field:last').data('xcount')||0;
                    xcount=xcount+1;
                   xhtml='<tr class="sub7field" data-xcount="'+xcount+'">'+
				    '<th width="160"></th>'+
				    '<td><span class="fieldname">字段名称：</span>'+
                    '<input type="text"  autocomplete="off" class="input-min xname" name="nav6f'+xcount+'name" placeholder="请输入字段名称（6个字符以内）" value="" maxlength="6" required /> &nbsp;&nbsp;&nbsp; '+
                    ' <input type="text"  autocomplete="off" class="input-min xv1" name="nav6f'+xcount+'v1"  placeholder="请输入"  value=""  maxlength="6" required /><span class="delitem" onclick="delitem(this,7)"> 删除 </span>'+
                   '</td></tr>';
            }
            $('#qitianscreen'+index+' tbody').append(xhtml);
        }

     function  saveCurrentData(s_index){
          var postArr={};
          var ishaveerror=0;
          var nameArr=new Array();
          if(s_index==1){
               postArr.xtype=0;
               var screentitle=$('#qitianscreen1 input[name="screentitle"]').val()||'';
               postArr.screentitle=screentitle;
               var bg_img=$('#qitianscreen1 input[name="bg_img"]').val()||'';
               postArr.bg_img=bg_img;
               var province_id=$('#choose_provincess').val()||0;
               var city_id=$('#choose_cityss').val()||0;
               if(province_id<=0 || city_id<=0){
                   layer.alert('请选择选择一个城市！');
                   return false;
               }
               postArr.province_id=province_id;
               postArr.city_id=city_id;
          }else if(s_index==2){
               postArr.xtype=1;
               var nav1title=$('#qitianscreen2 input[name="nav1title"]').val()||'';
               postArr.navtitle=nav1title;
               postArr.navinfo=new Array();
               $("#qitianscreen2 .subfield").each(function(ii){
                   var xname=$(this).find('.xname').val()||'';
                   xname=$.trim(xname);
                   if(!xname || xname.length<1){
                        layer.alert('请填写字段名称！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   if(xname &&  $.inArray(xname,nameArr)>-1){
                        layer.alert('有字段名称重复了，请检查！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   nameArr.push(xname);
                   var xv1= $(this).find('.xv1').val()||'';
                   var xv2=  $(this).find('.xv2').val()||'';
                   postArr.navinfo.push({xname:xname,xv1:xv1,xv2:xv2});
               });
          }else if(s_index==3){
                   postArr.xtype=2;
               var nav2title=$('#qitianscreen3 input[name="nav2title"]').val()||'';
               postArr.navtitle=nav2title;
               postArr.navinfo=new Array();
               $("#qitianscreen3 .sub3field").each(function(ii){
                   var xname=$(this).find('.xname').val()||'';
                   xname=$.trim(xname);
                   if(!xname || xname.length<1){
                        layer.alert('请填写字段名称！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   if(xname &&  $.inArray(xname,nameArr)>-1){
                        layer.alert('有字段名称重复了，请检查！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   nameArr.push(xname);
                   var xv1= $(this).find('.xv1').val()||'';
                   postArr.navinfo.push({xname:xname,xv1:xv1});
               });
               var tj2title=$('#qitianscreen3 input[name="tj2title"]').val()||'';
               postArr.tjtitle=tj2title;
               postArr.tjinfo=new Array();
               $("#qitianscreen3 .subtj3field").each(function(ii){
                   var xname=$(this).find('.xname').val()||'';
                   xname=$.trim(xname);
                   if(!xname || xname.length<1){
                        layer.alert('请填写字段名称！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   if(xname &&  $.inArray(xname,nameArr)>-1){
                        layer.alert('有字段名称重复了，请检查！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   nameArr.push(xname);
                   var xv1= $(this).find('.xv1').val()||'';
                   postArr.tjinfo.push({xname:xname,xv1:xv1});
               });
               
          }else if(s_index==4){
            postArr.xtype=3;
            var nav3title=$('#qitianscreen4 input[name="nav3title"]').val()||'';
               postArr.navtitle=nav3title;
               postArr.navinfo=new Array();
               $("#qitianscreen4 .sub4field").each(function(ii){
                   var xname=$(this).find('.xname').val()||'';
                   xname=$.trim(xname);
                   if(!xname || xname.length<1){
                        layer.alert('请填写字段名称！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   if(xname &&  $.inArray(xname,nameArr)>-1){
                        layer.alert('有字段名称重复了，请检查！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   nameArr.push(xname);
                   var xv1= $(this).find('.xv1').val()||'';
                   postArr.navinfo.push({xname:xname,xv1:xv1});
               });
               var tj3title=$('#qitianscreen4 input[name="tj3title"]').val()||'';
               var iserror=0;
               postArr.tjtitle=tj3title;
               postArr.xmonth=new Array();
               var xmonthArr=new Array();
               $("#qitianscreen4 .xmonth").each(function(ii){
                    var xv1=$(this).val()||1;
                    xv1=parseInt(xv1);
                    if(xmonthArr.length>0 && $.inArray(xv1,xmonthArr)>-1){
                          iserror=1;
                          layer.alert('月份设置有重复，请合理设置');
                          return false;
                    }
                    xmonthArr.push(xv1);
                    postArr.xmonth.push(xv1);
               });
               if(iserror==1){
                   return false;
               }
               postArr.tjinfo=new Array();
               
               $("#qitianscreen4 .subtj4field").each(function(ii){
                   var xname=$(this).find('.xname').val()||'';
                   xname=$.trim(xname);
                   if(!xname || xname.length<1){
                        layer.alert('请填写字段名称！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   if(xname &&  $.inArray(xname,nameArr)>-1){
                        layer.alert('有字段名称重复了，请检查！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   nameArr.push(xname);
                   var xv=new Array();
                    $(this).find('.screen4div').each(function(jj){
                          var xv1=$(this).find('.xv1').val()||0;
                          xv1=parseInt(xv1);
                          xv.push({xv1:xv1});
                    })
                       // console.log('iserror',iserror,'xv1Arr',xv1Arr);
                   postArr.tjinfo.push({xname:xname,xv:xv});
               });
               
          }else if(s_index==5){
                postArr.xtype=4;
            var nav4title=$('#qitianscreen5 input[name="nav4title"]').val()||'';
               postArr.navtitle=nav4title;
               postArr.navinfo=new Array();
               $("#qitianscreen5 .sub5field").each(function(ii){
                   var xname=$(this).find('.xname').val()||'';
                   xname=$.trim(xname);
                   if(!xname || xname.length<1){
                        layer.alert('请填写字段名称！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   if(xname &&  $.inArray(xname,nameArr)>-1){
                        layer.alert('有字段名称重复了，请检查！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   nameArr.push(xname);
                   var xv1= $(this).find('.xv1').val()||'';
                   postArr.navinfo.push({xname:xname,xv1:xv1});
               });
            var tj4title=$('#qitianscreen5 input[name="tj4title"]').val()||'';
               postArr.tjtitle=tj4title;
               postArr.tjattr=new Array();
               var attr1=$('#qitianscreen5 .subtj5attr .attr1').val()||'';
               postArr.tjattr.push({attr1:attr1});
               var attr2=$('#qitianscreen5 .subtj5attr .attr2').val()||'';
               postArr.tjattr.push({attr2:attr2});
               var attr3=$('#qitianscreen5 .subtj5attr .attr3').val()||'';
               postArr.tjattr.push({attr3:attr3});
               var attr4=$('#qitianscreen5 .subtj5attr .attr4').val()||'';
               postArr.tjattr.push({attr4:attr4});
               var attr5=$('#qitianscreen5 .subtj5attr .attr5').val()||'';
               postArr.tjattr.push({attr5:attr5});

               postArr.tjinfo=new Array();
               $("#qitianscreen5 .subtj5field").each(function(ii){
                   var xname=$(this).find('.xname').val()||'';
                    xname=$.trim(xname);
                   if(!xname || xname.length<1){
                        layer.alert('请填写字段名称。');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   if(xname &&  $.inArray(xname,nameArr)>-1){
                        layer.alert('有字段名称重复了，请检查！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   nameArr.push(xname);
                   var xv=new Array();
                   var xv1= $(this).find('.xv1').val()||'';
                   var xv2= $(this).find('.xv2').val()||'';
                   var xv3= $(this).find('.xv3').val()||'';
                   var xv4= $(this).find('.xv4').val()||'';
                   var xv5= $(this).find('.xv5').val()||'';
                   xv.push({xv1:xv1});
                   xv.push({xv2:xv2});
                   xv.push({xv3:xv3});
                   xv.push({xv4:xv4});
                   xv.push({xv5:xv5});
                   postArr.tjinfo.push({xname:xname,xv:xv});
               });
          }else if(s_index==6){
                postArr.xtype=5;
            var nav5title=$('#qitianscreen6 input[name="nav5title"]').val()||'';
               postArr.navtitle=nav5title;
               postArr.navinfo=new Array();
              $("#qitianscreen6 .sub6field").each(function(ii){
                   var xname=$(this).find('.xname').val()||'';
                   xname=$.trim(xname);
                   if(!xname || xname.length<1){
                        layer.alert('请填写字段名称！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   if(xname &&  $.inArray(xname,nameArr)>-1){
                        layer.alert('有字段名称重复了，请检查！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   nameArr.push(xname);
                   var xv1= $(this).find('.xv1').val()||'';
                   var xv2= $(this).find('.xv2').val()||'';
                   postArr.navinfo.push({xname:xname,xv1:xv1,xv2:xv2});
               });
           var tj5title=$('#qitianscreen6 input[name="tj5title"]').val()||'';
               postArr.tjtitle=tj5title;
               postArr.tjinfo=new Array();
               $("#qitianscreen6 .subtj6field").each(function(ii){
                   var xname=$(this).find('.xname').val()||'';
                    xname=$.trim(xname);
                   if(!xname || xname.length<1){
                        layer.alert('请填写字段名称！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   if(xname &&  $.inArray(xname,nameArr)>-1){
                        layer.alert('有字段名称重复了，请检查！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   nameArr.push(xname);
                   var xv1= $(this).find('.xv1').val()||'';
                   postArr.tjinfo.push({xname:xname,xv1:xv1});
               });
          }else if(s_index==7){
                postArr.xtype=6;
            var nav6title=$('#qitianscreen7 input[name="nav6title"]').val()||'';
               postArr.navtitle=nav6title;
               postArr.navinfo=new Array();
               $("#qitianscreen7 .sub7field").each(function(ii){
                   var xname=$(this).find('.xname').val()||'';
                   xname=$.trim(xname);
                   if(!xname || xname.length<1){
                        layer.alert('请填写字段名称！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                  if(xname &&  $.inArray(xname,nameArr)>-1){
                        layer.alert('有字段名称重复了，请检查！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   nameArr.push(xname);
                   var xv1= $(this).find('.xv1').val()||'';
                   postArr.navinfo.push({xname:xname,xv1:xv1});
               });
               postArr.navleft=new Array();
               $("#qitianscreen7 .sub7fieldleft").each(function(ii){
                   var xname=$(this).find('.xname').val()||'';
                   xname=$.trim(xname);
                   if(!xname || xname.length<1){
                        layer.alert('请填写字段名称！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   if(xname &&  $.inArray(xname,nameArr)>-1){
                        layer.alert('有字段名称重复了，请检查！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   nameArr.push(xname);
                   var xv1= $(this).find('.xv1').val()||'';
                   postArr.navleft.push({xname:xname,xv1:xv1});
               });
               
          }else if(s_index==8){
                postArr.xtype=7;
            var nav7title=$('#qitianscreen8 input[name="nav7title"]').val()||'';
               postArr.navtitle=nav7title;
               postArr.navinfo=new Array();
               $("#qitianscreen8 .sub8field").each(function(ii){
                   var xname=$(this).find('.xname').val()||'';
                   xname=$.trim(xname);
                   if(!xname || xname.length<1){
                        layer.alert('请填写字段名称！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                  if(xname &&  $.inArray(xname,nameArr)>-1){
                        layer.alert('有字段名称重复了，请检查！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   nameArr.push(xname);
                   var xv1= $(this).find('.xv1').val()||'';
                   postArr.navinfo.push({xname:xname,xv1:xv1});
               });
          }else if(s_index==9){
                postArr.xtype=8;
            var nav8title=$('#qitianscreen9 input[name="nav8title"]').val()||'';
               postArr.navtitle=nav8title;
               postArr.navinfo=new Array();
               $("#qitianscreen9 .sub9field").each(function(ii){
                   var xname=$(this).find('.xname').val()||'';
                   xname=$.trim(xname);
                   var xv1= $(this).find('.xv1').val()||'';
                   var xv2= $(this).find('.xv2').val()||'';
                   var is_img=0;
                   var camera_sn=$(this).find('.xv2').data('camera_sn')||'';
                   var look_url_type=$(this).find('.xv2').data('look_url_type')||'';
                   var ismonitor=$(this).find('.xv1').data('ismonitor');
                   if(ismonitor!=undefined && ismonitor=="1"){
                        is_img=1;
                   }

                   if(is_img==0 && (!xname || xname.length<1)){
                        layer.alert('请填写字段名称！');
                        $(this).find('.xname').focus();
                        ishaveerror=1;
                        return false;
                   }
                   postArr.navinfo.push({xname:xname,xv1:xv1,xv2:xv2,is_img:is_img,camera_sn:camera_sn,look_url_type:look_url_type});
               });
              postArr.faceimg=new Array();
              var face_img_count=$('#face_img_td .face_img').size();
              if(face_img_count>0){
                    $('#face_img_td .face_img').each(function(ii){
                        var imgsrc=$(this).data('xsrc');
                        postArr.faceimg.push(imgsrc);
                    });
              }
          }
          if(ishaveerror==1){
            return false;
          }
           nameArr=new Array();
          console.log(postArr)
        var postSaveUrl="{pigcms{:U('House/saveqitianscreen')}";
        $.post(postSaveUrl, postArr, function(ret){
            if(ret.status==1){
               layer.alert(ret.info);
            }else{
                layer.alert(ret.info);
            }
        
        },'JSON');
     }
    function monitorvideo(idStr) {
        var url = "{pigcms{:U('qitianscreen_v')}&idstr="+idStr;
        var page = layer.open({
            type: 2,
            area: ['850px', '650px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '选择视频',
            content: url,
        });
        layer.style(page, {top: '20px'});
    }

    function callbackFunc(idstr,camera_sn,look_url,lookUrlType,camera_name) {
       console.log(idstr,camera_sn,look_url,lookUrlType);
       $('#'+idstr).val(look_url);
       $('#'+idstr+'_name').val(camera_name);
       $('#'+idstr+'_name').data('camera_sn',camera_sn);
       $('#'+idstr+'_name').data('look_url_type',lookUrlType);
    }

</script>

<include file="Public:footer"/>