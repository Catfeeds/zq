<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/** 
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 *
 * See http://code.google.com/p/minify/wiki/CustomSource for other ideas
 **/

return array(
    'js'  => array(
    	'//Public/Plugs/jquery-1.8.0.min.js',
    	'//Public/Plugs/jshow.utils.js',
    	'//Public/Plugs/common.js',
    	'//Public/Plugs/jquery.validate.simple.js',
    	'//Public/Plugs/jquery.lazyload.min.js',
    	'//Public/Plugs/bootstrap/js/bootstrap_all.js',
    	'//Public/Plugs/bootstrap/js/modal.manager.plugin1.0.js',
    ),
    'css' => array(
    	'//Public/Home/css/reset.css',
        '//Public/Plugs/bootstrap/css/bootstrap.css',
    	'//Public/Home/css/font-awesome.css',
    	'//Public/Plugs/jquery.validate.simple.css',
        '//Public/Home/css/common.css',
    ),
);