# Laravel Repository

[![Latest Stable Version](https://poser.pugx.org/wilkques/repositories/v/stable)](https://packagist.org/packages/wilkques/repositories)
[![License](https://poser.pugx.org/wilkques/repositories/license)](https://packagist.org/packages/wilkques/repositories)

## How to use

`composer require wilkques/repositories`

example

```php
namespace App\Repositories\UserRepository;

use App\User;
use Wilkques\Repositories\Repository;

class UserRepository extends Repository
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function whereName(string $name)
    {
        return $this->where("name", $name);
    }
}

// other class

use App\Repositories\UserRepository;
use App\User;

class UserController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index(Request $request)
    {
        $user = User::where("name", $request->name)->get()->toArray();

        // same

        $user = $this->userRepository->where("name", $request->name)->get()->toArray();

        // same

        $user = $this->userRepository->whereName($request->name)->get()->toArray();
    }
}
```

## Methods

1. `\Illuminate\Database\Query\Builder` ALL Methods
1. `\Illuminate\Database\Eloquent\Model` ALL Methods
1. `\Illuminate\Database\Eloquent\Builder` ALL Methods
1. `\Illuminate\Database\Eloquent\Collection` ALL Methods
1. `\Illuminate\Pagination\LengthAwarePaginator` ALL Methods
1. `\Illuminate\Support\Facades\DB` ALL Methods

|      Method           |               Description                |
|-----------------------|------------------------------------------|
|   `setForceMethods`   |               force output               |
|   `setCurrentPage`    |               now page                   |
|   `setPrePage`        |               prepage                    |
|   `setPageName`       |               page name                  |
|   `enableQueryLog`    |       same `\DB::enableQueryLog()`       |
|   `getQueryLog`       |       same `\DB::getQueryLog()`          |
|   `getQueries`        |           get all sql queries            |
|   `getLastQuery`      |           get last sql queries           |
|   `throw`             |            throws exception              |
|   `isNull`            |             same `is_null`               |
|   `isNotNull`         |             same `!is_null`              |
|   `isNumeric`         |             same `is_numeric`            |
|   `isNotNumeric`      |             same `!is_numeric`           |
|   `isBool`            |             same `is_bool`               |
|   `isNotBool`         |             same `!is_bool`              |
|   `isObject`          |             same `is_object`             |
|   `isNotObject`       |             same `!is_object`            |

## Reference
1. [Laravel](https://laravel.com/docs)
1. [Laravel API DOCS](https://laravel.com/api/master/index.html)
