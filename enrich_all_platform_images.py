import os
from PIL import Image, ImageDraw, ImageFont, ImageFilter, ImageEnhance

WORKSPACE = r'c:\Users\sora\fira-link-lms'
BRAIN = r'C:\Users\sora\.gemini\antigravity-ide\brain\acac5cc3-3c17-436f-b041-cfd007e0995d'
WEB_BASE = os.path.join(WORKSPACE, 'Eduex Laravel', 'Eduex Laravel')
WEB_IMG = os.path.join(WEB_BASE, 'public', 'assets', 'front', 'img')
STORAGE_COURSES = os.path.join(WEB_BASE, 'storage', 'app', 'public', 'courses', 'images')
FLUTTER_IMG = os.path.join(WORKSPACE, 'Eduex Flutter', 'assets', 'img')

# Master sources
img_hero = Image.open(os.path.join(BRAIN, 'eth_hero_main_1791620807423.jpg')).convert('RGBA')
img_mentor = Image.open(os.path.join(BRAIN, 'eth_about_mentor_1791620834109.jpg')).convert('RGBA')
img_student = Image.open(os.path.join(BRAIN, 'eth_about_student_1791620867425.jpg')).convert('RGBA')
img_c1 = Image.open(os.path.join(STORAGE_COURSES, 'course-1-ethiopian-coffee-sourcing.jpg')).convert('RGBA')
img_c2 = Image.open(os.path.join(STORAGE_COURSES, 'course-2-green-coffee-grading-cupping.jpg')).convert('RGBA')
img_c4 = Image.open(os.path.join(STORAGE_COURSES, 'course-4-international-logistics-freight.jpg')).convert('RGBA')
img_logo = Image.open(os.path.join(WORKSPACE, 'image.png')).convert('RGBA')

FONT_BOLD = r'C:\Windows\Fonts\segoeuib.ttf'
FONT_REG = r'C:\Windows\Fonts\segoeui.ttf'

def get_font(path, size):
    try:
        return ImageFont.truetype(path, size)
    except:
        return ImageFont.load_default()

def smart_crop(img, target_w, target_h, anchor=(0.5, 0.5)):
    orig_w, orig_h = img.size
    target_ratio = target_w / target_h
    orig_ratio = orig_w / orig_h

    if orig_ratio > target_ratio:
        crop_h = orig_h
        crop_w = int(orig_h * target_ratio)
        max_x = orig_w - crop_w
        crop_x = int(max_x * anchor[0])
        crop_y = 0
    else:
        crop_w = orig_w
        crop_h = int(orig_w / target_ratio)
        crop_x = 0
        max_y = orig_h - crop_h
        crop_y = int(max_y * anchor[1])

    cropped = img.crop((crop_x, crop_y, crop_x + crop_w, crop_y + crop_h))
    return cropped.resize((target_w, target_h), Image.Resampling.LANCZOS)

print("Enriching remaining platform images with authentic Ethiopian imagery...")

# -------------------------------------------------------------
# 1. Web Home Courses (courses-01.jpg to 06.jpg) (453 x 280)
# -------------------------------------------------------------
pool_courses = [
    (img_c1, (0.5, 0.5)),      # 01: Coffee sourcing
    (img_c2, (0.5, 0.5)),      # 02: Coffee cupping laboratory
    (img_mentor, (0.5, 0.3)),  # 03: Export documentation mentor
    (img_c4, (0.5, 0.5)),      # 04: International freight & dry port
    (img_hero, (0.55, 0.25)),  # 05: Global trade executives
    (img_student, (0.5, 0.25)) # 06: Digital trade & business
]

courses_dir = os.path.join(WEB_IMG, 'home-1', 'courses')
for idx, (src_img, anchor) in enumerate(pool_courses, 1):
    cr = smart_crop(src_img, 453, 280, anchor=anchor).convert('RGB')
    p = os.path.join(courses_dir, f'courses-0{idx}.jpg')
    cr.save(p, 'JPEG', quality=95)
    print(f"Saved Web Course {idx}: {p}")

# -------------------------------------------------------------
# 2. Web Inner Program Images (program-01.jpg to 06.jpg) (413 x 280)
# -------------------------------------------------------------
prog_dir = os.path.join(WEB_IMG, 'inner', 'program')
for idx, (src_img, anchor) in enumerate(pool_courses, 1):
    pr = smart_crop(src_img, 413, 280, anchor=anchor).convert('RGB')
    p = os.path.join(prog_dir, f'program-0{idx}.jpg')
    pr.save(p, 'JPEG', quality=95)
    print(f"Saved Program {idx}: {p}")

# Program Details 01 (1420 x 700)
pd1 = smart_crop(img_hero, 1420, 700, anchor=(0.5, 0.3)).convert('RGB')
pd1_path = os.path.join(prog_dir, 'program-details-01.jpg')
pd1.save(pd1_path, 'JPEG', quality=95)
print(f"Saved: {pd1_path}")

