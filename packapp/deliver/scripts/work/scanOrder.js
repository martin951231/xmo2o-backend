//等待接单
const PENDING_STATUS = 1

//已接单
const RECEIVED_STATUS = 2;

//取货
const PICK_STATUS = 3;

//配送
const SEND_STATUS = 4;

//完成
const COMPLETE_STATUS = 5;

var deliverUser = common.getCache('deliver_user', true);
if (common.checkWeixin() == false || (deliverUser && !deliverUser.group) || (deliverUser && deliverUser.group != 1)) {
    $('#scan_div').remove();
} else {
    $('#scan_div').show();
}

function successAlert(msg, okfun, cancelfun) {
    setTimeout(() => {
        weui.alert('<p class="textP">' + msg + '！</p>', {
            buttons: [
                {
                    label: L_('确定'),
                    onClick: function () {
                        if (typeof cancelfun == 'function') {
                            cancelfun();
                        }
                        location.reload()
                    }
    
                },
                {
                    label: L_('继续扫码'),
                    type: 'primary',
                    onClick: function () {
                        if (typeof okfun == 'function') {
                            okfun();
                        }
                    }
                }
            ]
        });
    }, 300);

    
}


function grabOrder(supply_id, page = '') {
    common.http('Deliver&a=grab', { 'supply_id': supply_id }, function (data) {
        $(".supply_" + supply_id).remove();
        successAlert(L_('抢单成功'), function () {
            if (page) {
                $('#' + page).trigger('click');
            }
        })
    },function(res){
        successAlert(res.errorMsg, function () {
            if (page) {
                $('#' + page).trigger('click');
            }
        })
    });
}

function pickOrder(supply_id, page = '') {
    common.http('Deliver&a=pick', { 'supply_id': supply_id }, function (data) {
        $(".supply_" + supply_id).remove();
        successAlert(L_('取货成功'), function () {
            if (page) {
                $('#' + page).trigger('click');
            }
        })
    },function(res){
        successAlert(res.errorMsg, function () {
            if (page) {
                $('#' + page).trigger('click');
            }
        })
    });
}

function sendOrder(supply_id, page = '') {
    common.http('Deliver&a=send', { 'supply_id': supply_id }, function (data) {
        $(".supply_" + supply_id).remove();
        successAlert(L_('配送中'), function () {
            if (page) {
                $('#' + page).trigger('click');
            }
        })
    },function(res){
        successAlert(res.errorMsg, function () {
            if (page) {
                $('#' + page).trigger('click');
            }
        })
    });
}

function completeOrder(supply_id, page = '') {
    common.http('Deliver&a=complete', { 'supply_id': supply_id }, function (data) {
        $(".supply_" + supply_id).remove();
        successAlert(L_('配送已完成'), function () {
            if (page) {
                $('#' + page).trigger('click');
            }
        })
    },function(res){
        successAlert(res.errorMsg, function () {
            if (page) {
                $('#' + page).trigger('click');
            }
        })
    });
}

//配送员首页扫码收单
$('#indexScanOrder').on('click', function () {
    /*indexScanOrderResult('8531')
    return false*/
    common.scan('indexScanOrderResult');
})

