# psalm-type-setters-param
A [Psalm](https://github.com/vimeo/psalm) plugin that use property type to fix param in the setter


Installation:

```console
$ composer require --dev orklah/psalm-type-setters-param
$ vendor/bin/psalm-plugin enable orklah/psalm-type-setters-param
```

Usage:

To automatically fix code, run:
```console
$ vendor/bin/psalm --alter --plugin=vendor\orklah\psalm-type-setters-param\src\Plugin.php
```

Explanation:

When a property has a type, we can deduce that any setter should not take a type that is wider than the property.
When this plugin detect such a case, it adds the type to the param.

Warning: if the class of the setter does not have a strict_type declaration and the file that call the setter does, this plugin may create a TypeError if there was an implicit cast.
Please use orklah\psalm-strict-types to avoid such cases.
(If someone is interested, it may be possible to check that specific case and prevent the replacement, please create an issue)


This idea was first implemented by Rector for PHPStan: https://github.com/rectorphp/rector
Please read their article and the rest of rectors in the set: https://getrector.org/blog/2021/02/15/how-much-does-single-type-declaration-know
