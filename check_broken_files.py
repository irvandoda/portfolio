#!/usr/bin/env python3
"""
Check all HTML files for issues (blank, whitescreen, broken structure)
"""
import re
from pathlib import Path

def check_html_file(file_path):
    """Check if HTML file is valid and complete"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        issues = []
        
        # Check if file is too small (likely broken)
        if len(content.strip()) < 500:
            issues.append("File terlalu kecil (kemungkinan kosong/rusak)")
        
        # Check for basic HTML structure
        if not re.search(r'<!DOCTYPE\s+html', content, re.IGNORECASE):
            issues.append("Tidak ada DOCTYPE")
        
        if not re.search(r'<html', content, re.IGNORECASE):
            issues.append("Tidak ada tag <html>")
        
        if not re.search(r'<body', content, re.IGNORECASE):
            issues.append("Tidak ada tag <body>")
        
        # Check for React files
        is_react = 'type="text/babel"' in content or 'ReactDOM' in content
        
        if is_react:
            # Check if React root exists
            if not re.search(r'<div\s+id=["\']root["\']', content):
                issues.append("React: Tidak ada div#root")
            
            if not re.search(r'ReactDOM\.(createRoot|render)', content):
                issues.append("React: Tidak ada ReactDOM render")
            
            # Check for closing tags
            if content.count('<div id="root">') > 0 and content.count('</div>') < 3:
                issues.append("React: Struktur tidak lengkap")
        
        # Check for closing body/html tags
        if not re.search(r'</body>', content, re.IGNORECASE):
            issues.append("Tidak ada closing tag </body>")
        
        if not re.search(r'</html>', content, re.IGNORECASE):
            issues.append("Tidak ada closing tag </html>")
        
        # Check for syntax errors (unclosed tags, broken JSX)
        if is_react:
            # Check for unclosed JSX tags
            open_divs = len(re.findall(r'<div', content))
            close_divs = len(re.findall(r'</div>', content))
            if open_divs > close_divs + 5:  # Allow some difference
                issues.append("React: Kemungkinan tag tidak tertutup")
        
        # Check if file has actual content (not just empty structure)
        text_content = re.sub(r'<[^>]+>', '', content)
        if len(text_content.strip()) < 100:
            issues.append("Konten terlalu sedikit")
        
        return {
            'file': file_path.name,
            'size': len(content),
            'is_react': is_react,
            'issues': issues,
            'broken': len(issues) > 0
        }
    except Exception as e:
        return {
            'file': file_path.name,
            'error': str(e),
            'broken': True
        }

def main():
    """Main function"""
    lp_dir = Path('/www/wwwroot/portfolio.irvandoda.my.id/LP')
    
    if not lp_dir.exists():
        print(f"❌ Directory not found: {lp_dir}")
        return
    
    html_files = list(lp_dir.glob('*.html'))
    total = len(html_files)
    
    print(f"📁 Checking {total} HTML files...\n")
    
    broken_files = []
    all_results = []
    
    for html_file in html_files:
        result = check_html_file(html_file)
        all_results.append(result)
        
        if result.get('broken', False):
            broken_files.append(result)
            print(f"❌ {result['file']}")
            if 'error' in result:
                print(f"   Error: {result['error']}")
            else:
                for issue in result.get('issues', []):
                    print(f"   - {issue}")
            print()
    
    print("=" * 80)
    print(f"SUMMARY")
    print("=" * 80)
    print(f"Total files: {total}")
    print(f"Broken files: {len(broken_files)}")
    print(f"Valid files: {total - len(broken_files)}")
    
    if broken_files:
        print(f"\n📋 Broken files list:")
        for result in broken_files:
            print(f"   - {result['file']}")
    
    return broken_files

if __name__ == '__main__':
    main()

