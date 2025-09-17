<?php
2025-09-14 23:59:27 [D:\phpstudy_pro\WWW\xmo2o\cms\Lib\Model\Wisdom_qrcode_cateModel.class.php : 271]
/index.php?g=Index&c=Plan&pigcms_process_theadSafe=42b9b09472bc11fe897c4afbf382a84b
array (
  0 => '进来了271',
  1 => 'SELECT
                    * 
                FROM
                    (
                    SELECT
                        a.id,
                        b.index_id,
                        b.id as bid,
                        IF (b.add_time, b.add_time, 0 ) as add_time,
                        FROM_UNIXTIME( b.add_time, \'%Y-%m-%d %H:%i:%s\' ) AS add_time1
                    FROM
                        `pigcms_wisdom_qrcode_position_index` AS a
                        LEFT JOIN `pigcms_wisdom_qrcode_position_record` `b` ON `b`.`index_id` = `a`.`id` 
                    WHERE
                        a.status = 0 
                        AND a.del_time = 0 
                    ORDER BY
                        b.id DESC 
                    ) a 
                GROUP BY
                    a.id 
                HAVING
                    a.add_time <= (1757865567 - 120) 
                ORDER BY
                    a.index_id ASC',
  2 => 
  array (
  ),
)