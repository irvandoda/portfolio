#!/usr/bin/env python3
import re
from pathlib import Path
from collections import defaultdict

def analyze_html_file(file_path):
    """Analyze HTML file for required sections"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Check for required sections
        sections = {
            'header': bool(re.search(r'<nav|<header', content, re.IGNORECASE)),
            'hero': bool(re.search(r'hero|id=["\']home|id=["\']beranda', content, re.IGNORECASE)),
            'hero_headline': bool(re.search(r'<h1|<h2.*hero|hero.*<h1|hero.*<h2', content, re.IGNORECASE)),
            'hero_cta': bool(re.search(r'button.*cta|cta.*button|href=["\']#pricing|Beli|Pesan|Daftar|Order', content, re.IGNORECASE)),
            'benefit': bool(re.search(r'benefit|keuntungan|fitur|feature|kelebihan', content, re.IGNORECASE)),
            'preview': bool(re.search(r'preview|gallery|galeri|tampilan|lihat', content, re.IGNORECASE)),
            'testimonials': bool(re.search(r'testimonial|review|ulasan|kata.*mereka|kata.*klien', content, re.IGNORECASE)),
            'pricing': bool(re.search(r'pricing|harga|paket|price|tarif', content, re.IGNORECASE)),
            'footer': bool(re.search(r'<footer', content, re.IGNORECASE)),
            'credit': bool(re.search(r'Irvando Demas Arifiandani|irvandoda\.my\.id', content, re.IGNORECASE))
        }
        
        # Count missing sections
        missing = [key for key, value in sections.items() if not value]
        
        return {
            'file': file_path.name,
            'sections': sections,
            'missing': missing,
            'complete': len(missing) == 0
        }
    except Exception as e:
        return {
            'file': file_path.name,
            'error': str(e),
            'complete': False
        }

def main():
    """Main function"""
    lp_dir = Path('/www/wwwroot/portfolio.irvandoda.my.id/LP')
    
    if not lp_dir.exists():
        print(f"❌ Directory not found: {lp_dir}")
        return
    
    html_files = list(lp_dir.glob('*.html'))
    total = len(html_files)
    
    print(f"📁 Analyzing {total} HTML files...\n")
    
    results = []
    section_stats = defaultdict(int)
    missing_stats = defaultdict(int)
    
    for html_file in html_files:
        result = analyze_html_file(html_file)
        results.append(result)
        
        if 'error' not in result:
            for section, exists in result['sections'].items():
                if exists:
                    section_stats[section] += 1
            
            for missing in result['missing']:
                missing_stats[missing] += 1
    
    # Print summary
    print("=" * 80)
    print("SECTION STATISTICS")
    print("=" * 80)
    section_names = {
        'header': 'Header/Navigation',
        'hero': 'Hero Section',
        'hero_headline': 'Hero Headline',
        'hero_cta': 'Hero CTA',
        'benefit': 'Benefit Section',
        'preview': 'Preview Section',
        'testimonials': 'Testimonials',
        'pricing': 'Pricing Section',
        'footer': 'Footer',
        'credit': 'Credit Footer'
    }
    
    for section, count in sorted(section_stats.items()):
        percentage = (count / total) * 100
        status = "✅" if percentage >= 90 else "⚠️" if percentage >= 50 else "❌"
        print(f"{status} {section_names.get(section, section):<25} {count:>3}/{total} ({percentage:>5.1f}%)")
    
    print("\n" + "=" * 80)
    print("MISSING SECTIONS STATISTICS")
    print("=" * 80)
    for section, count in sorted(missing_stats.items(), key=lambda x: x[1], reverse=True):
        print(f"❌ {section_names.get(section, section):<25} Missing in {count:>3} files")
    
    # Files with missing sections
    incomplete_files = [r for r in results if not r.get('complete', False) and 'error' not in r]
    
    print(f"\n" + "=" * 80)
    print(f"INCOMPLETE FILES: {len(incomplete_files)}/{total}")
    print("=" * 80)
    
    if incomplete_files:
        # Group by missing sections
        by_missing = defaultdict(list)
        for result in incomplete_files:
            key = ', '.join(result['missing'])
            by_missing[key].append(result['file'])
        
        for missing_str, files in sorted(by_missing.items(), key=lambda x: len(x[1]), reverse=True)[:20]:
            print(f"\n❌ Missing: {missing_str}")
            print(f"   Files ({len(files)}): {', '.join(files[:5])}")
            if len(files) > 5:
                print(f"   ... and {len(files) - 5} more")
    else:
        print("\n✅ All files are complete!")
    
    # Complete files
    complete_files = [r for r in results if r.get('complete', False)]
    print(f"\n✅ Complete files: {len(complete_files)}/{total}")

if __name__ == '__main__':
    main()

