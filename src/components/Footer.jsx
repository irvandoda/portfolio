import React from 'react';

const Footer = () => {
  return (
    <footer className="border-t border-white/10 py-8 mt-20 animate-fade-in-up">
      <div className="container mx-auto px-6">
        <div className="flex flex-col md:flex-row items-center justify-between text-gray-400">
          <p>&copy; 2025 Irvandoda Portfolio. All rights reserved.</p>
          <div className="flex items-center gap-2 mt-4 md:mt-0">
            <span className="text-sm">Made with</span>
            <span className="text-red-500 animate-pulse">❤️</span>
            <span className="text-sm">using React & Tailwind CSS</span>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
