# Script PowerShell pour générer les icônes PWA sans dépendances Node.js
# Utilise ImageMagick (à installer: https://imagemagick.org/script/download.php)

Write-Host "🎨 Génération des icônes PWA pour SIGMA Alumni..." -ForegroundColor Cyan
Write-Host ""

# Vérifier si ImageMagick est installé
$magickPath = Get-Command magick -ErrorAction SilentlyContinue
if (-not $magickPath) {
    Write-Host "❌ ImageMagick n'est pas installé." -ForegroundColor Red
    Write-Host "Téléchargez-le depuis: https://imagemagick.org/script/download.php" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Alternative: Créez manuellement les icônes dans img/ avec:" -ForegroundColor Yellow
    Write-Host "  - icon-192.png (192x192)" -ForegroundColor Yellow
    Write-Host "  - icon-512.png (512x512)" -ForegroundColor Yellow
    Write-Host "  - apple-touch-icon.png (180x180)" -ForegroundColor Yellow
    Write-Host "  - favicon-32x32.png (32x32)" -ForegroundColor Yellow
    Write-Host "  - favicon-16x16.png (16x16)" -ForegroundColor Yellow
    exit 1
}

# Créer le dossier img s'il n'existe pas
$imgDir = "img"
if (-not (Test-Path $imgDir)) {
    New-Item -ItemType Directory -Path $imgDir | Out-Null
}

# Couleurs SIGMA
$bgColor = "#2563eb"
$textColor = "#ffffff"

# Fonction pour créer une icône
function Create-Icon {
    param (
        [int]$Size,
        [string]$OutputName
    )
    
    $outputPath = Join-Path $imgDir $OutputName
    $fontSize = [math]::Floor($Size * 0.4)
    $borderRadius = [math]::Floor($Size * 0.15)
    
    # Créer l'icône avec ImageMagick
    & magick -size "${Size}x${Size}" `
        xc:"$bgColor" `
        -fill "$textColor" `
        -font Arial-Bold `
        -pointsize $fontSize `
        -gravity center `
        -annotate +0+0 "Σ" `
        -background "$bgColor" `
        -alpha remove `
        "$outputPath"
    
    if ($?) {
        Write-Host "✅ $OutputName (${Size}x${Size}) créée avec succès" -ForegroundColor Green
    } else {
        Write-Host "❌ Erreur lors de la création de $OutputName" -ForegroundColor Red
    }
}

# Générer toutes les icônes
Create-Icon -Size 192 -OutputName "icon-192.png"
Create-Icon -Size 512 -OutputName "icon-512.png"
Create-Icon -Size 180 -OutputName "apple-touch-icon.png"
Create-Icon -Size 32 -OutputName "favicon-32x32.png"
Create-Icon -Size 16 -OutputName "favicon-16x16.png"

Write-Host ""
Write-Host "✨ Génération des icônes terminée !" -ForegroundColor Green
Write-Host ""
Write-Host "📝 Fichiers créés dans le dossier img/:" -ForegroundColor Cyan
Write-Host "   - icon-192.png"
Write-Host "   - icon-512.png"
Write-Host "   - apple-touch-icon.png"
Write-Host "   - favicon-32x32.png"
Write-Host "   - favicon-16x16.png"
