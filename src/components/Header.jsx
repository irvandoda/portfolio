import React from 'react';
import CategoryFilter from './CategoryFilter';

const Header = ({ activeCategory, onFilterChange, portfolioCount }) => {
  return (
    <header className="container mx-auto px-6 py-12 text-center">
      <div className="animate-fade-in-down">
        <h1 className="text-5xl md:text-6xl font-bold text-white mb-4 text-shadow-lg">
          <span className="gradient-text">Portfolio Library</span>
        </h1>
        <p className="text-xl text-gray-300 mb-2">
          Kumpulan <span className="text-purple-400 font-semibold">{portfolioCount}+</span> Projects Website yang telah dibuat
        </p>
        <div className="flex items-center justify-center gap-2 mt-4 mb-8">
          <div className="flex gap-1">
            <div className="w-2 h-2 bg-purple-500 rounded-full animate-pulse"></div>
            <div className="w-2 h-2 bg-pink-500 rounded-full animate-pulse" style={{ animationDelay: '0.2s' }}></div>
            <div className="w-2 h-2 bg-purple-500 rounded-full animate-pulse" style={{ animationDelay: '0.4s' }}></div>
          </div>
          <span className="text-gray-400 text-sm">Professional Portfolio Collection</span>
        </div>
      </div>
      
      <CategoryFilter activeCategory={activeCategory} onFilterChange={onFilterChange} />
    </header>
  );
};

export default Header;
