<?php
/**
 * @author https://github.com/JaydenOK
 */

ini_set("display_errors", "On");//打开错误提示
ini_set("error_reporting", E_ALL);//显示所有错误

require 'bootstrap.php';

$manager = new module\server\WorkerTaskManager();
$manager->run($argv);