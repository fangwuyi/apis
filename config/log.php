<?php

return [
    // 日志记录方式，内置 file socket 支持扩展
    'type'        => 'File',
    // 日志保存目录
    'path'        =>  __DIR__.'/../logs',
    // 日志记录级别
    'level'       => [],
    'close' => true,//是否关闭日志写入记录
];