# Program Details 02 (420 x 400)
pd2 = smart_crop(img_mentor, 420, 400, anchor=(0.5, 0.25)).convert('RGB')
pd2_path = os.path.join(prog_dir, 'program-details-02.jpg')
pd2.save(pd2_path, 'JPEG', quality=95)
print(f"Saved: {pd2_path}")

# -------------------------------------------------------------
# 3. Web Breadcrumb & Banner Backgrounds
# -------------------------------------------------------------
# breadcrumb.jpg (1920 x 400)
bc_base = smart_crop(img_c4, 1920, 400, anchor=(0.5, 0.5))
bc_overlay = Image.new('RGBA', (1920, 400), (0, 0, 0, 0))
d_bc = ImageDraw.Draw(bc_overlay)
# Apply deep Fira Purple gradient overlay (#6D1A95 to #12031B)
for y in range(400):
    alpha = int(210 + 35 * (y / 400))
    d_bc.line([(0, y), (1920, y)], fill=(80, 15, 115, alpha))
bc_comp = Image.alpha_composite(bc_base, bc_overlay).convert('RGB')
bc_path = os.path.join(WEB_IMG, 'inner', 'breadcrumb.jpg')
bc_comp.save(bc_path, 'JPEG', quality=92)
print(f"Saved: {bc_path}")

# discount/bg-img.jpg (1920 x 700)
disc_base = smart_crop(img_hero, 1920, 700, anchor=(0.5, 0.3))
disc_overlay = Image.new('RGBA', (1920, 700), (0, 0, 0, 0))
d_disc = ImageDraw.Draw(disc_overlay)
for x in range(1920):
    # Left dark, right allows slight photo to show
    t = x / 1920
    alpha = int(240 * (1 - t * 0.4))
    d_disc.line([(x, 0), (x, 700)], fill=(35, 8, 52, alpha))
disc_comp = Image.alpha_composite(disc_base, disc_overlay).convert('RGB')
disc_path = os.path.join(WEB_IMG, 'home-1', 'discount', 'bg-img.jpg')
disc_comp.save(disc_path, 'JPEG', quality=92)
print(f"Saved: {disc_path}")

# cta/cta-bg.jpg (1420 x 508)
cta_base = smart_crop(img_mentor, 1420, 508, anchor=(0.5, 0.35))
cta_overlay = Image.new('RGBA', (1420, 508), (0, 0, 0, 0))
d_cta = ImageDraw.Draw(cta_overlay)
for x in range(1420):
    t = x / 1420
    alpha = int(235 * (1 - t * 0.3))
    d_cta.line([(x, 0), (x, 508)], fill=(109, 26, 149, alpha))
cta_comp = Image.alpha_composite(cta_base, cta_overlay).convert('RGB')
cta_path = os.path.join(WEB_IMG, 'home-1', 'cta', 'cta-bg.jpg')
cta_comp.save(cta_path, 'JPEG', quality=92)
print(f"Saved: {cta_path}")

# -------------------------------------------------------------
# 4. Flutter Course Fallbacks (course1.jpg to course14.jpg)
# -------------------------------------------------------------
flutter_pool = [
    (img_c1, (0.5, 0.5)),      # 1: Coffee sourcing
    (img_c2, (0.5, 0.5)),      # 2: Cupping & grading
    (img_mentor, (0.5, 0.3)),  # 3: Export documentation
    (img_c4, (0.5, 0.5)),      # 4: Freight logistics
    (img_hero, (0.55, 0.25)),  # 5: Global coffee market
    (img_student, (0.5, 0.25)),# 6: Digital trade
    (img_c1, (0.3, 0.4)),      # 7: Specialty processing
    (img_c2, (0.7, 0.5)),      # 8: Sensory evaluation
    (img_hero, (0.25, 0.2)),   # 9: Executive trade leadership
    (img_c4, (0.7, 0.4)),      # 10: Multimodal shipping
    (img_mentor, (0.4, 0.25)), # 11: Customs & regulatory compliance
    (img_student, (0.6, 0.3)), # 12: E-commerce export
    (img_hero, (0.75, 0.25)),  # 13: Export negotiations
    (img_c1, (0.6, 0.6))       # 14: Agro-processing & warehousing
]

fc_dir = os.path.join(FLUTTER_IMG, 'courses')
for idx, (src_img, anchor) in enumerate(flutter_pool, 1):
    fc = smart_crop(src_img, 1280, 720, anchor=anchor).convert('RGB')
    p = os.path.join(fc_dir, f'course{idx}.jpg')
    fc.save(p, 'JPEG', quality=92)
    print(f"Saved Flutter Course {idx}: {p}")

print("\n--- ALL ENRICHED PLATFORM IMAGES COMPLETED! ---")
