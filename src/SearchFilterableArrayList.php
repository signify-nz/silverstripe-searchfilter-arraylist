<?php

namespace Signify\SearchFilterArrayList;

use LogicException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\Filters\EndsWithFilter;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\ORM\Filters\GreaterThanFilter;
use SilverStripe\ORM\Filters\GreaterThanOrEqualFilter;
use SilverStripe\ORM\Filters\LessThanFilter;
use SilverStripe\ORM\Filters\LessThanOrEqualFilter;
use SilverStripe\ORM\Filters\PartialMatchFilter;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\ORM\Filters\StartsWithFilter;

class SearchFilterableArrayList extends ArrayList
{
    /**
     * Find the first item of this list where the given key = value
     * Note that search filters can also be used, but dot notation is not respected.
     *
     * @inheritdoc
     * @link https://docs.silverstripe.org/en/4/developer_guides/model/searchfilters/
     */
    public function find($key, $value)
    {
        return $this->filter($key, $value)->first();
    }

    /**
     * Filter the list to include items with these characteristics.
     * Note that search filters can also be used, but dot notation is not respected.
     *
     * @inheritdoc
     * @link https://docs.silverstripe.org/en/4/developer_guides/model/searchfilters/
     */
    public function filter()
    {
        $filters = call_user_func_array([$this, 'normaliseFilterArgs'], func_get_args());
        return $this->filterOrExclude($filters);
    }

    /**
     * Return a copy of this list which contains items matching any of these characteristics.
     * Note that search filters can also be used, but dot notation is not respected.
     *
     * @inheritdoc
     * @link https://docs.silverstripe.org/en/4/developer_guides/model/searchfilters/
     */
    public function filterAny()
    {
        $filters = call_user_func_array([$this, 'normaliseFilterArgs'], func_get_args());
        return $this->filterOrExclude($filters, true, true);
    }

    /**
     * Return a copy of the list excluding any items that have all of these characteristics
     * Note that search filters can also be used, but dot notation is not respected.
     *
     * @inheritdoc
     * @link https://docs.silverstripe.org/en/4/developer_guides/model/searchfilters/
     */
    public function exclude()
    {
        $filters = call_user_func_array([$this, 'normaliseFilterArgs'], func_get_args());
        return $this->filterOrExclude($filters, false);
    }

    /**
     * Return a copy of the list excluding any items that have any of these characteristics
     * Note that search filters can also be used, but dot notation is not respected.
     *
     * @link https://docs.silverstripe.org/en/4/developer_guides/model/searchfilters/
     */
    public function excludeAny(): static
    {
        $filters = call_user_func_array([$this, 'normaliseFilterArgs'], func_get_args());
        return $this->filterOrExclude($filters, false, true);
    }

    /**
     * Apply the appropriate filtering or excluding
     *
     * @param array $filters
     * @return static
     */
    protected function filterOrExclude(array $filters, bool $inclusive = true, bool $any = false): static
    {
        $itemsToKeep = [];
        $searchFilters = [];

        foreach ($filters as $filterKey => $filterValue) {
            $searchFilter = $this->createSearchFilter($filterKey, $filterValue);
            $searchFilters[$filterKey] = $searchFilter;
        }

        foreach ($this->items as $item) {
            $matches = [];
            foreach ($filters as $filterKey => $filterValue) {
                $searchFilter = $searchFilters[$filterKey];
                $hasMatch = $this->checkValueMatchesSearchFilter($searchFilter, $item);
                $matches[$hasMatch] = 1;
                // If this is excludeAny or filterAny and we have a match, we can stop looking for matches.
                if ($any && $hasMatch) {
                    break;
                }
            }
            // filterAny or excludeAny allow any true value to be a match; filter or exclude require any false value
            // to be a mismatch.
            $isMatch = $any ? isset($matches[true]) : !isset($matches[false]);
            // If inclusive (filter) and we have a match, or exclusive (exclude) and there is NO match, keep the item.
            if (($inclusive && $isMatch) || (!$inclusive && !$isMatch)) {
                $itemsToKeep[] = $item;
            }
        }
        return static::create($itemsToKeep);
    }

