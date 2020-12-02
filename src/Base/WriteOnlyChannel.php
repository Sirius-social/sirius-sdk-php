<?php


namespace Siruis\Base;


/**
 * Communication abstraction for writing data stream
 *
 * @package Siruis\Base
 */
interface WriteOnlyChannel
{
    /**
     * Write message packet
     *
     * @param string $data
     * @return mixed
     */
    public function write(string $data);
}