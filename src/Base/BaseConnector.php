<?php

namespace Siruis\Base;

/**
 * Transport Layer.
 *
 * Connectors operate as transport provider for high-level abstractions
 *
 * @package Siruis\Base
 */
abstract class BaseConnector implements WriteOnlyChannel, ReadOnlyChannel
{
    /**
     * Open communication
     */
    abstract public function open();

    /**
     * Close communication
     */
    abstract public function close();
    
    /**
     * Read message packet
     *
     * @param int|null $timeout
     * @return mixed
     */
    public function read($timeout = null)
    {
        // TODO: Implement read() method.
    }

    /**
     * Write message packet
     *
     * @param string $data
     * @return mixed
     */
    public function write(string $data)
    {
        // TODO: Implement write() method.
    }
}