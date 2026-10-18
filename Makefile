.PHONY: help install build dev lint format test clean

help:
	@echo "Local Visual Feedback - Development Commands"
	@echo "================================================"
	@echo "make install      - Install all dependencies (PHP + Node)"
	@echo "make build        - Build production assets"
	@echo "make dev          - Start development server"
	@echo "make lint         - Run linters (PHP + JS)"
	@echo "make format       - Auto-format code (PHP + JS)"
	@echo "make test         - Run tests"
	@echo "make clean        - Remove build artifacts"
	@echo "make activate     - Activate plugin (via wp-cli)"
	@echo "make deactivate   - Deactivate plugin (via wp-cli)"

install:
	@echo "Installing PHP dependencies..."
	composer install --no-dev 2>/dev/null || echo "Composer not found, skipping PHP deps"
	@echo "Installing Node dependencies..."
	cd admin && npm install

build:
	@echo "Building admin app..."
	cd admin && npm run build
	@echo "✓ Build complete. Output: assets/dist/"

dev:
	@echo "Starting development server..."
	cd admin && npm run dev

lint:
	@echo "Linting PHP..."
	phpcs src/ --standard=WordPress -q || echo "PHPCS not found"
	@echo "Linting JavaScript..."
	cd admin && npm run lint

format:
	@echo "Formatting PHP..."
	phpcbf src/ --standard=WordPress -q || echo "PHPCBF not found"
	@echo "Formatting JavaScript..."
	cd admin && npm run format

test:
	@echo "Running tests..."
	@echo "TODO: Configure test suite"

clean:
	@echo "Cleaning build artifacts..."
	rm -rf admin/node_modules admin/dist assets/dist
	@echo "✓ Cleaned"

activate:
	@echo "Activating plugin..."
	wp plugin activate lunexia-live-feedback

deactivate:
	@echo "Deactivating plugin..."
	wp plugin deactivate lunexia-live-feedback

verify-migration:
	@echo "Verifying database integrity..."
	wp eval 'use LunexiaLiveFeedback\Plugin; echo json_encode(Plugin::get_migration_status());'

package:
	@echo "Packaging minimal plugin zip (excludes dev files)..."
	./scripts/package-plugin.sh

assets:
	@echo "Generating WordPress.org directory assets..."
	python3 ./scripts/generate-wporg-assets.py

deploy-svn:
	@echo "Starting SVN deployment..."
	./scripts/deploy-to-svn.sh

