<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-tablet"></i>
                <a href="{pigcms{:U('equipment/equipment_list')}">智能充电桩管理</a>
            </li>
            <li class="active">添加月卡</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <form  class="form-horizontal" method="post" onSubmit="return check_submit()"  action="{pigcms{:U('month_card_add')}">
                        <div class="tab-content">
                            <input  type="hidden" name='id' value="{pigcms{$_GET['id']}"/>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="card_name"><span style="color: red">*</span>月卡名称</label></label>
                                <input class="col-sm-2" size="20" name="card_name" id="card_name" type="text" placeholder="请输入月卡名称" value="{pigcms{$info['card_name']}"/>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="card_des">描述</label></label>
                                <input class="col-sm-2" size="20" name="card_des" id="card_des" placeholder="请输入月卡描述" type="text" value="{pigcms{$info['card_des']}"/>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="price"><span style="color: red">*</span>月卡价格</label></label>
                                <input class="col-sm-2" size="20" name="price" id="price" placeholder="请输入月卡价格" type="text" value="{pigcms{$info['price']}"/>
                                <label class="col-sm-1"><label for="price">单位：元</label></label>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="use_month"><span style="color: red">*</span>月卡使用月数</label></label>
                                <input class="col-sm-2" size="20" name="use_month" id="use_month" placeholder="请输入月卡使用月数" type="text" value="{pigcms{$info['use_month']}"/>
                                <label class="col-sm-1"><label for="price">单位：月</label></label>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="all_num"><span style="color: red">*</span>最多使用次数</label></label>
                                <input class="col-sm-2" size="20" name="all_num" id="all_num" placeholder="请输入最多使用次数" type="text" value="{pigcms{$info['all_num']}"/>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="max_num"><span style="color: red">*</span>每天最多使用次数</label></label>
                                <input class="col-sm-2" size="20" name="max_num" id="max_num" placeholder="请输入每天最多使用次数" type="text" value="{pigcms{$info['max_num']}"/>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="per_price"><span style="color: red">*</span>每次充电费用</label></label>
                                <input class="col-sm-2" size="20" name="per_price" id="per_price" placeholder="请输入每次充电费用" type="text" value="{pigcms{$info['per_price']}"/>
                                <label class="col-sm-1"><label for="price">单位：元</label></label>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-2"><label for="socket_num">状态</label></label>
                                <div class="radio">
                                    <label style="padding-left:0px;padding-right:20px;"><input name="status" value="1" type="radio" checked="checked" class="ace" <if condition="$info.status eq 1">checked</if>/><span class="lbl" style="z-index: 1">正常</span></label>
                                    <label style="padding-left:0px;"><input name="status" value="2" type="radio" class="ace" <if condition="$info.status eq 2">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
                                </div>
                            </div>

                        </div>
                        <div class="space"></div>
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
</div>
<script type="text/javascript">
    function check_submit(){
        if($('#card_name').val() == 0 || $('#price').val()=='' || $('#use_month').val() == 0 || $('#all_num').val()=='' || $('#max_num').val() == 0 || $('#per_price').val()==''){
            alert('请完善必填参数');
            return false;
        }
    }
</script>

<include file="Public:footer"/>