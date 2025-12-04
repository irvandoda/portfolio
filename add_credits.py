#!/usr/bin/env python3
import os
import re
from pathlib import Path

# Credit footer template
CREDIT_FOOTER_HTML = '''    <!-- Credit Footer -->
    <div class="border-t border-gray-200/30 mt-12 pt-8">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-center md:text-left">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2">
                        Designed & Developed with <span class="text-red-500">❤️</span> by
                    </p>
                    <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 text-sm">
                        <a href="https://irvandoda.my.id" target="_blank" class="text-gray-700 hover:text-purple-600 font-medium transition-colors">
                            Irvando Demas Arifiandani
                        </a>
                        <span class="text-gray-400">•</span>
                        <a href="https://wa.me/6285747476308" target="_blank" class="text-gray-600 hover:text-green-600 transition-colors">
                            📱 +62 857 4747 6308
                        </a>
                        <span class="text-gray-400">•</span>
                        <a href="mailto:irvando.d.a@gmail.com" class="text-gray-600 hover:text-blue-600 transition-colors">
                            ✉️ irvando.d.a@gmail.com
                        </a>
                    </div>
                </div>
                <div class="text-xs text-gray-400">
                    <a href="https://irvandoda.my.id" target="_blank" class="hover:text-purple-600 transition-colors">
                        irvandoda.my.id
                    </a>
                </div>
            </div>
        </div>
    </div>'''

CREDIT_FOOTER_REACT = '''                    {/* Credit Footer */}
                    <div className="border-t border-gray-200/30 mt-12 pt-8">
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
    return 'ReactDOM.createRoot' in content or 'ReactDOM.render' in content or 'type="text/babel"' in content

def has_existing_credit(content):
    """Check if credit already exists"""
    return 'Irvando Demas Arifiandani' in content or 'irvandoda.my.id' in content

def add_credit_to_html_file(file_path):
    """Add credit footer to HTML file"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Skip if credit already exists
        if has_existing_credit(content):
            print(f"⏭️  Skipping {file_path.name} - Credit already exists")
            return False
        
        is_react = is_react_file(content)
        
        if is_react:
            # For React files, add credit before closing footer tag
            # Look for footer closing tag
            footer_pattern = r'(</footer>)'
            if re.search(footer_pattern, content):
                # Add credit before closing footer
                content = re.sub(
                    r'(</footer>)',
                    CREDIT_FOOTER_REACT + r'\n                    \1',
                    content,
                    count=1
                )
            else:
                # If no footer, add before closing body
                content = re.sub(
                    r'(</body>)',
                    CREDIT_FOOTER_REACT + r'\n                </div>\n            );\n        };\n\n        const root = ReactDOM.createRoot(document.getElementById(\'root\'));\n        root.render(<App />);\n    </script>\n\1',
                    content,
                    count=1
                )
        else:
            # For regular HTML files, add credit before </body> or before existing footer
            # Try to find footer closing tag first
            footer_pattern = r'(</footer>)'
            if re.search(footer_pattern, content):
                # Add credit before closing footer
                content = re.sub(
                    r'(</footer>)',
                    CREDIT_FOOTER_HTML + r'\n    \1',
                    content,
                    count=1
                )
            else:
                # Add before </body>
                content = re.sub(
                    r'(</body>)',
                    CREDIT_FOOTER_HTML + r'\n    \1',
                    content,
                    count=1
                )
        
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        
        print(f"✅ Added credit to {file_path.name}")
        return True
    except Exception as e:
        print(f"❌ Error processing {file_path.name}: {str(e)}")
        return False

def main():
    """Main function"""
    lp_dir = Path('/www/wwwroot/portfolio.irvandoda.my.id/LP')
    
    if not lp_dir.exists():
        print(f"❌ Directory not found: {lp_dir}")
        return
    
    html_files = list(lp_dir.glob('*.html'))
    total = len(html_files)
    success = 0
    
    print(f"📁 Found {total} HTML files")
    print("🚀 Starting to add credit footers...\n")
    
    for html_file in html_files:
        if add_credit_to_html_file(html_file):
            success += 1
    
    print(f"\n✨ Done! Successfully added credits to {success}/{total} files")

if __name__ == '__main__':
    main()

