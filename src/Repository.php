<?php

namespace Wilkques\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

abstract class Repository implements \JsonSerializable, \ArrayAccess, \Countable, \IteratorAggregate
{
    /** @var Builder|Model|EloquentBuilder|Collection|LengthAwarePaginator */
    protected $entity;
    /** @var Builder|Model|EloquentBuilder|Collection|LengthAwarePaginator */
    protected $relations;
    /** @var int */
    protected $prePage = 10;
    /** @var int */
    protected $currentPage = 1;
    /** @var string */
    protected $pageName = 'page';
    /** @var array */
    protected static $resolvers = [];
    /** @var array */
    protected static $relationResolvers = [];

    /**
     * Force Output Methods
     *  
     * @var array 
     * 
     * @see [LaravelOffice-Collections](https://laravel.com/docs/master/collections) 
     * @see [LaravelOffice-Eloquent-Collections](https://laravel.com/docs/master/eloquent-collections)
     * @see [LaravelOffice-Queries-Builder](https://laravel.com/docs/master/queries#insert-statements)
     * @see [LaravelOffice-pagination](https://laravel.com/docs/master/pagination)
     */
    protected $methods = [
        'toArray', 'toJson', 'all', 'avg', 'contains', 'containsStrict', 'count', 'dd', 'dump', 'isObject', 'isNotObject',
        'duplicates', 'duplicatesStrict', 'has', 'isEmpty', 'isNotEmpty', 'trashed', 'isBool', 'isNotBool',
        'max', 'median', 'min', 'sum', 'insertGetId', 'isNull', 'isNotNull', 'isNumeric', 'isNotNumeric'
    ];

    /**
     * @param Builder|Model|EloquentBuilder|Collection|LengthAwarePaginator $entity
     */
    public function __construct($entity = null)
    {
        $this->boot($entity);
    }

    /**
     * @param Builder|Model|EloquentBuilder|Collection|LengthAwarePaginator $entity
     * 
     * @return static
     */
    protected function boot($entity)
    {
        static::resolverFor('base', $entity);

        return $this->setEntity($entity);
    }

    /**
     * @param Builder|Model|EloquentBuilder|Collection|LengthAwarePaginator $entity
     * 
     * @return static
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return Builder|Model|EloquentBuilder|Collection|LengthAwarePaginator
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param Builder|Model|EloquentBuilder|Collection|LengthAwarePaginator $relations
     * 
     * @return static
     */
    public function setRelations($relations)
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * @return Builder|Model|EloquentBuilder|Collection|LengthAwarePaginator
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @param string|array $method
     * 
     * @return static
     */
    public function setForceMethods()
    {
        $methods = func_get_args();

        array_map(function ($method) {
            if (is_array($method)) {
                $this->setForceMethods(...$method);
            } else if (is_string($method)) {
                $this->methods[] = $method;
            } else {
                throw new \UnexpectedValueException("setForceMethods Arguments must be string or array");
            }
        }, $methods);

        return $this;
    }

    /**
     * @return array
     */
    public function getForceMethods()
    {
        return $this->methods;
    }

    /**
     * @param integer|null $currentPage
     * 
     * @return static
     */
    public function setCurrentPage(int $currentPage = null)
    {
        $this->currentPage = $currentPage;

        return $this;
    }


