import os
import math
from PIL import Image, ImageDraw, ImageFont, ImageFilter, ImageEnhance

# -------------------------------------------------------------
# Base Directories
# -------------------------------------------------------------
WORKSPACE = r'c:\Users\sora\fira-link-lms'
BRAIN = r'C:\Users\sora\.gemini\antigravity-ide\brain\acac5cc3-3c17-436f-b041-cfd007e0995d'
WEB_BASE = os.path.join(WORKSPACE, 'Eduex Laravel', 'Eduex Laravel')
WEB_IMG = os.path.join(WEB_BASE, 'public', 'assets', 'front', 'img')
STORAGE_COURSES = os.path.join(WEB_BASE, 'storage', 'app', 'public', 'courses', 'images')
PUBLIC_COURSES = os.path.join(WEB_BASE, 'public', 'storage', 'courses', 'images')
FLUTTER_IMG = os.path.join(WORKSPACE, 'Eduex Flutter', 'assets', 'img')

# -------------------------------------------------------------
# Master Source Images
# -------------------------------------------------------------
HERO_SRC = os.path.join(BRAIN, 'eth_hero_main_1791620807423.jpg')
MENTOR_SRC = os.path.join(BRAIN, 'eth_about_mentor_1791620834109.jpg')
STUDENT_SRC = os.path.join(BRAIN, 'eth_about_student_1791620867425.jpg')
C1_SRC = os.path.join(STORAGE_COURSES, 'course-1-ethiopian-coffee-sourcing.jpg')
C2_SRC = os.path.join(STORAGE_COURSES, 'course-2-green-coffee-grading-cupping.jpg')
C4_SRC = os.path.join(STORAGE_COURSES, 'course-4-international-logistics-freight.jpg')
LOGO_SRC = os.path.join(WORKSPACE, 'image.png')

# Load master images
img_hero = Image.open(HERO_SRC).convert('RGBA')
img_mentor = Image.open(MENTOR_SRC).convert('RGBA')
img_student = Image.open(STUDENT_SRC).convert('RGBA')
img_c1 = Image.open(C1_SRC).convert('RGBA')
img_c2 = Image.open(C2_SRC).convert('RGBA')
img_c4 = Image.open(C4_SRC).convert('RGBA')
img_logo = Image.open(LOGO_SRC).convert('RGBA')

# Fonts
FONT_REG = r'C:\Windows\Fonts\segoeui.ttf'
FONT_BOLD = r'C:\Windows\Fonts\segoeuib.ttf'

def get_font(path, size):
    try:
        return ImageFont.truetype(path, size)
    except:
        return ImageFont.load_default()

def smart_crop(img, target_w, target_h, anchor=(0.5, 0.5)):
    """Smart crop with focal point anchor (x_ratio, y_ratio)"""
    orig_w, orig_h = img.size
    target_ratio = target_w / target_h
    orig_ratio = orig_w / orig_h

    if orig_ratio > target_ratio:
        # Image is wider than target
        crop_h = orig_h
        crop_w = int(orig_h * target_ratio)
        max_x = orig_w - crop_w
        crop_x = int(max_x * anchor[0])
        crop_y = 0
    else:
        # Image is taller than target
        crop_w = orig_w
        crop_h = int(orig_w / target_ratio)
        crop_x = 0
        max_y = orig_h - crop_h
        crop_y = int(max_y * anchor[1])

    cropped = img.crop((crop_x, crop_y, crop_x + crop_w, crop_y + crop_h))
    return cropped.resize((target_w, target_h), Image.Resampling.LANCZOS)

def round_corners(img, radius):
    """Apply rounded corners with alpha mask"""
    mask = Image.new('L', img.size, 0)
    draw = ImageDraw.Draw(mask)
    draw.rounded_rectangle([(0, 0), img.size], radius=radius, fill=255)
    result = img.copy()
    result.putalpha(mask)
    return result

print("Starting Ethiopian visual asset generation...")

