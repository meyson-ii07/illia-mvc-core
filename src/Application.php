<?php

namespace Meyson\IlliaMvcCore;

use Dotenv\Dotenv;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Application
{
    public static string $ROOT_DIR;

    public static Application $app;
    public Router $router;
    public Request $request;
    public Response $response;
    public Session $session;
    public Database $db;
    public Environment $twig;

    /**
     * Application constructor.
     * @param $rootDir
     * @param $config
     */
    public function __construct($rootDir, $config)
    {
        self::$ROOT_DIR = $rootDir;
        self::$app = $this;
        $this->request = new Request();
        $this->response = new Response();
        $this->session = new Session();
        $this->router = new Router($this->request, $this->response);
        $this->db = new Database($config['db']);
    }


    public static function initialiseApp()
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__)."/../../../");
        $dotenv->load();
        $config = [
            'db' => [
                'dsn' => $_ENV['DB_DSN'],
                'user' => $_ENV['DB_USER'],
                'password' => $_ENV['DB_PASSWORD'],
            ],
        ];
        $isDebug = $_ENV['APP_ENV'] == 'debug';

        if ($isDebug) {
            $twigConf = [
                'debug' => true,
                'cache' => false,
                'auto_reload' => true,
            ];
        } else {
            $twigConf = [
                'debug' => false,
                'cache' => '../var/cache',
                'auto_reload' => true,
            ];
        }

        try {
            $loader = new FilesystemLoader('../views');
            $twig = new Environment($loader, $twigConf);


            $app = new Application(dirname(__DIR__)."/../../../", $config);
            $app->session->setCsrfToken();
            $app->router->loadRoutes();
            $app->twig = $twig;
            $app->run();
        }
        catch (Exception $e) {
            if ($isDebug) {
                dd($e->getMessage()."\n".$e->getTraceAsString());
            } else {
                $twig->render('404_.html.twig');
            }
        }
    }


    /**
     *  Echoes content of the page
     */
    public function run()
    {
        echo $this->router->resolve();
    }

    public function setTwig($twig)
    {
        $this->twig = $twig;
    }

}