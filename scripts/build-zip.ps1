param(
    [string]$OutputDirectory = (Join-Path (Split-Path -Parent $PSScriptRoot) 'build')
)

$ErrorActionPreference = 'Stop'

$slug = 'wp24h-md-importer'
$root = (Resolve-Path (Split-Path -Parent $PSScriptRoot)).Path.TrimEnd('\', '/')
$tempRoot = Join-Path ([System.IO.Path]::GetTempPath()) ($slug + '-' + [Guid]::NewGuid().ToString('N'))
$packageDir = Join-Path $tempRoot $slug
$zipFile = Join-Path $OutputDirectory ($slug + '.zip')

function Test-IgnoredPath {
    param([string]$RelativePath)

    $normalized = $RelativePath.Replace('\', '/')
    $ignoredExact = @(
        '.git', '.github', '.distignore', 'scripts',
        'CONTRIBUTING.md', 'SECURITY.md', 'CHANGELOG.md', 'README.md', 'example-post.md',
        '.DS_Store', 'Thumbs.db'
    )

    foreach ($item in $ignoredExact) {
        if ($normalized -eq $item -or $normalized.StartsWith($item + '/')) {
            return $true
        }
    }

    return $normalized.EndsWith('.zip', [System.StringComparison]::OrdinalIgnoreCase)
}

try {
    New-Item -ItemType Directory -Force -Path $packageDir, $OutputDirectory | Out-Null

    Get-ChildItem -LiteralPath $root -Recurse -Force | ForEach-Object {
        $relative = $_.FullName.Substring($root.Length).TrimStart('\', '/')
        if (Test-IgnoredPath $relative) {
            return
        }

        $destination = Join-Path $packageDir $relative
        if ($_.PSIsContainer) {
            New-Item -ItemType Directory -Force -Path $destination | Out-Null
        } else {
            $parent = Split-Path -Parent $destination
            New-Item -ItemType Directory -Force -Path $parent | Out-Null
            Copy-Item -LiteralPath $_.FullName -Destination $destination -Force
        }
    }

    foreach ($required in @("$slug.php", 'readme.txt', 'LICENSE', 'includes')) {
        if (-not (Test-Path -LiteralPath (Join-Path $packageDir $required))) {
            throw "Required release path missing: $required"
        }
    }

    if (Test-Path -LiteralPath $zipFile) {
        Remove-Item -LiteralPath $zipFile -Force
    }

    Compress-Archive -Path $packageDir -DestinationPath $zipFile -CompressionLevel Optimal
    Write-Host "Built $zipFile"
} finally {
    if (Test-Path -LiteralPath $tempRoot) {
        Remove-Item -LiteralPath $tempRoot -Recurse -Force
    }
}
