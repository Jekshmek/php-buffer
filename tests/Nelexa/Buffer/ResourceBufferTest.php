<?php
namespace Nelexa\Buffer;


class ResourceBufferTest extends BufferTestCase
{

    /**
     * @return Buffer
     */
    protected function createBuffer()
    {
        $fp = fopen('php://temp', 'w+b');
        return new ResourceBuffer($fp);
    }

}