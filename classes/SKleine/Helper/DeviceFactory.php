<?php
namespace SKleine\Helper;

/**
 * factory to load device classes based on type
 * @package SKleine
 */
class DeviceFactory
{
    protected $_logger;

    public function __construct(\Monolog\Logger $logger) {
        $this->_logger = $logger;
    }
    /**
     * returns instance of device class
     *
     * @param string $name
     * @return null|\SKleine\Devices\DeviceInterface
     */
    public function getDevice($name) {
        $deviceClass = '\SKleine\Devices\\'.ucfirst($name);

        // try to load class
        try {
            $deviceInstance = new $deviceClass($this->_logger);
            return $deviceInstance;
        } catch (Exception $e) {

        }

        return null;
    }
}
