import React, { useState, useEffect } from 'react';
import { bulkBridgeAPI } from '../services/api';
import './UploadHistory.css';

const UploadHistory = ({ isOpen, onClose }) => {
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [selectedJob, setSelectedJob] = useState(null);

  useEffect(() => {
    if (isOpen) {
      fetchJobs();
    }
  }, [isOpen]);

  const fetchJobs = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await bulkBridgeAPI.getJobs();
      setJobs(response.data.data || []);
    } catch (err) {
      setError('Failed to fetch upload history');
      console.error('Error fetching jobs:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleRetry = async (jobId) => {
    try {
      await bulkBridgeAPI.retryJob(jobId);
      fetchJobs(); // Refresh the list
    } catch (err) {
      setError('Failed to retry job');
      console.error('Error retrying job:', err);
    }
  };

  const handleCancel = async (jobId) => {
    try {
      await bulkBridgeAPI.cancelJob(jobId);
      fetchJobs(); // Refresh the list
    } catch (err) {
      setError('Failed to cancel job');
      console.error('Error canceling job:', err);
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'completed': return '✅';
      case 'failed': return '❌';
      case 'processing': return '⏳';
      case 'pending': return '⏸️';
      case 'cancelled': return '🚫';
      default: return '❓';
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'completed': return '#28a745';
      case 'failed': return '#dc3545';
      case 'processing': return '#ffc107';
      case 'pending': return '#6c757d';
      case 'cancelled': return '#6c757d';
      default: return '#6c757d';
    }
  };

  const formatFileSize = (bytes) => {
    if (!bytes) return 'Unknown';
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
  };

  if (!isOpen) return null;

  return (
    <div className="upload-history-overlay">
      <div className="upload-history-modal">
        <div className="upload-history-header">
          <h2>Upload History</h2>
          <button className="close-btn" onClick={onClose}>×</button>
        </div>

        <div className="upload-history-content">
          {loading && <div className="loading">Loading upload history...</div>}
          
          {error && (
            <div className="error">
              {error}
              <button onClick={fetchJobs} className="retry-btn">Retry</button>
            </div>
          )}

          {!loading && !error && (
            <>
              <div className="history-actions">
                <button onClick={fetchJobs} className="refresh-btn">
                  🔄 Refresh
                </button>
              </div>

              {jobs.length === 0 ? (
                <div className="no-data">No uploads found</div>
              ) : (
                <div className="jobs-list">
                  {jobs.map((job) => (
                    <div key={job.job_id} className="job-item">
                      <div className="job-header">
                        <div className="job-status">
                          <span 
                            className="status-icon"
                            style={{ color: getStatusColor(job.status) }}
                          >
                            {getStatusIcon(job.status)}
                          </span>
                          <span className="status-text">{job.status}</span>
                        </div>
                        <div className="job-actions">
                          {job.status === 'failed' && (
                            <button 
                              onClick={() => handleRetry(job.job_id)}
                              className="action-btn retry"
                            >
                              🔄 Retry
                            </button>
                          )}
                          {(job.status === 'pending' || job.status === 'processing') && (
                            <button 
                              onClick={() => handleCancel(job.job_id)}
                              className="action-btn cancel"
                            >
                              🚫 Cancel
                            </button>
                          )}
                        </div>
                      </div>

                      <div className="job-details">
                        <div className="job-info">
                          <div className="info-item">
                            <strong>File:</strong> {job.original_filename}
                          </div>
                          <div className="info-item">
                            <strong>Size:</strong> {formatFileSize(job.metadata?.file_size)}
                          </div>
                          <div className="info-item">
                            <strong>Rows:</strong> {job.total_rows} total, {job.processed_rows} processed
                          </div>
                          <div className="info-item">
                            <strong>Success:</strong> {job.successful_rows} | <strong>Failed:</strong> {job.failed_rows}
                          </div>
                          <div className="info-item">
                            <strong>Created:</strong> {formatDate(job.created_at)}
                          </div>
                          {job.completed_at && (
                            <div className="info-item">
                              <strong>Completed:</strong> {formatDate(job.completed_at)}
                            </div>
                          )}
                        </div>

                        {job.error_message && (
                          <div className="job-error">
                            <strong>Error:</strong> {job.error_message}
                          </div>
                        )}

                        {job.status === 'processing' && (
                          <div className="job-progress">
                            <div className="progress-bar">
                              <div 
                                className="progress-fill"
                                style={{ 
                                  width: `${job.total_rows > 0 ? (job.processed_rows / job.total_rows) * 100 : 0}%` 
                                }}
                              />
                            </div>
                            <span className="progress-text">
                              {job.total_rows > 0 ? Math.round((job.processed_rows / job.total_rows) * 100) : 0}%
                            </span>
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default UploadHistory;
