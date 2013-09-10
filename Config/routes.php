<?php
Router::connect('/', array('controller' => 'pages', 'action' => 'home'));
Router::connect('/glossary', array('controller' => 'pages', 'action' => 'glossary'));
Router::connect('/calculator', array('controller' => 'calculators', 'action' => 'index'));
Router::connect('/clear_cache/*', array('controller' => 'pages', 'action' => 'clear_cache'));

CakePlugin::routes();
require CAKE . 'Config' . DS . 'routes.php';