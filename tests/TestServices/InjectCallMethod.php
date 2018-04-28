<?php

/**
 * Description of InjectCallMethod
 *
 * @create 2017-10-19 18:09:18
 * @author hao
 */

namespace Bg\Container\Tests\TestServices;

class InjectCallMethod extends AbstractService
{
    
    public function getValue()
    {
        return 'call method';
    }

}
