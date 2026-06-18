Add-Type -AssemblyName System.Drawing

function New-ExzentoLogoBitmap([int]$width, [bool]$light) {
    $height = [int]($width * 0.28)
    $bmp = New-Object System.Drawing.Bitmap $width, $height, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit
    $g.Clear([System.Drawing.Color]::Transparent)

    $fontFamily = "Segoe UI"
    $fontSize = [single]($height * 0.52)
    $font = New-Object System.Drawing.Font($fontFamily, $fontSize, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)

    $textColor = if ($light) { [System.Drawing.Color]::White } else { [System.Drawing.Color]::Black }
    $iconColor = if ($light) { [System.Drawing.Color]::White } else { [System.Drawing.Color]::FromArgb(255, 37, 99, 235) }
    $zColor = [System.Drawing.Color]::Black

    $brush = New-Object System.Drawing.SolidBrush $textColor
    $format = New-Object System.Drawing.StringFormat
    $format.Alignment = [System.Drawing.StringAlignment]::Near
    $format.LineAlignment = [System.Drawing.StringAlignment]::Center

    $exSize = $g.MeasureString("Ex", $font)
    $entoSize = $g.MeasureString("ento", $font)
    $iconW = [int]($height * 0.62)
    $iconH = [int]($height * 0.72)
    $gap = [int]($height * 0.06)
    $totalW = $exSize.Width + $gap + $iconW + $gap + $entoSize.Width
    $startX = ($width - $totalW) / 2
    $centerY = $height / 2

    $g.DrawString("Ex", $font, $brush, (New-Object System.Drawing.RectangleF $startX, 0, $exSize.Width, $height), $format)

    $iconX = $startX + $exSize.Width + $gap
    $iconY = ($height - $iconH) / 2
    $radius = [int]($iconW * 0.2)
    $path = New-Object System.Drawing.Drawing2D.GraphicsPath
    $rect = New-Object System.Drawing.RectangleF $iconX, $iconY, $iconW, $iconH
    $path.AddArc($rect.X, $rect.Y + $rect.Height - ($radius * 2), $radius * 2, $radius * 2, 90, 90)
    $path.AddArc($rect.X, $rect.Y, $radius * 2, $radius * 2, 180, 90)
    $path.AddLine($rect.Right - ($radius * 0.15), $rect.Y, $rect.Right - ($iconW * 0.24), $rect.Y)
    $path.AddLine($rect.Right - ($iconW * 0.24), $rect.Y, $rect.Right - ($iconW * 0.24), $rect.Y + ($iconH * 0.24))
    $path.AddLine($rect.Right - ($iconW * 0.24), $rect.Y + ($iconH * 0.24), $rect.Right, $rect.Y + ($iconH * 0.24))
    $path.AddLine($rect.Right, $rect.Y + ($iconH * 0.24), $rect.Right, $rect.Bottom - $radius)
    $path.AddArc($rect.Right - ($radius * 2), $rect.Bottom - ($radius * 2), $radius * 2, $radius * 2, 0, 90)
    $path.CloseFigure()
    $g.FillPath((New-Object System.Drawing.SolidBrush $iconColor), $path)

    $zFont = New-Object System.Drawing.Font($fontFamily, ($fontSize * 0.58), [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $zBrush = if ($light) { New-Object System.Drawing.SolidBrush $iconColor } else { New-Object System.Drawing.SolidBrush $zColor }
    if ($light) {
        $zBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(255, 37, 99, 235))
    }
    $zSize = $g.MeasureString("z", $zFont)
    $zX = $iconX + ($iconW - $zSize.Width) / 2
    $zY = $iconY + ($iconH - $zSize.Height) / 2
    $g.DrawString("z", $zFont, $zBrush, $zX, $zY)

    $entoX = $iconX + $iconW + $gap
    $g.DrawString("ento", $font, $brush, (New-Object System.Drawing.RectangleF $entoX, 0, $entoSize.Width, $height), $format)

    $g.Dispose()
    $font.Dispose()
    $zFont.Dispose()
    $brush.Dispose()
    $zBrush.Dispose()
    $path.Dispose()
    $format.Dispose()

    $minX = $width; $minY = $height; $maxX = 0; $maxY = 0
    for ($y = 0; $y -lt $bmp.Height; $y++) {
        for ($x = 0; $x -lt $bmp.Width; $x++) {
            if ($bmp.GetPixel($x, $y).A -gt 0) {
                if ($x -lt $minX) { $minX = $x }
                if ($y -lt $minY) { $minY = $y }
                if ($x -gt $maxX) { $maxX = $x }
                if ($y -gt $maxY) { $maxY = $y }
            }
        }
    }

    $pad = 8
    $cropW = $maxX - $minX + 1 + ($pad * 2)
    $cropH = $maxY - $minY + 1 + ($pad * 2)
    $cropped = New-Object System.Drawing.Bitmap $cropW, $cropH, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $cg = [System.Drawing.Graphics]::FromImage($cropped)
    $cg.Clear([System.Drawing.Color]::Transparent)
    $cg.DrawImage($bmp, $pad, $pad, (New-Object System.Drawing.Rectangle $minX, $minY, ($maxX - $minX + 1), ($maxY - $minY + 1)), [System.Drawing.GraphicsUnit]::Pixel)
    $cg.Dispose()
    $bmp.Dispose()
    return $cropped
}

$outDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$dark = New-ExzentoLogoBitmap 1200 $false
$light = New-ExzentoLogoBitmap 1200 $true

$dark.Save((Join-Path $outDir "logo.png"), [System.Drawing.Imaging.ImageFormat]::Png)
$light.Save((Join-Path $outDir "logo-white.png"), [System.Drawing.Imaging.ImageFormat]::Png)

$dark.Dispose()
$light.Dispose()
Write-Host "Generated logo.png ($((Get-Item (Join-Path $outDir 'logo.png')).Length) bytes)"
Write-Host "Generated logo-white.png ($((Get-Item (Join-Path $outDir 'logo-white.png')).Length) bytes)"