# =============================================================
# 1. WEB HERO ASSETS
# =============================================================
# hero-01.png: (272, 465) - Young Ethiopian businesswoman in modern Addis workspace
h1 = smart_crop(img_student, 272, 465, anchor=(0.5, 0.2))
h1_rounded = round_corners(h1, 24)
h1_path = os.path.join(WEB_IMG, 'home-1', 'hero', 'hero-01.png')
h1_rounded.save(h1_path, 'PNG')
print(f"Saved: {h1_path}")

# hero-02.png: (342, 576) - Two Ethiopian trade professionals in executive office
h2 = smart_crop(img_hero, 342, 576, anchor=(0.55, 0.25))
h2_rounded = round_corners(h2, 28)
h2_path = os.path.join(WEB_IMG, 'home-1', 'hero', 'hero-02.png')
h2_rounded.save(h2_path, 'PNG')
print(f"Saved: {h2_path}")

# client-img.png: (202, 53) - Overlapping authentic Ethiopian student avatars
client_strip = Image.new('RGBA', (202, 53), (0, 0, 0, 0))
# 4 avatar crops
av1 = smart_crop(img_student, 46, 46, anchor=(0.5, 0.2))
av2 = smart_crop(img_hero, 46, 46, anchor=(0.28, 0.2)) # Male executive
av3 = smart_crop(img_hero, 46, 46, anchor=(0.75, 0.25)) # Female executive
av4 = smart_crop(img_mentor, 46, 46, anchor=(0.5, 0.25)) # Mentor

avatars = [av1, av2, av3, av4]
x_offsets = [0, 32, 64, 96]
draw_strip = ImageDraw.Draw(client_strip)

for idx, (av, x) in enumerate(zip(avatars, x_offsets)):
    circle_mask = Image.new('L', (46, 46), 0)
    d = ImageDraw.Draw(circle_mask)
    d.ellipse((0, 0, 46, 46), fill=255)
    av_c = av.copy()
    av_c.putalpha(circle_mask)

    # white border ring
    border_circle = Image.new('RGBA', (50, 50), (0, 0, 0, 0))
    d_b = ImageDraw.Draw(border_circle)
    d_b.ellipse((0, 0, 48, 48), fill=(255, 255, 255, 255))
    client_strip.alpha_composite(border_circle, (x - 2, 0))
    client_strip.alpha_composite(av_c, (x, 2))

# +500 badge at end
badge_x = 132
draw_strip.rounded_rectangle([(badge_x, 3), (badge_x + 65, 47)], radius=22, fill=(109, 26, 149, 255))
font_badge = get_font(FONT_BOLD, 15)
draw_strip.text((badge_x + 12, 14), "+500", font=font_badge, fill=(255, 255, 255, 255))

client_strip_path = os.path.join(WEB_IMG, 'home-1', 'hero', 'client-img.png')
client_strip.save(client_strip_path, 'PNG')
print(f"Saved: {client_strip_path}")

# =============================================================
# 2. WEB ABOUT ASSETS
# =============================================================
# about-01.png: (570, 570) - Main Ethiopian trade mentor
ab1 = smart_crop(img_mentor, 570, 570, anchor=(0.5, 0.3))
ab1_rounded = round_corners(ab1, 32)
ab1_path = os.path.join(WEB_IMG, 'home-1', 'about', 'about-01.png')
ab1_rounded.save(ab1_path, 'PNG')
print(f"Saved: {ab1_path}")

# about-02.png: (250, 250) - Young Ethiopian entrepreneur
ab2 = smart_crop(img_student, 250, 250, anchor=(0.5, 0.25))
ab2_rounded = round_corners(ab2, 24)
ab2_path = os.path.join(WEB_IMG, 'home-1', 'about', 'about-02.png')
ab2_rounded.save(ab2_path, 'PNG')
print(f"Saved: {ab2_path}")

# about-03.png: (300, 300) - Trade executives analyzing export analytics
ab3 = smart_crop(img_hero, 300, 300, anchor=(0.6, 0.3))
ab3_rounded = round_corners(ab3, 24)
ab3_path = os.path.join(WEB_IMG, 'home-1', 'about', 'about-03.png')
ab3_rounded.save(ab3_path, 'PNG')
print(f"Saved: {ab3_path}")

