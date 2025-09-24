import React, { memo } from 'react';
import './ProgressBar.css';

const ProgressBar = memo(({ 
  totalRows = 0, 
  processedRows = 0, 
  successfulRows = 0, 
  failedRows = 0,
  status = 'pending',
  showDetails = true 
}) => {
  // Ensure status is always a string
  const safeStatus = status || 'pending';
  const progressPercentage = totalRows > 0 ? (processedRows / totalRows) * 100 : 0;
  const successPercentage = totalRows > 0 ? (successfulRows / totalRows) * 100 : 0;
  const failurePercentage = totalRows > 0 ? (failedRows / totalRows) * 100 : 0;

  const getStatusColor = (status) => {
    switch (status) {
      case 'completed':
        return '#28a745';
      case 'failed':
        return '#dc3545';
      case 'processing':
        return '#007bff';
      case 'pending':
        return '#6c757d';
      default:
        return '#6c757d';
    }
  };

  const getStatusText = (status) => {
    switch (status) {
      case 'completed':
        return 'Completed';
      case 'failed':
        return 'Failed';
      case 'processing':
        return 'Processing';
      case 'pending':
        return 'Pending';
      default:
        return 'Unknown';
    }
  };

  return (
    <div className="progress-container">
      <div className="progress-header">
        <div className="progress-title">
          <h4>Import Progress</h4>
          <span className={`status-badge status-${safeStatus}`}>
            {getStatusText(safeStatus)}
          </span>
        </div>
        <div className="progress-percentage">
          {Math.round(progressPercentage)}%
        </div>
      </div>

      <div className="progress-bar-container">
        <div className="progress-bar">
          <div 
            className="progress-fill"
            style={{ 
              width: `${progressPercentage}%`,
              backgroundColor: getStatusColor(safeStatus)
            }}
          />
        </div>
      </div>

      {showDetails && (
        <div className="progress-details">
          <div className="progress-stats">
            <div className="stat-item">
              <span className="stat-label">Total Rows:</span>
              <span className="stat-value">{totalRows.toLocaleString()}</span>
            </div>
            <div className="stat-item">
              <span className="stat-label">Processed:</span>
              <span className="stat-value">{processedRows.toLocaleString()}</span>
            </div>
            <div className="stat-item success">
              <span className="stat-label">Successful:</span>
              <span className="stat-value">{successfulRows.toLocaleString()}</span>
            </div>
            <div className="stat-item error">
              <span className="stat-label">Failed:</span>
              <span className="stat-value">{failedRows.toLocaleString()}</span>
            </div>
          </div>

          {totalRows > 0 && (
            <div className="progress-breakdown">
              <div className="breakdown-bar">
                <div 
                  className="breakdown-success"
                  style={{ width: `${successPercentage}%` }}
                  title={`${successfulRows} successful rows`}
                />
                <div 
                  className="breakdown-error"
                  style={{ width: `${failurePercentage}%` }}
                  title={`${failedRows} failed rows`}
                />
                <div 
                  className="breakdown-pending"
                  style={{ width: `${100 - progressPercentage}%` }}
                  title={`${totalRows - processedRows} pending rows`}
                />
              </div>
              <div className="breakdown-legend">
                <div className="legend-item">
                  <div className="legend-color success"></div>
                  <span>Successful ({Math.round(successPercentage)}%)</span>
                </div>
                <div className="legend-item">
                  <div className="legend-color error"></div>
                  <span>Failed ({Math.round(failurePercentage)}%)</span>
                </div>
                <div className="legend-item">
                  <div className="legend-color pending"></div>
                  <span>Pending ({Math.round(100 - progressPercentage)}%)</span>
                </div>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
});

ProgressBar.displayName = 'ProgressBar';

export default ProgressBar;
