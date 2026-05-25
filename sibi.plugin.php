<?php
/**
 * Plugin Name: SIBI
 * Plugin URI: https://github.com/dicarve
 * Description: Integrasi data bibliografi dengan data SIBI
 * Version: 0.0.1
 * Author: Ari Nugraha
 * Author URI: https://github.com/dicarve
 */

// get plugin instance
$plugin = \SLiMS\Plugins::getInstance();

// registering menus
$plugin->registerMenu('bibliography', 'SIBI', __DIR__ . '/index.php');
