# Working with Organizations in GitHub CLI

GitHub CLI (gh) does not currently have a built-in configuration setting for a default organization. However, there are several ways to work efficiently with organizations in GitHub CLI.

## Method 1: Specifying the organization in commands

Most GitHub CLI commands that operate on repositories allow you to specify the organization as part of the command:

```bash
gh repo list ORGANIZATION_NAME
gh issue list --repo ORGANIZATION_NAME/REPOSITORY_NAME
```

Replace `ORGANIZATION_NAME` with the name of your GitHub organization.

## Method 2: Using the GH_REPO environment variable

You can set the `GH_REPO` environment variable to specify a default repository, which includes the organization:

```bash
# Set for the current session
export GH_REPO=ORGANIZATION_NAME/REPOSITORY_NAME

# Or add to your shell profile for persistence
echo 'export GH_REPO=ORGANIZATION_NAME/REPOSITORY_NAME' >> ~/.bashrc  # or ~/.zshrc
```

This will set a default repository for commands that operate on repositories.

## Method 3: Creating aliases

You can create shell aliases to simplify working with specific organizations:

```bash
# Add to your shell profile (~/.bashrc, ~/.zshrc, etc.)
alias ghorg='gh repo list ORGANIZATION_NAME'
alias ghorgissue='gh issue list --repo ORGANIZATION_NAME/REPOSITORY_NAME'
```

## Method 4: Using GitHub CLI aliases

GitHub CLI supports creating command aliases:

```bash
gh alias set org-repos 'repo list ORGANIZATION_NAME'
gh alias set org-issues 'issue list --repo ORGANIZATION_NAME/REPOSITORY_NAME'
```

Then you can use them as:

```bash
gh org-repos
gh org-issues
```

## Examples of using the methods

### Using Method 1 (Specifying the organization in commands)
```bash
# List repositories in the organization
gh repo list ORGANIZATION_NAME

# List issues in a specific repository
gh issue list --repo ORGANIZATION_NAME/REPOSITORY_NAME

# Create a new repository in the organization
gh repo create ORGANIZATION_NAME/NEW_REPO_NAME --public
```

### Using Method 2 (GH_REPO environment variable)
```bash
# Set the environment variable
export GH_REPO=ORGANIZATION_NAME/REPOSITORY_NAME

# Now you can omit the repository flag in commands
gh issue list
gh pr list
```

### Using Method 3 and 4 (Aliases)
```bash
# Using a shell alias
ghorg

# Using a GitHub CLI alias
gh org-repos
```

## Additional configuration options

GitHub CLI offers several configuration options that can enhance your workflow:

```bash
# Set default protocol for git operations
gh config set git_protocol ssh

# Set default editor for editing issues, PRs, etc.
gh config set editor vim

# Set default browser for opening GitHub pages
gh config set browser firefox
```

For more configuration options, refer to the GitHub CLI documentation:
```bash
gh help config
gh help environment
```
