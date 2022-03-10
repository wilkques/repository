<?php

namespace Wilkques\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

abstract class Repository implements \JsonSerializable, \ArrayAccess
{
    /** @var Builder|Model|EloquentBuilder|Collection|LengthAwarePaginator */
    protected $entity;
    /** @var int */
    protected $prePage = 10;
    /** @var int */
    protected $currentPage = 1;
    /** @var string */
    protected $pageName = 'page';
    /** @var Container */
    protected $container;

    /**
     * Force Output Methods
     *  
     * @var array 
     * 
     * @see [LaravelOffice-Collections](https://laravel.com/docs/8.x/collections) 
     * @see [LaravelOffice-Eloquent-Collections](https://laravel.com/docs/8.x/eloquent-collections)
     * @see [LaravelOffice-Queries-Builder](https://laravel.com/docs/8.x/queries#insert-statements)
     */
    protected $methods = [
        'toArray', 'toJson', 'all', 'avg', 'contains', 'containsStrict', 'count', 'dd', 'dump', 'isObject', 'isNotObject',
        'duplicates', 'duplicatesStrict', 'has', 'isEmpty', 'isNotEmpty', 'trashed', 'isBool', 'isNotBool',
        'max', 'median', 'min', 'sum', 'insertGetId', 'isNull', 'isNotNull', 'isNumeric', 'isNotNumeric'
    ];

    /**
     * @param Builder|Model|EloquentBuilder|Collection|LengthAwarePaginator $entity
     * @param Container|null $container
     */
    public function __construct($entity = null, Container $container = null)
    {
        $this->setContainer($container)->setEntity($entity);
    }

    /**
     * @param Container|null $container
     * 
     * @return static
     */
    public function setContainer(Container $container = null)
    {
        $this->container = $container ?: new Container;

        return $this;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
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
     * @param string $method
     * 
     * @return bool
     */
    private function methodCheck(string $method)
    {
        return method_exists($this->getContainer()->make(get_called_class()), $method) ||
            method_exists($this->getEntity(), $method);
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
            } else if (is_string($method) && $this->methodCheck($method)) {
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
     * @param int|string $currentPage
     * @param int|string $prePage
     * @param string $pageName
     * @param array $column
     * 
     * @return static
     */
    public function paginations($currentPage = null, $prePage = null, string $pageName = null, array $column = ['*'])
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
        return $this[$offset] ?? $this->getEntity()->offsetGet($offset);
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
        if (property_exists($this, $offset))
            $this->{$offset} = $value;
        else
            $this->getEntity()->offsetSet($offset, $value);
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
        return (!property_exists($this, $offset) && !is_null($this->{$offset})) ||
            $this->getEntity()->offsetExists($offset);
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this[$offset]);

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
        if (!$key) return;

        if (property_exists($this, $key)) {
            return $this[$key];
        }

        if (!$this->getEntity()) return null;

        if ($this->getEntity()->offsetExists($key)) {
            return $this->getEntity()[$key];
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, $value)
    {
        if (property_exists($this, $key)) {
            $this->{$key} = $value;
        } else if ($this->getEntity() instanceof Collection) {
            $this->getEntity()->map(function ($model) use ($key, $value) {
                if ($model->offsetExists($key))
                    $model->__set($key, $value);

                return $model;
            });
        } else if ($this->getEntity()->offsetExists($key) || $this->getEntity() instanceof Model) {
            $this->getEntity()->__set($key, $value);
        }
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        $arguments = array_map(fn ($argument) => $this->callArguments($argument), $arguments);

        if (in_array($method, $this->getForceMethods()))
            return $this->getEntity()->{$method}(...$arguments);

        $this->setEntity($this->entityHandle($method, $arguments));

        $this->getContainer()->rebinding(get_called_class(), fn () => $this);

        return $this;
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return mixed
     */
    protected function entityHandle(string $method, array $arguments)
    {
        if ($this->getEntity() instanceof \Illuminate\Pagination\LengthAwarePaginator && !method_exists(\Illuminate\Pagination\LengthAwarePaginator::class, $method)) {
            $this->getEntity()->{$method}(...$arguments);

            return $this->getEntity();
        }

        return $this->getEntity()->{$method}(...$arguments);
    }

    /**
     * @param mixed $argument
     * 
     * @return mixed
     */
    private function callArguments($argument)
    {
        return $argument instanceof $this ? $argument->getEntity() : $argument;
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
