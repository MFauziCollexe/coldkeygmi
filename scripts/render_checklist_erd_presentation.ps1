Add-Type -AssemblyName System.Drawing

$width = 1800
$height = 980
$bitmap = New-Object System.Drawing.Bitmap($width, $height)
$graphics = [System.Drawing.Graphics]::FromImage($bitmap)
$graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$graphics.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit
$graphics.Clear([System.Drawing.Color]::FromArgb(248, 250, 252))

$titleFont = New-Object System.Drawing.Font("Segoe UI", 24, [System.Drawing.FontStyle]::Bold)
$subtitleFont = New-Object System.Drawing.Font("Segoe UI", 11, [System.Drawing.FontStyle]::Regular)
$headerFont = New-Object System.Drawing.Font("Segoe UI", 13, [System.Drawing.FontStyle]::Bold)
$bodyFont = New-Object System.Drawing.Font("Segoe UI", 10, [System.Drawing.FontStyle]::Regular)
$smallFont = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Regular)

$titleBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(15, 23, 42))
$subBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(71, 85, 105))
$textBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(30, 41, 59))
$headerBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
$linePen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(100, 116, 139), 3)
$linePen.CustomEndCap = New-Object System.Drawing.Drawing2D.AdjustableArrowCap(5, 7)
$guidePen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(203, 213, 225), 2)

function Draw-Card {
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
    $headerRect = [System.Drawing.Rectangle]::new([int]$X, [int]$Y, [int]$W, 40)
    $bodyRect = [System.Drawing.Rectangle]::new([int]$X, [int]($Y + 40), [int]$W, [int]($H - 40))

    $borderPen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(148, 163, 184), 2)
    $bodyBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
    $headerFill = New-Object System.Drawing.SolidBrush($HeaderColor)

    [void]$graphics.FillRectangle($bodyBrush, $bodyRect)
    [void]$graphics.FillRectangle($headerFill, $headerRect)
    [void]$graphics.DrawRectangle($borderPen, $outerRect)
    [void]$graphics.DrawLine($borderPen, [int]$X, [int]($Y + 40), [int]($X + $W), [int]($Y + 40))
    [void]$graphics.DrawString($Title, $headerFont, $headerBrush, [single]($X + 12), [single]($Y + 9))

    $textY = $Y + 54
    foreach ($line in $Lines) {
        [void]$graphics.DrawString($line, $bodyFont, $textBrush, [single]($X + 12), [single]$textY)
        $textY += 22
    }

    return @{
        Left = $X; Right = ($X + $W); Top = $Y; Bottom = ($Y + $H);
        MidX = ($X + [int]($W / 2)); MidY = ($Y + [int]($H / 2))
    }
}

function Connect-Cards {
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
        [void]$graphics.DrawString($Label, $smallFont, $subBrush, [single]($midX + 8), [single]([Math]::Min($mid1.Y, $mid2.Y) - 18))
    }
}

[void]$graphics.DrawString("GMIIC Checklist DB Flow - Presentation Version", $titleFont, $titleBrush, [single]36, [single]28)
[void]$graphics.DrawString("Versi sederhana untuk menjelaskan alur master data, transaksi, jawaban, scan, dan approval.", $subtitleFont, $subBrush, [single]38, [single]68)

$master = Draw-Card -Title "1. Master Template" -Lines @(
    "checklist_templates",
    "checklist_template_sections",
    "checklist_template_questions",
    "",
    "Menyimpan daftar template, section,",
    "dan pertanyaan checklist."
) -X 80 -Y 180 -W 360 -H 210 -HeaderColor ([System.Drawing.Color]::FromArgb(14, 165, 233))

$header = Draw-Card -Title "2. Checklist Header" -Lines @(
    "checklist_headers",
    "",
    "1 row = 1 checklist transaksi",
    "Contoh: 1 checklist sanitasi",
    "periode April 2026 untuk area tertentu.",
    "",
    "Status utama ada di sini."
) -X 560 -Y 180 -W 360 -H 230 -HeaderColor ([System.Drawing.Color]::FromArgb(34, 197, 94))

$answers = Draw-Card -Title "3. Jawaban Checklist" -Lines @(
    "checklist_answers",
    "",
    "Menyimpan jawaban item/pertanyaan.",
    "Terkait ke question master.",
    "",
    "Bisa pakai scope:",
    "bulan / hari-area / area aktif."
) -X 1040 -Y 180 -W 360 -H 230 -HeaderColor ([System.Drawing.Color]::FromArgb(16, 185, 129))

$process = Draw-Card -Title "4. Proses Pendukung" -Lines @(
    "checklist_scans",
    "checklist_approvals",
    "checklist_audit_logs",
    "",
    "Menyimpan scan barcode,",
    "approval multi-level,",
    "dan histori perubahan."
) -X 1420 -Y 180 -W 300 -H 230 -HeaderColor ([System.Drawing.Color]::FromArgb(245, 158, 11))

$state = Draw-Card -Title "5. Form State JSON" -Lines @(
    "checklist_states",
    "",
    "Untuk data kompleks yang sulit",
    "langsung dipecah jadi tabel rinci.",
    "",
    "Cocok untuk template yang sangat dinamis."
) -X 560 -Y 520 -W 360 -H 210 -HeaderColor ([System.Drawing.Color]::FromArgb(99, 102, 241))

$users = Draw-Card -Title "6. User / Actor" -Lines @(
    "users",
    "",
    "Dipakai sebagai created_by,",
    "answered_by, scanned_by,",
    "approved_by, actor_id."
) -X 80 -Y 540 -W 360 -H 180 -HeaderColor ([System.Drawing.Color]::FromArgb(59, 130, 246))

$notes = Draw-Card -Title "Simple Reading" -Lines @(
    "Master pertanyaan dibuat sekali.",
    "Saat user isi form, dibuat header transaksi.",
    "Jawaban disimpan ke tabel answers.",
    "Scan dan approval dipisah agar mudah dilacak.",
    "JSON state dipakai untuk menjaga fleksibilitas."
) -X 1040 -Y 520 -W 680 -H 180 -HeaderColor ([System.Drawing.Color]::FromArgb(168, 85, 247))

Connect-Cards -From $master -FromSide "right" -To $header -ToSide "left" -Label "template dipakai saat create"
Connect-Cards -From $header -FromSide "right" -To $answers -ToSide "left" -Label "1 checklist punya banyak jawaban"
Connect-Cards -From $header -FromSide "right" -To $process -ToSide "left" -Label "scan / approval / log"
Connect-Cards -From $header -FromSide "bottom" -To $state -ToSide "top" -Label "optional full form snapshot"
Connect-Cards -From $users -FromSide "right" -To $header -ToSide "left" -Label "creator"
Connect-Cards -From $users -FromSide "right" -To $answers -ToSide "bottom" -Label "answer actor"
Connect-Cards -From $users -FromSide "right" -To $process -ToSide "bottom" -Label "scan / approve actor"

$outputPath = Join-Path (Get-Location) "docs\gmiic-checklist-erd-presentation.png"
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
