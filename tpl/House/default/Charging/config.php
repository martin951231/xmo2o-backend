<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
                .tips {
                    position: relative;
                    padding: 10px 10px 10px 15px;
                    color: rgba(0, 0, 0, 0.65);
                    line-height: 1.5;
                    border-radius: 2px;
                    background-color: #e6f7ff;
                    border: 1px solid #91d5ff;
                }
            </style>
            <div class="row">
                <div class="tips">
                    <span> 提示说明：智能充电桩-基本设置，后台的充电桩下的基本设置都是同一个页面，设置一次即可。</span>
                </div>
                <form id="edit_form" method="post" class="form-horizontal" action="{pigcms{:U('Charging/config')}" enctype="multipart/form-data">
                    <div class="tab-content">
                        <div class="tab-pane active" id="basicinfo">
                            <div class="tab-pane" id="txtstore">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="low_consume_time">最低消费时长：</label></label>
                                    <div style="width: 92%;margin-left: 8%">
                                        <if condition="$config['low_consume_time']">
                                            <input type="number" name="low_consume_time" class="input-text" value="{pigcms{$config.low_consume_time}"  autocomplete="new-password" style="background-color: #FFFFFF!important;" />
                                            <else/>
                                            <input type="number" name="low_consume_time" class="input-text" value=""  autocomplete="new-password" style="background-color: #FFFFFF!important;"   />
                                        </if>
                                        <span class="form_tips">分钟。不设置不进行退款。在该时间内停止充电将全额退款给用户</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane active" id="basicinfo">
                            <div class="tab-pane" id="txtstore">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="des_time_level">扣费时间间隔档位：</label></label>
                                    <div style="width: 92%;margin-left: 8%">
                                        <if condition="$config['des_time_level']">
                                            <input type="number" name="des_time_level" class="input-text" value="{pigcms{$config.des_time_level}"  autocomplete="new-password" style="background-color: #FFFFFF!important;" />
                                            <else/>
                                            <input type="number" name="des_time_level" class="input-text" value=""  autocomplete="new-password" style="background-color: #FFFFFF!important;"   />
                                        </if>
                                        <span class="form_tips">分钟。设置后，消费时长根据该档位进行可退款余额计算，例如设置30分钟为一档位，用户在充电30分钟内停止充电将根据剩余时长计算金额退款给用户（不足30分钟，按30分钟进行扣费）。</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane active" id="basicinfo">
                            <div class="tab-pane" id="txtstore">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="low_consume_power">最低消费电量：</label></label>
                                    <div style="width: 92%;margin-left: 8%">
                                        <if condition="$config['low_consume_power']">
                                            <input type="text" name="low_consume_power" class="input-text" value="{pigcms{$config.low_consume_power}"  autocomplete="new-password" style="background-color: #FFFFFF!important;" />
                                            <else/>
                                            <input type="text" name="low_consume_power" class="input-text" value=""  autocomplete="new-password" style="background-color: #FFFFFF!important;"   />
                                        </if>
                                        <span class="form_tips">度。不设置不进行退款，在该度数内停止充电将全额退款给用户</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane active" id="basicinfo">
                            <div class="tab-pane" id="txtstore">
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="des_power_level">扣费电量间隔档位：</label></label>
                                    <div style="width: 92%;margin-left: 8%">
                                        <if condition="$config['des_power_level']">
                                            <input type="text" name="des_power_level" class="input-text" value="{pigcms{$config.des_power_level}"  autocomplete="new-password" style="background-color: #FFFFFF!important;" />
                                            <else/>
                                            <input type="text" name="des_power_level" class="input-text" value=""  autocomplete="new-password" style="background-color: #FFFFFF!important;"   />
                                        </if>
                                        <span class="form_tips">度。设置后，消费电量根据该档位进行可退款余额计算，例如设置1度为一档位，用户在充电1度内停止充电将根据剩余电量计算金额退款给用户（不足1度，按1度进行扣费）。</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="clearfix form-actions">
                            <div class="col-md-offset-3 col-md-9">
                                <if condition="in_array(3,$house_session['menus'])">
                                    <button type="submit" class="btn btn-info">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        保存
                                    </button>
                                    <else/>
                                    <button type="button" class="btn btn-info disabled" disabled="disabled" style="cursor: not-allowed;">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        保存
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
<style>
    .modal-content{
        width:400px;
    }
    .modal-body{
        text-align:center;
        padding:20px 15px;
    }
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script>

</script>
<include file="Public:footer"/>
