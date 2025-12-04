#!/usr/bin/env python3
import re
from pathlib import Path

CREDIT_FOOTER_REACT = '''                            {/* Credit Footer */}
                            <div className="border-t border-gray-200/30 mt-8 pt-8">
                                <div className="container mx-auto px-6">
                                    <div className="flex flex-col md:flex-row items-center justify-between gap-4 text-center md:text-left">
                                        <div className="flex-1">
                                            <p className="text-gray-500 text-sm mb-2">
                                                Designed & Developed with <span className="text-red-500">❤️</span> by
                                            </p>
                                            <div className="flex flex-wrap items-center justify-center md:justify-start gap-3 text-sm">
                                                <a href="https://irvandoda.my.id" target="_blank" className="text-gray-700 hover:text-purple-600 font-medium transition-colors">
                                                    Irvando Demas Arifiandani
                                                </a>
                                                <span className="text-gray-400">•</span>
                                                <a href="https://wa.me/6285747476308" target="_blank" className="text-gray-600 hover:text-green-600 transition-colors">
                                                    📱 +62 857 4747 6308
                                                </a>
                                                <span className="text-gray-400">•</span>
                                                <a href="mailto:irvando.d.a@gmail.com" className="text-gray-600 hover:text-blue-600 transition-colors">
                                                    ✉️ irvando.d.a@gmail.com
                                                </a>
                                            </div>
                                        </div>
                                        <div className="text-xs text-gray-400">
                                            <a href="https://irvandoda.my.id" target="_blank" className="hover:text-purple-600 transition-colors">
                                                irvandoda.my.id
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>'''

def is_react_file(content):
    """Check if file uses React"""
    return 'type="text/babel"' in content or 'ReactDOM.createRoot' in content

def fix_react_credit(file_path):
    """Fix credit footer placement in React files"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if not is_react_file(content):
            return False
        
        # Check if credit exists but is misplaced (outside footer)
        if 'Irvando Demas Arifiandani' in content:
            # Check if credit is outside footer tag
            # Pattern: credit footer followed by </footer> (wrong placement)
            wrong_pattern = r'\{/\* Credit Footer \*/\}.*?</div>\s*</footer>'
            if re.search(wrong_pattern, content, re.DOTALL):
                # Remove the misplaced credit
                content = re.sub(
                    r'\s*\{/\* Credit Footer \*/\}.*?</div>\s*',
                    '',
                    content,
                    flags=re.DOTALL
                )
            
            # Now add credit properly inside footer
            # Look for footer with closing tag
            footer_pattern = r'(<footer[^>]*>.*?)(</footer>)'
            match = re.search(footer_pattern, content, re.DOTALL)
            if match:
                footer_content = match.group(1)
                # Check if credit is already inside footer
                if 'Irvando Demas Arifiandani' not in footer_content:
                    # Add credit before closing footer tag
                    content = re.sub(
                        r'(</footer>)',
                        CREDIT_FOOTER_REACT + r'\n                        \1',
                        content,
                        count=1
                    )
                    with open(file_path, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"✅ Fixed credit in {file_path.name}")
                    return True
        else:
            # Credit doesn't exist, add it
            footer_pattern = r'(</footer>)'
            if re.search(footer_pattern, content):
                content = re.sub(
                    r'(</footer>)',
                    CREDIT_FOOTER_REACT + r'\n                        \1',
                    content,
                    count=1
                )
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"✅ Added credit to {file_path.name}")
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
    
    html_files = [f for f in lp_dir.glob('*.html') if is_react_file(f.read_text(encoding='utf-8'))]
    total = len(html_files)
    success = 0
    
    print(f"📁 Found {total} React HTML files")
    print("🔧 Fixing credit footer placement...\n")
    
    for html_file in html_files:
        if fix_react_credit(html_file):
            success += 1
    
    print(f"\n✨ Done! Fixed {success}/{total} React files")

if __name__ == '__main__':
    main()

