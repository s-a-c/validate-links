# PHPStorm File Watcher Configuration for PHPStan Level 5

This guide shows how to configure a PHPStorm file watcher that automatically runs PHPStan analysis at level 5 whenever a PHP file is changed.

## Prerequisites

- PHPStorm IDE installed
- PHPStan installed in your project (already configured in this project)
- Existing `phpstan.neon` configuration file (present in project root)

## Step-by-Step Configuration

### 1. Open File Watchers Settings

1. Open PHPStorm
2. Go to **File** → **Settings** (or **PHPStorm** → **Preferences** on macOS)
3. Navigate to **Tools** → **File Watchers**
4. Click the **+** button to add a new file watcher
5. Select **Custom** from the dropdown menu

### 2. Configure the File Watcher

Fill in the following settings in the File Watcher dialog:

#### Basic Settings
- **Name**: `PHPStan Level 5`
- **File type**: `PHP`
- **Scope**: `Project Files` (or create a custom scope if needed)

#### Tool Settings
- **Program**: `$ProjectFileDir$/vendor/bin/phpstan`
- **Arguments**: `analyse --level=5 --configuration=$ProjectFileDir$/phpstan.neon $FilePath$`
- **Working directory**: `$ProjectFileDir$`

#### Advanced Options
- **Auto-save edited files**: ✓ (checked)
- **Trigger the watcher on external changes**: ✓ (checked)
- **Show console**: `On error` (recommended)
- **Output paths to refresh**: `$ProjectFileDir$/reports/phpstan`

### 3. Alternative Configuration (Analyze Entire Project)

If you prefer to analyze the entire project instead of just the changed file:

- **Arguments**: `analyse --level=5 --configuration=$ProjectFileDir$/phpstan.neon`

This will run PHPStan on all configured paths (app, bootstrap, config, tests) as defined in `phpstan.neon`.

### 4. File Pattern Configuration

To be more specific about which files trigger the watcher:

1. In the **File type** dropdown, select **PHP**
2. Or create a custom pattern in **Scope**:
   - Click **...** next to Scope
   - Create a new scope with pattern: `file:*.php&&!file:vendor//*`

### 5. Environment Variables (Optional)

If you need to set specific environment variables:

- **Environment variables**: Add any required variables like `PHP_BINARY` or `COMPOSER_HOME`

## Complete Configuration Example

Here's a summary of the recommended configuration:

```
Name: PHPStan Level 5
File type: PHP
Scope: Project Files
Program: $ProjectFileDir$/vendor/bin/phpstan
Arguments: analyse --level=5 --configuration=$ProjectFileDir$/phpstan.neon $FilePath$
Working directory: $ProjectFileDir$
Auto-save edited files: ✓
Trigger the watcher on external changes: ✓
Show console: On error
Output paths to refresh: $ProjectFileDir$/reports/phpstan
```

## Testing the Configuration

1. Save the file watcher configuration
2. Open any PHP file in your project
3. Make a small change and save the file
4. PHPStan should automatically run in the background
5. Check the **Event Log** or **Console** for results

## Troubleshooting

### Common Issues

1. **PHPStan not found**: Ensure `vendor/bin/phpstan` exists
   - Run `composer install` to install dependencies
   - Check if PHPStan is in your `composer.json`

2. **Permission issues**: Make sure `vendor/bin/phpstan` is executable
   ```bash
   chmod +x vendor/bin/phpstan
   ```

3. **Configuration file not found**: Verify `phpstan.neon` exists in project root

4. **Memory issues**: Add memory limit to arguments:
   ```
   analyse --level=5 --memory-limit=1G --configuration=$ProjectFileDir$/phpstan.neon $FilePath$
   ```

### Performance Considerations

- For large projects, consider using the single file analysis (`$FilePath$`) instead of full project analysis
- Adjust the `maximumNumberOfProcesses` in `phpstan.neon` based on your system capabilities
- Use the `tmpDir` setting to cache results and improve performance

## Current Project Configuration

This project is already configured with:
- **Level**: 5 (as specified in `phpstan.neon`)
- **Paths**: app, bootstrap, config, tests
- **Bootstrap file**: `phpstan-bootstrap.php`
- **Parallel processing**: Up to 8 processes
- **Output directory**: `reports/phpstan`

The file watcher will use this existing configuration automatically.

## Additional Tips

1. **Disable for specific files**: Use scope patterns to exclude certain files
2. **Multiple watchers**: Create separate watchers for different analysis levels
3. **Integration with CI**: This local setup complements your CI/CD pipeline
4. **Custom rules**: Modify `phpstan.neon` to add custom rules or ignore patterns

## Conclusion

With this file watcher configured, PHPStan will automatically analyze your PHP files at level 5 whenever you make changes, helping you catch potential issues early in development.
