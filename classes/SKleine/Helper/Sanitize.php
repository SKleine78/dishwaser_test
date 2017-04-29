<?php
namespace SKleine\Helper;

/**
 * Sanitize methods
 *
 * @package SKleine
 */
class Sanitize
{

    public function int($value) {
        return filter_var(trim($value), FILTER_SANITIZE_NUMBER_INT);
    }

    public function hash($value) {
        $value = $this->string($value);
        return preg_replace('/[^a-z0-9#]/i', '', $value);
    }

    public function string($value) {
        return filter_var(trim($value), FILTER_SANITIZE_STRING);
    }

}
