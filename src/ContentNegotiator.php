<?php
/**
 * Copyright (c)  2016
 * Author  Henrik Karapetyan
 * Email:  henrikkarapetyan@gmail.com
 * Country: Armenia
 * File created:  2019/8/11  6:50:11.
 */

namespace henrik\http-client;


use henrik\http-client\exceptions\HeadersAlreadySendedException;

/**
 * Class ContentNegotiator
 * @package henrik\http-client
 */
class ContentNegotiator
{
    /**
     * @var ResponseOld
     */
    private $response;

    /**
     * ContentNegotiator constructor.
     * @param ResponseOld $response
     */
    public function __construct(ResponseOld $response)
    {
        $this->response = $response;
    }

    /**
     * @param $content
     * @throws HeadersAlreadySendedException
     */
    public function json($content)
    {
        $this->response->setHeaders(["Content-Type" => "application/json"]);
        $this->response->setContent(json_encode($content));
        $this->response->send();
    }

    /**
     * @param $content
     * @throws HeadersAlreadySendedException
     */
    public function html($content)
    {
        $this->response->setHeaders([
            "Content-type" => "text/html;charset=UTF-8",
            "Cache-control" => "no-cache, private",
        ]);
        $this->response->setContent($content);
        $this->response->send();
    }

    /**
     * @param $content
     * @throws HeadersAlreadySendedException
     */
    public function xml($content)
    {
        $this->response->setHeaders([
            "Content-type" => "text/xml;charset=UTF-8",
            "Cache-control: no-cache, private"
        ]);
        $this->response->setContent(xmlrpc_encode($content));
        $this->response->send();
    }
}