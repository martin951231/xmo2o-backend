<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
    .page-content {
        background-color: #fff;
        position: relative;
        margin: 0;
        padding: 0px 20px;
    }
</style>
<!-- 内容头部 -->
<!-- 内容头部 -->
<div class="page-content">
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
            #orderCheckControl a:hover,#orderCheckControl a:focus {
                color: #fff;
            }
            #orderCheckControl .check_control_p li .xdel{    background: red;border-radius: 7px;color: #fff;margin-left: 10px;}
            #orderCheckControl .check_control_p li img{width: 50px;}
            #orderCheckControl .check_control_p li span{display: inline-block; margin: 1px 20px;}
            #orderCheckControl .check_control_p li span strong{color:red;}
            #orderCheckControl .check_control_p li  select{width: 500px;}
            #orderCheckControl .check_control_p .xline{ width: 1px;height: 40px;background: #4e4e4e;border: 0;margin-left: 25px; }
        </style>
        <div class="row">
            <form class="form-horizontal" method="post" onSubmit="return saveOrderCheckControl()">
                <div class="col-xs-12" id="orderCheckControl">

                    <div class="form-group" style="margin-left: 10px;">
                        <p>提示说明</p>
                        <div>
                            <p>修改物业服务时间审核流程从上往下进行设置，最多可以设置2人进行审核。审核流程可根据当前角色减少申请流程（只有设置的审批人发起修改物业服务时间会减少审核流程）。</p>
                            <div class="tips" style="margin-left: 20px;">
                                <p>例：收银台客服发起手工调整申请流程→客服主管确认审核</p>
                            </div>
                        </div>
                    </div>
                    <div class="form-group service_time_check_open" style="margin-left: 100px;">
                        <label class="col-sm-3" style="width: 30%;"><label for="service_time_check_open">是否开启物业服务时间审核：</label></label>
                        <label style="padding-left:0px;padding-right:20px;margin-top: 6px"><input name="service_time_check_open" value="0" type="radio" class="ace " <if condition="$service_time_check_open eq 0">checked </if> <if condition="$service_time_check_open eq 1 AND !empty($applyhas)"> disabled </if> /><span class="lbl" style="z-index: 1"> 关闭 </span></label>
                        <label style="padding-left:0px;margin-top: 6px"><input name="service_time_check_open" value="1" type="radio" class="ace" <if condition="$service_time_check_open eq 1">checked </if>  <if condition="$service_time_check_open eq 1 AND !empty($applyhas)"> disabled </if> /><span class="lbl" style="z-index: 1"> 开启 </span></label>
                        <span class="form_tips" style="color:red;"><if condition="!empty($applyhas)"> 有在审核的流程未结束（{pigcms{$applyhas.msg}），不可改动相关设置 </if></span>
                    </div>
                    <div class="form-group" style="margin-left: 30px;">
                        <label style="width: 25%;">
                            <if condition="empty($applyhas)">
                            <a style="margin-left: 80px" class="ant-btn ant-btn-primary add_check_control" href="javascript:;" onclick="addCheckControl()" <if condition="$order_check_level_num egt 2"> style="display:none;" </if> > 新增审批人</a>
                            </if>
                        </label>
                        <div class="check_control_p" style="margin: 35px 0px 35px 50px;">
                            <ul>
                                <li class="check_people p_0"><img src="/static/images/cancelordercheckauth.png" ><span><strong style="color:red;">*</strong>审批人</span><select name="check_level[]" <if condition="!empty($applyhas)"> disabled </if> ><option value="0" >请选择</option>
                                        <volist name="worker_list" id="vo">
                                            <option value="{pigcms{$vo.wid}" <if condition="$check_level0_wid gt 0 AND $check_level0_wid eq $vo['wid']">selected </if> > {pigcms{$vo.department_str}{pigcms{$vo.name}</option>
                                        </volist>
                                    </select>
                                    </span><input type="hidden" class="check_level_v" value="0"></li>

                                <if condition="!empty($order_check_level)">
                                    <volist name="order_check_level" id="vv">
                                        <li class="xline"></li>
                                        <li class="check_people check_people_add p_{pigcms{$vv.level_v}"><img src="/static/images/cancelordercheckauth.png" ><span><strong style="color:red;">*</strong>审批人</span><select name="check_level[]" <if condition="!empty($applyhas)"> disabled </if> ><option value="0" >请选择</option>
                                                <volist name="worker_list" id="vo">
                                                    <option value="{pigcms{$vo.wid}" <if condition="$vv['level_wid'] gt 0 AND $vv['level_wid'] eq $vo['wid']">selected </if> > {pigcms{$vo.department_str}{pigcms{$vo.name}</option>
                                                </volist>
                                            </select>
                                            </span><input type="hidden" class="check_level_v" value="{pigcms{$vv.level_v}">
                                            <if condition="empty($applyhas)">
                                            <a href="javascript:;" class="ant-btn xdel" onclick="delCheckControl(this,{pigcms{$vv.level_v},{pigcms{$vv.level_wid})"> 删除 </a>
                                            </if>
                                        </li>
                                    </volist>
                                </if>
                            </ul>
                        </div>
                    </div>
                    <div class="clearfix form-actions">
                        <div class="col-md-offset-3 col-md-9">
                            <button class="btn btn-info" type="submit">
                                <i class="ace-icon fa fa-check bigger-110"></i>
                                保存
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    var worker_list={pigcms{$worker_list_json};
    function addCheckControl(){
        var checkNum=$('.check_control_p .check_people').size();
        if(checkNum>=2){
            layer.alert('您已经添加2个人了，不可在添加审批人员！');
            return false;
        }
        var checkTmp='<li class="xline"></li><li class="check_people check_people_add p_'+(checkNum)+'"><img src="/static/images/cancelordercheckauth.png" style="width: 50px;"><span><strong>*</strong>审批人</span><select name="check_level[]"><option value="0">请选择</option>';
        if(worker_list && worker_list.length>0){
            for(var ii=0;ii<worker_list.length;ii++){
                checkTmp+='<option value="'+worker_list[ii].wid+'">'+worker_list[ii].department_str+worker_list[ii].name+'</option>';
            }
        }
        checkTmp +='</select> <input type="hidden" class="check_level_v" value="'+(checkNum)+'"> <a href="javascript:;" class="ant-btn xdel" onclick="delCheckControl(this)"> 删除 </a></li>';
        $('.check_control_p ul').append(checkTmp);
        if(checkNum>=1){
            $('.add_check_control').hide();
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
        postArr.order_check_open=$('#orderCheckControl .service_time_check_open input:checked').val();
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
            check_level.push({'level_v':level_v,'level_wid':check_level_wid});
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
        postArr.xtype='service_time_check';
        isposting=true;
        $.post("{pigcms{:U('User/property_service_time_set')}",postArr,function(result){
            isposting=false;
            if(result.status == 1){
                layer.alert(result.info);
            }else{
                layer.alert(result.info);
            }
        })
        return false;
    }
</script>

<include file="Public:footer"/>