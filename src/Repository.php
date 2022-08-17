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
    /** @var int */
    protected $prePage = 10;
    /** @var int */
    protected $currentPage = 1;
    /** @var string */
    protected $pageName = 'page';
    /** @var array */
    protected static $resolvers = [];

    /**
     * Force Output Methods
     *  
     * @var array 
     * 
     * @see [LaravelOffice-Collections](https://laravel.com/docs/master/collections) 
     * @see [LaravelOffice-Eloquent-Collections](https://laravel.com/docs/master/eloquent-collections)
     * @see [LaravelOffice-Queries-Builder](https://laravel.com/docs/master/queries#insert-statements)
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
    public function boot($entity)
    {
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
    public function paginations(array $column = ['*'], $currentPage = null, $prePage = null, string $pageName = null)
    {
        return $this->paginate(
            $prePage ?: $this->getPrePage(),
            $column,
            $pageName ?: $this->getPageName(),
            $currentPage ?: $this->getCurrentPage()
        );
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
     * @param string|callable|\Exception $error
     * 
     * @throws \Exception
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

        $get = static::getResolverCallback(function ($abstract, $method) use ($key) {
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
     * @param \Closure $callback
     * 
     * @return string
     */
    public static function getResolverCallback(\Closure $callback = null)
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
     * @param  string  $abstract
     * @param  mixed  $class
     * @return void
     */
    public static function resolverFor($method, $abstract)
    {
        static::$resolvers[static::class][$method] = $abstract;
    }

    /**
     * Get the connection resolver for the given driver.
     *
     * @param  string  $abstract
     * @return mixed
     */
    public static function getResolver($method)
    {
        return static::$resolvers[static::class][$method] ?? null;
    }

    /**
     * @param string $method
     * 
     * @return string
     */
    protected function method($method)
    {
        $methods = array(
            "set"       => [
                'currentPage', 'prePage'
            ],
        );

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
     * @param array $arguments
     * 
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        $method = $this->method($method);

        if (static::$resolvers) {
            foreach (static::$resolvers[static::class] as $abstract) {
                if (method_exists($abstract, $method)) {
                    break;
                }
            }
        } else {
            $abstract = $this->getEntity();
        }

        if (method_exists(static::class, $method)) {
            return $this->{$method}(...$arguments);
        }

        $runAbstract = $abstract->{$method}(...$arguments);

        if (!is_object($runAbstract)) {
            $runAbstract = end(static::$resolvers[static::class]);
        }

        static::resolverFor($method, $runAbstract);

        if (array_key_exists("paginate", static::$resolvers[static::class])) {
            $method = "paginate";
        }

        if (in_array($method, $this->getForceMethods())) {
            return static::$resolvers[static::class][$method];
        }

        return $this->setEntity(static::$resolvers[static::class][$method]);
    }

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
