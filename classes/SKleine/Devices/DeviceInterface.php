<?php
namespace SKleine\Devices;

/**
 * device interface
 * @package SKleine
 */
interface DeviceInterface
{
    /**
     * constructor, required logging function
     *
     * @param \Monolog\Logger $logger
     */
    public function __construct(\Monolog\Logger $logger);

    /**
     * create new device
     *
     * @param string $hash
     * @param string $description
     * @param array $initData
     * @param array $typeInfo
     * @return self
     */
    public function newDevice($hash, $description, array $initData, array $typeInfo);

    /**
     * @param array $data
     * @param array $typeInfo
     * @return self
     */
    public function deviceFromData(array $data, array $typeInfo);

    /**
     * returns a savable array of the important device information
     * @return array
     */
    public function deviceToData();

    /**
     * return device details (status, current programm, remaining time, whatever)
     * if no code is given, returns all details
     *
     * @param string $code
     * @return null|string|array
     */
    public function getData($code = '');

    /**
     * return hash, type and description
     * @return array
     */
    public function getInfo();

    /**
     * changes device details
     * the device might need to react on a change or reject it
     *
     * @param string $code
     * @param mixed $value
     * @return self
     */
    public function setData($code, $value);

    /**
     * returns type of device
     *
     * @return string
     */
    public function getType();

    /**
     * returns hash for device
     *
     * @return string
     */
    public function getHash();

    /**
     * returns description for device
     *
     * @return string
     */
    public function getDescription();

}
