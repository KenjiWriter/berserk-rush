#!/usr/bin/env python3
import json
import math
import os
from PIL import Image, ImageDraw, ImageFilter, ImageEnhance

TARGET_DIR = r"C:\dev\berserk-rush\public\assets\skills\icons"
JSON_PATH = r"C:\dev\berserk-rush\scratch\skills_to_generate.json"

with open(JSON_PATH, "r", encoding="utf-8") as f:
    skills = json.load(f)

# Elemental Themes (Primary Color, Secondary Color, Accent)
THEMES = {
    'bell': ((245, 200, 70), (200, 140, 20), (255, 240, 180), "holy"),
    'wand': ((130, 90, 240), (70, 40, 180), (200, 170, 255), "magic"),
    'sword': ((235, 130, 40), (180, 70, 20), (255, 210, 120), "phys"),
    'axe': ((220, 50, 40), (150, 20, 20), (255, 140, 100), "red"),
    'bow': ((70, 210, 120), (30, 140, 70), (180, 255, 200), "green"),
    'dagger': ((180, 60, 220), (110, 20, 150), (230, 160, 255), "shadow"),
    'all': ((220, 160, 60), (160, 100, 30), (255, 220, 150), "gold"),
}

def create_base_canvas():
    img = Image.new("RGBA", (256, 256), (15, 12, 10, 255))
    draw = ImageDraw.Draw(img)
    
    # Dark radial vignette background
    for r in range(128, 0, -2):
        factor = r / 128.0
        c_val = int(12 + 18 * (1 - factor))
        draw.ellipse([128 - r, 128 - r, 128 + r, 128 + r], fill=(c_val, c_val - 2, c_val - 4, 255))
    return img, draw

