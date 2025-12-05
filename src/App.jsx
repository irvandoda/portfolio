import React, { useState, useEffect, useMemo } from 'react';
import Header from './components/Header';
import Footer from './components/Footer';
import PortfolioGrid from './components/PortfolioGrid';
import LoadingIndicator from './components/LoadingIndicator';
import ProgressBar from './components/ProgressBar';
import { portfolios } from './data/portfolios';

function App() {
  const [activeCategory, setActiveCategory] = useState('all');
  const [isLoading, setIsLoading] = useState(true);
  const [loadingProgress, setLoadingProgress] = useState(0);

  // Simulate loading with progress
  useEffect(() => {
    let progress = 0;
    const interval = setInterval(() => {
      progress += 10;
      setLoadingProgress(progress);
      
      if (progress >= 100) {
        clearInterval(interval);
        setTimeout(() => {
          setIsLoading(false);
        }, 300);
      }
    }, 100);

    return () => clearInterval(interval);
  }, []);

  // Filter portfolios based on active category
  const filteredPortfolios = useMemo(() => {
    if (activeCategory === 'all') {
      return portfolios;
    }
    return portfolios.filter(p => p.category === activeCategory);
  }, [activeCategory]);

  const handleFilterChange = (category) => {
    setActiveCategory(category);
    // Smooth scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  if (isLoading) {
    return (
      <>
        <ProgressBar progress={loadingProgress} show={true} />
        <LoadingIndicator text="Memuat Portfolio Library..." />
      </>
    );
  }

  return (
    <div className="min-h-screen">
      <ProgressBar progress={100} show={false} />
      
      {/* Background decoration */}
      <div className="fixed inset-0 overflow-hidden pointer-events-none">
        <div className="absolute top-20 left-10 w-72 h-72 bg-purple-500/20 rounded-full blur-3xl animate-float"></div>
        <div className="absolute bottom-20 right-10 w-96 h-96 bg-pink-500/20 rounded-full blur-3xl animate-float" style={{ animationDelay: '2s' }}></div>
        <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-purple-600/10 rounded-full blur-3xl"></div>
      </div>

      <div className="relative z-10">
        <Header
          activeCategory={activeCategory}
          onFilterChange={handleFilterChange}
          portfolioCount={portfolios.length}
        />
        
        <PortfolioGrid
          portfolios={filteredPortfolios}
          isLoading={false}
        />
        
        <Footer />
      </div>
    </div>
  );
}

export default App;