# =============================================================
# 3. WEB CHOOSE-US ASSETS
# =============================================================
# choose-1.png: (570, 570) - Authentic Ethiopian business leadership
ch1 = smart_crop(img_hero, 570, 570, anchor=(0.5, 0.25))
ch1_rounded = round_corners(ch1, 32)
ch1_path = os.path.join(WEB_IMG, 'home-1', 'choose-us', 'choose-1.png')
ch1_rounded.save(ch1_path, 'PNG')
print(f"Saved: {ch1_path}")

# choose-2.png: (250, 250) - Logistics & dry port shipping
ch2 = smart_crop(img_c4, 250, 250, anchor=(0.5, 0.5))
ch2_rounded = round_corners(ch2, 24)
ch2_path = os.path.join(WEB_IMG, 'home-1', 'choose-us', 'choose-2.png')
ch2_rounded.save(ch2_path, 'PNG')
print(f"Saved: {ch2_path}")

# choose-3.png: (300, 300) - Specialty coffee cupping laboratory
ch3 = smart_crop(img_c2, 300, 300, anchor=(0.5, 0.5))
ch3_rounded = round_corners(ch3, 24)
ch3_path = os.path.join(WEB_IMG, 'home-1', 'choose-us', 'choose-3.png')
ch3_rounded.save(ch3_path, 'PNG')
print(f"Saved: {ch3_path}")

# =============================================================
# 4. WEB TEAM / INSTRUCTOR ASSETS (Both home-1 and inner)
# =============================================================
team_crops = [
    # 01: Male executive director
    smart_crop(img_hero, 333, 380, anchor=(0.25, 0.2)),
    # 02: Female export specialist in Fira purple blazer
    smart_crop(img_hero, 332, 380, anchor=(0.75, 0.25)),
    # 03: Senior trade consultant and mentor
    smart_crop(img_mentor, 333, 380, anchor=(0.5, 0.25)),
    # 04: Agribusiness & digital trade lead
    smart_crop(img_student, 332, 380, anchor=(0.5, 0.25)),
]

for idx, t_img in enumerate(team_crops, 1):
    rgb = t_img.convert('RGB')
    p1 = os.path.join(WEB_IMG, 'home-1', 'team', f'team-0{idx}.jpg')
    p2 = os.path.join(WEB_IMG, 'inner', 'team', f'team-0{idx}.jpg')
    rgb.save(p1, 'JPEG', quality=95)
    rgb.save(p2, 'JPEG', quality=95)
    print(f"Saved team {idx}: {p1}")

# Team details 01.jpg
td1 = smart_crop(img_hero, 425, 425, anchor=(0.25, 0.2)).convert('RGB')
td1_path = os.path.join(WEB_IMG, 'inner', 'team-details', '01.jpg')
td1.save(td1_path, 'JPEG', quality=95)
print(f"Saved: {td1_path}")

# =============================================================
# 5. TESTIMONIAL CLIENT AVATARS (60 x 60)
# =============================================================
t_avatars = [
    smart_crop(img_hero, 60, 60, anchor=(0.25, 0.2)),
    smart_crop(img_student, 60, 60, anchor=(0.5, 0.25)),
    smart_crop(img_hero, 60, 60, anchor=(0.75, 0.25)),
]
for idx, tav in enumerate(t_avatars, 1):
    circle_mask = Image.new('L', (60, 60), 0)
    d = ImageDraw.Draw(circle_mask)
    d.ellipse((0, 0, 60, 60), fill=255)
    tav_c = tav.copy()
    tav_c.putalpha(circle_mask)
    t_path = os.path.join(WEB_IMG, 'home-1', 'testimonial', f'client-0{idx}.png')
    tav_c.save(t_path, 'PNG')
    print(f"Saved: {t_path}")

