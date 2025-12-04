#!/usr/bin/env python3
"""
Fix remaining files that need Hero section or proper navigation
"""
import re
from pathlib import Path

def has_hero_section(content):
    """Check if file has hero section"""
    # Check for various hero patterns
    patterns = [
        r'hero|Hero',
        r'id=["\']home|id=["\']beranda',
        r'<header|<section[^>]*hero',
        r'className.*hero|class.*hero',
        r'py-20.*header|py-24.*header',
        r'flex.*items-center.*justify-center.*h-screen',
        r'pt-32|pt-48|pt-20.*pb-20'
    ]
    for pattern in patterns:
        if re.search(pattern, content, re.IGNORECASE):
            return True
    return False

def has_navigation(content):
    """Check if file has navigation"""
    patterns = [
        r'<nav',
        r'Navigation|navigation',
        r'className.*nav|class.*nav',
        r'sticky.*top-0|fixed.*top-0'
    ]
    for pattern in patterns:
        if re.search(pattern, content, re.IGNORECASE):
            return True
    return False

def add_navigation_to_react_file(file_path):
    """Add navigation to React file that doesn't have it"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if has_navigation(content):
            return False
        
        # Check if it's a React file
        if 'type="text/babel"' not in content and 'ReactDOM' not in content:
            return False
        
        # Find where to insert nav (after opening body or root div)
        nav_template = '''                    <nav className="py-4 px-6 bg-white/90 backdrop-blur sticky top-0 z-50 border-b border-gray-200">
                        <div className="container mx-auto flex justify-between items-center">
                            <div className="text-xl font-bold text-gray-900">Brand</div>
                            <div className="hidden md:flex gap-6 text-sm font-medium text-gray-600">
                                <a href="#home" className="hover:text-blue-600">Beranda</a>
                                <a href="#about" className="hover:text-blue-600">Tentang</a>
                                <a href="#services" className="hover:text-blue-600">Layanan</a>
                                <a href="#contact" className="hover:text-blue-600">Kontak</a>
                            </div>
                            <button className="bg-blue-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-blue-700 transition-colors">
                                Hubungi Kami
                            </button>
                        </div>
                    </nav>'''
        
        # Try to find the opening of main component
        pattern = r'(<div className="min-h-screen|<div className="min-h-screen flex)'
        match = re.search(pattern, content)
        if match:
            insert_pos = match.end()
            content = content[:insert_pos] + '\n' + nav_template + '\n                    ' + content[insert_pos:]
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"✅ Added navigation to {file_path.name}")
            return True
        
        return False
    except Exception as e:
        print(f"❌ Error processing {file_path.name}: {str(e)}")
        return False

def main():
    """Main function"""
    lp_dir = Path('/www/wwwroot/portfolio.irvandoda.my.id/LP')
    
    if not lp_dir.exists():
        print(f"❌ Directory not found: {lp_dir}")
        return
    
    # Files that need fixing based on analysis
    files_to_check = [
        'webinar-landing.html',
        'webinarseries.html'
    ]
    
    print("🔧 Fixing navigation in files...\n")
    
    for filename in files_to_check:
        file_path = lp_dir / filename
        if file_path.exists():
            add_navigation_to_react_file(file_path)
        else:
            print(f"⚠️  File not found: {filename}")

if __name__ == '__main__':
    main()

