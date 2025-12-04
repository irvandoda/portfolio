#!/usr/bin/env python3
"""
Fix React files that have mixed HTML (class) and JSX (className) structure
This will convert HTML sections to JSX and move them inside React component
"""
import re
from pathlib import Path

def convert_html_to_jsx(html_content):
    """Convert HTML attributes to JSX"""
    # Replace class= with className=
    jsx = re.sub(r'\bclass\s*=', 'className=', html_content)
    # Replace for= with htmlFor=
    jsx = re.sub(r'\bfor\s*=', 'htmlFor=', jsx)
    # Replace self-closing tags
    jsx = re.sub(r'<(\w+)([^>]*?)(?<!/)>', r'<\1\2 />', jsx)
    return jsx

def fix_react_file(file_path):
    """Fix React file with mixed structure"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if 'type="text/babel"' not in content:
            return False
        
        # Find the React component return statement
        return_match = re.search(r'return\s*\(', content)
        if not return_match:
            return False
        
        # Find where the component ends (before closing );)
        component_end = re.search(r'\)\s*;\s*}\s*;\s*const\s+root', content)
        if not component_end:
            # Try alternative pattern
            component_end = re.search(r'\)\s*;\s*}\s*;\s*ReactDOM', content)
        
        if not component_end:
            return False
        
        # Extract the component content
        component_start = return_match.end()
        component_content = content[component_start:component_end.start()]
        
        # Find HTML sections with class= that are outside JSX
        # Look for sections that start with <!-- and have class=
        html_sections = []
        
        # Pattern to find HTML sections: <!-- Section --> followed by <section class=
        pattern = r'(<!--\s*[^>]*?-->\s*)?<section[^>]*class\s*=[^>]*>.*?</section>'
        matches = list(re.finditer(pattern, component_content, re.DOTALL | re.IGNORECASE))
        
        if not matches:
            # Try finding any HTML with class= inside component
            pattern2 = r'<[^>]+class\s*=[^>]*>'
            if re.search(pattern2, component_content):
                # This file has mixed structure
                # We need to convert all class= to className= in the component
                fixed_component = convert_html_to_jsx(component_content)
                new_content = content[:component_start] + fixed_component + content[component_end.start():]
                
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
    
    # Files that definitely need fixing
    priority_files = ['fotografer.html', 'desaingrafis.html']
    
    print("🔧 Fixing React files with mixed structure...\n")
    
    fixed = 0
    for filename in priority_files:
        file_path = lp_dir / filename
        if file_path.exists():
            if fix_react_file(file_path):
                print(f"✅ Fixed {filename}")
                fixed += 1
            else:
                print(f"⚠️  {filename} needs manual fix")
    
    print(f"\n✨ Fixed {fixed} files")
    print("\nNote: Other files may also need fixing. Consider rebuilding them.")

if __name__ == '__main__':
    main()

