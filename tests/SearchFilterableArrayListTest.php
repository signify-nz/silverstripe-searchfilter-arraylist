<?php

namespace Signify\SearchFilterArrayList\Tests;

use SilverStripe\Dev\SapphireTest;
use Signify\SearchFilterArrayList\SearchFilterableArrayList;

class SearchFilterableArrayListTest extends SapphireTest
{
    protected $usesDatabase = false;

    protected $objects;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objects = [
            [
                'Title' => 'First Object',
                'NoCase' => 'CaSe SeNsItIvE',
                'CaseSensitive' => 'Case Sensitive',
                'StartsWithTest' => 'Test Value',
                'GreaterThan100' => 300,
                'LessThan100' => 50,
            ],
            [
                'Title' => 'Second Object',
                'NoCase' => 'case sensitive',
                'CaseSensitive' => 'case sensitive',
                'StartsWithTest' => 'Not Starts With Test',
                'GreaterThan100' => 101,
                'LessThan100' => 99,
            ],
            [
                'Title' => 'Third Object',
                'NoCase' => null,
                'StartsWithTest' => 'Does not start with test',
                'GreaterThan100' => 99,
                'LessThan100' => 99,
            ],
            [
                'Title' => 'Fourth Object',
                'StartsWithTest' => 'test value, but lower case',
                'GreaterThan100' => 100,
                'LessThan100' => 100,
            ],
        ];
    }

    /**
     * @useDatabase false
     */
    public function testFind()
    {
        $list = new SearchFilterableArrayList($this->objects);

        // These should all find the object indicated in the variable name.
        $findFirst = $list->find('Title', 'First Object');
        $findFirst2 = $list->find('StartsWithTest:StartsWith:nocase', 'Test');
        $findSecond = $list->find('StartsWithTest:StartsWith:not', 'Test');
        $findThird = $list->find('GreaterThan100:LessThan', 100);
        self::assertEquals('First Object', $findFirst['Title']);
        self::assertEquals('First Object', $findFirst2['Title']);
        self::assertEquals('Second Object', $findSecond['Title']);
        self::assertEquals('Third Object', $findThird['Title']);

        // These should not find any result.
        $noFind1 = $list->find('Title', 'No Results');
        $noFind2 = $list->find('LessThan100:GreaterThan', 1000);
        $noFind3 = $list->find('LessThan100:LessThan:not', 1000);
        self::assertNull($noFind1);
        self::assertNull($noFind2);
        self::assertNull($noFind3);
    }

    /**
     * @useDatabase false
     */
    public function testDefaultFilter()
    {
        $list = new SearchFilterableArrayList($this->objects);

        // Filter using the "not" modifier which retains 3 objects.
        $notFilter1 = $list->filter('Title:not', 'First Object');
        $notFilter1Retained = $notFilter1->column('Title');
        self::assertCount(3, $notFilter1Retained, 'Three objects remain in the list.');
        self::assertNotContains('First Object', $notFilter1Retained);

        // Filter using the "not" modifier which retains 2 objects.
        $notFilter2 = $list->filter([
            'Title:not' => 'First Object',
            'Title:ExactMatch:not' => 'Second Object',
        ]);
        $notFilter2Retained = $notFilter2->column('Title');
        self::assertCount(2, $notFilter2Retained, 'Two objects remain in the list.');
        self::assertContains('Third Object', $notFilter2Retained);
        self::assertContains('Fourth Object', $notFilter2Retained);

        // Filter to test multiple value arguments which retains two objects.
        $basicFilter1 = $list->filter('Title', ['First Object', 'Second Object']);
        $basicFilter1Retained = $basicFilter1->column('Title');
        self::assertCount(2, $basicFilter1Retained, 'Two objects remain in the list.');
        self::assertContains('First Object', $basicFilter1Retained);
        self::assertContains('Second Object', $basicFilter1Retained);

        // Filter using the "not" modifier which retains 4 objects.
        $notFilter3 = $list->filter('Title:not', 'No Object');
        self::assertCount(4, $notFilter3->toArray(), 'All objects are retained.');

        // Simple filter which returns an empty list.
        $basicFilter2 = $list->filter([
            'Title' => 'No Object',
        ]);
        self::assertEmpty($basicFilter2->toArray(), 'All objects are filtered out.');
    }

    /**
     * @useDatabase false
     */
    public function testCaseSensitivityModifiers()
    {
        $list = new SearchFilterableArrayList($this->objects);

        // Filter testing case sensitivity which retains 1 object.
        $caseFilter1 = $list->filter('NoCase', 'case sensitive');
        $caseFilter1Retained = $caseFilter1->column('Title');
        self::assertCount(1, $caseFilter1Retained, 'One object remains in the list.');
        self::assertContains('Second Object', $caseFilter1Retained);

        // Filter testing case sensitivity which retains 2 objects.
        $caseFilter2 = $list->filter(['NoCase:nocase' => 'case sensitive']);
        $caseFilter2Retained = $caseFilter2->column('Title');
        self::assertCount(2, $caseFilter2Retained, 'Two objects remain in the list.');
        self::assertContains('First Object', $caseFilter2Retained);
        self::assertContains('Second Object', $caseFilter2Retained);

        // Filter testing case sensitivity which retains 1 object.
        $caseFilter3 = $list->filter([
            'NoCase:nocase' => 'case sensitive',
            'CaseSensitive' => 'case sensitive',
        ]);
        $caseFilter3Retained = $caseFilter3->column('Title');
        self::assertCount(1, $caseFilter3Retained, 'One object remains in the list.');
        self::assertContains('Second Object', $caseFilter3Retained);

        // Filter to test case sensitivity which returns an empty list.
        $caseFilter4 = $list->filter('NoCase', 'Case Sensitive');
        self::assertEmpty($caseFilter4->toArray(), 'All objects are filtered out.');
    }

    /**
     * @useDatabase false
     */
    public function testExactMatchFilter()
    {
        $list = new SearchFilterableArrayList($this->objects);

        // Filter testing exact match which retains 1 object.
        $exactFilter1 = $list->filter('Title:ExactMatch', 'Third Object');
        $exactFilter1Retained = $exactFilter1->column('Title');
        self::assertCount(1, $exactFilter1Retained, 'One object remains in the list.');
        self::assertContains('Third Object', $exactFilter1Retained);

        // Filter testing exact match case insensitive which retains 1 object.
        $exactFilter2 = $list->filter('Title:ExactMatch:nocase', 'third object');
        $exactFilter2Retained = $exactFilter2->column('Title');
        self::assertCount(1, $exactFilter2Retained, 'One object remains in the list.');
        self::assertContains('Third Object', $exactFilter2Retained);
    }

    /**
     * @useDatabase false
     */
    public function testPartialMatchFilter()
    {
        $list = new SearchFilterableArrayList($this->objects);

        // Filter testing partial match which retains 1 object.
        $partialFilter1 = $list->filter('StartsWithTest:PartialMatch', 'start');
        $partialFilter1Retained = $partialFilter1->column('Title');
        self::assertCount(1, $partialFilter1Retained, 'One object remains in the list.');
        self::assertContains('Third Object', $partialFilter1Retained);

        // Filter testing partial match case insensitive which retains 2 objects.
        $partialFilter2 = $list->filter('StartsWithTest:PartialMatch:nocase', 'start');
        $partialFilter2Retained = $partialFilter2->column('Title');
        self::assertCount(2, $partialFilter2Retained, 'Two objects remains in the list.');
        self::assertContains('Second Object', $partialFilter2Retained);
        self::assertContains('Third Object', $partialFilter2Retained);
    }

    /**
     * @useDatabase false
     */
    public function testGreaterThanFilter()
    {
        $list = new SearchFilterableArrayList($this->objects);

        // Filter to test the GreaterThan filter which retains 2 objects.
        $greaterLesserFilter1 = $list->filter('GreaterThan100:GreaterThan', 100);
        $greaterLesserFilter1Retained = $greaterLesserFilter1->column('Title');
        self::assertCount(2, $greaterLesserFilter1Retained, 'Two objects remain in the list.');
        self::assertContains('First Object', $greaterLesserFilter1Retained);
        self::assertContains('Second Object', $greaterLesserFilter1Retained);
    }

    /**
     * @useDatabase false
     */
    public function testGreaterThanOrEqualFilter()
    {
        $list = new SearchFilterableArrayList($this->objects);

        // Filter to test the GreaterThanOrEqual filter which retains 3 objects.
        $greaterOrEqualFilter1 = $list->filter('GreaterThan100:GreaterThanOrEqual', 100);
        $greaterOrEqualFilter1Retained = $greaterOrEqualFilter1->column('Title');
        self::assertCount(3, $greaterOrEqualFilter1Retained, 'Two objects remain in the list.');
        self::assertNotContains('Third Object', $greaterOrEqualFilter1Retained);
    }

    /**
     * @useDatabase false
     */
    public function testLessThanFilter()
    {
        $list = new SearchFilterableArrayList($this->objects);

        // Filter to test the LessThan filter which retains 3 objects.
        $greaterLesserFilter2 = $list->filter('LessThan100:LessThan', 100);
        $greaterLesserFilter2Retained = $greaterLesserFilter2->column('Title');
        self::assertCount(3, $greaterLesserFilter2Retained, 'Three objects remain in the list.');
        self::assertNotContains('Fourth Object', $greaterLesserFilter2Retained);

        // Filter to test the LessThan filter and not modifier which retains 1 object.
        $greaterLesserFilter3 = $list->filter('LessThan100:LessThan:not', 100);
        $greaterLesserFilter3Retained = $greaterLesserFilter3->column('Title');
        self::assertCount(1, $greaterLesserFilter3Retained, 'One object remains in the list.');
        self::assertContains('Fourth Object', $greaterLesserFilter3Retained);
    }

    /**
     * @useDatabase false
     */
    public function testLessThanOrEqualFilter()
    {
        $list = new SearchFilterableArrayList($this->objects);

        // Filter to test the LessThanOrEqual filter which retains 3 objects.
        $lesserOrEqualFilter3 = $list->filter('LessThan100:LessThanOrEqual', 99);
        $lesserOrEqualFilter3Retained = $lesserOrEqualFilter3->column('Title');
        self::assertCount(3, $lesserOrEqualFilter3Retained, 'Three objects remain in the list.');
        self::assertNotContains('Fourth Object', $lesserOrEqualFilter3Retained);
    }

    /**
     * @useDatabase false
     */
    public function testFilterMultipleRequirements()
    {
        $list = new SearchFilterableArrayList($this->objects);

        // Filter testing multiple filter items which retains 1 object.
        $multiFilter1 = $list->filter([
          'NoCase:nocase' => 'CASE SENSITIVE',
          'StartsWithTest:StartsWith' => 'Not',
        ]);
        $multiFilter1Retained = $multiFilter1->column('Title');
        self::assertCount(1, $multiFilter1Retained, 'One object remains in the list.');
        self::assertContains('Second Object', $multiFilter1Retained);

        // Filter testing multiple filter items which retains 0 objects.
        $multiFilter2 = $list->filter([
          'NoCase:case' => 'CASE SENSITIVE',
          'StartsWithTest:StartsWith' => 'Not',
        ]);
        self::assertEmpty($multiFilter2->toArray(), 'All objects are excluded.');

        // Filter to test the GreaterThan and LessThan filters which retains 1 object.
        $greaterLesserFilter4 = $list->filter([
            'LessThan100:LessThan' => 100,
            'GreaterThan100:GreaterThan:not' => 100,
        ]);
        $greaterLesserFilter4Retained = $greaterLesserFilter4->column('Title');
        self::assertCount(1, $greaterLesserFilter4Retained, 'One object remains in the list.');
        self::assertContains('Third Object', $greaterLesserFilter4Retained);

        // A filter using the GreaterThan and LesserThan filters which returns an empty list.
        $greaterLesserFilter5 = $list->filter([
            'LessThan100:LessThan' => 1,
            'GreaterThan100:GreaterThan' => 100,
        ]);
        self::assertEmpty($greaterLesserFilter5->toArray(), 'All objects are filtered out.');
    }

    /**
     * @useDatabase false
     */
    public function testFilterAny()
    {
        $list = new SearchFilterableArrayList($this->objects);

        // FilterAny test that retains 3 objects.
        $filterAny1 = $list->filterAny([
            'GreaterThan100:GreaterThan' => 100,
            'GreaterThan100:LessThan' => 100,
        ]);
        $filterAny1Retained = $filterAny1->column('Title');
        self::assertCount(3, $filterAny1Retained, 'Three objects remain in the list.');
        self::assertNotContains('Fourth Object', $filterAny1Retained);

        // FilterAny test that retains 3 objects.
        $filterAny2 = $list->filterAny([
            'NoCase:not' => null,
            'LessThan100' => 100,
        ]);
        $filterAny2Retained = $filterAny2->column('Title');
        self::assertCount(3, $filterAny2Retained, 'Three objects remain in the list.');
        self::assertNotContains('Third Object', $filterAny2Retained);

        // FilterAny test that retains 2 objects.
        $filterAny3 = $list->filterAny([
            'StartsWithTest:StartsWith:case' => 'test',
            'StartsWithTest:EndsWith:case' => 'test',
        ]);
        $filterAny3Retained = $filterAny3->column('Title');
        self::assertCount(2, $filterAny3Retained, 'Two objects remain in the list.');
        self::assertContains('Third Object', $filterAny3Retained);
        self::assertContains('Fourth Object', $filterAny3Retained);
    }

    /**
     * @useDatabase false
     */
    public function testExclude()
    {
        $list = new SearchFilterableArrayList($this->objects);

        // Exclude using the "not" modifier which retains 1 object.
        $notExclude1 = $list->exclude('Title:not', 'First Object');
        $notExclude1Retained = $notExclude1->column('Title');
        self::assertCount(1, $notExclude1Retained, 'One object remains in the list.');
        self::assertContains('First Object', $notExclude1Retained);

        // Exclude using the "not" modifier which returns two items.
        $notExclude2 = $list->exclude([
            'Title:not' => 'First Object',
            'Title:ExactMatch:not' => 'Second Object',
        ]);
        $notExclude2Retained = $notExclude2->column('Title');
        self::assertCount(2, $notExclude2Retained, 'Two objects remain in the list.');
        self::assertContains('First Object', $notExclude2Retained);
        self::assertContains('Second Object', $notExclude2Retained);

        // Exclude using the "not" modifier which returns an empty list.
        $notExclude3 = $list->exclude('Title:not', 'No Object');
        self::assertEmpty($notExclude3->toArray(), 'All objects are excluded.');

        // Simple exclude which retains 4 objects.
        $notExclude4 = $list->exclude([
            'Title' => 'No Object',
        ]);
        self::assertCount(4, $notExclude4->toArray(), 'No objects are excluded.');
    }

    /**
     * @useDatabase false
     */
    public function testExcludeAny()
    {
        $list = new SearchFilterableArrayList($this->objects);

        // ExcludeAny test that retains 1 object.
        $excludeAny1 = $list->excludeAny([
            'GreaterThan100:GreaterThan' => 100,
            'GreaterThan100:LessThan' => 100,
        ]);
        $excludeAny1Retained = $excludeAny1->column('Title');
        self::assertCount(1, $excludeAny1Retained, 'One object remains in the list.');
        self::assertContains('Fourth Object', $excludeAny1Retained);

        // ExcludeAny test that retains 1 object.
        $excludeAny2 = $list->excludeAny([
            'NoCase:not' => null,
            'LessThan100' => 100,
        ]);
        $excludeAny2Retained = $excludeAny2->column('Title');
        self::assertCount(1, $excludeAny2Retained, 'One object remains in the list.');
        self::assertContains('Third Object', $excludeAny2Retained);

        // ExcludeAny test that retains 2 objects.
        $excludeAny3 = $list->excludeAny([
            'StartsWithTest:StartsWith:case' => 'test',
            'StartsWithTest:EndsWith:case' => 'test',
        ]);
        $excludeAny3Retained = $excludeAny3->column('Title');
        self::assertCount(2, $excludeAny3Retained, 'Two objects remain in the list.');
        self::assertContains('First Object', $excludeAny3Retained);
        self::assertContains('Second Object', $excludeAny3Retained);
    }
}
