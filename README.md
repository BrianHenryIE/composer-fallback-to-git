# Composer Fallback to Git

When a package is on GitHub but not Packagist, have Composer just download it from there.

```
composer config minimum-stability dev
composer config prefer-stable true

composer config allow-plugins.brianhenryie/composer-fallback-to-git true
composer require --dev brianhenryie/composer-fallback-to-git
```
