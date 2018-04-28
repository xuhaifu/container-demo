<?php

/**
 * Description of SubConcrete1
 *
 * @create 2017-10-17 17:48:12
 * @author hao
 */

namespace Bg\Container\Tests\TestServices;

class SubConcrete1 extends Concrete1
{
    
    private $value = '';

    public function __construct($value = '')
    {
        $this->value = $value;
    }
    
    public function getValue()
    {
        return $this->value;
    }

}
