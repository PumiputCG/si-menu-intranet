param(
  [string] $SourceDirectory = '',
  [string] $TargetDirectory = '\\192.168.5.7\www\SiMenu\data\announcement_register'
)

$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($SourceDirectory)) {
  $hrRoot = '\\192.168.5.1\_DriveZ\_HR'
  $levelOne = @(Get-ChildItem -LiteralPath $hrRoot -Directory | Where-Object { $_.Name -like '5.*' })
  if ($levelOne.Count -ne 1) {
    throw "Expected one 5.* directory below $hrRoot, found $($levelOne.Count)"
  }

  $levelTwo = @(Get-ChildItem -LiteralPath $levelOne[0].FullName -Directory | Where-Object { $_.Name -like '3.Announce  Company -*' })
  if ($levelTwo.Count -ne 1) {
    throw "Expected one announcement directory below $($levelOne[0].FullName), found $($levelTwo.Count)"
  }

  $levelThree = @(Get-ChildItem -LiteralPath $levelTwo[0].FullName -Directory | Where-Object { $_.Name -like '*2569' })
  if ($levelThree.Count -ne 1) {
    throw "Expected one *2569 directory below $($levelTwo[0].FullName), found $($levelThree.Count)"
  }

  $SourceDirectory = $levelThree[0].FullName
}

if (!(Test-Path -LiteralPath $SourceDirectory)) {
  throw "Announcement source is unavailable: $SourceDirectory"
}

$targetParent = Split-Path -Path $TargetDirectory -Parent
if (!(Test-Path -LiteralPath $targetParent)) {
  throw "Announcement target parent is unavailable: $targetParent"
}

$allowedTargetRoot = '\\192.168.5.7\www\SiMenu\data\'
$targetFullPath = [IO.Path]::GetFullPath($TargetDirectory).TrimEnd('\') + '\'
if (!$targetFullPath.StartsWith($allowedTargetRoot, [StringComparison]::OrdinalIgnoreCase)) {
  throw "Refusing to sync outside the allowed target: $targetFullPath"
}

if (!(Test-Path -LiteralPath $TargetDirectory)) {
  New-Item -ItemType Directory -Path $TargetDirectory | Out-Null
}

$robocopyArguments = @(
  $SourceDirectory,
  $TargetDirectory,
  '/E',
  '/XO',
  '/FFT',
  '/R:2',
  '/W:2',
  '/COPY:DAT',
  '/DCOPY:T',
  '/NP',
  '/NFL',
  '/NDL',
  '/XF',
  '~$*'
)

& robocopy.exe @robocopyArguments
$robocopyExitCode = $LASTEXITCODE

if ($robocopyExitCode -ge 8) {
  throw "Announcement sync failed with robocopy exit code $robocopyExitCode"
}

$phpExecutable = 'C:\xampp\php\php.exe'
$linkBuilder = 'C:\xampp\htdocs\SIMenu\scripts\build_announcement_register_links.php'

if (!(Test-Path -LiteralPath $phpExecutable) -or !(Test-Path -LiteralPath $linkBuilder)) {
  throw 'PHP or the announcement link builder is unavailable'
}

& $phpExecutable $linkBuilder $TargetDirectory
if ($LASTEXITCODE -ne 0) {
  throw "Announcement link aliases failed with exit code $LASTEXITCODE"
}

exit 0
