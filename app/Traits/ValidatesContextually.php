<?php

namespace App\Traits;

use App\Webstuhl\QualityControl;

trait ValidatesContextually
{

    /**
     * @return QualityControl
     */
    abstract public function getQualityControl();

    public function getValidationRulesForContext($context = '__default', $allRulesTogether = true, $input = [])
    {
        $qc = $this->getQualityControl();

        if ($allRulesTogether) {
            return $qc->getRules($context);
        }

        $dependentRules = [];
        $independentRules = [];
        foreach ($qc->getRules($context) as $property => $rules) {
            // These are the Illuminate\Validation\Rule based rules and are not dependent rules
            if (is_object($rules)) {
                continue;
            }

            if (is_string($rules)) {
                $rules = explode('|', $rules);
            }
            foreach ($rules as $instruction) {
                $instructions = explode(':', $instruction, 2);
                $rule = $instructions[0];
                $parameters = isset($instructions[1]) ? $instructions[1] : '';
                if ($this->isDependentRule($rule)) {
                    $dependentRules[$property][] = $rule . (strlen($parameters) ? ':' . $parameters : '');
                } else {
                    $independentRules[$property][] = $rule . (strlen($parameters) ? ':' . $parameters : '');
                }
            }
        }

        return [$independentRules, $dependentRules, $qc->getMessages()];
    }

    protected function getValidFilters(array $inputFilters)
    {
        $qc = $this->getValidationRulesForContext('filter');
        $filters = [];
        $rules = [];
        foreach ($qc as $property => $ruleset) {
            if (key_exists($property, $inputFilters) && !empty($inputFilters[$property]) && !shallow($inputFilters[$property])) {
                $filters[$property] = $inputFilters[$property];
                if (!is_array($ruleset)) {
                    $ruleset = explode('|', $ruleset);
                }
                if (is_array($inputFilters[$property])) {
                    if (isset($inputFilters[$property]['__between'])) {
                        if (count($inputFilters[$property]['__between']) != 2) {
                            unset($filters[$property]);
                            continue;
                        }
                        $filters[$property] = [];
                        if (!emptyish($inputFilters[$property]['__between'][0])) {
                            $filters[$property][] = $inputFilters[$property]['__between'][0];
                        }
                        if (!emptyish($inputFilters[$property]['__between'][1])) {
                            $filters[$property][] = $inputFilters[$property]['__between'][1];
                        }
                        if (empty($filters[$property])) {
                            unset($filters[$property]);
                            continue;
                        }
                    } elseif (isset($inputFilters[$property]['__exactly'])) {
                        $filters[$property] = $inputFilters[$property]['__exactly'];
                    } elseif (isset($inputFilters[$property]['__not'])) {
                        $filters[$property] = $inputFilters[$property]['__not'];
                    } elseif (isset($inputFilters[$property]['__not_exactly'])) {
                        $filters[$property] = $inputFilters[$property]['__not_exactly'];
                    }
                    foreach ($ruleset as &$rule) {
                        $rule = 'array_check:' . str_replace(',', '^^', $rule);
                    }
                }
                $rules[$property] = $ruleset;
            }
        }
        $validFilters = validator($filters, $rules)->valid();
//        foreach ($this->getActiveEagerLinks() as $eager_link) {
//            if (!empty($inputFilters[snake_case($eager_link)])) {
//                $eager_model_name = get_class($this->$eager_link()->getRelated());
//                $eager_model = new $eager_model_name;
//                $validFilters[$eager_link] = $eager_model->getFiltersViaDefaultValidationRules($inputFilters[snake_case($eager_link)]);
//            }
//        }
        $returnFilters = $returnFilters['__auto_eager_links'] = [];
        foreach ($validFilters as $property => $validFilter) {
//            if (in_array($property, $this->getActiveEagerLinks())) {
//                $returnFilters['__auto_eager_links'][$property] = $validFilters[$property];
//                continue;
//            }
            $returnFilters[$property] = [
                'filter' => $validFilter,
                'comparator' => $this->determineComparator($rules[$property], $property, $inputFilters[$property]),
                'property' => $property,
            ];
        }
        return $returnFilters;
    }

    /**
     * @param $rule
     * @return bool
     */
    public function isDependentRule($rule)
    {
        return false;
    }

    protected function determineComparator($rules, $property, $filterGiven)
    {
        if (method_exists($this, 'filter' . studly_case($property))) {
            return call_user_func_array([$this, 'filter' . studly_case($property)], [$filterGiven]);
        }
        if (!is_array($filterGiven) || !isset($filterGiven['__between'])) {
            if (isset($filterGiven['__exactly'])) {
                return 'equals';
            } elseif (isset($filterGiven['__not_exactly'])) {
                return 'not equals';
            }
            $negated = isset($filterGiven['__not']) ? 'not ' : '';
            return preg_match('/id_exists/', implode('|', $rules)) ? $negated . 'equals' : $negated . 'like';
        }
        if (!emptyish($filterGiven['__between'][0]) && !emptyish($filterGiven['__between'][1])) {
            return 'between';
        } elseif (!emptyish($filterGiven['__between'][0])) {
            return '>=';
        } else {
            return '<=';
        }
    }

}