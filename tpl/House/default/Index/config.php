<include file="Public:header"/>
<style>
    .help_body{
        margin-top:5px;
    }
    .help_body .help_box{
        margin-left: 8.3%;
        margin-bottom:5px;
    }
    .help_body .help_asse{
        margin-right:10px;
    }
    .help_body .help_time{
        height:30px;
    }
    .help_body .help_del{
        color: red;
    }
    .help_body .help_type{
        float: left;
        width: 16.6%;
    }
    .help_body .help_custom{
        /*display: inline-block;*/
        /*float: left;*/
    }
    .help_body .help_custom .col-sm-1{
        width: 8.98%;
    }
    .flex{
        display: flex;
    }
</style>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Index/index')}">社区管理</a>
			</li>
			<li class="active">基本信息设置</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content" style="padding:0px 12px 12px 12px;">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
                .ant-btn {
                    line-height: 30px;
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
                    height: 32px;
                    padding: 0 15px;
                    font-size: 14px;
                    border-radius: 2px;
                    color: rgba(0, 0, 0, 0.65);
                    background-color: #fff;
                    border-color: #d9d9d9;
                }
                .ant-btn-primary {
                    color: #fff;
                    background-color: #1890ff;
                    border-color: #1890ff;
                    text-shadow: 0 -1px 0 rgb(0 0 0 / 12%);
                    -webkit-box-shadow: 0 2px 0 rgb(0 0 0 / 5%);
                    box-shadow: 0 2px 0 rgb(0 0 0 / 5%);
                }
                #myTab li{ margin-top: 10px; }
                #orderCheckControl a:hover,#orderCheckControl a:focus {
                    color: #fff;
                }
                #orderCheckControl .check_control_p li .xdel{    background: red;border-radius: 7px;color: #fff;margin-left: 10px;}
                #orderCheckControl .check_control_p li img{width: 50px;}
                #orderCheckControl .check_control_p li span{display: inline-block; margin: 1px 20px;}
                #orderCheckControl .check_control_p li span strong{color:red;}
                #orderCheckControl .check_control_p li  select{width: 500px;}
                #orderCheckControl .check_control_p .xline{ width: 1px;height: 40px;background: #4e4e4e;border: 0;margin-left: 25px; }
                .help-icon{
                    cursor: pointer;width: 20px;line-height: 20px;border-radius: 10px;background-color: #ddd;display: inline-block;text-align: center;
                }
                #orderCheckModifyControl a:hover,#orderCheckModifyControl a:focus {
                    color: #fff;
                }
                #orderCheckModifyControl .check_control_p1 li .xdel1{    background: red;border-radius: 7px;color: #fff;margin-left: 10px;}
                #orderCheckModifyControl .check_control_p1 li img{width: 50px;}
                #orderCheckModifyControl .check_control_p1 li span{display: inline-block; margin: 1px 20px;}
                #orderCheckModifyControl .check_control_p1 li span strong{color:red;}
                #orderCheckModifyControl .check_control_p1 li  select{width: 500px;}
                #orderCheckModifyControl .check_control_p1 .xline{ width: 1px;height: 40px;background: #4e4e4e;border: 0;margin-left: 25px; }

                #orderServiceTimeControl a:hover,#orderServiceTimeControl a:focus {
                    color: #fff;
                }
                #orderServiceTimeControl .check_control_p3 li .xdel3{    background: red;border-radius: 7px;color: #fff;margin-left: 10px;}
                #orderServiceTimeControl .check_control_p3 li img{width: 50px;}
                #orderServiceTimeControl .check_control_p3 li span{display: inline-block; margin: 1px 20px;}
                #orderServiceTimeControl .check_control_p3 li span strong{color:red;}
                #orderServiceTimeControl .check_control_p3 li  select{width: 500px;}
                #orderServiceTimeControl .check_control_p3 .xline{ width: 1px;height: 40px;background: #4e4e4e;border: 0;margin-left: 25px; }
                
                #orderCheckModifyControl .order_modify_discount{width: 140px;}
                #orderCheckModifyControl .order_dis{width: 250px; float: left; margin-bottom: 20px;}
                #orderCheckModifyControl .check_control_dis li .xdel1 {
                    background: red;
                    border-radius: 7px;
                    color: #fff;
                    margin-left: 10px;
                   
                }

                #orderModifyPayTimeControl a:hover,#orderModifyPayTimeControl a:focus {
                    color: #fff;
                }
                #orderModifyPayTimeControl .check_control_p4 li .xdel4{    background: red;border-radius: 7px;color: #fff;margin-left: 10px;}
                #orderModifyPayTimeControl .check_control_p4 li img{width: 50px;}
                #orderModifyPayTimeControl .check_control_p4 li span{display: inline-block; margin: 1px 20px;}
                #orderModifyPayTimeControl .check_control_p4 li span strong{color:red;}
                #orderModifyPayTimeControl .check_control_p4 li  select{width: 500px;}
                #orderModifyPayTimeControl .check_control_p4 .xline{ width: 1px;height: 40px;background: #4e4e4e;border: 0;margin-left: 25px; }
			</style>
			<div class="row">
					<div class="tabbable">
						<ul id="myTab" class="nav nav-tabs">
							<li class="active">
								<a href="#basicinfo" data-toggle="tab">基本设置</a>
							</li>
							<li>
								<a href="#aboutInfo" data-toggle="tab"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>信息</a>
							</li>
<!--							<li>--><!--//2020/8/21 位置转移 收费管理->收费设置->缴费设置-->
<!--								<a href="#txtstore" data-toggle="tab">缴费配置</a>-->
<!--							</li>-->
							<li>
								<a href="#txtpwd" data-toggle="tab">功能配置</a>
							</li>
                            <!--li>
								<a href="#txtservice" data-toggle="tab">便民配置</a>
							</li-->
                            <li>
								<a href="#bindweixin" data-toggle="tab">绑定管理微信号</a>
							</li>
							<if condition="$config.plate_house_open eq 1 && $config.plate_house_way eq 2">
                            <li>
								<a href="#plate" data-toggle="tab">车牌识别配置</a>
							</li>
							</if>

                            <li>
                                <a href="#accessControl" data-toggle="tab">智能门禁配置</a>
                            </li>
                            <if condition="$isPmwlcDomain||$isHzDomain">
                            <li>
                                <a href="#basicSourceInfo" data-toggle="tab">智能设施配置</a>
                            </li>
                            </if>

                            <li>
                                <a href="#uploadImgInfo" data-toggle="tab">上传图片设置</a>
                            </li>

							<!--新版隐藏配置人脸门禁管理，不做删除，以防查证-->
							<if condition="$config['house_door_docking_open'] eq 1 && 0">
                            <li>
                                <a href="#door_docking" data-toggle="tab">人脸门禁管理</a>
                            </li>
                        	</if>
                            <if condition="C('config.customized_meter_reading') OR $isYumenLaoSpecialMeter">
                            <li>
                                <a href="#auto_meter" data-toggle="tab">自动抄表设置</a>
                            </li>
                            </if>
							<!--新版隐藏配置新冠肺炎防护配置，不做删除，以防查证-->
