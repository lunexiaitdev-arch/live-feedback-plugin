#!/usr/bin/env python3
"""
Generate ultra-clean, pixel-perfect WordPress.org plugin screenshots:
- screenshot-1.png: Frontend feedback overlay with visual pinpointing
- screenshot-2.png: Admin review panel showing feedback list
- screenshot-3.png: Detailed feedback view with comments and metadata
- screenshot-4.png: Plugin settings page
Dimensions: 1440x900 (16:10 standard HD)
"""

import os
from PIL import Image, ImageDraw, ImageFont

OUTPUT_DIRS = [
    os.path.join(os.path.dirname(__file__), "..", "wporg-assets"),
    os.path.join(os.path.dirname(__file__), "..", ".wordpress-org")
]

FONT_REG = "/System/Library/Fonts/Supplemental/Arial.ttf"
FONT_BOLD = "/System/Library/Fonts/Supplemental/Arial Bold.ttf"

def get_font(size, bold=False):
    fp = FONT_BOLD if bold else FONT_REG
    try:
        return ImageFont.truetype(fp, size)
    except Exception:
        return ImageFont.load_default()

def draw_browser_chrome(draw, w, h, title="Lunexia Live Feedback Demo"):
    # Browser window header
    draw.rectangle([(0, 0), (w, 42)], fill=(241, 245, 249))
    draw.line([(0, 42), (w, 42)], fill=(226, 232, 240), width=1)
    
    # Traffic light window dots
    draw.ellipse([(14, 15), (26, 27)], fill=(239, 68, 68)) # red
    draw.ellipse([(34, 15), (46, 27)], fill=(245, 158, 11)) # yellow
    draw.ellipse([(54, 15), (66, 27)], fill=(34, 197, 94))  # green

    # URL bar
    url_box = [(120, 8), (w - 120, 34)]
    draw.rounded_rectangle(url_box, radius=6, fill=(255, 255, 255), outline=(203, 213, 225), width=1)
    
    f_url = get_font(12)
    draw.text((135, 14), f"🔒 https://example.com/shop — {title}", font=f_url, fill=(100, 116, 139))

def draw_admin_sidebar(draw, w, h, active_item="dashboard"):
    # Dark navy sidebar (matching admin UI)
    sb_w = 260
    draw.rectangle([(0, 43), (sb_w, h)], fill=(15, 23, 42))
    draw.line([(sb_w, 43), (sb_w, h)], fill=(30, 41, 59), width=1)

    # Logo / Title
    f_logo = get_font(18, bold=True)
    f_sub = get_font(12)
    draw.text((24, 72), "Lunexia Live Feedback", font=f_logo, fill=(255, 255, 255))
    draw.text((24, 98), "WordPress Feedback Hub", font=f_sub, fill=(148, 163, 184))
    draw.line([(24, 126), (sb_w - 24, 126)], fill=(30, 41, 59), width=1)

    # Menu items
    items = [
        ("dashboard", "📊", "Dashboard"),
        ("feedback", "💬", "Feedback List"),
        ("settings", "⚙️", "Settings")
    ]
    
    y = 146
    f_menu = get_font(15, bold=True)
    for key, icon, label in items:
        is_active = (key == active_item)
        if is_active:
            draw.rounded_rectangle([(16, y), (sb_w - 16, y + 44)], radius=8, fill=(2, 132, 199))
            draw.text((32, y + 12), f"{icon}  {label}", font=f_menu, fill=(255, 255, 255))
        else:
            draw.text((32, y + 12), f"{icon}  {label}", font=f_menu, fill=(203, 213, 225))
        y += 56

    # Bottom version badge
    f_ver = get_font(11)
    draw.text((24, h - 36), "Version 0.1.0 • WordPress.org", font=f_ver, fill=(100, 116, 139))
    return sb_w

