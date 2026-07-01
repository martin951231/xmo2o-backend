<?php

// Hidden one-off export endpoint. Keep the file name and token private.
$token = 'K6p9mV2xQ4t7A1c';
if (!isset($_GET['token']) || !hash_equals($token, (string)$_GET['token'])) {
    http_response_code(404);
    exit('Not Found');
}

$dbConfigPath = __DIR__ . '/conf/db.php';
if (!is_file($dbConfigPath)) {
    $dbConfigPath = dirname(__DIR__) . '/conf/db.php';
}
if (!is_file($dbConfigPath)) {
    http_response_code(500);
    exit('DB config not found');
}

$db = require $dbConfigPath;
$port = isset($db['DB_PORT']) && $db['DB_PORT'] ? (int)$db['DB_PORT'] : 3306;
$mysqli = new mysqli($db['DB_HOST'], $db['DB_USER'], $db['DB_PWD'], $db['DB_NAME'], $port);
if ($mysqli->connect_error) {
    http_response_code(500);
    exit('DB connect failed');
}
$mysqli->set_charset('utf8');
$prefix = $db['DB_PREFIX'];

function va_fetch_all(mysqli $mysqli, $sql)
{
    $res = $mysqli->query($sql);
    if (!$res) {
        throw new RuntimeException($mysqli->error);
    }
    $rows = array();
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $res->free();
    return $rows;
}

function va_list_count($value)
{
    if (empty($value)) {
        return 0;
    }
    if (is_array($value)) {
        $count = 0;
        foreach ($value as $item) {
            if (is_array($item)) {
                $hasValue = false;
                foreach ($item as $v) {
                    if (trim((string)$v) !== '') {
                        $hasValue = true;
                        break;
                    }
                }
                if ($hasValue) {
                    $count++;
                }
            } elseif (trim((string)$item) !== '') {
                $count++;
            }
        }
        return $count;
    }
    $text = trim((string)$value);
    if ($text === '') {
        return 0;
    }
    $json = json_decode($text, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        return va_list_count($json);
    }
    return 1;
}

function va_attachment_file_count(array $fieldValue)
{
    $fileListCount = isset($fieldValue['file_list']) ? va_list_count($fieldValue['file_list']) : 0;
    $filesCount = isset($fieldValue['files']) ? va_list_count($fieldValue['files']) : 0;
    return max($fileListCount, $filesCount);
}

function va_cell($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

try {
    $villages = va_fetch_all(
        $mysqli,
        "SELECT village_id, village_name, property_id
         FROM {$prefix}house_village
         ORDER BY property_id ASC, village_id ASC"
    );

    $fields = va_fetch_all(
        $mysqli,
        "SELECT property_id, `key`, title, type, sort
         FROM {$prefix}house_village_data_config
         WHERE village_id=0
           AND is_display=1
           AND is_close=0
           AND type IN (5,6)
         ORDER BY property_id ASC, sort DESC, `key` ASC"
    );

    $fieldsByProperty = array();
    foreach ($fields as $field) {
        $propertyId = (int)$field['property_id'];
        $key = trim((string)$field['key']);
        if ($propertyId <= 0 || $key === '') {
            continue;
        }
        if (!isset($fieldsByProperty[$propertyId])) {
            $fieldsByProperty[$propertyId] = array();
        }
        $fieldsByProperty[$propertyId][] = array(
            'key' => $key,
            'title' => (string)$field['title'],
            'type' => (int)$field['type'],
        );
    }

    $rows = array();
    foreach ($villages as $village) {
        $villageId = (int)$village['village_id'];
        $propertyId = (int)$village['property_id'];
        if (empty($fieldsByProperty[$propertyId])) {
            continue;
        }

        $bindRows = va_fetch_all(
            $mysqli,
            "SELECT pigcms_id, authentication_field
             FROM {$prefix}house_village_user_bind
             WHERE village_id={$villageId}
               AND vacancy_id>0
               AND status=1
               AND type IN (0,3)
               AND (uid>0 OR phone<>'' OR name<>'')"
        );

        $stats = array();
        foreach ($fieldsByProperty[$propertyId] as $field) {
            $stats[$field['key']] = array(
                'uploaded_bind_count' => 0,
                'uploaded_file_count' => 0,
            );
        }

        foreach ($bindRows as $bind) {
            if (empty($bind['authentication_field'])) {
                continue;
            }
            $auth = @unserialize($bind['authentication_field']);
            if (!is_array($auth)) {
                continue;
            }
            foreach ($fieldsByProperty[$propertyId] as $field) {
                $key = $field['key'];
                if (!isset($auth[$key]) || !is_array($auth[$key])) {
                    continue;
                }
                $fileCount = va_attachment_file_count($auth[$key]);
                if ($fileCount > 0) {
                    $stats[$key]['uploaded_bind_count']++;
                    $stats[$key]['uploaded_file_count'] += $fileCount;
                }
            }
        }

        foreach ($fieldsByProperty[$propertyId] as $field) {
            $rows[] = array(
                'property_id' => $propertyId,
                'village_id' => $villageId,
                'village_name' => $village['village_name'],
                'attachment_title' => $field['title'],
                'attachment_key' => $field['key'],
                'attachment_type' => $field['type'],
                'eligible_owner_bind_count' => count($bindRows),
                'uploaded_bind_count' => $stats[$field['key']]['uploaded_bind_count'],
                'uploaded_file_count' => $stats[$field['key']]['uploaded_file_count'],
            );
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    exit('Export failed');
}

$fileName = 'village_attachment_stats_' . date('Ymd_His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo "\xEF\xBB\xBF";
?>
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 6px 8px; mso-number-format:"\@"; }
        th { background: #d9eaf7; font-weight: bold; }
    </style>
</head>
<body>
<table>
    <thead>
    <tr>
        <th>物业ID</th>
        <th>小区ID</th>
        <th>小区名称</th>
        <th>附件名称</th>
        <th>附件key</th>
        <th>附件类型</th>
        <th>可统计业主绑定数</th>
        <th>上传绑定数</th>
        <th>上传文件数</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?php echo va_cell($row['property_id']); ?></td>
            <td><?php echo va_cell($row['village_id']); ?></td>
            <td><?php echo va_cell($row['village_name']); ?></td>
            <td><?php echo va_cell($row['attachment_title']); ?></td>
            <td><?php echo va_cell($row['attachment_key']); ?></td>
            <td><?php echo va_cell($row['attachment_type']); ?></td>
            <td><?php echo va_cell($row['eligible_owner_bind_count']); ?></td>
            <td><?php echo va_cell($row['uploaded_bind_count']); ?></td>
            <td><?php echo va_cell($row['uploaded_file_count']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