def draw_frame(draw, primary_color):
    # Outer border
    draw.rectangle([0, 0, 255, 255], outline=(30, 24, 18, 255), width=4)
    draw.rectangle([4, 4, 251, 251], outline=(primary_color[0]//3, primary_color[1]//3, primary_color[2]//3, 255), width=2)
    # Inner gold accent line
    draw.rectangle([8, 8, 247, 247], outline=primary_color, width=2)
    draw.rectangle([12, 12, 243, 243], outline=(40, 32, 24, 255), width=1)

def draw_glowing_aura(img, center, radius, color):
    aura = Image.new("RGBA", (256, 256), (0, 0, 0, 0))
    a_draw = ImageDraw.Draw(aura)
    cx, cy = center
    a_draw.ellipse([cx - radius, cy - radius, cx + radius, cy + radius], fill=(*color[:3], 140))
    aura = aura.filter(ImageFilter.GaussianBlur(radius=25))
    img.alpha_composite(aura)

def generate_icon_for_skill(skill):
    filename = skill['file']
    target_path = os.path.join(TARGET_DIR, filename)
    if os.path.exists(target_path):
        print(f"Skipping existing: {filename}")
        return

    w_type = skill.get('weapon', 'all')
    if w_type not in THEMES:
        w_type = 'all'
    
    p_col, s_col, acc_col, style_type = THEMES[w_type]
    name = skill['name']
    
    # Specific override themes by name
    if 'Okowy' in name or 'Cienia' in name or 'Jad' in name:
        p_col, s_col, acc_col = (160, 40, 220), (90, 10, 150), (220, 140, 255)
    elif 'Mroźny' in name or 'Chłód' in name or 'Lodowe' in name:
        p_col, s_col, acc_col = (60, 180, 240), (20, 100, 180), (180, 235, 255)
    elif 'Płonący' in name or 'Ogień' in name or 'Grad' in name:
        p_col, s_col, acc_col = (255, 100, 30), (180, 40, 10), (255, 200, 100)
    elif 'Boska' in name or 'Świętej' in name or 'Uzdrowienie' in name or 'Hymn' in name:
        p_col, s_col, acc_col = (250, 210, 80), (190, 140, 20), (255, 245, 180)

    img, draw = create_base_canvas()
    
    # Background glowing aura
    draw_glowing_aura(img, (128, 128), 75, p_col)
    
    # Re-acquire draw object after alpha composite
    draw = ImageDraw.Draw(img)
    
    # Draw central emblem icon geometry based on skill type
    cx, cy = 128, 128
    
    # Outer decorative glow ring
    draw.ellipse([cx - 65, cy - 65, cx + 65, cy + 65], outline=(*p_col, 180), width=3)
    draw.ellipse([cx - 55, cy - 55, cx + 55, cy + 55], outline=(*acc_col, 120), width=1)
    
    # Skill-specific symbols
    if 'Bariera' in name or 'Osłona' in name or 'Postawa' in name or 'Skóra' in name or 'Wola' in name:
        # Shield emblem
        points = [(cx, cy - 45), (cx + 40, cy - 30), (cx + 35, cy + 15), (cx, cy + 45), (cx - 35, cy + 15), (cx - 40, cy - 30)]
        draw.polygon(points, fill=(*s_col, 220), outline=acc_col)
        draw.polygon([(cx, cy - 35), (cx + 25, cy - 23), (cx + 22, cy + 10), (cx, cy + 32), (cx - 22, cy + 10), (cx - 25, cy - 23)], fill=(*p_col, 255))
        draw.line([(cx, cy - 35), (cx, cy + 32)], fill=acc_col, width=3)
        draw.line([(cx - 20, cy - 5), (cx + 20, cy - 5)], fill=acc_col, width=3)
    elif 'Dźwięk' in name or 'Chór' in name or 'Hymn' in name or 'Requiem' in name or 'Rezonans' in name or 'Krąg' in name:
        # Holy Bell / Cross
        draw.polygon([(cx, cy - 40), (cx + 30, cy + 20), (cx - 30, cy + 20)], fill=(*p_col, 255), outline=acc_col)
        draw.rectangle([cx - 35, cy + 20, cx + 35, cy + 28], fill=acc_col)
        draw.ellipse([cx - 8, cy + 28, cx + 8, cy + 40], fill=p_col)
        # Rays
        for angle in range(0, 360, 45):
            rad = math.radians(angle)
            x1 = cx + math.cos(rad) * 48
            y1 = cy + math.sin(rad) * 48
            x2 = cx + math.cos(rad) * 65
            y2 = cy + math.sin(rad) * 65
            draw.line([(x1, y1), (x2, y2)], fill=acc_col, width=3)
    elif 'Cięcie' in name or 'Wir' in name or 'Krok' in name or 'Zagłada' in name:
        # Slashing blades
        draw.line([(cx - 45, cy + 45), (cx + 45, cy - 45)], fill=acc_col, width=8)
        draw.line([(cx - 45, cy - 45), (cx + 45, cy + 45)], fill=p_col, width=8)
        # Diamond center
        draw.polygon([(cx, cy - 15), (cx + 15, cy), (cx, cy + 15), (cx - 15, cy)], fill=(255, 255, 255, 240))
    elif 'Strzał' in name or 'Deszcz' in name or 'Unik' in name:
        # Arrows / Bow
        for dx in [-20, 0, 20]:
            draw.line([(cx + dx, cy + 40), (cx + dx*1.5, cy - 40)], fill=p_col, width=5)
            draw.polygon([(cx + dx*1.5, cy - 48), (cx + dx*1.5 - 8, cy - 35), (cx + dx*1.5 + 8, cy - 35)], fill=acc_col)
    else:
        # Magic Orb / Slam
        draw.ellipse([cx - 35, cy - 35, cx + 35, cy + 35], fill=(*s_col, 220), outline=acc_col)
        draw.ellipse([cx - 22, cy - 22, cx + 22, cy + 22], fill=(*p_col, 255))
        draw.ellipse([cx - 10, cy - 10, cx + 10, cy + 10], fill=(255, 255, 255, 240))

    # Decorative frame on top
    draw_frame(draw, p_col)
    
    img.save(target_path, "PNG")
    print(f"Generated procedural skill icon: {filename}")

count = 0
for s in skills:
    generate_icon_for_skill(s)
    count += 1

print(f"Finished processing icons for all {count} skills.")
