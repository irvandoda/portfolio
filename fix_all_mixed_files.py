#!/usr/bin/env python3
"""
Fix all React files that have mixed HTML (class) and JSX (className) structure
This will remove HTML sections outside React component and add them as JSX inside
"""
import re
from pathlib import Path

def convert_html_to_jsx(html_content):
    """Convert HTML attributes to JSX"""
    # Replace class= with className=
    jsx = re.sub(r'\bclass\s*=', 'className=', html_content)
    # Replace for= with htmlFor=
    jsx = re.sub(r'\bfor\s*=', 'htmlFor=', jsx)
    # Fix self-closing tags
    jsx = re.sub(r'<img([^>]*?)(?<!/)>', r'<img\1 />', jsx)
    jsx = re.sub(r'<input([^>]*?)(?<!/)>', r'<input\1 />', jsx)
    jsx = re.sub(r'<br([^>]*?)(?<!/)>', r'<br\1 />', jsx)
    jsx = re.sub(r'<hr([^>]*?)(?<!/)>', r'<hr\1 />', jsx)
    # Fix stroke-width to strokeWidth
    jsx = re.sub(r'stroke-width', 'strokeWidth', jsx)
    jsx = re.sub(r'stroke-linecap', 'strokeLinecap', jsx)
    jsx = re.sub(r'stroke-linejoin', 'strokeLinejoin', jsx)
    jsx = re.sub(r'fill-rule', 'fillRule', jsx)
    jsx = re.sub(r'clip-rule', 'clipRule', jsx)
    return jsx

def extract_html_sections(content):
    """Extract HTML sections that are outside React component"""
    sections = []
    
    # Find React component return statement
    return_match = re.search(r'return\s*\(', content)
    if not return_match:
        return sections
    
    # Find where component ends
    component_end_patterns = [
        r'\)\s*;\s*}\s*;\s*const\s+root',
        r'\)\s*;\s*}\s*;\s*ReactDOM',
        r'\)\s*;\s*}\s*;\s*\n\s*const\s+root',
    ]
    
    component_end = None
    for pattern in component_end_patterns:
        match = re.search(pattern, content)
        if match:
            component_end = match.start()
            break
    
    if not component_end:
        return sections
    
    # Extract content between return and component end
    component_content = content[return_match.end():component_end]
    
    # Find HTML sections with class= (not className=)
    # Pattern: <!-- comment --> followed by <section class= or <div class=
    html_section_pattern = r'(<!--[^>]*?-->)?\s*<(section|div)[^>]*class\s*=[^>]*>.*?</\2>'
    
    matches = list(re.finditer(html_section_pattern, component_content, re.DOTALL | re.IGNORECASE))
    
    for match in matches:
        sections.append({
            'start': return_match.end() + match.start(),
            'end': return_match.end() + match.end(),
            'content': match.group(0)
        })
    
    return sections

