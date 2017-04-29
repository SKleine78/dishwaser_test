<?php
namespace SKleine\Helper;

/**
 * Interface for loading and saving devices
 *
 * @package SKleine
 */
interface DeviceStorageInterface
{
    /**
     * get devices from storage
     * @param string $userHash
     * @return array
     */
    public function loadDevicesForUser($userHash);

    /**
     * puts devices into storage
     *
     * @param array $devices
     * @param string $userHash
     * @return RedisDeviceStorage
     */
    public function saveDevicesForUser(array $devices, $userHash);

}
