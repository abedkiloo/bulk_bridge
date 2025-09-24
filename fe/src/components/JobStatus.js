import React, { useState, useEffect, memo } from 'react';
import ProgressBar from './ProgressBar';
import useJobStream from '../hooks/useJobStream';
import './JobStatus.css';

const JobStatus = memo(({ jobId, onJobComplete }) => {
  const [loading, setLoading] = useState(true);
  
  const {
    jobData: job,
    isConnected,
    error,
    lastUpdate,
    connectionAttempts,
    reconnect,
    fetchJobStatus
  } = useJobStream(jobId);

  useEffect(() => {
    if (jobId) {
      setLoading(false);
    }
  }, [jobId]);

  useEffect(() => {
    // Notify when job is completed or failed
    if (job?.status === 'completed' || job?.status === 'failed') {
      if (onJobComplete) {
        onJobComplete(job);
      }
    }
  }, [job?.status, onJobComplete, job]);

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString();
  };

  const formatFileSize = (bytes) => {
    if (!bytes) return 'N/A';
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
  };

  if (loading) {
    return (
      <div className="job-status-container">
        <div className="loading-state">
          <div className="spinner"></div>
          <p>Loading job status...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="job-status-container">
        <div className="error-state">
          <div className="error-icon">⚠️</div>
          <p>{error}</p>
          <button onClick={fetchJobStatus} className="retry-button">
            Retry
          </button>
        </div>
      </div>
    );
  }

  if (!job) {
    return (
      <div className="job-status-container">
        <div className="no-data-state">
          <p>No job data available</p>
          <button onClick={fetchJobStatus} className="retry-button">
            Refresh
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="job-status-container">
      <div className="job-header">
        <div className="job-title">
          <h3>Import Job: {job.filename}</h3>
          <span className={`job-status-badge status-${job.status}`}>
            {job.status.charAt(0).toUpperCase() + job.status.slice(1)}
          </span>
        </div>
        <div className="job-actions">
          <div className="connection-status">
            <span className={`connection-indicator ${isConnected ? 'connected' : 'disconnected'}`}>
              {isConnected ? '🟢 Live Stream' : '🔴 Disconnected'}
            </span>
            {lastUpdate && (
              <span className="last-update">
                Last update: {new Date(lastUpdate).toLocaleTimeString()}
              </span>
            )}
            {connectionAttempts > 0 && (
              <span className="connection-attempts">
                Attempts: {connectionAttempts}/5
              </span>
            )}
          </div>
          <button onClick={reconnect} className="reconnect-btn">
            🔄 Reconnect
          </button>
          <button onClick={fetchJobStatus} className="refresh-btn">
            📊 Refresh
          </button>
        </div>
      </div>

      <div className="job-details">
        <div className="detail-grid">
          <div className="detail-item">
            <label>Job ID:</label>
            <span className="job-id">{job.job_id}</span>
          </div>
          <div className="detail-item">
            <label>Original Filename:</label>
            <span>{job.original_filename}</span>
          </div>
          <div className="detail-item">
            <label>File Size:</label>
            <span>{formatFileSize(job.file_size)}</span>
          </div>
          <div className="detail-item">
            <label>Created:</label>
            <span>{formatDate(job.created_at)}</span>
          </div>
          <div className="detail-item">
            <label>Started:</label>
            <span>{formatDate(job.started_at)}</span>
          </div>
          <div className="detail-item">
            <label>Completed:</label>
            <span>{formatDate(job.completed_at)}</span>
          </div>
        </div>
      </div>

      <ProgressBar
        totalRows={job.total_rows || 0}
        processedRows={job.processed_rows || 0}
        successfulRows={job.successful_rows || 0}
        failedRows={job.failed_rows || 0}
        status={job.status || 'pending'}
        showDetails={true}
      />

      {job.error_message && (
        <div className="error-message">
          <h4>Error Details:</h4>
          <pre>{job.error_message}</pre>
        </div>
      )}

      {job.status === 'completed' && (
        <div className="completion-summary">
          <h4>Import Summary</h4>
          <div className="summary-stats">
            <div className="summary-item success">
              <span className="summary-label">Successfully Imported:</span>
              <span className="summary-value">{job.successful_rows} rows</span>
            </div>
            {job.failed_rows > 0 && (
              <div className="summary-item error">
                <span className="summary-label">Failed:</span>
                <span className="summary-value">{job.failed_rows} rows</span>
              </div>
            )}
            <div className="summary-item">
              <span className="summary-label">Total Processing Time:</span>
              <span className="summary-value">
                {job.started_at && job.completed_at
                  ? Math.round((new Date(job.completed_at) - new Date(job.started_at)) / 1000)
                  : 'N/A'} seconds
              </span>
            </div>
          </div>
        </div>
      )}
    </div>
  );
});

JobStatus.displayName = 'JobStatus';

export default JobStatus;
