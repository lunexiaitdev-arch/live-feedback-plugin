#!/usr/bin/env python3
"""
Generate WordPress.org plugin directory assets:
- icon-128x128.png
- icon-256x256.png
- banner-772x250.png
- banner-1544x500.png
"""

import os
import math
from PIL import Image, ImageDraw, ImageFont

OUTPUT_DIR = os.path.join(os.path.dirname(__file__), "..", "wporg-assets")
os.makedirs(OUTPUT_DIR, exist_ok=True)

def draw_gradient_rect(draw, bbox, start_color, end_color, vertical=True):
    x0, y0, x1, y1 = bbox
    width = x1 - x0
    height = y1 - y0
    steps = height if vertical else width
    for i in range(steps):
        ratio = i / float(steps)
        r = int(start_color[0] + (end_color[0] - start_color[0]) * ratio)
        g = int(start_color[1] + (end_color[1] - start_color[1]) * ratio)
        b = int(start_color[2] + (end_color[2] - start_color[2]) * ratio)
        if vertical:
            draw.line([(x0, y0 + i), (x1, y0 + i)], fill=(r, g, b))
        else:
            draw.line([(x0 + i, y0), (x0 + i, y1)], fill=(r, g, b))

def create_icon(size):
    img = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)

    # Rounded rectangle background with smooth gradient
    bg = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    bg_draw = ImageDraw.Draw(bg)
    draw_gradient_rect(bg_draw, (0, 0, size, size), (2, 132, 199), (3, 105, 161), vertical=True)

    # Mask for rounded corners
    mask = Image.new("L", (size, size), 0)
    mask_draw = ImageDraw.Draw(mask)
    corner_radius = int(size * 0.22)
    mask_draw.rounded_rectangle((0, 0, size, size), radius=corner_radius, fill=255)

    img.paste(bg, (0, 0), mask)

    # Draw pin / chat bubble symbol
    icon_draw = ImageDraw.Draw(img)
    scale = size / 256.0
    
    # Outer white ring / pin shape
    center_x = size / 2.0
    center_y = size * 0.44
    outer_r = 52.0 * scale
    
    # Chat bubble body
    bx0 = int(center_x - 64 * scale)
    by0 = int(center_y - 50 * scale)
    bx1 = int(center_x + 64 * scale)
    by1 = int(center_y + 44 * scale)
    icon_draw.rounded_rectangle((bx0, by0, bx1, by1), radius=int(24 * scale), fill=(255, 255, 255))
    
    # Tail of chat bubble pointing down
    tail = [
        (int(center_x - 16 * scale), int(by1 - 4 * scale)),
        (int(center_x - 30 * scale), int(by1 + 28 * scale)),
        (int(center_x + 12 * scale), int(by1 - 4 * scale)),
    ]
    icon_draw.polygon(tail, fill=(255, 255, 255))
    
    # Target / crosshair dot inside chat bubble
    dot_color = (2, 132, 199)
    # Target pin center
    px = int(center_x)
    py = int(center_y - 3 * scale)
    pr = int(14 * scale)
    icon_draw.ellipse((px - pr, py - pr, px + pr, py + pr), fill=dot_color)

    # Crosshair lines
    line_w = max(2, int(3 * scale))
    gap = int(18 * scale)
    length = int(32 * scale)
    # Left
    icon_draw.line([(px - length, py), (px - gap, py)], fill=dot_color, width=line_w)
    # Right
    icon_draw.line([(px + gap, py), (px + length, py)], fill=dot_color, width=line_w)
    # Top
    icon_draw.line([(px, py - length), (px, py - gap)], fill=dot_color, width=line_w)
    # Bottom
    icon_draw.line([(px, py + gap), (px, py + length)], fill=dot_color, width=line_w)

    return img