    /**
     * @return integer
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @param integer|null $prePage
     * 
     * @return static
     */
    public function setPrePage(int $prePage = null)
    {
        $this->prePage = $prePage;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPrePage()
    {
        return $this->prePage;
    }

    /**
     * @param string $pageName
     * 
     * @return static
     */
    public function setPageName(string $pageName = 'page')
    {
        $this->pageName = $pageName;

        return $this;
    }

    /**
     * @return string
     */
    public function getPageName()
    {
        return $this->pageName;
    }

    /**
     * @param array $column
     * @param int|string $currentPage
     * @param int|string $prePage
     * @param string $pageName
     * 
     * @return static
     */
    public function paginate(array $column = ['*'], $currentPage = null, $prePage = null, string $pageName = null)
    {
        $result = $this->getEntity()->paginate(
            $prePage ?: $this->getPrePage(),
            $column,
            $pageName ?: $this->getPageName(),
            $currentPage ?: $this->getCurrentPage()
        );

        static::resolverFor("paginate", $result);

        return $this->setEntity($result);
    }

    /**
     * @param array $column
     * @param int|string $currentPage
     * @param int|string $prePage
     * @param string $pageName
     * 
     * @return static
     */
    public function simplePaginate(array $column = ['*'], $currentPage = null, $prePage = null, string $pageName = null)
    {
        $result = $this->getEntity()->simplePaginate(
            $prePage ?: $this->getPrePage(),
            $column,
            $pageName ?: $this->getPageName(),
            $currentPage ?: $this->getCurrentPage()
        );

        static::resolverFor("simplePaginate", $result);

        return $this->setEntity($result);
    }

    /**
     * @param array $column
     * @param int|string $currentPage
     * @param int|string $prePage
     * @param string $pageName
     * 
     * @return static
     */
    public function cursorPaginate(array $column = ['*'], $currentPage = null, $prePage = null, string $pageName = null)
    {
        $result = $this->getEntity()->cursorPaginate(
            $prePage ?: $this->getPrePage(),
            $column,
            $pageName ?: $this->getPageName(),
            $currentPage ?: $this->getCurrentPage()
        );

        static::resolverFor("cursorPaginate", $result);

        return $this->setEntity($result);
    }

    /**
     * @return static
     */
    public function enableQueryLog()
    {
        DB::enableQueryLog();

        return $this;
    }

    /**
     * @return array
     */
    public function getQueryLog()
    {
        return DB::getQueryLog();
    }

    /**
     * @return string
     */
    public function getLastQuery()
    {
        $queries = $this->getQueries();

        return end($queries);
    }

    /**
     * @return array
     */
    public function getQueries()
    {
        return array_map(function ($queryLog) {
            $stringSQL = str_replace('?', '"%s"', $queryLog['query']);

            return sprintf($stringSQL, ...$queryLog['bindings']);
        }, $this->getQueryLog());
    }

    /**
     * @return static
     */
    public function beginTransaction()
    {
        DB::beginTransaction();

        return $this;
    }

    /**
     * @return static
     */
    public function commit()
    {
        DB::commit();

        return $this;
    }

    /**
     * @return static
     */
    public function rollback()
    {
        DB::rollback();

        return $this;
    }

    /**
     * @return int
     */
    public function transactionLevel()
    {
        return DB::transactionLevel();
    }

    /**
     * @return bool
     */
    public function isTransaction()
    {
        return $this->transactionLevel() != 0;
    }

    /**
     * @param string|callable|\Exception $error
     * 
     * @throws \Exception|\UnexpectedValueException|\Wilkques\Repositories\Exceptions\RepositoryException
     * 
     * @return static
     */
    public function throw($error = 'Data not exists')
    {
        if (($this->getEntity() instanceof Collection && $this->getEntity()->isNotEmpty()) || $this->isNotNull())
            return $this;

        if (is_callable($error)) {
            if (!$error instanceof \Exception)
                throw new \UnexpectedValueException("CallBack must be return exception object");

            throw $error($this);
        }

        if (is_string($error))
            throw new \Wilkques\Repositories\Exceptions\RepositoryException($error);

        if ($error instanceof \Exception)
            throw $error;

        throw new \UnexpectedValueException("Throw method first Arguments must be string or callable or exception");
    }

    /**
     * @param string|callable|\Exception $error
     * 
     * @throws \Exception|\UnexpectedValueException|\Wilkques\Repositories\Exceptions\RepositoryException
     * 
     * @return static
     */
    public function throwDBRollback($error = 'Data not exists')
    {
        if (($this->getEntity() instanceof Collection && $this->getEntity()->isNotEmpty()) || $this->isNotNull())
            return $this;

        $this->rollback()->throw($error);
    }

    /**
     * @return bool
     */
    public function isNull()
    {
        return is_null($this->getEntity());
    }

    /**
     * @return bool
     */
    public function isNotNull()
    {
        return !$this->isNull();
    }

    /**
     * @return bool
     */
    public function isNumeric()
    {
        return is_numeric($this->getEntity());
    }

    /**
     * @return bool
     */
    public function isNotNumeric()
    {
        return !$this->isNumeric();
    }

    /**
     * @return bool
     */
    public function isBool()
    {
        return is_bool($this->getEntity());
    }

    /**
     * @return bool
     */
    public function isNotBool()
    {
        return !$this->isBool();
    }

    /**
     * @return bool
     */
    public function isObject()
    {
        return is_object($this->getEntity());
    }

    /**
     * @return bool
     */
    public function isNotObject()
    {
        return !$this->isObject();
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return is_object($this->getEntity()) ?
            $this->getEntity()->jsonSerialize() : (is_array($this->getEntity()) ?
                $this->getEntity() :
                json_decode($this->getEntity(), true));
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * 
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getEntity()->offsetGet($offset);
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * 
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        return $this->getEntity()->offsetSet($offset, $value);
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * 
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->getEntity()->offsetExists($offset);
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->getEntity()->offsetUnset($offset);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Convert the model to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @param string $key
     * 
     * @return mixed
     */
    public function __get(string $key)
    {
        if (property_exists($this, $key)) {
            return $this[$key];
        }

        $get = static::getResolverCallback(function ($abstract) use ($key) {
            if (method_exists($abstract, "offsetExists") && $abstract->offsetExists($key)) {
                return $abstract->__get($key);
            }

            return false;
        });

        return $get;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, $value)
    {
        if (property_exists($this, $key)) {
            $this->{$key} = $value;
        }

        static::getResolverCallback(function ($abstract) use ($key, $value) {
            if ($abstract instanceof Collection) {
                $abstract->map(function ($model) use ($key, $value) {
                    if ($model->offsetExists($key))
                        $model->__set($key, $value);

                    return $model;
                });
            }

            if ($abstract->offsetExists($key)) {
                $abstract->__set($key, $value);
            }
        });
    }

    /**
     * @param \Closure|null $callback
     * 
     * @return string
     */
    protected static function getResolverCallback(\Closure $callback = null)
    {
        foreach (static::$resolvers[static::class] as $method => $abstract) {
            if ($mixed = $callback($abstract, $method)) {
                break;
            }
        }

        return $mixed;
    }

    /**
     * Register a connection resolver.
     *
     * @param  string  $method
     * @param  mixed  $abstract
     * @return void
     */
    protected static function resolverFor($method, $abstract)
    {
        static::$resolvers[static::class][$method] = $abstract;
    }

    /**
     * Get the connection resolver for the given driver.
     *
     * @param  string  $method
     * @return mixed
     */
    protected static function getResolver(string $method)
    {
        return static::$resolvers[static::class][$method] ?? null;
    }

    /**
     * @return mixed
     */
    protected static function getLastResolver()
    {
        return end(static::$resolvers[static::class]);
    }

    /**
     * Register a connection resolver.
     *
     * @param  string  $method
     * @param  mixed  $abstract
     * @return void
     */
    protected static function relationsResolverFor($method, $abstract)
    {
        static::$relationResolvers[static::class][$method] = $abstract;
    }

    /**
     * Get the connection resolver for the given driver.
     *
     * @param  string  $method
     * @return mixed
     */
    protected static function getRelationsResolver(string $method)
    {
        return static::$relationResolvers[static::class][$method] ?? null;
    }

    /**
     * @return mixed
     */
    protected static function getLasRelationsResolver()
    {
        return end(static::$relationResolvers[static::class]);
    }

    /**
     * @param \Closure $callback
     * 
     * @return mixed|null
     */
    protected function hasPaginate(\Closure $callback)
    {
        if ($resolver = array_intersect_key(static::$resolvers[static::class], array_flip(['paginate', 'simplePaginate', 'cursorPaginate']))) {
            return $callback($resolver);
        }

        return null;
    }

    /**
     * @param string $method
     * 
     * @return string
     */
    protected function method(string $method)
    {
        $methods = [
            "set" => [
                'currentPage', 'prePage', 'pageName',
            ],
        ];

        foreach ($methods as $index => $item) {
            if (in_array($method, $item)) {
                $method = $index . ucfirst($method);

                break;
            }
        }

        return $method;
    }

    /**
     * @param string $method
     * @param string|int|bool $result
     * 
     * @return string|int|bool
     */
    protected function notAbstract(string $method, $result)
    {
        static::resolverFor($method, static::getLastResolver());

        return $result;
    }

    /**
     * @param string|null $method
     * 
     * @return mixed
     */
    protected function getAbstract(string $method = null)
    {
        if (empty(static::$resolvers)) {
            return $this->getEntity();
        }

        $resolver = $method ? static::getResolver($method) : static::getLastResolver();

        return $this->hasPaginate(fn ($resolver) => static::getResolver(key($resolver))) ?: $resolver;
    }

    /**
     * @param string $method
     * @param mixed $result
     * 
     * @return mixed|static
     */
    protected function forceCall(string $method, $result)
    {
        if (in_array($method, $this->getForceMethods())) {
            return $result;
        }

        if ($result instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
            static::relationsResolverFor($method, $result);

            return $this->setRelations($result);
        }

        if (!is_null($result) && !is_object($result)) {
            static::resolverFor($method, static::getLastResolver());

            $result = static::getLastResolver();
        }

        static::resolverFor($method, $result);

        return $this->setEntity($this->getAbstract($method));
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        $method = $this->method($method);

        if (method_exists($this, $method)) {
            return $this->{$method}(...$arguments);
        }
        
        if (!empty(static::$relationResolvers) && $this->getEntity() instanceof Model && $this->getEntity() != static::getResolver('base')) {
            $result = static::getLasRelationsResolver()->{$method}(...$arguments);

            static::relationsResolverFor($method, $result);

            return $this->setRelations($result);
        }

        $result = $this->getAbstract()->{$method}(...$arguments);

        return $this->forceCall($method, $result);
    }

    // public function __debugInfo()
    // {
    //     return [
    //         'resolver'  => static::$resolvers[static::class],
    //         'relations' => static::$relationResolvers[static::class],
    //     ];
    // }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $val) {
            $this->{$key} = $val;
        }
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->getEntity()->count() ?? count($this->getEntity()->toArray());
    }

    /**
     * @return mixed
     */
    public function getIterator()
    {
        $entity = $this->getEntity();

        if ($this->getEntity() instanceof Model) {
            $entity = $entity->toArray();
        }

        yield from $entity;
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments)
    {
        return (new static)->$method(...$arguments);
    }
}
