import React from 'react';
import './Navigation.css';

const Navigation = ({ activePage, onPageChange }) => {
  const pages = [
    { id: 'upload', label: '📁 Upload', icon: '📁' },
    { id: 'monitor', label: '📊 Monitor', icon: '📊' },
    { id: 'history', label: '📋 History', icon: '📋' }
  ];

  return (
    <nav className="navigation">
      <div className="nav-container">
        <div className="nav-brand">
          <h2>🚀 BulkBridge</h2>
        </div>
        
        <div className="nav-menu">
          {pages.map((page) => (
            <button
              key={page.id}
              className={`nav-item ${activePage === page.id ? 'active' : ''}`}
              onClick={() => onPageChange(page.id)}
            >
              <span className="nav-icon">{page.icon}</span>
              <span className="nav-label">{page.label}</span>
            </button>
          ))}
        </div>

        <div className="nav-actions">
          <button 
            className="nav-action-btn"
            onClick={() => onPageChange('settings')}
            title="Settings"
          >
            ⚙️
          </button>
        </div>
      </div>
    </nav>
  );
};

export default Navigation;