# -------------------------------------------------------------
# SCREENSHOT 1: Frontend Feedback Overlay with Visual Pinpointing
# -------------------------------------------------------------
def generate_screenshot_1(w=1440, h=900):
    img = Image.new("RGBA", (w, h), (248, 250, 252))
    draw = ImageDraw.Draw(img)

    draw_browser_chrome(draw, w, h, "Acme Storefront - Live Site")

    # Mock website page header
    draw.rectangle([(0, 43), (w, 115)], fill=(255, 255, 255))
    draw.line([(0, 115), (w, 115)], fill=(226, 232, 240), width=1)

    f_brand = get_font(22, bold=True)
    draw.text((80, 66), "ACME STORE", font=f_brand, fill=(15, 23, 42))

    f_nav = get_font(14)
    navs = ["Products", "Collections", "About Us", "Contact"]
    nx = 260
    for nav in navs:
        draw.text((nx, 72), nav, font=f_nav, fill=(71, 85, 105))
        nx += 120

    # Cart button in header
    draw.rounded_rectangle([(w - 200, 62), (w - 80, 98)], radius=6, fill=(2, 132, 199))
    draw.text((w - 170, 72), "Cart (2)", font=get_font(13, bold=True), fill=(255, 255, 255))

    # Hero Banner on the mock website
    hero_h = 320
    draw.rectangle([(0, 116), (w, 116 + hero_h)], fill=(241, 245, 249))
    
    f_hero_title = get_font(36, bold=True)
    draw.text((120, 180), "Summer Collection 2026", font=f_hero_title, fill=(15, 23, 42))
    
    f_hero_sub = get_font(16)
    draw.text((120, 234), "Discover high performance outdoor gear designed for everyday comfort.", font=f_hero_sub, fill=(100, 116, 139))
    
    # Hero CTA Button
    btn_box = [(120, 280), (300, 330)]
    draw.rounded_rectangle(btn_box, radius=8, fill=(15, 23, 42))
    draw.text((158, 296), "Shop Now →", font=get_font(15, bold=True), fill=(255, 255, 255))

    # Product cards section
    py = 470
    draw.text((120, py), "Featured Items", font=get_font(24, bold=True), fill=(15, 23, 42))
    
    cards = [
        ("Alpine Explorer Pack", "$149.00", "High durability 40L waterproof backpack"),
        ("Trailblazer Jacket", "$189.00", "Breathable stormproof windbreaker"),
        ("TrekLite Running Shoes", "$129.00", "Ultralight cushioned trail sneakers"),
    ]
    cx = 120
    for title, price, desc in cards:
        card_box = [(cx, py + 40), (cx + 360, py + 340)]
        draw.rounded_rectangle(card_box, radius=12, fill=(255, 255, 255), outline=(226, 232, 240), width=1)
        # Mock image placeholder inside card
        draw.rounded_rectangle([(cx + 12, py + 52), (cx + 348, py + 230)], radius=8, fill=(241, 245, 249))
        draw.text((cx + 140, py + 130), "📷 Preview", font=get_font(14), fill=(148, 163, 184))
        # Title and price
        draw.text((cx + 20, py + 248), title, font=get_font(16, bold=True), fill=(15, 23, 42))
        draw.text((cx + 20, py + 276), price, font=get_font(15, bold=True), fill=(2, 132, 199))
        draw.text((cx + 20, py + 302), desc, font=get_font(12), fill=(100, 116, 139))
        cx += 400

    # ---------------------------------------------------------
    # LIVE FEEDBACK OVERLAY ELEMENTS
    # ---------------------------------------------------------
    # 1. Floating Right-Side Feedback Toggle
    t_y = 380
    draw.rounded_rectangle([(w - 56, t_y), (w, t_y + 110)], radius=12, fill=(2, 132, 199))
    draw.text((w - 44, t_y + 16), "💬", font=get_font(20), fill=(255, 255, 255))
    draw.text((w - 44, t_y + 60), "📌", font=get_font(20), fill=(255, 255, 255))

    # 2. Coordinate Pin on the Hero CTA Button
    pin_x, pin_y = 280, 270
    draw.ellipse([(pin_x - 18, pin_y - 18), (pin_x + 18, pin_y + 18)], fill=(2, 132, 199), outline=(255, 255, 255), width=3)
    draw.text((pin_x - 4, pin_y - 9), "1", font=get_font(14, bold=True), fill=(255, 255, 255))

    # Pulsing halo around the pin
    draw.ellipse([(pin_x - 28, pin_y - 28), (pin_x + 28, pin_y + 28)], outline=(2, 132, 199, 120), width=2)

    # 3. Live Feedback Modal Popover pinned next to the coordinate
    mx, my = 320, 220
    mw, mh = 380, 360
    # Shadow box
    draw.rounded_rectangle([(mx + 4, my + 6), (mx + mw + 4, my + mh + 6)], radius=14, fill=(0, 0, 0, 30))
    # Main modal
    draw.rounded_rectangle([(mx, my), (mx + mw, my + mh)], radius=14, fill=(255, 255, 255), outline=(203, 213, 225), width=1)

    # Modal Header
    draw.rounded_rectangle([(mx, my), (mx + mw, my + 54)], radius=14, fill=(248, 250, 252))
    draw.text((mx + 20, my + 16), "📌 Add Live Feedback", font=get_font(16, bold=True), fill=(15, 23, 42))
    draw.text((mx + mw - 32, my + 16), "✕", font=get_font(14), fill=(148, 163, 184))
    draw.line([(mx, my + 54), (mx + mw, my + 54)], fill=(226, 232, 240), width=1)

    # Coordinate badge in modal
    draw.rounded_rectangle([(mx + 20, my + 68), (mx + 210, my + 94)], radius=6, fill=(241, 245, 249))
    draw.text((mx + 28, my + 74), "Target: .hero-cta-btn (X:280, Y:270)", font=get_font(10, bold=True), fill=(71, 85, 105))

    # Textarea field
    draw.rounded_rectangle([(mx + 20, my + 104), (mx + mw - 20, my + 204)], radius=8, fill=(255, 255, 255), outline=(2, 132, 199), width=2)
    sample_text = "The button contrast is a bit low on mobile view.\nCan we make it primary blue instead?"
    draw.text((mx + 30, my + 116), sample_text, font=get_font(12), fill=(15, 23, 42))

    # Guest Info inputs
    draw.rounded_rectangle([(mx + 20, my + 214), (mx + 180, my + 248)], radius=6, fill=(248, 250, 252), outline=(226, 232, 240), width=1)
    draw.text((mx + 30, my + 224), "Sarah Jenkins", font=get_font(12), fill=(71, 85, 105))

    draw.rounded_rectangle([(mx + 190, my + 214), (mx + mw - 20, my + 248)], radius=6, fill=(248, 250, 252), outline=(226, 232, 240), width=1)
    draw.text((mx + 200, my + 224), "sarah@example.com", font=get_font(12), fill=(71, 85, 105))

    # Screenshot attachment toggle
    draw.text((mx + 20, my + 264), "📷 Screenshot attached automatically (1440x900)", font=get_font(11), fill=(16, 185, 129))

    # Submit Button
    draw.rounded_rectangle([(mx + 20, my + 296), (mx + mw - 20, my + 340)], radius=8, fill=(2, 132, 199))
    draw.text((mx + 130, my + 308), "Submit Feedback", font=get_font(14, bold=True), fill=(255, 255, 255))

    return img

