<include file="Public:header"/>
<style>
    .echarts_div div{
        width: 95% !important; display: inline-block !important;
    }
    .echarts_box{
        width: 100%; display: flex; flex-shrink: 0; flex-wrap: wrap;
        padding: 10px;
    }
    .div_span{
        margin-right: 10px;
    }
    #submit,#reset{
        padding: 3px 15px;
        border: 1px solid #4395ff;
    }
    #submit{
        background-color: #4395ff;
        color: #fff;
    }
    #reset{
        background-color: #fff;
        color: black;
    }
    #switchTab{
        margin-left: 50px;
        padding: 2px 3px;
    }

    .div_span .active{
        background-color: #1890FF;
        color: #FFFFFF;
        border: 1px solid #1890FF !important;
    }
    .echarts_box .tab_21{
        /*padding: 10px;*/
        max-height: 300px;
        overflow-y: auto;
        margin: 10px;
    }
    .echarts_box .tab_21 table,thead>tr>th,tbody>tr>td,tbody>tr{
        border: 1px solid !important;
        text-align: center;
        padding: 5px !important;
    }
    .echarts_box .tagDiv{
        width: 70px;
        display: inline-block;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    .echarts_box .u_colocr{
        color: #1890FF;
    }
    .tips0621{
        margin: 10px;color: #1890FF
    }
    .tips2{
        display: none;
    }
    .div_span .input-text{
        margin:10px;
    }
    .time_dox{
        display: none
    }
    .tips3{
        color: red;
    }

    /*.echarts_box .tab_21::-webkit-scrollbar {*/
        /*display: none; // 隐藏滚动条*/
    /*}*/

</style>
<div class="mainbox">
    <div style="margin: 10px">
<!--        <span class="div_span">-->
<!--             分类名称：-->
<!--        <input type="text" id="group_name" value=""/>-->
<!--        </span>-->
        <span class="div_span">
             <div class="layui-inline">
              <label class="layui-form-label" style="width: 88px">选择人员</label>
              <div class="layui-input-inline" style="line-height: 42px">
                <select id="personnel_id">
                    <option value="">请选择人员</option>
                      <volist name="list" id="vo">
                         <option value="{pigcms{$vo.wid}" <if condition="$vo.type eq 1">disabled="disabled" <else/>style="color: #4395ff;"</if> >
                          {pigcms{$vo.name}
                          </option>
                      </volist>
                </select>
              </div>
            </div>
        </span>
        <span class="div_span">
            选择时间：
        <select id="date_type" >
            <option value="3" <if condition="$date_type eq '3'">selected="selected"</if> >本日</option>
            <option value="2" <if condition="$date_type eq '2'">selected="selected"</if> >本月</option>
            <option value="1" <if condition="$date_type eq '1'">selected="selected"</if> >本年</option>
            <option value="4" >其它</option>
        </select>
        </span>

        <span class="div_span time_dox">
            <span style="margin-left:10px;">日期范围：</span>
            <input type="text" name="start_time" class="input-text" value="" onfocus="WdatePicker({isShowClear:false, minDate: '1900-01-01', readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="请选择开始日期"/>
            &nbsp;—&nbsp;
            <input type="text" name="end_time" class="input-text" value=""  onfocus="WdatePicker({isShowClear:false, minDate: '1900-01-01', readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="请选择结束日期"/>
        </span>

        <span class="div_span">
            <input type="button" value="查询" id="submit"/>
        </span>
        <span class="div_span">
            <input type="button" value="重置" id="reset"/>
        </span>
        <span class="div_span">
            <input type="button" value="切换表格" id="switchTab"/>
        </span>
    </div>
    <div class="row">
        <div class="am-margin-bottom">
            <div class="widget clearfix">
                <div class="widget-body clearfix">
                    <div class="tips0621">
                        <span class="tips1">1、展示该部门组织架构，可进行下拉选择部门工作人员进行统计，统计个人工单数据。</span>
                        <span class="tips2">2、工作人员字体为蓝色时，代表该工作人员已删除，已有的工单数据统计还是保留。</span>
                        <span class="tips3"></span>
                    </div>
                    <div class="echarts_box"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{pigcms{$static_public}echarts/echarts.min.js"></script>
<script src="{pigcms{$static_public}echarts/echarts-walden.js"></script>
<script>

    $(function () {
        var group_id='{pigcms{$group_id}';
        var data_type='{pigcms{$data_type}';

        get_simple(0);

        //初始化
        function get_simple(type){
            var loading = layer.msg('正在加载', {
                icon: 16,
                shade: [0.1, '#000'],
                time: false  // 取消自动关闭
            });
            var group_name,date_type = $('#date_type').val(),personnel_id = $('#personnel_id').val(),is_tab=0,start_time=$('input[name="start_time"]').val(),end_time=$('input[name="end_time"]').val();
            if($('#switchTab').hasClass("active")){
                is_tab=1;
            }
            if(type == 0 ){
                group_name = $('#group_name').val();
            }
            else{
                group_name = '';
                personnel_id='';
                start_time='';
                end_time='';
                $('#group_name').val(group_name);
                $('#personnel_id').val(personnel_id);
                $('input[name="start_time"]').val(start_time);
                $('input[name="end_time"]').val(end_time)
            }
            if(date_type == 4){
                if(!start_time || !end_time){
                    return layer.msg('请选择日期范围！');
                }
            }
            $.post("{pigcms{:U('get_work_order_simple')}",{
                group_id:group_id,
                group_name:group_name,
                date_type:date_type,
                data_type:data_type,
                personnel_id:personnel_id,
                is_tab:is_tab,
                start_time:start_time,
                end_time:end_time,
            },function(d){
                layer.close(loading);
                var rr=d.info,list=rr.data.list;
                if(Number(rr.status) == 0){
                   return layer.msg(rr.msg);
                }
                if(list < 1){
                    layer.msg('暂无数据');
                }
                $('.tips3').html(rr.data.cache_msg)
                if(Number(rr.data.is_tab) == 1){
                    get_table(list);
                }else{
                    var ss=add_viem(list);
                    if(ss){
                        get_echart(list);
                    }
                }
                return true;
            });
        }

        //渲染表格
        function get_table(data) {
            var str='';
            for (var i=0;i<data.length;i++) {
                var std='';
                for (var j=0;j<data[i].user.length;j++){
                    var uu='';
                    if(Number(data[i].user[j].is_del) == 1){
                        uu=' u_colocr';
                    }
                    std+='<tr>\n' +
                        '<td><div class="tagDiv '+uu+'" title="'+data[i].user[j].user_name+'">'+data[i].user[j].user_name+'</div></td>\n' +
                        '<td><div class="tagDiv">'+data[i].user[j].total_num+'</div></td>\n' +
                        '<td><div class="tagDiv">'+data[i].user[j].succ_num+'</div></td>\n' +
                        '<td><div class="tagDiv">'+data[i].user[j].delay_num+'</div></td>\n' +
                        '<td><div class="tagDiv">'+data[i].user[j].succ_rate+'</div></td>\n' +
                        '</tr>';
                }
                str+='<div class="tab_21"><table class="table table-striped table-bordered table-hover"><thead><th colspan="5">'+data[i].name+'</th><tr><th>姓名</th><th>工单数量</th><th>及时数</th><th>延迟数</th><th>及时率</th></tr></thead><tbody>'+std+'</tbody></table></div>';
            }
            $('.echarts_box').html(str);
            return true;
        }

        //追加页面
        function add_viem(data) {
            var str='';
            for (var i=0;i<data.length;i++) {
                str+='<div id="echarts_simple_'+(i+1)+'" class="echarts_div" style="height: 400px; width: 400px;"></div>'
            }
            $('.echarts_box').html(str);
            return true;
        }

        //渲染echarts
        function get_echart(data) {
            for (var i=0;i<data.length;i++) {
                var echart='echarts_'+data[i],id='echarts_simple_'+(i+1);
                echart = echarts.init(document.getElementById(id),'walden');
                echart.setOption({
                    title: {
                        text: data[i].name,
                        x:'center',
                        y: '74%'
                    },
                    series: data[i],
                    itemStyle: {
                        emphasis: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        },
                        normal:{
                            color:function(params) {
                                return data[i].bg_color[params.dataIndex]
                            }
                        }
                    }
                }, true);
                // echart.on('click', function (params) {
                // });
            }
        }

        //提交
        $('#submit').click(function () {
            get_simple(0);
        })

        //重置
        $('#reset').click(function () {
            get_simple(1);
        })

        //切换表格
        $('#switchTab').click(function () {
            var that=$(this),
                is_active=that.hasClass("active");
            if(is_active){
                that.removeClass("active");
                $('.tips2').hide()
            }else{
                that.addClass("active");
                $('.tips2').show()
            }
            get_simple(0);
        })

        //选择日期
        $("#date_type").change(function(){
            var selected=$(this).children('option:selected').val();
            if(selected == 4){
                $('.time_dox').show();
            }else{
                $('input[name="start_time"]').val('');
                $('input[name="end_time"]').val('')
                $('.time_dox').hide();
            }
        })

    })

</script>
<include file="Public:footer"/>