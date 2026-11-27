Add-Type -AssemblyName System.Drawing

$width = 2200
$height = 1500
$bitmap = New-Object System.Drawing.Bitmap($width, $height)
$graphics = [System.Drawing.Graphics]::FromImage($bitmap)
$graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$graphics.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit
$graphics.Clear([System.Drawing.Color]::FromArgb(248, 250, 252))

$titleFont = New-Object System.Drawing.Font("Segoe UI", 24, [System.Drawing.FontStyle]::Bold)
$subtitleFont = New-Object System.Drawing.Font("Segoe UI", 10, [System.Drawing.FontStyle]::Regular)
$headerFont = New-Object System.Drawing.Font("Segoe UI", 12, [System.Drawing.FontStyle]::Bold)
$bodyFont = New-Object System.Drawing.Font("Consolas", 10, [System.Drawing.FontStyle]::Regular)
$smallFont = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Regular)

$titleBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(15, 23, 42))
$subBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(71, 85, 105))
$textBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(30, 41, 59))
$headerBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
$linePen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(100, 116, 139), 2)
$linePen.CustomEndCap = New-Object System.Drawing.Drawing2D.AdjustableArrowCap(4, 6)
$guidePen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(203, 213, 225), 2)

function Draw-Table {
    param(
        [string]$Title,
        [string[]]$Lines,
        [int]$X,
        [int]$Y,
        [int]$W,
        [int]$H,
        [System.Drawing.Color]$HeaderColor
    )

    $outerRect = [System.Drawing.Rectangle]::new([int]$X, [int]$Y, [int]$W, [int]$H)
    $headerRect = [System.Drawing.Rectangle]::new([int]$X, [int]$Y, [int]$W, 34)
    $bodyRect = [System.Drawing.Rectangle]::new([int]$X, [int]($Y + 34), [int]$W, [int]($H - 34))

    $borderPen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(148, 163, 184), 2)
    $bodyBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
    $headerFill = New-Object System.Drawing.SolidBrush($HeaderColor)

    [void]$graphics.FillRectangle($bodyBrush, $bodyRect)
    [void]$graphics.FillRectangle($headerFill, $headerRect)
    [void]$graphics.DrawRectangle($borderPen, $outerRect)
    [void]$graphics.DrawLine($borderPen, [int]$X, [int]($Y + 34), [int]($X + $W), [int]($Y + 34))
    [void]$graphics.DrawString($Title, $headerFont, $headerBrush, [single]($X + 10), [single]($Y + 8))

    $textY = $Y + 44
    foreach ($line in $Lines) {
        [void]$graphics.DrawString($line, $bodyFont, $textBrush, [single]($X + 10), [single]$textY)
        $textY += 18
    }

    return @{
        X = $X; Y = $Y; W = $W; H = $H;
        Left = $X; Right = ($X + $W); Top = $Y; Bottom = ($Y + $H);
        MidX = ($X + [int]($W / 2)); MidY = ($Y + [int]($H / 2))
    }
}

function Connect-Boxes {
    param(
        [hashtable]$From,
        [string]$FromSide,
        [hashtable]$To,
        [string]$ToSide,
        [string]$Label = ""
    )

    switch ($FromSide) {
        "right" { $p1 = [System.Drawing.Point]::new([int]$From.Right, [int]$From.MidY) }
        "left" { $p1 = [System.Drawing.Point]::new([int]$From.Left, [int]$From.MidY) }
        "top" { $p1 = [System.Drawing.Point]::new([int]$From.MidX, [int]$From.Top) }
        "bottom" { $p1 = [System.Drawing.Point]::new([int]$From.MidX, [int]$From.Bottom) }
    }
    switch ($ToSide) {
        "right" { $p2 = [System.Drawing.Point]::new([int]$To.Right, [int]$To.MidY) }
        "left" { $p2 = [System.Drawing.Point]::new([int]$To.Left, [int]$To.MidY) }
        "top" { $p2 = [System.Drawing.Point]::new([int]$To.MidX, [int]$To.Top) }
        "bottom" { $p2 = [System.Drawing.Point]::new([int]$To.MidX, [int]$To.Bottom) }
    }

    $midX = [int]($p1.X + (($p2.X - $p1.X) / 2))
    $mid1 = [System.Drawing.Point]::new($midX, [int]$p1.Y)
    $mid2 = [System.Drawing.Point]::new($midX, [int]$p2.Y)

    [void]$graphics.DrawLine($guidePen, $p1, $mid1)
    [void]$graphics.DrawLine($guidePen, $mid1, $mid2)
    [void]$graphics.DrawLine($linePen, $mid2, $p2)

    if ($Label -ne "") {
        $labelX = [Math]::Min($mid1.X, $mid2.X) + 6
        $labelY = [Math]::Min($mid1.Y, $mid2.Y) - 18
        [void]$graphics.DrawString($Label, $smallFont, $subBrush, [single]$labelX, [single]$labelY)
    }
}

