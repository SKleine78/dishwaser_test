<?php
namespace SKleine\Helper;

/**
 * load and store devices
 *
 * @package SKleine
 */
class RedisDeviceStorage implements DeviceStorageInterface {

    const KEY_PREFIX = 'skleine:coding:';
    const KEY_SUFFIX = ':devices';

    protected $_redis = null;

    public function __construct(\Predis\Client $redis) {
        $this->_redis = $redis;
    }

    /**
     * get devices from storage (redis in this case)
     * @param string $userHash
     * @return array
     */
    public function loadDevicesForUser($userHash) {
        $devices = array();
        $json = $this->_redis->get($this->_getKeyForUserHash($userHash));
        if (!empty($json)) {
            $devices = json_decode($json, true);
        }

        return $devices;
    }

    /**
     * puts devices into storage (redis is used)
     *
     * @param array $devices
     * @param string $userHash
     * @return RedisDeviceStorage
     */
    public function saveDevicesForUser(array $devices, $userHash) {
        if (empty($devices)) {
            $devices = array();
        }

        $json = json_encode($devices);
        $this->_redis->set($this->_getKeyForUserHash($userHash), $json);

        return $this;
    }

    protected function _getKeyForUserHash($userHash) {
        return self::KEY_PREFIX.$userHash.self::KEY_SUFFIX;
    }
}
