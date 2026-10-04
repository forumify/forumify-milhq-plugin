<p align="center">
    <img src="./public/images/milhq.svg" width="250" height="250">
</p>

<div align="center">

[![Latest Stable Version](https://poser.pugx.org/forumify/forumify-milhq-plugin/v)](https://packagist.org/packages/forumify/forumify-milhq-plugin)
[![Total Downloads](https://poser.pugx.org/forumify/forumify-milhq-plugin/downloads)](https://packagist.org/packages/forumify/forumify-milhq-plugin)
[![License](https://poser.pugx.org/forumify/forumify-milhq-plugin/license)](https://packagist.org/packages/forumify/forumify-milhq-plugin)
[![PHP Version Require](https://poser.pugx.org/forumify/forumify-milhq-plugin/require/php)](https://packagist.org/packages/forumify/forumify-milhq-plugin)
![Symfony](https://img.shields.io/badge/Symfony-7.4-black.svg)
![CodeStyle](https://img.shields.io/badge/CodeStyle-PSR2-green.svg)
![PHPStan](https://img.shields.io/badge/PHPStan-level%205-green.svg)
[![Automated Tests](https://github.com/forumify/forumify-milhq-plugin/actions/workflows/tests.yml/badge.svg)](https://github.com/forumify/forumify-milhq-plugin/actions/workflows/tests.yml)
[![Code Quality](https://github.com/forumify/forumify-milhq-plugin/actions/workflows/quality.yml/badge.svg)](https://github.com/forumify/forumify-milhq-plugin/actions/workflows/quality.yml)

</div>

# forumify MILHQ

**Personnel & unit management for MilSim units**

MILHQ is forumify's first-party unit and personnel management plugin, built for military simulation units and other structured communities. It brings personnel tracking, chain of command and operational logistics straight into your [forumify](https://github.com/forumify/forumify-platform) community.

📖 **Documentation:** [docs.forumify.net/plugins/milhq-plugin](https://docs.forumify.net/plugins/milhq-plugin/introduction)

## Features

- **[Organization Setup](https://docs.forumify.net/plugins/milhq-plugin/organization-setup)**: units, positions, specialties, statuses and rosters to model your chain of command.
- **[Enlistment](https://docs.forumify.net/plugins/milhq-plugin/enlistment)**: let forum members apply to join your unit.
- **[Forms](https://docs.forumify.net/plugins/milhq-plugin/forms)**: custom forms with submissions and supervisor review.
- **[Assignments](https://docs.forumify.net/plugins/milhq-plugin/assignments)**: track where every soldier serves, and every move they make.
- **[Ranks](https://docs.forumify.net/plugins/milhq-plugin/ranks)**: rank groups, promotions and demotions.
- **[Awards](https://docs.forumify.net/plugins/milhq-plugin/awards)**: medals and ribbons, with tiers and groups.
- **[Qualifications](https://docs.forumify.net/plugins/milhq-plugin/qualifications)**: certify soldiers for roles and equipment.
- **[Combat Records](https://docs.forumify.net/plugins/milhq-plugin/combat-records)**: a complete service history for every soldier.
- **[Courses](https://docs.forumify.net/plugins/milhq-plugin/courses)**: schedule classes, assign instructors and report results.
- **[Operations](https://docs.forumify.net/plugins/milhq-plugin/operations)**: plan operations and missions, collect RSVPs and write after action reports.
- **[Attendance Tracking](https://docs.forumify.net/plugins/milhq-plugin/attendance-tracking)**: see who shows up, and who doesn't.
- **[Report In](https://docs.forumify.net/plugins/milhq-plugin/report-in)**: keep your roster active with periodic check-ins.

### Integrations

MILHQ works on its own, but integrates with other forumify plugins when they are installed:

- [forumify-calendar-plugin](https://github.com/forumify/forumify-calendar-plugin): missions and course classes show up on your calendars.
- [forumify-discord-plugin](https://github.com/forumify/forumify-discord-plugin): look up soldiers, units, ranks, awards and qualifications with Discord commands.

## Installation

MILHQ can be installed from the [forumify marketplace](https://forumify.net/marketplace), or manually with composer:

```bash
composer require forumify/forumify-milhq-plugin
```

Afterwards, activate the plugin from the plugins page in your forumify admin panel.

See the [documentation](https://docs.forumify.net/plugins/milhq-plugin/introduction) to get your unit set up.

## Contributing

When you believe you've found a bug or think the plugin could be expanded with a new feature, you can start by creating an issue [here on GitHub](https://github.com/forumify/forumify-milhq-plugin/issues).

The easiest way to work on MILHQ is by using the [production template](https://github.com/forumify/forumify-production-template) and installing the plugin with composer using a symlink.

Add the following to the template's `composer.json`:
```json
    "repositories": [
        {
            "type": "path",
            "url": "../forumify-milhq-plugin"
        }
    ]
```

Now when you run `composer require forumify/forumify-milhq-plugin`, the plugin will be symlinked and any changes you make will immediately be available in your project.

### Code quality

The following checks are performed when you create a pull request:
- Tests: [PHPUnit](https://phpunit.de/), run with and without the optional calendar and discord plugins
- CodeStyle: checked by [PHPCS](https://github.com/PHPCSStandards/PHP_CodeSniffer/), using the [PSR-2 codestyle](https://www.php-fig.org/psr/psr-2/) extended with [Slevomat](https://github.com/slevomat/coding-standard) rules
- Static analysis: checked by [PHPStan](https://phpstan.org/)
- JavaScript: checked by [ESLint](https://eslint.org/)

You can avoid failing builds by running these tools locally:

On Linux/WSL/MacOS:

- Quality checks: `make quality`
- Tests: `make tests`

The tests require a MySQL 8.4 database, configured through `DATABASE_URL` in `.env`.

On Windows:

- See commands in `Makefile` and execute manually.

## License

MILHQ is released under the [Open Software License 3.0](https://opensource.org/license/osl-3-0-php).
