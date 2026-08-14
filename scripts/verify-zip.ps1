param(
    [string]$ZipPath = (Join-Path (Join-Path (Split-Path -Parent $PSScriptRoot) 'build') 'wp24h-md-importer.zip')
)

$ErrorActionPreference = 'Stop'
$slug = 'wp24h-md-importer'

if (-not (Test-Path -LiteralPath $ZipPath -PathType Leaf)) {
    throw "Release ZIP not found: $ZipPath"
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::OpenRead((Resolve-Path $ZipPath).Path)

try {
    $entries = @($archive.Entries | ForEach-Object { $_.FullName.Replace('\', '/') })

    if ($entries.Count -eq 0) {
        throw "Release ZIP is empty: $ZipPath"
    }

    $prefix = "$slug/"
    foreach ($entry in $entries) {
        if (-not $entry.StartsWith($prefix, [System.StringComparison]::Ordinal)) {
            throw "Unexpected top-level entry: $entry"
        }
    }

    foreach ($required in @(
        "$slug/$slug.php",
        "$slug/readme.txt",
        "$slug/LICENSE"
    )) {
        if ($entries -notcontains $required) {
            throw "Required release file missing: $required"
        }
    }

    if (-not ($entries | Where-Object { $_.StartsWith("$slug/includes/", [System.StringComparison]::Ordinal) })) {
        throw "Required release directory missing: $slug/includes/"
    }

    foreach ($forbiddenPrefix in @(
        "$slug/.git/",
        "$slug/.github/",
        "$slug/scripts/"
    )) {
        if ($entries | Where-Object { $_.StartsWith($forbiddenPrefix, [System.StringComparison]::Ordinal) }) {
            throw "Forbidden release path found: $forbiddenPrefix"
        }
    }

    foreach ($forbiddenFile in @(
        "$slug/.distignore",
        "$slug/CONTRIBUTING.md",
        "$slug/SECURITY.md",
        "$slug/CHANGELOG.md",
        "$slug/README.md",
        "$slug/example-post.md"
    )) {
        if ($entries -contains $forbiddenFile) {
            throw "Forbidden release file found: $forbiddenFile"
        }
    }

    foreach ($entry in $entries) {
        if ($entry.EndsWith('.zip', [System.StringComparison]::OrdinalIgnoreCase)) {
            throw "Nested ZIP must not be included: $entry"
        }
    }

    Write-Host "Release ZIP verified: $ZipPath ($($entries.Count) entries)"
} finally {
    $archive.Dispose()
}
