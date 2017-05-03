<?php
use \PHPUnit\Framework\TestCase;

class DevicesTest extends \PHPUnit\Framework\TestCase
{

    public function testGetDevicesList() {

        $class = new \SKleine\Devices($this->_typeInfo(), $this->_mockRedisDeviceStorage(), $this->_mockLogger(), $this->_mockDeviceFactory());
        $list = $class->getDeviceList();

        $this->assertCount(2, $list);
        $this->assertArrayHasKey('test1', $list);
        $this->assertArrayHasKey('test2', $list);
        $this->assertInstanceOf('\SKleine\Devices\Dishwasher', $list['test1']);
    }

    public function testGetDevice() {

        $class = new \SKleine\Devices($this->_typeInfo(), $this->_mockRedisDeviceStorage(), $this->_mockLogger(), $this->_mockDeviceFactory());
        $device = $class->getDevice('test1');

        $this->assertInstanceOf('\SKleine\Devices\Dishwasher', $device);
        $this->assertEquals('closed', $device->getData('door'));
    }
    public function testGetDevice2() {

        $class = new \SKleine\Devices($this->_typeInfo(), $this->_mockRedisDeviceStorage(), $this->_mockLogger(), $this->_mockDeviceFactory());
        $device = $class->getDevice('test2');

        $this->assertInstanceOf('\SKleine\Devices\Dishwasher', $device);
        $this->assertEquals('open', $device->getData('door'));
    }

    public function testCreateDevice() {
        $class = new \SKleine\Devices($this->_typeInfo(), $this->_mockRedisDeviceStorage(), $this->_mockLogger(), $this->_mockDeviceFactory());
        $device = $class->createDevice('test3', 'dishwasher', 'test description3', array('door' => 'open'));

        $this->assertInstanceOf('\SKleine\Devices\Dishwasher', $device);
        $this->assertEquals('test3', $device->getHash());
        $this->assertEquals('open', $device->getData('door'));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Device already exists
     */
    public function testCreateExistingDevice() {
        $class = new \SKleine\Devices($this->_typeInfo(), $this->_mockRedisDeviceStorage(), $this->_mockLogger(), $this->_mockDeviceFactory());
        $device = $class->createDevice('test1', 'dishwasher', 'test description3');
    }


    protected function _typeInfo() {
        $typeInfo = array(
            'dishwasher' => array(
                'active' => 1,
                'description' => 'test dishwasher',
                'data' => array(
                    'programs' => array(
                        'Standard' => array(
                            'id' => 1,
                            'default' => 1,
                            'active' => 1,
                            'name' => 'Standard',
                            'duration' => 120,
                        )
                    )
                ),
            ),
            'oven' => array(
                'active' => 0,
                'description' => 'not available',
            )
        );
        return $typeInfo;
    }

    protected function _userDevicesData() {
        $userDevicesData = array(
            0 => array(
                'hash' => 'test1',
                'description' => 'test description',
                'door' => 'closed',
                'status' => 'off',
                'currentProgram' => 'Standard',
                'currentProgramStarted' => '',
                'type' => 'dishwasher',
            ),
            1 => array(
                'hash' => 'test2',
                'description' => 'test description',
                'door' => 'open',
                'status' => 'off',
                'currentProgram' => 'Standard',
                'currentProgramStarted' => '',
                'type' => 'dishwasher',
            ),
        );

        return $userDevicesData;
    }

    protected function _mockRedisDeviceStorage() {
        $userDeviceData = $this->_userDevicesData();

        $stub = $this->createMock('\SKleine\Helper\RedisDeviceStorage');
        $stub->method('loadDevicesForUser')
            ->will($this->returnValue($userDeviceData));

        return $stub;
    }

    protected function _mockLogger() {
        $stub = $this->createMock('\Monolog\Logger');
        return $stub;
    }

    protected function _mockDeviceFactory() {
        // mock device factory and use real device class
        // should be mocked too, but this would take a while
        $dishwasher = new \SKleine\Devices\Dishwasher($this->_mockLogger());

        $stub = $this->createMock('\SKleine\Helper\DeviceFactory');
        $stub->method('getDevice')
            ->will($this->returnValue($dishwasher));

        return $stub;
    }
}
