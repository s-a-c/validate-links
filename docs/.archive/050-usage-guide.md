# 5. Usage Guide

**Date:** July 21, 2025  
**Project:** Laravel Zero validate-links Implementation  
**Compliance:** `.ai/guidelines.md` and `.ai/guidelines/` standards

## 5.1. Installation and Distribution

### 5.1.1. Installation Methods

The validate-links application supports multiple installation methods to accommodate different user preferences and deployment scenarios.

#### Method 1: Composer Global Installation (Recommended)

**Requirements:**
- PHP 8.4 or higher
- Composer 2.0 or higher
- Required extensions: `ext-json`, `ext-mbstring`, `ext-curl`

```bash
# Install globally via Composer
composer global require s-a-c/validate-links

# Verify installation
validate-links --version

# Update to latest version
composer global update s-a-c/validate-links
```

#### Method 2: PHAR Binary Download

**Download the latest PHAR binary:**
```bash
# Download latest release
curl -L https://github.com/s-a-c/validate-links/releases/latest/download/validate-links.phar -o validate-links.phar

# Make executable
chmod +x validate-links.phar

# Move to system PATH (optional)
sudo mv validate-links.phar /usr/local/bin/validate-links

# Verify installation
validate-links --version
```

#### Method 3: Direct Git Installation

**For development or customization:**
```bash
# Clone repository
git clone https://github.com/s-a-c/validate-links.git
cd validate-links

# Install dependencies
composer install

# Make executable
chmod +x validate-links

# Create symlink (optional)
sudo ln -s $(pwd)/validate-links /usr/local/bin/validate-links

# Verify installation
./validate-links --version
```

#### Method 4: Docker Container

**Run in containerized environment:**
```bash
# Pull Docker image
docker pull s-a-c/validate-links:latest

# Run validation
docker run --rm -v $(pwd):/workspace s-a-c/validate-links:latest validate /workspace/docs

# Create alias for convenience
alias validate-links='docker run --rm -v $(pwd):/workspace s-a-c/validate-links:latest'
```

### 5.1.2. System Requirements

#### Minimum Requirements
- **PHP Version:** 8.4 or higher
- **Memory:** 128MB RAM
- **Disk Space:** 50MB free space
- **Network:** Internet connection for external link validation

#### Recommended Requirements
- **PHP Version:** 8.4+ with OPcache enabled
- **Memory:** 512MB RAM for large documentation sets
- **Disk Space:** 200MB free space for reports and cache
- **Network:** Stable internet connection with 10Mbps+ bandwidth

#### Required PHP Extensions
```bash
# Check required extensions
php -m | grep -E "(json|mbstring|curl)"

# Install missing extensions (Ubuntu/Debian)
sudo apt-get install php8.4-json php8.4-mbstring php8.4-curl

# Install missing extensions (CentOS/RHEL)
sudo yum install php84-json php84-mbstring php84-curl
```

### 5.1.3. Configuration Setup

#### Environment Configuration

**Create configuration file:**
```bash
# Copy default configuration
cp vendor/s-a-c/validate-links/config/validate-links.php config/validate-links.php

# Or publish configuration (if using Laravel)
php artisan vendor:publish --provider="App\Providers\ValidateLinksServiceProvider"
```

**Basic configuration example:**
```php
<?php
// config/validate-links.php

return [
    'paths' => [
        'docs/',
        'README.md',
    ],
    
    'scope' => ['internal', 'anchor'],
    
    'external' => [
        'enabled' => false,
        'timeout' => 30,
        'user_agent' => 'validate-links/1.0',
    ],
    
    'output' => [
        'format' => 'console',
        'colors' => true,
        'verbosity' => 'normal',
    ],
    
    'security' => [
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'blocked_domains' => [],
        'allowed_protocols' => ['http', 'https'],
    ],
];
```

#### Environment Variables

**Create .env file:**
```bash
# Application settings
APP_NAME="validate-links"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=UTC

# Logging configuration
LOG_CHANNEL=stack
LOG_LEVEL=info

# Validation settings
VALIDATE_LINKS_TIMEOUT=30
VALIDATE_LINKS_MAX_BROKEN=50
VALIDATE_LINKS_CACHE_ENABLED=true
```

### 5.1.4. Verification and Testing

