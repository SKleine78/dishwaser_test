<?php
namespace SKleine\Devices;

/**
 * dishwaser device
 *
 * @package SKleine
 */
class Dishwasher extends \SKleine\Devices\AbstractDevice implements \SKleine\Devices\DeviceInterface
{
    const STATUS_ON = 'on';
    const STATUS_OFF = 'off';
    const DOOR_OPEN = 'open';
    const DOOR_CLOSED = 'closed';

    const ATTR_DOOR = 'door';
    const ATTR_STATUS = 'status';
    const ATTR_CURRENT_PROGRAM = 'currentProgram';
    const ATTR_PROGRAM_STARTED = 'currentProgramStarted';
    const ATTR_PROGRAM_REMAINING = 'currentProgramRemaining';

    protected $_type = 'dishwasher';
    protected $_door = self::DOOR_CLOSED;
    protected $_status = self::STATUS_OFF;
    protected $_currentProgram = 'Standard';
    protected $_currentProgramStarted = null; // timestamp

    protected $_availableStates = array(
        'door' => array(self::DOOR_OPEN, self::DOOR_CLOSED),
        'status' => array(self::STATUS_ON, self::STATUS_OFF),
    );
    protected $_availableData = array(self::ATTR_DOOR, self::ATTR_STATUS, self::ATTR_CURRENT_PROGRAM, self::ATTR_PROGRAM_STARTED, self::ATTR_PROGRAM_REMAINING);
    protected $_changeableData = array(self::ATTR_DOOR, self::ATTR_STATUS, self::ATTR_CURRENT_PROGRAM);

    /**
     * create new dishwasher device
     *
     * @param string $hash
     * @param string $description
     * @param array $initData
     * @param array $typeInfo
     * @return self
     */
    public function newDevice($hash, $description, array $initData, array $typeInfo) {
        $this->_hash = $hash;
        $this->_description = $description;
        $this->_typeInfo = $typeInfo;

        $this->_additional = $initData;
        // might need some more sophisticated handling, depending on possible init data
        if (isset($initData[self::ATTR_DOOR]) && in_array($initData[self::ATTR_DOOR], $this->_availableStates[self::ATTR_DOOR])) {
            $this->_door = $initData[self::ATTR_DOOR];
        }

        return $this;
    }

    /**
     * @param array $data
     * @param array $typeInfo
     * @return self
     */
    public function deviceFromData(array $data, array $typeInfo) {

        $this->_typeInfo = $typeInfo;

        $this->_checkArrayForDevice($data);

        $this->_hash = $data['hash'];
        $this->_description = $data['description'];
        $this->_door = $data[self::ATTR_DOOR];
        $this->_currentProgram = $data[self::ATTR_CURRENT_PROGRAM];
        $this->_currentProgramStarted = $data[self::ATTR_PROGRAM_STARTED];
        $this->_status = $data['status'];

        // refresh device to update status
        $this->refresh();
    }

    /**
     * returns a savable array of the important device information
     * @return array
     */
    public function deviceToData() {
        $result = array(
            'hash' => $this->_hash,
            'description' => $this->_description,
            self::ATTR_DOOR => $this->_door,
            self::ATTR_STATUS => $this->_status,
            self::ATTR_CURRENT_PROGRAM => $this->_currentProgram,
            self::ATTR_PROGRAM_STARTED => $this->_currentProgramStarted,
            'type' => $this->_type,
        );

        return $result;
    }

    /**
     * return device details (status, current programm, remaining time, whatever)
     * if no code is given, returns all details
     *
     * @param string $code
     * @return null|string|array
     */
    public function getData($code = '') {
        // refresh device first
        $this->refresh();

        if (empty($code)) {
            $result = array('hash' => $this->_hash);
            foreach ($this->_availableData as $_key) {
                $methodName = '_get'.ucfirst($_key);
                $result[$_key] = $this->$methodName();
            }

            return $result;
        } else if (in_array($code, $this->_availableData)) {
            $methodName = '_get'.ucfirst($code);
            return $this->$methodName();
        }

        return null;
    }

    /**
     * return hash, type and description
     * @return array
     */
    public function getInfo() {
        $result = array(
            'hash' => $this->_hash,
            'description' => $this->_description,
            'type' => $this->getType(),
        );

        return $result;
    }

    /**
     * changes device details
     * the device might need to react on a change or reject it
     *
     * @param string $code
     * @param mixed $value
     * @return self
     */
    public function setData($code, $value) {
        if (in_array($code, $this->_changeableData)) {
            $methodName = '_set'.ucfirst($code);
            $this->$methodName($value);

            // refresh if anything changed
            $this->refresh();
        }

        return $this;
    }

    /**
     * refresh device details
     * i.e. recalculate remaining time
     * stop program if finished
     *
     * @return self
     */
    public function refresh() {
        if ($this->_status == self::STATUS_OFF) {
            // reset starting time just to be sure it's empty
            $this->_currentProgramStarted = null;
        } else if ($this->_status == self::STATUS_ON) {
            // calculate finished time and check if we've reached that
            $finished = $this->_calculateFinishedTs();
            if ($finished < time()) {
                // program is done
                $this->_status = self::STATUS_OFF;
                $this->_currentProgramStarted = null;
            }
        }

        return $this;
    }

