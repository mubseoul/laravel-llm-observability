# Contributing

Thank you for considering contributing to Laravel LLM Observability!

## Development Setup

1. Clone the repository:
```bash
git clone https://github.com/mubseoul/laravel-llm-observability.git
cd laravel-llm-observability
```

2. Install dependencies:
```bash
composer install
```

3. Run tests:
```bash
composer test
```

## Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run static analysis
composer analyse

# Format code
composer format
```

## Pull Request Process

1. **Fork the repository** and create your branch from `main`
2. **Write tests** for your changes
3. **Ensure tests pass** and code is formatted
4. **Update documentation** if needed (README.md, config comments, etc.)
5. **Submit a pull request** with a clear description of the changes

## Coding Standards

- Follow PSR-12 coding standards
- Use Laravel Pint for code formatting: `composer format`
- Run PHPStan for static analysis: `composer analyse`
- Write descriptive commit messages
- Add type hints to all methods
- Document public methods with DocBlocks

## Adding New Features

- Discuss major changes in an issue first
- Keep backward compatibility in mind
- Add tests for new functionality
- Update configuration if needed
- Update README with usage examples

## Reporting Bugs

When reporting bugs, please include:
- Laravel version
- PHP version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Any error messages or logs

## Feature Requests

We welcome feature requests! Please:
- Check existing issues first
- Describe the use case clearly
- Explain why it would be useful to most users
- Be open to discussion and feedback

## Questions

For questions, please use:
- GitHub Discussions for general questions
- Issues for bug reports
- Pull Requests for code contributions

Thank you for contributing!