function indexScanOrderResult(val) {
    common.http('Deliver&a=scanOrderCheck', {order_id: val}, function (data) {
        if (data.uid == 0) {
            //待接单
            grabOrder(data.supply_id,'indexScanOrder');
            return false
        } else {
            if (data.order_transfer == 1 && deliverUser.uid != data.uid) {
                successAlert('操作失败，订单已被别人处理，当前状态为“' + data.status_desc + '”',function(){$('#indexScanOrder').trigger('click')});
                return false;
            }
            
            if (deliverUser.uid == data.uid) {
                //登陆和接单是同一人,状态流转
                switch (data.status) {
                    case PENDING_STATUS:
                    case RECEIVED_STATUS:
                        setTimeout(() => {
                            weui.alert('<p class="textP">' + L_('当前订单为' + data.status_desc + '状态，您是要保持订单当前状态还是继续流传当前订单下一的状态？') + '！</p>', {
                                buttons: [
                                    {label: L_('保持状态')},
                                    {
                                        label: L_('流转状态'), type: 'primary', onClick: function () {
                                            pickOrder(data.supply_id, 'indexScanOrder');
                                        }
                                    }
                                ]
                            });
                        }, 300);
                        break;
                    case PICK_STATUS:
                        setTimeout(() => {
                            weui.alert('<p class="textP">' + L_('当前订单为' + data.status_desc + '状态，您是要保持订单当前状态还是继续流传当前订单下一的状态？') + '！</p>', {
                                buttons: [
                                    {label: L_('保持状态')},
                                    {
                                        label: L_('流转状态'), type: 'primary', onClick: function () {
                                            sendOrder(data.supply_id, 'indexScanOrder');
                                        }
                                    }
                                ]
                            });
                        }, 300);
                        break;
                    case SEND_STATUS:
                        setTimeout(() => {
                            weui.alert('<p class="textP">' + L_('当前订单为' + data.status_desc + '状态，您是要保持订单当前状态还是继续流传当前订单下一的状态？') + '！</p>', {
                                buttons: [
                                    {label: L_('保持状态')},
                                    {
                                        label: L_('流转状态'), type: 'primary', onClick: function () {
                                            completeOrder(data.supply_id, 'indexScanOrder');
                                        }
                                    }
                                ]
                            });
                        }, 300);
                        break;
                    default:
                        setTimeout(() => {
                            weui.alert('<p class="textP">' + L_('该订单为' + data.status_desc + '状态，无需扫码') + '！</p>', {
                                buttons: [{label: L_('知道了')}]
                            });
                        }, 300);
                        break;
                }

            } else {
                //登陆和接单非同一人
                successAlert('操作失败，订单已被别人处理，当前状态为“' + data.status_desc + '”','indexScanOrder');
                return false;
            }
        }
    }, function (res) {
        successAlert(res.errorMsg, function () {
            $('#indexScanOrder').trigger('click')
        });
        return false
    });
}


//取单页面扫码收单
$('#pickScanOrder').click(function () {
    /*var type = $('.Navigation .on').attr('data-type');
    pickScanOrderResult(8528,type)
    return false*/

    var type = $('.Navigation .on').attr('data-type');
    if (type == undefined || type == '') {
        motify.log('参数有误');
    } else {
        common.scan('pickScanOrderResult',type);
    }
});

function pickScanOrderResult(val, type) {
    common.http('Deliver&a=scanOrderCheck', {order_id: val}, function (data) {
        if (data.order_transfer != 1 && data.uid > 0 && data.uid != deliverUser.uid) {
            successAlert('操作失败，订单已被别人处理，当前状态为“' + data.status_desc + '”',function(){$('#pickScanOrder').trigger('click')});
            return false;
        }
        //流转
        var tag = '';
        if (type == 'pick') {
            tag = 'pick';
        } else if (type == 'send') {
            tag = 'send';
        } else if (type == 'my') {
            tag = 'complete';
        } else {
            return false;
        }

        common.http('Deliver&a=supplyTransfer', {order_id: data.order_id, tag: tag, supply_id: data.supply_id}, function (data) {
            successAlert(data,function(){$('#pickScanOrder').trigger('click')});
            return false;
        },function(data){
            successAlert(data.errorMsg,function(){$('#pickScanOrder').trigger('click')});
            return false
        })
    }, function (res) {
        successAlert(res.errorMsg, function () {
            $('#pickScanOrder').trigger('click')
        });
        return false
    })
}

//待抢单扫码
$('#grabScanOrder').on('click', function () {
    /*grabScanOrderResult('8527')
    return false*/
    common.scan('grabScanOrderResult');
})

function grabScanOrderResult(val){
    common.http('Deliver&a=scanOrderCheck', {order_id: val}, function (data) {
        if (data.order_transfer != 1 && data.uid > 0 && data.uid != deliverUser.uid) {
            successAlert('操作失败，订单已被别人处理，当前状态为“' + data.status_desc + '”',function(){$('#grabScanOrder').trigger('click')});
            return false;
        }

        if (data.status != 1) {
            if(data.uid > 0 && data.uid != deliverUser.uid){
                successAlert('操作失败，订单已被别人处理，当前状态为“' + data.status_desc + '”',function(){$('#grabScanOrder').trigger('click')});
            }else{
                successAlert('操作失败，当前状态为“' + data.status_desc + '”',function(){$('#grabScanOrder').trigger('click')});
            }
            return false;
        } else {
            grabOrder(data.supply_id,'grabScanOrder');
        }
    }, function (res) {
        successAlert(res.errorMsg, function () {
            $('#grabScanOrder').trigger('click')
        });
        return false
    })
}