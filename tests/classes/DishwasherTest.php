<?php

use \PHPUnit\Framework\TestCase;

class DishwasherTest extends \PHPUnit\Framework\TestCase
{

    public function testNewDishwasher() {
        $dishwasher = new \SKleine\Devices\Dishwasher($this->_mockLogger());
        $dishwasher->newDevice('test123', 'test', array(), $this->_dishwasherTypeInfo());

        $this->assertEquals('dishwasher', $dishwasher->getType());
        $this->assertEquals('test', $dishwasher->getDescription());
        $this->assertEquals('Standard', $dishwasher->getData('currentProgram'));
        $this->assertEquals('closed', $dishwasher->getData('door'));
        $this->assertEquals('off', $dishwasher->getData('status'));
    }

    public function testDishwasherFromData() {
        $dishwasher = new \SKleine\Devices\Dishwasher($this->_mockLogger());
        $dishwasher->deviceFromData($this->_dishwasherData(), $this->_dishwasherTypeInfo());

        $this->assertEquals('dishwasher', $dishwasher->getType());
        $this->assertEquals('test description', $dishwasher->getDescription());
        $this->assertEquals('Standard', $dishwasher->getData('currentProgram'));
        $this->assertEquals('closed', $dishwasher->getData('door'));
        $this->assertEquals('off', $dishwasher->getData('status'));
    }

    public function testRunningDishwasherFromData() {
        $data = $this->_dishwasherData();
        $data['status'] = 'on';
        $data['currentProgramStarted'] = time()-2;    // started 2 seconds ago

        $dishwasher = new \SKleine\Devices\Dishwasher($this->_mockLogger());
        $dishwasher->deviceFromData($data, $this->_dishwasherTypeInfo());

        $this->assertEquals('dishwasher', $dishwasher->getType());
        $this->assertEquals('test description', $dishwasher->getDescription());
        $this->assertEquals('Standard', $dishwasher->getData('currentProgram'));
        $this->assertEquals('closed', $dishwasher->getData('door'));
        $this->assertEquals('on', $dishwasher->getData('status'));
        $this->assertGreaterThan(100, $dishwasher->getData('currentProgramRemaining'));
    }

    public function testFinishedDishwasherFromData() {
        $data = $this->_dishwasherData();
        $data['status'] = 'on';
        $data['currentProgramStarted'] = time()-122;    // started 122 seconds ago, should be finished

        $dishwasher = new \SKleine\Devices\Dishwasher($this->_mockLogger());
        $dishwasher->deviceFromData($data, $this->_dishwasherTypeInfo());
        $dishwasher->refresh();

        $this->assertEquals('dishwasher', $dishwasher->getType());
        $this->assertEquals('test description', $dishwasher->getDescription());
        $this->assertEquals('Standard', $dishwasher->getData('currentProgram'));
        $this->assertEquals('closed', $dishwasher->getData('door'));
        $this->assertEquals('off', $dishwasher->getData('status'));
    }

    public function testOpenDoor() {
        $dishwasher = new \SKleine\Devices\Dishwasher($this->_mockLogger());
        $dishwasher->deviceFromData($this->_dishwasherData(), $this->_dishwasherTypeInfo());
        $dishwasher->setData('door', 'open');

        $this->assertEquals('dishwasher', $dishwasher->getType());
        $this->assertEquals('test description', $dishwasher->getDescription());
        $this->assertEquals('Standard', $dishwasher->getData('currentProgram'));
        $this->assertEquals('open', $dishwasher->getData('door'));
        $this->assertEquals('off', $dishwasher->getData('status'));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Could not open door while dishwasher is running
     */
    public function testOpenDoorWhileRunning() {
        // is not allowed
        $data = $this->_dishwasherData();
        $data['status'] = 'on';
        $data['currentProgramStarted'] = time()-2;    // started 2 seconds ago

        $dishwasher = new \SKleine\Devices\Dishwasher($this->_mockLogger());
        $dishwasher->deviceFromData($data, $this->_dishwasherTypeInfo());

        $dishwasher->setData('door', 'open');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Could not start because door is open
     */
    public function testStartWithOpenDoor() {
        // is not allowed
        $data = $this->_dishwasherData();
        $data['door'] = 'open';

        $dishwasher = new \SKleine\Devices\Dishwasher($this->_mockLogger());
        $dishwasher->deviceFromData($data, $this->_dishwasherTypeInfo());

        $dishwasher->setData('status', 'on');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Wrong value for door
     */
    public function testDishwasherFromWrongData() {
        // this test will fail, because the check for useful data is not implemented
        $data = $this->_dishwasherData();
        $data['door'] = 'broken';

        $dishwasher = new \SKleine\Devices\Dishwasher($this->_mockLogger());
        $dishwasher->deviceFromData($data, $this->_dishwasherTypeInfo());
    }


    protected function _dishwasherTypeInfo() {
        $typeInfo = array(
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
        );
        return $typeInfo;
    }
    protected function _dishwasherData() {
        $dishwasherData = array(
            'hash' => 'test1',
            'description' => 'test description',
            'door' => 'closed',
            'status' => 'off',
            'currentProgram' => 'Standard',
            'currentProgramStarted' => '',
            'type' => 'dishwasher',
        );

        return $dishwasherData;
    }

    protected function _mockLogger() {
        $stub = $this->createMock('\Monolog\Logger');
        return $stub;
    }
}
