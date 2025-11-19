$(document).ready(function () {
  var staffArr = common.getCache("store_staff", true);
  var ticket = common.getTicket();
  // 获取当前时间的毫秒级时间戳
  let timestamp = new Date().getTime();
  $("#store_qrcode").attr(
    "src",
    requestUrl +
      "Storestaff&a=store_qrcode&Device-Id=" +
      common.getDeviceId() +
      "&v20_ticket=" +
      ticket +
      "&ticket=" +
      ticket +
      "&t=" +
      timestamp
  );
});
