<?php
2025-09-15 23:59:51 [D:\phpstudy_pro\WWW\xmo2o\cms\Lib\Model\House_new_repair_cateModel.class.php : 150]
/index.php?g=Index&c=Plan&pigcms_process_theadSafe=d978e7bbc55f24e362155cf8cb0bc36f
array (
  0 => '指定工作人员接单===150',
  1 => 'SELECT a.village_id,a.property_id,a.is_grab_order,a.grab_order_time,b.order_id,b.cat_fid,b.cat_id,b.event_status,b.add_time,c.wid FROM `pigcms_house_village_info` as a left join pigcms_house_new_repair_works_order as b ON a.village_id = b.village_id  left join pigcms_house_new_repair_cate_bind_worker as c ON c.cate_id=b.cat_id and c.village_id = a.village_id WHERE ( a.is_grab_order = 1 ) AND ( a.grab_order_time > 0 ) AND ( b.event_status = 15 ) AND ( b.cat_id > 0 ) AND ( c.wid > 0 ) AND ( (1757951991 - b.add_time) >= (a.grab_order_time * 60 * 60) ) ORDER BY b.order_id asc LIMIT 0,20  ',
  2 => 
  array (
  ),
)