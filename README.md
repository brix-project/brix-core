# brix-core

```
composer install brixlab/core
```


## Write brix plugins

Create a /src/boostrap.php file

```php

class SomeFunctionality extends \Brix\Core\AbstractBrixCommand {


}

\Phore\Cli\CliDispatcher::addClass(SomeFunctionality::class, "some-functionality");

```


