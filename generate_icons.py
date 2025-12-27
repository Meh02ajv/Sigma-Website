"""
Génération des icônes PWA à partir du SVG
Utilise Pillow (PIL) pour créer les icônes PNG
Installation: pip install Pillow cairosvg
"""

try:
    from PIL import Image, ImageDraw, ImageFont
    from cairosvg import svg2png
    import os
    
    print("🎨 Génération des icônes PWA avec CairoSVG + Pillow...")
    
    svg_file = 'img/icon.svg'
    sizes = [(16, 'favicon-16x16.png'), 
             (32, 'favicon-32x32.png'),
             (180, 'apple-touch-icon.png'),
             (192, 'icon-192.png'),
             (512, 'icon-512.png')]
    
    for size, filename in sizes:
        output_path = f'img/{filename}'
        svg2png(url=svg_file, write_to=output_path, output_width=size, output_height=size)
        print(f"✅ {filename} ({size}x{size}) créée")
    
    print("\n✨ Toutes les icônes ont été générées avec succès!")
    
except ImportError:
    print("❌ Modules Python manquants.")
    print("\nPour installer les dépendances:")
    print("  pip install Pillow cairosvg")
    print("\nOu utilisez la méthode alternative ci-dessous...")
    
    # Méthode alternative sans cairosvg
    from PIL import Image, ImageDraw, ImageFont
    
    print("\n🔄 Utilisation de la méthode alternative (Pillow uniquement)...")
    
    # Couleurs SIGMA
    bg_color = (37, 99, 235)  # #2563eb
    text_color = (255, 255, 255)  # #ffffff
    
    sizes = [(16, 'favicon-16x16.png'), 
             (32, 'favicon-32x32.png'),
             (180, 'apple-touch-icon.png'),
             (192, 'icon-192.png'),
             (512, 'icon-512.png')]
    
    for size, filename in sizes:
        # Créer une image avec fond bleu
        img = Image.new('RGB', (size, size), color=bg_color)
        draw = ImageDraw.Draw(img)
        
        # Dessiner le symbole Sigma (Σ)
        try:
            # Essayer d'utiliser une police système
            font_size = int(size * 0.6)
            font = ImageFont.truetype("arial.ttf", font_size)
        except:
            # Fallback sur la police par défaut
            font = ImageFont.load_default()
        
        text = "Σ"
        
        # Centrer le texte
        bbox = draw.textbbox((0, 0), text, font=font)
        text_width = bbox[2] - bbox[0]
        text_height = bbox[3] - bbox[1]
        
        position = ((size - text_width) // 2, (size - text_height) // 2 - int(size * 0.05))
        
        draw.text(position, text, fill=text_color, font=font)
        
        # Sauvegarder
        output_path = f'img/{filename}'
        img.save(output_path, 'PNG')
        print(f"✅ {filename} ({size}x{size}) créée")
    
    print("\n✨ Toutes les icônes ont été générées avec succès!")
    print("\n💡 Note: Pour de meilleures icônes, installez cairosvg:")
    print("   pip install cairosvg")
