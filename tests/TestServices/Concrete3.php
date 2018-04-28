<?php

/**
 * Description of Concrete3
 *
 * @create 2017-10-17 17:58:25
 * @author hao
 */

namespace Bg\Container\Tests\TestServices;

class Concrete3 extends AbstractService
{
    private $valueObj;
    
    public function __construct(InjectService $obj)
    {
        $this->valueObj = $obj;
    }
    
    public function getValue()
    {
        return $this->valueObj->getValue();
    }

}
