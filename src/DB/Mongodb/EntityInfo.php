<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/14 0014
 * Time: 9:47
 */

namespace DB\Mongodb;
use DocParse\DocParser;
use Tool\Tool;

/**
 * 实体信息管理
 * Class EntityInfo
 *
 * @package DB\Mongodb
 */
class EntityInfo
{

    private static $fieldCache = [];

    /**
     * 获取一个实体表的字段信息
     * @param $className
     *
     * @return bool|mixed|null
     */
    public static function getFieldInfo($className)
    {
        if(class_exists($className)){
            $info = Tool::getArrVal( $className ,self::$fieldCache);
            if ( !$info ) {
                $info = self::getClassInfo( $className );
                self::$fieldCache[$className] = $info;
            }

            return Tool::getArrVal('property',$info);
        }
    }

    /**
     * 获取类的方法注解数据
     * @param $className
     *
     * @return bool|null
     */
    public static function getMethodsInfo( $className )
    {
        $info = Tool::getArrVal( $className ,self::$fieldCache);
        if ( !$info ) {
            $info = self::getClassInfo( $className );
            self::$fieldCache[$className] = $info;
        }

        return Tool::getArrVal('methods',$info);
    }

    /**
     * 获取一个实体类的相关联其它实体的字段
     *
     * @param $className
     *
     * @return bool|null
     */
    public static function getEntityFieldInfo( $className )
    {
        $info = Tool::getArrVal( $className ,self::$fieldCache);
        if ( !$info ) {
            $info = self::getClassInfo( $className );
            self::$fieldCache[$className] = $info;
        }

        return Tool::getArrVal('entity',$info);
    }
    /**
     * 获取一个类的注解信息
     * @param $className
     *
     * @return mixed
     */
    public static function getClassInfo($className)
    {
        $reflection = new \ReflectionClass ( $className );
        //通过反射获取类的注释
        $doc = $reflection->getDocComment ();
        $data['class']=self::docParserInstance()->parse( $doc );
        $field = [];
        $entity = [];
        $properties=$reflection->getProperties();
        if($properties){
            foreach ( $properties as $property ) {
                if($property->isPublic()){
                    $p_doc = $property->getDocComment();
                    $name=$property->getName();

                    $field[$name]= self::docParserInstance()->parse( $p_doc);
                    $hasEntity = Tool::getArrVal( 'entity' , $field[$name] );
                    if($hasEntity){
                        $entity[$name] = $field[$name];
                    }
                }
                unset($property);
            }
            $data['property'] = $field;
            $data['entity'] =$entity;

        }
        unset($properties,$entity);
        $methodData = [];
        $methods = $reflection->getMethods();
        if ( $methods ) {
            foreach ( $methods as $method ) {
                $m_doc = $method->getDocComment();
                $name = $method->getName();
                $methodData[$name] = self::docParserInstance()->parse( $m_doc );
            }
            $data['methods'] = $methodData;
            unset($method);
        }
        unset($methodData , $methods);
        return $data;
    }

	/**
	 * 获取属性注解信息
	 * @param $className
	 *
	 * @return array
	 */
	public static function getPropertiesInfo( $className ) {
		$reflection = new \ReflectionClass ( $className );
		$properties=$reflection->getProperties();
		$field = [];
		if($properties){
			foreach ( $properties as $property ) {
				if($property->isPublic()){
					$p_doc = $property->getDocComment();
					$name=$property->getName();
					$field[$name]= self::docParserInstance()->parse( $p_doc);
				}
				unset($property);
			}
		}

		return $field;
	}


    public static $docParserInstance=null;

    /**
     * @return \DocParse\DocParser|null
     */
    public static function docParserInstance()
    {
        if ( is_null( self::$docParserInstance ) ) {
            self::$docParserInstance = new DocParser();
        }

        return self::$docParserInstance;
    }


}