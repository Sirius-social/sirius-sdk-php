<?php

namespace Siruis\Base;


/**
 * Communication abstraction for reading data stream
 *
 * @package Siruis\Base
 */
interface ReadOnlyChannel
{
    /**
     * Read message packet
     *
     * @param int|null $timeout
     * @return mixed
     */
    public function read($timeout = null);
}
