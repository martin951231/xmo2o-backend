if ($(".handleBtn").size() > 0) {
  $(".container").css("padding-bottom", "60px");
}

$(".recive_confirm").click(function () {
  layer.open({
    content: "确定收货吗？",
    btn: ["确定", "取消"],
    skin: "footer",
    yes: function (index) {
      window.location.href = group_recive_confirm_url;
    },
  });
});

$(".order-pay").click(function () {
  var order_id = $(this).data("order_id");
  if (
    typeof wx !== "undefined" &&
    window.__wxjs_environment === "miniprogram"
  ) {
    wx.miniProgram.navigateTo({
      url:
        "/pages/pay/check?order_id=" + order_id + "&order_type=group",
      // url: '/pages/pay/index?order_id='+order_id+'&type=group',
      success: function (res) {},
    });
    return false;
  } else {
    location.href = pay_check;
  }
});

