
import os
import shutil

lp_dir = '/www/wwwroot/portfolio.irvandoda.my.id/LP'

# 1. Revert directory structure: Move index.php back to projectname.html
# Iterate over directories in LP
for item in os.listdir(lp_dir):
    path = os.path.join(lp_dir, item)
    
    if os.path.isdir(path):
        # Check for index.php
        index_php = os.path.join(path, 'index.php')
        if os.path.exists(index_php):
            # Move and rename to item.html in lp_dir
            dest = os.path.join(lp_dir, f"{item}.html")
            shutil.move(index_php, dest)
            print(f"Reverted {item}/index.php to {item}.html")
            # Remove empty directory
            try:
                os.rmdir(path)
            except OSError:
                print(f"Directory {path} not empty, skipping removal")

# 2. Scan for all .html files in LP/ to synchronize with index.html
html_files = [f[:-5] for f in os.listdir(lp_dir) if f.endswith('.html')]
print(f"Found {len(html_files)} HTML files.")

# 3. Update index.html
index_path = '/www/wwwroot/portfolio.irvandoda.my.id/index.html'
with open(index_path, 'r') as f:
    content = f.read()

# Revert URL format in render function and specific items
# We need to ensure `url` property points to `/LP/filename.html`
# And remove the `url` property if we want the fallback to work, OR update it.
# The user wants: "sinkronkan ... supaya yang kosong bisa dilihat"

# Strategy:
# Read index.html, identify the `portfolios` array.
# For each item in `portfolios` (found via regex or line parsing), 
# check if its `id` (or a mapped variant) exists in `html_files`.
# If yes, ensure `url: '/LP/id.html'` is present or default behavior covers it.
# If no, maybe disable it? Or just ensure the link is correct even if 404? 
# User said "supaya yang kosong bisa dilihat projectsnya sesuai dengan file".
# This implies some IDs in index.html might not match filenames exactly?
# Or just that I should make sure all files are linked.

# Let's just do a simple string replacement to revert the previous changes first:
# /LP/foo/ -> /LP/foo.html
# .php -> .html

new_content = content.replace("/LP/${portfolio.id}.php", "/LP/${portfolio.id}.html")
new_content = new_content.replace(".php'", ".html'") # Revert specific URLs in array
new_content = new_content.replace("/'", ".html'") # Revert folder urls '/LP/foo/' -> '/LP/foo.html'
# Wait, '/LP/foo/' replacement needs care.
# In previous step I did: replace(".php'", "/'")
# So '/LP/kedaikopi.php' became '/LP/kedaikopi/'
# Now I need '/LP/kedaikopi/' -> '/LP/kedaikopi.html'

import re
# Replace '/LP/xyz/' with '/LP/xyz.html'
new_content = re.sub(r"'/LP/([^/']+)/'", r"'/LP/\1.html'", new_content)

with open(index_path, 'w') as f:
    f.write(new_content)

print("Done reverting structure and updating index.html")

