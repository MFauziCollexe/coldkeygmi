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

[void]$graphics.DrawString("GMIIC Checklist Database Flow (Recommended Relational Model)", $titleFont, $titleBrush, [single]40, [single]24)
[void]$graphics.DrawString("Master template disimpan terpisah dari transaksi checklist, jawaban, scan, dan approval. Cocok untuk semua template checklist yang sekarang.", $subtitleFont, $subBrush, [single]42, [single]66)

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

$sections = Draw-Table -Title "checklist_template_sections" -Lines @(
    "PK id",
    "FK template_id -> checklist_templates.id",
    "code",
    "title",
    "sort_order",
    "is_active"
) -X 740 -Y 150 -W 360 -H 180 -HeaderColor ([System.Drawing.Color]::FromArgb(6, 182, 212))

$questions = Draw-Table -Title "checklist_template_questions" -Lines @(
    "PK id",
    "FK template_id -> checklist_templates.id",
    "FK section_id -> checklist_template_sections.id",
    "question_code",
    "question_text",
    "answer_type",
    "sort_order",
    "is_required",
    "is_active"
) -X 1180 -Y 130 -W 430 -H 235 -HeaderColor ([System.Drawing.Color]::FromArgb(8, 145, 178))

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
) -X 360 -Y 480 -W 360 -H 270 -HeaderColor ([System.Drawing.Color]::FromArgb(34, 197, 94))

$states = Draw-Table -Title "checklist_states" -Lines @(
    "PK id",
    "FK checklist_header_id -> checklist_headers.id",
    "version_no",
    "state_json",
    "FK saved_by -> users.id",
    "saved_at"
) -X 40 -Y 500 -W 290 -H 180 -HeaderColor ([System.Drawing.Color]::FromArgb(22, 163, 74))

$answers = Draw-Table -Title "checklist_answers" -Lines @(
    "PK id",
    "FK checklist_header_id -> checklist_headers.id",
    "FK template_question_id -> checklist_template_questions.id",
    "FK section_id -> checklist_template_sections.id (nullable)",
    "scope_key",
    "answer_value",
    "note",
    "answered_by",
    "answered_at"
) -X 800 -Y 450 -W 430 -H 235 -HeaderColor ([System.Drawing.Color]::FromArgb(16, 185, 129))

$scans = Draw-Table -Title "checklist_scans" -Lines @(
    "PK id",
    "FK checklist_header_id -> checklist_headers.id",
    "scan_scope",
    "scope_key",
    "barcode_value",
    "scan_date",
    "FK scanned_by -> users.id"
) -X 1280 -Y 470 -W 350 -H 185 -HeaderColor ([System.Drawing.Color]::FromArgb(245, 158, 11))

$approvals = Draw-Table -Title "checklist_approvals" -Lines @(
    "PK id",
    "FK checklist_header_id -> checklist_headers.id",
    "approval_type",
    "scope_key",
    "notes",
    "FK approved_by -> users.id",
    "approved_at"
) -X 1680 -Y 470 -W 330 -H 185 -HeaderColor ([System.Drawing.Color]::FromArgb(249, 115, 22))

$audit = Draw-Table -Title "checklist_audit_logs" -Lines @(
    "PK id",
    "FK checklist_header_id -> checklist_headers.id",
    "action",
    "old_value_json",
    "new_value_json",
    "FK actor_id -> users.id",
    "created_at"
) -X 880 -Y 780 -W 380 -H 185 -HeaderColor ([System.Drawing.Color]::FromArgb(168, 85, 247))

$legend = Draw-Table -Title "Notes" -Lines @(
    "1 checklist entry = 1 row di checklist_headers",
    "Pertanyaan master ada di checklist_template_questions",
    "Jawaban user masuk ke checklist_answers",
    "Data kompleks per template tetap aman di checklist_states.state_json",
    "Scan barcode / area masuk ke checklist_scans",
    "Approval multi-level masuk ke checklist_approvals"
) -X 1340 -Y 760 -W 640 -H 180 -HeaderColor ([System.Drawing.Color]::FromArgb(99, 102, 241))

Connect-Boxes -From $templates -FromSide "right" -To $sections -ToSide "left" -Label "1:N"
Connect-Boxes -From $templates -FromSide "right" -To $questions -ToSide "left" -Label "1:N"
Connect-Boxes -From $sections -FromSide "right" -To $questions -ToSide "left" -Label "1:N"
Connect-Boxes -From $templates -FromSide "bottom" -To $headers -ToSide "top" -Label "1:N"
Connect-Boxes -From $users -FromSide "right" -To $headers -ToSide "left" -Label "created_by"
Connect-Boxes -From $headers -FromSide "left" -To $states -ToSide "right" -Label "1:N"
Connect-Boxes -From $headers -FromSide "right" -To $answers -ToSide "left" -Label "1:N"
Connect-Boxes -From $questions -FromSide "bottom" -To $answers -ToSide "top" -Label "1:N"
Connect-Boxes -From $sections -FromSide "bottom" -To $answers -ToSide "top" -Label "section ref"
Connect-Boxes -From $headers -FromSide "right" -To $scans -ToSide "left" -Label "1:N"
Connect-Boxes -From $headers -FromSide "right" -To $approvals -ToSide "left" -Label "1:N"
Connect-Boxes -From $headers -FromSide "bottom" -To $audit -ToSide "top" -Label "1:N"
Connect-Boxes -From $users -FromSide "bottom" -To $states -ToSide "top" -Label "saved_by"
Connect-Boxes -From $users -FromSide "bottom" -To $answers -ToSide "left" -Label "answered_by"
Connect-Boxes -From $users -FromSide "bottom" -To $scans -ToSide "left" -Label "scanned_by"
Connect-Boxes -From $users -FromSide "bottom" -To $approvals -ToSide "top" -Label "approved_by"
Connect-Boxes -From $users -FromSide "bottom" -To $audit -ToSide "left" -Label "actor_id"

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