#### Installation Verification
```bash
# Check version and basic functionality
validate-links --version
validate-links --help

# Test with sample file
echo "# Test\n[Link](https://example.com)" > test.md
validate-links validate test.md
rm test.md
```

#### Performance Testing
```bash
# Benchmark validation performance
time validate-links validate docs/ --scope=internal

# Memory usage monitoring
validate-links validate docs/ --verbose --memory-limit=256M
```

#### Troubleshooting Common Issues

**Issue: Command not found**
```bash
# Check if Composer global bin is in PATH
echo $PATH | grep composer

# Add to PATH if missing (add to ~/.bashrc or ~/.zshrc)
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

**Issue: Permission denied**
```bash
# Fix permissions for PHAR file
chmod +x validate-links.phar

# Fix permissions for Git installation
chmod +x validate-links
```

**Issue: PHP extension missing**
```bash
# Check installed extensions
php -m

# Install missing extensions
sudo apt-get install php8.4-{json,mbstring,curl}
```

### 5.1.5. Update and Maintenance

#### Updating validate-links

**Composer installation:**
```bash
# Update to latest version
composer global update s-a-c/validate-links

# Update to specific version
composer global require s-a-c/validate-links:^2.0
```

**PHAR installation:**
```bash
# Download latest PHAR
curl -L https://github.com/s-a-c/validate-links/releases/latest/download/validate-links.phar -o validate-links.phar
chmod +x validate-links.phar
```

**Git installation:**
```bash
# Update to latest version
git pull origin main
composer install --no-dev --optimize-autoloader
```

#### Version Management

**Check current version:**
```bash
validate-links --version
```

**List available versions:**
```bash
# Composer versions
composer show s-a-c/validate-links --all

# GitHub releases
curl -s https://api.github.com/repos/s-a-c/validate-links/releases | grep tag_name
```

#### Uninstallation

**Remove Composer installation:**
```bash
composer global remove s-a-c/validate-links
```

**Remove PHAR installation:**
```bash
rm /usr/local/bin/validate-links
```

**Remove Git installation:**
```bash
rm -rf validate-links/
sudo rm /usr/local/bin/validate-links  # if symlinked
```

## 5.2. Getting Started

The Laravel Zero validate-links tool provides comprehensive link validation for documentation projects. This guide covers practical usage scenarios, from basic validation to advanced workflows for different user types and project requirements.

### 5.2.1. Quick Start

**Basic Validation:**
```bash
# Validate all markdown files in current directory
validate-links validate .

# Validate specific directory
validate-links validate docs/

# Validate multiple files
validate-links validate README.md CHANGELOG.md
```

**Interactive Mode:**
```bash
# Launch interactive setup
validate-links validate --interactive
```

The interactive mode guides you through configuration options with user-friendly prompts, making it ideal for first-time users or complex validation scenarios.

### 5.1.2. Common Workflows

**Documentation Maintenance:**
```bash
# Daily documentation check
validate-links validate docs/ --scope=internal --scope=anchor

# Pre-publication validation
validate-links validate docs/ --scope=all --check-external --max-broken=0
```

**CI/CD Integration:**
```bash
# Automated validation in pipelines
validate-links validate docs/ \
  --format=json \
  --output=validation-report.json \
  --max-broken=0 \
  --no-progress
```

### 5.2.2. Advanced Usage Examples

**Comprehensive Validation with External Links:**

```bash
# Full validation including external links
validate-links validate docs/ README.md \
  --scope=all \
  --check-external \
  --timeout=60 \
  --format=html \
  --output=comprehensive-report.html \
  --verbose

# CI/CD Integration Example
validate-links validate docs/ \
  --scope=internal,anchor \
  --format=json \
  --output=ci-validation.json \
  --max-broken=0 \
  --no-progress

# Performance-Optimized Validation
validate-links validate large-docs/ \
  --scope=internal \
  --max-depth=3 \
  --exclude="**/node_modules/**" \
  --exclude="**/vendor/**" \
  --cache \
  --no-progress

# Interactive Validation with Fixing
validate-links validate docs/ \
  --interactive \
  --fix \
  --backup \
  --scope=internal,anchor

# Batch Processing Multiple Projects
for project in project1 project2 project3; do
  validate-links validate "$project/docs/" \
    --format=json \
    --output="reports/${project}-validation.json" \
    --scope=internal,anchor
done

