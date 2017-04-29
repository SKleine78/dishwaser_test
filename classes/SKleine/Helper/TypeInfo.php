<?php
namespace SKleine\Helper;

/**
 * Load device-info.xml
 *
 * @package SKleine
 */
class TypeInfo
{

    protected $_data = array();

    protected $_fileName = null;

    /**
     * init filename
     * @param string $fileName optional new file name
     */
    public function __construct($fileName = 'device-info.xml') {
        $this->_fileName = $fileName;
    }

    /**
     * get information about active devices
     * @return array
     */
    public function getActiveTypes() {
        $activeDevices = array();
        foreach ($this->getAllTypes() as $_key => $_device) {
            if (isset($_device['active']) && $_device['active']) {
                $activeDevices[$_key] = $_device;
            }
        }

        return $activeDevices;
    }

    /**
     * get information about all devices
     * @return array
     */
    public function getAllTypes() {
        if (empty($this->_data)) {
            $this->_load();
        }

        return $this->_data;
    }

    /**
     * get information about one device
     *
     * @param string $code
     * @return array|null
     */
    public function getType($code) {
        $allDevices = $this->getAllTypes();
        if (isset($allDevices[$code])) {
            return $allDevices[$code];
        }

        return null;
    }

    /**
     * load data from xml file
     * @throws \Exception
     */
    protected function _load() {
        $file = __DIR__ . '/../../../config/'.$this->_fileName;
        if (file_exists($file)) {
            $xml = simplexml_load_file($file);
            //print_r($xml);
            foreach ($xml as $key => $_device) {
                $this->_data[$key] = array(
                    'type' => $key,
                    'active' => (int)$_device['active'],
                    'description' => (string)$_device->description,
                    'data' => array(),
                );

                if ($_device->data->count()) {
                    $this->_data[$key]['data'] = $this->_xml2array($_device->data->children());
                }
            }

        } else {
            throw new \Exception('file '.$file.' does not exist');
        }
    }

    /**
     * returns xml as php array
     * ignores all attributes in xml tags
     *
     * @param SimpleXmlElement $xml
     * @return array
     */
    protected function _xml2array($xml) {
        $arr = array();

        foreach ($xml as $element) {
            $tag = $element->getName();
            if ($element->count()) {
                $arr[$tag] = $this->_xml2array($element);
            }else {
                $arr[$tag] = trim($element);
            }
        }

        return $arr;
    }
}
