import React from 'react';
import './PageIcons.css';

const UploadIcon = ({ size = 'medium' }) => {
  return (
    <div className={`page-icon upload-icon ${size}`}>
      <div className="upload-cloud">
        <div className="cloud-body"></div>
        <div className="cloud-shadow"></div>
      </div>
      <div className="upload-arrow">
        <div className="arrow-shaft"></div>
        <div className="arrow-head"></div>
      </div>
      <div className="upload-lines">
        <div className="upload-line line-1"></div>
        <div className="upload-line line-2"></div>
        <div className="upload-line line-3"></div>
      </div>
    </div>
  );
};

const MonitorIcon = ({ size = 'medium' }) => {
  return (
    <div className={`page-icon monitor-icon ${size}`}>
      <div className="monitor-screen">
        <div className="screen-frame"></div>
        <div className="screen-content">
          <div className="progress-bar">
            <div className="progress-fill"></div>
          </div>
          <div className="data-points">
            <div className="data-dot dot-1"></div>
            <div className="data-dot dot-2"></div>
            <div className="data-dot dot-3"></div>
            <div className="data-dot dot-4"></div>
          </div>
        </div>
      </div>
      <div className="monitor-stand">
        <div className="stand-base"></div>
        <div className="stand-arm"></div>
      </div>
    </div>
  );
};

const DetailsIcon = ({ size = 'medium' }) => {
  return (
    <div className={`page-icon details-icon ${size}`}>
      <div className="details-document">
        <div className="document-page">
          <div className="page-lines">
            <div className="page-line line-1"></div>
            <div className="page-line line-2"></div>
            <div className="page-line line-3"></div>
            <div className="page-line line-4"></div>
          </div>
          <div className="page-stats">
            <div className="stat-item">
              <div className="stat-bar success"></div>
            </div>
            <div className="stat-item">
              <div className="stat-bar error"></div>
            </div>
            <div className="stat-item">
              <div className="stat-bar pending"></div>
            </div>
          </div>
        </div>
        <div className="document-fold"></div>
      </div>
      <div className="details-magnifier">
        <div className="magnifier-lens"></div>
        <div className="magnifier-handle"></div>
      </div>
    </div>
  );
};

export { UploadIcon, MonitorIcon, DetailsIcon };