# Custom Configuration with Exclusions
validate-links validate . \
  --exclude="**/build/**" \
  --exclude="**/dist/**" \
  --exclude="**/.git/**" \
  --include-hidden=false \
  --max-depth=5 \
  --scope=internal,anchor,cross-reference
```

**Report Generation Examples:**

```bash
# Generate multiple report formats
validate-links report docs/ \
  --format=html \
  --output=reports/detailed-report.html \
  --detailed

validate-links report docs/ \
  --format=markdown \
  --output=VALIDATION_SUMMARY.md

validate-links report docs/ \
  --format=json \
  --output=api-data.json \
  --include-statistics

# Executive summary report
validate-links report docs/ \
  --format=html \
  --output=executive-summary.html \
  --summary-only \
  --group-by=status
```

## 5.3. User Scenarios

### 5.3.1. Technical Writers

Technical writers need reliable link validation to maintain documentation quality and user experience.

**Daily Workflow:**
```bash
# Morning documentation check
validate-links validate docs/ --verbose

# Before committing changes
validate-links validate docs/ --scope=internal --scope=anchor --fix
```

**Content Review Process:**
```bash
# Generate comprehensive report for review
validate-links report docs/ \
  --format=html \
  --output=review-report.html \
  --include-valid \
  --group-by=file

# Export data for analysis
validate-links report docs/ \
  --format=csv \
  --output=links-analysis.csv
```

**Best Practices for Technical Writers:**

1. **Regular Validation Schedule**
   - Run validation daily before starting work
   - Validate after major content changes
   - Include validation in content review process

2. **Link Maintenance Strategy**
   - Use internal links for stable content
   - Regularly review external links
   - Implement link fixing for common issues

3. **Documentation Standards**
   - Establish consistent linking patterns
   - Use descriptive link text
   - Maintain link inventories for large projects

### 5.3.2. Developers

Developers integrate link validation into development workflows and CI/CD pipelines.

**Development Workflow:**
```bash
# Pre-commit validation
validate-links validate docs/ --scope=internal --max-broken=0

# Feature branch validation
validate-links validate docs/ \
  --scope=internal \
  --scope=anchor \
  --format=json \
  --output=branch-validation.json
```

**CI/CD Integration:**
```bash
# GitHub Actions integration
validate-links validate docs/ \
  --scope=internal \
  --scope=anchor \
  --format=json \
  --output=ci-report.json \
  --max-broken=0 \
  --no-progress
```

**Advanced Developer Usage:**

1. **Custom Configuration**
   ```bash
   # Environment-specific validation
   validate-links validate docs/ --config=config/validate-links-dev.php
   
   # Production validation
   validate-links validate docs/ --config=config/validate-links-prod.php
   ```

2. **Programmatic Integration**
   ```php
   <?php
   use Illuminate\Support\Facades\Artisan;
   
   // Integrate into deployment scripts
   $exitCode = Artisan::call('validate', [
       'path' => ['docs/'],
       '--scope' => ['internal', 'anchor'],
       '--max-broken' => 0
   ]);
   
   if ($exitCode !== 0) {
       throw new Exception('Link validation failed');
   }
   ```

3. **Custom Reporting**
   ```bash
   # Generate developer-focused reports
   validate-links report docs/ \
     --format=json \
     --output=dev-report.json \
     --group-by=type \
     --sort-by=status
   ```

### 5.3.3. DevOps Engineers

DevOps engineers implement automated validation in deployment pipelines and monitoring systems.

**Pipeline Integration:**
```yaml
# GitHub Actions example
- name: Validate Documentation Links
  run: |
    validate-links validate docs/ \
      --scope=internal \
      --scope=anchor \
      --format=json \
      --output=validation-report.json \
      --max-broken=0
```

**Monitoring Integration:**
```bash
# Scheduled validation for live documentation
validate-links validate docs/ \
  --check-external \
  --timeout=60 \
  --format=json \
  --output=/var/log/link-validation.json
```

**Production Deployment:**
```bash
# Pre-deployment validation
validate-links validate docs/ \
  --scope=all \
  --check-external \
  --max-broken=0 \
  --cache \
  --format=json \
  --output=deployment-validation.json
