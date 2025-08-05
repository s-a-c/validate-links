# Sample Markdown Document for Testing

This is a comprehensive markdown document designed for testing link validation across various scenarios.

## External Links

### Valid External Links
- [Example Website](https://example.com)
- [Test Organization](https://test.org)
- [Demo Network](https://demo.net)
- [Secure Example](https://secure.example.com)
- [API Endpoint](https://api.example.com/v1/users)
- [Documentation](https://docs.example.com/guide/installation)

### External Links with Query Parameters
- [Search Results](https://example.com/search?q=test&limit=10)
- [API with Parameters](https://api.service.com/users?page=1&size=20&sort=name)
- [Complex Query](https://example.com/search?query=test%20data&format=json)

### External Links with Fragments
- [Documentation Section](https://docs.example.com/guide#installation)
- [GitHub README](https://github.com/user/repo#readme)
- [Page Section](https://example.com/page#top-section)

## Internal Links

### Relative Links
- [README File](./README.md)
- [Documentation](./docs/installation.md)
- [Configuration Guide](./config/settings.md)
- [Test Files](./tests/unit/example.test.js)

### Absolute Internal Links
- [Root Documentation](/docs/readme.md)
- [Configuration](/config/app.json)
- [Assets](/assets/styles.css)

### Parent Directory Links
- [Parent README](../README.md)
- [Sibling Project](../other-project/docs.md)
- [Root Config](../../config.yml)

## Anchor Links

### Valid Anchors (exist in this document)
- [External Links Section](#external-links)
- [Internal Links Section](#internal-links)
- [Anchor Links Section](#anchor-links)
- [Valid External Links](#valid-external-links)

### Invalid Anchors (don't exist in this document)
- [Non-existent Section](#non-existent-section)
- [Missing Anchor](#missing-anchor)
- [Invalid Reference](#invalid-reference)

## Image Links

### Valid Image Links
![Example Image](https://example.com/images/sample.jpg)
![Local Image](./assets/logo.png)
![Relative Image](../images/banner.gif)

### Image Links with Alt Text
![Company Logo](https://cdn.example.com/logo.svg "Company Logo")
![Profile Picture](./images/profile.jpg "User Profile")

## Mixed Content

Here's a paragraph with multiple link types: [external link](https://example.com), [internal link](./docs/guide.md), and [anchor link](#external-links) all in one place.

### List with Various Links
1. [First external link](https://first.example.com)
2. [Second external link](https://second.example.com)
3. [Internal documentation](./docs/api.md)
4. [Anchor to top](#sample-markdown-document-for-testing)

### Table with Links

| Service | URL | Type |
|---------|-----|------|
| Main Site | [https://example.com](https://example.com) | External |
| API | [https://api.example.com](https://api.example.com) | External |
| Docs | [./docs/readme.md](./docs/readme.md) | Internal |
| Config | [#configuration](#configuration) | Anchor |

## Code Blocks with Links

```markdown
[Example Link](https://example.com)
[Internal Link](./file.md)
```

```html
<a href="https://example.com">Example</a>
<a href="./internal.html">Internal</a>
```

## Problematic Links for Testing

### Broken External Links
- [Broken Link](https://broken.example.com)
- [Not Found](https://example.com/404-page)
- [Server Error](https://example.com/500-error)

### Malformed Links
- [Spaces in URL](https://example.com/path with spaces)
- [Invalid Characters](https://example.com/path<script>)

### Security Test Links
- [JavaScript Protocol](javascript:alert('xss'))
- [Data URI](data:text/html,<script>alert(1)</script>)
- [FTP Link](ftp://example.com/file.txt)

### Path Traversal Attempts
- [Path Traversal](../../../etc/passwd)
- [Windows Path](..\\..\\..\\windows\\system32)

## Configuration

This section exists to test anchor links pointing to it.

### Settings
- timeout: 30 seconds
- max_redirects: 5
- concurrent_requests: 10

## Footer

End of test document. [Back to top](#sample-markdown-document-for-testing)

---

**Note**: This document contains intentionally broken and malicious links for testing purposes. Do not use in production.