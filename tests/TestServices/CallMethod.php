<?php

/**
 * Description of CallMethod
 *
 * @create 2017-10-19 17:59:06
 * @author hao
 */

namespace Bg\Container\Tests\TestServices;

class CallMethod
{
    
    public function getValue(InjectCallMethod $obj)
    {
        return $obj->getValue();
    }
    
    public function getAnotherValue(AbstractService $obj)
    {
        return $obj->getValue();
    }

}
