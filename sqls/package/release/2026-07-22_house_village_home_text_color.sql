ALTER TABLE `pigcms_house_village_info` 
    ADD COLUMN `switch_village_color` varchar(7) NOT NULL DEFAULT '#FFFFFF' COMMENT '首页切换小区按钮字体颜色',
    ADD COLUMN `index_title_color` varchar(7) NOT NULL DEFAULT '#000000' COMMENT '首页标题字体颜色';
