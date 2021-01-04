<?php

declare(strict_types=1);

namespace PTFramework;

class Config
{
    private static $instance;

    private static $config = [];

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param $keys
     * @param null $default
     * @return null|mixed
     */
    public function get($keys, $default = null)
    {
        $keys = explode('.', strtolower($keys));
        if (empty($keys)) {
            return null;
        }
        $file = array_shift($keys);
        if (empty(self::$config[$file])) {
            if (! is_file(CONFIG_PATH . $file . '.php')) {
                return null;
            }
            self::$config[$file] = include CONFIG_PATH . $file . '.php';
        }
        $config = self::$config[$file];

        while ($keys) {
            $key = array_shift($keys);
            if (! isset($config[$key])) {
                $config = $default;
                break;
            }
            $config = $config[$key];
        }

        return $config;
    }

	/**
	 * 监听类型配置
	 * @return mixed|null
	 */
	public function getListener(){
	    return self::getInstance()->get('listeners',[]);
	}

	/**
	 * 服务器配置
	 * @return mixed|null
	 */
	public function getServersConfig(){
	    return self::getInstance()->get('servers');
	}
}