    /**
     * Determine if an item is matched by a given SearchFilter.
     *
     * Regex with explicitly casted strings is used for many of these checks, which allows for things like
     * '1' to match true, without 'abcd' matching true. This can be useful for things like checkboxes which
     * will often return '1' or '0', but we don't want 'abcd' to match against the truthy '1', nor a raw true
     * value.
     *
     * Dot notation is not respected (if you try to filter against "Field.Count", it will be searching for a
     * field or array key literally called "Field.Count". This is consistent with the behaviour of ArrayList).
     *
     * @todo: Consider respecting dot notation in the future.
     *
     * @param SearchFilter $searchFilter
     * @param mixed $item
     * @return bool
     */
    protected function checkValueMatchesSearchFilter(SearchFilter $searchFilter, $item): bool
    {
        $modifiers = $searchFilter->getModifiers();
        $regexSensitivity = in_array('nocase', $modifiers) ? 'i' : '';
        $negated = in_array('not', $modifiers);
        $field = $searchFilter->getFullName();
        $extractedValue = $this->extractValue($item, $field);
        $extractedValueString = (string)$extractedValue;
        $values = $searchFilter->getValue();
        if (!is_array($values)) {
            $values = [$values];
        }
        $fieldMatches = false;
        foreach ($values as $value) {
            $value = (string)$value;
            $regexSafeValue = preg_quote($value, '/');
            switch (get_class($searchFilter)) {
                case EndsWithFilter::class:
                    if (is_bool($extractedValue)) {
                        $doesMatch = false;
                    } else {
                        $doesMatch = preg_match(
                            '/' . $regexSafeValue . '$/u' . $regexSensitivity,
                            $extractedValueString
                        );
                    }
                    break;
                case ExactMatchFilter::class:
                    $doesMatch = preg_match(
                        '/^' . $regexSafeValue . '$/u' . $regexSensitivity,
                        $extractedValueString
                    );
                    break;
                case GreaterThanFilter::class:
                    $doesMatch = $extractedValueString > $value;
                    break;
                case GreaterThanOrEqualFilter::class:
                    $doesMatch = $extractedValueString >= $value;
                    break;
                case LessThanFilter::class:
                    $doesMatch = $extractedValueString < $value;
                    break;
                case LessThanOrEqualFilter::class:
                    $doesMatch = $extractedValueString <= $value;
                    break;
                case PartialMatchFilter::class:
                    $doesMatch = preg_match(
                        '/' . $regexSafeValue . '/u' . $regexSensitivity,
                        $extractedValueString
                    );
                    break;
                case StartsWithFilter::class:
                    if (is_bool($extractedValue)) {
                        $doesMatch = false;
                    } else {
                        $doesMatch = preg_match(
                            '/^' . $regexSafeValue . '/u' . $regexSensitivity,
                            $extractedValueString
                        );
                    }
                    break;
                default:
                    // This will only be reached if an Extension class added classes to
                    // getSupportedSearchFilterClasses(). We will let them handle matching
                    // against it in their implementation of updateFilterMatch.
                    continue 2; // continue the loop
            }

            // Respect "not" modifier.
            if ($negated) {
                $doesMatch = !$doesMatch;
            }
            // If any value matches, then we consider the field to have matched.
            if ($doesMatch) {
                $fieldMatches = true;
                break;
            }
        }

        // Allow developers to make their own changes (e.g. for unsupported SearchFilters or modifiers).
        $this->extend('updateFilterMatch', $fieldMatches, $extractedValue, $searchFilter);
        return $fieldMatches;
    }

    /**
     * Given a filter expression and value construct a {@see SearchFilter} instance
     *
     * @param string $filter E.g. `Name:ExactMatch:not:nocase`, `Name:ExactMatch`, `Name:not`, `Name`, etc...
     * @param mixed $value Value of the filter
     * @return SearchFilter
     * @see \SilverStripe\ORM\DataList::createSearchFilter
     */
    public function createSearchFilter($filter, $value)
    {
        // Field name is always the first component
        $fieldArgs = explode(':', $filter);
        $fieldName = array_shift($fieldArgs);
        $default = 'DataListFilter.default';

        // Inspect type of second argument to determine context
        $secondArg = array_shift($fieldArgs);
        $modifiers = $fieldArgs;
        if (!$secondArg) {
            // Use default SearchFilter if none specified. E.g. `->filter(['Name' => $myname])`
            $filterServiceName = $default;
        } else {
            // The presence of a second argument is by default ambiguous; We need to query
            // Whether this is a valid modifier on the default filter, or a filter itself.
            /** @var SearchFilter $defaultFilterInstance */
            $defaultFilterInstance = Injector::inst()->get($default);
            if (in_array(strtolower($secondArg), $defaultFilterInstance->getSupportedModifiers())) {
                // Treat second (and any subsequent) argument as modifiers, using default filter
                $filterServiceName = $default;
                array_unshift($modifiers, $secondArg);
            } else {
                // Second argument isn't a valid modifier, so assume is filter identifier
                $filterServiceName = "DataListFilter.{$secondArg}";
            }
        }
        // Explicitly don't allow unsupported modifiers instead of silently ignoring them.
        if (!empty($invalid = array_diff($modifiers, $this->getSupportedModifiers()))) {
            throw new LogicException('Unsupported SearchFilter modifier(s): ' . implode(', ', $invalid));
        }

        // Build instance
        $filter = Injector::inst()->create($filterServiceName, $fieldName, $value, $modifiers);
        // Explicitly don't allow unsupported SearchFilters instead of silently ignoring them.
        if (!in_array(get_class($filter), $this->getSupportedSearchFilterClasses())) {
            throw new LogicException('Unsupported SearchFilter class: ' . get_class($filter));
        }

        return $filter;
    }

    /**
     * Get the SearchFilter classes supported by this class.
     *
     * @return string[]
     */
    protected function getSupportedSearchFilterClasses(): array
    {
        $supportedClasses = [
            EndsWithFilter::class,
            ExactMatchFilter::class,
            GreaterThanFilter::class,
            GreaterThanOrEqualFilter::class,
            LessThanFilter::class,
            LessThanOrEqualFilter::class,
            PartialMatchFilter::class,
            StartsWithFilter::class,
        ];
        // Allow developers to add their own SearchFilter classes.
        $this->extend('updateSupportedSearchFilterClasses', $supportedClasses);
        return $supportedClasses;
    }

    /**
     * Get the SearchFilter modifiers supported by this class.
     *
     * @return string[]
     */
    protected function getSupportedModifiers(): array
    {
        $supportedModifiers = ['not', 'nocase', 'case'];
        // Allow developers to add their own modifiers.
        $this->extend('updateSupportedModifiers', $supportedModifiers);
        return $supportedModifiers;
    }
}
