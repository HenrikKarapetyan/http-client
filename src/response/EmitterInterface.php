<?php
/**
 * Copyright (c)  2016
 * Author  Henrik Karapetyan
 * Email:  henrikkarapetyan@gmail.com
 * Country: Armenia
 * File created:  2019/9/8  11:7:59.
 */

namespace henrik\http_client\response;


use Psr\Http\Message\ResponseInterface;

/**
 * Interface EmitterInterface
 * @package henrik\http_client\response
 */
interface EmitterInterface
{
    /**
     * Emit a response.
     *
     * Emits a response, including status line, headers, and the message body,
     * according to the environment.
     *
     * Implementations of this method may be written in such a way as to have
     * side effects, such as usage of header() or pushing output to the
     * output buffer.
     *
     * Implementations MAY raise exceptions if they are unable to emit the
     * response; e.g., if headers have already been sent.
     *
     * @param ResponseInterface $response
     * @param null $maxBufferLevel
     */
    public function emit(ResponseInterface $response, $maxBufferLevel = null);
}
