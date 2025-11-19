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
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div class="" style="margin-top: 5px;">
                        <div>注意事项</div>
                        <div style="color: green;">1.设置的开始时间即为费用实际生效时间，例如设置了2020年1月开始生效，那么对应日期之前如果没有其他标准即不产生费用；</div>
                    </div>
                    <form  class="form-horizontal" method="post" onSubmit="return check_submit()">
                        <div class="tab-content">
                            <div id="basicinfo" class="tab-pane active">
                                <div class="form-group">
                                    <label class="col-sm-2"><label for="name">名称</label></label>
                                    <input class="col-sm-2" size="20" name="name" id="name" type="text" value="{pigcms{$list['name']}"/>
                                </div>

                                <volist name="list['date_month']" id="vo">
                                    <div class="form-group">
                                        <label class="col-sm-2"><label for="presented_property_month_num">请选择开始月份</label></label>
                                        <input type="text" name="date_month" class="input-text date_month col-sm-2" value="{pigcms{$vo.date_month}"  style="height:31px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM'})" placeholder="请选择开始月份"/>
                                        <label class="col-sm-1"><label for="name">单价</label></label>
                                        <input class="col-sm-2 price" size="20" name="price" type="text" value="{pigcms{$vo.price}"/> <label class="col-sm-2"><label for="name">元/平方米/单价</label></label>
                                    </div>
                                </volist>

                                <div class="form-group" style="margin-left: 135px;" id="insert_before">
                                <img src="{pigcms{$static_public}images/add.png" onclick="add_price()" style="height: 60px;width: 60px">
                                <img src="{pigcms{$static_public}images/tishi.png" style="height: 25px;width: 25px;margin-left: 10px" title="添加单价后，需设置单价对应的时间，按月份设置，若物业费收费单价有变化，可再设置一个对应时间的单价！">
                            </div>

                                <div class="form-group">
                                    <label class="col-sm-2"><label for="status">状态</label></label>
                                    <label style="padding-left:0px;padding-right:20px;margin-top: 5px"><input type="radio" <if condition='$list["status"] eq 1'>checked="checked"</if> checked="" class="ace" value="1" name="status"><span style="z-index: 1" class="lbl">开启</span></label>
                                    <label style="padding-left:0px;"><input type="radio" <if condition='$list["status"] eq 2'>checked="checked"</if>  class="ace" value="0" name="status"><span style="z-index: 1" class="lbl">关闭</span></label>
                                </div>

                            </div>
                        </div>
                        <div class="space"></div>
                        <input  name="id" id="id" type="hidden"  value="{pigcms{$list['id']}"/>
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
    function add_price() {
        var html = '<div class="form-group">\n' +
            '                                    <label class="col-sm-2"><label for="presented_property_month_num">请选择开始月份</label></label>\n' +
            '                                    <input type="text" name="date_month" class="input-text date_month col-sm-2" value=""  style="height:31px" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:\'yyyy-MM\'})" placeholder="请选择开始月份"/>\n' +
            '                                    <label class="col-sm-1"><label for="name">单价</label></label>\n' +
            '                                    <input class="col-sm-2 price" size="20" name="price" type="text" value=""/> <label class="col-sm-2"><label for="name">元/平方米/单价</label></label>\n' +
            '                                    <img src="{pigcms{$static_public}images/jian.png" class="del_price" style="margin-top:8px;height: 20px;width: 20px;">\n' +
            '                                </div>';
        $(html).insertBefore('#insert_before');
    }

    $(function () {
        $(".del_price").live('click',function () {
            $(this).parent().remove();
        });
    });
    function check_submit(){
        /*if(!$('select[name="property_month_num"]').val()){
            if(!$('input[name="diy_property_month_num"]').val()){
                alert('物业缴费周期不能为空！');
                return false;
            }
        }*/
        var length = $('.price').length;
        var data = new Array();
        for(var i = 0; i < length;i++){
            if(i > 0){
                var starttime = $('.date_month').eq(i).val();
                var endtime = $('.date_month').eq(i-1).val()

                if(new Date(Date.parse(starttime)) <= new Date(Date.parse(endtime))){
                    alert('后添加的收费标准时间不可小于或等于前面添加的时间');
                    return false;
                }
            }
            data.push({price:$('.price').eq(i).val(),date_month:$('.date_month').eq(i).val()});
        }
        var params = {
            'name'	: $('#name').val(),
            'info'	: data,
            'status':$('input:radio:checked').val(),
            'id':$('#id').val()
        };
        $.post("{pigcms{:U('Unit/propertyfee_edit')}", params, function(data) {
            //data = JSON.parse(data);
            console.log(data)
            if(data.status == 1){
                layer.open({
                    content: '操作成功！',
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        parent.location.reload();
                    }
                })
            }
        });
        return false;
    }

    $('select').change(function(){
        if($(this).val() == 0){
            $(this).next('label').show();
        }else{
            $(this).next('label').hide();
        }
    });

    $('input[name="diy_type"]').each(function(i){
        $(this).click(function(){
            $('.diy_property_content').each(function(){
                $(this).hide();
            });
            $('.diy_property_content').eq(i).show();
        });

    });
</script>

<include file="Public:footer"/>