# -------------------------------------------------------------
# SCREENSHOT 2: Admin Review Panel Showing Feedback List
# -------------------------------------------------------------
def generate_screenshot_2(w=1440, h=900):
    img = Image.new("RGBA", (w, h), (248, 250, 252))
    draw = ImageDraw.Draw(img)

    draw_browser_chrome(draw, w, h, "WordPress Admin — Lunexia Live Feedback")
    sb_w = draw_admin_sidebar(draw, w, h, active_item="dashboard")

    # Main dashboard area
    mx = sb_w + 36
    my = 72

    # Title & Subtitle
    draw.text((mx, my), "Dashboard & Feedback Overview", font=get_font(26, bold=True), fill=(15, 23, 42))
    draw.text((mx, my + 36), "Monitor, filter, and resolve real-time visitor and team feedback.", font=get_font(14), fill=(100, 116, 139))

    # Status Cards
    cards = [
        ("Total Feedback", "28", "💬", (239, 246, 255), (29, 78, 216)),
        ("Open Items", "19", "🔴", (254, 242, 242), (185, 28, 28)),
        ("Resolved", "9", "✅", (240, 253, 244), (21, 128, 61)),
        ("High Priority", "4", "⚠️", (254, 252, 232), (161, 98, 7))
    ]
    card_w = 260
    cx = mx
    cy = my + 80
    for label, count, icon, bg_color, text_color in cards:
        box = [(cx, cy), (cx + card_w, cy + 96)]
        draw.rounded_rectangle(box, radius=12, fill=bg_color, outline=(226, 232, 240), width=1)
        draw.text((cx + 20, cy + 18), label, font=get_font(13), fill=(100, 116, 139))
        draw.text((cx + 20, cy + 42), count, font=get_font(28, bold=True), fill=text_color)
        draw.text((cx + card_w - 52, cy + 28), icon, font=get_font(24))
        cx += card_w + 20

    # Search & Filter bar
    fy = cy + 120
    draw.rounded_rectangle([(mx, fy), (mx + 460, fy + 42)], radius=8, fill=(255, 255, 255), outline=(203, 213, 225), width=1)
    draw.text((mx + 16, fy + 12), "🔍 Search feedback by URL, element, or author...", font=get_font(13), fill=(148, 163, 184))

    # Filter dropdowns
    draw.rounded_rectangle([(mx + 480, fy), (mx + 620, fy + 42)], radius=8, fill=(255, 255, 255), outline=(203, 213, 225), width=1)
    draw.text((mx + 496, fy + 12), "Status: All ▾", font=get_font(13), fill=(71, 85, 105))

    draw.rounded_rectangle([(mx + 636, fy), (mx + 786, fy + 42)], radius=8, fill=(255, 255, 255), outline=(203, 213, 225), width=1)
    draw.text((mx + 652, fy + 12), "Priority: All ▾", font=get_font(13), fill=(71, 85, 105))

    # Data Table Container
    ty = fy + 60
    table_w = w - mx - 40
    table_h = h - ty - 40
    draw.rounded_rectangle([(mx, ty), (mx + table_w, ty + table_h)], radius=12, fill=(255, 255, 255), outline=(226, 232, 240), width=1)

    # Table Header
    draw.rounded_rectangle([(mx, ty), (mx + table_w, ty + 46)], radius=12, fill=(248, 250, 252))
    draw.line([(mx, ty + 46), (mx + table_w, ty + 46)], fill=(226, 232, 240), width=1)

    f_th = get_font(12, bold=True)
    draw.text((mx + 24, ty + 16), "ID", font=f_th, fill=(100, 116, 139))
    draw.text((mx + 70, ty + 16), "PAGE / URL", font=f_th, fill=(100, 116, 139))
    draw.text((mx + 340, ty + 16), "SELECTOR", font=f_th, fill=(100, 116, 139))
    draw.text((mx + 560, ty + 16), "STATUS", font=f_th, fill=(100, 116, 139))
    draw.text((mx + 680, ty + 16), "PRIORITY", font=f_th, fill=(100, 116, 139))
    draw.text((mx + 800, ty + 16), "DEVICE", font=f_th, fill=(100, 116, 139))
    draw.text((mx + 920, ty + 16), "DATE", font=f_th, fill=(100, 116, 139))
    draw.text((mx + 1020, ty + 16), "ACTIONS", font=f_th, fill=(100, 116, 139))

    # Table Rows
    rows = [
        ("#104", "/shop/checkout", ".payment-card-input", "open", "high", "Desktop", "2 mins ago"),
        ("#103", "/pricing", ".annual-toggle-switch", "open", "normal", "Mobile", "25 mins ago"),
        ("#102", "/", ".hero-cta-btn", "open", "normal", "Desktop", "1 hour ago"),
        ("#101", "/contact", ".support-ticket-form", "resolved", "low", "Desktop", "3 hours ago"),
        ("#100", "/blog/launch", ".social-share-bar", "resolved", "normal", "Tablet", "Yesterday"),
        ("#099", "/cart", ".coupon-code-btn", "resolved", "high", "Desktop", "2 days ago"),
    ]

    ry = ty + 48
    f_td = get_font(13)
    for rid, page, sel, status, priority, dev, date in rows:
        draw.line([(mx, ry + 46), (mx + table_w, ry + 46)], fill=(241, 245, 249), width=1)
        draw.text((mx + 24, ry + 14), rid, font=f_td, fill=(71, 85, 105))
        draw.text((mx + 70, ry + 14), page, font=get_font(13, bold=True), fill=(15, 23, 42))
        draw.text((mx + 340, ry + 14), sel, font=f_td, fill=(100, 116, 139))
        
        # Status Badge
        s_bg = (254, 242, 242) if status == "open" else (240, 253, 244)
        s_color = (185, 28, 28) if status == "open" else (21, 128, 61)
        draw.rounded_rectangle([(mx + 560, ry + 8), (mx + 636, ry + 34)], radius=12, fill=s_bg)
        draw.text((mx + 574, ry + 13), status.capitalize(), font=get_font(11, bold=True), fill=s_color)

        # Priority Badge
        p_bg = (254, 242, 242) if priority == "high" else ((254, 252, 232) if priority == "normal" else (241, 245, 249))
        p_color = (185, 28, 28) if priority == "high" else ((161, 98, 7) if priority == "normal" else (71, 85, 105))
        draw.rounded_rectangle([(mx + 680, ry + 8), (mx + 756, ry + 34)], radius=12, fill=p_bg)
        draw.text((mx + 694, ry + 13), priority.capitalize(), font=get_font(11, bold=True), fill=p_color)

        draw.text((mx + 800, ry + 14), dev, font=f_td, fill=(71, 85, 105))
        draw.text((mx + 920, ry + 14), date, font=f_td, fill=(148, 163, 184))

        # View button
        draw.rounded_rectangle([(mx + 1020, ry + 8), (mx + 1076, ry + 34)], radius=6, fill=(239, 246, 255))
        draw.text((mx + 1032, ry + 13), "View →", font=get_font(12, bold=True), fill=(2, 132, 199))

        ry += 52

    return img

