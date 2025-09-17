$(function(){
    // 选择规格
    $(document).on('click', '.spec-li', function(){
        var today_can_buy = $(this).data('today_can_buy');
        console.log('今日该用户限买数----', today_can_buy);
        if (today_can_buy != 0) {
            $(this).addClass("on").siblings().removeClass("on");
            show_select_txt($(this));
        } else {
            var count_num = $(this).data('count_num');
            var surplus = $(this).data('surplus');
            if (count_num<=0 || (count_num >0 && surplus >0)) {
                layer.open({
                    content: '该规格当天购买的数量已达上限'
                    ,skin: 'msg'
                    ,anim: 'up'
                    ,time: 2 //2秒后自动关闭
                });
            } else {
                layer.open({
                    content: '该规格库存不足'
                    ,skin: 'msg'
                    ,anim: 'up'
                    ,time: 2 //2秒后自动关闭
                });
            }
        }
    });



    //加减号的操作
    $(document).on('click', '.plus_group a', function() {
        var num = parseInt($('.plus_group input').val());
        var min_num = $(this).data('num');
        var once_min_msg = parseInt($('#once_min_msg').val());
        if (once_min_msg && min_num != once_min_msg) {
            min_num = once_min_msg;
        }
        // 取上限  取 每日购买最大数和每人购买最大数的最小值
        var max = 0;
        var once_max_msg = parseInt($('#once_max_msg').val());
        var once_max_day_msg = parseInt($('#once_max_day_msg').val());
        if (once_max_msg < once_max_day_msg && once_max_msg != 0) {
            max = once_max_msg;
        }
        if (once_max_day_msg < once_max_msg && once_max_day_msg != 0) {
            max = once_max_day_msg;
        }
        if (!max && !once_max_day_msg && once_max_msg > 0) {
            max = once_max_msg;
        }
        if (!max && !once_max_msg && once_max_day_msg > 0) {
            max = once_max_day_msg;
        }
        var today_can_buy = parseInt($('#today_can_buy').val());
        if (max > 0 && today_can_buy > 0 && max > today_can_buy) {
            max = today_can_buy;
        } else if(max==0 && today_can_buy > 0) {
            max = today_can_buy;
        }
        if (!min_num) {
            min_num = 1;
        }
        console.log('today_can_buy-------------', today_can_buy);
        console.log('max--------------', max);
        console.log('num--------------', num);
        console.log('按钮', $(this).attr('class'));
        if ($(this).attr('class') == 'jia') {
            if (max > 0 && num < max) {
                num ++;
                if (num >= max) {
                    layer.open({
                        content: '您当前最多购买此数量'
                        ,skin: 'msg'
                        ,anim: 'up'
                        ,time: 2 //2秒后自动关闭
                    });
                    $(".plus_group a.jia").css("color","#bfbfbf");
                } else {
                    $(".plus_group a.jia").css("color","#333333");
                }
            } else if(max <= 0) {
                num ++;
            } else if ($(".plus_group a.jia").css("color") == 'rgb(51, 51, 51)'){
                layer.open({
                    content: '您当前最多购买此数量'
                    ,skin: 'msg'
                    ,anim: 'up'
                    ,time: 2 //2秒后自动关闭
                });
                $(".plus_group a.jia").css("color","#bfbfbf");
            }
        } else if (num > min_num) {
            num --;
            if (max > 0 && num < max) {
                $(".plus_group a.jia").css("color","#333333");
            }
        }
        if (num < min_num) {
            num = min_num;
        }
        if (num > min_num) {
            $(".plus_group a.jian").css("color","#333333");
        }
        if (num < min_num + 1) {
            $(".plus_group a.jian").css("color","#bfbfbf");
        }
        $('.plus_group input').val(num);
        buy_num = num;
    });

    // 选择规格时候的数据变化
    function show_select_txt(index)
    {
        var specifications_id = index.data('id');
        buy_spec_id = specifications_id;
        var json_msg = $.parseJSON(json_info);
        var info = json_msg[specifications_id];
        var price = info['price'];
        var old_price = info['old_price'];
        var count_num = info['count_num'];
        var once_max = info['once_max'];
        var once_max_day = info['once_max_day'];
        var once_min = info['once_min'];
        var sale_count = info['sale_count'];
        var today_can_buy = info['today_can_buy'];
        console.log('today_can_buy', today_can_buy);
        $('#show_format_price').text(price);

        if (count_num <= 0) {
            $('#show_stock_num').html('库存充足')
        } else {
            var surplus = count_num - sale_count;
            if(surplus<=0){
                today_can_buy = 0;
                $('#show_stock_num').html('库存为0');
            }else {
                $('#show_stock_num').html('剩余'+ surplus + '份');
            }
            if (today_can_buy > surplus || (today_can_buy==-1 && surplus > 0)) {
                // 今日可购买数小于库存，可购买数改为库存
                today_can_buy = surplus;
            }
        }
        $('#old_price').html(old_price);
        $('#once_min').html(once_min + '份起购');
        $('.plus_group input').val(once_min);
        $('#once_min_msg').val(once_min);
        $('#once_max_msg').val(once_max);
        $('#once_max_day_msg').val(once_max_day);
        $('#today_can_buy').val(today_can_buy);
        $('#today_can_buy_now').html('当前可购买'+today_can_buy+'份');
        $(".plus_group a.jian").css("color","#bfbfbf");
        $(".plus_group a.jia").css("color","#333333");
        buy_num = once_min;
    }

    // 点击立即购买弹出 规格菜单
    $(document).on('click', '.buy-info', function(){
        if ($(".Speci").is(":hidden")) {
            $(".Speci").slideDown();
            $(".mask, .one_btn").show();
            $('.one_btn').data('type', $(this).data('type'));
        }
    });
    //关闭选择的商品的规格窗口
    $(".del").click(function(){
        $(this).parents(".Speci").slideUp();
        $(".mask").hide();
    });

    //关闭选择的商品的规格窗口
    $(".Speci .one_btn").click(function(){
        console.log('跳转路径-----------', group_buy);
        group_buy += '&specifications_id=' + buy_spec_id + '&num=' + buy_num;
        window.location.href = group_buy;
    });

    // 立即购买
    $(document).on('click', '.buy-now', function(){
        window.location.href = group_now;
    });
    // 立即预定
    $(document).on('click', '.buy-hotel', function(){
        window.location.href = buy_hotel;
    });
    // 立即预约
    $(document).on('click', '.buy-appoint', function(){
        window.location.href = buy_appoint;
    });
    // 地址导航
    $(document).on('click', '.group_map', function(){
        window.location.href = group_map;
    });

    /*页面点击事件*/
    $(document).on('click','.link-url',function(){
        window.location.href = $(this).data('url');
    });

});