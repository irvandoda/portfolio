import React, { useState } from 'react';
import { categoryLabels } from '../data/portfolios';

const PortfolioCard = ({ portfolio, index }) => {
  const [imageLoaded, setImageLoaded] = useState(false);
  const [imageError, setImageError] = useState(false);

  const handleImageLoad = () => {
    setImageLoaded(true);
  };

  const handleImageError = () => {
    setImageError(true);
    setImageLoaded(true);
  };

  return (
    <div
      className="portfolio-card glass-card overflow-hidden group animate-fade-in-up"
      style={{ animationDelay: `${index * 0.05}s` }}
    >
      <div className="relative h-48 overflow-hidden">
        <a href={portfolio.url || `/LP/${portfolio.id}.html`} className="block w-full h-full">
          {!imageLoaded && (
            <div className="absolute inset-0 bg-gradient-to-br from-purple-600/20 to-pink-600/20 animate-pulse flex items-center justify-center">
              <div className="w-12 h-12 border-4 border-purple-500/50 border-t-purple-500 rounded-full animate-spin"></div>
            </div>
          )}
          <img
            src={imageError ? 'https://placehold.co/600x400/9333ea/ffffff?text=Image' : portfolio.image}
            alt={portfolio.title}
            onLoad={handleImageLoad}
            onError={handleImageError}
            className={`w-full h-full object-cover transition-all duration-500 group-hover:scale-110 ${
              imageLoaded ? 'opacity-100' : 'opacity-0'
            }`}
            loading="lazy"
          />
        </a>
        
        <div className="absolute top-4 right-4 transform transition-transform duration-300 group-hover:scale-110">
          <span className="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg backdrop-blur-sm">
            {categoryLabels[portfolio.category] || portfolio.category}
          </span>
        </div>
        
        <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-black/0 to-black/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
          <div className="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
            <span className="bg-white/95 text-purple-600 px-5 py-2 rounded-full text-sm font-semibold shadow-xl flex items-center gap-2">
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              Preview
            </span>
          </div>
        </div>
      </div>
      
      <div className="p-6">
        <h3 className="text-xl font-bold text-white mb-2 group-hover:text-purple-400 transition-colors">
          {portfolio.title}
        </h3>
        <p className="text-gray-300 text-sm mb-4 leading-relaxed line-clamp-2">
          {portfolio.description}
        </p>
        
        <div className="flex flex-wrap gap-2 mb-4">
          {portfolio.tags.map((tag, idx) => (
            <span
              key={idx}
              className="bg-white/10 text-gray-300 px-2 py-1 rounded text-xs backdrop-blur-sm hover:bg-white/20 transition-colors"
            >
              {tag}
            </span>
          ))}
        </div>
        
        <div className="flex items-center justify-between">
          <span className="text-gray-400 text-xs">{portfolio.date}</span>
          <a
            href={portfolio.url || `/LP/${portfolio.id}.html`}
            target="_blank"
            rel="noopener noreferrer"
            className="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-5 py-2 rounded-full text-sm font-medium transition-all duration-300 inline-flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:scale-105"
          >
            Lihat Project
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </a>
        </div>
      </div>
    </div>
  );
};

export default PortfolioCard;
