<?php

/**
 * 容器测试
 *
 * @create 2017-8-8 15:52:08
 * @author hao
 */

namespace Bg\Container\Tests;

use PHPUnit\Framework\TestCase;
use Bg\Container\Container;

class ContainerTest extends TestCase
{
    
    private $container;
    
    public function setUp()
    {
        parent::setUp();
        
        $this->container = Container::getInstance();
    }
    
    public function testMakeWithoutBind()
    {
        $instance = $this->container->make(TestServices\SingleService::class);
        $this->assertEquals($instance->getValue(), 'single');
    }
    
    public function testMakeWithBind()
    {
        $this->container->bind(TestServices\AbstractService::class, TestServices\Concrete2::class);
        
        $instance = $this->container->make(TestServices\AbstractService::class);
        $this->assertEquals($instance->getValue(), 'concrete2');
    }
    
    public function testMakeDoubleBind()
    {
        $this->container->bind(TestServices\AbstractService::class, TestServices\Concrete1::class);
        $this->container->bind(TestServices\Concrete1::class, function($app, $parameters){
            return $app->make(TestServices\SubConcrete1::class, $parameters);
        });
        
        $instance = $this->container->make(TestServices\AbstractService::class, ['test']);
        $this->assertEquals($instance->getValue(), 'test');
    }
    
    public function testMakeWithInject()
    {
        $this->container->bind(TestServices\AbstractService::class, TestServices\Concrete3::class);
        $instance = $this->container->make(TestServices\AbstractService::class);
        $this->assertEquals($instance->getValue(), 'inject');
    }
    
    public function testCallMethod()
    {
        $instance = $this->container->make(TestServices\CallMethod::class);
        $this->assertEquals($this->container->callMethod([$instance, 'getValue']), 'call method');
    }
    
    public function testCallMethodWithBind()
    {
        $this->container->bind(TestServices\AbstractService::class, TestServices\InjectCallMethod::class);
        $instance = $this->container->make(TestServices\CallMethod::class);
        $this->assertEquals($this->container->callMethod([$instance, 'getValue']), 'call method');
    }
}
