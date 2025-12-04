#!/usr/bin/env python3
"""
Rebuild broken React files that have mixed HTML/JSX structure
"""
import re
from pathlib import Path

def is_react_file(content):
    """Check if file uses React"""
    return 'type="text/babel"' in content or 'ReactDOM' in content

def has_mixed_structure(content):
    """Check if file has mixed HTML (class) and JSX (className)"""
    # Check if there's HTML with class= inside React component
    # Pattern: React component opening, then HTML with class=
    react_pattern = r'return\s*\(|const\s+\w+\s*=\s*\(\)\s*=>'
    if re.search(react_pattern, content):
        # Check for class= after React component starts
        # This is a simple check - if we find class= near className, it's likely mixed
        if 'className' in content and re.search(r'class\s*=', content):
            # More specific: check if class= appears after return statement
            return_match = re.search(r'return\s*\(', content)
            if return_match:
                after_return = content[return_match.end():]
                if re.search(r'class\s*=', after_return):
                    return True
    return False

def find_broken_react_files():
    """Find React files with mixed structure"""
    lp_dir = Path('/www/wwwroot/portfolio.irvandoda.my.id/LP')
    broken_files = []
    
    for html_file in lp_dir.glob('*.html'):
        try:
            with open(html_file, 'r', encoding='utf-8') as f:
                content = f.read()
            
            if is_react_file(content) and has_mixed_structure(content):
                broken_files.append(html_file.name)
        except:
            pass
    
    return broken_files

if __name__ == '__main__':
    broken = find_broken_react_files()
    print(f"Found {len(broken)} files with mixed structure:")
    for f in broken:
        print(f"  - {f}")

