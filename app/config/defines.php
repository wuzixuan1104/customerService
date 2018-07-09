<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

defined ('SHOW_DEBUG_BACKTRACE')                 || define ('SHOW_DEBUG_BACKTRACE', TRUE);

defined ('FILE_READ_MODE')                       || define ('FILE_READ_MODE', 0644);
defined ('FILE_WRITE_MODE')                      || define ('FILE_WRITE_MODE', 0666);
defined ('DIR_READ_MODE')                        || define ('DIR_READ_MODE', 0755);
defined ('DIR_WRITE_MODE')                       || define ('DIR_WRITE_MODE', 0755);

defined ('FOPEN_READ')                           || define ('FOPEN_READ', 'rb');
defined ('FOPEN_READ_WRITE')                     || define ('FOPEN_READ_WRITE', 'r+b');
defined ('FOPEN_WRITE_CREATE_DESTRUCTIVE')       || define ('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined ('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE')  || define ('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined ('FOPEN_WRITE_CREATE')                   || define ('FOPEN_WRITE_CREATE', 'ab');
defined ('FOPEN_READ_WRITE_CREATE')              || define ('FOPEN_READ_WRITE_CREATE', 'a+b');
defined ('FOPEN_WRITE_CREATE_STRICT')            || define ('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined ('FOPEN_READ_WRITE_CREATE_STRICT')       || define ('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

defined ('EXIT_SUCCESS')                         || define ('EXIT_SUCCESS', 0); // no errors
defined ('EXIT_ERROR')                           || define ('EXIT_ERROR', 1); // generic error
defined ('EXIT_CONFIG')                          || define ('EXIT_CONFIG', 3); // configuration error
defined ('EXIT_UNKNOWN_FILE')                    || define ('EXIT_UNKNOWN_FILE', 4); // file not found
defined ('EXIT_UNKNOWN_CLASS')                   || define ('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined ('EXIT_UNKNOWN_METHOD')                  || define ('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined ('EXIT_USER_INPUT')                      || define ('EXIT_USER_INPUT', 7); // invalid user input
defined ('EXIT_DATABASE')                        || define ('EXIT_DATABASE', 8); // database error
defined ('EXIT__AUTO_MIN')                       || define ('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined ('EXIT__AUTO_MAX')                       || define ('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code
