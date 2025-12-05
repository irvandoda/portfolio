import React, { useEffect, useState } from 'react';

const ProgressBar = ({ progress = 0, show = true }) => {
  const [displayProgress, setDisplayProgress] = useState(0);

  useEffect(() => {
    if (show) {
      const timer = setTimeout(() => {
        setDisplayProgress(progress);
      }, 100);
      return () => clearTimeout(timer);
    }
  }, [progress, show]);

  if (!show) return null;

  return (
    <div className="fixed top-0 left-0 w-full h-1 z-50 bg-slate-800">
      <div
        className="h-full bg-gradient-to-r from-purple-600 via-pink-500 to-purple-600 transition-all duration-500 ease-out shadow-lg"
        style={{ width: `${displayProgress}%` }}
      >
        <div className="h-full w-full bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer"></div>
      </div>
    </div>
  );
};

export default ProgressBar;
