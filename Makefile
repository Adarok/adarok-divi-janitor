.PHONY: help install lint lint-fix analyze check test

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \\033[36m%-15s\\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install dependencies
	@echo "Installing Composer dependencies..."
	composer install
	@echo "Done!"

lint: ## Run PHP_CodeSniffer
	@echo "Running PHP_CodeSniffer..."
	composer run-script lint

lint-fix: ## Fix coding standard violations automatically
	@echo "Fixing coding standard violations..."
	composer run-script lint:fix

analyze: ## Run PHPStan static analysis
	@echo "Running PHPStan analysis..."
	composer run-script analyze

check: ## Run all quality checks
	@echo "Running all quality checks..."
	@$(MAKE) lint
	@$(MAKE) analyze
	@echo "All checks passed!"

test: ## Run tests (placeholder for future)
	@echo "No tests configured yet"
