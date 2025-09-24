import React from 'react';
import './Logo.css';

const Logo = ({ size = 'medium', showText = true }) => {
  const getSizeClass = () => {
    switch (size) {
      case 'small': return 'logo-small';
      case 'large': return 'logo-large';
      default: return 'logo-medium';
    }
  };

  return (
    <div className={`logo-container ${getSizeClass()}`}>
      <div className="logo-icon">
        {/* Bridge Structure */}
        <div className="bridge">
          <div className="bridge-pillar bridge-pillar-left"></div>
          <div className="bridge-pillar bridge-pillar-right"></div>
          <div className="bridge-deck"></div>
          <div className="bridge-cables">
            <div className="cable cable-1"></div>
            <div className="cable cable-2"></div>
            <div className="cable cable-3"></div>
            <div className="cable cable-4"></div>
          </div>
        </div>
        
        {/* Bulk Data Flow */}
        <div className="data-flow">
          <div className="data-stream data-stream-1">
            <div className="data-point"></div>
            <div className="data-point"></div>
            <div className="data-point"></div>
          </div>
          <div className="data-stream data-stream-2">
            <div className="data-point"></div>
            <div className="data-point"></div>
            <div className="data-point"></div>
          </div>
          <div className="data-stream data-stream-3">
            <div className="data-point"></div>
            <div className="data-point"></div>
            <div className="data-point"></div>
          </div>
        </div>
        
        {/* Balance Scale */}
        <div className="balance-scale">
          <div className="scale-arm"></div>
          <div className="scale-pan scale-pan-left">
            <div className="scale-weight"></div>
          </div>
          <div className="scale-pan scale-pan-right">
            <div className="scale-weight"></div>
          </div>
          <div className="scale-base"></div>
        </div>
      </div>
      
      {showText && (
        <div className="logo-text">
          <span className="logo-primary">Bulk</span>
          <span className="logo-secondary">Bridge</span>
        </div>
      )}
    </div>
  );
};

export default Logo;
