<?php

/**
 * Description of Container
 *
 * @create 2017-10-11 17:56:01
 * @author hao
 */

namespace Bg\Container;

use Closure;
use ArrayAccess;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use Bg\Container\Exception\BindingResolutionException;

class Container implements ArrayAccess
{
    
    /**
     * 当前类的实例
     *
     * @var static
     */
    protected static $instance;
    
    protected $instances = [];
    
    protected $buildStack = [];
    
    protected $bindings = [];
    
    
    /**
     * 将具体类绑定到抽象接口
     *
     * @param  string|array  $abstract
     * @param  \Closure|string|null  $concrete
     * @param  bool  $shared    是否以单例模式绑定
     * @return void
     */
    public function bind($abstract, $concrete = null, $shared = false)
    {
        $abstract = $this->normalize($abstract);

        $concrete = $this->normalize($concrete);

        // 剔除原来实例化的单例
        unset($this->instances[$abstract]);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        // 将绑定的实现类以及是否单例条件设为数组写入 bindings
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }
    
    /**
     * 创建一个类的实例
     * 
     * @param type $concrete
     * @param type $parameters
     * @return Object
     * @throws BindingResolutionException
     */
    public function build($concrete, $parameters = [])
    {
        // 如果传入的$concrete是回调函数，则直接调用
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }
        
        $reflector = new ReflectionClass($concrete);

        if (! $reflector->isInstantiable()) {
            throw new BindingResolutionException('实例化'. $concrete .'失败');
        }
        
        
        $constructor = $reflector->getConstructor();

        // 如果该类没有构造函数，则直接返回实例
        if (is_null($constructor)) {

            return new $concrete;
        }

        // 获取构造函数的参数
        $dependencies = $constructor->getParameters();

        // 将用户传递的参数进行解析，将索引数组转为关联数组
        $parameters = $this->keyParametersByArgument(
            $dependencies, $parameters
        );

        $instances = $this->getDependencies(
            $dependencies, $parameters
        );


