#!/bin/bash

echo "=== Reproducing PHPStan errors ==="
echo ""

# Test the specific files mentioned in the issue
files=(
    "app/Providers/ValidateLinksServiceProvider.php"
    "app/Enums/OutputFormat.php"
    "app/Exceptions/Handler.php"
)

for file in "${files[@]}"; do
    echo "=== Analyzing: $file ==="
    ./vendor/bin/phpstan analyse "$file" --level=9 --no-progress 2>&1 | head -10
    echo ""
done
