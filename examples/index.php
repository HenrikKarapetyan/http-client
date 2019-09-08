<?php
/**
 * Copyright (c)  2016
 * Author  Henrik Karapetyan
 * Email:  henrikkarapetyan@gmail.com
 * Country: Armenia
 * File created:  2019/9/8  10:48:53.
 */

require "../vendor/autoload.php";


$request = new \henrik\http_client\Request();


var_dump($request->getMethod());
