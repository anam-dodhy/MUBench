<?php

namespace MuBench\ReviewSite\Tests;

use Illuminate\Container\Container;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Response;
use Slim\Http\Uri;

require_once __DIR__ . '/../setup/database_setup_utils.php';


class SlimTestCase extends TestCase
{
    protected $app;

    /** @var Container */
    protected $container;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /** @var \Illuminate\Database\Capsule\Manager */
    protected $db;

    /** @var  \Illuminate\Support\Facades\Schema */
    protected $schema;

    public function setUp(){
        $settings = [
            'settings' => [
                'displayErrorDetails' => true, // set to false in production
                'addContentLengthHeader' => false, // Allow the web server to send the content-length header

                'db' => [
                    'driver' => 'sqlite',
                    'host' => 'localhost',
                    'database' => ':memory:',
                    'username' => 'admin',
                    'password' => 'admin',
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix'    => '',
                ],

                // Monolog settings
                'logger' => [
                    'name' => 'mubench',
                    'path' => __DIR__ . '/../logs/app.log',
                    'level' => \Monolog\Logger::DEBUG,
                ],
                'site_base_url' => '/',
                'upload' => "./upload",
                'default_ex2_review_size' => '20',
                'blind_mode' => [
                    'enabled' => false,
                    'detector_blind_names' => []
                ]
            ],
            'users' => [
                "admin" => "pass"
            ]
        ];
        $app = new \Slim\App($settings);
        $container = $app->getContainer();
        require __DIR__ . '/../bootstrap/logger.php';
        require __DIR__ . '/../bootstrap/db.php';
        require __DIR__ . '/../bootstrap/auth.php';
        require __DIR__ . '/../bootstrap/renderer.php';
        $this->logger = new \Monolog\Logger("test");
        createTables('default');
        require __DIR__ . '/../src/routes.php';
        require_once __DIR__ . '/../src/route_utils.php';
        require_once __DIR__ . '/../src/csv_utils.php';
        \MuBench\ReviewSite\Models\Detector::flushEventListeners();
        \MuBench\ReviewSite\Models\Detector::boot();
        $this->app = $app;
        $this->container = $container;
    }

    public function get($path, $data = array(), $optionalHeaders = array()){
        return $this->request('get', $path, $data, $optionalHeaders);
    }

    private function request($method, $path, $data = array(), $optionalHeaders = array()){
        //Make method uppercase
        $method = strtoupper($method);
        $options = array(
            'REQUEST_METHOD' => $method,
            'REQUEST_URI'    => $path
        );
        if ($method === 'GET') {
            $options['QUERY_STRING'] = http_build_query($data);
        } else {
            $params  = json_encode($data);
        }
        // Prepare a mock environment
        $env = Environment::mock(array_merge($options, $optionalHeaders));
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $serverParams = $env->all();
        $body = new RequestBody();
        // Attach JSON request
        if (isset($params)) {
            $headers->set('Content-Type', 'application/json;charset=utf8');
            $body->write($params);
        }
        $this->request  = new Request($method, $uri, $headers, array(), $serverParams, $body);
        $response = new Response();
        // Invoke request
        $app = $this->app;
        $this->response = $app($this->request, $response);
        // Return the application output.
        return $this->response->getBody();
    }

    private function mySQLToSQLite($mysql){
        $lines = explode("\n", $mysql);
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            // remove all named keys, i.e., leave only PRIMARY keys
            if (strpos($lines[$i], 'KEY `') !== false) {
                $lines[$i] = "";
                $lines[$i - 1] = substr($lines[$i - 1], 0, -1); // remove trailing comma in previous line
            }
        }
        $sqlite = implode("\n", $lines);
        $sqlite = str_replace("AUTO_INCREMENT", "", $sqlite);
        $sqlite = str_replace("int(11)", "INTEGER", $sqlite);
        $sqlite = str_replace(" ENGINE=MyISAM  DEFAULT CHARSET=latin1;", ";", $sqlite);
        return $sqlite;
    }
}
