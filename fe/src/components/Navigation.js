import React from 'react';
import Logo from './Logo';
import { UploadIcon, MonitorIcon, DetailsIcon } from './PageIcons';
import './Navigation.css';

const Navigation = ({ activePage, onPageChange }) => {
  const pages = [
    { id: 'upload', label: 'Upload', icon: 'upload' },
    { id: 'monitor', label: 'Monitor', icon: 'monitor' },
    { id: 'details', label: 'Details', icon: 'details' },
    { id: 'employees', label: 'Employees', icon: 'employees' }
  ];

  const renderIcon = (iconType) => {
    switch (iconType) {
      case 'upload':
        return <UploadIcon size="medium" />;
      case 'monitor':
        return <MonitorIcon size="medium" />;
      case 'details':
        return <DetailsIcon size="medium" />;
      case 'employees':
        return <span className="nav-icon-text">👥</span>;
      default:
        return null;
    }
  };

  return (
    <nav className="navigation">
      <div className="nav-container">
        <div className="nav-brand">
          <Logo size="medium" showText={true} />
        </div>
        
        <div className="nav-menu">
          {pages.map((page) => (
            <button
              key={page.id}
              className={`nav-item ${activePage === page.id ? 'active' : ''}`}
              onClick={() => onPageChange(page.id)}
            >
              <span className="nav-icon">{renderIcon(page.icon)}</span>
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