```

**DevOps Best Practices:**

1. **Automated Validation**
   - Include in all deployment pipelines
   - Set up scheduled validation for live sites
   - Implement failure notifications

2. **Performance Optimization**
   - Use caching for external link validation
   - Configure appropriate timeouts
   - Implement concurrent processing

3. **Monitoring and Alerting**
   - Track validation metrics over time
   - Set up alerts for validation failures
   - Monitor external link health

### 5.3.4. Content Managers

Content managers oversee documentation quality and coordinate content updates across teams.

**Content Audit Workflow:**
```bash
# Comprehensive content audit
validate-links validate docs/ \
  --scope=all \
  --check-external \
  --include-valid \
  --format=html \
  --output=content-audit.html
```

**Team Coordination:**
```bash
# Generate team reports
validate-links report docs/ \
  --format=markdown \
  --output=team-report.md \
  --include-valid \
  --group-by=file
```

**Quality Assurance:**
```bash
# Quality gate validation
validate-links validate docs/ \
  --scope=all \
  --max-broken=0 \
  --format=json \
  --output=qa-report.json
```

## 5.3. Project Types

### 5.3.1. Open Source Projects

Open source projects need reliable documentation to support community contributions and user adoption.

**Repository Setup:**
```bash
# Initial project validation
validate-links validate . \
  --scope=internal \
  --scope=anchor \
  --exclude="node_modules/**" \
  --exclude="vendor/**"
```

**Contributor Guidelines:**
```bash
# Pre-commit hook for contributors
validate-links validate docs/ README.md CONTRIBUTING.md \
  --scope=internal \
  --scope=anchor \
  --max-broken=0
```

**Release Preparation:**
```bash
# Pre-release validation
validate-links validate . \
  --scope=all \
  --check-external \
  --format=html \
  --output=release-validation.html
```

**Open Source Best Practices:**

1. **Community Standards**
   - Include validation in contribution guidelines
   - Provide clear documentation standards
   - Automate validation in pull requests

2. **Accessibility**
   - Ensure all links are accessible
   - Provide alternative text for images
   - Test with screen readers

3. **Internationalization**
   - Validate translated documentation
   - Handle multi-language link structures
   - Consider cultural link conventions

### 5.3.2. Enterprise Documentation

Enterprise documentation requires strict quality controls and compliance with organizational standards.

**Enterprise Validation:**
```bash
# Comprehensive enterprise validation
validate-links validate docs/ \
  --scope=all \
  --check-external \
  --timeout=30 \
  --max-broken=0 \
  --format=json \
  --output=enterprise-report.json
```

**Compliance Reporting:**
```bash
# Generate compliance reports
validate-links report docs/ \
  --format=html \
  --output=compliance-report.html \
  --include-valid \
  --template=enterprise
```

**Security Considerations:**
```bash
# Security-focused validation
validate-links validate docs/ \
  --scope=internal \
  --scope=anchor \
  --exclude="**/sensitive/**" \
  --format=json \
  --output=security-validation.json
```

**Enterprise Best Practices:**

1. **Governance**
   - Establish documentation governance policies
   - Implement approval workflows
   - Maintain audit trails

2. **Security**
   - Validate internal links only in secure environments
   - Implement access controls
   - Regular security audits

3. **Scalability**
   - Use distributed validation for large projects
   - Implement caching strategies
   - Monitor performance metrics

### 5.3.3. API Documentation

API documentation requires special attention to endpoint links and code examples.

**API Documentation Validation:**
```bash
# Validate API documentation
validate-links validate api-docs/ \
  --scope=internal \
  --scope=anchor \
  --scope=external \
  --timeout=60 \
  --format=json \
  --output=api-validation.json
```

**Endpoint Validation:**
```bash
# Validate API endpoints
validate-links validate api-docs/ \
  --check-external \
  --timeout=30 \
  --user-agent="API-Docs-Validator/1.0" \
  --format=html \
  --output=endpoint-report.html
```

**Code Example Validation:**
```bash
# Validate code examples and references
validate-links validate examples/ \
  --scope=internal \
  --include="*.md" \
  --include="*.rst" \
  --format=markdown \
  --output=examples-report.md
```

## 5.4. Advanced Usage Patterns

### 5.4.1. Custom Validation Workflows

**Multi-Environment Validation:**
```bash
#!/bin/bash
# multi-env-validation.sh

environments=("dev" "staging" "prod")