# =============================================================
# 6. COURSE COVERS REDESIGN (Replacing course 3 & 5 Caucasian models)
# =============================================================
# Course 3: Coffee Export Documentation & Compliance (1376 x 768)
# Use authentic mentor reviewing export documents + professional branding overlay
c3_bg = smart_crop(img_mentor, 1376, 768, anchor=(0.5, 0.35))
# Apply dark purple gradient on left and bottom for text readability
c3_overlay = Image.new('RGBA', (1376, 768), (0, 0, 0, 0))
d_c3 = ImageDraw.Draw(c3_overlay)

# Left gradient panel
for x in range(750):
    alpha = int(240 * (1 - (x / 750) ** 1.3))
    d_c3.line([(x, 0), (x, 768)], fill=(15, 7, 25, alpha))

# Bottom subtle gradient
for y in range(450, 768):
    alpha = int(220 * ((y - 450) / 318) ** 1.2)
    d_c3.line([(0, y), (1376, y)], fill=(109, 26, 149, alpha))

c3_comp = Image.alpha_composite(c3_bg, c3_overlay)
d_c3_text = ImageDraw.Draw(c3_comp)

# Brand pill tag
d_c3_text.rounded_rectangle([(60, 60), (360, 105)], radius=22, fill=(109, 26, 149, 240), outline=(255, 255, 255, 120), width=1)
f_tag = get_font(FONT_BOLD, 18)
d_c3_text.text((85, 72), "FIRA LINK ACADEMY", font=f_tag, fill=(255, 255, 255, 255))

# Course Category
f_cat = get_font(FONT_REG, 20)
d_c3_text.text((65, 135), "EXPORT COMPLIANCE & CUSTOMS", font=f_cat, fill=(216, 180, 254, 255))

# Course Title
f_title = get_font(FONT_BOLD, 46)
d_c3_text.text((65, 175), "Ethiopian Coffee Export\nDocumentation & Trade", font=f_title, fill=(255, 255, 255, 255))

# Slogan badge
f_slogan = get_font(FONT_BOLD, 20)
d_c3_text.text((65, 310), "Art of Excellence!!  |  የልህቀት ምንጭ!!", font=f_slogan, fill=(243, 232, 255, 230))

# Small logo in top right
logo_small = img_logo.resize((100, 100), Image.Resampling.LANCZOS)
c3_comp.alpha_composite(logo_small, (1230, 40))

c3_final = c3_comp.convert('RGB')
c3_dest1 = os.path.join(STORAGE_COURSES, 'course-3-coffee-export-documentation.jpg')
c3_dest2 = os.path.join(PUBLIC_COURSES, 'course-3-coffee-export-documentation.jpg')
c3_final.save(c3_dest1, 'JPEG', quality=95)
c3_final.save(c3_dest2, 'JPEG', quality=95)
print(f"Saved Course 3: {c3_dest1}")


# Course 5: Global Coffee Market & Buyers (1376 x 768)
# Use authentic Ethiopian trade executives negotiating international market
c5_bg = smart_crop(img_hero, 1376, 768, anchor=(0.55, 0.3))
c5_overlay = Image.new('RGBA', (1376, 768), (0, 0, 0, 0))
d_c5 = ImageDraw.Draw(c5_overlay)

for x in range(750):
    alpha = int(240 * (1 - (x / 750) ** 1.3))
    d_c5.line([(x, 0), (x, 768)], fill=(15, 7, 25, alpha))

for y in range(450, 768):
    alpha = int(220 * ((y - 450) / 318) ** 1.2)
    d_c5.line([(0, y), (1376, y)], fill=(109, 26, 149, alpha))

c5_comp = Image.alpha_composite(c5_bg, c5_overlay)
d_c5_text = ImageDraw.Draw(c5_comp)

d_c5_text.rounded_rectangle([(60, 60), (360, 105)], radius=22, fill=(109, 26, 149, 240), outline=(255, 255, 255, 120), width=1)
d_c5_text.text((85, 72), "FIRA LINK ACADEMY", font=f_tag, fill=(255, 255, 255, 255))

