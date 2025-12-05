import React from 'react';
import PortfolioCard from './PortfolioCard';

const PortfolioGrid = ({ portfolios, isLoading }) => {
  if (isLoading) {
    return (
      <div className="container mx-auto px-6 pb-20">
        <div className="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {[...Array(8)].map((_, idx) => (
            <div
              key={idx}
              className="glass-card h-96 animate-pulse"
              style={{ animationDelay: `${idx * 0.1}s` }}
            >
              <div className="h-48 bg-gradient-to-br from-purple-600/20 to-pink-600/20"></div>
              <div className="p-6 space-y-4">
                <div className="h-4 bg-gray-700/50 rounded w-3/4"></div>
                <div className="h-3 bg-gray-700/50 rounded"></div>
                <div className="h-3 bg-gray-700/50 rounded w-5/6"></div>
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  if (portfolios.length === 0) {
    return (
      <div className="container mx-auto px-6 pb-20">
        <div className="col-span-full text-center py-20 animate-fade-in-up">
          <div className="inline-block p-8 glass-card rounded-2xl">
            <svg
              className="w-24 h-24 mx-auto mb-4 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <p className="text-gray-400 text-xl font-medium">Tidak ada portfolio dalam kategori ini.</p>
            <p className="text-gray-500 text-sm mt-2">Coba pilih kategori lain</p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <main className="container mx-auto px-6 pb-20">
      <div className="mb-6 text-gray-400 text-sm animate-fade-in-down">
        Menampilkan <span className="text-purple-400 font-semibold">{portfolios.length}</span> portfolio
      </div>
      <div className="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        {portfolios.map((portfolio, index) => (
          <PortfolioCard key={portfolio.id} portfolio={portfolio} index={index} />
        ))}
      </div>
    </main>
  );
};

export default PortfolioGrid;