for env in "${environments[@]}"; do
    echo "Validating $env environment..."
    
    validate-links validate docs/ \
      --config="config/validate-links-$env.php" \
      --format=json \
      --output="reports/$env-validation.json"
done
```

**Conditional Validation:**
```bash
#!/bin/bash
# conditional-validation.sh

# Check if external validation is needed
if [ "$VALIDATE_EXTERNAL" = "true" ]; then
    SCOPE_OPTIONS="--scope=all --check-external"
else
    SCOPE_OPTIONS="--scope=internal --scope=anchor"
fi

validate-links validate docs/ $SCOPE_OPTIONS \
  --format=json \
  --output=conditional-report.json
```

**Incremental Validation:**
```bash
#!/bin/bash
# incremental-validation.sh

# Get changed files since last commit
CHANGED_FILES=$(git diff --name-only HEAD~1 HEAD | grep -E '\.(md|rst)$')

if [ -n "$CHANGED_FILES" ]; then
    echo "Validating changed files: $CHANGED_FILES"
    validate-links validate $CHANGED_FILES \
      --scope=internal \
      --scope=anchor \
      --max-broken=0
else
    echo "No documentation files changed"
fi
```

### 5.4.2. Integration Patterns

**Webhook Integration:**
```bash
#!/bin/bash
# webhook-validation.sh

# Run validation
validate-links validate docs/ \
  --format=json \
  --output=webhook-report.json

# Send results to webhook
curl -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -d @webhook-report.json
```

**Slack Integration:**
```bash
#!/bin/bash
# slack-notification.sh

# Run validation and capture exit code
validate-links validate docs/ \
  --format=json \
  --output=slack-report.json
EXIT_CODE=$?

# Send Slack notification based on result
if [ $EXIT_CODE -eq 0 ]; then
    MESSAGE="✅ Link validation passed"
    COLOR="good"
else
    MESSAGE="❌ Link validation failed"
    COLOR="danger"
fi

curl -X POST "$SLACK_WEBHOOK" \
  -H "Content-Type: application/json" \
  -d "{\"text\":\"$MESSAGE\",\"color\":\"$COLOR\"}"
```

**Database Integration (Optional):**

**⚠️ Note:** Database integration is completely optional. See [Optional Database Integration](800-appendices/050-optional-database-integration.md) for detailed information.
```php
<?php
// database-integration.php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Run validation
$exitCode = Artisan::call('validate', [
    'path' => ['docs/'],
    '--format' => 'json',
    '--output' => 'db-report.json'
]);

// Parse results
$results = json_decode(file_get_contents('db-report.json'), true);

// Store in database
DB::table('validation_reports')->insert([
    'timestamp' => now(),
    'status' => $exitCode === 0 ? 'passed' : 'failed',
    'summary' => json_encode($results['summary']),
    'results' => json_encode($results['results']),
]);
```

### 5.4.3. Performance Optimization

**Large Project Optimization:**
```bash
# Optimize for large projects
validate-links validate docs/ \
  --batch-size=50 \
  --concurrency=20 \
  --cache \
  --memory-limit=1024M \
  --timeout=60
```

**Parallel Processing:**
```bash
#!/bin/bash
# parallel-validation.sh

# Split directories for parallel processing
directories=("docs/api" "docs/guides" "docs/tutorials")

# Run validations in parallel
for dir in "${directories[@]}"; do
    validate-links validate "$dir" \
      --format=json \
      --output="reports/$(basename $dir)-report.json" &
done

# Wait for all processes to complete
wait

# Combine results
echo "All validations completed"
```

**Caching Strategy:**
```bash
# Enable aggressive caching for repeated validations
validate-links validate docs/ \
  --cache \
  --cache-ttl=86400 \
  --check-external \
  --format=json \
  --output=cached-report.json
```

## 5.5. Troubleshooting Guide

### 5.5.1. Common Issues

**Issue: Validation Takes Too Long**

*Symptoms:* Validation process hangs or takes excessive time

*Solutions:*
```bash
# Reduce timeout for external links
validate-links validate docs/ --timeout=15

# Disable external link validation
validate-links validate docs/ --scope=internal --scope=anchor

# Increase concurrency (with caution)
validate-links validate docs/ --concurrency=5
```

**Issue: Memory Errors**

*Symptoms:* PHP memory limit exceeded errors

*Solutions:*
```bash
# Increase memory limit
validate-links validate docs/ --memory-limit=1024M

