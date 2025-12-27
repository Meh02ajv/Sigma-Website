#!/usr/bin/env node

/**
 * Script pour générer les icônes PWA pour SIGMA Alumni
 * Nécessite: npm install sharp
 * 
 * Usage: node generate-pwa-icons.js
 */

const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

// Couleurs du thème SIGMA
const BACKGROUND_COLOR = '#2563eb';
const TEXT_COLOR = '#ffffff';

// Tailles d'icônes requises
const ICON_SIZES = [
  { size: 192, name: 'icon-192.png' },
  { size: 512, name: 'icon-512.png' },
  { size: 180, name: 'apple-touch-icon.png' }, // Pour iOS
  { size: 32, name: 'favicon-32x32.png' },
  { size: 16, name: 'favicon-16x16.png' }
];

// Vérifier si le dossier img existe
const imgDir = path.join(__dirname, 'img');
if (!fs.existsSync(imgDir)) {
  fs.mkdirSync(imgDir, { recursive: true });
}

/**
 * Crée une icône SVG avec le logo SIGMA
 */
function createSVGIcon(size) {
  const fontSize = Math.floor(size * 0.4);
  const borderRadius = Math.floor(size * 0.15);
  
  return `
    <svg width="${size}" height="${size}" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:#1e3a8a;stop-opacity:1" />
          <stop offset="100%" style="stop-color:#2563eb;stop-opacity:1" />
        </linearGradient>
      </defs>
      <rect width="${size}" height="${size}" rx="${borderRadius}" fill="url(#grad)"/>
      <text 
        x="50%" 
        y="50%" 
        dominant-baseline="middle" 
        text-anchor="middle" 
        font-family="Arial, sans-serif" 
        font-size="${fontSize}" 
        font-weight="bold" 
        fill="${TEXT_COLOR}">Σ</text>
    </svg>
  `;
}

/**
 * Génère toutes les icônes
 */
async function generateIcons() {
  console.log('🎨 Génération des icônes PWA pour SIGMA Alumni...\n');

  for (const { size, name } of ICON_SIZES) {
    const outputPath = path.join(imgDir, name);
    const svg = createSVGIcon(size);

    try {
      await sharp(Buffer.from(svg))
        .png()
        .toFile(outputPath);
      
      console.log(`✅ ${name} (${size}x${size}) créée avec succès`);
    } catch (error) {
      console.error(`❌ Erreur lors de la création de ${name}:`, error.message);
    }
  }

  console.log('\n✨ Génération des icônes terminée !');
  console.log('\n📝 Fichiers créés dans le dossier img/:');
  ICON_SIZES.forEach(({ name }) => console.log(`   - ${name}`));
}

// Exécuter la génération
if (require.main === module) {
  generateIcons().catch(console.error);
}

module.exports = { generateIcons };
