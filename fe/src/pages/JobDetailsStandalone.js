import React, { useState, useEffect } from 'react';
import { bulkBridgeAPI } from '../services/api';
import './JobDetailsPage.css';

const JobDetailsStandalone = () => {
  const [job, setJob] = useState(null);
  const [jobDetails, setJobDetails] = useState(null);
  const [importRows, setImportRows] = useState([]);
  const [importErrors, setImportErrors] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Get jobId from URL parameters
  const getJobIdFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('jobId');
  };

  useEffect(() => {
    const jobId = getJobIdFromUrl();
    if (jobId) {
      fetchJobData(jobId);
    } else {
      setError('No job ID provided');
      setLoading(false);
    }
  }, []);

  const fetchJobData = async (jobId) => {
    setLoading(true);
    setError(null);
    try {
      const [jobsResponse, detailsResponse, rowsResponse, errorsResponse] = await Promise.all([
        bulkBridgeAPI.getJobs(),
        bulkBridgeAPI.getJobDetails(jobId),
        bulkBridgeAPI.getImportRows(jobId),
        bulkBridgeAPI.getImportErrors(jobId)
      ]);
      
      // Find the specific job from the jobs list
      const jobs = jobsResponse.data.data || [];
      const foundJob = jobs.find(j => j.job_id === jobId);
      
      if (!foundJob) {
        setError('Job not found');
        return;
      }
      
      setJob(foundJob);
      setJobDetails(detailsResponse.data.data);
      setImportRows(rowsResponse.data.data || []);
      setImportErrors(errorsResponse.data.data || []);
    } catch (err) {
      setError('Failed to fetch job data');
      console.error('Error fetching job data:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleDispatchJob = async () => {
    const jobId = getJobIdFromUrl();
    try {
      await bulkBridgeAPI.dispatchJob(jobId);
      setError(null);
      fetchJobData(jobId); // Refresh the data
    } catch (err) {
      setError('Failed to dispatch job');
      console.error('Error dispatching job:', err);
    }
  };

  const handleRetryJob = async () => {
    const jobId = getJobIdFromUrl();
    try {
      await bulkBridgeAPI.retryJob(jobId);
      setError(null);
      fetchJobData(jobId); // Refresh the data
    } catch (err) {
      setError('Failed to retry job');
      console.error('Error retrying job:', err);
    }
  };

  const handleCancelJob = async () => {
    const jobId = getJobIdFromUrl();
    try {
      await bulkBridgeAPI.cancelJob(jobId);
      setError(null);
      fetchJobData(jobId); // Refresh the data
    } catch (err) {
      setError('Failed to cancel job');
      console.error('Error canceling job:', err);
    }
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString();
  };

  if (loading) {
    return (
      <div className="job-details-page">
        <div className="page-header">
          <button onClick={() => window.close()} className="back-btn">
            ✕ Close
          </button>
          <h1>Job Details</h1>
        </div>
        <div className="loading-state">
          <div className="spinner"></div>
          <p>Loading job details...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="job-details-page">
        <div className="page-header">
          <button onClick={() => window.close()} className="back-btn">
            ✕ Close
          </button>
          <h1>Job Details</h1>
        </div>
        <div className="error-state">
          <p>Error: {error}</p>
          <button onClick={() => fetchJobData(getJobIdFromUrl())} className="retry-btn">
            Retry
          </button>
        </div>
      </div>
    );
  }

  if (!job) {
    return (
      <div className="job-details-page">
        <div className="page-header">
          <button onClick={() => window.close()} className="back-btn">
            ✕ Close
          </button>
          <h1>Job Details</h1>
        </div>
        <div className="error-state">
          <p>Job not found</p>
        </div>
      </div>
    );
  }

  return (
    <div className="job-details-page">
      <div className="page-header">
        <button onClick={() => window.close()} className="back-btn">
          ✕ Close
        </button>
        <h1>Job Details: {job.original_filename}</h1>
        <div className="header-actions">
          {job.status === 'pending' && (
            <button onClick={handleDispatchJob} className="action-btn dispatch">
              Dispatch
            </button>
          )}
          {job.status === 'failed' && (
            <button onClick={handleRetryJob} className="action-btn retry">
              Retry
            </button>
          )}
          {(job.status === 'pending' || job.status === 'processing') && (
            <button onClick={handleCancelJob} className="action-btn cancel">
              Cancel
            </button>
          )}
        </div>
      </div>

      {error && (
        <div className="error-message">
          <p>{error}</p>
        </div>
      )}

      <div className="job-details-content">
        <div className="details-grid">
          <div className="detail-card">
            <h3>Summary</h3>
            <div className="detail-items">
              <div className="detail-item">
                <span className="detail-label">Status:</span>
                <span className={`detail-value status-${job.status}`}>{job.status}</span>
              </div>
              <div className="detail-item">
                <span className="detail-label">Total Rows:</span>
                <span className="detail-value">{(job.total_rows || 0).toLocaleString()}</span>
              </div>
              <div className="detail-item">
                <span className="detail-label">Successful:</span>
                <span className="detail-value success">{(job.successful_rows || 0).toLocaleString()}</span>
              </div>
              <div className="detail-item">
                <span className="detail-label">Failed:</span>
                <span className="detail-value error">{(job.failed_rows || 0).toLocaleString()}</span>
              </div>
              <div className="detail-item">
                <span className="detail-label">Success Rate:</span>
                <span className="detail-value rate">
                  {(job.total_rows || 0) > 0 ? Math.round(((job.successful_rows || 0) / (job.total_rows || 1)) * 100) : 0}%
                </span>
              </div>
            </div>
          </div>

          <div className="detail-card">
            <h3>Job Information</h3>
            <div className="detail-items">
              <div className="detail-item">
                <span className="detail-label">Job ID:</span>
                <span className="detail-value">{job.job_id}</span>
              </div>
              <div className="detail-item">
                <span className="detail-label">File Name:</span>
                <span className="detail-value">{job.original_filename}</span>
              </div>
              <div className="detail-item">
                <span className="detail-label">File Size:</span>
                <span className="detail-value">{job.file_size ? `${(job.file_size / 1024 / 1024).toFixed(2)} MB` : 'N/A'}</span>
              </div>
              <div className="detail-item">
                <span className="detail-label">Created:</span>
                <span className="detail-value">{formatDate(job.created_at)}</span>
              </div>
              <div className="detail-item">
                <span className="detail-label">Completed:</span>
                <span className="detail-value">{formatDate(job.completed_at)}</span>
              </div>
            </div>
          </div>
        </div>

        {importErrors.length > 0 && (
          <div className="errors-section">
            <h3>Import Errors ({importErrors.length})</h3>
            <div className="errors-list">
              {importErrors.slice(0, 10).map((error, index) => (
                <div key={index} className="error-item">
                  <div className="error-header">
                    <span className="error-row">Row {error.row_number}</span>
                    <span className="error-status">{error.status}</span>
                  </div>
                  <div className="error-message">{error.error_message}</div>
                </div>
              ))}
              {importErrors.length > 10 && (
                <div className="more-errors">
                  ... and {importErrors.length - 10} more errors
                </div>
              )}
            </div>
          </div>
        )}

        {importRows.length > 0 && (
          <div className="rows-section">
            <h3>Sample Import Rows ({importRows.length})</h3>
            <div className="rows-table">
              <div className="table-header">
                <div className="table-cell">Row #</div>
                <div className="table-cell">Status</div>
                <div className="table-cell">Data Preview</div>
              </div>
              {importRows.slice(0, 10).map((row, index) => (
                <div key={index} className="table-row">
                  <div className="table-cell">
                    {row.row_number}
                  </div>
                  <div className="table-cell">
                    <span className={`status-badge ${row.status}`}>
                      {row.status}
                    </span>
                  </div>
                  <div className="table-cell">
                    <div className="data-preview">
                      {(() => {
                        try {
                          const data = typeof row.raw_data === 'string' ? JSON.parse(row.raw_data || '{}') : row.raw_data || {};
                          return JSON.stringify(data, null, 2).substring(0, 100) + '...';
                        } catch (e) {
                          return String(row.raw_data || '').substring(0, 100) + '...';
                        }
                      })()}
                    </div>
                  </div>
                </div>
              ))}
              {importRows.length > 10 && (
                <div className="more-rows">
                  ... and {importRows.length - 10} more rows
                </div>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default JobDetailsStandalone;
