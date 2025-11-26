<?php

$tmplParseString = M('Tmpl_parse_string')->select();
if ($tmplParseString) {
	$parseArrayString = array_column($tmplParseString, 'parse_str', 'mark_str');
	C('TMPL_PARSE_STRING', $parseArrayString);
}
return [
    'sys_conf' =>[
        'special_internal_card_id' => ['88888888888']
    ],
];