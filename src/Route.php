<?php

declare(strict_types=1);


namespace PTFramework;

use FastRoute\Dispatcher;
use PTFramework\Factory\InstanceFactory;
use PTFramework\MiddlewareInterface\RequestMiddlewareInterface;

use PTLibrary\Log\Log;
use PTLibrary\Tool\Tool;
use RuntimeException;
use function FastRoute\simpleDispatcher;

class Route
{
    private static $instance;

    private static $config;

    private static $dispatcher = null;

	private static array $_type_arr = [ 'GET', 'POST' ];

    private function __construct()
    {
    }

	/**
	 * @return \PTFramework\Route
	 */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();

	        self::$config = Config::getInstance()->get('routes');

            self::$dispatcher = simpleDispatcher(

                function (\FastRoute\RouteCollector $routerCollector) {
                    foreach (self::$config as $key=>$routerDefine) {
                    	try{
                    		$key=(string)$key;
		                    if(in_array($key,self::$_type_arr)){
			                    foreach ( $routerDefine as $GETRoute ) {
				                    self::setRoute($routerCollector,$key,$GETRoute[0], $GETRoute[1]);
                    			}
                    		}elseif($key === 'GROUP'){
			                    foreach ( $routerDefine as $groupKey=>$routes ) {
				                    foreach ( $routes as $_nextKey=>$route ) {
					                    if(in_array((string)$_nextKey,self::$_type_arr)){
						                    foreach ( $route as $_v ) {
							                    self::setRoute($routerCollector,$_nextKey,$groupKey.$_v[0], $_v[1]);
						                    }
					                    }else{
						                    self::setRoute($routerCollector,$route[0], $groupKey.$route[1], $route[2]);
					                    }
									}

			                    }

		                    }else{
			                    self::setRoute($routerCollector,$routerDefine[0], $routerDefine[1], $routerDefine[2]);
		                    }

                    	}catch(\Exception $e){
                    	    Application::echoError($e->getMessage());
                    	}

                    }

                }
            );
        }
        return self::$instance;
    }


    public static function setRoute($instance,$type,$path,$method){
	    $instance->addRoute($type,$path, $method);
    }

    /**
     * @param $request
     * @param $response
     * @throws \Exception
     * @return mixed|void
     */
    public function dispatch($request, $response)
    {
        $method = $request->server['request_method'] ?? 'GET';
        $uri = $request->server['request_uri'] ?? '/';
        $routeInfo = self::$dispatcher->dispatch($method, $uri);

		$requestMiddle=Tool::getArrVal('middleware.requestMiddleware',Config::getInstance()->getListener());

		if($requestMiddle && class_exists($requestMiddle)){
			$requestMiddle::RequestStart( $request, $response );
		}


        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return $this->defaultRouter($request, $response, $uri);
            case Dispatcher::METHOD_NOT_ALLOWED:
                $response->status(405);
                return $response->end();
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                if (is_string($handler)) {

                    $handler = explode('@', $handler);
                    if (count($handler) != 2) {
                        throw new RuntimeException("Route {$uri} config error, Only @ are supported");
                    }

                    $className = $handler[0];
                    $func = $handler[1];

                    if (! class_exists($className)) {
                        throw new RuntimeException("Route {$uri} defined '{$className}' Class Not Found");
                    }


                    $controller = new $className();

                    if (! method_exists($controller, $func)) {
                        throw new RuntimeException("Route {$uri} defined '{$func}' Method Not Found");
                    }

                    try{
	                    $middlewareHandler = function ($request, $response, $vars) use ($controller, $func) {
		                    return $controller->{$func}($request, $response, $vars ?? null);
	                    };
	                    $middleware = 'middleware';
						if(method_exists($controller,'init')){
						    $controller->init($request,$response);
						}
	                    if (property_exists($controller, $middleware)) {
		                    $classMiddlewares = $controller->{$middleware}['__construct'] ?? [];
		                    $methodMiddlewares = $controller->{$middleware}[$func] ?? [];
		                    $middlewares = array_merge($classMiddlewares, $methodMiddlewares);
		                    if ($middlewares) {
			                    $middlewareHandler = $this->packMiddleware($middlewareHandler, array_reverse($middlewares));
		                    }
	                    }
	                    $result= $middlewareHandler($request, $response, $vars ?? null);


                    }catch(\Exception $e){
	                    print_r( '异常：'.$e->getMessage().PHP_EOL );
	                    print_r( '调用：'.$e->getTraceAsString().PHP_EOL );
	                    Log::error($e->getMessage().PHP_EOL.$e->getTraceAsString());
	                    $result=$e;
                    }catch (\TypeError $e){
	                    print_r( $e->getMessage() .PHP_EOL);
	                    Log::error($e->getMessage().PHP_EOL.$e->getTraceAsString());
	                    $result=$e;
                    }

	                if($requestMiddle && class_exists($requestMiddle)){
		                $requestMiddle::End( $request, $response,$result );

	                }else{
	                	$response->end((string)$result);
	                }

					return true;
                }




                if (is_callable($handler)) {
                    return call_user_func_array($handler, [$request, $response, $vars ?? null]);
                }

                throw new RuntimeException("Route {$uri} config error");
            default:
                $response->status(400);
                return $response->end();
        }
    }

    /**
     * @param $request
     * @param $response
     * @param $uri
     */
    public function defaultRouter($request, $response, $uri)
    {
        $uri = trim($uri, '/');
        $uri = explode('/', $uri);

        if ($uri[0] === '') {
            $className = '\\App\\Controller\\IndexController';
            if (class_exists($className) && method_exists($className, 'index')) {
                return (new $className())->index($request, $response);
            }
        }
        $response->status(404);
        return $response->end();
    }

    /**
     * @param $handler
     * @param array $middlewares
     * @return mixed
     */
    public function packMiddleware($handler, $middlewares = [])
    {
        foreach ($middlewares as $middleware) {
            $handler = $middleware($handler);
        }
        return $handler;
    }
}
