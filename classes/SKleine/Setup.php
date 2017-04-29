<?php
namespace SKleine;

/**
 * Main controller file containing routes and basic logic
 *
 * @package SKleine
 */
class Setup
{

    public function setupRoutes(\Slim\App $app) {

        $app->get('/', function (\Slim\Http\Request $request, \Slim\Http\Response $response) {
            $response->getBody()->write('Dishwasher Test Backend. Please use API');
            return $response;
        });

        // get active device type info
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

        // get list of current devices
        $app->get('/api/v1/devices', function(\Slim\Http\Request $request, \Slim\Http\Response $response){
            try {
                $list = array();

                $devices = new \SKleine\Devices($this->type_info->getAllTypes(), $this->storage, $this->logger, $this->device_factory);
                foreach ($devices->getDeviceList() as $_device) {
                    $list[] = $_device->getInfo();
                }
                //$this->logger->info('Devices', $list);
            } catch (\Exception $e) {
                $this->logger->error('Error getting devices list: '. $e->getMessage());
                return $response->withJson(array('error' => 'Could not load devices'), 500);
            }

            if (!empty($list)) {
                $newResponse = $response->withJson($list);
            } else {
                $newResponse = $response->withJson(array(), 204);
            }

            return $newResponse;
        });

        // get status details for one device
        $app->get('/api/v1/devices/{hash}', function(\Slim\Http\Request $request, \Slim\Http\Response $response, $args){
            try {
                $deviceHash = $args['hash'];
                $deviceHash = $this->sanitize->hash($deviceHash);

                $devices = new \SKleine\Devices($this->type_info->getAllTypes(), $this->storage, $this->logger, $this->device_factory);
                $_device = $devices->getDevice($deviceHash);

                if (empty($_device)) {
                    throw new \Exception('Could not find device for '.$deviceHash);
                }

                $newResponse = $response->withJson($_device->getData());
                return $newResponse;

            } catch (\Exception $e) {
                $this->logger->error('Error getting devices list: '. $e->getMessage());
                return $response->withJson(array('error' => 'Could not load device'), 500);
            }
        });

        // create new device
        $app->post('/api/v1/devices', function(\Slim\Http\Request $request, \Slim\Http\Response $response){
            try {
                $data = $request->getParsedBody();
                if (empty($data['type']) || empty($data['description'])) {
                    $this->logger->error('Error creating device', $data);
                    return $response->withJson(array('error' => 'Could not create device'), 500);
                }

                $type = $this->sanitize->string($data['type']);
                $description = $this->sanitize->string($data['description']);

                $devices = new \SKleine\Devices($this->type_info->getAllTypes(), $this->storage, $this->logger, $this->device_factory);
                $newDevice = $devices->createDevice('', $type, $description, array());

                if (empty($newDevice)) {
                    throw new \Exception('New device is empty');
                }

                $newResponse = $response->withJson($newDevice->getInfo(), 201);
                return $newResponse;

            } catch (\Exception $e) {
                $this->logger->error('Error creating device: '. $e->getMessage());
                $this->logger->error($e->getTraceAsString());
                return $response->withJson(array('error' => 'Could not create device'), 500);
            }
        });

        // remove one device
        $app->delete('/api/v1/devices/{hash}', function(\Slim\Http\Request $request, \Slim\Http\Response $response, $args){
            try {
                $deviceHash = $args['hash'];
                $deviceHash = $this->sanitize->hash($deviceHash);

                $devices = new \SKleine\Devices($this->type_info->getAllTypes(), $this->storage, $this->logger, $this->device_factory);
                $devices->deleteDevice($deviceHash);

                $newResponse = $response->withJson(array('removed' => $deviceHash));
                return $newResponse;

            } catch (\Exception $e) {
                $this->logger->error('Error deleting device: '. $e->getMessage());
                return $response->withJson(array('error' => 'Could not delete device '.$deviceHash), 500);
            }
        });

        // get status details for one device
        $app->put('/api/v1/devices/{hash}', function(\Slim\Http\Request $request, \Slim\Http\Response $response, $args){
            try {
                $deviceHash = $args['hash'];
                $deviceHash = $this->sanitize->hash($deviceHash);

                $devices = new \SKleine\Devices($this->type_info->getAllTypes(), $this->storage, $this->logger, $this->device_factory);
                $_device = $devices->getDevice($deviceHash);

                if (empty($_device)) {
                    throw new \Exception('Could not load device');
                }

                $data = $request->getParsedBody();

                if (!empty($data)) {
                    // uipdate device data
                    foreach ($data as $_key => $_value) {
                        $_key = $this->sanitize->string($_key);
                        $_value = $this->sanitize->string($_value);
                        $_device->setData($_key, $_value);
                    }
                    $devices->updateDevice($_device);
                }

                $newResponse = $response->withJson($_device->getData());
                return $newResponse;

            } catch (\Exception $e) {
                $this->logger->error('Error updating device: '. $e->getMessage());
                $response->getBody()->write('Could not update device '.$deviceHash.': '.$e->getMessage());
                $newResponse = $response->withStatus(403);
                return $newResponse;
                return $newResponse->getBody()->write('Could not update device '.$deviceHash.': '.$e->getMessage());
            }
        });

    }

    public function setupContainer(\Slim\App $app) {
        // containers are used for dependency injection
        $container = $app->getContainer();

        // add logger (monolog)
        $container['logger'] = function($c) {
            $logger = new \Monolog\Logger('my_logger');
            $file_handler = new \Monolog\Handler\StreamHandler("../logs/app.log");
            $logger->pushHandler($file_handler);
            return $logger;
        };

        // add device info
        $container['type_info'] = function($c) {
            $typeInfo = new \SKleine\Helper\TypeInfo();
            return $typeInfo;
        };

        // add redis connection
        $container['redis'] = function($c) {
            $redis = new \Predis\Client(array(
                'host' => $c->get('settings')['redis']['host'],
                'port' => $c->get('settings')['redis']['port'],
            ));
            return $redis;
        };

        // add device storage
        // uses redis at the moment
        // should be an implementation of DeviceStorageInterface
        $container['storage'] = function($c) {
            $storage = new \SKleine\Helper\RedisDeviceStorage($c->get('redis'));
            return $storage;
        };

        // add sanitize
        $container['sanitize'] = function($c) {
            $sanitize = new \SKleine\Helper\Sanitize();
            return $sanitize;
        };

        // add device factory
        $container['device_factory'] = function($c) {
            $deviceFactory = new \SKleine\Helper\DeviceFactory($c->get('logger'));
            return $deviceFactory;
        };

    }
}
