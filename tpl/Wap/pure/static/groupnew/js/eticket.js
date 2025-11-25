$(function () {
  var swiper = new Swiper(".qcode-swiper", {
    pagination: ".qcode-swiper .swiper-pagination",
    paginationClickable: ".qcode-swiper .swiper-pagination",
    nextButton: ".qcode-swiper .swiper-button-next",
    prevButton: ".qcode-swiper .swiper-button-prev",
    onInit: function () {
      $(
        ".qcode-swiper .swiper-pagination-bullet-active, .qcode-swiper .swiper-pagination-bullet"
      ).css("background", main_color);
    },
    onTransitionStart: function () {
      $(
        ".qcode-swiper .swiper-pagination-bullet-active,.qcode-swiper .swiper-pagination-bullet"
      ).css("background", main_color);
    },
  });
});
