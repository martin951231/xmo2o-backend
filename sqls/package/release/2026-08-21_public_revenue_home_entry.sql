-- 公共收益小程序首页入口配置
ALTER TABLE `pigcms_house_village_info`
  ADD COLUMN `public_revenue_home_show` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '小程序首页是否显示公共收益入口' AFTER `index_header_bg_image`,
  ADD COLUMN `public_revenue_home_title` varchar(30) NOT NULL DEFAULT '公共资源经营收益' COMMENT '小程序首页公共收益入口标题' AFTER `public_revenue_home_show`;

-- 兼容已执行过旧版公共收益 SQL 的环境，将菜单移动到“小区设置”下。
UPDATE `pigcms_house_menu_new`
SET `fid` = 1,
    `name` = '公共收益配置',
    `sort` = 99850,
    `show` = 1,
    `status` = 1,
    `type` = 1,
    `jump_type` = 3,
    `component` = 'publicRevenue',
    `path` = '/village/village.publicRevenue/publicRevenue',
    `is_hide` = 0
WHERE `component` = 'publicRevenue';

INSERT INTO `pigcms_house_menu_new`
  (`fid`,`name`,`module`,`action`,`select_module`,`select_action`,`choose_page`,`icon`,`sort`,`show`,`status`,`type`,`jump_type`,`jump_url`,`application_id`,`application_txt`,`component`,`path`,`is_hide`)
SELECT 1,'公共收益配置','','',' ',' ',' ','',99850,1,1,1,3,' ',0,' ','publicRevenue','/village/village.publicRevenue/publicRevenue',0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `pigcms_house_menu_new` WHERE `component` = 'publicRevenue'
);
