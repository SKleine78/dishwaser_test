<?php
use \PHPUnit\Framework\TestCase;

class ApiTest extends \PHPUnit\Framework\TestCase
{

    public function testGetTypeInfo() {
        $app = $this->_setupApp();

        $env = \Slim\Http\Environment::mock([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/v1/info',
            'QUERY_STRING' => '',
            'SERVER_NAME' => 'coding.local',
            'CONTENT_TYPE' => 'application/json;charset=utf8',
            'CONTENT_LENGTH' => 15
        ]);
        $container = $app->getContainer();
        $container['environment'] = $env;

        $app->get('/api/v1/info', function(\Slim\Http\Request $request, \Slim\Http\Response $response){
            try {
                $activeTypes = $this->type_info->getActiveTypes();
            } catch (\Exception $e) {
                $this->logger->error($request->getUri()->getPath().' Error getting device types: '. $e->getMessage());
                return $response->withJson(array('error' => 'Could not load active devices'), 500);
            }

            if (!empty($activeTypes)) {
                $newResponse = $response->withJson($activeTypes);
            } else {
                $newResponse = $response->withJson(array(), 204);
            }

            return $newResponse;
        });

        $response = $app->run(true);

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(157, $response->getBody()->getSize());
        $this->assertEquals('200', $response->getStatusCode());

        $body = $response->getBody();
        $body->rewind();
        $json = $body->read(200);
        $data = json_decode($json, true);
        $this->assertEquals(true, is_array($data));
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('dishwasher', $data);
    }


    protected function _setupApp() {
        $app = new \Slim\App();
        $this->_setupContainer($app);

        return $app;
    }

    protected function _setupContainer($app) {
        $container = $app->getContainer();

        // add logger (monolog)
        $container['logger'] = function($c) {
            $stub = $this->createMock('\Monolog\Logger');
            return $stub;
        };

        // add device info
        $container['type_info'] = function($c) {
            $allTypes = array(
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
            $activeTypes = array(
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
            );

            $stub = $this->createMock('\SKleine\Helper\TypeInfo');
            $stub->method('getAllTypes')
                ->will($this->returnValue($allTypes));
            $stub->method('getActiveTypes')
                ->will($this->returnValue($activeTypes));
            $stub->method('getType')
                ->will($this->returnValue($activeTypes['dishwasher']));
            return $stub;
        };

        // add redis connection
        $container['redis'] = function($c) {
            $stub = $this->createMock('\Predis\Client');
            return $stub;
        };

        // add device storage
        // uses redis at the moment
        // should be an implementation of DeviceStorageInterface
        $container['storage'] = function($c) {
            $userDevicesData = array(
                0 => array(
                    'hash' => 'test1',
                    'description' => 'test description',
                    'door' => 'closed',
                    'status' => 'off',
                    'current_program' => 'Standard',
                    'current_program_started' => '',
                    'type' => 'dishwasher',
                ),
                1 => array(
                    'hash' => 'test2',
                    'description' => 'test description',
                    'door' => 'open',
                    'status' => 'off',
                    'current_program' => 'Standard',
                    'current_program_started' => '',
                    'type' => 'dishwasher',
                ),
            );

            $stub = $this->createMock('\SKleine\Helper\RedisDeviceStorage');
            $stub->method('loadDevicesForUser')
                ->will($this->returnValue($userDevicesData));
            return $stub;
        };

        // add sanitize
        $container['sanitize'] = function($c) {
            $stub = $this->createMock('\SKleine\Helper\Sanitize');
            return $stub;
        };

        // add device factory
        $container['device_factory'] = function($c) {
            // mock device factory and use real device class
            // should be mocked too, but this would take a while
            $dishwasher = new \SKleine\Devices\Dishwasher($this->createMock('\Monolog\Logger'));

            $stub = $this->createMock('\SKleine\Helper\DeviceFactory');
            $stub->method('getDevice')
                ->will($this->returnValue($dishwasher));

            return $stub;
        };
    }
}