        return $reflector->newInstanceArgs($instances);
    }
    
    /**
     * 从容器中创建一个实例
     * 本方法将判断接口是否存在单例，存在单例则直接返回
     * 然后判断绑定的具体类，未绑定具体类则直接实例化
     * 绑定了具体类的使用具体类生成实例
     * 
     * @param type $abstract
     * @param type $parameters
     * @return type
     */
    public function make($abstract, $parameters = [])
    {
        $abstract = $this->normalize($abstract);
        
        // 如果已创建实例，则直接返回
        if(isset($this->instances[$abstract])){
            return $this->instances[$abstract];
        }
        
        $concrete = $this->getConcrete($abstract);
        
        // 如果 $concrete 和 $abstract 相等，或者 $concrete 是回调函数
        // 则 $concrete 可以直接通过 build 方法实例化，否则需要 make 方法进行解析
        // make 方法需要判断一下是否存在已有的单例实例
        if($this->isBuildable($concrete, $abstract)){
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        // 如果该接口是单例模式调用，则记录实例到 instances
        if($this->isShared($abstract)){
            $this->instances[$abstract] = $object;
        }
        
        return $object;
    }
    
    /**
     * 调用一个方法
     * 
     * @param callable $callback 要调用的方法
     * @param array $parameters
     * @return mixed
     */
    public function callMethod(callable $callback, $parameters = []){
        $reflector = $this->getCallReflector($callback);
        
        // 获取函数的参数
        $dependencies = $reflector->getParameters();

        $parameters = $this->getDependencies(
            $dependencies, $parameters
        );


        return call_user_func_array($callback, $parameters);
    }

    /**
     * 获取一个方法的反射对象
     *
     * @param  callable|string  $callback
     * @return \ReflectionFunctionAbstract
     */
    protected function getCallReflector($callback)
    {
        if (is_string($callback) && strpos($callback, '::') !== false) {
            $callback = explode('::', $callback);
        }

        if (is_array($callback)) {
            return new ReflectionMethod($callback[0], $callback[1]);
        }

        return new ReflectionFunction($callback);
    }

    /**
     * 验证 $concrete 可以直接被 build 方法实例化
     *
     * @param  mixed   $concrete
     * @param  string  $abstract
     * @return bool
     */
    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }
    
    
    /**
     * 检查一个类是否是单例调用
     *
     * @param  string  $abstract
     * @return bool
     */
    public function isShared($abstract)
    {
        $abstract = $this->normalize($abstract);

        // 如果存在单例，则直接返回 true
        if (isset($this->instances[$abstract])) {
            return true;
        }

        if (! isset($this->bindings[$abstract]['shared'])) {
            return false;
        }

        return $this->bindings[$abstract]['shared'] === true;
    }




    /**
     * 从一个抽象服务中获取被绑定的具体服务
     *
     * @param  string  $abstract
     * @return mixed   $concrete
     */
    protected function getConcrete($abstract)
    {
        // 如果该抽象没有绑定具体类，则直接返回
        if (! isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }
    
    
    /**
     * 如果用户传入了参数数组，将索引数组转为关联数组
     * 与方法的参数列表对应
     *
     * @param  array  $dependencies
     * @param  array  $parameters
     * @return array
     */
    protected function keyParametersByArgument(array $dependencies, array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (is_numeric($key)) {
                unset($parameters[$key]);

                $parameters[$dependencies[$key]->name] = $value;
            }
        }

        return $parameters;
    }

    /**
     * 解析通过 ReflectionMethod::getParameters 返回的方法参数
     * 实例化需要注入的参数，未传入值的设置为默认值
     *
     * @param  array  $parameters ReflectionMethod::getParameters 获取的参数数组
     * @param  array  $primitives 用户传入的参数数组
     * @return array
     */
    protected function getDependencies(array $parameters, array $primitives = [])
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            // 如果 $primitives 传入了对应参数，则直接使用
            // 如果该参数不是类实例，尝试获取默认值
            // 如果该参数是类实例，则获取实例
            if (array_key_exists($parameter->name, $primitives)) {
                $dependencies[] = $primitives[$parameter->name];
            } elseif (is_null($dependency)) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                $dependencies[] = $this->resolveClass($parameter);
            }
        }

        return $dependencies;
    }
    
    

    /**
     * 获取自身单例的实例
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * 解析一个非类实例的参数
     *
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function resolveNonClass(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {// 如果构造方法中存在默认值，则直接使用默认值
            return $parameter->getDefaultValue();
        }

        // 构造方法的参数没有默认值，且没有传递参数，抛出异常
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new BindingResolutionException($message);
    }

    /**
     * 解析一个类实例的参数
     *
     * @param  \ReflectionParameter  $parameter
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->make($parameter->getClass()->name);
        }

        // 捕获 BindingResolutionException 异常，若参数存在默认值，直接获取默认值
        // 如果没有默认值，则抛出异常
        catch (BindingResolutionException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }

            throw $e;
        }
    }

    /**
     * 删除类名最前方的反斜杠
     *
     * @param  mixed  $classname
     * @return mixed
     */
    protected function normalize($classname)
    {
        return is_string($classname) ? ltrim($classname, '\\') : $classname;
    }


    /**
     * Determine if a given offset exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->bound($key);
    }

    /**
     * Get the value at a given offset.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->make($key);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        // If the value is not a Closure, we will make it one. This simply gives
        // more "drop-in" replacement functionality for the Pimple which this
        // container's simplest functions are base modeled and built after.
        if (! $value instanceof Closure) {
            $value = function () use ($value) {
                return $value;
            };
        }

        $this->bind($key, $value);
    }

    /**
     * Unset the value at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $key = $this->normalize($key);

        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
    }

    /**
     * Dynamically access container services.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }
}
