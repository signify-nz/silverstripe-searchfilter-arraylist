# Silverstripe SearchFilter ArrayList
Provides an [ArrayList](https://api.silverstripe.org/3/ArrayList.html) subclass that can be filtered using [SearchFilters](https://docs.silverstripe.org/en/4/developer_guides/model/searchfilters/).

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
See [the documentation](docs/en/01-docs.md#examples) for examples.

All of the `SearchFilter`s and modifiers documented in [Silverstripe's SearchFilter documentation](https://docs.silverstripe.org/en/4/developer_guides/model/searchfilters/) should be supported - if you find that isn't the case, please [raise an issue](https://github.com/signify-nz/silverstripe-searchfilter-arraylist/issues) or, better yet, a pull request.

If you have implemented your own `SearchFilter`, you can add support for it via an `Extension` class - [see the extension documentation](docs/en/01-docs.md#implementing-your-own-searchfilter-classes-andor-modifiers).
