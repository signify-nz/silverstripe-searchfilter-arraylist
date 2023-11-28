[![Build Status](https://travis-ci.com/signify-nz/silverstripe-searchfilter-arraylist.svg?branch=master)](https://travis-ci.com/signify-nz/silverstripe-searchfilter-arraylist)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/signify-nz/silverstripe-searchfilter-arraylist/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/signify-nz/silverstripe-searchfilter-arraylist/?branch=master)
[![codecov](https://codecov.io/gh/signify-nz/silverstripe-searchfilter-arraylist/branch/master/graph/badge.svg?token=ADKJFY1HUN)](https://codecov.io/gh/signify-nz/silverstripe-searchfilter-arraylist)

# Silverstripe SearchFilter ArrayList
Provides an [ArrayList](https://api.silverstripe.org/3/ArrayList.html) subclass that can be filtered using [SearchFilters](https://docs.silverstripe.org/en/4/developer_guides/model/searchfilters/).

**This module is obsolete as of Silverstripe CMS 5.1.0**, as the functionality it provides is now in core. Please see [this comment](https://github.com/signify-nz/silverstripe-searchfilter-arraylist/issues/4#issuecomment-1828841648) for more information.

## Installation
Install via [composer](https://getcomposer.org):
```shell
composer require signify-nz/silverstripe-searchfilter-arraylist
```

If you want to, you can replace (most) instances of `ArrayList` with this implementation via yaml config:
```yaml
SilverStripe\Core\Injector\Injector:
  SilverStripe\ORM\ArrayList:
    class: Signify\SearchFilterArrayList\SearchFilterableArrayList
```
**Beware** however that some code - even potentially within Silverstripe itself - may use the `new` keyword instead of relying on the `Injector` when instantiating new `ArrayList`s. In those cases the original `ArrayList` class will be used.

## Usage
When calling `find`, `filter`, `filterAny`, `exclude`, or `excludeAny` on a `SearchFilterableArrayList`, you can use [`SearchFilter` syntax](https://docs.silverstripe.org/en/4/developer_guides/model/searchfilters/) - the same as if you were calling those methods on a `DataList`.

All of the `SearchFilter`s and modifiers documented in [Silverstripe's SearchFilter documentation](https://docs.silverstripe.org/en/4/developer_guides/model/searchfilters/) should be supported - if you find that isn't the case, please [raise an issue](https://github.com/signify-nz/silverstripe-searchfilter-arraylist/issues) or, better yet, a pull request.

If you have implemented your own `SearchFilter`, you can add support for it via an `Extension` class - [see the extension documentation](docs/en/01-docs.md#implementing-your-own-searchfilter-classes-and-or-modifiers).
