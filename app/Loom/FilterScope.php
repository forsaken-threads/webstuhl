<?php

namespace App\Loom;

use App\Contracts\Filter as FilterContract;
use Illuminate\Database\Eloquent\Builder;

class FilterScope implements FilterContract
{
    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $defaultValues = [];

    /**
     * @var array
     */
    protected $input = [];

    /**
     * @var array
     */
    protected $inputValues = [];

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|callable
     */
    protected $presentation;

    /**
     * @var array
     */
    protected $validationRules = [];

    /**
     * FilterScope constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->presentation = $name;
    }

    /**
     * @param Builder $query
     * @param $orTogether
     */
    public function applyFilter(Builder $query, $orTogether)
    {
        if ($orTogether) {
            $query->orWhere(function ($q) {
                /** @var Builder $q */
                $q->{$this->name}(...$this->inputValues);
            });
        } else {
            $query->{$this->name}(...$this->inputValues);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function presentFilter()
    {
        if (is_callable($this->presentation)) {
            return call_user_func($this->presentation, $this->input);
        }
        return $this->presentation;
    }

    /**
     * @param $argument
     * @param $default
     * @return $this
     */
    public function setArgumentDefault($argument, $default)
    {
        $this->defaultValues[$argument] = $default;
        return $this;
    }

    /**
     * @param string|callable $presentation
     * @return $this
     */
    public function setPresentation($presentation)
    {
        $this->presentation = $presentation;
        return $this;
    }

    /**
     * @param $validationRules
     * @return $this
     */
    public function setValidationRules($validationRules)
    {
        $this->validationRules = $validationRules;
        return $this;
    }

    /**
     * @param $givenInput
     * @return bool
     */
    public function validateAndSetInput($givenInput)
    {
        $testInput = [];
        foreach ($this->arguments as $argument) {
            if (key_exists($argument, $givenInput)) {
                $testInput[$argument] = $givenInput[$argument];
            } elseif (key_exists($argument, $this->defaultValues)) {
                $testInput[$argument] = $this->defaultValues[$argument];
            }
        }
        if (validator($testInput, $this->validationRules)->passes()) {
            $this->input = $testInput;
            $this->inputValues = array_values($testInput);
            return true;
        }
        return false;
    }

    /**
     * @param array ...$arguments
     * @return $this
     */
    public function withArguments(...$arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }
}