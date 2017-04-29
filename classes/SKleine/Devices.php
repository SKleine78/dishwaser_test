<?php
namespace SKleine;

/**
 * Main class for accessing any devices
 *
 * @package SKleine
 */
class Devices
{

    protected $_typeInfo = array();
    protected $_userHash = '000';

    /** @var \SKleine\Helper\DeviceStorageInterface */
    protected $_storage = null;

    /** @var \Monolog\Logger */
    protected $_logger = null;

    /** @var null|\SKleine\Helper\DeviceFactory */
    protected $_deviceFactory = null;

    /**
     * init dependencies for devices
     *
     * @param array $typeInfo
     * @param Helper\DeviceStorageInterface $storage
     * @param \Monolog\Logger $logger
     * @param Helper\DeviceFactory $factory
     */
    public function __construct(array $typeInfo, \SKleine\Helper\DeviceStorageInterface $storage, \Monolog\Logger $logger, \SKleine\Helper\DeviceFactory $factory) {
        $this->_typeInfo = $typeInfo;
        $this->_storage = $storage;
        $this->_logger = $logger;
        $this->_deviceFactory = $factory;
    }

    /**
     * to separate users, we use a user hash value
     * @param string $userHash
     */
    public function setUserHash($userHash = '000') {
        $this->_userHash = $userHash;
    }

    /**
     * Returns list of all available devices
     * @return array
     */
    public function getDeviceList() {
        $list = array();
        $devices = $this->_storage->loadDevicesForUser($this->_userHash);
//$this->_logger->info(print_r( $devices, true));

        if (is_array($devices)) {
            foreach ($devices as $_data) {
                if ($this->_checkDeviceData($_data)) {
                    $deviceType = $_data['type'];
                    try {
                        $_thisDevice = $this->_deviceFactory->getDevice($deviceType);
                        $_thisDevice->deviceFromData($_data, $this->_typeInfo[$deviceType]);
                        $list[$_thisDevice->getHash()] = $_thisDevice;
                    } catch (\Exception $e) {
                        // if we fail to load a device
                        // we just ignore it
                    }
                }
            }
        }

        return $list;
    }

    /**
     * returns a certain device for a given user
     *
     * @param string $hash
     * @return null|\SKleine\Devices\DeviceInterface
     */
    public function getDevice($hash) {
        $devices = $this->_storage->loadDevicesForUser($this->_userHash);
        foreach ($devices as $_data) {
            if ($this->_checkDeviceData($_data)) {
                if ($_data['hash'] == $hash) {
                    try {
                        $deviceType = $_data['type'];
                        /** @var $_device \SKleine\Devices\DeviceInterface */
                        $_device = $this->_deviceFactory->getDevice($deviceType);
                        $_device->deviceFromData($_data, $this->_typeInfo[$deviceType]);
                        return $_device;
                    } catch (\Exception $e) {
                        // fail silent
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param string $hash
     * @param string $type
     * @param string $description
     * @param array $initData
     * @return null|Devices\DeviceInterface
     * @throws Exception
     */
    public function createDevice($hash, $type, $description, array $initData = array()) {
        // check that type is available
        if (!isset($this->_typeInfo[$type])) {
            throw new \Exception('Unknown device type '.$type);
        }

        // get list of existing devices, check that hash does not exist
        $existingDevices = $this->getDeviceList();
        if (!empty($hash) && isset($existingDevices[$hash])) {
            throw new \Exception('Device already exists');
        } else if (empty($hash)) {
            // create new hash
            $hash = $this->_newHash();
            // we should check, that our newly generated hash does not exist, but I skip that
        }

        $deviceType = $type;
        try {
            /** @var $newDevice \SKleine\Devices\DeviceInterface  */
            $newDevice = $this->_deviceFactory->getDevice($deviceType);
            $newDevice->newDevice($hash, $description, $initData, $this->_typeInfo[$deviceType]);

            // add new device and save devices
            $existingDevices[$hash] = $newDevice;
            $this->saveDevicesToStorage($existingDevices);

            return $newDevice;
        } catch (\Exception $e) {
            $this->_logger->error($e->getTraceAsString());
            // if we fail to create a device
            // return error, might need different handling in future
            throw new \Exception($e->getMessage());
        }

        return null;
    }

    /**
     * update a single device in the list (or add if it does not exist)
     * and saves all to storage
     *
     * @param string $hash
     * @param Devices\DeviceInterface $changed
     */
    public function updateDevice(\SKleine\Devices\DeviceInterface $changed) {
        $hash = $changed->getHash();
        $allDevices = $this->getDeviceList();
        $allDevices[$hash] = $changed;

        $this->saveDevicesToStorage($allDevices);
    }

    /**
     * removes device with given hash
     *
     * @param string $hash
     * @return self
     */
    public function deleteDevice($hash) {
        $devices = $this->_storage->loadDevicesForUser($this->_userHash);
        $newDevices = array();
        foreach ($devices as $_data) {
            if ($this->_checkDeviceData($_data)) {
                if ($_data['hash'] != $hash) {
                    $newDevices[] = $_data;
                }
            }
        }
        $this->_saveDeviceDataArrayToStorage($newDevices);

        return $this;
    }

    /**
     * save list of DevicesInterface into storage
     * must receive a complete list, all devices not in the list are removed
     *
     * @param array $devices
     * @return self
     */
    public function saveDevicesToStorage(array $devices) {
        $data = array();
        foreach ($devices as $_device) {
            $data[] = $_device->deviceToData();
        }
        $this->_saveDeviceDataArrayToStorage($data);

        return $this;
    }

    /**
     * saves array into storage
     * array must contain no objects!
     *
     * @param array $data
     */
    protected function _saveDeviceDataArrayToStorage(array $data) {
        $this->_storage->saveDevicesForUser($data, $this->_userHash);
    }

    /**
     * checks if device data seems to be a valid device
     * @param array $data
     * @return bool
     */
    protected function _checkDeviceData(array $data) {
        if (empty($data['hash'])) {
            return false;
        }
        if (empty($data['description'])) {
            return false;
        }
        if (empty($data['type'])) {
            return false;
        }
        if (empty($this->_typeInfo[$data['type']])) {
            return false;
        }

        return true;
    }

    protected function _newHash() {
        $string = 'SKCC'.time().$this->_userHash;
        return md5($string);
    }

}
