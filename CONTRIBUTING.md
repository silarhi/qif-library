# Contributing

Contributions are welcome! Bug reports and feature requests go to the
[issue tracker](https://github.com/silarhi/qif-library/issues), changes to a pull request against `main`.

## Setup

```bash
composer install
yarn install # Git hooks: lint-staged formats the staged files on commit
```

## Quality checks

The CI runs them all, on every supported PHP version. Please make sure your changes pass them before submitting a pull
request:

```bash
# Tests
vendor/bin/phpunit

# Static analysis (level: max)
vendor/bin/phpstan analyse

# Code style (drop --dry-run --diff to fix)
vendor/bin/php-cs-fixer fix --dry-run --diff

# Code modernization (drop --dry-run to apply)
vendor/bin/rector process --dry-run

# composer.json
composer validate --strict
composer normalize --dry-run

# Documentation examples
php docs/scripts/render-examples.php --check
```

## Documentation

The documentation site, [qif-library.silarhi.dev](https://qif-library.silarhi.dev), is built from the [`docs/`](docs)
folder with [Starlight](https://starlight.astro.build). Document a change in the same pull request:

```bash
cd docs
yarn install
yarn dev # http://localhost:4321
```

The outputs the pages show are real: `docs/examples/*.php` run with the library, and their output is committed in
`docs/src/data/examples/`. After changing an example or the library's output, regenerate them:

```bash
php docs/scripts/render-examples.php
```

Pull requests touching `docs/` build the site; it is published once merged into `main`.

## License

By contributing, you agree that your contributions are released under the [MIT License](LICENSE).