# Reduce batch size
validate-links validate docs/ --batch-size=25

# Process directories separately
validate-links validate docs/section1/
validate-links validate docs/section2/
```

**Issue: False Positives**

*Symptoms:* Valid links reported as broken

*Solutions:*
```bash
# Check case sensitivity
validate-links validate docs/ --case-sensitive=false

# Verify file extensions
validate-links validate docs/ --verbose

# Test specific files
validate-links validate problematic-file.md --verbose
```

### 5.5.2. Configuration Issues

**Issue: Configuration Not Loading**

*Symptoms:* Default settings used instead of custom configuration

*Solutions:*
```bash
# Verify configuration file exists
ls -la config/validate-links.php

# Test configuration loading
validate-links config show

# Use explicit configuration path
validate-links validate docs/ --config=config/validate-links.php
```

**Issue: Environment Variables Not Working**

*Symptoms:* Environment variables not being recognized

*Solutions:*
```bash
# Check .env file
cat .env | grep VALIDATE_LINKS

# Verify environment loading
php -r "echo getenv('VALIDATE_LINKS_TIMEOUT');"

# Use explicit values
validate-links validate docs/ --timeout=60
```

### 5.5.3. Network Issues

**Issue: External Link Timeouts**

*Symptoms:* External links consistently timing out

*Solutions:*
```bash
# Increase timeout
validate-links validate docs/ --check-external --timeout=120

# Use custom user agent
validate-links validate docs/ --check-external --user-agent="MyBot/1.0"

# Test specific URLs manually
curl -I "https://example.com"
```

**Issue: SSL Certificate Errors**

*Symptoms:* SSL verification failures for valid sites

*Solutions:*
```bash
# Disable SSL verification (not recommended for production)
validate-links validate docs/ --check-external --verify-ssl=false

# Update CA certificates
# On Ubuntu/Debian: sudo apt-get update && sudo apt-get install ca-certificates
# On macOS: brew install ca-certificates
```

### 5.5.4. Performance Issues

**Issue: High Memory Usage**

*Symptoms:* System becomes unresponsive during validation

*Solutions:*
```bash
# Monitor memory usage
validate-links validate docs/ --verbose --memory-limit=512M

# Use smaller batch sizes
validate-links validate docs/ --batch-size=10

# Process incrementally
find docs/ -name "*.md" | head -10 | xargs validate-links validate
```

**Issue: Slow External Link Validation**

*Symptoms:* External link validation takes excessive time

*Solutions:*
```bash
# Enable caching
validate-links validate docs/ --check-external --cache

# Reduce concurrency
validate-links validate docs/ --check-external --concurrency=3

# Add request delays
validate-links validate docs/ --check-external --request-delay=1000
```

## 5.6. Best Practices

### 5.6.1. Documentation Standards

**Link Formatting:**
```markdown
<!-- Good: Descriptive link text -->
[User Authentication Guide](./auth/user-guide.md)

<!-- Bad: Generic link text -->
[Click here](./auth/user-guide.md)

<!-- Good: Relative paths -->
[API Reference](../api/reference.md)

<!-- Bad: Absolute paths -->
[API Reference](/full/path/to/api/reference.md)
```

**File Organization:**
```
docs/
├── README.md
├── getting-started/
│   ├── installation.md
│   └── quick-start.md
├── guides/
│   ├── user-guide.md
│   └── admin-guide.md
└── api/
    ├── reference.md
    └── examples.md
```

**Anchor Links:**
```markdown
<!-- Good: Clear, descriptive anchors -->
## User Authentication {#user-authentication}