# -------------------------------------------------------------
# SCREENSHOT 3: Detailed Feedback View with Comments and Metadata
# -------------------------------------------------------------
def generate_screenshot_3(w=1440, h=900):
    img = Image.new("RGBA", (w, h), (248, 250, 252))
    draw = ImageDraw.Draw(img)

    draw_browser_chrome(draw, w, h, "Feedback #102 — Lunexia Live Feedback")
    sb_w = draw_admin_sidebar(draw, w, h, active_item="feedback")

    mx = sb_w + 36
    my = 72

    # Back Link & Header
    draw.text((mx, my), "← Back to Feedback List", font=get_font(13, bold=True), fill=(2, 132, 199))
    draw.text((mx, my + 30), "Feedback #102: Button contrast on mobile view", font=get_font(24, bold=True), fill=(15, 23, 42))

    # Mark as Resolved Button in header
    draw.rounded_rectangle([(w - 230, my + 24), (w - 40, my + 66)], radius=8, fill=(22, 163, 74))
    draw.text((w - 212, my + 36), "✓ Mark as Resolved", font=get_font(14, bold=True), fill=(255, 255, 255))

    # Left Column: Metadata & Screenshot (width: 580)
    col1_w = 560
    cy = my + 84

    # Metadata Card
    draw.rounded_rectangle([(mx, cy), (mx + col1_w, cy + 220)], radius=12, fill=(255, 255, 255), outline=(226, 232, 240), width=1)
    draw.text((mx + 20, cy + 18), "Metadata & Environment", font=get_font(16, bold=True), fill=(15, 23, 42))
    draw.line([(mx, cy + 50), (mx + col1_w, cy + 50)], fill=(241, 245, 249), width=1)

    meta_items = [
        ("Target URL:", "https://example.com/"),
        ("Target Selector:", ".hero-cta-btn"),
        ("Coordinates:", "X: 280, Y: 270"),
        ("Device / Viewport:", "Desktop (1440 x 900)"),
        ("Author:", "Sarah Jenkins (sarah@example.com)"),
    ]
    my_off = cy + 64
    for k, v in meta_items:
        draw.text((mx + 20, my_off), k, font=get_font(12, bold=True), fill=(100, 116, 139))
        draw.text((mx + 170, my_off), v, font=get_font(12), fill=(15, 23, 42))
        my_off += 28

    # Screenshot Preview Card
    sy = cy + 236
    draw.rounded_rectangle([(mx, sy), (mx + col1_w, sy + 430)], radius=12, fill=(255, 255, 255), outline=(226, 232, 240), width=1)
    draw.text((mx + 20, sy + 18), "Attached Screenshot", font=get_font(16, bold=True), fill=(15, 23, 42))
    draw.line([(mx, sy + 50), (mx + col1_w, sy + 50)], fill=(241, 245, 249), width=1)
    
    # Mock image thumbnail with pin
    draw.rounded_rectangle([(mx + 20, sy + 64), (mx + col1_w - 20, sy + 410)], radius=8, fill=(241, 245, 249), outline=(203, 213, 225), width=1)
    draw.text((mx + 180, sy + 210), "📷 Screenshot Captured with Coordinate Pin", font=get_font(13), fill=(148, 163, 184))
    draw.ellipse([(mx + 240, sy + 250), (mx + 266, sy + 276)], fill=(2, 132, 199))
    draw.text((mx + 249, sy + 256), "1", font=get_font(12, bold=True), fill=(255, 255, 255))

    # Right Column: Comment & Discussion Thread (width: remaining)
    col2_x = mx + col1_w + 24
    col2_w = w - col2_x - 40
    
    draw.rounded_rectangle([(col2_x, cy), (col2_x + col2_w, cy + 666)], radius=12, fill=(255, 255, 255), outline=(226, 232, 240), width=1)
    draw.text((col2_x + 24, cy + 18), "Discussion & Comments (3)", font=get_font(16, bold=True), fill=(15, 23, 42))
    draw.line([(col2_x, cy + 50), (col2_x + col2_w, cy + 50)], fill=(241, 245, 249), width=1)

    # Comment 1 (Guest Author)
    c1_y = cy + 68
    draw.ellipse([(col2_x + 24, c1_y), (col2_x + 60, c1_y + 36)], fill=(2, 132, 199))
    draw.text((col2_x + 36, c1_y + 9), "SJ", font=get_font(12, bold=True), fill=(255, 255, 255))
    draw.text((col2_x + 72, c1_y + 2), "Sarah Jenkins (Guest)", font=get_font(14, bold=True), fill=(15, 23, 42))
    draw.text((col2_x + 72, c1_y + 22), "1 hour ago • via Frontend Overlay", font=get_font(11), fill=(148, 163, 184))
    
    c1_text = "The button contrast is a bit low on mobile view. Can we make it primary blue instead?"
    draw.rounded_rectangle([(col2_x + 72, c1_y + 42), (col2_x + col2_w - 24, c1_y + 104)], radius=8, fill=(248, 250, 252))
    draw.text((col2_x + 88, c1_y + 58), c1_text, font=get_font(13), fill=(51, 65, 85))

    # Comment 2 (Admin reply)
    c2_y = c1_y + 126
    draw.ellipse([(col2_x + 24, c2_y), (col2_x + 60, c2_y + 36)], fill=(15, 23, 42))
    draw.text((col2_x + 35, c2_y + 9), "AD", font=get_font(12, bold=True), fill=(255, 255, 255))
    draw.text((col2_x + 72, c2_y + 2), "Admin Team", font=get_font(14, bold=True), fill=(15, 23, 42))
    draw.text((col2_x + 72, c2_y + 22), "35 mins ago • Assigned Developer", font=get_font(11), fill=(148, 163, 184))

    c2_text = "Great spot Sarah! I've updated the button CSS token to #0284c7 and verified WCAG AA contrast."
    draw.rounded_rectangle([(col2_x + 72, c2_y + 42), (col2_x + col2_w - 24, c2_y + 104)], radius=8, fill=(239, 246, 255), outline=(191, 219, 254), width=1)
    draw.text((col2_x + 88, c2_y + 58), c2_text, font=get_font(13), fill=(30, 58, 138))

    # Comment input box
    ci_y = cy + 420
    draw.line([(col2_x, ci_y - 20), (col2_x + col2_w, ci_y - 20)], fill=(241, 245, 249), width=1)
    draw.text((col2_x + 24, ci_y), "Post a Reply:", font=get_font(14, bold=True), fill=(15, 23, 42))

    draw.rounded_rectangle([(col2_x + 24, ci_y + 28), (col2_x + col2_w - 24, ci_y + 140)], radius=8, fill=(255, 255, 255), outline=(203, 213, 225), width=1)
    draw.text((col2_x + 36, ci_y + 44), "Type your reply here... (Guest notifications enabled)", font=get_font(13), fill=(148, 163, 184))

    # Post Comment Button
    draw.rounded_rectangle([(col2_x + col2_w - 180, ci_y + 154), (col2_x + col2_w - 24, ci_y + 196)], radius=8, fill=(2, 132, 199))
    draw.text((col2_x + col2_w - 156, ci_y + 168), "Post Comment", font=get_font(13, bold=True), fill=(255, 255, 255))

    return img