[void]$graphics.DrawString("GMIIC Checklist Database Flow (Current Storage Model)", $titleFont, $titleBrush, [single]40, [single]24)
[void]$graphics.DrawString("Checklist disimpan sebagai header dan snapshot state; file foto dicatat terpisah di checklist_media.", $subtitleFont, $subBrush, [single]42, [single]66)

$users = Draw-Table -Title "users" -Lines @(
    "PK id",
    "name",
    "email",
    "... existing app users ..."
) -X 40 -Y 160 -W 250 -H 130 -HeaderColor ([System.Drawing.Color]::FromArgb(59, 130, 246))

$templates = Draw-Table -Title "checklist_templates" -Lines @(
    "PK id",
    "code",
    "name",
    "module",
    "is_active",
    "version_no"
) -X 360 -Y 150 -W 300 -H 165 -HeaderColor ([System.Drawing.Color]::FromArgb(14, 165, 233))

$headers = Draw-Table -Title "checklist_headers" -Lines @(
    "PK id",
    "FK template_id -> checklist_templates.id",
    "entry_code",
    "period_type",
    "period_value",
    "area_code",
    "location_code",
    "status",
    "current_step",
    "FK created_by -> users.id",
    "approved_by (nullable)",
    "approved_at (nullable)",
    "payload_summary_json"
) -X 740 -Y 150 -W 400 -H 270 -HeaderColor ([System.Drawing.Color]::FromArgb(34, 197, 94))

$states = Draw-Table -Title "checklist_states" -Lines @(
    "PK id",
    "FK checklist_header_id -> checklist_headers.id",
    "version_no",
    "state_json",
    "FK saved_by -> users.id",
    "saved_at"
) -X 360 -Y 500 -W 320 -H 180 -HeaderColor ([System.Drawing.Color]::FromArgb(22, 163, 74))

$media = Draw-Table -Title "checklist_media" -Lines @(
    "PK id",
    "FK checklist_header_id -> checklist_headers.id",
    "media_type",
    "scope_key",
    "path",
    "url",
    "original_name",
    "sort_order"
) -X 1240 -Y 150 -W 420 -H 220 -HeaderColor ([System.Drawing.Color]::FromArgb(245, 158, 11))

$legend = Draw-Table -Title "Notes" -Lines @(
    "1 checklist entry = 1 row di checklist_headers",
    "Data kompleks per template tetap aman di checklist_states.state_json",
    "Status dan approval terakhir ada pada checklist_headers",
    "Metadata foto disimpan di checklist_media",
    "File foto disimpan pada storage checklist terkonfigurasi"
) -X 740 -Y 520 -W 660 -H 170 -HeaderColor ([System.Drawing.Color]::FromArgb(99, 102, 241))

Connect-Boxes -From $templates -FromSide "bottom" -To $headers -ToSide "top" -Label "1:N"
Connect-Boxes -From $headers -FromSide "left" -To $states -ToSide "right" -Label "1:N"
Connect-Boxes -From $headers -FromSide "right" -To $media -ToSide "left" -Label "1:N"

$outputPath = Join-Path (Get-Location) "docs\gmiic-checklist-erd.png"
$bitmap.Save($outputPath, [System.Drawing.Imaging.ImageFormat]::Png)

$graphics.Dispose()
$bitmap.Dispose()
$titleFont.Dispose()
$subtitleFont.Dispose()
$headerFont.Dispose()
$bodyFont.Dispose()
$smallFont.Dispose()
$titleBrush.Dispose()
$subBrush.Dispose()
$textBrush.Dispose()
$headerBrush.Dispose()
$linePen.Dispose()
$guidePen.Dispose()

Write-Output "Generated: $outputPath"
