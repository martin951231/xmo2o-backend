var pids = [], isLoading = false, sort = 1, sort_type = 1;
$(function(){
    $('.twoClass>ul li:last-child').hover(function(){
        $(this).css('border-color', main_color);
        $(this).find('span, .glyphicon-triangle-top').css({'color':main_color, 'border-color':main_color});
    }, function(){
        $(this).css('border-color', '');
        $(this).find('span, .glyphicon-triangle-top').css({'color':'','border-color':'' });
    })


    $('.lastClass dd').hover(function(){
        $(this).css('color', main_color);
        $(this).find('a').css('color', main_color);
    }, function(){
        $(this).css('color', '');
        if ($(this).attr('class').indexOf('active') == -1) {
            $(this).find('a').css('color', '');
        }
    })

    $('.classify ul li').hover(function(){
        $(this).css('color', main_color);
        $(this).find('a').css('color', main_color);
    },function(){
        if ($(this).attr('class').indexOf('active') == -1) {
            $(this).css('color', '')
            $(this).find('a').css('color', '');
        }
    })

    $('.awardLimit').click(function(){
        pids = [];
        $('input[type=checkbox]:checked').each(function(){
            pids.push($(this).val());
        });
        mallGoods(1);
    });
    
    $('.sort li').click(function(e){
        if ($(this).index() == 0) {
            $('.sort .active').removeClass('active');
            $(this).addClass('active');
            sort = 1;
            sort_type = 1
        } else if ($(this).index() == 1) {
            if ($(this).find('i').hasClass('active')) {
                if (sort_type == 1) {
                    sort_type = 2
                    $(this).find('i').eq(0).addClass('active');
                    $(this).find('i').eq(1).removeClass('active');
                } else {
                    sort_type = 1;
                    $(this).find('i').eq(1).addClass('active');
                    $(this).find('i').eq(0).removeClass('active');
                }
            } else {
                $('.sort .active').removeClass('active');
                $(this).find('i').eq(1).addClass('active');
                sort_type = 1;
            }
            
            sort = 2;
        } else if ($(this).index() == 2) {
            sort = 3;
            if ($(this).find('i').hasClass('active')) {
                if (sort_type == 1) {
                    sort_type = 2
                    $(this).find('i').eq(0).addClass('active');
                    $(this).find('i').eq(1).removeClass('active');
                } else {
                    sort_type = 1;
                    $(this).find('i').eq(1).addClass('active');
                    $(this).find('i').eq(0).removeClass('active');
                }
            } else {
                $('.sort .active').removeClass('active');
                $(this).find('i').eq(0).addClass('active');
                sort_type = 2;
            }
        }
        mallGoods(1);
        $('.sort li').find('i').css('color', '')
        $(this).find('i.active').css('color', main_color)
    });
    $('.Load').click(function(){
        mallGoods($(this).data('page'));
    });
});
mallGoods(1);
function mallGoods(now_page)
{
    $.post('/index.php?g=Mall&c=Index&a=mallGoods', {'cateid':cateid, 'catefid':catefid, 'page':now_page, 'sort':sort, 'sort_type':sort_type, 'pids':pids.join()}, function(result){
        if(result.total > 0){
            hasMorePage = now_page < result.total_page ? true : false;
            laytpl($('#mallListBoxTpl').html()).render(result.goods_list, function (html) {
                if (now_page > 1) {
                    $('.variousFoods').append(html);
                } else {
                    $('.variousFoods').html(html);
                }
            });
        } else {
            $('.variousFoods').html('');
        }
        if (result.next_page == 0) {
            $('.Load').hide();
        } else {
            $('.Load').show().data('page', result.next_page);
        }
    }, 'json');
    
}