d_c5_text.text((65, 135), "INTERNATIONAL TRADE & NEGOTIATION", font=f_cat, fill=(216, 180, 254, 255))
d_c5_text.text((65, 175), "Global Coffee Market,\nContracts & Buyer Relations", font=f_title, fill=(255, 255, 255, 255))
d_c5_text.text((65, 310), "Art of Excellence!!  |  የልህቀት ምንጭ!!", font=f_slogan, fill=(243, 232, 255, 230))
c5_comp.alpha_composite(logo_small, (1230, 40))

c5_final = c5_comp.convert('RGB')
c5_dest1 = os.path.join(STORAGE_COURSES, 'course-5-global-coffee-market-buyers.jpg')
c5_dest2 = os.path.join(PUBLIC_COURSES, 'course-5-global-coffee-market-buyers.jpg')
c5_final.save(c5_dest1, 'JPEG', quality=95)
c5_final.save(c5_dest2, 'JPEG', quality=95)
print(f"Saved Course 5: {c5_dest1}")


# =============================================================
# 7. FLUTTER MOBILE APP ASSETS
# =============================================================
# A. banner.png (395 x 168) - Replace "Welcome to Ms Morgan's Online Class"
banner1 = Image.new('RGBA', (395, 168), (109, 26, 149, 255))
d_b1 = ImageDraw.Draw(banner1)

# Subtle background gradient
for x in range(395):
    # Gradient from #6D1A95 (109, 26, 149) to #430C5D (67, 12, 93)
    t = x / 395
    r = int(109 * (1 - t) + 55 * t)
    g = int(26 * (1 - t) + 10 * t)
    b = int(149 * (1 - t) + 80 * t)
    d_b1.line([(x, 0), (x, 168)], fill=(r, g, b, 255))

# Blend authentic Ethiopian professional crop on right
eth_side = smart_crop(img_student, 160, 168, anchor=(0.5, 0.25))
# Fade left edge of side image
eth_side_mask = Image.new('L', (160, 168), 0)
d_sm = ImageDraw.Draw(eth_side_mask)
for x in range(160):
    val = int(255 * (x / 60)) if x < 60 else 255
    d_sm.line([(x, 0), (x, 168)], fill=val)
eth_side.putalpha(eth_side_mask)
banner1.alpha_composite(eth_side, (235, 0))

# Content on left
logo_b = img_logo.resize((36, 36), Image.Resampling.LANCZOS)
banner1.alpha_composite(logo_b, (18, 16))

f_b_tag = get_font(FONT_BOLD, 11)
d_b1.text((58, 20), "FIRA LINK ACADEMY", font=f_b_tag, fill=(233, 213, 255, 255))

f_b_title = get_font(FONT_BOLD, 17)
d_b1.text((18, 58), "Master Ethiopian Trade\n& Export Excellence", font=f_b_title, fill=(255, 255, 255, 255))

f_b_sub = get_font(FONT_REG, 11)
d_b1.text((18, 110), "Accredited training by certified experts", font=f_b_sub, fill=(216, 180, 254, 255))

# CTA Pill button
d_b1.rounded_rectangle([(18, 130), (120, 154)], radius=12, fill=(255, 255, 255, 255))
f_b_btn = get_font(FONT_BOLD, 10)
d_b1.text((32, 136), "Explore Now", font=f_b_btn, fill=(109, 26, 149, 255))

banner1_rgb = banner1.convert('RGB')
banner1_path = os.path.join(FLUTTER_IMG, 'banner.png')
banner1_rgb.save(banner1_path, 'PNG')
print(f"Saved: {banner1_path}")


# B. banner2.png (395 x 168)
banner2 = Image.new('RGBA', (395, 168), (30, 15, 45, 255))
d_b2 = ImageDraw.Draw(banner2)
for x in range(395):
    t = x / 395
    r = int(45 * (1 - t) + 109 * t)
    g = int(12 * (1 - t) + 26 * t)
    b = int(70 * (1 - t) + 149 * t)
    d_b2.line([(x, 0), (x, 168)], fill=(r, g, b, 255))

eth_side2 = smart_crop(img_c4, 170, 168, anchor=(0.5, 0.5))
eth_side2_mask = Image.new('L', (170, 168), 0)
d_sm2 = ImageDraw.Draw(eth_side2_mask)
for x in range(170):
    val = int(255 * (x / 60)) if x < 60 else 255
    d_sm2.line([(x, 0), (x, 168)], fill=val)
