import React, { memo, useState, useEffect } from 'react';
import './ProgressTicker.css';

const ProgressTicker = memo(({ 
  jobData,
  isConnected = false,
  lastUpdate = null
}) => {
  const [tickerItems, setTickerItems] = useState([]);
  const [currentStats, setCurrentStats] = useState({
    processed: 0,
    successful: 0,
    failed: 0,
    duplicates: 0
  });

  useEffect(() => {
    if (!jobData) return;

    const newStats = {
      processed: jobData.processed_rows || 0,
      successful: jobData.successful_rows || 0,
      failed: jobData.failed_rows || 0,
      duplicates: jobData.duplicate_rows || 0
    };

    // Check if stats have changed to add new ticker items
    const hasChanged = Object.keys(newStats).some(key => 
      newStats[key] !== currentStats[key]
    );

    if (hasChanged && jobData.status === 'processing') {
      const now = new Date();
      const newItem = {
        id: Date.now(),
        timestamp: now.toLocaleTimeString(),
        stats: { ...newStats },
        changes: {
          processed: newStats.processed - currentStats.processed,
          successful: newStats.successful - currentStats.successful,
          failed: newStats.failed - currentStats.failed,
          duplicates: newStats.duplicates - currentStats.duplicates
        }
      };

      setTickerItems(prev => {
        const updated = [newItem, ...prev].slice(0, 10); // Keep last 10 items
        return updated;
      });
    }

    setCurrentStats(newStats);
  }, [jobData, currentStats]);

  const formatNumber = (num) => {
    return num.toLocaleString();
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'processing':
        return '⚡';
      case 'completed':
        return '✅';
      case 'failed':
        return '❌';
      case 'pending':
        return '⏳';
      default:
        return '❓';
    }
  };

  const getConnectionStatus = () => {
    if (isConnected) {
      return { icon: '🟢', text: 'Live', class: 'connected' };
    }
    return { icon: '🔴', text: 'Offline', class: 'disconnected' };
  };

  const connectionStatus = getConnectionStatus();

  return (
    <div className="progress-ticker">
      <div className="ticker-header">
        <div className="ticker-title">
          <span className="status-icon">{getStatusIcon(jobData?.status)}</span>
          <h4>Real-time Progress</h4>
          <span className={`connection-status ${connectionStatus.class}`}>
            {connectionStatus.icon} {connectionStatus.text}
          </span>
        </div>
        {lastUpdate && (
          <div className="last-update">
            Last update: {new Date(lastUpdate).toLocaleTimeString()}
          </div>
        )}
      </div>

      <div className="current-stats">
        <div className="stats-grid">
          <div className="stat-card processed">
            <div className="stat-icon">📊</div>
            <div className="stat-content">
              <div className="stat-label">Processed</div>
              <div className="stat-value">{formatNumber(currentStats.processed)}</div>
            </div>
          </div>
          <div className="stat-card success">
            <div className="stat-icon">✅</div>
            <div className="stat-content">
              <div className="stat-label">Successful</div>
              <div className="stat-value">{formatNumber(currentStats.successful)}</div>
            </div>
          </div>
          <div className="stat-card failed">
            <div className="stat-icon">❌</div>
            <div className="stat-content">
              <div className="stat-label">Failed</div>
              <div className="stat-value">{formatNumber(currentStats.failed)}</div>
            </div>
          </div>
          <div className="stat-card duplicates">
            <div className="stat-icon">🔄</div>
            <div className="stat-content">
              <div className="stat-label">Duplicates</div>
              <div className="stat-value">{formatNumber(currentStats.duplicates)}</div>
            </div>
          </div>
        </div>
      </div>

      {jobData?.status === 'processing' && tickerItems.length > 0 && (
        <div className="ticker-feed">
          <div className="feed-header">
            <h5>Live Updates</h5>
            <span className="feed-indicator">●</span>
          </div>
          <div className="ticker-items">
            {tickerItems.map((item) => (
              <div key={item.id} className="ticker-item">
                <div className="ticker-timestamp">{item.timestamp}</div>
                <div className="ticker-changes">
                  {item.changes.processed > 0 && (
                    <span className="change processed">
                      +{formatNumber(item.changes.processed)} processed
                    </span>
                  )}
                  {item.changes.successful > 0 && (
                    <span className="change success">
                      +{formatNumber(item.changes.successful)} successful
                    </span>
                  )}
                  {item.changes.failed > 0 && (
                    <span className="change failed">
                      +{formatNumber(item.changes.failed)} failed
                    </span>
                  )}
                  {item.changes.duplicates > 0 && (
                    <span className="change duplicates">
                      +{formatNumber(item.changes.duplicates)} duplicates
                    </span>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {jobData?.status === 'processing' && (
        <div className="processing-indicator">
          <div className="pulse-dot"></div>
          <span>Processing in progress...</span>
        </div>
      )}
    </div>
  );
});

ProgressTicker.displayName = 'ProgressTicker';

export default ProgressTicker;