<!-- Good: Reference to anchor -->
See [User Authentication](#user-authentication) for details.

<!-- Bad: Generic anchors -->
## Section 1 {#section1}
```

### 5.6.2. Validation Strategies

**Development Workflow:**
1. **Pre-commit Validation**
   ```bash
   # Add to .git/hooks/pre-commit
   validate-links validate docs/ --scope=internal --max-broken=0
   ```

2. **Branch Validation**
   ```bash
   # Validate changes in feature branches
   validate-links validate docs/ --scope=internal --scope=anchor
   ```

3. **Release Validation**
   ```bash
   # Comprehensive validation before releases
   validate-links validate docs/ --scope=all --check-external --max-broken=0
   ```

**CI/CD Integration:**
1. **Pull Request Validation**
   - Validate only changed files
   - Use internal and anchor scopes
   - Fail fast on broken links

2. **Deployment Validation**
   - Comprehensive validation
   - Include external links
   - Generate detailed reports

3. **Scheduled Validation**
   - Regular external link health checks
   - Monitor link degradation over time
   - Automated notifications

### 5.6.3. Team Collaboration

**Role-Based Workflows:**

**Content Creators:**
- Use interactive mode for complex validations
- Focus on internal and anchor links
- Generate HTML reports for review

**Reviewers:**
- Use comprehensive validation with all scopes
- Review validation reports before approval
- Ensure external links are current and relevant

**Maintainers:**
- Implement automated validation in CI/CD
- Monitor validation metrics over time
- Maintain validation configuration and standards

**Communication:**
- Share validation reports with team members
- Document validation standards and procedures
- Provide training on validation tools and processes

### 5.6.4. Maintenance Procedures

**Regular Maintenance:**
```bash
# Weekly comprehensive validation
validate-links validate docs/ \
  --scope=all \
  --check-external \
  --format=html \
  --output=weekly-report.html

# Monthly external link audit
validate-links validate docs/ \
  --scope=external \
  --timeout=60 \
  --format=csv \
  --output=external-links-audit.csv
```

**Link Cleanup:**
```bash
# Identify and fix broken internal links
validate-links fix docs/ --scope=internal --backup

# Generate cleanup report
validate-links report docs/ \
  --format=markdown \
  --output=cleanup-report.md \
  --group-by=status
```

**Documentation Updates:**
```bash
# Validate after major updates
validate-links validate docs/ \
  --scope=all \
  --max-broken=0 \
  --format=json \
  --output=update-validation.json

# Generate change impact report
validate-links report docs/ \
  --format=html \
  --output=impact-report.html \
  --include-valid
```

## 5.7. Migration Guide

### 5.7.1. From Other Tools

**From markdown-link-check:**
```bash
# Old command
markdown-link-check docs/**/*.md

# New equivalent
validate-links validate docs/ --scope=all --check-external
```

**From linkchecker:**
```bash
# Old command
linkchecker --check-extern docs/

# New equivalent
validate-links validate docs/ --check-external --format=html
```

**Configuration Migration:**
```bash
# Export current configuration
validate-links config export --file=migration-config.json

# Import to new environment
validate-links config import --file=migration-config.json
```

### 5.7.2. Legacy Project Integration

**Gradual Migration:**
```bash
# Phase 1: Internal links only
validate-links validate docs/ --scope=internal

# Phase 2: Add anchor validation
validate-links validate docs/ --scope=internal --scope=anchor

# Phase 3: Full validation
validate-links validate docs/ --scope=all --check-external
```

**Compatibility Mode:**
```bash
# Use legacy-compatible settings
validate-links validate docs/ \
  --case-sensitive=false \
  --follow-symlinks=false \
  --max-broken=100
```

## 5.8. Success Metrics

### 5.8.1. Quality Metrics

**Link Health Metrics:**
- Broken link percentage
- External link response times
- Link validation coverage
- Fix success rate

**Process Metrics:**
- Validation frequency
- Time to fix broken links
- Team adoption rate
- Automation coverage

**Monitoring Dashboard:**
```bash
# Generate metrics report
validate-links report docs/ \
  --format=json \
  --output=metrics.json \
  --include-valid

# Extract key metrics
jq '.summary | {
  total_links: .total_links,
  broken_links: .broken_links,
  success_rate: ((.total_links - .broken_links) / .total_links * 100)
}' metrics.json
```

### 5.8.2. Performance Benchmarks

**Validation Speed:**
- Files per second processed
- Links per second validated
- Memory usage efficiency
- Cache hit rates

**Benchmark Testing:**
```bash
# Performance benchmark
time validate-links validate docs/ \
  --scope=all \
  --format=json \
  --output=benchmark.json

# Memory usage monitoring
/usr/bin/time -v validate-links validate docs/ \
  --scope=internal \
  --batch-size=100
```

---

## 📖 Navigation

**[⬅️ Previous: API Reference](040-api-reference.md)** | **[Next: Code Documentation ➡️](060-code-documentation.md)** | **[🔝 Top](#5-usage-guide)**
