<?php

declare(strict_types=1);

namespace PTFramework;

class Listener
{
    private static $instance;

    private static $config;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$config = Config::getInstance()->get('listeners');
        }
        return self::$instance;
    }

    public function listen($listener, ...$args)
    {
        $listeners = isset(self::$config[$listener]) ? self::$config[$listener] : [];
        while ($listeners) {
            [$class, $func] = array_shift($listeners);
            try {
            	if(class_exists($class)){
		            $class::getInstance()->{$func}(...$args);
	            }else{
            		print_r("类不存在:$class");
	            }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }
}
