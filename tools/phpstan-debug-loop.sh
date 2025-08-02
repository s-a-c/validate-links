for file in $(find app/ -name "*.php" | head -5);
 do
    echo "=== Analyzing: $file ===";
    ./vendor/bin/phpstan analyse "$file" --level=9 --no-progress 2>&1 | head -10; echo "";
done