    /**
     * check if array is a usable dishwasher
     * @param $array
     * @return bool
     */
    protected function _checkArrayForDevice($array) {
        // TODO: change this to return Exceptions with valid error messages

        // check that values exist and are valid
        if (empty($array[self::ATTR_DOOR])) {
            throw new \Exception('Missing value for '.self::ATTR_DOOR);
        }
        if (empty($array[self::ATTR_STATUS])) {
            throw new \Exception('Missing value for '.self::ATTR_STATUS);
        }
        if (empty($array[self::ATTR_CURRENT_PROGRAM])) {
            throw new \Exception('Missing value for '.self::ATTR_CURRENT_PROGRAM);
        }

        if (!in_array($array[self::ATTR_DOOR], $this->_availableStates[self::ATTR_DOOR])) {
            throw new \Exception('Wrong value for '.self::ATTR_DOOR);
        }
        if (!in_array($array[self::ATTR_STATUS], $this->_availableStates[self::ATTR_STATUS])) {
            throw new \Exception('Wrong value for '.self::ATTR_STATUS);
        }

        $usablePrograms = array_keys($this->_typeInfo['data']['programs']);
        if (!in_array($array[self::ATTR_CURRENT_PROGRAM], $usablePrograms)) {
            throw new \Exception('Wrong value for '.self::ATTR_CURRENT_PROGRAM);
        }

        // check that logic is kept
        if ($array[self::ATTR_STATUS] == self::STATUS_ON) {
            // door must be close
            if ($array[self::ATTR_DOOR] == self::DOOR_OPEN) {
                throw new \Exception('Program is running, door can not be open');
            }
            // program start time must be set and in the past
            if (empty($array[self::ATTR_PROGRAM_STARTED]) || $array[self::ATTR_PROGRAM_STARTED] > time()) {
                throw new \Exception('Program start time is in future');
            }
        } else if ($array[self::ATTR_STATUS] == self::STATUS_OFF) {
            // we could check for start time, but that should not be a problem
            /* if (!empty($array[self::ATTR_PROGRAM_STARTED])) {
                return false;
            } */
        }

        return true;
    }

    protected function _getDoor() {
        return $this->_door;
    }
    protected function _getStatus() {
        return $this->_status;
    }
    protected function _getCurrentProgram() {
        return $this->_currentProgram;
    }
    /**
     * return timestamp of program start or null, if not running
     * @return null|int
     */
    protected function _getCurrentProgramStarted() {
        return $this->_currentProgramStarted;
    }
    /**
     * return remaining time in second or null, if not running
     * @return null|int
     */
    protected function _getCurrentProgramRemaining() {
        if ($this->_status == self::STATUS_ON) {
            $finished = $this->_calculateFinishedTs();
            return $finished - time();
        }

        return null;
    }

    protected function _calculateFinishedTs() {
        // get program duration
        $duration = (int)$this->_typeInfo['data']['programs'][$this->_currentProgram]['duration'];
        // calculate and return the expected finish time
        return (int)$this->_currentProgramStarted + $duration;
    }

    /**
     * set doort status
     * door can not be opended if dishwasher is running
     *
     * @param string $value
     * @throws Exception
     */
    protected function _setDoor($value) {
        if (in_array($value, $this->_availableStates[self::ATTR_DOOR])) {
            if ($value == 'open' && $this->_status == self::STATUS_ON) {
                // do not open door, while dishwasher is running
                throw new \Exception('Could not open door while dishwasher is running');
            }
            $this->_door = $value;
        } else {
            throw new \Exception('Could not change door to '.$value);
        }
    }
    /**
     * set dishwasher to on or off
     * can not start if door is still open or no program selected
     *
     * @param string $value
     */
    protected function _setStatus($value) {
        if (in_array($value, $this->_availableStates[self::ATTR_STATUS])) {
            if ($value == 'on' && $this->_door == self::DOOR_OPEN) {
                // do not start with open door
                throw new \Exception('Could not start because door is open');
            }
            if ($value == 'on' && $this->_currentProgram == '') {
                // do not start without program
                throw new \Exception('Please select a program to start the dishwasher');
            }

            $this->_status = $value;
            if ($value == self::STATUS_ON) {
                // if we can start the program, we need to set the starting time
                $this->_currentProgramStarted = time();
            } else if ($value == self::STATUS_OFF) {
                // does stop a program means, it is reset?
                // or is this more of a pause?
                // for now off means the program is reset
                $this->_currentProgramStarted = null;
            }
        } else {
            throw new \Exception('Could not change status to '.$value);
        }
    }
    /**
     * change current program
     * only if not running
     *
     * @param string $value
     */
    protected function _setCurrentProgram($value) {
        if ($this->_status == self::STATUS_ON) {
            throw new \Exception('Could not change program while dishwasher is running');
        }

        // check that this is an existing program
        $usablePrograms = array_keys($this->_typeInfo['data']['programs']);
        if (!in_array($value, $usablePrograms)) {
            throw new \Exception('Unknown program');
        }

        $this->_currentProgram = $value;
    }

}
