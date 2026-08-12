---
title: "Example Markdown import"
slug: "example-markdown-import"
date: "2026-08-12"
status: "draft"
excerpt: "Example article for testing WP24H MD Importer."
categories:
  - Technology
tags:
  - WordPress
  - Markdown
seo_title: "Example Markdown import"
meta_description: "A sample Markdown file for testing the WP24H MD Importer plugin."
featured_image: "https://example.com/cover.webp"
featured_image_alt: "Example cover for a Markdown article"
sources:
  - "https://wordpress.org/"
---

## This is an example

The importer converts **Markdown** into WordPress post content.

- Front matter becomes post metadata.
- Markdown becomes sanitized HTML.
- Reimporting the same slug can update the post.
- A valid `featured_image` URL is sideloaded into the Media Library and reused on later imports.

> Replace the example image URL with a real public JPEG, PNG, GIF, or WebP URL before testing featured image imports.
