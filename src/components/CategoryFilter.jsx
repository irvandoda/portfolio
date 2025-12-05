import React from 'react';
import { categoryLabels } from '../data/portfolios';

const CategoryFilter = ({ activeCategory, onFilterChange }) => {
  const categories = Object.keys(categoryLabels);

  return (
    <div className="flex flex-wrap justify-center gap-3 mb-12 animate-fade-in-down">
      {categories.map((category) => (
        <button
          key={category}
          onClick={() => onFilterChange(category)}
          className={`
            px-6 py-2.5 rounded-full text-sm font-medium transition-all duration-300 transform
            ${
              activeCategory === category
                ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg shadow-purple-500/50 scale-105'
                : 'bg-white/10 text-gray-300 hover:bg-white/20 backdrop-blur-sm hover:scale-105'
            }
            hover:shadow-lg
          `}
        >
          <span className="relative z-10">{categoryLabels[category]}</span>
        </button>
      ))}
    </div>
  );
};

export default CategoryFilter;