# -------------------------------------------------------------
# SCREENSHOT 4: Plugin Settings Page
# -------------------------------------------------------------
def generate_screenshot_4(w=1440, h=900):
    img = Image.new("RGBA", (w, h), (248, 250, 252))
    draw = ImageDraw.Draw(img)

    draw_browser_chrome(draw, w, h, "Settings — Lunexia Live Feedback")
    sb_w = draw_admin_sidebar(draw, w, h, active_item="settings")

    mx = sb_w + 36
    my = 72

    draw.text((mx, my), "Settings & Access Controls", font=get_font(26, bold=True), fill=(15, 23, 42))
    draw.text((mx, my + 36), "Configure frontend visibility, guest permissions, and email notifications.", font=get_font(14), fill=(100, 116, 139))

    set_w = 760
    sy = my + 84

    # 1. Frontend Overlay Card
    draw.rounded_rectangle([(mx, sy), (mx + set_w, sy + 130)], radius=12, fill=(255, 255, 255), outline=(226, 232, 240), width=1)
    draw.text((mx + 24, sy + 20), "Frontend Feedback Overlay", font=get_font(16, bold=True), fill=(15, 23, 42))
    draw.text((mx + 24, sy + 44), "Control whether the floating live feedback widget appears for site visitors.", font=get_font(13), fill=(100, 116, 139))

    # Toggle switch (ON)
    draw.rounded_rectangle([(mx + 24, sy + 76), (mx + 68, sy + 100)], radius=12, fill=(2, 132, 199))
    draw.ellipse([(mx + 46, sy + 78), (mx + 66, sy + 98)], fill=(255, 255, 255))
    draw.text((mx + 80, sy + 80), "Enable Frontend Feedback Widget", font=get_font(14, bold=True), fill=(15, 23, 42))

    # 2. Guest Access Card
    sy2 = sy + 146
    draw.rounded_rectangle([(mx, sy2), (mx + set_w, sy2 + 130)], radius=12, fill=(255, 255, 255), outline=(226, 232, 240), width=1)
    draw.text((mx + 24, sy2 + 20), "Guest Feedback & Commenting", font=get_font(16, bold=True), fill=(15, 23, 42))
    draw.text((mx + 24, sy2 + 44), "Allow unauthenticated visitors to pin notes, submit screenshots, and reply.", font=get_font(13), fill=(100, 116, 139))

    # Toggle switch (ON)
    draw.rounded_rectangle([(mx + 24, sy2 + 76), (mx + 68, sy2 + 100)], radius=12, fill=(2, 132, 199))
    draw.ellipse([(mx + 46, sy2 + 78), (mx + 66, sy2 + 98)], fill=(255, 255, 255))
    draw.text((mx + 80, sy2 + 80), "Allow Anonymous / Guest Feedback", font=get_font(14, bold=True), fill=(15, 23, 42))

    # 3. Notification Settings Card
    sy3 = sy2 + 146
    draw.rounded_rectangle([(mx, sy3), (mx + set_w, sy3 + 300)], radius=12, fill=(255, 255, 255), outline=(226, 232, 240), width=1)
    draw.text((mx + 24, sy3 + 20), "Email Notifications", font=get_font(16, bold=True), fill=(15, 23, 42))
    draw.text((mx + 24, sy3 + 44), "Configure alert emails when new feedback is created or comments are posted.", font=get_font(13), fill=(100, 116, 139))

    # Checkbox 1
    draw.rounded_rectangle([(mx + 24, sy3 + 76), (mx + 44, sy3 + 96)], radius=4, fill=(2, 132, 199))
    draw.text((mx + 28, sy3 + 78), "✓", font=get_font(12, bold=True), fill=(255, 255, 255))
    draw.text((mx + 54, sy3 + 78), "Send Admin email notifications for new feedback submissions", font=get_font(13), fill=(15, 23, 42))

    # Checkbox 2
    draw.rounded_rectangle([(mx + 24, sy3 + 112), (mx + 44, sy3 + 132)], radius=4, fill=(2, 132, 199))
    draw.text((mx + 28, sy3 + 114), "✓", font=get_font(12, bold=True), fill=(255, 255, 255))
    draw.text((mx + 54, sy3 + 114), "Send update emails to guests when their feedback receives a reply", font=get_font(13), fill=(15, 23, 42))

    # Recipients textarea
    draw.text((mx + 24, sy3 + 152), "Admin Notification Recipients (comma separated):", font=get_font(12, bold=True), fill=(71, 85, 105))
    draw.rounded_rectangle([(mx + 24, sy3 + 176), (mx + set_w - 24, sy3 + 240)], radius=8, fill=(248, 250, 252), outline=(203, 213, 225), width=1)
    draw.text((mx + 36, sy3 + 192), "admin@example.com, support@lunexiait.com", font=get_font(13), fill=(15, 23, 42))

    # Save Settings Button
    draw.rounded_rectangle([(mx, sy3 + 320), (mx + 180, sy3 + 365)], radius=8, fill=(2, 132, 199))
    draw.text((mx + 38, sy3 + 334), "Save Settings", font=get_font(14, bold=True), fill=(255, 255, 255))
    draw.text((mx + 200, sy3 + 336), "✓ Settings saved successfully", font=get_font(13), fill=(16, 185, 129))

    return img

def main():
    print("Generating WordPress.org plugin directory screenshots...")

    gens = [
        ("screenshot-1.png", generate_screenshot_1),
        ("screenshot-2.png", generate_screenshot_2),
        ("screenshot-3.png", generate_screenshot_3),
        ("screenshot-4.png", generate_screenshot_4),
    ]

    for fname, func in gens:
        img = func()
        for d in OUTPUT_DIRS:
            os.makedirs(d, exist_ok=True)
            path = os.path.join(d, fname)
            img.save(path, "PNG")
        print(f"✓ Created {fname}")

    print("\nAll 4 screenshots successfully generated and saved to wporg-assets/ and .wordpress-org/!")

if __name__ == "__main__":
    main()
