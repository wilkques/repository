# Laravel Repository

## How to use

example

```php
namespace App\Repositories\UserRepository;

use App\User;
use Wilkques\Repository;

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

class UserController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function index(Request $request)
    {
        $this->userRepository->where("name", $request->name)->first();

        // or

        $this->userRepository->whereName($request->name)->first();
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
|   `paginations`       |same `\Illuminate\Pagination\LengthAwarePaginator` paginate|
|   `enableQueryLog`    |       same `\DB::enableQueryLog()`       |
|   `setForceMethods`   |       same `\DB::getQueryLog()`          |
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