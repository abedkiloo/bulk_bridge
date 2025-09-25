import React, { memo } from 'react';
import './PercentageProgressBar.css';

const PercentageProgressBar = memo(({ 
  totalRows = 0, 
  processedRows = 0, 
  status = 'pending',
  showLabel = true 
}) => {
  const progressPercentage = totalRows > 0 ? (processedRows / totalRows) * 100 : 0;
  const roundedPercentage = Math.round(progressPercentage);

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
    <div className="percentage-progress-container" data-status={status}>
      <div className="progress-percentage-only">
        {roundedPercentage}%
      </div>
    </div>
  );
});

PercentageProgressBar.displayName = 'PercentageProgressBar';

export default PercentageProgressBar;
