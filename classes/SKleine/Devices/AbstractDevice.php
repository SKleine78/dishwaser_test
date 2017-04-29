<?php
namespace SKleine\Devices;

/**
 * abstract device
 * contains common methods
 *
 * @package SKleine
 */
abstract class AbstractDevice
{
    protected $_hash = '';
    protected $_type = '';
    protected $_description = '';
    protected $_typeInfo = array();
    protected $_additional = array();
    protected $_logger = null;

    /**
     * constructor, required logging function
     *
     * @param \Monolog\Logger $logger
     */
    public function __construct(\Monolog\Logger $logger)
    {
        $this->_logger = $logger;
    }


    public function getType() {
        return $this->_type;
    }
    public function getHash() {
        return $this->_hash;
    }
    public function getDescription() {
        return $this->_description;
    }

}