<!--							<li>-->
<!--                                <a href="#ncov_2019" data-toggle="tab">新冠肺炎防护配置</a>-->
<!--                            </li>-->
                            <li>
                                <a href="#orderCheckControl" data-toggle="tab">订单退款(作废)审核设置</a>
                            </li>
                            <if condition="$third_import_data_switch_judge">
                                <li>
                                    <a href="#third_import" data-toggle="tab">车位绑定房屋</a>
                                </li>
                            </if>
                            <if condition="$orderModifyTitle && $orderModifyTitle neq ''">
                                <li>
                                    <a href="#orderCheckModifyControl" data-toggle="tab">{pigcms{$orderModifyTitle}设置</a>
                                </li>
                            </if>
                            <if condition="$config['hikGatewayHost'] && $config['hikGatewayHost'] neq ''">
                                <li>
                                    <a href="#hikDeviceGateway" data-toggle="tab" class="addEventClass">Hik Device Gateway 海康网关(小区)</a>
                                </li>
                            </if>
                           <li>
                                <a href="#orderServiceTimeControl" data-toggle="tab">房间计费时间修改审核设置</a>
                            </li>
                            <if condition="$is_aihorse_customized"> 
                            <li>
                                <a href="#orderModifyPayTimeControl" data-toggle="tab">已缴账单修改支付时间审核设置</a>
                            </li>
                            </if>
						</ul>
					</div>
					<form id="edit_form" method="post" class="form-horizontal" action="{pigcms{:U('Index/village_edit')}" enctype="multipart/form-data">
						<div class="tab-content">
                            <if condition="C('config.customized_meter_reading')">
                            <div class="tab-pane" id="auto_meter">

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="plot_area">公司名称</label></label>
                                    <input class="col-sm-2" size="20" name="company_name" value="{pigcms{$village_info['company_name']}" type="text" style=""/>
                                    <span class="form_tips" style="color: gray;margin-left:5px;">注释：获取水表信息，设置公司名称</span>
                                </div>
	                            
                                <div class="form-group">
                                    <label class="col-sm-2"><label for="has_index_nav">自动抄表扣费日期设置</label></label>
                                    <span>每</span>
                                    <select name="meter_type" id="meter_type" style="width: 100px">
                                        <option value="1" <if condition="$village_info['meter_type'] eq 1">selected="selected"</if> >月</option>
                                        <option value="2" <if condition="$village_info['meter_type'] eq 2">selected="selected"</if> >日</option>
                                    </select>
                                    <select name="meter_day" id="meter_day" style="width: 100px">
                                        <option value="01" <if condition="$village_info['meter_time'] eq '01'">selected="selected"</if> >01</option>
                                        <option value="02" <if condition="$village_info['meter_time'] eq '02'">selected="selected"</if> >02</option>
                                        <option value="03" <if condition="$village_info['meter_time'] eq '03'">selected="selected"</if> >03</option>
                                        <option value="04" <if condition="$village_info['meter_time'] eq '04'">selected="selected"</if> >04</option>
                                        <option value="05" <if condition="$village_info['meter_time'] eq '05'">selected="selected"</if> >05</option>
                                        <option value="06" <if condition="$village_info['meter_time'] eq '06'">selected="selected"</if> >06</option>
                                        <option value="07" <if condition="$village_info['meter_time'] eq '07'">selected="selected"</if> >07</option>
                                        <option value="08" <if condition="$village_info['meter_time'] eq '08'">selected="selected"</if> >08</option>
                                        <option value="09" <if condition="$village_info['meter_time'] eq '09'">selected="selected"</if> >09</option>
                                        <option value="10" <if condition="$village_info['meter_time'] eq '10'">selected="selected"</if> >10</option>
                                        <option value="11" <if condition="$village_info['meter_time'] eq '11'">selected="selected"</if> >11</option>
                                        <option value="12" <if condition="$village_info['meter_time'] eq '12'">selected="selected"</if> >12</option>
                                        <option value="13" <if condition="$village_info['meter_time'] eq '13'">selected="selected"</if> >13</option>
                                        <option value="14" <if condition="$village_info['meter_time'] eq '14'">selected="selected"</if> >14</option>
                                        <option value="15" <if condition="$village_info['meter_time'] eq '15'">selected="selected"</if> >15</option>
                                        <option value="16" <if condition="$village_info['meter_time'] eq '16'">selected="selected"</if> >16</option>
                                        <option value="17" <if condition="$village_info['meter_time'] eq '17'">selected="selected"</if> >17</option>
                                        <option value="18" <if condition="$village_info['meter_time'] eq '18'">selected="selected"</if> >18</option>
                                        <option value="19" <if condition="$village_info['meter_time'] eq '19'">selected="selected"</if> >19</option>
                                        <option value="20" <if condition="$village_info['meter_time'] eq '20'">selected="selected"</if> >20</option>
                                        <option value="21" <if condition="$village_info['meter_time'] eq '21'">selected="selected"</if> >21</option>
                                        <option value="22" <if condition="$village_info['meter_time'] eq '22'">selected="selected"</if> >22</option>
                                        <option value="23" <if condition="$village_info['meter_time'] eq '23'">selected="selected"</if> >23</option>
                                        <option value="24" <if condition="$village_info['meter_time'] eq '24'">selected="selected"</if> >24</option>
                                        <option value="25" <if condition="$village_info['meter_time'] eq '25'">selected="selected"</if> >25</option>
                                        <option value="26" <if condition="$village_info['meter_time'] eq '26'">selected="selected"</if> >26</option>
                                        <option value="27" <if condition="$village_info['meter_time'] eq '27'">selected="selected"</if> >27</option>
                                        <option value="28" <if condition="$village_info['meter_time'] eq '28'">selected="selected"</if> >28</option>
                                        <option value="29" <if condition="$village_info['meter_time'] eq '29'">selected="selected"</if> >29</option>
                                        <option value="30" <if condition="$village_info['meter_time'] eq '30'">selected="selected"</if> >30</option>
                                    </select>
                                    <select name="meter_time" id="meter_time" style="width: 100px;display: none">
                                        <option value="00" <if condition="$village_info['meter_time'] eq '00'">selected="selected"</if> >00:00</option>
                                        <option value="01" <if condition="$village_info['meter_time'] eq '01'">selected="selected"</if> >01:00</option>
                                        <option value="02" <if condition="$village_info['meter_time'] eq '02'">selected="selected"</if> >02:00</option>
                                        <option value="03" <if condition="$village_info['meter_time'] eq '03'">selected="selected"</if> >03:00</option>
                                        <option value="04" <if condition="$village_info['meter_time'] eq '04'">selected="selected"</if> >04:00</option>
                                        <option value="05" <if condition="$village_info['meter_time'] eq '05'">selected="selected"</if> >05:00</option>
                                        <option value="06" <if condition="$village_info['meter_time'] eq '06'">selected="selected"</if> >06:00</option>
                                        <option value="07" <if condition="$village_info['meter_time'] eq '07'">selected="selected"</if> >07:00</option>
                                        <option value="08" <if condition="$village_info['meter_time'] eq '08'">selected="selected"</if> >08:00</option>
                                        <option value="09" <if condition="$village_info['meter_time'] eq '09'">selected="selected"</if> >09:00</option>
                                        <option value="10" <if condition="$village_info['meter_time'] eq '10'">selected="selected"</if> >10:00</option>
                                        <option value="11" <if condition="$village_info['meter_time'] eq '11'">selected="selected"</if> >11:00</option>
                                        <option value="12" <if condition="$village_info['meter_time'] eq '12'">selected="selected"</if> >12:00</option>
                                        <option value="13" <if condition="$village_info['meter_time'] eq '13'">selected="selected"</if> >13:00</option>
                                        <option value="14" <if condition="$village_info['meter_time'] eq '14'">selected="selected"</if> >14:00</option>
                                        <option value="15" <if condition="$village_info['meter_time'] eq '15'">selected="selected"</if> >15:00</option>
                                        <option value="16" <if condition="$village_info['meter_time'] eq '16'">selected="selected"</if> >16:00</option>
                                        <option value="17" <if condition="$village_info['meter_time'] eq '17'">selected="selected"</if> >17:00</option>
                                        <option value="18" <if condition="$village_info['meter_time'] eq '18'">selected="selected"</if> >18:00</option>
                                        <option value="19" <if condition="$village_info['meter_time'] eq '19'">selected="selected"</if> >19:00</option>
                                        <option value="20" <if condition="$village_info['meter_time'] eq '20'">selected="selected"</if> >20:00</option>
                                        <option value="21" <if condition="$village_info['meter_time'] eq '21'">selected="selected"</if> >21:00</option>
                                        <option value="22" <if condition="$village_info['meter_time'] eq '22'">selected="selected"</if> >22:00</option>
                                        <option value="23" <if condition="$village_info['meter_time'] eq '23'">selected="selected"</if> >23:00</option>
                                    </select>
                                    <span>电表设备自动进行抄表并扣除用户费用</span>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-2"><label for="has_index_nav">自动停水或停电设置</label></label>
                                    <span>自动抄表 &nbsp;&nbsp;<input type="number" max="30" min="0" name="meter_nopayorder_date"  placeholder="请输入时间" value="{pigcms{$village_info['meter_nopayorder_date']}" type="text" style="width: 150px;height: 30px;" />天后</span>
                                    <span class="form_tips" style="color: gray;margin-left:5px;">余额不足抵扣待缴账单，自动断水断电（ 业主住户余额不够抵扣水费、电费待缴账单，实施停水或停电，缴费后自动回复用水、用电）0表示不会自动断水断电</span>
                                </div>

                            </div>
                            <elseif condition="$isYumenLaoSpecialMeter" />
                                <div class="tab-pane" id="auto_meter">
                                    <div class="form-group">
                                        <label class="col-sm-2"><label for="has_index_nav">自动抄表扣费日期设置</label></label>
                                        <span>每</span>
                                        <select name="meter_type" id="meter_type" style="width: 100px">
                                            <option value="1" <if condition="$village_info['meter_type'] eq 1">selected="selected"</if> >月</option>
                                        </select>
                                        <select name="meter_day" id="meter_day" style="width: 100px">
                                            <option value="01" <if condition="$village_info['meter_time'] eq '01'">selected="selected"</if> >01</option>
                                            <option value="02" <if condition="$village_info['meter_time'] eq '02'">selected="selected"</if> >02</option>
                                            <option value="03" <if condition="$village_info['meter_time'] eq '03'">selected="selected"</if> >03</option>
                                            <option value="04" <if condition="$village_info['meter_time'] eq '04'">selected="selected"</if> >04</option>
                                            <option value="05" <if condition="$village_info['meter_time'] eq '05'">selected="selected"</if> >05</option>
                                            <option value="06" <if condition="$village_info['meter_time'] eq '06'">selected="selected"</if> >06</option>
                                            <option value="07" <if condition="$village_info['meter_time'] eq '07'">selected="selected"</if> >07</option>
                                            <option value="08" <if condition="$village_info['meter_time'] eq '08'">selected="selected"</if> >08</option>
                                            <option value="09" <if condition="$village_info['meter_time'] eq '09'">selected="selected"</if> >09</option>
                                            <option value="10" <if condition="$village_info['meter_time'] eq '10'">selected="selected"</if> >10</option>
                                            <option value="11" <if condition="$village_info['meter_time'] eq '11'">selected="selected"</if> >11</option>
                                            <option value="12" <if condition="$village_info['meter_time'] eq '12'">selected="selected"</if> >12</option>
                                            <option value="13" <if condition="$village_info['meter_time'] eq '13'">selected="selected"</if> >13</option>
                                            <option value="14" <if condition="$village_info['meter_time'] eq '14'">selected="selected"</if> >14</option>
                                            <option value="15" <if condition="$village_info['meter_time'] eq '15'">selected="selected"</if> >15</option>
                                            <option value="16" <if condition="$village_info['meter_time'] eq '16'">selected="selected"</if> >16</option>
                                            <option value="17" <if condition="$village_info['meter_time'] eq '17'">selected="selected"</if> >17</option>
                                            <option value="18" <if condition="$village_info['meter_time'] eq '18'">selected="selected"</if> >18</option>
                                            <option value="19" <if condition="$village_info['meter_time'] eq '19'">selected="selected"</if> >19</option>
                                            <option value="20" <if condition="$village_info['meter_time'] eq '20'">selected="selected"</if> >20</option>
                                            <option value="21" <if condition="$village_info['meter_time'] eq '21'">selected="selected"</if> >21</option>
                                            <option value="22" <if condition="$village_info['meter_time'] eq '22'">selected="selected"</if> >22</option>
                                            <option value="23" <if condition="$village_info['meter_time'] eq '23'">selected="selected"</if> >23</option>
                                            <option value="24" <if condition="$village_info['meter_time'] eq '24'">selected="selected"</if> >24</option>
                                            <option value="25" <if condition="$village_info['meter_time'] eq '25'">selected="selected"</if> >25</option>
                                            <option value="26" <if condition="$village_info['meter_time'] eq '26'">selected="selected"</if> >26</option>
                                            <option value="27" <if condition="$village_info['meter_time'] eq '27'">selected="selected"</if> >27</option>
                                            <option value="28" <if condition="$village_info['meter_time'] eq '28'">selected="selected"</if> >28</option>
                                            <option value="29" <if condition="$village_info['meter_time'] eq '29'">selected="selected"</if> >29</option>
                                            <option value="30" <if condition="$village_info['meter_time'] eq '30'">selected="selected"</if> >30</option>
                                        </select>
                                        <span>水电表设备自动进行抄表并生成待缴账单，如果用户的余额足够并自动扣除用户余额缴费</span>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2"><label for="has_index_nav">自动停水或停电设置</label></label>
                                        <span>自动抄表 &nbsp;&nbsp;<input type="number" max="30" min="0" name="meter_nopayorder_date"  placeholder="请输入时间" value="{pigcms{$village_info['meter_nopayorder_date']}" type="text" style="width: 150px;height: 30px;" />天后</span>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">待缴账单还未交费的，自动断水断电（水费、电费待缴账单还未缴纳的，实施停水或停电，缴费后自动回复用水、用电）0表示不会自动断水断电</span>
                                    </div>
                                    <!--
                                    <p>以下是对接表设置</p>
                                    <hr>
                                    <div class="form-group">
                                        <label class="col-sm-2"><label for="has_index_nav">泰安轻松表计对接URL</label></label>
									      <input class="col-sm-4" size="20" name="meter_apiset[easymeter][base_url]" id="meter_apiset_easymeter_base_url" type="text" value="{pigcms{$meter_apiset.easymeter.base_url}"/>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2"><label for="has_index_nav">泰安轻松表计对接KEY</label></label>
									     <input class="col-sm-4" size="20" name="meter_apiset[easymeter][key]" id="meter_apiset_easymeter_base_key" type="text" value="{pigcms{$meter_apiset.easymeter.key}"/>
                                    </div>
                                    --->
                                </div>
                            </if>

							<div class="tab-pane active" id="basicinfo">
								<!--<div class="form-group">
									<label class="col-sm-1"><label for="name">物业名称</label></label>
									<input class="col-sm-2" size="20" value="{pigcms{$property.property_name}" type="text" style="border:none;background:white!important;" readonly/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="property_phone">物业联系方式</label></label>
									<input class="col-sm-2" size="20" value="{pigcms{$property.property_phone}" type="text" style="border:none;background:white!important;" readonly/>
									<span class="form_tips" style="color: gray;margin-left:1px;">(电话号码以空格分开)</span>
								</div>-->
								<div class="form-group">
									<label class="col-sm-1"><label for="property_phone">物业联系方式</label></label>
									<input class="col-sm-2" size="20" name="property_phone" value="{pigcms{$village_info['property_phone']}" type="text" style=""/>
									<span class="form_tips" style="color: gray;margin-left:1px;">(电话号码以空格分开)
                                    </span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="property_address">物业联系地址</label></label>
									<input class="col-sm-2" size="20" name="property_address" id="property_address" type="text" value="{pigcms{$village_info.property_address}"/>
								</div>

                                <!-- todo 办公时间-->
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="work_time">办公时间</label></label>
                                    <input type="text" class="input-text valid" name="work_time[]" value="{pigcms{$village_info.start_time}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">&nbsp;-&nbsp;&nbsp;
                                    <input type="text" class="input-text" name="work_time[]" value="{pigcms{$village_info.end_time}" size="5" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})">
                                </div>


								<div class="form-group">
									<label class="col-sm-1"><label for="village_name">{pigcms{$config['house_name']}名称</label></label>
									<input class="col-sm-2" size="20" value="{pigcms{$village_info.village_name}" id="village_name" type="text" style="border:none;background:white!important;" readonly/>
								</div>
                                <if condition="$isAihorseCustomized">
								<div class="form-group">
									<label class="col-sm-1"><label for="village_name">公司代码</label></label>
									<input class="col-sm-2" size="20" value="{pigcms{$village_info.village_code}" name="village_code" id="village_code" type="text"/>
                                    <span class="form_tips" style="color: gray;margin-left:5px;">请填写公司的字母代码列如 HHM 这样的字符串来代表公司，用于打印编码中</span>
								</div>
                                </if>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="village_logo">{pigcms{$config['house_name']}logo</label></label>
                                    <input class="btn btn-primary" type="button" id="image3" value="选择图片" />
                                    <input type="hidden" name="village_logo" value="{pigcms{$village_info.village_logo}" id="village_logo" />

                                    <img src="{pigcms{:replace_file_domain($village_info['village_logo'])}" id="village_logo_view" style="padding: 15px; width: 100px; <if condition="!$village_info['village_logo']">display: none;</if>">

                                    <span class="form_tips">建议上传200*200的图片</span>
                                </div>
                                <if condition="$is_aihorse_customized">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="village_seal_img">{pigcms{$config['house_name']}电子印章图片</label></label>
                                    <input class="btn btn-primary" type="button" id="seal_img" value="选择图片" />
                                    <input type="hidden" name="seal_img" value="{pigcms{$village_info.seal_img}" id="village_seal_img" />

                                    <img src="{pigcms{:replace_file_domain($village_info['seal_img'])}" id="village_seal_view" style="padding: 15px; width: 120px; <if condition="!$village_info['seal_img']">display: none;</if>">
                                </div>
                                </if>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="contract_time">合同时间</label></label>
                                    <input type="text" name="contract_time_start" class="input-text" value="{pigcms{$village_about_info.contract_time_start}"  style="height:30px;" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="合同开始时间"/>
                                    &nbsp;—&nbsp;
                                    <input type="text" name="contract_time_end" class="input-text" value="{pigcms{$village_about_info.contract_time_end}"  style="height:30px;" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="合同结束时间"/>&nbsp;&nbsp;&nbsp;&nbsp;
                                    <span class="form_tips">设置物业服务<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>时间范围，设置后物业只能收取合同时间内收费项目，未到合同开始时间或结束时间不能收费；未设置物业服务小区时间范围，则不影响。</span>
                                </div>
                                <if condition="$config['property_self_logo'] eq 1 ">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="house_village_login"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>登录地址</label></label>
                                        <input class="col-sm-4" id="house_village_login" value="{pigcms{$house_village_login}" type="text" readonly/>
                                        <span class="form_tips">
                                            <div style="line-height: 16px;" class="btn btn-success" onclick="copyContent();">点击复制</div>
                                        </span>
                                    </div>
                                </if>
<!--                                <div class="form-group">-->
<!--                                    <label class="col-sm-1">{pigcms{$config['house_name']}微信二维码</label>-->
<!--                                    <input class="btn btn-primary" type="button" id="weixin_qr_code_put" value="选择图片" />-->
<!--                                    <input type="hidden" name="weixin_qr_code" value="{pigcms{$village_info.weixin_qr_code}" id="weixin_qr_code" />-->
<!---->
<!--                                    <img src="{pigcms{:replace_file_domain($village_info['weixin_qr_code'])}" id="weixin_qr_code_view" style="padding: 15px; width: 80px; <if condition="!$village_info['weixin_qr_code']">display: none;</if>">-->
<!---->
<!--                                    <span class="form_tips">建议上传200*200的图片</span>-->
<!--                                </div>-->
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="property_phone">物业联系方式</label></label>-->
<!--									<input class="col-sm-2" size="20" name="property_phone" id="property_phone" type="text" value="{pigcms{$village_info.property_phone}"/>-->
<!--									<span class="form_tips">多个电话号码以空格分开</span>-->
<!--								</div>-->
<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="property_address">物业联系地址</label></label>-->
<!--									<input class="col-sm-2" size="20" name="property_address" id="property_address" type="text" value="{pigcms{$village_info.property_address}"/>-->
<!--								</div>-->
								<div class="form-group">
									<label class="col-sm-1"><label for="long_lat">{pigcms{$config['house_name']}经纬度</label></label>
									<input class="col-sm-2" size="10" name="long_lat" id="long_lat" type="text"  value="<if condition="$village_info['long']">{pigcms{$village_info.long},{pigcms{$village_info.lat}</if>"/>
									&nbsp;&nbsp;&nbsp;&nbsp;<a href="#modal-table" class="btn btn-sm btn-success" id="show_map_frame" data-toggle="modal">点击选取经纬度</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>所在省市区</label></label>
<!--									<fieldset id="choose_cityarea" province_id="{pigcms{$village_info.province_id}" city_id="{pigcms{$village_info.city_id}" area_id="{pigcms{$village_info.area_id}" circle_id="{pigcms{$village_info.circle_id}"></fieldset>-->

                                    <fieldset id="choose_street" province_idss="{pigcms{$village_info.province_id}" city_idss="{pigcms{$village_info.city_id}" area_id="{pigcms{$village_info.area_id}" street_idss="{pigcms{$village_info.street_id}" community_idss="{pigcms{$village_info.community_id}"></fieldset>
                                </div>
								<div class="form-group">
									<label class="col-sm-1"><label for="village_address">{pigcms{$config['house_name']}地址</label></label>
									<input class="col-sm-3" name="village_address" id="adress" type="text" value="{pigcms{$village_info.village_address}"/>
									<span class="form_tips">地址不能带有上面所在地选择的省/市/区/街道/社区信息。</span>
								</div>


                                <div class="form-group">
                                    <label class="col-sm-1 title_right"><label for="phone">该<?php echo ((D('House_village')->getHouseAlias('house_name')?:'小区').(D('House_village')->getHouseAlias('single_name')?:'楼栋')) ?>是否超过100<?php echo (D('House_village')->getHouseAlias('single_name')?:'栋') ?>：</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input type="radio" class="ace" value="3" disabled <if condition="$village_info.village_single_support_digit eq 3">checked</if>><span style="z-index: 1" class="lbl">是</span></label>
                                    <label style="padding-left:0px;"><input type="radio" class="ace" value="2" disabled <if condition="$village_info.village_single_support_digit eq 2">checked</if>><span style="z-index: 1" class="lbl">否</span></label>
                                    <span class="form_tips" style="color: gray;margin-left:5px;">选择是，<?php echo (D('House_village')->getHouseAlias('single_name')?:'楼栋') ?>号即可支持三位， 不支持编辑</span>
                                </div>

<!--								<div class="form-group">-->
<!--									<label class="col-sm-1"><label for="diy_village_name">个性化名称设置</label></label>-->
<!--									<input class="col-sm-2" name="diy_village_name" id="diy_village_name" type="text" value="{pigcms{$village_info.diy_village_name}"/>-->
<!--									<span class="form_tips"><span style="color: red;">{pigcms{$village_param_txt}</span> 为变量值，可删除，文字可进行编辑，可设置专属的楼栋单元房间名，设置成功后，您的小区的所有地方的楼栋、单元、房间的命名将会随之变化。</span>-->
<!--								</div>-->
								
								<div class="form-group">
									<label class="col-sm-1"><label for="diy_village_name">IC卡云读写</label></label>
									<div class="radio">
										<label style="padding-left:0px;padding-right:20px;"><input name="write_iccard" value="1" type="radio" class="ace" <if condition="$village_info.write_iccard eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
										<label style="padding-left:0px;"><input name="write_iccard" value="0" type="radio" class="ace" <if condition="$village_info.write_iccard eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
										<span class="form_tips">开启后业主管理页面将会开启IC卡读写的功能。需要另外购买读IC卡硬件进行支持。</span>
									</div>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="diy_village_name">身份证云读取</label></label>
									<div class="radio">
										<label style="padding-left:0px;padding-right:20px;"><input name="read_idcard" value="1" type="radio" class="ace" <if condition="$village_info.read_idcard eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
										<label style="padding-left:0px;"><input name="read_idcard" value="0" type="radio" class="ace" <if condition="$village_info.read_idcard eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
										<span class="form_tips">开启后业主管理页面将会开启身份证信息读取的功能。需要另外购买身份证阅读硬件进行支持。</span>
									</div>
								</div>

                                <if condition="$set_house_alias==1">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="single_name">楼栋别名</label></label>
                                        <input class="col-sm-3" name="single_name" id="adress" type="text" value="{pigcms{$village_info.single_name}"/>
                                        <span class="form_tips">默认显示"楼栋"，可以重命名。</span>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="floor_name">单元别名</label></label>
                                        <input class="col-sm-3" name="floor_name" id="adress" type="text" value="{pigcms{$village_info.floor_name}"/>
                                        <span class="form_tips">默认显示"单元"，可以重命名。</span>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="room_name">房间别名</label></label>
                                        <input class="col-sm-3" name="room_name" id="adress" type="text" value="{pigcms{$village_info.room_name}"/>
                                        <span class="form_tips">默认显示"房间"，可以重命名。</span>
                                    </div>
                                </if>
							</div>
                            <if  condition="$isPmwlcDomain||$isHzDomain" >
                            <div class="tab-pane" id="basicSourceInfo">
                                <if condition="$isHzDomain">
                                    <div class="form-group">
                                        <label class="col-sm-1" style="padding-top: 15px;"><label for="village_logo"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>楼盘底图</label></label>
                                        <input type="hidden" name="village_floor" value="{pigcms{$village_about_info.village_floor}" id="village_floor" />
                                        <img src="{pigcms{:replace_file_domain($village_about_info['village_floor'])}" id="village_floor_view" <if condition="!$village_about_info['village_floor']">style="padding: 15px; width: 80px;display: none;"<else/>style="padding: 15px; width: 80px;"</if>>
                                        <input class="btn btn-primary" type="button" id="image_basic" value="上传图片" />
                                        <input class="btn btn-primary" type="button" id='dddddddd'  value="绘制区域" style="margin-left: 15px;"/>
                                        <p class="form_tips" style="margin-left: 140px;color: #a6a4a4;">上传的图片仅支持JPG、PNG格式，图片大小不能大于2M，用于驾驶舱首页地图展示，上传后，点击“绘制区域”可手动绘制区域绑定<?php echo ((D('House_village')->getHouseAlias('house_name')?:'小区').(D('House_village')->getHouseAlias('single_name')?:'楼栋')) ?></p>
                                    </div>
                                <else/>
                                    <div class="form-group">
                                        <label class="col-sm-1" style="padding-top: 15px;"><label for="village_logo"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>楼盘底图</label></label>
                                        <input type="hidden" name="village_floor" value="{pigcms{$village_about_info.village_floor}" id="village_floor" />
                                        <img src="{pigcms{:replace_file_domain($village_about_info['village_floor'])}" id="village_floor_view" <if condition="!$village_about_info['village_floor']">style="padding: 15px; width: 80px;display: none;"<else/>style="padding: 15px; width: 80px;"</if>>
                                        <input class="btn btn-primary" type="button" id="image_basic" value="上传图片" />
                                        <input class="btn btn-primary" type="button" id='dddddddd'  value="绘制区域" style="margin-left: 15px;"/>
                                        <p class="form_tips" style="margin-left: 140px;color: #a6a4a4;">上传的图片仅支持JPG、PNG格式，图片大小不能大于2M，用于驾驶舱首页地图展示，上传后，点击“绘制区域”可手动绘制区域绑定<?php echo ((D('House_village')->getHouseAlias('house_name')?:'小区').(D('House_village')->getHouseAlias('single_name')?:'楼栋')) ?></p>
                                    </div>
                                    <div class="form-group" style="display:none;">
                                        <label class="col-sm-1" style="padding-top: 15px;"><label for="village_logo"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>平面图</label></label>
                                        <input type="hidden" name="village_photo" value="{pigcms{$village_about_info.village_photo}" id="village_photo" />
                                        <img src="{pigcms{:replace_file_domain($village_about_info['village_photo'])}" id="village_photo_view" <if condition="!$village_about_info['village_photo']">style="padding: 15px; width: 80px;display: none;"<else/>style="padding: 15px; width: 80px;"</if>>
                                        <input class="btn btn-primary" type="button" id="image_basic1" value="上传图片" />
                                        <p class="form_tips" style="margin-left: 140px;color: #a6a4a4;">上传的图片仅支持JPG、PNG格式，图片大小不能大于2M，用于<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>智能设施分布图馆里展示，上传后，添加硬件设施时，可直接在该图中标记点位</p>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1" style="padding-top: 15px;"><label for="village_logo">室外设备地图</label></label>
                                        <input type="hidden" name="village_photo" value="{pigcms{$village_about_info.village_photo}" id="village_photo01" />
                                        <img src="{pigcms{:replace_file_domain($village_about_info['village_photo'])}" id="village_photo_view01"  <if condition="!$village_about_info['village_photo']">style="padding: 15px; width: 80px;display: none;"<else/>style="padding: 15px; width: 80px;"</if>>
                                        <input class="btn btn-primary" type="button" id="image_basic01" value="上传图片" />
                                        <input class="btn btn-primary" type="button" id='adress4'  value="绘制点位" style="margin-left: 15px;" />
                                        <p class="form_tips" style="margin-left: 140px;color: #a6a4a4;">上传的图片仅支持JPG、PNG格式，图片大小不能大于2M，用于<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>智能设施分布图馆里展示，上传后，添加硬件设施时，可直接在该图中标记点位</p>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1" style="padding-top: 15px;"><label for="village_logo">室内设备地图</label></label>
                                        <div style="display: flex;flex-direction: row">
                                            <input type="hidden" name="village_photo2" value="{pigcms{$village_about_info.village_photo2}" id="village_photo2" />
                                            <img src="{pigcms{:replace_file_domain($village_about_info['village_photo2'])}" id="village_photo_view2" <if condition="!$village_about_info['village_photo2']">style="padding: 15px; width: 80px;display: none;"<else/>style="padding: 15px; width: 80px;"</if>>
                                            <input style="height: 34px;" class="btn btn-primary" type="button" id="image_basic12" value="上传图片" />
                                            <input style="height: 34px;margin-left: 15px;" class="btn btn-primary" type="button" id='adress1'  value="绘制点位"  style="margin-left: 15px;"/>
                                            <p class="form_tips" style="color: #a6a4a4;">地下一层</p>
                                            <div style="margin-left: 30px;display: inline-block;display: flex;flex-direction: row">
                                                <input type="hidden" name="village_photo1" value="{pigcms{$village_about_info.village_photo1}" id="village_photo1" />
                                                <img src="{pigcms{:replace_file_domain($village_about_info['village_photo1'])}" id="village_photo_view1" <if condition="!$village_about_info['village_photo1']">style="padding: 15px; width: 80px;display: none;"<else/>style="padding: 15px; width: 80px;"</if>>
                                                <input style="height: 34px;" class="btn btn-primary" type="button" id="image_basic11" value="上传图片" />
                                                <input style="height: 34px;margin-left: 15px;" class="btn btn-primary" type="button" id='adress2'  value="绘制点位"  style="margin-left: 15px;"/>
                                                <p class="form_tips" style="color: #a6a4a4;">地下二层</p>
                                            </div>
                                        </div>


                                        <p class="form_tips" style="margin-left: 140px;color: #a6a4a4;">上传的图片仅支持JPG、PNG格式，图片大小不能大于2M，用于<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>智能设施分布图馆里展示，上传后，添加硬件设施时，可直接在该图中标记点位</p>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-1" style="padding-top: 15px;"><label for="village_logo">天使之眼地图</label></label>
                                        <input type="hidden" name="village_photo3" value="{pigcms{$village_about_info.village_photo3}" id="village_photo3" />
                                        <img src="{pigcms{:replace_file_domain($village_about_info['village_photo3'])}" id="village_photo_view3" <if condition="!$village_about_info['village_photo3']">style="padding: 15px; width: 80px;display: none;"<else/>style="padding: 15px; width: 80px;"</if>>
                                        <input class="btn btn-primary" type="button" id="image_basic21" value="上传图片" />
                                        <input class="btn btn-primary" type="button" id='adress3'  value="绘制点位"  style="margin-left: 15px;"/>
                                        <p class="form_tips" style="margin-left: 140px;color: #a6a4a4;">上传的图片仅支持JPG、PNG格式，图片大小不能大于2M，用于<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>智能设施分布图馆里展示，上传后，添加硬件设施时，可直接在该图中标记点位</p>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-1" style="padding-top: 15px;"><label>驾驶舱LOGO</label></label>
                                        <input type="hidden" name="village_cockpit_logo" value="{pigcms{$village_about_info.village_cockpit_logo}" id="village_cockpit_logo" />
                                        <img src="{pigcms{:replace_file_domain($village_about_info['village_cockpit_logo'])}" id="village_cockpit_logo_view" <if condition="!$village_about_info['village_cockpit_logo']">style="padding: 15px; width: 80px;display: none;"<else/>style="padding: 15px; width: 80px;"</if>>
                                        <input class="btn btn-primary" type="button" id="village_cockpit_logo_img" value="上传图片" />
                                        <p class="form_tips" style="margin-left: 140px;color: #a6a4a4;">上传的图片仅支持JPG、PNG格式，图片大小不能大于2M</p>
                                    </div>

                                </if>
                            </div>
                          </if>
							<div class="tab-pane" id="aboutInfo">
                                <div class="flex form-group">
                                    <table class="table table-bordered" style="width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0;">
                                        <thead>
                                        <tr style="background: #f8f9fa;">
                                            <!-- 固定首列表头：房屋类型 -->
                                            <th style="position: sticky; left: 0; z-index: 3; background: #f8f9fa; width: 120px; text-align: center; border: 1px solid #e5e6eb; padding: 8px 5px;">
                                                房屋类型
                                            </th>
                                            <!-- 其他表头：已售/未售/已交/未交 -->
                                            <th style="position: sticky; top: 0; z-index: 2; background: #f8f9fa; width: 100px; text-align: center; border: 1px solid #e5e6eb; padding: 8px 5px;">
                                                已售
                                            </th>
                                            <th style="position: sticky; top: 0; z-index: 2; background: #f8f9fa; width: 100px; text-align: center; border: 1px solid #e5e6eb; padding: 8px 5px;">
                                                未售
                                            </th>
                                            <th style="position: sticky; top: 0; z-index: 2; background: #f8f9fa; width: 100px; text-align: center; border: 1px solid #e5e6eb; padding: 8px 5px;">
                                                已交
                                            </th>
                                            <th style="position: sticky; top: 0; z-index: 2; background: #f8f9fa; width: 100px; text-align: center; border: 1px solid #e5e6eb; padding: 8px 5px;">
                                                未交
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <!-- 住宅行：name命名规则 类型_字段，方便后端解析 -->
                                        <tr>
                                            <td style="position: sticky; left: 0; z-index: 1; background: #fff; font-weight: 500; text-align: center; border: 1px solid #e5e6eb; padding: 8px 5px;">
                                                住宅
                                            </td>
                                            <td style="text-align: center; border: 1px solid #e5e6eb; padding: 5px;"><input type="number" value="{pigcms{$village_about_info['zhuzhai_yishou']}" name="house[zhuzhai][yishou]" class="form-control input-sm" placeholder="" style="width: 100%; margin: 0 auto;"></td>
                                            <td style="text-align: center; border: 1px solid #e5e6eb; padding: 5px;"><input type="number" value="{pigcms{$village_about_info['zhuzhai_weishou']}" name="house[zhuzhai][weishou]" class="form-control input-sm" placeholder="" style="width: 100%; margin: 0 auto;"></td>
                                            <td style="text-align: center; border: 1px solid #e5e6eb; padding: 5px;"><input type="number" value="{pigcms{$village_about_info['zhuzhai_yijiao']}" name="house[zhuzhai][yijiao]" class="form-control input-sm" placeholder="" style="width: 100%; margin: 0 auto;"></td>
                                            <td style="text-align: center; border: 1px solid #e5e6eb; padding: 5px;"><input type="number" value="{pigcms{$village_about_info['zhuzhai_weijiao']}" name="house[zhuzhai][weijiao]" class="form-control input-sm" placeholder="" style="width: 100%; margin: 0 auto;"></td>
                                        </tr>
                                        <!-- 商铺行 -->
                                        <tr>
                                            <td style="position: sticky; left: 0; z-index: 1; background: #fff; font-weight: 500; text-align: center; border: 1px solid #e5e6eb; padding: 8px 5px;">
                                                商铺
                                            </td>
                                            <td style="text-align: center; border: 1px solid #e5e6eb; padding: 5px;"><input type="number" value="{pigcms{$village_about_info['shangpu_yishou']}" name="house[shangpu][yishou]" class="form-control input-sm" placeholder="" style="width: 100%; margin: 0 auto;"></td>
                                            <td style="text-align: center; border: 1px solid #e5e6eb; padding: 5px;"><input type="number" value="{pigcms{$village_about_info['shangpu_weishou']}" name="house[shangpu][weishou]" class="form-control input-sm" placeholder="" style="width: 100%; margin: 0 auto;"></td>
                                            <td style="text-align: center; border: 1px solid #e5e6eb; padding: 5px;"><input type="number" value="{pigcms{$village_about_info['shangpu_yijiao']}" name="house[shangpu][yijiao]" class="form-control input-sm" placeholder="" style="width: 100%; margin: 0 auto;"></td>
                                            <td style="text-align: center; border: 1px solid #e5e6eb; padding: 5px;"><input type="number" value="{pigcms{$village_about_info['shangpu_weijiao']}" name="house[shangpu][weijiao]" class="form-control input-sm" placeholder="" style="width: 100%; margin: 0 auto;"></td>
                                        </tr>
                                        <!-- 车位行 -->
                                        <tr>
                                            <td style="position: sticky; left: 0; z-index: 1; background: #fff; font-weight: 500; text-align: center; border: 1px solid #e5e6eb; padding: 8px 5px;">
                                                车位
                                            </td>
                                            <td style="text-align: center; border: 1px solid #e5e6eb; padding: 5px;"><input type="number" value="{pigcms{$village_about_info['chewei_yishou']}" name="house[chewei][yishou]" class="form-control input-sm" placeholder="" style="width: 100%; margin: 0 auto;"></td>
                                            <td style="text-align: center; border: 1px solid #e5e6eb; padding: 5px;"><input type="number" value="{pigcms{$village_about_info['chewei_weishou']}" name="house[chewei][weishou]" class="form-control input-sm" placeholder="" style="width: 100%; margin: 0 auto;"></td>
                                            <td style="text-align: center; border: 1px solid #e5e6eb; padding: 5px;"><input type="number" value="{pigcms{$village_about_info['chewei_yijiao']}" name="house[chewei][yijiao]" class="form-control input-sm" placeholder="" style="width: 100%; margin: 0 auto;"></td>
                                            <td style="text-align: center; border: 1px solid #e5e6eb; padding: 5px;"><input type="number" value="{pigcms{$village_about_info['chewei_weijiao']}" name="house[chewei][weijiao]" class="form-control input-sm" placeholder="" style="width: 100%; margin: 0 auto;"></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
								<div class="flex form-group">
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="plot_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>占地面积</label></label>
                                        <input class="col-sm-3" size="20" name="plot_area" value="{pigcms{$village_about_info['plot_area']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
                                    </div>
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="decoration_num"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>装修中数量</label></label>
                                        <input class="col-sm-3" size="20" name="decoration_num" value="{pigcms{$village_about_info['decoration_num']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：(套)</span>
                                    </div>
								</div>
								<div class="flex form-group">
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="building_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>建筑面积</label></label>
                                        <input class="col-sm-3" size="20" name="building_area" value="{pigcms{$village_about_info['building_area']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
                                    </div>
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="decorated_num"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>已装修数量</label></label>
                                        <input class="col-sm-3" size="20" name="decorated_num" value="{pigcms{$village_about_info['decorated_num']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：(套)</span>
                                    </div>
								</div>
								<div class="flex form-group">
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="public_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>公共场所面积</label></label>
                                        <input class="col-sm-3" size="20" name="public_area" value="{pigcms{$village_about_info['public_area']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
                                    </div>
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="undecorate_num"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>未装修数量</label></label>
                                        <input class="col-sm-3" size="20" name="undecorate_num" value="{pigcms{$village_about_info['undecorate_num']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：(套)</span>
                                    </div>
								</div>
								<div class="flex form-group">
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="green_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>绿化面积</label></label>
                                        <input class="col-sm-3" size="20" name="green_area" value="{pigcms{$village_about_info['green_area']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
                                    </div>
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="non_motor_num"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>非机动车数量</label></label>
                                        <input class="col-sm-3" size="20" name="non_motor_num" value="{pigcms{$village_about_info['non_motor_num']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：(辆)</span>
                                    </div>
								</div>
								<div class="flex form-group">
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="garage_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>车库面积</label></label>
                                        <input class="col-sm-3" size="20" name="garage_area" value="{pigcms{$village_about_info['garage_area']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
                                    </div>
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="charger_num"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>充电桩数量</label></label>
                                        <input class="col-sm-3" size="20" name="charger_num" value="{pigcms{$village_about_info['charger_num']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：(个)</span>
                                    </div>
								</div>
								<div class="flex form-group">
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="design_parking_area"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>设计车位面积</label></label>
                                        <input class="col-sm-3" size="20" name="design_parking_area" value="{pigcms{$village_about_info['design_parking_area']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：㎡(平方米)</span>
                                    </div>
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="new_energy_num"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>新能源车辆数</label></label>
                                        <input class="col-sm-3" size="20" name="new_energy_num" value="{pigcms{$village_about_info['new_energy_num']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：(辆)</span>
                                    </div>
								</div>
                                <div class="flex form-group">
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="ground_charger_num"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>地面充电桩</label></label>
                                        <input class="col-sm-3" size="20" name="ground_charger_num" value="{pigcms{$village_about_info['ground_charger_num']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：(个)</span>
                                    </div>
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="fuel_num"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>燃油车辆</label></label>
                                        <input class="col-sm-3" size="20" name="fuel_num" value="{pigcms{$village_about_info['fuel_num']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：(辆)</span>
                                    </div>
                                </div>
                                <div class="flex form-group">
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="garage_charger_num"><?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>车库充电桩</label></label>
                                        <input class="col-sm-3" size="20" name="garage_charger_num" value="{pigcms{$village_about_info['garage_charger_num']}" type="text" style=""/>
                                        <span class="form_tips" style="color: gray;margin-left:5px;">单位：(个)</span>
                                    </div>
                                </div>
								<div class="flex form-group" <if condition="$village_about_info.park_new_switch eq 1">style="display:none"</if>>
                                    <div style="width: 50%;">
                                        <label class="col-sm-3"><label for="cars_park_num">车位数</label></label>
                                        <input class="col-sm-3" size="20" name="cars_park_num" value="{pigcms{$village_about_info['cars_park_num']}" type="text" style=""/>
                                    </div>
								</div>
								<div class="flex form-group">
                                 <div style="width: 50%;">
                                     <label class="col-sm-3"><label for="remark">备注</label></label>
                                     <textarea class="col-sm-3" size="20" name="remark" placeholder="请填写备注">{pigcms{$village_about_info['remark']}</textarea>
                                 </div>
								</div>
							</div>

							<div class="tab-pane" id="txtstore">
								<!--<div class="form-group">
									<label class="col-sm-1"><label for="property_price">物业费单价</label></label>
									<input class="col-sm-1" size="10" name="property_price" id="property_price" type="text" value="{pigcms{$village_info.property_price|floatval=###}"/>
									<span class="form_tips">{pigcms{$config['Currency_txt']}/平方米/月 （支持两位小数，设置为0表示不支持）</span>
								</div>-->
								<div class="form-group">
									<label class="col-sm-1"><label for="water_price">水费单价</label></label>
									<input class="col-sm-1" size="10" name="water_price" id="water_price" type="text" value="{pigcms{$village_info.water_price|floatval=###}" />
									<span class="form_tips">{pigcms{$config['Currency_txt']}/立方米 （支持两位小数，设置为0表示不支持）</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="electric_price">电费单价</label></label>
									<input class="col-sm-1" size="10" name="electric_price" id="electric_price" type="text" value="{pigcms{$village_info.electric_price|floatval=###}" />
									<span class="form_tips">{pigcms{$config['Currency_txt']}/千瓦时(度) （支持两位小数，设置为0表示不支持）</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="gas_price">燃气费单价</label></label>
									<input class="col-sm-1" size="10" name="gas_price" id="gas_price" type="text" value="{pigcms{$village_info.gas_price|floatval=###}"  />
									<span class="form_tips">{pigcms{$config['Currency_txt']}/立方米 （支持两位小数，设置为0表示不支持）</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="park_price">停车位单价</label></label>
									<input class="col-sm-1" size="10" name="park_price" id="park_price" type="text" value="{pigcms{$village_info.park_price|floatval=###}" />
									<span class="form_tips">{pigcms{$config['Currency_txt']}/月 （支持两位小数，设置为0表示不支持）</span>
								</div>

                                <div class="form-group">
                                    <label class="col-sm-2"><label for="refund_cycle">收银台已缴纳账单退款期限：收款后</label></label>
                                    <input class="col-sm-1" size="10" name="refund_cycle" id="refund_cycle" type="text" value="{pigcms{$village_info.refund_cycle}" />
                                    <span class="form_tips">（天）可进行退款</span>
                                </div>
								
								<div class="form-group" style="display:none">
									<label class="col-sm-1"><label for="express_collection_price">快递代送价格</label></label>
									<input class="col-sm-1" size="10" name="express_collection_price" id="express_collection_price" type="text" value="{pigcms{$village_info.express_collection_price|floatval=###}" />
									<span class="form_tips">{pigcms{$config['Currency_txt']} （不支持小数。）</span>
								</div>
								
								
								<!-- <div class="form-group">
									<label class="col-sm-1"><label for="property_warn_day">物业提醒时间</label></label>
									<input class="col-sm-1" size="10" name="property_warn_day" id="property_warn_day" type="text" value="{pigcms{$village_info.property_warn_day|floatval=###}" />
									<span class="form_tips">天 （不支持小数。开通后，即可向业主手动提前相应天数进行提醒发送模板消息。）</span>
								</div> -->
								
								
								<div class="form-group">
									<label class="col-sm-1"><label for="has_property_pay">自定义物业缴费</label></label>
									<div class="radio">
										<label style="padding-left:0px;padding-right:20px;"><input name="has_property_pay" value="1" type="radio" class="ace" <if condition="$village_info.has_property_pay eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
										<label style="padding-left:0px;"><input name="has_property_pay" value="0" type="radio" class="ace" <if condition="$village_info.has_property_pay eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
										<span class="form_tips">开通后，物业费可自定义上缴月份</span>
									</div>
								</div>
								
								
								<div class="form-group">
									<label class="col-sm-1"><label for="has_custom_pay">临时费用</label></label>
									<div class="radio">
										<label style="padding-left:0px;padding-right:20px;"><input name="has_custom_pay" value="1" type="radio" class="ace" <if condition="$village_info.has_custom_pay eq 1">checked</if>/><span class="lbl" style="z-index: 1">支持</span></label>
										<label style="padding-left:0px;"><input name="has_custom_pay" value="0" type="radio" class="ace" <if condition="$village_info.has_custom_pay eq 0">checked</if>/><span class="lbl" style="z-index: 1">不支持</span></label>
										<span class="form_tips">开通后，用户可以自定义名称、费用向物业发起缴费！方便物业上门维修等自定义收取费用</span>
									</div>
								</div>


								<div class="form-group">
									<label class="col-sm-1"><label for="send_date">自动催缴日期</label></label>
									每月
									<if condition="$village_info['send_date']">
										<input type="text" name="send_date" class="input-text" value="{pigcms{$village_info.send_date}" onfocus="WdatePicker({isShowClear:true,dateFmt:'d'})" placeholder="默认不通知" autocomplete="new-password" style="background-color: #FFFFFF!important;" readonly onfocus="this.removeAttribute('readonly');" onblur="this.setAttribute('readonly',true);"/>
										<else/>
										<input type="text" name="send_date" class="input-text" value="" onfocus="WdatePicker({isShowClear:true,dateFmt:'d'})" placeholder="默认不通知" autocomplete="new-password" style="background-color: #FFFFFF!important;" readonly onfocus="this.removeAttribute('readonly');" onblur="this.setAttribute('readonly',true);"/>
									</if>
									号
									<span class="form_tips">水费，电费，燃气费，停车费的自动催缴日期设置，不设置则不发送微信通知；物业费、自定义缴费的自动催缴则根据每个缴费周期到期时间来自动微信通知；以上微信通知均在当天的早上9:30分通知。</span>
								</div>

								<div class="form-group">
									<label class="col-sm-1"><label for="send_user_type">自动催缴发送人</label></label>
									<select name="send_user_type" id="send_user_type" style="width: 250px">
										<option value="1" <if condition="$village_info['send_user_type'] eq 1">selected="selected"</if> >仅业主</option>
										<option value="2" <if condition="$village_info['send_user_type'] eq 2">selected="selected"</if> >业主和<if condition="$config['open_house_user_name']">亲友<else/>家属</if></option>
										<option value="3" <if condition="$village_info['send_user_type'] eq 3">selected="selected"</if> >仅<if condition="$config['open_house_user_name']">亲友<else/>家属</if></option>
									</select>
									<span class="form_tips">自动催缴微信通知发送人，默认仅发给业主</span>
								</div>




							</div>
							<div class="tab-pane" id="txtpwd">

								<div class="form-group">
									<label class="col-sm-1"><label for="touch_alarm_phone">业主一键报警</label></label>
									<input class="col-sm-2" name="touch_alarm_phone" id="touch_alarm_phone" type="text" value="{pigcms{$village_info.touch_alarm_phone}"/>
									<span class="form_tips">业主一键报警时联系社区物业的电话，只能填写一个联系方式。</span>
								</div>

								<div class="form-group" <if condition="$village_about_info.works_order_switch gt 0">style="display:none"</if> >
									<label class="col-sm-1"><label for="handle_type">接任务类型</label></label>
									<label style="padding-left:0px;padding-right:20px;"><input name="handle_type" value="1" type="radio" class="ace" <if condition="$village_info.handle_type eq 1">checked</if>/><span class="lbl" style="z-index: 1">自行抢单</span></label>
									<label style="padding-left:0px;"><input name="handle_type" value="0" type="radio" class="ace" <if condition="$village_info.handle_type eq 0">checked</if>/><span class="lbl" style="z-index: 1">分配指定</span></label>
									<span class="form_tips">自行抢单：业主发布了投诉或报修时，工作人员自行抢接这个任务；分配指定：业主发布了投诉或报修时，由平台直接分发给某个特定的工作人员</span>
								</div>
								<div class="form-group hour" <if condition="$village_info.handle_type eq 0 OR $village_about_info.works_order_switch gt 0 ">style="display:none"</if>>
									<label class="col-sm-1"><label for="hour">超时指定的时间</label></label>
									<input class="col-sm-1" size="20" name="hour" id="hour" min="0" type="number" value="{pigcms{$village_info.hour|default=4}"/>
									<span class="form_tips">小时　(业主发布了投诉或报修后多少小时没有工作人员接任务，由平台指定给特定的工作人员)</span>
                                <span style="line-height: 16px;" class="btn btn-success form_tips bind_id" onclick="bind_worker();">
                                    <if condition="intval($village_about_info['repair_wid']) neq 0">
                                       <span title="在线报修已绑定1个工作人员，投诉建议已绑定1个工作人员">已绑定</span>
                                        <else/>
                                         去绑定
                                    </if>
                                </span>
                                <input type="hidden" name="repair_wid">
                                <input type="hidden" name="advise_wid">
								</div>	
                                <!--新版隐藏配置首页自定义导航，不做删除，以防查证-->
								<div class="form-group" style="display: none;">
									<label class="col-sm-1"><label for="has_index_nav">首页自定义导航</label></label>
									
										<label style="padding-left:0px;padding-right:20px;"><input name="has_index_nav" value="1" type="radio" class="ace" <if condition="$village_info.has_index_nav eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
										<label style="padding-left:0px;"><input name="has_index_nav" value="0" type="radio" class="ace" <if condition="$village_info.has_index_nav eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
								</div>
							<!--新版隐藏配置首页社区商家，不做删除，以防查证-->
                                <div class="form-group" style="display: none;">
									<label class="col-sm-1"><label for="has_index_store">首页{pigcms{$config['house_name']}商家</label></label>
									
										<label style="padding-left:0px;padding-right:20px;"><input name="has_index_store" value="1" type="radio" class="ace" <if condition="$village_info.has_index_store eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
										<label style="padding-left:0px;"><input name="has_index_store" value="0" type="radio" class="ace" <if condition="$village_info.has_index_store eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
								</div>	

								<div class="form-group">
									<label class="col-sm-1"><label for="tourist">是否允许游客访问</label></label>
									<label style="padding-left:0px;padding-right:20px;"><input name="tourist" value="1" type="radio" class="ace" <if condition="$village_info.tourist eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
									<label style="padding-left:0px;"><input name="tourist" value="0" type="radio" class="ace" <if condition="$village_info.tourist eq 0">checked</if>/><span class="lbl" style="z-index: 1">禁止</span></label>
									<span class="form_tips">不是业主，是否能进入平台中{pigcms{$config['house_name']}的页面。</span>
								</div>

							  <!--新版隐藏配置是否开启人脸识别门禁,前往其他地方处理，不做删除，以防查证-->
                                <div class="form-group" style="display: none;">
                                    <label class="col-sm-1"><label for="open_face_door">是否开启人脸识别门禁</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="open_face_door" value="1" type="radio" class="ace" <if condition="$village_info.open_face_door eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                    <label style="padding-left:0px;"><input name="open_face_door" value="0" type="radio" class="ace" <if condition="$village_info.open_face_door eq 0 || !$village_info.open_face_door ">checked</if>/><span class="lbl" style="z-index: 1">禁止</span></label>
                                    <span class="form_tips">开启后可以使用对应人脸识别门禁功能。</span>
                                </div>
							<!--新版隐藏配置开启快递代收/代发,前往其他地方处理，不做删除，以防查证-->
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="has_express_service">快递代收/代发</label></label>

                                    <label style="padding-left:0px;padding-right:20px;"><input name="has_express_service" value="1" type="radio" class="ace" <if condition="$village_info.has_express_service eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                    <label style="padding-left:0px;"><input name="has_express_service" value="0" type="radio" class="ace" <if condition="$village_info.has_express_service eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
                                    <span class="form_tips">开通后，功能库中的<font class="green">&nbsp;快递代收/代发&nbsp;</font>功能可正常使用</span>

                                </div>

								<div class="form-group">
									<label class="col-sm-1"><label for="has_express_send">快递代发</label></label>
										<label style="padding-left:0px;padding-right:20px;">
											<input name="has_express_send" value="1" type="radio" class="ace" <if condition="$village_info.has_express_send eq 1">checked</if>/>
											<span class="lbl" style="z-index: 1">开启</span>
										</label>
										<label style="padding-left:0px;">
											<input name="has_express_send" value="0" type="radio" class="ace" <if condition="$village_info.has_express_send eq 0">checked</if>/>
											<span class="lbl" style="z-index: 1">关闭</span>
										</label>
										<span class="form_tips">开通后，功能库中的<font class="green">&nbsp;快递代发&nbsp;</font>功能可正常使用</span>
								</div>

							<!--新版隐藏配置代发价格,前往其他地方处理，不做删除，以防查证-->
								<div class="form-group express_send_price" <if condition="$village_info.has_express_send eq 0 || 1">style="display:none"</if>>
									<label class="col-sm-1"><label for="express_send_price">代发价格</label></label>
									<input class="col-sm-1" size="10" name="express_send_price" id="express_send_price" type="text" placeholder="请设置代发价格" value="{pigcms{$village_info.express_send_price}" />
									<span class="form_tips">请设置代发价格</span>
								</div>


								<script>
									$('input[name=has_express_send]').click(function(){
										if ($(this).val() == 1) {
											$('.express_send_price').show();
										} else {
											$('.express_send_price').hide();
										}
									});
								</script>

						<!--新版隐藏配置访客登记,前往其他地方处理，不做删除，以防查证-->
								<div class="form-group" style="display: none;">
									<label class="col-sm-1"><label for="has_visitor">访客登记</label></label>
									
										<label style="padding-left:0px;padding-right:20px;"><input name="has_visitor" value="1" type="radio" class="ace" <if condition="$village_info.has_visitor eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
										<label style="padding-left:0px;"><input name="has_visitor" value="0" type="radio" class="ace" <if condition="$village_info.has_visitor eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
										<span class="form_tips">开通后，功能库中的<font class="green">&nbsp;访客登记&nbsp;</font>功能可正常使用</span>
									
								</div>
						<!--新版去除首页幻灯片,不做删除，以防查证-->
                                 <div class="form-group" style="display: none;">
									<label class="col-sm-1"><label for="has_slide">首页幻灯片</label></label>
									
										<label style="padding-left:0px;padding-right:20px;"><input name="has_slide" value="1" type="radio" class="ace" <if condition="$village_info.has_slide eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
										<label style="padding-left:0px;"><input name="has_slide" value="0" type="radio" class="ace" <if condition="$village_info.has_slide eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
										<span class="form_tips">开通后，功能库中的<font class="green">&nbsp;首页幻灯片&nbsp;</font>功能可正常使用</span>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="has_activity">社区活动</label></label>

                                    <label style="padding-left:0px;padding-right:20px;"><input name="has_activity" value="1" type="radio" class="ace" <if condition="$village_info.has_activity eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                    <label style="padding-left:0px;"><input name="has_activity" value="0" type="radio" class="ace" <if condition="$village_info.has_activity eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
                                    <span class="form_tips">开通后，功能库中的<font class="green">&nbsp;社区活动&nbsp;</font>功能可正常使用</span>
                                </div>

                                <if condition="$is_aihorse_customized">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="balance_automat_pay">水电然抄表账单自主扣除余额</label></label>

                                    <label style="padding-left:0px;padding-right:20px;"><input name="balance_automat_pay" value="1" type="radio" class="ace" <if condition="$village_info.balance_automat_pay eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                    <label style="padding-left:0px;"><input name="balance_automat_pay" value="0" type="radio" class="ace" <if condition="$village_info.balance_automat_pay eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
                                    <span class="form_tips">开通后，在原来水电然抄表生成的账单需要业主授权才能自动扣款得基础上，无需业主授权而自动扣款业主余额</span>
                                </div>
                                <div class="form-group">
                                        <label class="col-sm-1"><label for="special_set_price">水费订单累计统计最小金额：</label></label>
                                        <input class="col-sm-1" name="special_set_price" id="special_set_price" min ="0" type="number" step="0.01" placeholder="支持2位有效小数" pattern="^\d+(\.\d)?$"  value="{pigcms{$village_about_info.special_set_price}"/>
                                        <span class="form_tips">物业费汇总，收费率（权责）统计项中的水费统计，订单累计统计最小金额，即小于这个设置金额不统计</span>
                                    </div>
                                </if>
                                <!--
						<div class="form-group">
							<label class="col-sm-1"><label for="has_activity">支持访客车辆进入小区</label></label>

							<label style="padding-left:0px;padding-right:20px;"><input name="is_visitor_car" value="1" type="radio" class="ace" <if condition="$village_info.is_visitor_car eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
							<label style="padding-left:0px;"><input name="is_visitor_car" value="0" type="radio" class="ace" <if condition="$village_info.is_visitor_car eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
						</div>  -->
						<!--新版隐藏配置是否开启社区客服功能,前往其他地方处理，不做删除，以防查证-->
                                <div class="form-group" style="display: none;">
                                    <label class="col-sm-1"><label for="has_activity">是否开启社区客服功能</label></label>

                                    <label style="padding-left:0px;padding-right:20px;"><input name="has_kefu" value="1" type="radio" class="ace" <if condition="$village_info.has_kefu eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                    <label style="padding-left:0px;"><input name="has_kefu" value="0" type="radio" class="ace" <if condition="$village_info.has_kefu eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
                                    <span class="form_tips">开启后，用户可联系人工客服进行咨询</span>
                                </div>

								<div class="form-group">
									<label class="col-sm-1"><label for="has_activity">公众号关注设置</label></label>

									<label style="padding-left:0px;padding-right:20px;"><input name="is_set_public_num" value="1" type="radio" class="ace" <if condition="$village_info.is_set_public_num eq 1">checked</if>/><span class="lbl" style="z-index: 1">强制关注</span></label>
									<label style="padding-left:0px;padding-right:20px;"><input name="is_set_public_num" value="2" type="radio" class="ace" <if condition="$village_info.is_set_public_num eq 2">checked</if>/><span class="lbl" style="z-index: 1">可选关注</span></label>
									<label style="padding-left:0px;padding-right:20px;"><input name="is_set_public_num" value="3" type="radio" class="ace" <if condition="$village_info.is_set_public_num eq 3">checked</if>/><span class="lbl" style="z-index: 1">无需关注</span></label>
									<span class="form_tips">为业主可及时收到重要通知，提高新闻群发的送达率，可设置业主申请入住时是否需要关注公众号</span>
								</div>
                                <!--2020/11/12 身份证号配置项 start-->
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="has_activity">身份证号填写设置</label></label>

                                    <label style="padding-left:0px;padding-right:20px;"><input name="set_id_code" value="1" type="radio" class="ace" <if condition="$village_info.set_id_code eq 1">checked</if>/><span class="lbl" style="z-index: 1">强制填写</span></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="set_id_code" value="2" type="radio" class="ace" <if condition="$village_info.set_id_code eq 2">checked</if>/><span class="lbl" style="z-index: 1">可选填写</span></label>
                                    <span class="form_tips">目前在人脸门禁部分型号及对接公安政务系统时需要使用到身份证号码，未来也会有更多的场景需要使用。若身份证号码未录入会导致这些功能无法正常使用。届时需要时需要自行进行二次收集，加大困难。请谨慎开启可选。</span>
                                </div>
                                <!--2020/11/12 身份证号配置项 end-->
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="has_activity">家属手机号填写设置</label></label>
                        
                                    <label style="padding-left:0px;padding-right:20px;"><input name="set_phone" value="1" type="radio" class="ace" <if condition="$village_info.set_phone eq 1">checked</if>/><span class="lbl" style="z-index: 1">强制填写</span></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="set_phone" value="2" type="radio" class="ace" <if condition="$village_info.set_phone eq 2">checked</if>/><span class="lbl" style="z-index: 1">可选填写</span></label>
                                    <span class="form_tips">选择【强制填写】时只有和业主是父母、子女关系的强制填写手机号，其他关系可以不填手机号</span>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-1"><label for="has_activity">收费预存配置项</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="is_new_storage" value="1" type="radio" class="ace" <if condition="$village_about_info.is_new_storage eq 1">checked</if>/><span class="lbl" style="z-index: 1">预存</span></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="is_new_storage" value="0" type="radio" class="ace" <if condition="$village_about_info.is_new_storage eq 0">checked</if>/><span class="lbl" style="z-index: 1">预缴</span></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="is_new_storage" value="2" type="radio" class="ace" <if condition="$village_about_info.is_new_storage eq 2">checked</if>/><span class="lbl" style="z-index: 1">预存、预缴</span></label>
                                    <span class="form_tips">选择预存时，用户端展示预存入口；选择预缴时，用户端展示预缴入口；选择预存、预缴时，用户端展示预存、预缴入口</span>
                                </div>
                                <if condition="false && !empty($haveVcon)">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="has_activity">视频监控设置</label></label>

                                    <label style="padding-left:0px;padding-right:20px;"><input name="is_limit_date" value="1" type="radio" class="ace" <if condition="$village_about_info.is_limit_date eq 1">checked</if>/><span class="lbl" style="z-index: 1">限制时间</span></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="is_limit_date" value="0" type="radio" class="ace" <if condition="$village_about_info.is_limit_date eq 0">checked</if>/><span class="lbl" style="z-index: 1">不限制时间</span></label>
                                    <span class="form_tips">限制时间即表示用户在查看<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>视频监控时限制日期、时间，不限制时间即表示用户在查看<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>视频监控时限制日期、时间</span>
                                </div>
								</if>
								<if condition="$yingshan">
									<div class="form-group">
										<label class="col-sm-1">可视化页面类型</label>

										<label style="padding-left:0px;padding-right:20px;"><input name="visualization_page_type" value="1" type="radio" class="ace" <if condition="$village_about_info.visualization_page_type eq 1">checked</if>/><span class="lbl" style="z-index: 1">新版可视化页面</span></label>
										<label style="padding-left:0px;padding-right:20px;"><input name="visualization_page_type" value="0" type="radio" class="ace" <if condition="$village_about_info.visualization_page_type eq 0">checked</if>/><span class="lbl" style="z-index: 1">老版可视化页面</span></label>
										<span class="form_tips">开通后，请重新配置可视化页面</span>
									</div>
								</if>

								<div class="form-group">
									<label class="col-sm-1">允许已缴账单打印多次</label>
									<label style="padding-left:0px;padding-right:20px;"><input name="print_number_times" value="1" type="radio" class="ace" <if condition="$village_about_info.print_number_times eq 1">checked</if>/><span class="lbl" style="z-index: 1">允许</span></label>
									<label style="padding-left:0px;padding-right:20px;"><input name="print_number_times" value="0" type="radio" class="ace" <if condition="$village_about_info.print_number_times eq 0">checked</if>/><span class="lbl" style="z-index: 1">不允许</span></label>
									<span class="form_tips">允许则已缴账单支持多种打印方式，打印编号为多个；不允许则已缴账单打印后，不支持其他打印方式，列表上打印按钮不可操作，打印编号是唯一值，且不会变。</span>
								</div>

								<div class="form-group">
									<label class="col-sm-1">待缴、已缴账单打印合并</label>
									<label style="padding-left:0px;padding-right:20px;"><input name="print_orders_merge_project" value="1" type="radio" class="ace" <if condition="$village_about_info.print_orders_merge_project eq 1">checked</if>/><span class="lbl" style="z-index: 1"> 合并</span></label>
									<label style="padding-left:0px;padding-right:20px;"><input name="print_orders_merge_project" value="0" type="radio" class="ace" <if condition="$village_about_info.print_orders_merge_project eq 0">checked</if>/><span class="lbl" style="z-index: 1"> 不合并</span></label>
									<span class="form_tips">选择合并：选择多个订单打印时将按照收费项目合并相关订单信息成一条信息打印，不合并：按照选择的订单逐条打印信息</span>
								</div>

                            <!--挪到 小区列表 编辑中
                                <div class="form-group">
                                    <label class="col-sm-1">小区业务短信使用类型</label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="sms_type" value="1" type="radio" class="ace" <if condition="$village_about_info.sms_type eq 1">checked</if>/><span class="lbl" style="z-index: 1">小区短信</span></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="sms_type" value="2" type="radio" class="ace" <if condition="$village_about_info.sms_type eq 2">checked</if>/><span class="lbl" style="z-index: 1">平台短信</span></label>
                                    <span class="form_tips">默认为小区选项时，发送催缴短信通知时扣除小区的短信数，小区没有短信数量，需要在小区进行提示，并发送失败。选项为平台时，扣除平台的短信数</span>
                                </div>
				                -->
                                <div class="form-group">
                                    <label class="col-sm-1">催缴通知方式</label>
									<label style="padding-left:0px;padding-right:20px;"><input name="urge_notice_type" value="1" type="radio" class="ace" <if condition="$village_about_info.urge_notice_type eq 1">checked</if>/> <span class="lbl" style="z-index: 1"> &nbsp;短信通知</span></label>
									<label style="padding-left:0px;padding-right:20px;"><input name="urge_notice_type" value="2" type="radio" class="ace" <if condition="$village_about_info.urge_notice_type eq 2">checked</if>/> <span class="lbl" style="z-index: 1"> &nbsp;微信模板通知</span></label>
									<label style="padding-left:0px;padding-right:20px;"><input name="urge_notice_type" value="3" type="radio" class="ace" <if condition="!$village_about_info.urge_notice_type OR $village_about_info.urge_notice_type eq 3">checked</if>/><span class="lbl" style="z-index: 1"> &nbsp;短信和微信模板通知</span></label>
									<span class="form_tips"></span>
							    </div>
				

        <!-- todo 寻求帮助-->
        <div class="form-group">
            <label class="col-sm-1">寻求帮助</label>
            <div>
                <label style="padding-left:0px;padding-right:20px;"><input name="help_info[is_close]" value="0" type="radio" class="ace help_info_close" <if condition="$help_info['0']['is_close'] eq 0">checked</if>/><span class="lbl" style="z-index: 1"> 启用 </span></label>
                <label style="padding-left:0px;padding-right:20px;"><input name="help_info[is_close]" value="1" type="radio" class="ace help_info_close" <if condition="$help_info['0']['is_close'] eq 1">checked</if>/><span class="lbl" style="z-index: 1"> 禁用 </span></label>
                <span class="form_tips"></span>
                <div class="help_body" <if condition="$help_info['0']['is_close'] eq 1"> style="display:none;" </if> />

                    <volist name="help_info" id="vo" key="k">
                        <div class="help_box">
                            <select class="help_asse help_type" name="help_info[type][{pigcms{$k-1}]" data-id="{pigcms{$k-1}" <if condition="$vo['type'] eq '3'">style="width:6.91%;" </if>>
                                <option value="1" <if condition="$vo['type'] eq '1'">selected</if> >白班</option>
                                <option value="2" <if condition="$vo['type'] eq '2'">selected</if> >晚班</option>
                                <option value="3" <if condition="$vo['type'] eq '3'">selected</if> >自定义</option>
                            </select>
                            <div class="help_custom">
                                <if condition="isset($vo['custom']) && $vo['type'] eq '3'">
                                    <input class="col-sm-1 help_asse" maxlength="5" size="20" name="help_info[custom][{pigcms{$k-1}]" value="{pigcms{$vo.custom}" type="text" placeholder="请输入自定义" style="width: 8.98%;"/>
                                </if>

                            </div>
                            <input class="col-sm-2 help_asse" maxlength="20" name="help_info[phone][{pigcms{$k-1}]" value="{pigcms{$vo.phone}" type="number" placeholder="请输入联系方式"/>
                            <input class="input-text help_asse help_time" type="text" name="help_info[start][{pigcms{$k-1}]"  value="{pigcms{$vo.start}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})" placeholder="开始时间"/>
                            &nbsp;—&nbsp;
                            <input class="input-text help_asse help_time" type="text" name="help_info[end][{pigcms{$k-1}]" value="{pigcms{$vo.end}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})" placeholder="结束时间"/>
                            <span>业主寻求帮助时联系物业的电话，只能填写一个联系方式。</span>
                        </div>
                    </volist>

                </div>
            </div>
        </div>

        <!-- todo 新版工单及时率-->
        <div class="form-group"  style="display:none;">
            <label class="col-sm-1">新版工单及时率</label>
            <label style="padding-left:0px;padding-right:20px;"><input name="is_timely" value="1" type="radio" class="ace" <if condition="$village_about_info.is_timely eq 1">checked</if>/><span class="lbl" style="z-index: 1">启用</span></label>
            <label style="padding-left:0px;padding-right:20px;"><input name="is_timely" value="0" type="radio" class="ace" <if condition="$village_about_info.is_timely eq 0">checked</if>/><span class="lbl" style="z-index: 1">禁用</span></label>
            <span class="form_tips">及时率是计算工作人员接单到结单的时间，工单类目设置时间为30分钟，在该时间内结单算正常，超过时间算问题工单,</span>
        </div>

        <!-- todo 新版工单自动抢单-->
        <div class="form-group"  style="display:none;">

            <label class="col-sm-1"><label for="is_grab_order">新版工单自动抢单</label></label>
            <label style="padding-left:0px;padding-right:20px;">
                <input name="is_grab_order" value="1" type="radio" class="ace" <if condition="$village_about_info.is_grab_order eq 1">checked</if>/>
                <span class="lbl" style="z-index: 1">启用</span>
            </label>
            <label style="padding-left:0px;">
                <input name="is_grab_order" value="0" type="radio" class="ace" <if condition="$village_about_info.is_grab_order eq 0">checked</if>/>
                <span class="lbl" style="z-index: 1">禁用</span>
            </label>
            <span class="form_tips">自动抢单功能，是指对工单类别，没有绑定负责人或不在负责人处理时间的情况下，业主发布工单时工作人员自行抢接这个任务;例:投诉工单或报修工单.</span>
        </div>
        <div class="form-group grab_order_time" style="display:none" >
        <label class="col-sm-1"><label for="hour">超时指定的时间</label></label>
        <input class="col-sm-1" size="20" name="grab_order_time" id="grab_order_time" min="1" type="number" value="{pigcms{$village_about_info.grab_order_time|default=1}"/>
        <span class="form_tips">小时(业主发布工单多少小时没有工作人员接任务，由平台指定给特定的工作人员;例:投诉工单或报修工单)</span>
        <span style="line-height: 16px;" class="btn btn-success form_tips bind_id" onclick="new_repair_bind_worker();">去绑定</span>
        <input type="hidden" name="repair_wid">
        <input type="hidden" name="advise_wid">
        </div>

    <!-- todo 新/老版工单启用配置-->
    <div class="form-group" style="display:none;">
        <label class="col-sm-1">新/老版工单启用配置</label>
        <label style="padding-left:0px;padding-right:20px;" title="默认为启用当前配置，启用后，后台新/老版工单的入口都展示，管理端也展示新/老版工单入口，老版工单后台支持创建工单。"><input name="works_order_switch" value="0" type="radio" class="ace" <if condition="$village_about_info.works_order_switch eq 0">checked</if>/><span class="lbl" style="z-index: 1">新/老版工单</span></label>
        <label style="padding-left:0px;padding-right:20px;" title="启用当前配置时，老版工单的后台/管理端入口不展示。只展示新版工单。"><input name="works_order_switch" value="1" type="radio" class="ace" <if condition="$village_about_info.works_order_switch eq 1">checked</if>/><span class="lbl" style="z-index: 1">新版工单</span></label>
        <label style="padding-left:0px;padding-right:20px;" title="启用当前配置时，展示后台的新/老版工单入口（老版工单不能创建工单，只允许查看已有的工单数据），管理端只展示新版工单入口，老版不展示。"><input name="works_order_switch" value="2" type="radio" class="ace" <if condition="$village_about_info.works_order_switch eq 2">checked</if>/><span class="lbl" style="z-index: 1">新/版工单清晰版</span></label>
        <span class="help-icon" title="【新/老版工单】：默认为启用当前配置，启用后，后台新/老版工单的入口都展示，管理端也展示新/老版工单入口，老版工单后台支持创建工单；【新版工单】：启用当前配置时，老版工单的后台/管理端入口不展示。只展示新版工单；【新/版工单清晰版】：启用当前配置时，展示后台的新/老版工单入口（老版工单不能创建工单，只允许查看已有的工单数据），管理端只展示新版工单入口，老版不展示。">?</span>
    </div>

            <!-- todo 新/老版智慧停车配置-->
            <div class="form-group" <if condition="$is_new_charge neq 1 || $is_new_charge neq '1'">style="display:none"</if>>
                <label class="col-sm-1">新/老版智慧停车配置</label>
                <label style="padding-left:0px;padding-right:20px;" ><input name="park_new_switch" value="1" type="radio" class="ace" <if condition="$village_about_info.park_new_switch eq 1">checked disabled</if>/><span class="lbl" style="z-index: 1">启用新版停车</span></label>
                <label style="padding-left:0px;padding-right:20px;" ><input name="park_new_switch" value="2" type="radio" class="ace" <if condition="$village_about_info.park_new_switch eq 2">checked <else/> disabled </if>/><span class="lbl" style="z-index: 1">启用新/老版停车</span></label>
                <span class="help-icon" title="默认启用新/老版停车，启用新/老版停车时，新/老版的智慧停车模块都显示；启用新版停车时，老版智慧停车入口隐藏（车位管理、车辆管理、“智能硬件-智慧停车“功能） 启用新版停车 后将无法再修改">?</span>
            </div>

            <if condition="$show_sky_cockpit_entrance eq 1">
            <div class="form-group">
                <label class="col-sm-1">是否开启<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>门禁</label>
                <label style="padding-left:0px;padding-right:20px;" ><input name="village_house_door_close" value="0" type="radio" class="ace" <if condition="$village_about_info.village_house_door_close eq 0">checked</if>/><span class="lbl" style="z-index: 1">是</span></label>
                <label style="padding-left:0px;padding-right:20px;" ><input name="village_house_door_close" value="1" type="radio" class="ace" <if condition="$village_about_info.village_house_door_close eq 1">checked</if>/><span class="lbl" style="z-index: 1">否</span></label>
                <span class="help-icon" title="当开启时，社区配置里的【开启<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>门禁】必须也是开启状态才会显示">?</span>
            </div>

                <div class="form-group">
                    <label class="col-sm-1"><label for="room_bind_people_num"><?php echo (D('House_village')->getHouseAlias('room_name')?:'房间') ?>最多绑定住户数量</label></label>
                    <input class="col-sm-2" size="20" name="room_bind_people_num" id="room_bind_people_num" min="0" type="number" value="{pigcms{$village_about_info.room_bind_people_num}" /> &nbsp;&nbsp;&nbsp;<span class="msg_span">总控制从用户端绑定人时，能绑定的人数，空或者0表示不限制</span>
                </div>
                <div class="form-group">
                    <label class="col-sm-1"><label for="room_bind_people_num">是否启用替换业主</label></label>
                    <label style="padding-left:0px;padding-right:20px;" ><input name="house_room_replace_owner" value="1" type="radio" class="ace" <if condition="$village_about_info.house_room_replace_owner eq 1">checked</if>/><span class="lbl" style="z-index: 1">启用</span></label>
                    <label style="padding-left:0px;padding-right:20px;" ><input name="house_room_replace_owner" value="0" type="radio" class="ace" <if condition="$village_about_info.house_room_replace_owner eq 0">checked</if>/><span class="lbl" style="z-index: 1">禁用</span></label>
                    <span class="help-icon" title="如果是禁用状态时，在移动用户端加入房屋时，不显示“替换业主”选项">?</span>
                </div>
            </if>
            <if condition="$is_aihorse_customized">
                <div class="form-group">
                    <label class="col-sm-1"><label for="third_pay_url">通联缴费链接：</label></label>
                    <input class="col-sm-3" name="third_pay_url" id="third_pay_url" type="text" value="{pigcms{$village_about_info.third_pay_url}"/>
                    <span class="form_tips">填写通联缴费链接后，社区生活缴费支付会跳转至通联缴费<span style="color:red;margin-left: 15px;">通联缴费链接 和 建行缴费链接 不允许同时填写！</span></span>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-1"><label for="third_ccb_pay_url">建行缴费链接：</label></label>
                    <input class="col-sm-3" name="third_ccb_pay_url" id="third_ccb_pay_url" type="text" value="{pigcms{$village_about_info.third_ccb_pay_url}"/>
                    <span class="form_tips">填写建行缴费链接后，社区生活缴费支付会跳转至建行缴费<span style="color:red;margin-left: 15px;">建行缴费链接 和 通联缴费链接 不允许同时填写！</span></span>
                </div>

            </if>
</div>
                             
                            <!--div class="tab-pane" id="txtservice">
								<div class="form-group">
									<label class="col-sm-1"><label for="has_custom_pay">便民页面幻灯片</label></label>
									
										<label style="padding-left:0px;padding-right:20px;"><input name="has_service_slide" value="1" type="radio" class="ace" <if condition="$village_info.has_service_slide eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
										<label style="padding-left:0px;"><input name="has_service_slide" value="0" type="radio" class="ace" <if condition="$village_info.has_service_slide eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
										<span class="form_tips">开通后，便民服务中的<font class="green">&nbsp;便民页面幻灯片&nbsp;</font>功能可正常使用</span>
									
								</div>
							</div-->
                            
                            
                            <div class="tab-pane" id="bindweixin">
                            	
                            	<div id="weixin_info" <if condition="empty($village_info['openid'])">style="display:none"</if>>
								<div class="form-group">
									<label class="col-sm-1"><label for="village_name">微信昵称</label></label>
									<input class="col-sm-1" value="{pigcms{$village_info.nickname'}" type="text" id="nickname" style="border:none;background:white!important;" readonly/>
									<a class="btn btn-sm btn-warning" id="cancel">取消绑定</a>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>微信头像</label></label>
									<div id="upload_pic_box"><img width="100" height="100" src="{pigcms{$village_info.avatar}" id="avatar"/></div>
								</div>
								</div>
								<div class="form-group" id="weixin_bind" <if condition="$village_info['openid']">style="display:none"</if>>
									<label class="col-sm-1">扫描二维码</label>
									<div style="padding-top:4px;line-height:24px;"><a href="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=see_tmp_qrcode&qrcode_id={pigcms{$village_info['village_id'] + 3950000000}&img=1" class="see_qrcode">查看二维码</a></div>
								</div>
								
								
								
							</div>
                            
							<if condition="$config.plate_house_open eq 1 && $config.plate_house_way eq 2">
                            <div class="tab-pane" id="plate">	
                            	<div class="form-group">
									<label class="col-sm-1"><label for="plate_open">是否开启</label></label>
									
									<label style="padding-left:0px;padding-right:20px;"><input name="plate_open" value="1" type="radio" class="ace" <if condition="$village_info.plate_open eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
									<label style="padding-left:0px;"><input name="plate_open" value="0" type="radio" class="ace" <if condition="$village_info.plate_open eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span>
									</label>
									<span class="form_tips">开启后，<font class="green">&nbsp;车牌识别&nbsp;</font>功能可正常使用</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="village_name">AppID</label></label>
									<input class="col-sm-5" value="{pigcms{$village_info.plate_appid}" type="text" id="plate_appid" name="plate_appid"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label>APIKey</label></label>
									<input class="col-sm-5" value="{pigcms{$village_info.plate_api_key}" type="text" id="plate_api_key" name="plate_api_key"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="village_name">SecretKey</label></label>
									<input class="col-sm-5" value="{pigcms{$village_info.plate_secret_key}" type="text" id="plate_secret_key" name="plate_secret_key"/>
								</div>
							</div>
							</if>
                            
                            <div class="tab-pane" id="uploadImgInfo">
                                <div class="tips">
                                    <span>说明：默认开启“拍照上传、图片上传”选项，APP、h5、小程序根据开启的选项进行展示</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="face_door_set">人脸设置</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="face_door_set" value="1" type="radio" class="ace" <if condition="$village_about_info.face_door_set eq 1">checked</if>/><span class="lbl" style="z-index: 1">拍照上传、图片上传</span></label>
                                    <label style="padding-left:0px;"><input name="face_door_set" value="2" type="radio" class="ace" <if condition="$village_about_info.face_door_set eq 2">checked</if>/><span class="lbl" style="z-index: 1">拍照上传</span></label>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="work_order_set">工单设置</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="work_order_set" value="1" type="radio" class="ace" <if condition="$village_about_info.work_order_set eq 1">checked</if>/><span class="lbl" style="z-index: 1">拍照上传、图片上传</span></label>
                                    <label style="padding-left:0px;"><input name="work_order_set" value="2" type="radio" class="ace" <if condition="$village_about_info.work_order_set eq 2">checked</if>/><span class="lbl" style="z-index: 1">拍照上传</span></label>
                               </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="wisdom_qrcode_set">巡检设置</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="wisdom_qrcode_set" value="1" type="radio" class="ace" <if condition="$village_about_info.wisdom_qrcode_set eq 1">checked</if>/><span class="lbl" style="z-index: 1">拍照上传、图片上传</span></label>
                                    <label style="padding-left:0px;"><input name="wisdom_qrcode_set" value="2" type="radio" class="ace" <if condition="$village_about_info.wisdom_qrcode_set eq 2">checked</if>/><span class="lbl" style="z-index: 1">拍照上传</span></label>
                                 </div>
                            </div>
                            <div class="tab-pane" id="accessControl">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="owe_property_open_door">允许欠物业费开门</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="owe_property_open_door" value="1" type="radio" class="ace" <if condition="$village_info.owe_property_open_door eq 1">checked</if>/><span class="lbl" style="z-index: 1">允许</span></label>
                                    <label style="padding-left:0px;"><input name="owe_property_open_door" value="0" type="radio" class="ace" <if condition="$village_info.owe_property_open_door eq 0">checked</if>/><span class="lbl" style="z-index: 1">不允许</span></label>
                                    <span class="form_tips">不允许则欠物业费立即不能开门，允许还可以设置延期天数，在延期天数内还可以开门，过期则不能</span>
                                </div>


                                <div class="form-group door_day" <if condition="$village_info.owe_property_open_door eq 0">style="display:none"</if>>
                                    <label class="col-sm-1"><label for="owe_property_open_door_day">欠物业费延期开门天数</label></label>
                                    <input class="col-sm-1" size="10" name="owe_property_open_door_day" id="owe_property_open_door_day" type="text" value="{pigcms{$village_info.owe_property_open_door_day}" />
                                    <span class="form_tips">天 (1 表示欠物业费一天内还可以开门，0 代表不限制)</span>
                                </div>


                                <div class="form-group">
                                    <label class="col-sm-1"><label for="has_index_nav">是否开启智能访客临时开门功能</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="temporary_visitor_switch" value="1" type="radio" class="ace" <if condition="$village_info.temporary_visitor_switch eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                    <label style="padding-left:0px;"><input name="temporary_visitor_switch" value="0" type="radio" class="ace" <if condition="$village_info.temporary_visitor_switch eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
                                    <span class="form_tips">智能访客开门功能指业主可以将自己所在{pigcms{$config['house_name']}的开门权限分享给他的访客好友等，为实现智慧<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>智能门禁增添色彩。（当前平台该功能处于<if condition="$config['house_village_temporary_visitor'] eq 1">开启<else/>关闭</if>状态）</span>
                                </div>


                                <div class="form-group">
                                    <label class="col-sm-1"><label for="has_index_nav">智能访客开门最长时效</label></label>


                                    <select name="temporary_visitor_time" id="temporary_visitor_time" style="width: 250px">
                                        <option value="1" <if condition="$village_info['temporary_visitor_time'] eq 1">selected="selected"</if> >1小时</option>
                                        <option value="2" <if condition="$village_info['temporary_visitor_time'] eq 2">selected="selected"</if> >2小时</option>
                                        <option value="3" <if condition="$village_info['temporary_visitor_time'] eq 3">selected="selected"</if> >3小时</option>
                                        <option value="5" <if condition="$village_info['temporary_visitor_time'] eq 5">selected="selected"</if> >5小时</option>
                                        <option value="8" <if condition="$village_info['temporary_visitor_time'] eq 8">selected="selected"</if> >8小时</option>
                                        <option value="10" <if condition="$village_info['temporary_visitor_time'] eq 10">selected="selected"</if> >10小时</option>
                                        <option value="12" <if condition="$village_info['temporary_visitor_time'] eq 12">selected="selected"</if> >12小时</option>
                                        <option value="24" <if condition="$village_info['temporary_visitor_time'] eq 24">selected="selected"</if> >一天</option>
                                        <option value="48" <if condition="$village_info['temporary_visitor_time'] eq 48">selected="selected"</if> >两天</option>
                                        <option value="72" <if condition="$village_info['temporary_visitor_time'] eq 72">selected="selected"</if> >三天</option>
                                        <option value="96" <if condition="$village_info['temporary_visitor_time'] eq 96">selected="selected"</if> >四天</option>
                                        <option value="120" <if condition="$village_info['temporary_visitor_time'] eq 120">selected="selected"</if> >五天</option>
                                        <option value="144" <if condition="$village_info['temporary_visitor_time'] eq 144">selected="selected"</if> >六天</option>
                                        <option value="168" <if condition="$village_info['temporary_visitor_time'] eq 168">selected="selected"</if> >七天</option>
                                    </select>

                                    <span class="form_tips">社区访客临时开门有效期最大限制为7天，如您有需要超过7天以上的权限请协助业主将他的访客添加为
                                    <if condition="$config['open_house_user_name']">亲友<else/>家属</if>
                                	即可实现长期开门。</span>
                                </div>
                                <if condition="$village_info['write_iccard'] eq 1">
                                    <div class="form-group" >
                                        <label class="col-sm-1"><label>IC门禁卡扇区</label></label>
                                        <input style="width: 50px; text-align: center;" name="door_sector" type="text" value="{pigcms{$village_info['door_sector']}" />&nbsp;

                                        <span class="form_tips">(管理卡门禁扇区、仅能填写数字)</span>
                                    </div>

                                    <div class="form-group" >
                                        <label class="col-sm-1"><label>IC门禁密码</label></label>
                                        <input style="width: 50px; text-align: center;" onkeyup="value=value.replace(/[^\\d]/g,''),door_pwd_keyup(this.value,this)" name="door_pwd[]" type="text" value="{pigcms{$village_info['door_pwd'][0]}" />&nbsp;
                                        <input style="width: 50px; text-align: center;" onkeyup="value=value.replace(/[^\\d]/g,''),door_pwd_keyup(this.value,this)" name="door_pwd[]" type="text" value="{pigcms{$village_info['door_pwd'][1]}" />&nbsp;
                                        <input style="width: 50px; text-align: center;" onkeyup="value=value.replace(/[^\\d]/g,''),door_pwd_keyup(this.value,this)" name="door_pwd[]" type="text" value="{pigcms{$village_info['door_pwd'][2]}" />&nbsp;
                                        <input style="width: 50px; text-align: center;" onkeyup="value=value.replace(/[^\\d]/g,''),door_pwd_keyup(this.value,this)" name="door_pwd[]" type="text" value="{pigcms{$village_info['door_pwd'][3]}" />&nbsp;
                                        <input style="width: 50px; text-align: center;" onkeyup="value=value.replace(/[^\\d]/g,''),door_pwd_keyup(this.value,this)" name="door_pwd[]" type="text" value="{pigcms{$village_info['door_pwd'][4]}" />&nbsp;
                                        <input style="width: 50px; text-align: center;" onkeyup="value=value.replace(/[^\\d]/g,''),door_pwd_keyup(this.value,this)" name="door_pwd[]" type="text" value="{pigcms{$village_info['door_pwd'][5]}" />
                                        <span class="form_tips">(管理卡门禁密码)</span>
                                    </div>
                                    <script>
                                        function door_pwd_keyup(val,obj){
                                            if(val > 255){
                                                $(obj).val(255);
                                            }
                                        }
                                    </script>
                                </if>

                                <if condition="$getSwitchQrCode">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="vistor_open_number_limit">访客通行人脸门禁开门次数</label></label>
                                        <select name="vistor_open_number_limit" id="limitOpenSelect" style="width: 150px">
                                            <volist name="limitOpen['select']" id="limitOpenItem" key="k">
                                                <option value="{pigcms{$limitOpenItem['value']}" <if condition="$limitOpen['vistor_open_number_limit'] eq $limitOpenItem['value']">selected="selected"</if> >{pigcms{$limitOpenItem['title']}</option>
                                            </volist>
                                        </select>
                                        <span class="form_tips">{pigcms{$tipQRCode}</span>
                                    </div>
                                </if>

                                <if condition="$haveXiHanFaceDoor">
                                    <div class="form-group">
                                        <label class="col-sm-1"><label for="card_expire_time_type">住户卡有效配置</label></label>
                                        <select name="card_expire_time_type" id="card_expire_time_type" style="width: 200px">
                                         <option value="0" <if condition="$village_about_info['card_expire_time_type'] neq 1">selected="selected"</if> >物业收费业务</option>
                                         <option value="1" <if condition="$village_about_info['card_expire_time_type'] eq 1">selected="selected"</if> >添加门禁卡时自定义</option>
                                        </select>
                                        <span class="form_tips">1、按小区物业收费业务，有效期就截止到业主缴的物业费缴的计费结束时间，
                                            2、添加门禁卡时自定义 仅支持西涵门禁，不跟业务关联</span>
                                    </div>
                                </if>
                            </div>

                            <div class="tab-pane" id="door_docking">
                            	
                            	<div id="face_door_info" <if condition="empty($village_face_door)">style="display:none"</if>>
									<!-- <div class="form-group">
										<label class="col-sm-2"><label for="village_name">绑定人脸门禁</label></label>
										<a class="btn btn-info btn-warning" id="cancel">已绑定</a>
									</div> -->
									<div class="form-group">
										<label class="col-sm-2"><label for="village_name">同步社区</label></label>
										<a class="btn btn-info sync_village" id="sync_community" data-type="community">同步</a>
									</div>
									<div class="form-group">
										<label class="col-sm-2"><label for="village_name">同步<?php echo ((D('House_village')->getHouseAlias('floor_name')?:'单元').(D('House_village')->getHouseAlias('single_name')?:'楼栋')) ?></label></label>
										<a class="btn btn-info sync_village" id="sync_floor" data-type="floor">同步</a>
									</div>
									<div class="form-group">
										<label class="col-sm-2"><label for="village_name">同步<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间') ?></label></label>
										<a class="btn btn-info sync_village" id="sync_house" data-type="house">同步</a>
									</div>
									<div class="form-group">
										<label class="col-sm-2"><label for="village_name">同步业主</label></label>
										<a class="btn btn-info sync_village" id="sync_house" data-type="user">同步</a>
									</div>
									<div class="form-group">
										<label class="col-sm-2"><label for="village_name">门禁管理</label></label>
										<a class="btn btn-info"  onclick="door_list()">门禁列表</a>
									</div>
								</div>
								<!-- 
								<div id="door_code_bind" <if condition="$village_face_door['code']">style="display:none"</if>>
									<div class="form-group">
										<label class="col-sm-2"><label for="village_name">绑定人脸门禁</label></label>
										<a class="btn authorize">授权</a>
									</div>
								</div> -->
							</div>
                            
							<div class="tab-pane" id="ncov_2019">
								<div class="form-group">
                                    <label class="col-sm-1"><label for="ncov_2019_health_visit">允许匿名打卡</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input class="ncov_2019_health_visit" name="ncov_2019_health_visit" value="1" type="radio" class="ace" <if condition="$village_info.ncov_2019_health_visit eq 1">checked</if>/><span class="lbl" style="z-index: 1">允许</span></label>
                                    <label style="padding-left:0px;"><input class="ncov_2019_health_visit" name="ncov_2019_health_visit" value="0" type="radio" class="ace" <if condition="$village_info.ncov_2019_health_visit eq 0">checked</if>/><span class="lbl" style="z-index: 1">不允许</span></label>
                                    <span class="form_tips">没有入住为业主，是否允许使用健康打卡功能。匿名时房屋、姓名等信息由用户自行填写。</span>
                                </div>
								
								<div class="form-group">
                                    <label class="col-sm-1"><label for="ncov_2019_visit">允许匿名登记</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input class="ncov_2019_visit" name="ncov_2019_visit" value="1" type="radio" class="ace" <if condition="$village_info.ncov_2019_visit eq 1">checked</if>/><span class="lbl" style="z-index: 1">允许</span></label>
                                    <label style="padding-left:0px;"><input class="ncov_2019_visit" name="ncov_2019_visit" value="0" type="radio" class="ace" <if condition="$village_info.ncov_2019_visit eq 0">checked</if>/><span class="lbl" style="z-index: 1">不允许</span></label>
                                    <span class="form_tips">没有入住为业主，是否允许进行出入登记。匿名时房屋、姓名等信息由用户自行填写。</span>
                                </div>
								
								<div class="form-group">
                                    <label class="col-sm-1"><label for="ncov_2019_out_type">外出<?php echo (D('House_village')->getHouseAlias('house_name')?:'小区') ?>限制</label></label>


                                    <select name="ncov_2019_out_type" id="ncov_2019_out_type" style="width: 250px">
                                        <option value="0" <if condition="$village_info['ncov_2019_out_type'] eq 0">selected="selected"</if> >不限制</option>
                                        <option value="1" <if condition="$village_info['ncov_2019_out_type'] eq 1">selected="selected"</if> >按每周限制外出次数</option>
                                        <option value="2" <if condition="$village_info['ncov_2019_out_type'] eq 2">selected="selected"</if> >按每几天限制一次外出</option>
                                    </select>

                                    <span class="form_tips"></span>
                                </div>
								
                                <div class="form-group" id="ncov_2019_week_group" <if condition="$village_info['ncov_2019_out_type'] neq 1">style="display:none;"</if>>
									<label class="col-sm-1"><label>每周限制次数</label></label>
									<input class="col-sm-1" value="{pigcms{$village_info.ncov_2019_week}" type="text" id="ncov_2019_week" name="ncov_2019_week"/>
								</div>
								
								<div class="form-group" id="ncov_2019_day_group" <if condition="$village_info['ncov_2019_out_type'] neq 2">style="display:none;"</if>>
									<label class="col-sm-1"><label>每几天限制一次外出</label></label>
									<input class="col-sm-1" value="{pigcms{$village_info.ncov_2019_day}" type="text" id="ncov_2019_day" name="ncov_2019_day"/>
								</div>
								
								<div class="form-group" id="ncov_2019_limit_group" <if condition="$village_info['ncov_2019_out_type'] eq 0">style="display:none;"</if>>
                                    <label class="col-sm-1"><label for="ncov_2019_limit">外出限制类型</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="ncov_2019_limit" value="1" type="radio" class="ace" <if condition="$village_info.ncov_2019_limit eq 1">checked</if>/><span class="lbl" style="z-index: 1">每户</span></label>
                                    <label style="padding-left:0px;"><input name="ncov_2019_limit" value="0" type="radio" class="ace" <if condition="$village_info.ncov_2019_limit eq 0">checked</if>/><span class="lbl" style="z-index: 1">每人</span></label>
                                    <span class="form_tips">匿名登记的系统不知属实所在的<?php echo (D('House_village')->getHouseAlias('room_name')?:'房间') ?>，所以每户仅在关闭“允许匿名登记”的情况下生效。</span>
                                </div>
								
								<div class="form-group" id="ncov_2019_time_group" <if condition="$village_info['ncov_2019_out_type'] eq 0">style="display:none;"</if>>
									<label class="col-sm-1"><label>外出时间段限制</label></label>
									<input class="col-sm-1" value="{pigcms{$village_info.ncov_2019_time}" type="text" id="ncov_2019_time" name="ncov_2019_time"/>
									<span class="form_tips">例：8-20，早8点整到晚8点整，暂仅支持整点。留空不限制。</span>
								</div>
								
							</div>

							<div class="tab-pane" id="orderCheckControl" data-xtype="orderCheckControl">
							   <div class="form-group order_check_open">
                                    <label class="col-sm-3" style="width: 21%;"><label for="refund_order_check_open">是否开启订单退款（作废）审核流程：</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="refund_order_check_open" value="0" type="radio"  <if condition="$refund_order_check_open eq 0"> checked </if> class="ace " <if condition="$refund_order_check_open eq 1 AND !empty($applyhas)"> disabled </if>  /><span class="lbl" style="z-index: 1"> 关闭 </span></label>
                                    <label style="padding-left:0px;"><input name="refund_order_check_open" value="1" type="radio"  <if condition="$refund_order_check_open eq 1"> checked </if> class="ace" <if condition="$refund_order_check_open eq 1 AND !empty($applyhas)"> disabled </if> /><span class="lbl" style="z-index: 1"> 开启 </span></label>
                                    <span class="form_tips" style="color:red;"><if condition="!empty($applyhas)"> 有在审核的流程未结束（{pigcms{$applyhas.msg}），不可改动相关设置 </if></span>
                                </div>

                                <div class="form-group" style="margin-left: 10px;">
                                    <p>提示说明</p>
                                    <div>
                                     <p>1、设置应收账单的作废、已缴账单的退款的审核管理人员</p>
                                    </div>

                                    <div>
                                     <p>2、退款审核流程从上往下进行设置，最多可以设置5人进行审核。审核流程可根据当前角色减少申请流程（只有设置的审批人发起作废或退款会减少审核流程）</p>
                                     <div class="tips" style="margin-left: 20px;"> 
                                        <p>例：收银台客服发起退款申请流程→客服主管审核→物业经理审核→物业总经理审核→财务主管审核</p>
                                        <p>例：收银台物业经理发起退款申请流程→物业总经理审核→财务主管审核</p>
                                     </div>
                                    </div>
                                </div>
							    <div class="form-group" style="margin-left: 30px;">
									 <label style="width: 25%;"> <a class="ant-btn ant-btn-primary add_check_control" href="javascript:;" onclick="addCheckControl()" <if condition="$order_check_level_num egt 4"> style="display:none;" </if> > 新增审批人</a></label>
                                     <div class="check_control_p" style="margin: 35px 0px 35px 50px;">
                                        <ul>
                                            <li class="check_people p_0">
                                                <img src="/static/images/cancelordercheckauth.png" >
                                                <span><strong style="color:red;">*</strong>审批人</span>
                                                <select name="check_level[]" <if condition="!empty($applyhas)"> disabled </if> >
                                                    <option value="0" >请选择</option>
                                                    <volist name="worker_list" id="vo">
                                                        <option value="{pigcms{$vo.wid}" <if condition="$check_level0_wid gt 0 AND $check_level0_wid eq $vo['wid']">selected  </if> > {pigcms{$vo.department_str}{pigcms{$vo.name}</option>
                                                    </volist>
                                                </select>
											    <input type="hidden" class="check_level_v" value="0">
                                            </li>
											
											<if condition="!empty($order_check_level)">
												<volist name="order_check_level" id="vv">
													<li class="xline"></li>
                                                    <li class="check_people check_people_add p_{pigcms{$vv.level_v}">
                                                        <img src="/static/images/cancelordercheckauth.png" >
                                                        <span><strong style="color:red;">*</strong>审批人</span>
                                                        <select name="check_level[]" <if condition="!empty($applyhas)"> disabled </if> >
                                                            <option value="0" >请选择</option>
                                                            <volist name="worker_list" id="vo">
                                                                <option value="{pigcms{$vo.wid}" <if condition="$vv['level_wid'] gt 0 AND $vv['level_wid'] eq $vo['wid']">selected </if> > {pigcms{$vo.department_str}{pigcms{$vo.name}</option>
                                                            </volist>
                                                        </select>
                                                        <input type="hidden" class="check_level_v" value="{pigcms{$vv.level_v}">
                                                        <if condition="empty($applyhas)">  
                                                            <a href="javascript:;" class="ant-btn xdel" onclick="delCheckControl(this,{pigcms{$vv.level_v},{pigcms{$vv.level_wid})"> 删除 </a>
                                                        </if>
                                                    </li>
												</volist>
											</if>
                                        </ul>
                                     </div>
							  </div>
							  
							 <!-- <a  href="javascript:;" class="ant-btn ant-btn-primary saveOrderCheckControl" style="margin-left: 250px;"> 保存设置 </a>-->
							  
							</div>

                            <if condition="$third_import_data_switch_judge">
                                <div class="tab-pane" id="third_import">
                                    <iframe src="{pigcms{$third_import_url}" width="100%" scrolling="no" id="viewpageIframe" style="height:100%;border:0;overflow：hidden;">
                                        
                                    </iframe>
                                </div>
                            </if>

                            <if condition="$orderModifyTitle && $orderModifyTitle neq ''">
                                <div class="tab-pane" id="orderCheckModifyControl" data-xtype="orderCheckModifyControl" >
                                    <div class="form-group orderModifyCheckOpen">
                                        <label class="col-sm-3" style="width: 21%;"><label for="orderModifyCheckOpen">是否开启{pigcms{$orderModifyTitle}流程：</label></label>
                                        <label style="padding-left:0px;padding-right:20px;"><input name="orderModifyCheckOpen" value="0" type="radio"  <if condition="$orderModifyCheckOpen eq 0"> checked </if> class="ace " <if condition="$orderModifyCheckOpen eq 1 AND !empty($applyModify)"> disabled </if>  /><span class="lbl" style="z-index: 1"> 关闭 </span></label>
                                        <label style="padding-left:0px;"><input name="orderModifyCheckOpen" value="1" type="radio"  <if condition="$orderModifyCheckOpen eq 1"> checked </if> class="ace" <if condition="$orderModifyCheckOpen eq 1 AND !empty($applyModify)"> disabled </if> /><span class="lbl" style="z-index: 1"> 开启 </span></label>
                                        <span class="form_tips" style="color:red;"><if condition="!empty($applyModify)"> 有在审核的流程未结束（{pigcms{$applyModify.msg}），不可改动相关设置 </if></span>
                                    </div>
                                    <div class="form-group" style="margin-left: 10px;">
                                        <p>提示说明</p>
                                        <div>
                                            <p>1、设置待缴账单修改费用、挂单账单修改费用的审核管理人员</p>
                                        </div>

                                        <div>
                                            <p>2、审核流程从上往下进行设置，最多可以设置5人进行审核。审核流程可根据当前角色减少申请流程（只有设置的审批人发起修改费用会减少审核流程）</p>
                                            <div class="tips" style="margin-left: 20px;">
                                                <p>例：收银台客服发起修改费用申请流程→客服主管审核→物业经理审核→物业总经理审核→财务主管审核</p>
                                                <p>例：收银台物业经理发起修改费用申请流程→物业总经理审核→财务主管审核</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin-left: 30px;">
                                        <label style="width: 25%;"> <a class="ant-btn ant-btn-primary add_check_control1" href="javascript:;" onclick="addCheckControl1()" <if condition="$orderModifyCheckLevelNum egt 4"> style="display:none;" </if> > 新增审批人</a></label>
                                        <div class="check_control_p1" style="margin: 35px 0px 35px 50px;">
                                            <ul>
                                                <li class="check_people1 p1_0">
                                                    <img src="/static/images/cancelordercheckauth.png" >
                                                    <span><strong style="color:red;">*</strong>审批人</span>
                                                    <select name="check_level1[]" <if condition="!empty($applyModify)"> disabled </if> >
                                                    <option value="0" >请选择</option>
                                                    <volist name="worker_list" id="vo">
                                                        <option value="{pigcms{$vo.wid}" <if condition="$orderModifyCheckLevelWid0 gt 0 AND $orderModifyCheckLevelWid0 eq $vo['wid']">selected  </if> > {pigcms{$vo.department_str}{pigcms{$vo.name}</option>
                                                    </volist>
                                                    </select>
                                                    <input type="hidden" class="check_level_v1" value="0">
                                                </li>

                                                <if condition="!empty($orderModifyCheckLevel)">
                                                    <volist name="orderModifyCheckLevel" id="vv1">
                                                        <li class="xline"></li>
                                                        <li class="check_people1 check_people_add1 p1_{pigcms{$vv1.level_v}">
                                                            <img src="/static/images/cancelordercheckauth.png" >
                                                            <span><strong style="color:red;">*</strong>审批人</span>
                                                            <select name="check_level1[]" <if condition="!empty($applyModify)"> disabled </if> >
                                                                <option value="0" >请选择</option>
                                                                <volist name="worker_list" id="vo1">
                                                                    <option value="{pigcms{$vo1.wid}" <if condition="$vv1['level_wid'] gt 0 AND $vv1['level_wid'] eq $vo1['wid']">selected </if> > {pigcms{$vo1.department_str}{pigcms{$vo1.name}</option>
                                                                </volist>
                                                            </select>
                                                            <input type="hidden" class="check_level_v1" value="{pigcms{$vv1.level_v}">
                                                            <if condition="empty($applyModify)">
                                                                <a href="javascript:;" class="ant-btn xdel1" onclick="delCheckControl1(this,{pigcms{$vv1.level_v},{pigcms{$vv1.level_wid})"> 删除 </a>
                                                            </if>
                                                        </li>
                                                    </volist>
                                                </if>
                                            </ul>
                                        </div>
                                    </div>
                                    <if condition="$is_aihorse_customized || $isFuncKaidalaicnSpecial"> 
                                    <div class="form-group" style="margin-left: 30px;">
                                        <div style="margin: 20px;font-size: 16px;font-weight: bold;"><p style="padding-bottom: 10px;bold;border-bottom: 1px solid #ccc;">收银台修改待缴账单金额折扣配置</p></div>
                                        
                                        <a class="ant-btn ant-btn-primary add_order_modify_discount" href="javascript:;" onclick="addDiscountControl()" style="margin-left: 50px; <if condition="$orderModifyDiscountNum egt 100"> display:none; </if>" > 新增折扣配置</a>
                                        <div class="check_control_dis" style="margin: 35px 0px 35px 50px;">
                                            <ul>
                                                <if condition="!empty($orderModifyDiscount)">
                                                    <volist name="orderModifyDiscount" id="ddv">
                                                        <li class="order_dis d1_{pigcms{$key}">
                                                            <input type="number" class="order_modify_discount" value="{pigcms{$ddv}"  min="0" max="100" required="true" step="0.1" placeholder="支持1位有效小数" pattern="^\d+(\.\d)?$" > %  
                                                             <a href="javascript:;" class="ant-btn xdel1" onclick="delDiscountControl(this)"> 删除 </a>
                                                        </li>
                                                    </volist>
                                                </if>
                                            </ul>
                                        </div>
                                    </div>
                                    </if> 
                                </div>
                            </if>


                            <if condition="$config['hikGatewayHost'] && $config['hikGatewayHost'] neq ''">
                                <div class="tab-pane" id="hikDeviceGateway" data-xtype="hikDeviceGateway">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="hik_gateway_open">是否开启</label></label>
                            
                                    <label style="padding-left:0px;padding-right:20px;"><input name="hik_gateway_open" value="1" type="radio" class="ace" <if condition="$village_info.hik_gateway_open eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                    <label style="padding-left:0px;"><input name="hik_gateway_open" value="0" type="radio" class="ace" <if condition="$village_info.hik_gateway_open eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span>
                                    </label>
                                    <span class="form_tips">开启后，<font class="green">&nbsp;添加海康的
                                            《<a href="javascript:;" class="blue" onclick="open_new_page('/village/village.device.camera/cameraList','new_v20')">监控设备</a>》和
                                            《<a href="javascript:;" class="blue" onclick="open_new_page('/village/village.iframe/house_face_door_door_list','new_v20')">门禁设备</a>》&nbsp;
                                        </font>才可以出现【Hik Device Gateway 海康网关(小区)】的设备协议，如果使用平台的则不需要配置！</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="hik_gateway_protocol">协议类型</label></label>
                                    <input class="col-sm-2" value="{pigcms{$village_info.hik_gateway_protocol}" type="text" id="hik_gateway_protocol" name="hik_gateway_protocol"/>
                                    <span class="form_tips">只支持 http,https。一般都是 http ，若是填写 https 请确保你已经正确配置了网关的 HTTPS 证书！</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="hik_gateway_host">网关IP或域名</label></label>
                                    <input class="col-sm-2" value="{pigcms{$village_info.hik_gateway_host}" type="text" id="hik_gateway_host" name="hik_gateway_host"/>
                                    <span class="form_tips">建议直接写 网关的IP </span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="hik_gateway_port">网关端口</label></label>
                                    <input class="col-sm-2" value="{pigcms{$village_info.hik_gateway_port}" type="text" id="hik_gateway_port" name="hik_gateway_port"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="hik_gateway_username">网关账号</label></label>
                                    <input class="col-sm-2" value="{pigcms{$village_info.hik_gateway_username}" type="text" id="hik_gateway_username" name="hik_gateway_username"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="hik_gateway_password">网关密码</label></label>
                                    <input class="col-sm-2" value="{pigcms{$village_info.hik_gateway_password}" type="text" id="hik_gateway_password" name="hik_gateway_password"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="hik_gateway_network_host">平台端口映射外部地址	</label></label>
                                    <input class="col-sm-2" value="{pigcms{$village_info.hik_gateway_network_host}" type="text" id="hik_gateway_network_host" name="hik_gateway_network_host"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="hik_gateway_rtsp_port">平台端口映射RTSP外部端口</label></label>
                                    <input class="col-sm-2" value="{pigcms{$village_info.hik_gateway_rtsp_port}" type="text" id="hik_gateway_rtsp_port" name="hik_gateway_rtsp_port"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="hik_gateway_http_port">平台端口映射HTTP外部端口</label></label>
                                    <input class="col-sm-2" value="{pigcms{$village_info.hik_gateway_http_port}" type="text" id="hik_gateway_http_port" name="hik_gateway_http_port"/>
                                </div>
                            </div>
                            </if>

							<div class="tab-pane" id="orderServiceTimeControl" data-xtype="orderServiceTimeControl">
							   <div class="form-group order_service_time_open">
                                    <label class="col-sm-3" style="width: 21%;"><label for="order_service_time_open">是否开启房间计费时间修改审核流程：</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="order_service_time_open" value="0" type="radio"  <if condition="$servicetime_check_open eq 0"> checked </if> class="ace " <if condition="$servicetime_check_open eq 1 AND !empty($servicetimeapplyhas)"> disabled </if>  /><span class="lbl" style="z-index: 1"> 关闭 </span></label>
                                    <label style="padding-left:0px;"><input name="order_service_time_open" value="1" type="radio"  <if condition="$servicetime_check_open eq 1"> checked </if> class="ace" <if condition="$servicetime_check_open eq 1 AND !empty($servicetimeapplyhas)"> disabled </if> /><span class="lbl" style="z-index: 1"> 开启 </span></label>
                                    <span class="form_tips" style="color:red;"><if condition="!empty($servicetimeapplyhas)"> 有在审核的流程未结束（{pigcms{$servicetimeapplyhas.msg}），不可改动相关设置 </if></span>
                                </div>

                                <div class="form-group" style="margin-left: 10px;">
                                    <p>提示说明</p>
                                    <div>
                                     <p>1、设置房间计费时间修改审核管理人员</p>
                                    </div>

                                    <div>
                                     <p>2、房间计费时间修改审核流程从上往下进行设置，最多可以设置5人进行审核。审核流程可根据当前角色减少申请流程（只有设置的审批人发起房间计费时间修改会减少审核流程）</p>
                                     <div class="tips" style="margin-left: 20px;margin-top: 7px;"> 
                                        <p>例：收银台客服发起退款申请流程→客服主管审核→物业经理审核→物业总经理审核→财务主管审核</p>
                                        <p>例：收银台物业经理发起退款申请流程→物业总经理审核→财务主管审核</p>
                                     </div>
                                    </div>
                                </div>
							    <div class="form-group" style="margin-left: 30px;">
									 <label style="width: 25%;"> <a class="ant-btn ant-btn-primary add_check_control3" href="javascript:;" onclick="addServiceTimeControl()" <if condition="$servicetime_check_level_num egt 4"> style="display:none;" </if> > 新增审批人</a></label>
                                     <div class="check_control_p3" style="margin: 35px 0px 35px 50px;">
                                        <ul>
                                            <li class="check_people p_0">
                                                <img src="/static/images/cancelordercheckauth.png" >
                                                <span><strong style="color:red;">*</strong>审批人</span>
                                                <select name="check_level3[]" <if condition="!empty($servicetimeapplyhas)"> disabled </if> >
                                                    <option value="0" >请选择</option>
                                                    <volist name="worker_list" id="vo">
                                                        <option value="{pigcms{$vo.wid}" <if condition="$servicetime_level0_wid gt 0 AND $servicetime_level0_wid eq $vo['wid']">selected  </if> > {pigcms{$vo.department_str}{pigcms{$vo.name}</option>
                                                    </volist>
                                                </select>
											    <input type="hidden" class="check_level_v3" value="0">
                                            </li>
											
											<if condition="!empty($servicetime_check_level)">
												<volist name="servicetime_check_level" id="vv">
													<li class="xline"></li>
                                                    <li class="check_people check_people_add p_{pigcms{$vv.level_v}">
                                                        <img src="/static/images/cancelordercheckauth.png" >
                                                        <span><strong style="color:red;">*</strong>审批人</span>
                                                        <select name="check_level3[]" <if condition="!empty($servicetimeapplyhas)"> disabled </if> >
                                                            <option value="0" >请选择</option>
                                                            <volist name="worker_list" id="vo">
                                                                <option value="{pigcms{$vo.wid}" <if condition="$vv['level_wid'] gt 0 AND $vv['level_wid'] eq $vo['wid']">selected </if> > {pigcms{$vo.department_str}{pigcms{$vo.name}</option>
                                                            </volist>
                                                        </select>
                                                        <input type="hidden" class="check_level_v3" value="{pigcms{$vv.level_v}">
                                                        <if condition="empty($servicetimeapplyhas)">  
                                                            <a href="javascript:;" class="ant-btn xdel3" onclick="delCheckControl3(this,{pigcms{$vv.level_v},{pigcms{$vv.level_wid})"> 删除 </a>
                                                        </if>
                                                    </li>
												</volist>
											</if>
                                        </ul>
                                     </div>
							  </div>
							  
							 <!-- <a  href="javascript:;" class="ant-btn ant-btn-primary saveOrderCheckControl" style="margin-left: 250px;"> 保存设置 </a>-->
							  
							</div>

                        <if condition="$is_aihorse_customized"> 

                            <div class="tab-pane" id="orderModifyPayTimeControl" data-xtype="orderModifyPayTimeControl">
							   <div class="form-group order_pay_time_open">
                                    <label class="col-sm-3" style="width: 21%;"><label for="order_pay_time_open">是否开启已缴账单修改支付时间审核设置流程：</label></label>
                                    <label style="padding-left:0px;padding-right:20px;"><input name="order_pay_time_open" value="0" type="radio"  <if condition="$paytime_check_open eq 0"> checked </if> class="ace " <if condition="$order_pay_time_open eq 1 AND !empty($paytimeapplyhas)"> disabled </if>  /><span class="lbl" style="z-index: 1"> 关闭 </span></label>
                                    <label style="padding-left:0px;"><input name="order_pay_time_open" value="1" type="radio"  <if condition="$paytime_check_open eq 1"> checked </if> class="ace" <if condition="$paytime_check_open eq 1 AND !empty($paytimeapplyhas)"> disabled </if> /><span class="lbl" style="z-index: 1"> 开启 </span></label>
                                    <span class="form_tips" style="color:red;"><if condition="!empty($paytimeapplyhas)"> 有在审核的流程未结束（{pigcms{$paytimeapplyhas.msg}），不可改动相关设置 </if></span>
                                </div>

                                <div class="form-group" style="margin-left: 10px;">
                                    <p>提示说明</p>
                                    <div>
                                     <p>1、设置已缴账单修改支付时间审核管理人员</p>
                                    </div>

                                    <div>
                                     <p>2、已缴账单修改支付时间审核流程从上往下进行设置，最多可以设置5人进行审核。审核流程可根据当前角色减少申请流程（只有设置的审批人发起已缴账单支付时间修改会减少审核流程）</p>
                                     <div class="tips" style="margin-left: 20px;margin-top: 7px;"> 
                                        <p>例：收银台客服发起已缴账单支付时间修改申请流程→客服主管审核→物业经理审核→物业总经理审核→财务主管审核</p>
                                        <p>例：收银台物业经理发起已缴账单支付时间修改申请流程→物业总经理审核→财务主管审核</p>
                                     </div>
                                    </div>
                                </div>
							    <div class="form-group" style="margin-left: 30px;">
									 <label style="width: 25%;"> <a class="ant-btn ant-btn-primary add_check_control4" href="javascript:;" onclick="addPayTimeControl()" <if condition="$paytime_check_level_num egt 4"> style="display:none;" </if> > 新增审批人</a></label>
                                     <div class="check_control_p4" style="margin: 35px 0px 35px 50px;">
                                        <ul>
                                            <li class="check_people p_0">
                                                <img src="/static/images/cancelordercheckauth.png" >
                                                <span><strong style="color:red;">*</strong>审批人</span>
                                                <select name="check_level4[]" <if condition="!empty($paytimeapplyhas)"> disabled </if> >
                                                    <option value="0" >请选择</option>
                                                    <volist name="worker_list" id="vo">
                                                        <option value="{pigcms{$vo.wid}" <if condition="$paytime_level0_wid gt 0 AND $paytime_level0_wid eq $vo['wid']">selected  </if> > {pigcms{$vo.department_str}{pigcms{$vo.name}</option>
                                                    </volist>
                                                </select>
											    <input type="hidden" class="check_level_v4" value="0">
                                            </li>
											
											<if condition="!empty($paytime_check_level)">
												<volist name="paytime_check_level" id="vv">
													<li class="xline"></li>
                                                    <li class="check_people check_people_add p_{pigcms{$vv.level_v}">
                                                        <img src="/static/images/cancelordercheckauth.png" >
                                                        <span><strong style="color:red;">*</strong>审批人</span>
                                                        <select name="check_level4[]" <if condition="!empty($paytimeapplyhas)"> disabled </if> >
                                                            <option value="0" >请选择</option>
                                                            <volist name="worker_list" id="vo">
                                                                <option value="{pigcms{$vo.wid}" <if condition="$vv['level_wid'] gt 0 AND $vv['level_wid'] eq $vo['wid']">selected </if> > {pigcms{$vo.department_str}{pigcms{$vo.name}</option>
                                                            </volist>
                                                        </select>
                                                        <input type="hidden" class="check_level_v4" value="{pigcms{$vv.level_v}">
                                                        <if condition="empty($paytimeapplyhas)">  
                                                            <a href="javascript:;" class="ant-btn xdel4" onclick="delCheckControl4(this,{pigcms{$vv.level_v},{pigcms{$vv.level_wid})"> 删除 </a>
                                                        </if>
                                                    </li>
												</volist>
											</if>
                                        </ul>
                                     </div>
							  </div>
							  
							</div>
                            </if>

							<div class="clearfix form-actions save_btn-info">
								<div class="col-md-offset-3 col-md-9">
									<if condition="empty($house_session['menus']) OR (empty($house_session['menus']['0'])) OR in_array(2,$house_session['menus'])">
									<button type="submit" class="btn btn-info">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保 存
									</button>
									<else/>
									<button type="button" class="btn btn-info disabled" disabled="disabled" style="cursor: not-allowed;">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保 存
									</button>
									</if>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
			</div>
		</div>
	</div>
</div>
<div id="modal-table" class="modal fade map-modal" tabindex="-1" style="display:none;">
	<div class="modal-dialog" style="width:80%;">
		<div class="modal-content" style="width:100%;">
			<div class="modal-header no-padding" style="width:100%;">
				<div class="table-header">
					<button id="close_button" type="button" class="close" data-dismiss="modal" aria-hidden="true">
						<span class="white">&times;</span>
					</button>
					(用鼠标滚轮可以缩放地图)    拖动红色图标，经纬度框内将自动填充经纬度。
				</div>
			</div>
			<div class="modal-body no-padding" style="width:100%;">
				<form id="map-search" style="margin:10px;">
					<input id="map-keyword" type="textbox" style="width:500px;" placeholder="尽量填写城市、区域、街道名"/>
					<input type="submit" value="搜索"/>
				</form>
				<div style="width:100%;height:600px;min-height:600px;" id="cmmap"></div>
			</div>
			<div class="modal-footer no-margin-top">
				<button class="btn btn-sm btn-success pull-right" data-dismiss="modal">
					<i class="ace-icon fa fa-times"></i>
					关闭
				</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",long="{pigcms{$village_info.long}",lat="{pigcms{$village_info.lat}";
var choose_provincess="{pigcms{:U('Area/ajax_province')}",choose_cityss="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="";
var choose_street="{pigcms{:U('Area/ajax_street')}",choose_community="{pigcms{:U('Area/ajax_community')}";
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/area_street.js?t=06"></script>
<if condition="$config['tianditu_web_key']">
    <script src="https://api.tianditu.gov.cn/api?v=4.0&tk={pigcms{$config.tianditu_web_key}" type="text/javascript"></script>
    <script src="/tpl/Static/js/coordinate.js"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/tianditu_map.js"></script>
<else />
    <script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=03"></script>
</if>

<style>
    .tips {
        position: relative;
        padding: 10px 10px 10px 15px;
        color: rgba(0, 0, 0, 0.65);
        line-height: 1.5;
        border-radius: 2px;
        background-color: #e6f7ff;
        border: 1px solid #91d5ff;
    }
    .BMap_cpyCtrl{display:none;}
    input.ke-input-text {
        background-color: #FFFFFF;
        background-color: #FFFFFF!important;
        font-family: "sans serif",tahoma,verdana,helvetica;
        font-size: 12px;
        line-height: 24px;
        height: 24px;
        padding: 2px 4px;
        border-color: #848484 #E0E0E0 #E0E0E0 #848484;
        border-style: solid;
        border-width: 1px;
        display: -moz-inline-stack;
        display: inline-block;
        vertical-align: middle;
        zoom: 1;
    }
    .form-group>label{font-size:12px;line-height:24px;}
    #upload_pic_box{margin-top:20px;height:150px;}
    #upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
    #upload_pic_box img{width:100px;height:70px;border:1px solid #ccc;}
</style>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>    
<script>
    KindEditor.ready(function(K) {
        var editor = K.editor({
            allowFileManager : true
        });
        K('#image3').click(function() {
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#village_logo_view');
                        img.attr("src",url);
                        img.show();
                        K("#village_logo").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });
        K('#seal_img').click(function() {
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#village_seal_view');
                        img.attr("src",url);
                        img.show();
                        K("#village_seal_img").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });

        K('#image_basic').click(function() {
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#village_floor_view');
                        img.attr("src",url);
                        img.show();
                        K("#village_floor").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });
        K('#image_basic1').click(function() {
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#village_photo_view');
                        img.attr("src",url);
                        img.show();
                        var img = K('#village_photo_view01');
                        img.attr("src",url);
                        img.show();

                        K("#village_photo").val(url);
                        K("#village_photo01").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });
       K('#image_basic01').click(function() {
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#village_photo_view01');
                        img.attr("src",url);
                        img.show();
                       var img = K('#village_photo_view');
                        img.attr("src",url);
                        img.show();
                        K("#village_photo").val(url);
                        K("#village_photo01").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });


        K('#image_basic11').click(function() {
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#village_photo_view1');
                        img.attr("src",url);
                        img.show();
                        K("#village_photo1").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });
        K('#image_basic12').click(function() {
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#village_photo_view2');
                        img.attr("src",url);
                        img.show();
                        K("#village_photo2").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });
        K('#image_basic21').click(function() {
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#village_photo_view3');
                        img.attr("src",url);
                        img.show();
                        K("#village_photo3").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });

        K('#village_cockpit_logo_img').click(function() {
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#village_cockpit_logo_view');
                        img.attr("src",url);
                        img.show();
                        K("#village_cockpit_logo").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });


        K('#weixin_qr_code_put').click(function() {
            editor.uploadJson = "{pigcms{:U('Cashier/ajax_upload_pic')}";
            editor.loadPlugin('image', function() {
                editor.plugin.imageDialog({
                    showRemote : false,
                    imageUrl : K('#url3').val(),
                    clickFn : function(url, title, width, height, border, align) {
                        var img = K('#weixin_qr_code_view');
                        img.attr("src",url);
                        img.show();
                        K("#weixin_qr_code").val(url);
                        editor.hideDialog();
                    }
                });
            });
        });
    });
</script>
<script type="text/javascript">
var lock = 0, test;
KindEditor.ready(function(K){
	$('#edit_form').submit(function(){
		var xtype=$('#edit_form .tab-content .tab-pane.active').data('xtype');
        if(xtype && xtype=='orderCheckControl'){
            saveOrderCheckControl();
            return false;
        }
        if(xtype && xtype=='orderCheckModifyControl'){
            saveOrderCheckControl1();
            return false;
        }
        if(xtype && xtype=='orderServiceTimeControl'){
            saveOrderCheckControl3();
            return false;
        }
        if(xtype && xtype=='orderModifyPayTimeControl'){
            saveOrderCheckControl4();
            return false;
        }
        
		//2020/8/21 位置转移 收费管理->收费设置->缴费设置
		var property_price = $("#property_price").val();
		// if(property_price<0){
		// 	alert('物业费单价填写不合法！');
		// 	return false;
		// }
		// var water_price = $("#water_price").val();
		// if(water_price<0){
		// 	alert('水费单价填写不合法！');
		// 	return false;
		// }
		// var electric_price = $("#electric_price").val();
		// if(electric_price<0){
		// 	alert('电费单价填写不合法！');
		// 	return false;
		// }
		// var gas_price = $("#gas_price").val();
		// if(gas_price<0){
		// 	alert('燃气费单价填写不合法！');
		// 	return false;
		// }
		// var park_price = $("#park_price").val();
		// if(park_price<0){
		// 	alert('停车费单价填写不合法！');
		// 	return false;
		// }
		// var property_warn_day = $("#property_warn_day").val();
		// if(property_warn_day<0){
		// 	alert('物业提醒时间不合法！');
		// 	return false;
		// }

		var old_pwd = $("#old_pwd").val();
		var new_pwd = $("#pwd").val();
		var sure_pwd = $("#sure_pwd").val();
		if (new_pwd) {
			if (!old_pwd) {
				alert('请输入原密码！');
				return false;
			}
			if (new_pwd.length < 6) {
				alert('密码最少6位！');
				return false;
			}
			if (new_pwd!=sure_pwd) {
				alert('确认密码与新密码不一样！');
				return false;
			}
		}
		
		$('#edit_form button[type="submit"]').prop('disabled',true).html('保存中...');
		if (lock = 1) {
			return;
		}
		lock = 1;

		
		$.post("{pigcms{:U('Index/village_edit')}",$('#edit_form').serialize(),function(result){
			lock = 0;
			if(result.status == 1){
				window.location.href = "{pigcms{:U('Index/index')}";
			}else{
				$('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
				alert(result.info);
			}
		})
		return false;
	});
});
function copyContent(){
    var copyobject=document.getElementById("house_village_login");
    copyobject.select();
    document.execCommand("Copy");
    layer.alert('已复制成功哦~');
};

//新版工单及时率
$('.help_type').on('change', function () {
    var that=$(this),value = that.val(),str='',width='6.59%',idd=that.attr('data-id');
    if(value == 3){
        str='<input class="col-sm-1 help_asse" maxlength="10" size="20" name="help_info[custom]['+idd+']" value="" type="text" placeholder="请输入自定义" />';
        width='-3.09%'
    }
    that.width(width)
    that.siblings('.help_custom').html(str)
});

//新版工单自动抢单
$('input[name=is_grab_order]').click(function(){
    if ($(this).val() == 1) {
        $('.grab_order_time').show();
    } else {
        $('.grab_order_time').hide();
    }
});

$('.help_info_close').click(function(){
    var tmpV=$(this).val();
    if(tmpV==1 || tmpV=='1'){
        $('.help_body').hide();
    }else{
        $('.help_body').show();
    }
});

//在线报修 投诉建议绑定指定工作人员
function bind_worker(){
    layer.open({
        type: 2,
        title: '绑定指定工作人员',
        shadeClose: false,
        maxmin: true, //开启最大化最小化按钮
        area: ['900px', '500px'],
        content: "{pigcms{:U('Index/bind_worker')}"
    });
};

//新版工单绑定指定工作人员
function new_repair_bind_worker(){
    layer.open({
        type: 2,
        title: '绑定指定工作人员',
        shadeClose: false,
        maxmin: true, //开启最大化最小化按钮
        area: ['800px', '500px'],
        content: "{pigcms{:U('Index/new_repair_bind_worker')}"
    });
}

$(document).ready(function(){
    var optionValue=$('#meter_type').val();
    if(optionValue == 1){
        $('#meter_time').css('display','none');
        $('#meter_day').css('display','inline');
    }else{
        $('#meter_time').css('display','inline');
        $('#meter_day').css('display','none');
    }
	$('input[name=handle_type]').click(function(){
		if ($(this).val() == 1) {
			$('.hour').show();
		} else {
			$('.hour').hide();
		}
	});
	
	$('input[name=owe_property_open_door]').click(function(){
		if ($(this).val() == 1) {
			$('.door_day').show();
		} else {
			$('.door_day').hide();
		}
	});
	$('.see_qrcode').click(function(){
		test = art.dialog.open($(this).attr('href'),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'扫描二维码绑定微信号',
			padding: 0,
			width: 430,
			height: 433,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: function(){clearInterval(t);},
			left: '50%',
			top: '38.2%',
			opacity:'0.4'
		});
		var t = window.setInterval(function(){
			$.get("{pigcms{:U('Index/check_bind')}", function(result){
				if (result.error_code == 0) {
					$('#nickname').val(result.nickname);
					$('#avatar').attr('src', result.avatar);
					clearInterval(t);
					test.close();
					$('#weixin_bind').hide();
					$('#weixin_info').show();
				}
			}, 'json');
		},3000);
		return false;
	});
	$('#cancel').click(function(){
		var obj = $(this);
		obj.attr('disabled', true);
		$.post("{pigcms{:U('Index/cancel')}", function(result){
			obj.attr('disabled', false);
			if (result.error_code == 1) {
				alert(result.msg);
			} else {
				$('#weixin_bind').show();
				$('#weixin_info').hide();
			}
		}, 'json');
	});

	// 人脸门禁授权
	$('.authorize').click(function(){
		window.location = 'https://openapi.vooct.com/openapi/oauth/authorize?response_type=code&scope=read&state=123&client_id=ynlk2019&redirect_uri={pigcms{$redirect_uri}';		
		return false;
	});


	// 人脸门禁同步社区
	$('.sync_village').click(function(){
		var type = $(this).data('type');
		var that = $(this);
		layer.open({
		  	title: '提示'
		  	,type:1
		  	,content: '<div style="width:250px;height:80px;text-align:center;margin-top:30px;">同步中,请勿关闭网页</div>'
		});     
  
		$.post('{pigcms{:U("sync_data")}',{type:type},function(data){
			console.log(data)
			layer.closeAll();
			if (data.code==1) {
				that.html('已同步')
			}else{
				layer.alert(data.msg)
			}
		},'json')
		return false;
	});


	// 【A2智能门禁机】人脸识别门禁同步
    $('.sync_house_village_info').click(function () {
        var that = $(this);
        layer.open({
            title: '提示'
            ,type:1
            ,content: '<div style="width:250px;height:80px;text-align:center;margin-top:30px;">同步中,请勿关闭网页</div>'
        });

        $.post('{pigcms{:U("Face_door/sync_village_data")}',{type:'all'},function(data){
            console.log(data)
            layer.closeAll();
            if (data.code==1) {
                that.html('已同步')
            }else{
                layer.alert(data.msg)
            }
        },'json')
        return false;
    });
	
	//新冠肺炎
	$('#ncov_2019_out_type').change(function(){
		var value = $(this).val();
		if(value == 0){
			$('#ncov_2019_week_group').hide();
			$('#ncov_2019_day_group').hide();
			$('#ncov_2019_limit_group').hide();
			$('#ncov_2019_time_group').hide();
		}else if(value == 1){
			$('#ncov_2019_time_group').show();
			$('#ncov_2019_limit_group').show();
			$('#ncov_2019_week_group').show();
			$('#ncov_2019_day_group').hide();
		}else if(value == 2){
			$('#ncov_2019_week_group').hide();
			$('#ncov_2019_day_group').show();
			$('#ncov_2019_limit_group').show();
			$('#ncov_2019_time_group').show();
		}
	});
});

function door_list(){
	var href = "{pigcms{:U('door_list')}";
	art.dialog.open(href,{
		init: function(){
			var iframe = this.iframe.contentWindow;
			window.top.art.dialog.data('iframe_handle',iframe);
		},
		id: 'handle',
		title:'门禁列表',
		padding: 0,
		width: '55%',
		height: '85%',
		lock: true,
		resize: false,
		background:'black',
		button: null,
		fixed: false,
		close: null,
		left: '50%',
		top: '38.2%',
		opacity:'0.4'
	});
}
$("#meter_type").change(function(){
    var optionValue=$(this).children('option:selected').val();
    if(optionValue == 1){
        $('#meter_time').css('display','none');
        $('#meter_day').css('display','inline');
    }else{
        $('#meter_time').css('display','inline');
        $('#meter_day').css('display','none');
    }
});

    $('#dddddddd').click(function () {
		sessionStorage.removeItem('is_edit');
		var is_edit=false
		sessionStorage.setItem('is_edit', is_edit);
		window.open("/v20/public/platform/#/community/village/cockpit/hotZone?single_id=-1")
       /* var that = $(this);
        layer.open({
            type: 2,
            title: '绘制区域',
            fix: false,
            shadeClose: true,
            maxmin: true,
            area: ['80%', '70%'],
            content: urlss,
        });
 */
        return false;
    });
	var worker_list={pigcms{$worker_list_json};
	function addCheckControl(){
        var checkNum=$('.check_control_p .check_people').size();
        if(checkNum>=5){
            layer.alert('您已经添加5个人了，不可在添加审批人员！');
        }else {
            var checkTmp='<li class="xline"></li><li class="check_people check_people_add p_'+(checkNum)+'"><img src="/static/images/cancelordercheckauth.png" style="width: 50px;"><span><strong>*</strong>审批人</span><select name="check_level[]"><option value="0">请选择</option>';
            if(worker_list && worker_list.length>0){
                for(var ii=0;ii<worker_list.length;ii++){
                    checkTmp+='<option value="'+worker_list[ii].wid+'">'+worker_list[ii].department_str+worker_list[ii].name+'</option>';
                }
            }
            checkTmp +='</select> <input type="hidden" class="check_level_v" value="'+(checkNum)+'"> <a href="javascript:;" class="ant-btn xdel" onclick="delCheckControl(this)"> 删除 </a></li>';
            $('.check_control_p ul').append(checkTmp);
            if(checkNum>=4){
                $('.add_check_control').hide();
            }
            }
    }

    function delCheckControl(obj,level_v,wid){
        $(obj).parent('li').prev('.xline').remove();
        $(obj).parent('li').remove();
        var check_num=$('.check_control_p .check_people_add').size();
        $('.add_check_control').show();
        if(check_num>0){
            $('.check_control_p .check_people_add').each(function(ii){
                var mm=ii+1;
                $(this).find('.check_level_v').val(mm);
            });
        }
    }
	
	//保存数据设置
	var isposting=false;
	function saveOrderCheckControl(){
		if(isposting){
			//layer.msg('正在保存数据，请稍等...');
			layer.alert('正在保存数据，请稍等...');
            return false;
		}
		var postArr={};
		postArr.order_check_open=$('#orderCheckControl .order_check_open input:checked').val();
		var findnoselect=false;
		var check_level=[];
		$('.check_control_p .check_people').each(function(ii){
			var level_v=$(this).find('.check_level_v').val();
			level_v=$.trim(level_v);
			var check_level_wid=$(this).find('select').val();
			check_level_wid=$.trim(check_level_wid);
			if((check_level_wid==0 || check_level_wid=='0') && (postArr.order_check_open==1 || postArr.order_check_open=="1")){
				findnoselect=true;
				layer.alert('第'+(ii+1)+'项审批人未选择，请选择！');
				return false;
			}
			if(check_level.length>0){
				for(var mm=0;mm<check_level.length;mm++){
					if(check_level[mm].level_wid==check_level_wid){
						findnoselect=true;
						layer.alert('第'+(ii+1)+'项审批人已经选择过了，请选择其他人！');
						break;
					}
				}
			}
			if(!findnoselect){
				check_level.push({'level_v':level_v,'level_wid':check_level_wid});
			}
        });
		if(findnoselect){
			isposting=false;
			return false;
		}
		
		if((postArr.order_check_open==1 || postArr.order_check_open=="1") && check_level.length<1){
				isposting=false;
				layer.alert('请至少选择设置一项审批人！');
				return false;
		}
		postArr.check_level=check_level;
		postArr.xtype='order_refund_check';
		isposting=true;
		$.post("{pigcms{:U('Index/order_check_set')}",postArr,function(result){
			isposting=false;
			if(result.status == 1){
				layer.alert(result.info,function(index){
					//do something
					//layer.close(index);
					window.location.reload();
				});
			}else{
				$('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
				layer.alert(result.info);
			}
		})
	}


    function addCheckControl1(){
        var checkNum=$('.check_control_p1 .check_people1').size();
        if(checkNum>=5){
            layer.alert('您已经添加5个人了，不可在添加审批人员！');
            return false;
        }
        var checkTmp='<li class="xline"></li><li class="check_people1 check_people_add1 p1_'+(checkNum)+'"><img src="/static/images/cancelordercheckauth.png" style="width: 50px;"><span><strong>*</strong>审批人</span><select name="check_level1[]"><option value="0">请选择</option>';
        if(worker_list && worker_list.length>0){
            for(var ii=0;ii<worker_list.length;ii++){
                checkTmp+='<option value="'+worker_list[ii].wid+'">'+worker_list[ii].department_str+worker_list[ii].name+'</option>';
            }
        }
        checkTmp +='</select> <input type="hidden" class="check_level_v1" value="'+(checkNum)+'"> <a href="javascript:;" class="ant-btn xdel1" onclick="delCheckControl1(this)"> 删除 </a></li>';
        $('.check_control_p1 ul').append(checkTmp);
        if(checkNum>=4){
            $('.add_check_control1').hide();
        }
    }

    function delCheckControl1(obj,level_v,wid){
        $(obj).parent('li').prev('.xline').remove();
        $(obj).parent('li').remove();
        var check_num=$('.check_control_p1 .check_people_add1').size();
        $('.add_check_control1').show();
        if(check_num>0){
            $('.check_control_p1 .check_people_add1').each(function(ii){
                var mm=ii+1;
                $(this).find('.check_level_v1').val(mm);
            });
        }
    }

    function addDiscountControl(){
         var checkNum=$('.check_control_dis .order_modify_discount').size();
        if(checkNum>=100){
            layer.alert('您已经添加100个优惠折扣了！');
            return false;
        }
        var discountHtml ='<li class="order_dis d1_'+(checkNum)+'"><input type="number" pattern="^\d+(\.\d)?$" class="order_modify_discount" value="" min="0" max="100" required="true" step="0.1" placeholder="支持1位有效小数">  % <a href="javascript:;" class="ant-btn xdel1" onclick="delDiscountControl(this)"> 删除 </a></li>'
        if(checkNum>=99){
            $('.add_order_modify_discount').hide();
        }
        $('.check_control_dis ul').append(discountHtml);
    }
    function  delDiscountControl(obj){
        $(obj).parent('li').prev('.xline').remove();
        $(obj).parent('li').remove();
    }
    //保存数据设置
    var isposting1 = false;
    function saveOrderCheckControl1(){
        if(isposting1){
            layer.alert('正在保存数据，请稍等...');
            return false;
        }
        var postArr1={};
        postArr1.order_check_open = $('#orderCheckModifyControl .orderModifyCheckOpen input:checked').val();
        if (!postArr1.order_check_open) {
            postArr1.order_check_open = 0;
        }
        var findnoselect1=false;
        var check_level1=[];
        var levelKey = 0;
        $('.check_control_p1 .check_people1').each(function(ii){
            var level_v = $(this).find('.check_level_v1').val();
            level_v = $.trim(level_v);
            if (levelKey == 0 && !level_v) {
                level_v = 0;
            } else if(!level_v) {
                level_v = levelKey;
            }
            var check_level_wid = $(this).find('select').val();
            check_level_wid = $.trim(check_level_wid);
            if((check_level_wid==0 || check_level_wid=='0') && (postArr1.order_check_open==1 || postArr1.order_check_open=="1")){
                findnoselect1=true;
                layer.alert('第'+(ii+1)+'项审批人未选择，请选择！');
                return false;
            }
            if(check_level1.length>0){
                for(var mm=0;mm<check_level1.length;mm++){
                    if(check_level1[mm].level_wid==check_level_wid){
                        findnoselect1=true;
                        layer.alert('第'+(ii+1)+'项审批人已经选择过了，请选择其他人！');
                        break;
                    }
                }
            }
            if(!findnoselect1){
                check_level1.push({'level_v':level_v,'level_wid':check_level_wid});
                levelKey++;
            }
        });
        if(findnoselect1){
            isposting1 = false;
            return false;
        }
        if((postArr1.order_check_open==1 || postArr1.order_check_open=="1") && check_level1.length<1){
            isposting1 = false;
            layer.alert('请至少选择设置一项审批人！');
            return false;
        }
        var order_modify_discount=[];
        if($('.check_control_dis .order_modify_discount').size()>0){

            $('.check_control_dis .order_modify_discount').each(function(di){
                     var xdiscount = $(this).val()||0;
                     order_modify_discount.push(xdiscount);
            });
        }
        postArr1.check_level=check_level1;
        postArr1.xtype='order_modify_check';
        postArr1.order_modify_discount=order_modify_discount;
        isposting1 = true;
        console.log('postArr1', postArr1);
        $.post("{pigcms{:U('Index/order_check_set')}",postArr1,function(result){
            isposting1 = false;
            if(result.status == 1){
                layer.alert(result.info,function(index){
                    //do something
                    //layer.close(index);
                    window.location.reload();
                });
            }else{
                $('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
                layer.alert(result.info);
            }
        })
    }

   function addServiceTimeControl(){
        var checkNum=$('.check_control_p3 .check_people').size();
        if(checkNum>=5){
            layer.alert('您已经添加5个人了，不可在添加审批人员！');
        }else {
            var checkTmp='<li class="xline"></li><li class="check_people check_people_add3 p_'+(checkNum)+'"><img src="/static/images/cancelordercheckauth.png" style="width: 50px;"><span><strong>*</strong>审批人</span><select name="check_level3[]"><option value="0">请选择</option>';
            if(worker_list && worker_list.length>0){
                for(var ii=0;ii<worker_list.length;ii++){
                    checkTmp+='<option value="'+worker_list[ii].wid+'">'+worker_list[ii].department_str+worker_list[ii].name+'</option>';
                }
            }
            checkTmp +='</select> <input type="hidden" class="check_level_v3" value="'+(checkNum)+'"> <a href="javascript:;" class="ant-btn xdel3" onclick="delCheckControl3(this)"> 删除 </a></li>';
            $('.check_control_p3 ul').append(checkTmp);
            if(checkNum>=4){
                $('.add_check_control3').hide();
            }
            }
   }

    function delCheckControl3(obj,level_v,wid){
        $(obj).parent('li').prev('.xline').remove();
        $(obj).parent('li').remove();
        var check_num=$('.check_control_p3 .check_people_add3').size();
        $('.add_check_control3').show();
        if(check_num>0){
            $('.check_control_p3 .check_people_add3').each(function(ii){
                var mm=ii+1;
                $(this).find('.check_level_v3').val(mm);
            });
        }
    }
	//保存数据设置
	var isposting3=false;
	function saveOrderCheckControl3(){
		if(isposting3){
			//layer.msg('正在保存数据，请稍等...');
			layer.alert('正在保存数据，请稍等...');
            return false;
		}
		var postArr3={};
		postArr3.order_check_open=$('#orderServiceTimeControl .order_service_time_open input:checked').val();
		var findnoselect=false;
		var check_level=[];
		$('.check_control_p3 .check_people').each(function(ii){
			var level_v=$(this).find('.check_level_v3').val();
			level_v=$.trim(level_v);
			var check_level_wid=$(this).find('select').val();
			check_level_wid=$.trim(check_level_wid);
			if((check_level_wid==0 || check_level_wid=='0') && (postArr3.order_check_open==1 || postArr3.order_check_open=="1")){
				findnoselect=true;
				layer.alert('第'+(ii+1)+'项审批人未选择，请选择！');
				return false;
			}
			if(check_level.length>0){
				for(var mm=0;mm<check_level.length;mm++){
					if(check_level[mm].level_wid==check_level_wid){
						findnoselect=true;
						layer.alert('第'+(ii+1)+'项审批人已经选择过了，请选择其他人！');
						break;
					}
				}
			}
			if(!findnoselect){
				check_level.push({'level_v':level_v,'level_wid':check_level_wid});
			}
        });
		if(findnoselect){
			isposting3=false;
			return false;
		}
		
		if((postArr3.order_check_open==1 || postArr3.order_check_open=="1") && check_level.length<1){
				isposting3=false;
				layer.alert('请至少选择设置一项审批人！');
				return false;
		}
		postArr3.check_level=check_level;
		postArr3.xtype='order_servicetime_check';
		isposting3=true;
		$.post("{pigcms{:U('Index/order_check_set')}",postArr3,function(result){
			isposting3=false;
			if(result.status == 1){
				layer.alert(result.info,function(index){
					//do something
					//layer.close(index);
					window.location.reload();
				});
			}else{
				$('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
				layer.alert(result.info);
			}
		})
	}


   function addPayTimeControl(){
        var checkNum=$('.check_control_p4 .check_people').size();
        if(checkNum>=5){
            layer.alert('您已经添加5个人了，不可在添加审批人员！');
        }else {
            var checkTmp='<li class="xline"></li><li class="check_people check_people_add4 p_'+(checkNum)+'"><img src="/static/images/cancelordercheckauth.png" style="width: 50px;"><span><strong>*</strong>审批人</span><select name="check_level4[]"><option value="0">请选择</option>';
            if(worker_list && worker_list.length>0){
                for(var ii=0;ii<worker_list.length;ii++){
                    checkTmp+='<option value="'+worker_list[ii].wid+'">'+worker_list[ii].department_str+worker_list[ii].name+'</option>';
                }
            }
            checkTmp +='</select> <input type="hidden" class="check_level_v4" value="'+(checkNum)+'"> <a href="javascript:;" class="ant-btn xdel4" onclick="delCheckControl4(this)"> 删除 </a></li>';
            $('.check_control_p4 ul').append(checkTmp);
            if(checkNum>=4){
                $('.add_check_control4').hide();
            }
            }
   }

    function delCheckControl4(obj,level_v,wid){
        $(obj).parent('li').prev('.xline').remove();
        $(obj).parent('li').remove();
        var check_num=$('.check_control_p4 .check_people_add4').size();
        $('.add_check_control4').show();
        if(check_num>0){
            $('.check_control_p4 .check_people_add4').each(function(ii){
                var mm=ii+1;
                $(this).find('.check_level_v4').val(mm);
            });
        }
    }
	//保存数据设置
	var isposting4=false;
	function saveOrderCheckControl4(){
		if(isposting4){
			//layer.msg('正在保存数据，请稍等...');
			layer.alert('正在保存数据，请稍等...');
            return false;
		}
		var postArr4={};
		postArr4.order_check_open=$('#orderModifyPayTimeControl .order_pay_time_open input:checked').val();
		var findnoselect=false;
		var check_level=[];
		$('#orderModifyPayTimeControl .check_control_p4 .check_people').each(function(ii){
			var level_v=$(this).find('.check_level_v4').val();
			level_v=$.trim(level_v);
			var check_level_wid=$(this).find('select').val();
			check_level_wid=$.trim(check_level_wid);
			if((check_level_wid==0 || check_level_wid=='0') && (postArr4.order_check_open==1 || postArr4.order_check_open=="1")){
				findnoselect=true;
				layer.alert('第'+(ii+1)+'项审批人未选择，请选择！');
				return false;
			}
			if(check_level.length>0){
				for(var mm=0;mm<check_level.length;mm++){
					if(check_level[mm].level_wid==check_level_wid){
						findnoselect=true;
						layer.alert('第'+(ii+1)+'项审批人已经选择过了，请选择其他人！');
						break;
					}
				}
			}
			if(!findnoselect){
				check_level.push({'level_v':level_v,'level_wid':check_level_wid});
			}
        });
		if(findnoselect){
			isposting4=false;
			return false;
		}
		
		if((postArr4.order_check_open==1 || postArr4.order_check_open=="1") && check_level.length<1){
				isposting4=false;
				layer.alert('请至少选择设置一项审批人！');
				return false;
		}
		postArr4.check_level=check_level;
		postArr4.xtype='order_paytime_check';
		isposting4=true;
		$.post("{pigcms{:U('Index/order_check_set')}",postArr4,function(result){
			isposting3=false;
			if(result.status == 1){
				layer.alert(result.info,function(index){
					//do something
					//layer.close(index);
					window.location.reload();
				});
			}else{
				$('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
				layer.alert(result.info);
			}
		})
	}

    $('#adress3').click(function(){
        sessionStorage.removeItem('is_edit');
        var is_edit=false
        sessionStorage.setItem('is_edit', is_edit);
        window.open("/v20/public/platform/#/community/village/cockpit/equipPoint?type=3")
        return false;
    });
    $('#adress2').click(function(){
        sessionStorage.removeItem('is_edit');
        var is_edit=false
        sessionStorage.setItem('is_edit', is_edit);
        window.open("/v20/public/platform/#/community/village/cockpit/equipPoint?type=2")
        return false;
    });
    $('#adress1').click(function(){
        sessionStorage.removeItem('is_edit');
        var is_edit=false
        sessionStorage.setItem('is_edit', is_edit);
        window.open("/v20/public/platform/#/community/village/cockpit/equipPoint?type=1")
        return false;
    });
    $('#adress4').click(function(){
        console.log(11111);
        window.open("/v20/public/platform/#/community/village/cockpit/equipPoint?type=4")
        return false;
    });

</script>
<script>
    function bind_work_callback(repair_wid,advise_wid) {
        window.layer.closeAll();
        layer.msg('绑定成功，请点击下方【保存】按钮',{icon: 1,time:3000});
        $('.bind_id').html('<span title="在线报修已绑定1个工作人员，投诉建议已绑定1个工作人员">已绑定</span>');
        $("input[name='repair_wid']").val(repair_wid);
        $("input[name='advise_wid']").val(advise_wid);
    }

    function new_bind_work_callback(msg) {
        window.layer.closeAll();
        layer.msg(msg,{icon: 1,time:3000});
    }
</script>

<script>
    // 获取所有带有 class="link" 的 <a> 标签
    var links = document.querySelectorAll('.addEventClass');
    
    var input = document.createElement('input');
    var myform = document.getElementById('edit_form')
    input.type='hidden'
   

    // 为每个链接添加点击事件监听器
    links.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();  // 阻止链接默认行为
            var fragment = this.getAttribute('href').substr(1);
            input.name=fragment
            input.value=1
            myform.appendChild(input)
        });
    });

    function open_new_page(url,$path_name='') {
        var iframe = "{pigcms{$_GET['iframe']}";
        if($path_name=='new_v20'){
            window.top.addIframe('','',url);
        }else if (iframe && $path_name) {
            $path = "/village/village.iframe/"+$path_name;
            window.top.addIframe('','',$path);
        }
    }
</script>


<include file="Public:footer"/>