def fix_react_file(file_path):
    """Fix React file with mixed structure"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if 'type="text/babel"' not in content:
            return False
        
        # Check if file has mixed structure
        if not re.search(r'class\s*=', content):
            return False  # No HTML with class= found
        
        # Find React component return statement
        return_match = re.search(r'return\s*\(', content)
        if not return_match:
            return False
        
        # Find where component ends
        component_end_patterns = [
            r'\)\s*;\s*}\s*;\s*const\s+root',
            r'\)\s*;\s*}\s*;\s*ReactDOM',
        ]
        
        component_end = None
        for pattern in component_end_patterns:
            match = re.search(pattern, content)
            if match:
                component_end = match.start()
                break
        
        if not component_end:
            return False
        
        # Extract component content
        component_start = return_match.end()
        component_content = content[component_start:component_end]
        
        # Find HTML sections with class= inside component
        html_sections = []
        pattern = r'(<!--[^>]*?-->)?\s*<(section|div)[^>]*class\s*=[^>]*>.*?</\2>'
        
        for match in re.finditer(pattern, component_content, re.DOTALL | re.IGNORECASE):
            html_sections.append(match)
        
        if not html_sections:
            return False
        
        # Remove HTML sections from component
        new_component = component_content
        offset = 0
        for match in reversed(html_sections):
            start = match.start() + offset
            end = match.end() + offset
            new_component = new_component[:start] + new_component[end:]
            offset -= (end - start)
        
        # Convert removed sections to JSX and add before closing tag
        jsx_sections = []
        for match in html_sections:
            html_section = match.group(0)
            jsx_section = convert_html_to_jsx(html_section)
            # Add as comment if it was a comment
            if html_section.strip().startswith('<!--'):
                jsx_section = '{/* ' + html_section.replace('<!--', '').replace('-->', '').strip() + ' */}\n                    ' + jsx_section
            jsx_sections.append(jsx_section)
        
        # Find closing tag of main div
        closing_div = re.search(r'</div>\s*\)\s*;', new_component)
        if closing_div:
            # Insert JSX sections before closing div
            insert_pos = closing_div.start()
            new_component = new_component[:insert_pos] + '\n                    ' + '\n                    '.join(jsx_sections) + '\n                    ' + new_component[insert_pos:]
        else:
            # Add before closing parenthesis
            closing_paren = new_component.rfind(')')
            if closing_paren > 0:
                new_component = new_component[:closing_paren] + '\n                    ' + '\n                    '.join(jsx_sections) + '\n                    ' + new_component[closing_paren:]
        
        # Reconstruct file
        new_content = content[:component_start] + new_component + content[component_end:]
        
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        
        return True
    except Exception as e:
        print(f"Error processing {file_path.name}: {str(e)}")
        return False

def main():
    """Main function"""
    lp_dir = Path('/www/wwwroot/portfolio.irvandoda.my.id/LP')
    
    if not lp_dir.exists():
        print(f"❌ Directory not found: {lp_dir}")
        return
    
    # Get list of files with mixed structure
    broken_files = [
        'workshop-kreatif.html', 'webinar-landing.html', 'lightroom-presets.html',
        'skincare.html', 'ai-ml.html', 'pizza-restaurant.html', 'salon.html',
        'jasa-renovasi.html', 'child-development.html', 'sushi-japanese.html',
        'eventorganizer.html', 'program-beasiswa.html', 'travel-agent.html',
        'psikolog-online.html', 'fintech.html', 'saas-software.html', 'tour-guide.html',
        'wordpress-plugin.html', 'community-membership.html', 'homestay-villa.html',
        'food-truck.html', 'kitchen-set.html', 'healthy-food.html', 'cafe-kopi.html',
        'travel-blog.html', 'restoran-fine-dining.html', 'gym-trainer.html',
        'hotel-resort.html', 'jam-tangan.html', 'developer-tools.html',
        'bimbel-les-privat.html', 'online-course.html', 'cloud-services.html',
        'training-korporat.html', 'ice-cream-shop.html', 'kelaspublicspeaking.html',
        'konsultan-bisnis.html', 'iot-platform.html', 'companyprofileteknologi.html',
        'sekolah-online.html', 'tourguideprofesional.html', 'weddingorganizer.html',
        'kolam-renang.html', 'dental-clinic.html', 'platform-elearning.html',
        'kost-eksklusif.html', 'streetwear.html', 'travel-photography.html',
        'handmade-accessories.html', 'kursus-online.html', 'sistempos.html',
        'digital-marketing.html', 'bakery-roti.html', 'cateringpesta.html',
        'life-coach.html', 'parfum.html', 'furniture.html', 'public-speaker.html',
        'kontraktor-rumah.html', 'konsultan-pajak.html', 'rental-mobil.html',
        'klinik-kulit.html', 'cybersecurity.html', 'interior-designer.html',
        'cloudkitchen.html', 'skill-assessment.html', 'ahli-gizi.html',
        'landscaping.html', 'baby-product.html', 'dermatologist.html',
        'home-care.html', 'arsitek.html', 'kursus-bahasa-asing.html',
        'klinik-kecantikan.html', 'restofinedining.html', 'sepatu-custom.html',
        'villabnbbooking.html', 'catering.html', 'logo-package.html',
        'warung-makan.html', 'freelancer-dev.html', 'webinarseries.html',
        'bootcampdigitalmarketing.html', 'developer-perumahan.html', 'mobile-app.html',
        'travel-insurance.html', 'blockchain-crypto.html', 'personal-creator.html',
        'spa-therapy.html', 'freelancer-designer.html', 'notion-template.html',
        'herbal-product.html', 'jasa-pengiriman.html', 'penulis-buku.html',
        'paketumroh.html', 'saas-startup.html', 'rentalmotor.html',
        'gadget-accessories.html', 'backpacker-hostel.html', 'tech-consulting.html',
        'airport-transfer.html', 'bootcamp-coding.html'
    ]
    
    print(f"🔧 Fixing {len(broken_files)} React files with mixed structure...\n")
    
    fixed = 0
    for filename in broken_files:
        file_path = lp_dir / filename
        if file_path.exists():
            if fix_react_file(file_path):
                print(f"✅ Fixed {filename}")
                fixed += 1
            else:
                print(f"⚠️  {filename} - No changes needed or error")
        else:
            print(f"❌ {filename} - File not found")
    
    print(f"\n✨ Fixed {fixed}/{len(broken_files)} files")

if __name__ == '__main__':
    main()