def create_banner(width, height):
    img = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)

    # Deep dark slate to vibrant blue gradient background
    draw_gradient_rect(draw, (0, 0, width, height), (15, 23, 42), (2, 132, 199), vertical=False)

    scale = width / 772.0

    # Add subtle decorative circles in the background
    overlay = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    ov_draw = ImageDraw.Draw(overlay)
    
    # Glowing accent rings
    ov_draw.ellipse((int(width * 0.75), int(-height * 0.2), int(width * 1.15), int(height * 1.1)), outline=(255, 255, 255, 25), width=int(3 * scale))
    ov_draw.ellipse((int(width * 0.8), int(-height * 0.1), int(width * 1.1), int(height * 0.9)), outline=(255, 255, 255, 35), width=int(2 * scale))

    img = Image.alpha_composite(img, overlay)
    draw = ImageDraw.Draw(img)

    # Icon on the left
    icon_size = int(120 * scale)
    icon_img = create_icon(icon_size)
    icon_x = int(50 * scale)
    icon_y = int((height - icon_size) / 2)
    img.paste(icon_img, (icon_x, icon_y), icon_img)

    draw = ImageDraw.Draw(img)

    # Fonts
    text_x = icon_x + icon_size + int(36 * scale)
    title_font = None
    sub_font = None
    badge_font = None

    # Try system fonts
    font_paths = [
        "/System/Library/Fonts/SFPro-Bold.otf",
        "/System/Library/Fonts/HelveticaNeue.ttc",
        "/System/Library/Fonts/Supplemental/Arial Bold.ttf",
        "/System/Library/Fonts/Supplemental/Arial.ttf",
    ]
    for fp in font_paths:
        if os.path.exists(fp):
            try:
                title_font = ImageFont.truetype(fp, int(34 * scale))
                sub_font = ImageFont.truetype(fp, int(15 * scale))
                badge_font = ImageFont.truetype(fp, int(11 * scale))
                break
            except Exception:
                continue

    if not title_font:
        title_font = ImageFont.load_default()
        sub_font = ImageFont.load_default()
        badge_font = ImageFont.load_default()

    # Category badge
    badge_text = "VISUAL FEEDBACK & BUG TRACKER"
    badge_y = int(icon_y + 8 * scale)
    draw.text((text_x, badge_y), badge_text, font=badge_font, fill=(56, 189, 248))

    # Main title
    title_text = "Lunexia Live Feedback"
    title_y = int(badge_y + 22 * scale)
    draw.text((text_x, title_y), title_text, font=title_font, fill=(255, 255, 255))

    # Subtitle
    sub_text = "Contextual on-page visual feedback, pins & real-time collaboration"
    sub_y = int(title_y + 46 * scale)
    draw.text((text_x, sub_y), sub_text, font=sub_font, fill=(203, 213, 225))

    # Feature pills
    pills = ["💬 Guest Submissions", "🎯 Pin Coordinates", "📸 Screenshots", "⚡ Instant Sync"]
    pill_x = text_x
    pill_y = int(sub_y + 32 * scale)
    pill_padding = int(12 * scale)
    pill_h = int(22 * scale)

    for p in pills:
        bbox = badge_font.getbbox(p)
        tw = bbox[2] - bbox[0]
        pw = tw + pill_padding * 2
        # Pill background
        draw.rounded_rectangle((pill_x, pill_y, pill_x + pw, pill_y + pill_h), radius=int(6 * scale), fill=(255, 255, 255, 30))
        draw.text((pill_x + pill_padding, pill_y + int(4 * scale)), p, font=badge_font, fill=(241, 245, 249))
        pill_x += pw + int(10 * scale)

    return img

def main():
    print("Generating WordPress.org plugin directory assets in wporg-assets/...")
    
    # Icons
    i128 = create_icon(128)
    i128.save(os.path.join(OUTPUT_DIR, "icon-128x128.png"), "PNG")
    print("✓ Created icon-128x128.png")

    i256 = create_icon(256)
    i256.save(os.path.join(OUTPUT_DIR, "icon-256x256.png"), "PNG")
    print("✓ Created icon-256x256.png")

    # Banners
    b772 = create_banner(772, 250)
    b772.save(os.path.join(OUTPUT_DIR, "banner-772x250.png"), "PNG")
    print("✓ Created banner-772x250.png")

    b1544 = create_banner(1544, 500)
    b1544.save(os.path.join(OUTPUT_DIR, "banner-1544x500.png"), "PNG")
    print("✓ Created banner-1544x500.png")

    print(f"\nAll assets successfully generated in {OUTPUT_DIR}")

if __name__ == "__main__":
    main()
