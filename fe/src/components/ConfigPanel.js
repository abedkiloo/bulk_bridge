import React, { useState, useEffect } from 'react';
import './ConfigPanel.css';

const ConfigPanel = ({ refreshInterval, onRefreshIntervalChange, onShowUploadHistory }) => {
  const [isOpen, setIsOpen] = useState(false);
  const [tempInterval, setTempInterval] = useState(refreshInterval);

  useEffect(() => {
    setTempInterval(refreshInterval);
  }, [refreshInterval]);

  const handleSave = () => {
    onRefreshIntervalChange(tempInterval);
    setIsOpen(false);
  };

  const handleCancel = () => {
    setTempInterval(refreshInterval);
    setIsOpen(false);
  };

  return (
    <div className="config-panel">
      <button 
        className="config-toggle"
        onClick={() => setIsOpen(!isOpen)}
        title="Settings"
      >
        ⚙️
      </button>
      
      {isOpen && (
        <div className="config-dropdown">
          <h3>Settings</h3>
          
          <div className="config-group">
            <label htmlFor="refresh-interval">
              Status Refresh Interval (seconds):
            </label>
            <select 
              id="refresh-interval"
              value={tempInterval}
              onChange={(e) => setTempInterval(parseInt(e.target.value))}
            >
              <option value={5}>5 seconds</option>
              <option value={10}>10 seconds</option>
              <option value={15}>15 seconds</option>
              <option value={30}>30 seconds</option>
              <option value={60}>1 minute</option>
            </select>
          </div>

          <div className="config-group">
            <button 
              className="btn-secondary"
              onClick={onShowUploadHistory}
            >
              📁 View Upload History
            </button>
          </div>

          <div className="config-actions">
            <button className="btn-primary" onClick={handleSave}>
              Save
            </button>
            <button className="btn-secondary" onClick={handleCancel}>
              Cancel
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default ConfigPanel;
