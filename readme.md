一个简易的服务容器模式（LoC）的实现，本包将实现以下功能：

- 对象实例化
- 服务绑定
- 依赖注入

## 快速入门

### 获取服务实例

```php
$app = Bg\Container\Container::getInstance();
```

### 实例化一个对象

```php
$app->make(\App\Example::class);
```

### 依赖注入

假如现在有一个类 \App\Example ，其依赖于类 \App\Inject ，代码如下：

```php
namespace App;

class Example
{

    private $obj;
    
    public function __construct(Inject $obj)
    {
        $this->obj = $obj;
    }
    
    
    public function getValue()
    {
        return $this->obj->getValue();
    }
}



class Inject
{
    public function getValue()
    {
        return 'inject';
    }
}

```

类 \App\Example 的构造函数需要 \App\Inject 的实例参数，使用服务容器，我们不需要手工对 \App\Inject 实例化，服务容器将替我们完成这项工作：

```php
$example = $app->make(\App\Example::class);
echo($example->getValue());
```

以上例子将输出： ==inject==