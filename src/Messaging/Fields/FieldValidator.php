<?php


namespace Siruis\Messaging\Fields;

/**
 * Interface for field validators
 * @package Siruis\Messaging\Fields
 */
interface FieldValidator
{
    /**
     * Validates field value
     *
     * @param $value
     * @return mixed
     */
    public function validate($value);
}