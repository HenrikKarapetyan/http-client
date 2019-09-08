<?php
/**
 * Copyright (c)  2016
 * Author  Henrik Karapetyan
 * Email:  henrikkarapetyan@gmail.com
 * Country: Armenia
 * File created:  2019/8/11  6:22:0.
 */

/**
 * Created by PhpStorm.
 * User: Henrik
 * Date: 2/6/2018
 * Time: 9:58 AM
 */

namespace henrik\http-client\exceptions;


use Throwable;

class HeadersAlreadySendedException extends HttpException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}