<?php
/**
 * 程序引导文件
 */

define('PROJECT_DIR', dirname(dirname(__FILE__)));
define('MODULE_DIR', dirname(__FILE__));
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

require_once MODULE_DIR . DS . 'commons' . DS . 'Autoloader.php';
require_once MODULE_DIR . DS . 'commons' . DS . 'functions.php';
require_once MODULE_DIR . DS . 'vendor' . DS . 'autoload.php';

/**
 * todo 添加当前模块的命名空间
 */
$autoloader = Autoloader::getInstance();
$autoloader->addNamespace('module', MODULE_DIR);
$autoloader->register();