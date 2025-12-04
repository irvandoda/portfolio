#!/usr/bin/env python3
"""
Aggressively remove all HTML sections (with class=) from inside React components
"""
import re
from pathlib import Path

def clean_react_component(content):
    """Remove all HTML with class= from React component"""
    # Find React component return statement
    return_match = re.search(r'return\s*\(', content)
    if not return_match:
        return content, False
    
    # Find component end
    component_end = re.search(r'\)\s*;\s*}\s*;\s*(const\s+root|ReactDOM)', content)
    if not component_end:
        return content, False
    
    component_start = return_match.end()
    component_content = content[component_start:component_end.start()]
    
    # Find all HTML sections with class= (not className=)
    # More aggressive pattern to catch broken HTML
    patterns = [
        r'<svg[^>]*class\s*=[^>]*>.*?</svg>',  # SVG with class
        r'<div[^>]*class\s*=[^>]*>.*?</div>',  # Div with class
        r'<section[^>]*class\s*=[^>]*>.*?</section>',  # Section with class
        r'<h3[^>]*class\s*=[^>]*>.*?</h3>',  # H3 with class
        r'<p[^>]*class\s*=[^>]*>.*?</p>',  # P with class
        r'<img[^>]*class\s*=[^>]*>',  # Img with class
    ]
    
    changed = False
    new_component = component_content
    
    # Remove all HTML with class= in reverse order
    for pattern in patterns:
        matches = list(re.finditer(pattern, new_component, re.DOTALL | re.IGNORECASE))
        if matches:
            changed = True
            offset = 0
            for match in reversed(matches):
                start = match.start() + offset
                end = match.end() + offset
                # Check if it's not part of a JSX comment or string
                before = new_component[max(0, start-20):start]
                if 'className' not in match.group(0) and '//' not in before[-10:]:
                    new_component = new_component[:start] + new_component[end:]
                    offset -= (end - start)
    
    # Also remove incomplete HTML fragments
    # Remove lines that start with HTML tags but are incomplete
    lines = new_component.split('\n')
    cleaned_lines = []
    skip_next = False
    for i, line in enumerate(lines):
        # Skip lines with class= that are not JSX
        if re.search(r'class\s*=', line) and 'className' not in line:
            # Check if it's inside a JSX expression
            if '{' not in line[:line.find('class')] and '}' not in line:
                skip_next = True
                continue
        if skip_next:
            # Skip until we find a closing tag or JSX
            if '</' in line or 'className' in line or '}' in line:
                skip_next = False
            continue
        cleaned_lines.append(line)
    
    new_component = '\n'.join(cleaned_lines)
    
    # Remove orphaned HTML fragments
    new_component = re.sub(r'^\s*<[^>]*class\s*=[^>]*>.*$', '', new_component, flags=re.MULTILINE | re.DOTALL)
    
    if changed or new_component != component_content:
        new_content = content[:component_start] + new_component + content[component_end.start():]
        return new_content, True
    
    return content, False

def fix_file(file_path):
    """Fix a single file"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if 'type="text/babel"' not in content:
            return False
        
        new_content, changed = clean_react_component(content)
        
        if changed:
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(new_content)
            return True
        
        return False
    except Exception as e:
        print(f"Error processing {file_path.name}: {str(e)}")
        return False

def main():
    """Main function"""
    lp_dir = Path('/www/wwwroot/portfolio.irvandoda.my.id/LP')
    
    # Get all React files
    react_files = []
    for html_file in lp_dir.glob('*.html'):
        try:
            content = html_file.read_text(encoding='utf-8')
            if 'type="text/babel"' in content:
                react_files.append(html_file)
        except:
            pass
    
    print(f"🔧 Cleaning HTML from {len(react_files)} React files...\n")
    
    fixed = 0
    for html_file in react_files:
        if fix_file(html_file):
            print(f"✅ Cleaned {html_file.name}")
            fixed += 1
    
    print(f"\n✨ Cleaned {fixed}/{len(react_files)} files")

if __name__ == '__main__':
    main()

