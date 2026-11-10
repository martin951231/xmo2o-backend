-- 公共收益装修：小程序首页入口背景、详情页/季度详情页共用背景
ALTER TABLE `pigcms_house_village_info`
  ADD COLUMN `public_revenue_home_bg_color` varchar(20) NOT NULL DEFAULT '#FFFFFF' COMMENT '小程序首页公共收益入口背景色' AFTER `public_revenue_home_title`,
  ADD COLUMN `public_revenue_page_bg_color` varchar(20) NOT NULL DEFAULT '#F3F4F6' COMMENT '公共收益详情页背景色' AFTER `public_revenue_home_bg_color`,
  ADD COLUMN `public_revenue_page_bg_image` varchar(255) NOT NULL DEFAULT '' COMMENT '公共收益详情页背景图' AFTER `public_revenue_page_bg_color`;
