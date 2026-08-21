[CmdletBinding()]
param(
	[Parameter(Mandatory = $true)]
	[string] $WordPressVersion
)

$ErrorActionPreference = 'Stop'
$supportedVersions = @('7.1', '7.0.4')

if ($WordPressVersion -notin $supportedVersions) {
	[Console]::Error.WriteLine("Unsupported WordPress version '$WordPressVersion'. Supported versions: 7.1, 7.0.4.")
	exit 2
}

try {
	$null = docker info --format '{{.ServerVersion}}'
	if ($LASTEXITCODE -ne 0) { throw 'Docker daemon is unavailable.' }
} catch {
	Write-Error 'Docker daemon is unavailable. Start Docker and retry.'
	exit 1
}

$project = "wp24h-compat-$($WordPressVersion.Replace('.', ''))"
$compose = @('-p', $project, '-f', 'docker/compatibility.compose.yml')
$env:WP_COMPAT_VERSION = $WordPressVersion
$reportPath = Join-Path $PSScriptRoot "../reports/compatibility/$WordPressVersion.json"
$runnerExitCode = 1

function Invoke-CompatDocker {
	param([string[]] $DockerArguments)
	& docker compose @compose @DockerArguments
	if ($LASTEXITCODE -ne 0) { throw "Docker Compose failed: $($DockerArguments -join ' ')" }
}

function Write-BlockedReport {
	param([string] $Reason)
	@{
		wordpress_version = $WordPressVersion
		status            = 'BLOCKED'
		reason            = $Reason
	} | ConvertTo-Json | Set-Content -Path $reportPath -Encoding utf8
}

try {
	Remove-Item -LiteralPath $reportPath -Force -ErrorAction SilentlyContinue
	try {
		Invoke-CompatDocker -DockerArguments @('pull')
	} catch {
		Write-BlockedReport $_.Exception.Message
		throw
	}

	Invoke-CompatDocker -DockerArguments @('up', '--detach', 'db', 'fixtures', 'wordpress')
	$deadline = (Get-Date).AddMinutes(3)
	$ready = $false
	do {
		$dbHealth = docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$project-db-1" 2>$null
		& docker compose @compose exec -T fixtures wget -q -O /dev/null http://127.0.0.1/featured.png 2>$null
		$fixtureReady = $LASTEXITCODE -eq 0
		& docker compose @compose exec -T wordpress test -f /var/www/html/wp-includes/version.php 2>$null
		$coreReady = $LASTEXITCODE -eq 0
		if ($dbHealth -eq 'healthy' -and $fixtureReady -and $coreReady) { $ready = $true; break }
		Start-Sleep -Milliseconds 500
	} while ((Get-Date) -lt $deadline)
	if (-not $ready) { throw 'Timed out waiting for the database and fixture server.' }

	Invoke-CompatDocker -DockerArguments @('run', '--rm', 'cli', 'core', 'install', '--url=http://wordpress', '--title=WP24H Compatibility', '--admin_user=admin', '--admin_password=admin', '--admin_email=admin@example.test', '--skip-email')
	Invoke-CompatDocker -DockerArguments @('run', '--rm', 'cli', 'plugin', 'activate', 'wp24h-md-importer')
	& docker compose @compose run --rm cli eval 'wp_set_current_user(1); require "/var/www/html/wp-content/plugins/wp24h-md-importer/tests/compatibility/run.php";'
	$runnerExitCode = $LASTEXITCODE
	if ($runnerExitCode -ne 0) { throw "Compatibility runner failed with exit code $runnerExitCode." }
} catch {
	Write-Error $_.Exception.Message
} finally {
	& docker compose @compose down --volumes --remove-orphans
}

exit $runnerExitCode
