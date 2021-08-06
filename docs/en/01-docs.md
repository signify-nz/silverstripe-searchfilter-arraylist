# Documentation

## Examples
TODO: Provide some examples of normal usage.

## Implementing your own SearchFilter classes and/or modifiers
If your project provides new injectable `SearchFilter`s, you can mark them as supported by implementing `updateSupportedSearchFilterClasses` in an `Extension` class. If that SearchFilter has any modifiers, you will need to mark those as supported as well by implementing `updateSupportedModifiers`. Finally, implement the actual logic to determine whether the value in the `SearchFilterableArrayList` matches against your `SearchFilter` by implementing `updateFilterMatch`.

A skeleton implementation of these methods is below for your convenience.
```php
class SearchFilterableArrayListExtension extends Extension
{
    /**
     * Check if the field being checked matches with your project-specific SearchFilter class and/or modifiers.
     * 
     * @param bool &$fieldMatches True if $extractedValue matches any value in $searchFilter.
     * @param mixed $extractedValue The value of the item currently being checked.
     * @param SearchFilter $searchFilter The search filter to check against.
     * This contains the field name, and the value(s) to be checked against - as well as any modifiers.
     */
    public function updateFilterMatch(bool &$fieldMatches, $extractedValue, SearchFilter $searchFilter)
    {
        if (get_class($searchFilter === MySearchFilter::class)) {
            $values = $searchFilter->getValue();
            if (!is_array($values)) {
                $values = [$values];
            }
            foreach ($values as $value) {
                $hasMatch = /* Some boolean comparative logic between $extractedValue and $value here */;
                in_array('not', $searchFilter->getModifiers()) {
                    $hasMatch = !$hasMatch;
                }
                // To be consistent, the field matches if ANY value in the search filter matches the extracted value.
                if ($hasMatch) {
                    $fieldMatches = true;
                    return;
                }
            }
            // Explicitly mark the field as not matching if we haven't made a match yet.
            $fieldMatches = false;
        }
    }

    /**
     * Update the list of SearchFilter classes that SearchFilterableArrayList supports to include your project-specific SearchFilter.
     * 
     * @param string[] &$supportedClasses The SearchFilter classes supported by SearchFilterableArrayList
     */
    public function updateSupportedSearchFilterClasses(array &$supportedClasses)
    {
        $supportedClasses[] = MySearchFilter::class;
    }

    /**
     * Update the list of SearchFilter modifiers that SearchFilterableArrayList supports to include any custom modifiers in your project.
     * @param string[] &$supportedModifiers The SearchFilter modifiers supported by SearchFilterableArrayList
     */
    public function updateSupportedModifiers(array &$supportedModifiers)
    {
        $supportedModifiers[] = 'mymodifier';
    }
}
```