eth_side2.putalpha(eth_side2_mask)
banner2.alpha_composite(eth_side2, (225, 0))

banner2.alpha_composite(logo_b, (18, 16))
d_b2.text((58, 20), "EXPORT LOGISTICS & SUPPLY CHAIN", font=f_b_tag, fill=(233, 213, 255, 255))
d_b2.text((18, 58), "Multimodal Transport &\nDry Port Operations", font=f_b_title, fill=(255, 255, 255, 255))
d_b2.text((18, 110), "Direct practical industry workflows", font=f_b_sub, fill=(216, 180, 254, 255))
d_b2.rounded_rectangle([(18, 130), (120, 154)], radius=12, fill=(255, 255, 255, 255))
d_b2.text((32, 136), "Enroll Today", font=f_b_btn, fill=(109, 26, 149, 255))

banner2_rgb = banner2.convert('RGB')
banner2_path = os.path.join(FLUTTER_IMG, 'banner2.png')
banner2_rgb.save(banner2_path, 'PNG')
print(f"Saved: {banner2_path}")


# C. Onboarding Illustrations (262 x 341) - Clean, premium Fira Link branded cards
# Onboarding 1: Discover
onb1 = Image.new('RGBA', (262, 341), (0, 0, 0, 0))
d_o1 = ImageDraw.Draw(onb1)
# Card background
d_o1.rounded_rectangle([(0, 0), (262, 341)], radius=24, fill=(248, 244, 252, 255), outline=(109, 26, 149, 40), width=2)
# Photo area
o1_photo = smart_crop(img_student, 238, 220, anchor=(0.5, 0.25))
o1_photo_rounded = round_corners(o1_photo, 16)
onb1.alpha_composite(o1_photo_rounded, (12, 12))
# Tag badge
d_o1.rounded_rectangle([(24, 24), (160, 52)], radius=14, fill=(109, 26, 149, 230))
f_o_tag = get_font(FONT_BOLD, 11)
d_o1.text((36, 31), "FIRA LINK ACADEMY", font=f_o_tag, fill=(255, 255, 255, 255))
# Bottom title
f_o_title = get_font(FONT_BOLD, 15)
f_o_sub = get_font(FONT_REG, 11)
d_o1.text((16, 248), "Discover Ethiopian Trade", font=f_o_title, fill=(26, 10, 36, 255))
d_o1.text((16, 274), "Explore export, import, coffee, &\nlogistics training programs.", font=f_o_sub, fill=(100, 80, 115, 255))
# Brand accent line
d_o1.rounded_rectangle([(16, 318), (80, 323)], radius=3, fill=(109, 26, 149, 255))

onb1_path = os.path.join(FLUTTER_IMG, 'onboarding', 'onboarding_1.png')
onb1.save(onb1_path, 'PNG')
print(f"Saved: {onb1_path}")


# Onboarding 2: Learn
onb2 = Image.new('RGBA', (262, 341), (0, 0, 0, 0))
d_o2 = ImageDraw.Draw(onb2)
d_o2.rounded_rectangle([(0, 0), (262, 341)], radius=24, fill=(248, 244, 252, 255), outline=(109, 26, 149, 40), width=2)
o2_photo = smart_crop(img_mentor, 238, 220, anchor=(0.5, 0.3))
o2_photo_rounded = round_corners(o2_photo, 16)
onb2.alpha_composite(o2_photo_rounded, (12, 12))
d_o2.rounded_rectangle([(24, 24), (170, 52)], radius=14, fill=(109, 26, 149, 230))
d_o2.text((36, 31), "EXPERT MENTORSHIP", font=f_o_tag, fill=(255, 255, 255, 255))
d_o2.text((16, 248), "Learn from Industry Leaders", font=f_o_title, fill=(26, 10, 36, 255))
d_o2.text((16, 274), "Hands-on guidance from certified\nEthiopian trade practitioners.", font=f_o_sub, fill=(100, 80, 115, 255))
d_o2.rounded_rectangle([(16, 318), (80, 323)], radius=3, fill=(109, 26, 149, 255))

