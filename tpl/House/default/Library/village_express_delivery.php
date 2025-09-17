<include file="Publics:header"/>
<!-- 主题框架css -->
<style>
    .frame_form .input{
		height:30px;
	}
    .container{
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: space-between;
    }
    .left_phone_con{
        width: 28%;
        height: 600px;
        border-radius: 10px;
        background-image: url('https://v1.uviewui.com/common/iPhoneX_model.png');
        background-size: 100% 100%;
        display: flex;
        justify-content: center;
    }
    .image_con{
        width: 93%;
        height: 400px;
        background-image: url('https://hf.pigcms.com/static/wxapp/images/phone_index.jpg');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        border-top-left-radius: 40px;
        border-top-right-radius: 40px;
        display: flex;
        justify-content: space-between;
        padding: 10px;
        transform: translateY(6px);
    }
    .left_delivery_con{
        width: 48%;
        height: 240px;
        transform: translateY(135px);
        border-radius: 10px;
        cursor: pointer;
    }
    .right_service_con{
        width: 48%;
        height: 240px;
        transform: translateY(135px);
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .top_navigate{
        width: 100%;
        height: calc(100% - 150px - 18px);
        border-radius: 10px;
        cursor: pointer;
    }

    .middle_navigate{
        width: 100%;
        height: 92px;
        border-radius: 10px;
        cursor: pointer;
    }

    .bottom_street{
        height: 50px;
        border-radius: 10px;
        cursor: pointer;
    }

    .right_set_con{
        width: 68%;
        height: 600px;
        border: 1px dashed #eee;
        overflow-y:scroll;
        overflow-x:hidden;
    }
</style>

<!-- 模块一部分css -->
<style>
    .table-list .summary {
        margin: 10px 25px 5px 0 !important;
        text-align: right !important;
    }
    .table-list .pager {
        margin: 0 25px 5px 0 !important;
        text-align: right !important;
    }
    .pager .next>a {
        height: 30px;
        line-height: 30px;
    }
    .pager .prev>a {
        height: 30px;
        line-height: 30px;
    }
    .model1_tab{
        display: flex;
        align-items: center;
    }
    .tab_item{
        width: 100px;
        height: 30px;
        border: 1px solid #eee;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .tab_item.active{
        background: #18f;
        color: #fff;
    }
    .model1_form_con{
        margin-top: 10px;
    }

    .right_div{
        display: inline-block;    margin-left: 50px;
    }
    .ant-btn {
        line-height: 1.499;
        position: relative;
        display: inline-block;
        font-weight: 400;
        white-space: nowrap;
        text-align: center;
        background-image: none;
        border: 1px solid transparent;
        -webkit-box-shadow: 0 2px 0 rgb(0 0 0 / 2%);
        box-shadow: 0 2px 0 rgb(0 0 0 / 2%);
        cursor: pointer;
        -webkit-transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
        transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        -ms-touch-action: manipulation;
        touch-action: manipulation;
        height: 32px;
        padding: 0 15px;
        font-size: 14px;
        border-radius: 2px;
        color: #fff;
        background-color: #1890ff;
        text-shadow: 0 -1px 0 rgb(0 0 0 / 12%);
}


    .upload_con{
        display: flex;
        align-items: center;
    }

    .upload_con .goods_item{
        width: 75px;
        height: 75px;
        border: 1px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ddd;
        font-size: 30px;
        border-radius: 10px;
        font-weight: 300;
        cursor: pointer;
        margin-right: 10px;
        position: relative;
    }

    .upload_con .goods_item .close_icon{
        position: absolute;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        right: -5px;
        top: -5px;
        background: red;
        border-radius: 10px;
        color: #fff;
        font-size: 16px;
        font-weight: 300;
    }

    .upload_con .upload_icon{
        width: 75px;
        height: 75px;
        border: 1px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ddd;
        font-size: 30px;
        border-radius: 10px;
        font-weight: 300;
        cursor: pointer;
    }
    .selected_div{
        border:3px solid #ed880e;
        background: rgba(0, 0, 0, .2);
    }
</style>

<div class="container">
    <div class="left_phone_con">
        <div class="image_con">
            <div class="left_delivery_con change_tab  <if condition="$block_type_id eq 'business_express_delivery'"> selected_div </if>" id="deliveryMain"><a href="{pigcms{:U('Library/village_express_delivery',array('village_id'=>$village_id,'block_type_id' => 'business_express_delivery'))}" style="height: 250px;
    display: block;"> </a></div>
            <div class="right_service_con change_tab">
                <div class="top_navigate change_tab  <if condition="$block_type_id eq 'village_life_top'"> selected_div </if>" id="navigateTopMain"><a href="{pigcms{:U('Library/village_express_delivery',array('village_id'=>$village_id,'block_type_id' => 'village_life_top'))}" style="height: 72px;
    display: block;"> </a></div>
                <div class="middle_navigate change_tab <if condition="$block_type_id eq 'village_life_middle'"> selected_div </if>"" id="navigateMiddleMain"><a href="{pigcms{:U('Library/village_express_delivery',array('village_id'=>$village_id,'block_type_id' => 'village_life_middle'))}" style="height: 90px;
    display: block;"> </a></div>
                <div class="bottom_street change_tab <if condition="$block_type_id eq 'village_street_community'"> selected_div </if>" id="streetMain"><a href="{pigcms{:U('Library/village_express_delivery',array('village_id'=>$village_id,'block_type_id' => 'village_street_community'))}" style="height: 60px;
    display: block;"> </a></div>
            </div>
        </div>
    </div>
    <div class="right_set_con">
        <!-- 模块一 -->
        <div id="delivery_set_con" <if condition="$block_type_id neq 'business_express_delivery'"> class="hide" </if> >
            <!-- top -->
            <div id="business_nav_set">
            <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
                <tr>
                    <th width="120">顶部颜色</th>
                    <td>
                       <input type="text" class="input-text set_input" name="block_top_color" id="block_top_color" value="{pigcms{$nav_set.block_top_color}" readonly="readonly" style="text-indent:1000px;width:80px;height: 30px;cursor:default;background-color:{pigcms{$nav_set.block_top_color}"/>&nbsp;&nbsp;
                        <a href="javascript:void(0);" id="block_top_color_set" style="line-height:30px;">点击选择颜色</a>
                    </td>
                </tr>
                <tr>
                    <th width="120">底部颜色</th>
                    <td>
                        <input type="text" class="input-text set_input" name="block_bottom_color" id="block_bottom_color" value="{pigcms{$nav_set.block_bottom_color}" readonly="readonly" style="text-indent:1000px;width:80px;height: 30px;cursor:default;background-color:{pigcms{$nav_set.block_bottom_color}"/>&nbsp;&nbsp;
                        <a href="javascript:void(0);" id="block_bottom_color_set" style="line-height:30px;">点击选择颜色</a>
                    </td>
                </tr>
                <tr>
                    <th width="120">圆角半径</th>
					<td>
						<div id="slideTest1" style="width: 112px;display: inline-block; margin-left: 5px;"></div>
						<div style="display: inline-block;margin-left: 15px">
							<input type="number" class="input set_input" id="fillet_radius" style="width: 75px;margin-right: 10px" name="fillet_radius" min="0" max="50" size="20" value="{pigcms{$nav_set.fillet_radius}"/>
							<span style="line-height: 30px">
								圆角半径默认为0,最大50,圆角半径值越大，圆角越圆
							</span>
						</div>
					</td>
                </tr>
            </table>

            <!-- bottom -->
            <div class="model1_tab">
                <div class="tab_item active" data-sub_block_type_id='business_small_shop' >快店</div>
                <div class="tab_item" data-sub_block_type_id='business_new_mall' >新版商城</div>
                <div class="tab_item" data-sub_block_type_id='business_community_group' >社区团购</div>
                <div class="tab_item" data-sub_block_type_id='business_service_reservation' >预约服务</div>
                <input type="hidden" class="input set_input" id="sub_block_type_id" name="sub_block_type_id" value="business_small_shop"/>
                <input type="hidden" class="input set_input" id="parent_block_type_id" name="parent_block_type_id" value="business_express_delivery"/>
            </div>

            <div class="model1_form_con" >
                <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
                    <tr>
                        <th width="120">状态</th>
                        <td>
                           <div class="radio" style="padding-top: 5px;" id="business_is_show" >
                                <label style="padding-left:0px;padding-right:20px;"><input name="is_show" value="1" type="radio" class="ace set_input select1"  <if condition="empty($nav_set) OR !isset($nav_set['is_show']) OR $nav_set.is_show eq 1">checked</if> >&nbsp;<span class="lbl" style="z-index: 1">&nbsp;显示</span></label>
                                <label style="padding-left:0px;"><input name="is_show" value="0" type="radio" class="ace set_input select0" <if condition="!empty($nav_set) AND isset($nav_set['is_show']) AND $nav_set.is_show eq 0">checked</if> >&nbsp;<span class="lbl" style="z-index: 1">&nbsp;影藏</span></label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th width="120"><label class="red">*标题</label></th>
                        <td>
                            <input type="text" class="input set_input" value="{pigcms{$nav_set.title}" id="business_title" name="title" placeholder="4个汉字以内" maxlength="4" style="width:250px;height:30px;" />
                            &nbsp;&nbsp;&nbsp;(必填项)
                        </td>
                    </tr>
                    <tr>
                        <th width="120"><label class="red">限制距离</label></th>
                        <td>
                            <input type="number" class="input set_input" value="{pigcms{$nav_set.distance}" id="business_distance" name="distance" placeholder="商品显示的限制的距离（千米）" max="99999" min="0" style="width:200px;height:30px;" /> &nbsp;千米
                            &nbsp;&nbsp;&nbsp;(0表示不限制)
                        </td>
                    </tr>
                    <tr>
                        <th width="120">排序</th>
                        <td>
                            <input type="number"  min="0" value="{pigcms{$nav_set.xsort}"  id="business_xsort" class="input set_input" name="xsort" style="width:120px;height:30px;" />
                        </td>
                    </tr>
                    <tr>
                        <th width="120">商品展示列表</th>
                        
                        <td>
                         <div class="product_info">
                              <table width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>序号</th>
                                            <th>名称</th>
                                            <th>图片</th>
                                            <th>商家名称</th>
                                            
                                        </tr>
                                    </thead>
                                    <tbody class="product_info_tbody">
                                       <if  condition="!empty($nav_set) AND isset($nav_set['product_info']) AND !empty($nav_set['product_info'])">
                                             <volist name="nav_set['product_info']" id="pinfo">
                                                <tr data-product_id="{pigcms{$pinfo.goods_id}"  data-price="{pigcms{$pinfo.price}" >
                                                    <td><php>echo $i; </php></td>
                                                    <td>{pigcms{$pinfo.name}</td>
                                                    <td><img style="width:75px" src="{pigcms{$pinfo.image}" class="view_msg"></td>
                                                    <td>{pigcms{$pinfo.merchant_name}</td>
                                                </tr>
                                            </volist>
                                        <else/>
                                            <tr>
                                                <td class="textcenter red">没有获取到数据！</td>
                                            </tr>
                                        </if>
                                    </tbody>
                                </table>
                            </div>
                            <!--
                            <div class="upload_con" id="business_product_set">
                                <div id="business_product_item" style="display: flex;">
                                <if  condition="!empty($nav_set) AND isset($nav_set['product_info']) AND !empty($nav_set['product_info'])">
                                <volist name="nav_set['product_info']" id="pinfo">
                                    <div class="goods_item">
                                        <div class="close_icon">x</div>
                                        <img style="width:75px" src="{pigcms{$pinfo.pimg}" class="view_msg">
                                        <input type="hidden" class="input product_input"  name="product_id" value="{pigcms{$pinfo.product_id}" data-product_id="{pigcms{$pinfo.product_id}" data-pimg="{pigcms{$pinfo.pimg}" data-pname="{pigcms{$pinfo.pname}"  data-price="{pigcms{$pinfo.price}" data-purl="{pigcms{$pinfo.purl}" />
                                    </div>
                                </volist>
                                </if>
                                </div>
                                <div class="upload_icon" id="business_upload_add" onclick="get_business_product_list('business_product_item')" <if  condition="!empty($nav_set) AND isset($nav_set['product_num']) AND $nav_set['product_num'] egt 3"> style="display:none;" </if> >+</div>
                                <div style="margin-left: 10px;"><p>请最少选择一件商品</p><p>最多选择3件商品</p></div>
                            </div>
                            --->
                        </td>
                        
                    </tr>
                </table>
            </div>
            </div>
             <div id="addGoodsModal" style="display: none;">添加商品</div>
             <if condition="!$house_visualize_assign">
             <div style="text-align:center;margin-top: 50px;">
                   <button type="button" onclick="saveNavBusinessSetData()" class="ant-btn ant-btn-primary" >保存设置</button>
               </div>
             </if>
        </div>

         <!-- 模块二 模块三-->
        <div id="navigate_set_con_top" <if condition="!in_array($block_type_id,array('village_life_middle','village_life_top'))"> class="hide" </if> >
            <div class="mainbox">
                  <div id="basicinfo_div" class="tab_div" style="padding-bottom: 20px; border-bottom: 1px solid #ccc; margin-bottom: 15px;">
                    <input type="hidden" class="input set_input" name="block_type_id" value="{pigcms{$block_type_id}"/>
                    <input type="hidden" class="input set_input" name="village_id" value="{pigcms{$village_id}"/>
                   <div class="form-group">
                        <label class="col-sm-1" style="width: 15%;"><label class="red">*标题：</label></label>
                        <div class="radio" style="padding-top: 5px;">
                            <input type="text" class="input set_input" id="set_input_title" name="title" value="{pigcms{$nav_set.title}" placeholder="6个汉字以内" maxlength="6" required /> &nbsp;&nbsp;&nbsp;(必填项)
                        </div>
                    </div>
                   <div class="form-group">
                        <label class="col-sm-1" style="width: 15%;"><label>副标题：</label></label>
                        <div class="radio" style="padding-top: 5px;">
                            <input type="text" class="input set_input" name="sub_title" value="{pigcms{$nav_set.sub_title}" placeholder="15个汉字以内" maxlength="15"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1" style="width: 15%;"><label>角标名称：</label></label>
                        <div class="radio" style="padding-top: 5px;">
                            <input type="text" class="input set_input" name="label_title" value="{pigcms{$nav_set.label_title}" placeholder="7个汉字以内" maxlength="7"/>
                             <div class="right_div">
                                    <span>角标背景颜色：</span>
                                    <input type="text" class="input-text set_input" name="label_bg_color" id="config_labelcolor" value="{pigcms{$nav_set.label_bg_color}" readonly="readonly" style="text-indent:1000px;width:60px;height: 30px;cursor:default;background-color:{pigcms{$nav_set.label_bg_color}"/>
                                    <a href="javascript:void(0);" id="label_bg_color_set" style="line-height:28px;">点击选择颜色</a>
                                </div>
                        </div>
                    </div>
                    <div class="form-group">
                                <label class="col-sm-1" style="width: 15%;"><label>圆角半径:</label></label>
                                <div id="slideTest2" style="width: 100px;display: inline-block"></div>
                                <div style="display: inline-block;margin-left: 10px">
                                    <input type="number" class="input set_input" id="fillet_radius2" style="width: 50px;margin-right: 15px" name="fillet_radius" min="0" max="50" size="20" value="{pigcms{$nav_set.fillet_radius}"/>
                                    <span>圆角半径默认为0,最大设置为50,圆角半径值越大，圆角越圆，0是为直角</span>
                                </div>
                         </div>
                    <div class="form-group">
                        <label class="col-sm-1" style="width: 15%;"><label>是否显示系统导航</label></label>
                        <div class="radio" style="padding-top: 5px;">
                            <label style="padding-left:0px;padding-right:20px;"><input name="show_community_platform" value="1" type="radio" class="ace set_input"  <if condition="$nav_set AND $nav_set.show_community_platform eq 1"> checked </if> >&nbsp;<span class="lbl" style="z-index: 1">&nbsp;开启</span></label>
                            <label style="padding-left:0px;"><input name="show_community_platform" value="0" type="radio" class="ace set_input" <if condition="empty($nav_set) OR $nav_set.show_community_platform eq 0"> checked</if> >&nbsp;<span class="lbl" style="z-index: 1">&nbsp;关闭</span></label>
                        </div>
                    </div>
                     <if condition="!$house_visualize_assign">
                        <div style="text-align:center;margin-top: 50px;">
                           <button type="button" onclick="saveNavSetData()" class="ant-btn ant-btn-primary" >保存设置</button>
                       </div>
                       </if>
                    </div>
                    
                    <div id="datalist_div" class="tab_div">
                    <div style="margin-left: 10px;color: #9a9999">
                        <span> 说明：</span>
                        <p>1.开关按钮默认开启</p>
                        <p> 2.当开关是开启状态下，即默认小区里的正常导航大于或等于20个的时候显示的只有小区导航，如果小于20个则追加系统导航。</p>
                        <p>3.如果是关闭状态下，则无论小区里正常的导航有多少个都不会追加系统导航。</p>

                    </div>
                    <div id="nav" class="mainnav_title">

                        <ul>
                            <a href="" class="on">首页自定义导航列表</a>
                            <if condition="!$house_visualize_assign">|
                            <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Library/nav_add_new',array('block_type_id' => $block_type_id,'is_community_platform'=>$is_community_platform))}','添加导航',850,580,true,false,false,addbtn,'add',true,'refreshPage');">添加导航</a></if>
                        </ul>
                    </div>
                    <div class="page_tips">图标建议尺寸：图片宽度建议为：150px，高度建议为：150px 平台已为你添加<span>{pigcms{$adver_count}</span> 张</div>
                    <form name="myform" id="myform" action="" method="post">
                        <div class="table-list">
                            <table width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>编号</th>
                                        <th>分类名称</th>
                                        <th>排序</th>
                                        <th>状态</th>
                                        <if condition="!$house_visualize_assign"><th class="textcenter">操作</th></if>
                                    </tr>
                                </thead>
                                <tbody>
                                    <if condition="is_array($result['list'])">
                                        <volist name="result['list']" id="vo">
                                            <tr>
                                                <td>{pigcms{$vo.id}</td>
                                                <td>{pigcms{$vo.name}</td>

                                                <td>{pigcms{$vo.sort}</td>


                                                <td>
                                                <if condition="$vo['status'] eq 1">
                                                    <font color="green">正常</font>
                                                <elseif condition="$vo['status'] eq 0"/>
                                                    <font color="red">关闭</font>
                                                </if></td>
                                                <if condition="!$house_visualize_assign">
                                                <td class="textcenter">
                                                   <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Library/nav_edit_new',array('id'=>$vo['id'],'block_type_id' => $block_type_id,'is_community_platform'=>$is_community_platform,'from_page'=>$_GET['from_page']))}','编辑导航信息',850,580,true,false,false,editbtn,'add',true);">编辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="id={pigcms{$vo.id}" url="{pigcms{:U('Library/nav_del_new')}">删除</a>
                                                </td>
                                               </if>
                                            </tr>
                                        </volist>
                                    <else/>
                                        <tr>
                                            <td class="textcenter red"  <if condition="($assign_type['type_2'] && $row_type neq single) || ($assign_type['type_11'] && $row_type eq single)"> colspan="5" <else/>colspan="4"</if>>列表为空！</td>
                                        </tr>
                                    </if>
                                </tbody>
                            </table>
                            {pigcms{$result.pagebar}
                        </div>
                    </form>
                    </div>
                </div>
        </div>

        
        <!-- 模块四 -->
        <div id="street_set_con" <if condition="$block_type_id neq 'village_street_community'"> class="hide" </if> >
              <div id="village_street_community_set">
                <input type="hidden"  class="set_input" name="village_nav_id" value="{pigcms{$nav_set['village_nav']['id']}"/>
                <input type="hidden"  class="set_input" name="block_type_id" value="{pigcms{$block_type_id}"/>
                <input type="hidden" class="set_input"  name="is_community_platform" value="{pigcms{$is_community_platform}"/>
                <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
                    <tr>
                        <th width="80"><label class="red">*标题</label></th>
                        <td><input type="text" class="input set_input set_title" name="title"  value="{pigcms{$nav_set['title']}"  validate="maxlength:6,required:true" placeholder="6个汉字以内" maxlength="6" required  style="width: 250px;" /> &nbsp;&nbsp;&nbsp;(必填项)</td>
                    </tr>
                     <tr>
                        <th width="80">副标题</th>
                        <td><input type="text" class="input set_input" name="sub_title" value="{pigcms{$nav_set['sub_title']}"  validate="maxlength:15,required:true" placeholder="15个汉字以内" maxlength="15" style="width: 250px;" /></td>
                    </tr>
                 <tr>
                    <th width="120">圆角半径</th>
					<td>
						<div id="slideTest3" style="width: 112px;display: inline-block; margin-left: 5px;"></div>
						<div style="display: inline-block;margin-left: 15px">
							<input type="number" class="input set_input" id="fillet_radius3" style="width: 75px;margin-right: 10px" name="fillet_radius" min="0" max="50" size="20" value="{pigcms{$nav_set['fillet_radius']}"/>
							<span style="line-height: 30px">
								圆角半径默认为0,最大50,圆角半径值越大，圆角越圆
							</span>
						</div>
					</td>
                </tr>
                    <tr>
                        <th width="80"><label class="red">*图标</label></th>
                        <td>
                            <div class="radio" style="padding-top: 5px;">
                                    <input class="btn btn-primary" type="button" id="street_img_up" value="上传图片" style="line-height: 8px;" />
                                    <input type="hidden"  class="set_input set_img" name="img" value="{pigcms{$nav_set['village_nav']['img']}" id="street_img_value" />
                                    <img src="{pigcms{:replace_file_domain($nav_set['village_nav']['img'])}" id="street_img_view"  class="view_msg" style="cursor: pointer;padding: 10px; width: 100px; <if condition="!$nav_set['village_nav'] OR !$nav_set['village_nav']['img']">display: none;</if>">
                                    &nbsp;&nbsp;&nbsp;(必填项)
                            </div>
 
                        </td>
                    </tr>
                    <tr>
                        <th width="80"><label class="red">*链接</label></th>
                        <td>
                            <input type="text" class="input set_input set_url" name="url" id="url" value="{pigcms{$nav_set['village_nav']['url']}" style="width:200px;" placeholder="请填写链接地址" validate="maxlength:200,required:true"/>
                            <?php if(!C('butt_open')){ ?>
                                <if condition="$now_category['cat_type'] neq '1'">
                                    <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 'on_deving', 0)" data-toggle="modal">从功能库选择</a>
                                <else />
                                    <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 'on_deving', 1)" data-toggle="modal">从功能库选择</a>
                                </if>
                            <?php } ?>

                             &nbsp;&nbsp;&nbsp;(必填项)
                        </td>
                    </tr>
                    <tr class="wxapp_open_wxapp">
                        <th width="80">打开其他小程序</th>
                        <td>
                            <select name="wxapp_id" id="wxapp_id" class="set_input" style="width: 200px;" tips="请选择一个想要打开的其他小程序，目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。">
                                <option value="">选择小程序</option>
                                <volist name='wxapp_list' id='vo'>
                                  <option value="{pigcms{$vo.appid}" <if condition="$nav_set['village_nav'] AND  $nav_set['village_nav']['wxapp_id'] AND $vo['appid'] eq $nav_set['village_nav']['wxapp_id']">selected</if> >{pigcms{$vo.name}</option>
                                </volist>
                           </select>
                           <span style="position: relative;top: 2px;">选填</span>
                        </td>
                    </tr>
                    <tr class="wxapp_open_wxapp wxapp_page" <if condition="empty($nav_set['village_nav']) OR empty($nav_set['village_nav']['wxapp_page'])">style="display: none;"</if>>
                        <th width="80">小程序页面</th>
                        <td><input type="text" class="input  set_input" name="wxapp_page" id="wxapp_page" style="width:200px;" value="{pigcms{$nav_set['village_nav']['wxapp_page']}"  placeholder="请输入小程序页面路径" tips="即打开另一个小程序时进入的页面路径，如果为空则打开首页；另一个小程序的页面路径请联系该小程序的技术人员询要；目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。"/></td>
                    </tr>
                    <tr>
                        <th width="80">排序</th>
                        <td><input type="text" class="input set_input" name="xsort" style="width:80px;" value="{pigcms{$nav_set['xsort']}" validate="maxlength:10,required:true,number:true"/></td>
                    </tr>
                    <tr>
                        <th width="80">是否开启</th>
                        <td>
                            <span class="cb-enable"><label class="cb-enable <if condition="empty($nav_set) OR $nav_set['is_show'] eq 1">selected</if>"><span>开启</span><input type="radio" name="status_show" value="1" checked="checked" class="set_input" <if condition="empty($nav_set) OR $nav_set['is_show'] eq 1">checked="checked"</if> /></label></span>
                            <span class="cb-disable"><label class="cb-disable <if condition="!empty($nav_set) AND $nav_set['is_show'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status_show" value="0" class="set_input"  <if condition="!empty($nav_set) AND $nav_set['is_show'] eq 0">checked="checked"</if> /></label></span>
                        </td>
                    </tr>

                </table>
                </div>
                <if condition="!$house_visualize_assign">
                <div style="text-align:center;margin-top: 50px;">
                   <button type="button" onclick="saveNavStreetSetData()" class="ant-btn ant-btn-primary" >保存设置</button>
               </div>
               </if>
        </div>
    </div>
</div>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/upyun.js"></script>
<link rel="stylesheet" href="{pigcms{$static_public}layui2/css/layui.css" media="all"/>
<script src="{pigcms{$static_public}layui2/layui.all.js" charset="utf-8"></script>
<!-- 表单交互 -->
<script type="text/javascript" charset="utf-8">
      KindEditor.ready(function(K) {
        var editor = K.editor({
            allowFileManager : true
        });
        K('#street_img_up').click(function() {
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#street_img_view');
                        img.attr("src",url);
                        img.show();
                        K("#street_img_value").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });
    });

   function saveNavSetData(){

       var inputArr=$('#basicinfo_div .set_input').serializeArray();
       console.log('inputArr',inputArr);
       var set_input_title=$('#set_input_title').val()||'';
       set_input_title=$.trim(set_input_title);
       if(!set_input_title){
            layer.alert('请填写标题！');
            return false;
       }
       $.post("{pigcms{:U('save_village_nav_set')}",inputArr,function(result){
            if(result.status != 1){
                layer.alert('保存成功,请重试~ ');
            }else{
                layer.alert('保存成功！');
            }
        },'json');
   }

    function saveNavStreetSetData(){

       var inputArr=$('#village_street_community_set .set_input').serializeArray();
       console.log('street_community_set',inputArr);
       var set_input_title=$('#village_street_community_set .set_title').val()||'';
       set_input_title=$.trim(set_input_title);
       if(!set_input_title){
            layer.alert('请填写标题！');
            return false;
       }
      var set_set_img=$('#village_street_community_set .set_img').val()||'';
       set_set_img=$.trim(set_set_img);
       if(!set_set_img){
            layer.alert('请上传图标！');
            return false;
       }

       var set_set_url=$('#village_street_community_set .set_url').val()||'';
       set_set_url=$.trim(set_set_url);
       if(!set_set_url){
            layer.alert('请填写链接地址！');
            return false;
       }

       $.post("{pigcms{:U('save_village_nav_set')}",inputArr,function(result){
            if(result.status != 1){
                layer.alert('保存成功,请重试~ ');
            }else{
                layer.alert('保存成功！');
            }
        },'json');
    }

   function saveNavBusinessSetData(){
        var inputArr=$('#business_nav_set .set_input').serializeArray();
       console.log('business_nav_set',inputArr);
       var set_input_title=$('#business_title').val()||'';
       set_input_title=$.trim(set_input_title);
       if(!set_input_title){
            layer.alert('请填写标题！');
            return false;
       }
       var postArr={};
        $.each( inputArr, function(i, field){
            var key_name=field.name;
            postArr[key_name]=field.value;
        });
       /*
       var product_num=$('#business_product_item .product_input').size();
       if(product_num<1){
           layer.alert('请至少选择一个商品进行展示！');
           return false;
       }
       var productArr=Array();
       $('#business_product_item .product_input').each(function(ii){
            var product_id=$(this).val()||0;
            var pimg=$(this).data('pimg');
            var pname=$(this).data('pname');
            var price=$(this).data('price');
            var purl=$(this).data('purl');
            productArr.push({'product_id':product_id,'pname':pname,'pimg':pimg,'price':price,'purl':purl});
        });
        postArr.product_info=productArr;
        */
        console.log('postArr',postArr);
       $.post("{pigcms{:U('save_village_nav_set')}",postArr,function(result){
            if(result.status != 1){
                layer.alert('保存成功,请重试~ ');
            }else{
                layer.alert('保存成功！');
            }
        },'json');
   }

    $("#business_product_item").on('click','.close_icon',function (e){
        $(this).parent(".goods_item").remove();
        var goods_item_num=$("#business_product_item .goods_item").size();
        if(goods_item_num<3){
            $('#business_upload_add').show();
        }
        
    });

    function get_business_product_list(domid){
        var goods_item_num=$("#business_product_item .goods_item").size();
        if(goods_item_num>=3){
            $('#business_upload_add').hide();
        }
        var xtype=$('#sub_block_type_id').val();
        var xtype_name='快店';
        if(xtype=='business_new_mall'){
            xtype_name='新版商城';
        }
        if(xtype=='business_community_group'){
            xtype_name='社区团购';
        }
        if(xtype=='business_service_reservation'){
            xtype_name='预约服务';
        }
        art.dialog.data('domid', domid);
        art.dialog.open('?g=House&c=Library&a=business_product_list&xtype='+xtype,{lock:true,title:'请选择【'+xtype_name+'】相关商品',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
    }

    layui.use('slider', function(){
        
        var slider = layui.slider;
        
        //渲染
        var ins1 = slider.render({
        elem: '#slideTest1',  //绑定元素
            min: 0,
            max: 50,
        change: function(value){
            $("#fillet_radius").val(value)
        }
        });
        $(function (){
            ins1.setValue($("#fillet_radius").val())
        })
        $("#fillet_radius").on('input keyup',function (e){
            ins1.setValue(e.delegateTarget.value)
        })

       //渲染
        var ins2 = slider.render({
        elem: '#slideTest2',  //绑定元素
            min: 0,
            max: 50,
        change: function(value){
            $("#fillet_radius2").val(value)
        }
        });
        $(function (){
            ins2.setValue($("#fillet_radius2").val())
        })
        $("#fillet_radius2").on('input keyup',function (e){
            ins2.setValue(e.delegateTarget.value)
        })

        //渲染
        var ins3 = slider.render({
        elem: '#slideTest3',  //绑定元素
            min: 0,
            max: 50,
        change: function(value){
            $("#fillet_radius3").val(value)
        }
        });
         $(function (){
            ins3.setValue($("#fillet_radius3").val())
        })
        $("#fillet_radius3").on('input keyup',function (e){
            ins3.setValue(e.delegateTarget.value)
        })
        
    });

    $(function(){
        $("#deliveryMain").click(function(){
            $("#delivery_set_con").css('display','block')
            $("#navigate_set_con_top").css('display','none')
            $("#street_set_con").css('display','none')
            $('.left_phone_con .change_tab').removeClass('selected_div');
            $(this).addClass('selected_div');
        });
        $("#navigateTopMain").click(function(){
            $("#delivery_set_con").css('display','none')
            $("#navigate_set_con_top").css('display','block')
            $("#street_set_con").css('display','none')
            $('.left_phone_con .change_tab').removeClass('selected_div');
            $(this).addClass('selected_div');
        });
        $("#navigateMiddleMain").click(function(){
            $("#delivery_set_con").css('display','none')
            $("#navigate_set_con_top").css('display','block')
            $("#street_set_con").css('display','none')
            $('.left_phone_con .change_tab').removeClass('selected_div');
            $(this).addClass('selected_div');
        });
        $("#streetMain").click(function(){
            $("#delivery_set_con").css('display','none')
            $("#navigate_set_con_top").css('display','none')
            $("#street_set_con").css('display','block')
            $('.left_phone_con .change_tab').removeClass('selected_div');
            $(this).addClass('selected_div');

        });
        // 设置顶部颜色
        $('#label_bg_color_set').click(function(){
            window.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'config_labelcolor',function(color){
                 if(color==undefined || color==''){
                    color='';
                }
                $("#config_labelcolor").val(color);
                if(color==''){
                    color='#fff';
                }
                var cssText = $("#config_labelcolor").attr("style") + ";background-color:"+ color;
                $("#config_labelcolor").css("cssText",cssText);
            })
        });
        $('#block_top_color_set').click(function(){
            window.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'block_top_color',function(color){
                if(color==undefined || color==''){
                    color='';
                }
                $("#block_top_color").val(color);
                if(color==''){
                    color='#fff';
                }
                var cssText = $("#block_top_color").attr("style") + ";background-color:"+ color;
                $("#block_top_color").css("cssText",cssText);
            })
        });
        // 设置底部颜色
        $('#block_bottom_color_set').click(function(){
            window.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'block_bottom_color',function(color){
                if(color==undefined || color==''){
                    color='';
                }
                $("#block_bottom_color").val(color);
                if(color==''){
                    color='#fff';
                }
                var cssText = $("#block_bottom_color").attr("style") + ";background-color:"+ color;
                $("#block_bottom_color").css("cssText",cssText);
            })
        });

        $("#business_nav_set .model1_tab .tab_item").click(function(){

            var sub_block_type_id=$(this).data('sub_block_type_id');
            $('#sub_block_type_id').val(sub_block_type_id);
            console.log('sub_block_type_id',sub_block_type_id);
            var postArr={'sub_block_type_id':sub_block_type_id};
            if(!$(this).hasClass("active")){
                $(this).addClass('active').siblings().removeClass('active');
                $('#business_is_show .select1').click();
                $('#business_xsort').val('0');
                $('#business_title').val('');
                //$('#business_product_item .goods_item').remove();
                //$('#business_upload_add').show();
                $.post("{pigcms{:U('get_business_nav_set_info',array('village_id'=>$village_id,'is_community_platform'=>$is_community_platform))}",postArr,function(result){
                        if(result.data && result.data.is_show!=undefined){
                            var is_show_tmp=result.data.is_show ? result.data.is_show:0;
                            $('#business_is_show .select'+is_show_tmp).click();
                        }
                        if(result.data && result.data.title!=undefined){
                            $('#business_title').val(result.data.title);
                        }
                        if(result.data && result.data.xsort!=undefined){
                            $('#business_xsort').val(result.data.xsort);
                        }
                        if(result.data && result.data.distance!=undefined){
                            $('#business_distance').val(result.data.distance);
                        }
                        var product_count=1;
                        if(result.data && result.data.product_info!=undefined && result.data.product_info.length>0){
                            var pHtml='';
                            var product_info=result.data.product_info;
                            var merchant_name='';
                            for(ii=0;ii<product_info.length;ii++){
                                merchant_name='';
                                 if(product_info[ii].merchant_name!=undefined){
                                    merchant_name=product_info[ii].merchant_name;
                                 }
                                 pHtml += '<tr data-product_id="'+product_info[ii].goods_id+'"  data-price="'+product_info[ii].price+'" ><td>'+product_count+'</td><td>'+product_info[ii].name+'</td><td><img class="view_msg" style="width:75px" src="'+product_info[ii].image+'"></td><td>'+merchant_name+'</td></tr>'
                                /*
                                 pHtml +='<div class="goods_item"><div class="close_icon">x</div><img class="view_msg" style="width:75px" src="'+product_info[ii].pimg+'"><input type="hidden" class="input product_input"  name="product_id" value="'+product_info[ii].product_id+'" data-product_id="'+product_info[ii].product_id+'" data-pimg="'+product_info[ii].pimg+'" data-pname="'+product_info[ii].pname+'"  data-price="'+product_info[ii].price+'" data-purl="'+product_info[ii].purl+'" /></div>';
                                 */
                                 product_count++;
                            }
                            $('.product_info_tbody').html(pHtml);
                            /*
                                $('#business_product_item').html(pHtml);
                            */
                        }else{
                               var pHtml='<tr><td class="textcenter red">没有获取到数据！</td></tr>';
                               $('.product_info_tbody').html(pHtml);
                        }
                        /*
                        if(product_count>=3){
                            $('#business_upload_add').hide();
                         }else{
                            $('#business_upload_add').show();
                        }
                        */
                    },'json');
            }
            
        });

        $("#wxapp_id").change(function(){
            if ($("#wxapp_id").val()) {
                $(".wxapp_page").show();
            }else{
                $("#wxapp_page").val('');
                $(".wxapp_page").hide();
            }
        })
    });
    
</script>
<!-- 模块二js -->
<script>
	let single = '{pigcms{$row_type}'
    $("input[name=read_idcard]").change(function(){
        var village_nav = $(this).val();
        layer.confirm("是否保存显示系统导航设置?",{
            btn: ['确认', '取消']
        }, function () {
            var url ="{pigcms{:U('set_village_nav')}";
            $.post(url,{'village_nav': village_nav,'row_type': single},function(data){
            layer.msg(data['msg'])
            },'json')
        }, function(){
            if(village_nav == 1){
            $("input[name=read_idcard][type=radio][value='0']").attr("checked",true);
            }else{
            $("input[name=read_idcard][type=radio][value='1']").attr("checked",true);
            }
        });
    });

	// 功能导航效果
    $("input[name=multiline_effect]").change(function(){
        var multiline_effect = $(this).val();
        layer.confirm("是否保存功能导航效果?",{
        btn: ['确认', '取消']
        }, function () {
            var url ="{pigcms{:U('set_village_nav')}";
            $.post(url,{'multiline_effect': multiline_effect,'row_type': single},function(data){
                layer.msg(data['msg'])
            },'json')
        }, function(){
            if(multiline_effect == 1){
                $("input[name=multiline_effect][type=radio][value='0']").attr("checked",true);
            }else{
                $("input[name=multiline_effect][type=radio][value='1']").attr("checked",true);
            }
        });
    });

    // 一屏展示数量
    $("#view_num").change(function(){
        var view_num = $("#view_num").val();
        layer.confirm("是否保存一屏展示数量效果?",{
            btn: ['确认', '取消']
        }, function () {
        var url ="{pigcms{:U('set_village_nav')}";
        $.post(url,{'view_num': view_num,'row_type': single},function(data){
            layer.msg(data['msg'])
        },'json')}, function(){
            if(view_num == 5){
                $("#view_num").attr('value',7);
            }else{
                $("#view_num").attr('value',5);
            }
        });
    });
</script>
<include file="Publics:footer"/>
