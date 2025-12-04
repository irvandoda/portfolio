#!/usr/bin/env python3
"""
Remove all remaining HTML sections (with class=) from inside React components
"""
import re
from pathlib import Path

def remove_html_from_react_component(content):
    """Remove HTML sections with class= from React component"""
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
    # Pattern: <!-- comment --> followed by <section class= or <div class=
    html_pattern = r'(<!--[^>]*?-->)?\s*<(section|div)[^>]*class\s*=[^>]*>.*?</\2>'
    
    matches = list(re.finditer(html_pattern, component_content, re.DOTALL | re.IGNORECASE))
    
    if not matches:
        return content, False
    
    # Remove HTML sections
    new_component = component_content
    offset = 0
    for match in reversed(matches):
        start = match.start() + offset
        end = match.end() + offset
        new_component = new_component[:start] + new_component[end:]
        offset -= (end - start)
    
    # Also remove incomplete comments like {/* Benefits Section
    new_component = re.sub(r'\{/\*[^}]*$', '', new_component, flags=re.MULTILINE)
    
    new_content = content[:component_start] + new_component + content[component_end.start():]
    
    return new_content, True

def fix_file(file_path):
    """Fix a single file"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if 'type="text/babel"' not in content:
            return False
        
        # Check if there's HTML with class= inside component
        return_match = re.search(r'return\s*\(', content)
        if not return_match:
            return False
        
        component_content = content[return_match.end():]
        component_end = re.search(r'\)\s*;\s*}\s*;\s*(const\s+root|ReactDOM)', component_content)
        if not component_end:
            return False
        
        component_content = component_content[:component_end.start()]
        
        # Check if there's class= (not in body tag)
        if not re.search(r'<(section|div)[^>]*class\s*=', component_content, re.IGNORECASE):
            return False
        
        new_content, changed = remove_html_from_react_component(content)
        
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
    
    print(f"🔧 Removing remaining HTML from {len(react_files)} React files...\n")
    
    fixed = 0
    for html_file in react_files:
        if fix_file(html_file):
            print(f"✅ Fixed {html_file.name}")
            fixed += 1
    
    print(f"\n✨ Fixed {fixed}/{len(react_files)} files")

if __name__ == '__main__':
    main()