onb2_path = os.path.join(FLUTTER_IMG, 'onboarding', 'onboarding_2.png')
onb2.save(onb2_path, 'PNG')
print(f"Saved: {onb2_path}")


# Onboarding 3: Track & Grow
onb3 = Image.new('RGBA', (262, 341), (0, 0, 0, 0))
d_o3 = ImageDraw.Draw(onb3)
d_o3.rounded_rectangle([(0, 0), (262, 341)], radius=24, fill=(248, 244, 252, 255), outline=(109, 26, 149, 40), width=2)
o3_photo = smart_crop(img_hero, 238, 220, anchor=(0.55, 0.25))
o3_photo_rounded = round_corners(o3_photo, 16)
onb3.alpha_composite(o3_photo_rounded, (12, 12))
d_o3.rounded_rectangle([(24, 24), (160, 52)], radius=14, fill=(109, 26, 149, 230))
d_o3.text((36, 31), "GLOBAL SUCCESS", font=f_o_tag, fill=(255, 255, 255, 255))
d_o3.text((16, 248), "Scale into Global Markets", font=f_o_title, fill=(26, 10, 36, 255))
d_o3.text((16, 274), "Earn accredited certificates and\nexpand your export enterprise.", font=f_o_sub, fill=(100, 80, 115, 255))
d_o3.rounded_rectangle([(16, 318), (80, 323)], radius=3, fill=(109, 26, 149, 255))

onb3_path = os.path.join(FLUTTER_IMG, 'onboarding', 'onboarding_3.png')
onb3.save(onb3_path, 'PNG')
print(f"Saved: {onb3_path}")


# D. Flutter Profile Images (profile_1.jpg to profile_8.jpg) (333 x 380)
# 8 authentic Ethiopian professionals
flutter_profiles = [
    smart_crop(img_hero, 333, 380, anchor=(0.25, 0.2)),   # 1: Male Executive
    smart_crop(img_hero, 333, 380, anchor=(0.75, 0.25)),  # 2: Female Executive in purple
    smart_crop(img_mentor, 333, 380, anchor=(0.5, 0.25)),  # 3: Trade Mentor
    smart_crop(img_student, 333, 380, anchor=(0.5, 0.25)), # 4: Young businesswoman
    smart_crop(img_hero, 333, 380, anchor=(0.35, 0.22)),  # 5: Senior Trade Analyst
    smart_crop(img_hero, 333, 380, anchor=(0.65, 0.25)),  # 6: Export Operations Lead
    smart_crop(img_mentor, 333, 380, anchor=(0.4, 0.3)),   # 7: Commodity Specialist
    smart_crop(img_student, 333, 380, anchor=(0.55, 0.2)), # 8: Digital Business Lead
]

for idx, prof in enumerate(flutter_profiles, 1):
    p_path = os.path.join(FLUTTER_IMG, 'profile', f'profile_{idx}.jpg')
    prof.convert('RGB').save(p_path, 'JPEG', quality=95)
    print(f"Saved Flutter profile {idx}: {p_path}")

# E. Default Profile (default-profile.png)
dp = Image.new('RGBA', (200, 200), (0, 0, 0, 0))
d_dp = ImageDraw.Draw(dp)
d_dp.ellipse([(0, 0), (200, 200)], fill=(243, 232, 255, 255), outline=(109, 26, 149, 255), width=4)
# Silhouette head and shoulders
d_dp.ellipse([(65, 35), (135, 105)], fill=(109, 26, 149, 255))
d_dp.chord([(30, 115), (170, 240)], start=180, end=0, fill=(109, 26, 149, 255))
dp_path = os.path.join(FLUTTER_IMG, 'default-profile.png')
dp.save(dp_path, 'PNG')
print(f"Saved: {dp_path}")

print("\n--- ALL ETHIOPIAN ASSETS GENERATED & INTEGRATED SUCCESSFULLY! ---")
