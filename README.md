# Phx Framework (PHP hero experience)
> A simple and efficient web framework

## Installation

Run the follow command using the composer:

```sh
composer require newpoow/phx-framework
```

## Documentation

Create the ``index.php`` file, and put this:

```php
require_once dirname(__DIR__).'/vendor/autoload.php';

Phx\Application::create()
    ->register(App\AppPackage::class)
    ->run();
```

Create the class ``AppPackage`` and put this:

```php
namespace App;

use Phx\Http\RouterInterface;
use Phx\Package;

class AppPackage extends Package
{
    public function drawRoutes(RouterInterface $router): void
    {
        $router->get('/', function () {
            echo "Hello Phx!";
        });

        $router->get('/{s:name}', function (string $name) {
            echo "Hello $name!";
        });
    }
}
```

Now execute:

```sh
php -S localhost:8000
```
