Add-Type -AssemblyName System.Drawing

$src = Join-Path $PSScriptRoot "logo.jpeg"
$dest = Join-Path $PSScriptRoot "logo.png"
$bmp = [System.Drawing.Bitmap]::FromFile($src)
$w = $bmp.Width; $h = $bmp.Height
$png = New-Object System.Drawing.Bitmap $w, $h, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
$g = [System.Drawing.Graphics]::FromImage($png)
$g.DrawImage($bmp, 0, 0)
$g.Dispose()
$bmp.Dispose()

function Test-Bg([System.Drawing.Color]$c) { return ($c.R -lt 40 -and $c.G -lt 40 -and $c.B -lt 40) }

$visited = New-Object 'bool[,]' $w, $h
$queue = [System.Collections.Generic.Queue[object]]::new()
foreach ($seed in @(@(0,0), @(($w-1),0), @(0,($h-1)), @(($w-1),($h-1)))) {
  $sx = [int]$seed[0]; $sy = [int]$seed[1]
  if (-not $visited[$sx,$sy] -and (Test-Bg ($png.GetPixel($sx,$sy)))) { $queue.Enqueue(@($sx,$sy)) }
}

while ($queue.Count -gt 0) {
  $p = $queue.Dequeue()
  $px = [int]$p[0]; $py = [int]$p[1]
  if ($px -lt 0 -or $py -lt 0 -or $px -ge $w -or $py -ge $h) { continue }
  if ($visited[$px,$py]) { continue }
  $c = $png.GetPixel($px,$py)
  if (-not (Test-Bg $c)) { continue }
  $visited[$px,$py] = $true
  $png.SetPixel($px,$py, [System.Drawing.Color]::FromArgb(0,0,0,0))
  $queue.Enqueue(@(($px+1),$py))
  $queue.Enqueue(@(($px-1),$py))
  $queue.Enqueue(@($px,($py+1)))
  $queue.Enqueue(@($px,($py-1)))
}

$png.Save($dest, [System.Drawing.Imaging.ImageFormat]::Png)
$png.Dispose()
Write-Host "logo.png created from logo.jpeg"
