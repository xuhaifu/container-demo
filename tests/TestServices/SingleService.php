<?php

/**
 * 可以直接实例化的类
 *
 * @create 2017-10-17 17:26:18
 * @author hao
 */

namespace Bg\Container\Tests\TestServices;

class SingleService
{
    
    public function getValue()
    {
        return 'single';
    }
}
